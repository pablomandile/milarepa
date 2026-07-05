<script>
export default {
    name: 'OracionCantadaForm'
}
</script>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import InputSwitch from 'primevue/inputswitch';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    updating: {
        type: Boolean,
        required: true,
        default: false
    },
    hideHeader: {
        type: Boolean,
        default: false
    },
    streams: {
        type: Array,
        default: () => []
    },
    modalidades: {
        type: Array,
        default: () => []
    },
})

defineEmits(['submit'])

const periodicidades = [
    { label: 'Diaria', value: 'Diaria' },
    { label: 'Mensual', value: 'Mensual' },
];

const mesesOptions = [
    { label: 'Enero', value: 1 },
    { label: 'Febrero', value: 2 },
    { label: 'Marzo', value: 3 },
    { label: 'Abril', value: 4 },
    { label: 'Mayo', value: 5 },
    { label: 'Junio', value: 6 },
    { label: 'Julio', value: 7 },
    { label: 'Agosto', value: 8 },
    { label: 'Septiembre', value: 9 },
    { label: 'Octubre', value: 10 },
    { label: 'Noviembre', value: 11 },
    { label: 'Diciembre', value: 12 },
];

const diasSemanaOptions = [
    { label: 'Lunes', value: 'lunes' },
    { label: 'Martes', value: 'martes' },
    { label: 'Miercoles', value: 'miercoles' },
    { label: 'Jueves', value: 'jueves' },
    { label: 'Viernes', value: 'viernes' },
    { label: 'Sabado', value: 'sabado' },
    { label: 'Domingo', value: 'domingo' },
];

const allDaysValues = diasSemanaOptions.map(item => item.value);
const isDiaria = computed(() => props.form.periodicidad === 'Diaria');
const configuracionesPorMes = computed(() => Array.isArray(props.form.configuracion_por_mes) ? props.form.configuracion_por_mes : []);
const modalidadSeleccionada = computed(() => {
    const selectedId = props.form.modalidad_id;
    return (props.modalidades || []).find((item) => String(item?.id) === String(selectedId)) || null;
});
const isModalidadPresencial = computed(() => {
    const nombre = modalidadSeleccionada.value?.nombre;
    return typeof nombre === 'string' && nombre.trim().toLowerCase() === 'presencial';
});

const allDaysChecked = computed({
    get() {
        const selected = Array.isArray(props.form.dias_semana) ? props.form.dias_semana : [];
        return allDaysValues.every(day => selected.includes(day));
    },
    set(checked) {
        props.form.dias_semana = checked ? [...allDaysValues] : [];
    }
});

function nuevaConfiguracionPorMes() {
    return {
        mes: null,
        periodicidad: props.form.periodicidad || 'Mensual',
        dia: props.form.dia || 1,
        dias_semana: Array.isArray(props.form.dias_semana) ? [...props.form.dias_semana] : [],
        hora: props.form.hora || '08:00',
        horarios_por_dia: {},
    };
}

function agregarConfiguracionPorMes() {
    if (!Array.isArray(props.form.configuracion_por_mes)) {
        props.form.configuracion_por_mes = [];
    }

    props.form.configuracion_por_mes.push(nuevaConfiguracionPorMes());
}

function quitarConfiguracionPorMes(index) {
    props.form.configuracion_por_mes.splice(index, 1);
}

function isConfiguracionDiaria(configuracion) {
    return configuracion?.periodicidad === 'Diaria';
}

// ===== Excepciones por fecha =====
// Overrides puntuales sobre una fecha concreta: cambian la hora de ese dia o traen
// un mensaje (ej. "Centro cerrado"). El select de fecha se arma con las fechas que la
// configuracion efectiva de ese mes genera (base, u override mensual si existe).
const mensajeSugerencias = ['Centro cerrado', 'Sin actividad'];
const weekdayJsToNombre = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
const excepcionesPorFecha = computed(() => Array.isArray(props.form.excepciones_por_fecha) ? props.form.excepciones_por_fecha : []);

const pad2 = (n) => String(n).padStart(2, '0');
const anioActual = new Date().getFullYear();

// Opciones de anio para el select de una excepcion. Rango [actual-1 .. actual+2] mas
// el anio ya guardado (edicion) para que siga siendo seleccionable si cae fuera.
function opcionesAnioParaExcepcion(excepcion) {
    const set = new Set([anioActual - 1, anioActual, anioActual + 1, anioActual + 2]);
    if (excepcion?.anio) set.add(Number(excepcion.anio));
    return Array.from(set)
        .sort((a, b) => a - b)
        .map((y) => ({ label: String(y), value: y }));
}

