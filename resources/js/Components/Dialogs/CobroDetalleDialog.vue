<script setup>
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import ComprobanteVisorDialog from '@/Components/Dialogs/ComprobanteVisorDialog.vue';
import { formatPrice, formatPrecios, SIMBOLO_PRINCIPAL } from '@/composables/useActividadHelpers';

const props = defineProps({
    // v-model:visible del diálogo de detalle.
    visible: { type: Boolean, default: false },
    // Lista de cobros del cobrable (inscripción / cuota de membresía).
    cobros: { type: Array, default: () => [] },
    // Subtítulo del header (p.ej. "Juan Pérez · Retiro de Yoga").
    contexto: { type: String, default: '' },
    // Deuda del cobrable, una línea por moneda: [{ moneda_id, monto, simbolo,
    // es_principal }]. Una inscripción de total dividido debe dos cosas distintas
    // (BUSINESS_RULES §2.1bis) y cada una se salda por separado. Sin esto no se
    // puede decir cuánto falta, que en un pago parcial es el dato que importa.
    adeudado: { type: Array, default: () => [] },
    // Id de la moneda principal, para que un cobro legacy (`moneda_id` null) y uno
    // que la trae explícita cuenten como la misma moneda — la convención de toda la
    // app (BUSINESS_RULES §2.1bis). Sin esto se abrirían dos líneas con el mismo
    // símbolo y el saldo se calcularía contra la mitad de lo cobrado.
    monedaPrincipalId: { type: Number, default: null },
});

const emit = defineEmits(['update:visible']);

const visibleProxy = computed({
    get: () => props.visible,
    set: (v) => emit('update:visible', v),
});

const esARevisar = (cobro) => cobro.estado === 'a_revisar';

const cobrosOrdenados = computed(() =>
    [...(props.cobros || [])].sort((a, b) => String(b.fecha_pago || '').localeCompare(String(a.fecha_pago || '')))
);

// --- Formateo (mismo criterio que Cobros/Index.vue) ---
const formatMoney = (value, simbolo) => `${simbolo || SIMBOLO_PRINCIPAL} ${formatPrice(Number(value || 0))}`;

const simboloDe = (cobro) => cobro?.moneda?.simbolo || SIMBOLO_PRINCIPAL;

// Clave de agrupación: la principal es siempre 0, venga como null (legacy) o con su
// id. Cualquier otra moneda va por su id.
const claveMoneda = (monedaId) => {
    if (monedaId === null || monedaId === undefined) return 0;
    if (props.monedaPrincipalId !== null && Number(monedaId) === Number(props.monedaPrincipalId)) return 0;
    return Number(monedaId);
};

// Los totales van por moneda: una inscripción de total dividido tiene un cobro en
// dólares y otro en pesos, y sumarlos daría un número que no existe.
const totalesPorMoneda = (incluir) => {
    const porMoneda = new Map();

    cobrosOrdenados.value.forEach((c) => {
        if (!incluir(c)) return;
        const clave = claveMoneda(c.moneda_id);
        const linea = porMoneda.get(clave) ?? {
            precio: 0,
            simbolo: simboloDe(c),
            esPrincipal: c.moneda ? Boolean(c.moneda.es_principal) : true,
        };
        linea.precio += Number(c.monto || 0);
        porMoneda.set(clave, linea);
    });

    return [...porMoneda.values()].sort((a, b) => Number(b.esPrincipal) - Number(a.esPrincipal));
};

// El total sólo suma plata verificada; lo informado "a revisar" se muestra aparte.
const totalCobrado = computed(
    () => formatPrecios(totalesPorMoneda((c) => !esARevisar(c))) || formatMoney(0)
);

const hayARevisar = computed(() => cobrosOrdenados.value.some((c) => esARevisar(c) && Number(c.monto || 0) > 0));
const totalARevisar = computed(() => formatPrecios(totalesPorMoneda(esARevisar)));

