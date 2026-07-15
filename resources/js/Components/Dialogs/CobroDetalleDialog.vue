<script setup>
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';

const props = defineProps({
    // v-model:visible del diálogo de detalle.
    visible: { type: Boolean, default: false },
    // Lista de cobros del cobrable (inscripción / cuota de membresía).
    cobros: { type: Array, default: () => [] },
    // Subtítulo del header (p.ej. "Juan Pérez · Retiro de Yoga").
    contexto: { type: String, default: '' },
});

const emit = defineEmits(['update:visible']);

const visibleProxy = computed({
    get: () => props.visible,
    set: (v) => emit('update:visible', v),
});

const cobrosOrdenados = computed(() =>
    [...(props.cobros || [])].sort((a, b) => String(b.fecha_pago || '').localeCompare(String(a.fecha_pago || '')))
);

const totalCobrado = computed(() =>
    cobrosOrdenados.value.reduce((acc, c) => acc + Number(c.monto || 0), 0)
);

// --- Formateo (mismo criterio que Cobros/Index.vue) ---
const formatMoney = (value) => new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
}).format(Number(value || 0));

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
    { label: 'Moneda', value: cobro.moneda ? (cobro.moneda.codigo || cobro.moneda.nombre) : null },
];

// --- Origen (etiqueta + color de badge) ---
const ORIGEN_LABEL = {
    manual: 'Manual',
    import: 'Importación',
    backfill: 'Migración',
    webhook: 'Online',
    membresia: 'Membresía',
};
const origenLabel = (o) => ORIGEN_LABEL[o] || o || '—';
const origenClass = (o) => ({
    manual: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    import: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    backfill: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    webhook: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    membresia: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
}[o] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300');

// --- Visor de comprobante (2º diálogo, apilado) ---
const comprobanteVisible = ref(false);
const comprobantePath = ref('');
const comprobanteIsPdf = computed(() => (comprobantePath.value || '').toLowerCase().endsWith('.pdf'));

const verComprobante = (ruta) => {
    if (!ruta) return;
    comprobantePath.value = ruta;
    comprobanteVisible.value = true;
};

// Fallback autocontenido (data-URI) si la imagen del comprobante está rota.
const IMAGEN_FALLBACK = 'data:image/svg+xml;utf8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">' +
    '<rect width="200" height="200" fill="#e5e7eb"/>' +
    '<text x="100" y="105" font-family="sans-serif" font-size="14" fill="#6b7280" text-anchor="middle">Sin imagen</text>' +
    '</svg>'
);
const onImgError = (event) => {
    const el = event?.target;
    if (!el || el.dataset.fallback) return;
    el.dataset.fallback = '1';
    el.src = IMAGEN_FALLBACK;
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
                class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700"
            >
                <!-- Cabecera: monto + fecha + origen -->
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/60">
                    <div>
                        <p class="text-2xl font-bold leading-none text-emerald-600 dark:text-emerald-400">
                            {{ formatMoney(cobro.monto) }}
                        </p>
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <i class="far fa-calendar"></i>
                            {{ formatDate(cobro.fecha_pago) }}
                        </p>
                    </div>
                    <span
                        class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="origenClass(cobro.origen)"
                    >
                        {{ origenLabel(cobro.origen) }}
                    </span>
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
                        @click="verComprobante(comp.ruta)"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20"
                    >
                        <i class="fas fa-file-invoice"></i>
                        Ver comprobante{{ cobro.comprobantes.length > 1 ? ` ${idx + 1}` : '' }}
                    </button>
                </div>
            </div>

            <!-- Total (solo si hay más de un cobro) -->
            <div
                v-if="cobrosOrdenados.length > 1"
                class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 text-sm dark:bg-gray-800/60"
            >
                <span class="font-medium text-gray-600 dark:text-gray-300">Total cobrado</span>
                <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ formatMoney(totalCobrado) }}</span>
            </div>
        </div>

        <div v-else class="py-10 text-center text-sm text-gray-500">
            <i class="far fa-folder-open mb-2 block text-2xl text-gray-300"></i>
            Sin cobros registrados.
        </div>
    </Dialog>

    <!-- Diálogo 2: visor del comprobante (imagen o PDF) -->
    <Dialog
        v-model:visible="comprobanteVisible"
        modal
        header="Comprobante"
        :style="{ width: '600px' }"
        :breakpoints="{ '575px': '95vw' }"
        dismissableMask
    >
        <div class="max-h-[70vh] overflow-y-auto">
            <template v-if="comprobanteIsPdf">
                <iframe :src="'/storage/' + comprobantePath" class="w-full h-[60vh] rounded"></iframe>
            </template>
            <template v-else>
                <img
                    v-if="comprobantePath"
                    :src="'/storage/' + comprobantePath"
                    class="w-full rounded"
                    alt="Comprobante"
                    @error="onImgError"
                />
            </template>
        </div>
    </Dialog>
</template>
