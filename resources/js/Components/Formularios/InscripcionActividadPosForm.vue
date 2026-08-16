<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
    form: { type: Object, required: true },
    actividades: { type: Array, default: () => [] },
    provincias: { type: Array, default: () => [] },
    municipios: { type: Array, default: () => [] },
    barrios: { type: Array, default: () => [] },
    buttonLabel: { type: String, default: 'Agregar al carrito' },
});

const emit = defineEmits(['submit']);

const datos = ref({ grabacion: null, comidas: [], transportes: [], hospedajes: [], monedas: [] });
const cargandoDatos = ref(false);
const cotizando = ref(false);

// Multi-moneda: la actividad es lo único que se puede vender en otra moneda. Si su
// esquema tiene más de una, el POS deja elegir y cotiza en esa moneda; los servicios
// sin precio en ella se cobran en pesos aparte (total dividido).
const monedasDisponibles = computed(() => datos.value.monedas || []);
const mostrarSelectorMoneda = computed(() => monedasDisponibles.value.length > 1);
const monedaElegida = computed(() =>
    monedasDisponibles.value.find((m) => m.id === Number(props.form.moneda_id))
    || monedasDisponibles.value.find((m) => m.es_principal)
    || null
);
const simboloMoneda = computed(() => monedaElegida.value?.simbolo || '$');
const simboloPrincipal = computed(() => monedasDisponibles.value.find((m) => m.es_principal)?.simbolo || '$');
const hayPorcionEnPesos = computed(() => Number(props.form.montoMonedaPrincipal || 0) > 0);

const formatImporte = (valor, simbolo) =>
    `${simbolo} ${new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(valor || 0))}`;

const totalTexto = computed(() => {
    const enMoneda = formatImporte(props.form.montoApagar, simboloMoneda.value);
    return hayPorcionEnPesos.value
        ? `${enMoneda} + ${formatImporte(props.form.montoMonedaPrincipal, simboloPrincipal.value)}`
        : enMoneda;
});

const municipiosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id, 10);
    if (!provId || Number.isNaN(provId)) return [];
    return (props.municipios || []).filter((m) => parseInt(m.provincia_id, 10) === provId);
});
const barriosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id, 10);
    if (provId !== 24) return [];
    return (props.barrios || []).filter((b) => parseInt(b.provincia_id, 10) === 24);
});

/** Trae servicios y monedas de la actividad, ya con los precios de la moneda elegida. */
const traerDatosActividad = async () => {
    const { data } = await axios.get(route('pos.actividad-datos', props.form.actividad_id), {
        params: { moneda_id: props.form.moneda_id || undefined },
    });
    datos.value = data.actividad || { grabacion: null, comidas: [], transportes: [], hospedajes: [], monedas: [] };

    // Si la moneda elegida no está en esta actividad, vuelve a la principal.
    const monedas = datos.value.monedas || [];
    if (!monedas.some((m) => m.id === Number(props.form.moneda_id))) {
        props.form.moneda_id = monedas.find((m) => m.es_principal)?.id ?? null;
    }
    // El carrito necesita el símbolo al agregar el ítem.
    props.form.monedas = monedas;
};

const conDatos = async (accion) => {
    cargandoDatos.value = true;
    try {
        await accion();
    } catch (e) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'No se pudieron cargar los servicios.', showConfirmButton: false, timer: 2500 });
    } finally {
        cargandoDatos.value = false;
        cotizar();
    }
};

/** Cambio de actividad: limpia lo elegido y arranca de cero. */
const cargarDatosActividad = async () => {
    props.form.incluye_grabacion = false;
    props.form.comidas_ids = [];
    props.form.transportes_ids = [];
    props.form.hospedajes_ids = [];
    datos.value = { grabacion: null, comidas: [], transportes: [], hospedajes: [], monedas: [] };

    if (!props.form.actividad_id) {
        props.form.montoApagar = 0;
        props.form.montoMonedaPrincipal = 0;
        return;
    }

    await conDatos(traerDatosActividad);
};

/** Cambio de moneda: sólo refresca precios, conservando lo que ya se eligió. */
const cargarPreciosYCotizar = () => conDatos(traerDatosActividad);

const cotizar = async () => {
    if (!props.form.actividad_id) { props.form.montoApagar = 0; return; }
    cotizando.value = true;
    try {
        const { data } = await axios.post(route('pos.cotizar-actividad'), {
            actividad_id: props.form.actividad_id,
            email: props.form.email,
            moneda_id: props.form.moneda_id || null,
            incluye_grabacion: props.form.incluye_grabacion,
            comidas_ids: props.form.comidas_ids,
            transportes_ids: props.form.transportes_ids,
            hospedajes_ids: props.form.hospedajes_ids,
        });
        props.form.montoActividad = data.montoActividad;
        props.form.montoApagar = data.montoApagar;
        props.form.montoMonedaPrincipal = data.montoMonedaPrincipal ?? 0;
        props.form.membresia = data.membresia;
    } catch (e) {
        // dejar el monto anterior
    } finally {
        cotizando.value = false;
    }
};