// El monto de un cobro a revisar es de una de dos clases: lo que declaró quien pagó,
// o el saldo puesto de oficio al subir el comprobante. Rotularlos igual hacía pasar
// un número provisional por un dato informado.
const hayMontoDeclarado = computed(() =>
    cobrosOrdenados.value.some((c) => esARevisar(c) && c.monto_declarado)
);
const rotuloARevisar = computed(() =>
    hayMontoDeclarado.value ? 'Informado por quien pagó' : 'A revisar (saldo estimado)'
);

// Cuánto falta, por moneda: la porción adeudada menos los cobros CONFIRMADOS de esa
// moneda (los a revisar no son plata verificada). Mismo criterio que
// CobroService::saldoPendientePorMoneda(), pero calculado acá a propósito: el método
// dispara una query por cobrable y el listado trae ~2.000 filas. Los datos ya viajan.
const faltaCobrar = computed(() => {
    const confirmado = new Map();
    cobrosOrdenados.value.forEach((c) => {
        if (esARevisar(c)) return;
        const clave = claveMoneda(c.moneda_id);
        confirmado.set(clave, (confirmado.get(clave) || 0) + Number(c.monto || 0));
    });

    return (props.adeudado || [])
        .map((porcion) => ({
            simbolo: porcion.simbolo || SIMBOLO_PRINCIPAL,
            esPrincipal: Boolean(porcion.es_principal),
            precio: Math.max(
                0,
                Number(porcion.monto || 0) - (confirmado.get(claveMoneda(porcion.moneda_id)) || 0)
            ),
        }))
        .filter((linea) => linea.precio > 0.001)
        .sort((a, b) => Number(b.esPrincipal) - Number(a.esPrincipal));
});
const hayFaltante = computed(() => faltaCobrar.value.length > 0);
const totalFaltante = computed(() => formatPrecios(faltaCobrar.value));

const formatDate = (value) => {
    if (!value) return '—';
    const fecha = new Date(value);
    if (!Number.isNaN(fecha.getTime())) {
        return fecha.toLocaleDateString('es-AR', { day: 'numeric', month: 'long', year: 'numeric' });
    }
    return String(value).split('T')[0];
};

// Campos de detalle de un cobro (se renderizan en una grilla; los vacíos muestran "—").
const camposDe = (cobro) => [
    { label: 'Medio de pago', value: cobro.metodo_pago?.nombre },
    { label: 'Referencia', value: cobro.referencia },
    { label: 'Registrado por', value: cobro.registrador?.name },
    { label: 'Moneda', value: cobro.moneda?.nombre },
];