function configEfectivaDelMes(mes) {
    const override = (props.form.configuracion_por_mes || []).find((c) => Number(c?.mes) === Number(mes));
    if (override && override.periodicidad) {
        return {
            periodicidad: override.periodicidad,
            dia: override.dia,
            dias_semana: Array.isArray(override.dias_semana) ? override.dias_semana : [],
        };
    }
    return {
        periodicidad: props.form.periodicidad,
        dia: props.form.dia,
        dias_semana: Array.isArray(props.form.dias_semana) ? props.form.dias_semana : [],
    };
}

function labelFecha(dia, nombreDia) {
    const label = nombreDia ? nombreDia.charAt(0).toUpperCase() + nombreDia.slice(1) : '';
    return `${label} ${dia}`.trim();
}

function fechasCandidatasDelMes(mes, anio) {
    if (!mes) return [];
    const year = anio || anioActual;
    const config = configEfectivaDelMes(mes);
    const diasEnMes = new Date(year, mes, 0).getDate();
    const opciones = [];

    if (config.periodicidad === 'Mensual') {
        let dia = Number(config.dia || 0);
        if (dia === 29 && diasEnMes === 28) dia = 28;
        if (dia >= 1 && dia <= diasEnMes) {
            const nombreDia = weekdayJsToNombre[new Date(year, mes - 1, dia).getDay()];
            opciones.push({ value: `${year}-${pad2(mes)}-${pad2(dia)}`, label: labelFecha(dia, nombreDia) });
        }
        return opciones;
    }

    if (config.periodicidad !== 'Diaria') return [];

    const dias = config.dias_semana || [];
    if (!dias.length) return [];

    for (let d = 1; d <= diasEnMes; d++) {
        const nombreDia = weekdayJsToNombre[new Date(year, mes - 1, d).getDay()];
        if (!dias.includes(nombreDia)) continue;
        opciones.push({ value: `${year}-${pad2(mes)}-${pad2(d)}`, label: labelFecha(d, nombreDia) });
    }
    return opciones;
}

// Opciones del select de fecha de una excepcion. Usa el anio elegido en la fila (o el
// de la fecha ya guardada como fallback) y garantiza que la fecha guardada siga siendo
// seleccionable aunque ya no matchee la configuracion base.
function opcionesFechaParaExcepcion(excepcion) {
    const anio = Number(excepcion?.anio)
        || (excepcion?.fecha && /^\d{4}-/.test(excepcion.fecha) ? Number(excepcion.fecha.slice(0, 4)) : anioActual);
    let opciones = fechasCandidatasDelMes(excepcion?.mes, anio);

    if (excepcion?.fecha && !opciones.some((o) => o.value === excepcion.fecha)) {
        const [y, m, d] = excepcion.fecha.split('-').map(Number);
        const nombreDia = weekdayJsToNombre[new Date(y, m - 1, d).getDay()];
        opciones = [{ value: excepcion.fecha, label: labelFecha(d, nombreDia) }, ...opciones];
    }
    return opciones;
}

function nuevaExcepcionPorFecha() {
    return { anio: anioActual, mes: null, fecha: null, hora: '', mensaje: '' };
}

function agregarExcepcionPorFecha() {
    if (!Array.isArray(props.form.excepciones_por_fecha)) {
        props.form.excepciones_por_fecha = [];
    }
    props.form.excepciones_por_fecha.push(nuevaExcepcionPorFecha());
}

function quitarExcepcionPorFecha(index) {
    props.form.excepciones_por_fecha.splice(index, 1);
}

const selectedImageFile = ref(null);
const selectedImagePreview = ref(null);
const isUploadingImage = ref(false);
const imageUploadError = ref('');

const currentImagePreview = computed(() => {
    if (selectedImagePreview.value) return selectedImagePreview.value;
    return props.form.imagen || null;
});

function onImageFileSelected(event) {
    const file = event.target.files?.[0];
    imageUploadError.value = '';

    if (!file) return;

    if (selectedImagePreview.value) {
        URL.revokeObjectURL(selectedImagePreview.value);
    }

    selectedImageFile.value = file;
    selectedImagePreview.value = URL.createObjectURL(file);
}