watch(() => props.form.actividad_id, cargarDatosActividad);
// Cambiar de moneda recarga los precios de los servicios (cada uno puede tener el
// suyo) y vuelve a cotizar.
watch(() => props.form.moneda_id, () => { if (props.form.actividad_id) cargarPreciosYCotizar(); });
watch(
    () => [props.form.incluye_grabacion, props.form.comidas_ids, props.form.transportes_ids, props.form.hospedajes_ids, props.form.email],
    () => cotizar(),
    { deep: true }
);

const onSubmit = () => emit('submit');
</script>

<template>
    <form @submit.prevent="onSubmit" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Actividad</label>
            <select v-model="form.actividad_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" required>
                <option value="">Seleccione</option>
                <option v-for="a in actividades" :key="a.id" :value="a.id">{{ a.nombre }}</option>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email participante</label>
                <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" required />
                <p class="mt-1 text-xs text-gray-500">Si el email corresponde a un socio, el precio usa su membresía.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                <input v-model="form.nombre" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provincia</label>
                <select v-model="form.provincia_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="p in provincias" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
            </div>
            <div v-if="municipiosFiltrados.length">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio</label>
                <select v-model="form.municipio_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="m in municipiosFiltrados" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                </select>
            </div>
            <div v-if="barriosFiltrados.length">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barrio</label>
                <select v-model="form.barrio_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="b in barriosFiltrados" :key="b.id" :value="b.id">{{ b.nombre }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                <input v-model="form.telefono" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" />
            </div>
            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input v-model="form.registrar_datos" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm" />
                    Registrar los datos del participante (crea usuario)
                </label>
            </div>
        </div>

        <!-- Moneda: sólo si la actividad está cotizada en más de una -->
        <div v-if="mostrarSelectorMoneda" class="rounded-md border border-amber-200 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20 p-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moneda de la actividad</label>
            <select v-model="form.moneda_id" class="mt-1 block w-full md:w-64 rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                <option v-for="m in monedasDisponibles" :key="m.id" :value="m.id">
                    {{ m.nombre }} ({{ m.simbolo }})
                </option>
            </select>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                Sólo las actividades pueden cobrarse en otra moneda; el resto del carrito va en {{ simboloPrincipal }}.
                Los servicios sin precio en la moneda elegida se cobran en {{ simboloPrincipal }} y se saldan por separado.
            </p>
        </div>

        <!-- Servicios -->
        <div v-if="form.actividad_id" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="datos.grabacion" class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input v-model="form.incluye_grabacion" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm" />
                    Incluir grabación ({{ datos.grabacion.nombre }})
                </label>
            </div>
            <div v-if="datos.comidas.length">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comidas</label>
                <MultiSelect v-model="form.comidas_ids" :options="datos.comidas" optionLabel="nombre" optionValue="id" display="chip" placeholder="Seleccione" class="mt-1 w-full border border-gray-300 dark:border-gray-600 rounded-md" />
            </div>
            <div v-if="datos.transportes.length">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transportes</label>
                <MultiSelect v-model="form.transportes_ids" :options="datos.transportes" optionLabel="nombre" optionValue="id" display="chip" placeholder="Seleccione" class="mt-1 w-full border border-gray-300 dark:border-gray-600 rounded-md" />
            </div>
            <div v-if="datos.hospedajes.length">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hospedajes</label>
                <MultiSelect v-model="form.hospedajes_ids" :options="datos.hospedajes" optionLabel="nombre" optionValue="id" display="chip" placeholder="Seleccione" class="mt-1 w-full border border-gray-300 dark:border-gray-600 rounded-md" />
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-3">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                Total: <span class="text-lg font-bold text-emerald-600">{{ totalTexto }}</span>
                <span v-if="cotizando" class="ml-2 text-xs text-gray-400">calculando…</span>
                <p v-if="hayPorcionEnPesos" class="text-xs text-gray-500 mt-0.5">
                    Incluye servicios sin precio en {{ simboloMoneda }}, que se cobran en {{ simboloPrincipal }}.
                </p>
            </div>
            <button type="submit" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" :disabled="!form.actividad_id || !form.email || cotizando">
                {{ buttonLabel }}
            </button>
        </div>
    </form>
</template>