// --- Origen (etiqueta + color de badge) ---
const ORIGEN_LABEL = {
    manual: 'Manual',
    checkout: 'Checkout',
    mercadopago: 'Mercado Pago',
    importacion: 'Importación',
    backfill: 'Migración',
    membresia: 'Membresía',
    pos: 'POS',
};
const origenLabel = (o) => ORIGEN_LABEL[o] || o || '—';
const origenClass = (o) => ({
    manual: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    checkout: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
    mercadopago: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    importacion: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    backfill: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    membresia: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
    pos: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
}[o] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300');

// --- Visor de comprobante (2º diálogo, apilado) ---
const comprobanteVisible = ref(false);
const comprobantePath = ref('');
const comprobanteDescripcion = ref('');

const verComprobante = (comp) => {
    if (!comp?.ruta) return;
    comprobantePath.value = comp.ruta;
    comprobanteDescripcion.value = comp.descripcion || '';
    comprobanteVisible.value = true;
};
</script>

<template>
    <!-- Diálogo 1: detalle del/los cobro(s) -->
    <Dialog
        v-model:visible="visibleProxy"
        modal
        header="Detalle del pago"
        :style="{ width: '40rem' }"
        :breakpoints="{ '575px': '95vw' }"
        dismissableMask
    >
        <p v-if="contexto" class="-mt-1 mb-4 text-sm text-gray-500 dark:text-gray-400">{{ contexto }}</p>

        <div v-if="cobrosOrdenados.length" class="max-h-[70vh] space-y-3 overflow-y-auto pr-0.5">
            <div
                v-for="cobro in cobrosOrdenados"
                :key="cobro.id"
                class="overflow-hidden rounded-xl border"
                :class="esARevisar(cobro) ? 'border-amber-300 dark:border-amber-500/50' : 'border-gray-200 dark:border-gray-700'"
            >
                <!-- Cabecera: monto + fecha + estado/origen -->
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/60">
                    <div>
                        <p
                            class="text-2xl font-bold leading-none"
                            :class="esARevisar(cobro) ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'"
                        >
                            {{ formatMoney(cobro.monto, simboloDe(cobro)) }}
                        </p>
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <i class="far fa-calendar"></i>
                            <template v-if="!esARevisar(cobro)">{{ formatDate(cobro.fecha_pago) }}</template>
                            <template v-else-if="cobro.monto_declarado">Importe informado, pendiente de verificación</template>
                            <template v-else>Pendiente de verificación · importe estimado sobre el saldo</template>
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                        <span
                            v-if="cobro.estado"
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="esARevisar(cobro)
                                ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
                                : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'"
                        >
                            {{ esARevisar(cobro) ? 'A revisar' : 'Confirmado' }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="origenClass(cobro.origen)"
                        >
                            {{ origenLabel(cobro.origen) }}
                        </span>
                    </div>
                </div>

                <!-- Detalle: grilla de campos -->
                <div class="grid grid-cols-1 gap-x-6 gap-y-3 px-4 py-3 sm:grid-cols-2">
                    <div v-for="campo in camposDe(cobro)" :key="campo.label">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ campo.label }}</p>
                        <p
                            class="mt-0.5 text-sm"
                            :class="campo.value ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400'"
                        >
                            {{ campo.value || '—' }}
                        </p>
                    </div>
                    <div v-if="cobro.observaciones" class="sm:col-span-2">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Observaciones</p>
                        <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300">{{ cobro.observaciones }}</p>
                    </div>
                </div>

                <!-- Comprobante(s) -->
                <div
                    v-if="cobro.comprobantes?.length"
                    class="flex flex-wrap gap-2 border-t border-gray-100 px-4 py-3 dark:border-gray-700"
                >
                    <button
                        v-for="(comp, idx) in cobro.comprobantes"
                        :key="comp.id"
                        type="button"
                        @click="verComprobante(comp)"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                    >
                        <i class="fas fa-file-invoice"></i>
                        Ver comprobante{{ cobro.comprobantes.length > 1 ? ` ${idx + 1}` : '' }}
                    </button>
                </div>
            </div>

            <!-- Totales (solo si hay más de un cobro, monto a revisar o saldo abierto) -->
            <div
                v-if="cobrosOrdenados.length > 1 || hayARevisar || hayFaltante"
                class="space-y-1 rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-gray-800/60"
            >
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-600 dark:text-gray-300">Total cobrado</span>
                    <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ totalCobrado }}</span>
                </div>
                <div v-if="hayARevisar" class="flex items-center justify-between">
                    <span class="font-medium text-gray-600 dark:text-gray-300">{{ rotuloARevisar }}</span>
                    <span class="font-semibold text-amber-600 dark:text-amber-400">{{ totalARevisar }}</span>
                </div>
                <div v-if="hayFaltante" class="flex items-center justify-between border-t border-gray-200 pt-1 dark:border-gray-700">
                    <span class="font-medium text-gray-600 dark:text-gray-300">Falta cobrar</span>
                    <span class="font-semibold text-rose-600 dark:text-rose-400">{{ totalFaltante }}</span>
                </div>
            </div>
        </div>

        <div v-else class="py-10 text-center text-sm text-gray-500">
            <i class="far fa-folder-open mb-2 block text-2xl text-gray-300"></i>
            Sin cobros registrados.
        </div>
    </Dialog>

    <!-- Diálogo 2: visor del comprobante (imagen o PDF) -->
    <ComprobanteVisorDialog v-model:visible="comprobanteVisible" :path="comprobantePath" :descripcion="comprobanteDescripcion" />
</template>