async function uploadSelectedImage() {
    if (!selectedImageFile.value || isUploadingImage.value) return;

    imageUploadError.value = '';
    isUploadingImage.value = true;

    try {
        const data = new FormData();
        data.append('imagen', selectedImageFile.value);
        data.append('folder', 'img/puyas');

        const response = await axios.post(route('upload.store'), data, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        props.form.imagen = response.data?.filePath || '';
        selectedImageFile.value = null;
    } catch (error) {
        imageUploadError.value = error?.response?.data?.error || 'No se pudo subir la imagen.';
    } finally {
        isUploadingImage.value = false;
    }
}

function clearSelectedImage() {
    imageUploadError.value = '';
    selectedImageFile.value = null;

    if (selectedImagePreview.value) {
        URL.revokeObjectURL(selectedImagePreview.value);
        selectedImagePreview.value = null;
    }
}

onUnmounted(() => {
    if (selectedImagePreview.value) {
        URL.revokeObjectURL(selectedImagePreview.value);
    }
});

watch(
    isModalidadPresencial,
    (isPresencial) => {
        if (isPresencial) {
            props.form.stream_id = null;
        }
    },
    { immediate: true }
);
</script>

<template>
    <FormSection :no-aside="hideHeader" @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Oracion Cantada' : 'Nueva Oracion Cantada' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando la oracion cantada seleccionada' : 'Agregando una nueva oracion cantada al sistema.' }}
                </template>
            </SectionTitle>
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true" />
                <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="descripcion" value="Descripcion" :required="true" />
                <Textarea
                    id="descripcion"
                    v-model="form.descripcion"
                    rows="4"
                    autoResize
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>

            <div class="col-span-6 mt-3" :class="isDiaria ? 'sm:col-span-6' : 'sm:col-span-3'">
                <InputLabel for="periodicidad" value="Periodicidad" :required="true" />
                <Dropdown
                    id="periodicidad"
                    v-model="form.periodicidad"
                    :options="periodicidades"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Seleccione una periodicidad"
                    class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                />
                <InputError :message="$page.props.errors.periodicidad" class="mt-2" />
            </div>

            <div v-if="!isDiaria" class="col-span-6 sm:col-span-3 mt-3">
                <InputLabel for="dia" value="Dia" :required="true" />
                <TextInput id="dia" v-model="form.dia" type="number" min="1" max="31" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.dia" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3 mt-3">
                <InputLabel for="hora" value="Hora" :required="true" />
                <input
                    id="hora"
                    v-model="form.hora"
                    type="time"
                    step="60"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError :message="$page.props.errors.hora" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3 mt-3">
                <InputLabel for="modalidad_id" value="Modalidad" />
                <Dropdown
                    id="modalidad_id"
                    v-model="form.modalidad_id"
                    :options="modalidades"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione una modalidad"
                    class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                    showClear
                />
                <InputError :message="$page.props.errors.modalidad_id" class="mt-2" />
            </div>

            <div v-if="!isModalidadPresencial" class="col-span-6 sm:col-span-3 mt-3">
                <InputLabel for="stream_id" value="Stream" />
                <Dropdown
                    id="stream_id"
                    v-model="form.stream_id"
                    :options="streams"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione un stream"
                    class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                    showClear
                />
                <InputError :message="$page.props.errors.stream_id" class="mt-2" />
            </div>

            <div v-if="isDiaria" class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel value="Dias de la semana" :required="true" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Para cada dia podes definir un horario propio. Si lo dejas vacio, se usa la Hora general.
                </p>

                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="diaOption in diasSemanaOptions"
                        :key="diaOption.value"
                        class="rounded border border-gray-200 dark:border-gray-700 px-3 py-2"
                    >
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.dias_semana"
                                type="checkbox"
                                :value="diaOption.value"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ diaOption.label }}</span>
                        </label>
                        <input
                            v-if="form.dias_semana.includes(diaOption.value)"
                            v-model="form.horarios_por_dia[diaOption.value]"
                            type="time"
                            step="60"
                            :placeholder="form.hora"
                            class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <label class="mt-3 inline-flex items-center gap-2 rounded border border-indigo-200 bg-indigo-50 px-3 py-2">
                    <input
                        v-model="allDaysChecked"
                        type="checkbox"
                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    />
                    <span class="text-sm font-medium text-indigo-700">Todos los dias</span>
                </label>

                <InputError :message="$page.props.errors.dias_semana" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-6 rounded-lg border border-indigo-100 bg-indigo-50/40 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Configuracion personalizada por mes</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Si un mes no tiene configuracion personalizada, se usa la configuracion general.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="agregarConfiguracionPorMes"
                    >
                        Agregar mes
                    </button>
                </div>

                <div v-if="configuracionesPorMes.length === 0" class="mt-4 rounded border border-dashed border-indigo-200 bg-white dark:bg-gray-800 px-3 py-3 text-sm text-gray-500">
                    No hay meses personalizados.
                </div>

                <div
                    v-for="(configuracion, index) in configuracionesPorMes"
                    :key="index"
                    class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Mes personalizado {{ index + 1 }}</h4>
                        <button
                            type="button"
                            class="rounded bg-red-100 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-200"
                            @click="quitarConfiguracionPorMes(index)"
                        >
                            Quitar
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <InputLabel :for="`configuracion_mes_${index}`" value="Mes" :required="true" />
                            <Dropdown
                                :id="`configuracion_mes_${index}`"
                                v-model="configuracion.mes"
                                :options="mesesOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Seleccione mes"
                                class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                            />
                            <InputError :message="$page.props.errors[`configuracion_por_mes.${index}.mes`]" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel :for="`configuracion_periodicidad_${index}`" value="Periodicidad" :required="true" />
                            <Dropdown
                                :id="`configuracion_periodicidad_${index}`"
                                v-model="configuracion.periodicidad"
                                :options="periodicidades"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Seleccione periodicidad"
                                class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                            />
                            <InputError :message="$page.props.errors[`configuracion_por_mes.${index}.periodicidad`]" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel :for="`configuracion_hora_${index}`" value="Hora" :required="true" />
                            <input
                                :id="`configuracion_hora_${index}`"
                                v-model="configuracion.hora"
                                type="time"
                                step="60"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError :message="$page.props.errors[`configuracion_por_mes.${index}.hora`]" class="mt-2" />
                        </div>
                    </div>

                    <div v-if="!isConfiguracionDiaria(configuracion)" class="mt-3">
                        <InputLabel :for="`configuracion_dia_${index}`" value="Dia" :required="true" />
                        <TextInput
                            :id="`configuracion_dia_${index}`"
                            v-model="configuracion.dia"
                            type="number"
                            min="1"
                            max="31"
                            class="mt-1 block w-full sm:max-w-xs"
                        />
                        <InputError :message="$page.props.errors[`configuracion_por_mes.${index}.dia`]" class="mt-2" />
                    </div>

                    <div v-else class="mt-3">
                        <InputLabel value="Dias de la semana" :required="true" />
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Para cada dia podes definir un horario propio. Si lo dejas vacio, se usa la Hora de este mes.
                        </p>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <div
                                v-for="diaOption in diasSemanaOptions"
                                :key="`${index}-${diaOption.value}`"
                                class="rounded border border-gray-200 dark:border-gray-700 px-3 py-2"
                            >
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="configuracion.dias_semana"
                                        type="checkbox"
                                        :value="diaOption.value"
                                        class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ diaOption.label }}</span>
                                </label>
                                <input
                                    v-if="(configuracion.dias_semana || []).includes(diaOption.value)"
                                    v-model="configuracion.horarios_por_dia[diaOption.value]"
                                    type="time"
                                    step="60"
                                    :placeholder="configuracion.hora"
                                    class="mt-2 block w-full rounded-md border-gray-300 dark:border-gray-600 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                        <InputError :message="$page.props.errors[`configuracion_por_mes.${index}.dias_semana`]" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-6 mt-6 rounded-lg border border-amber-100 bg-amber-50/40 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Excepciones por fecha</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Cambia la hora de una fecha puntual o marca un mensaje (ej. "Centro cerrado"). Se aplica solo a esa fecha; el resto del mes usa la configuracion general.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700"
                        @click="agregarExcepcionPorFecha"
                    >
                        Agregar excepcion
                    </button>
                </div>

                <div v-if="excepcionesPorFecha.length === 0" class="mt-4 rounded border border-dashed border-amber-200 bg-white dark:bg-gray-800 px-3 py-3 text-sm text-gray-500">
                    No hay excepciones por fecha.
                </div>

                <div
                    v-for="(excepcion, index) in excepcionesPorFecha"
                    :key="`excepcion-${index}`"
                    class="mt-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Excepcion {{ index + 1 }}</h4>
                        <button
                            type="button"
                            class="rounded bg-red-100 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-200"
                            @click="quitarExcepcionPorFecha(index)"
                        >
                            Quitar
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <InputLabel :for="`excepcion_anio_${index}`" value="Año" :required="true" />
                            <Dropdown
                                :id="`excepcion_anio_${index}`"
                                v-model="excepcion.anio"
                                :options="opcionesAnioParaExcepcion(excepcion)"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Año"
                                class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                                @change="excepcion.fecha = null"
                            />
                        </div>

                        <div>
                            <InputLabel :for="`excepcion_mes_${index}`" value="Mes" :required="true" />
                            <Dropdown
                                :id="`excepcion_mes_${index}`"
                                v-model="excepcion.mes"
                                :options="mesesOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Seleccione mes"
                                class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                                @change="excepcion.fecha = null"
                            />
                        </div>

                        <div>
                            <InputLabel :for="`excepcion_fecha_${index}`" value="Fecha" :required="true" />
                            <Dropdown
                                :id="`excepcion_fecha_${index}`"
                                v-model="excepcion.fecha"
                                :options="opcionesFechaParaExcepcion(excepcion)"
                                optionLabel="label"
                                optionValue="value"
                                :placeholder="excepcion.mes ? 'Seleccione fecha' : 'Elegi un mes primero'"
                                :disabled="!excepcion.mes"
                                class="mt-1 w-full border border-gray-300 dark:border-gray-600"
                            />
                            <InputError :message="$page.props.errors[`excepciones_por_fecha.${index}.fecha`]" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel :for="`excepcion_hora_${index}`" value="Hora (opcional)" />
                            <input
                                :id="`excepcion_hora_${index}`"
                                v-model="excepcion.hora"
                                type="time"
                                step="60"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError :message="$page.props.errors[`excepciones_por_fecha.${index}.hora`]" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel :for="`excepcion_mensaje_${index}`" value="Mensaje (opcional)" />
                            <input
                                :id="`excepcion_mensaje_${index}`"
                                v-model="excepcion.mensaje"
                                type="text"
                                maxlength="255"
                                list="excepcion-mensaje-sugerencias"
                                placeholder="Ej. Centro cerrado"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <InputError :message="$page.props.errors[`excepciones_por_fecha.${index}.mensaje`]" class="mt-2" />
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Indica una hora, un mensaje, o ambos. Si dejas un mensaje, se muestra en lugar de la hora en la grilla publica.
                    </p>
                </div>

                <datalist id="excepcion-mensaje-sugerencias">
                    <option v-for="sugerencia in mensajeSugerencias" :key="sugerencia" :value="sugerencia" />
                </datalist>
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="imagen" value="Imagen (URL o direccion)" :required="false" />

                <div class="mt-2 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <label class="inline-flex cursor-pointer items-center rounded bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Seleccionar imagen
                            <input type="file" accept="image/*" class="hidden" @change="onImageFileSelected" />
                        </label>

                        <button
                            type="button"
                            class="inline-flex items-center rounded bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!selectedImageFile || isUploadingImage"
                            @click="uploadSelectedImage"
                        >
                            {{ isUploadingImage ? 'Subiendo...' : 'Subir imagen' }}
                        </button>

                        <button
                            v-if="selectedImageFile"
                            type="button"
                            class="inline-flex items-center rounded bg-gray-200 dark:bg-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600"
                            @click="clearSelectedImage"
                        >
                            Cancelar seleccion
                        </button>
                    </div>

                    <div v-if="selectedImageFile" class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Archivo: <span class="font-medium">{{ selectedImageFile.name }}</span>
                    </div>

                    <div v-if="currentImagePreview" class="mt-3 flex items-center gap-3">
                        <img :src="currentImagePreview" alt="Vista previa" class="h-16 w-16 rounded border border-gray-200 dark:border-gray-700 object-cover" />
                        <div class="text-xs text-gray-500 break-all">
                            {{ form.imagen ? 'Imagen cargada' : 'Vista previa local (pendiente de subir)' }}
                        </div>
                    </div>

                    <div v-if="imageUploadError" class="mt-2 text-sm text-red-600">
                        {{ imageUploadError }}
                    </div>
                </div>
                <InputError :message="$page.props.errors.imagen" class="mt-2" />
            </div>

            <div class="mt-4 flex items-center col-span-6">
                <InputSwitch v-model="form.mostrar_en_calendario" class="mr-3" />
                <label for="mostrar_en_calendario" class="block text-sm text-indigo-400">
                    Mostrar en calendario
                </label>
                <InputError :message="$page.props.errors.mostrar_en_calendario" class="ml-2" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
