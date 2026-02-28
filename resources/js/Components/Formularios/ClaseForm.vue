<script>
export default {
    name: 'ClaseForm'
}
</script>

<script setup>
import { computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';
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
    ciclos: {
        type: Array,
        default: () => []
    },
    entidades: {
        type: Array,
        default: () => []
    },
    maestros: {
        type: Array,
        default: () => []
    },
    coordinadores: {
        type: Array,
        default: () => []
    },
    esquemaPrecios: {
        type: Array,
        default: () => []
    },
    hideHeader: {
        type: Boolean,
        default: false
    },
    imagenPreviewUrl: {
        type: String,
        default: ''
    },
});

defineEmits(['submit']);

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

const allDaysChecked = computed({
    get() {
        const selected = Array.isArray(props.form.dias_semana) ? props.form.dias_semana : [];
        return allDaysValues.every(day => selected.includes(day));
    },
    set(checked) {
        props.form.dias_semana = checked ? [...allDaysValues] : [];
    }
});

const weekdayIndexMap = {
    domingo: 0,
    lunes: 1,
    martes: 2,
    miercoles: 3,
    jueves: 4,
    viernes: 5,
    sabado: 6,
};

const monthFormatter = new Intl.DateTimeFormat('es-AR', {
    month: 'long',
    year: 'numeric',
});

const generatedSessionDates = computed(() => {
    const monthValue = props.form.mes_referencia;
    const selectedDays = Array.isArray(props.form.dias_semana) ? props.form.dias_semana : [];

    if (!monthValue || !/^\d{4}-(0[1-9]|1[0-2])$/.test(monthValue) || selectedDays.length === 0) {
        return [];
    }

    const [year, month] = monthValue.split('-').map(Number);
    const daysSet = new Set(
        selectedDays
            .map(day => weekdayIndexMap[day])
            .filter(day => day !== undefined)
    );

    const result = [];
    const cursor = new Date(year, month - 1, 1);
    while (cursor.getMonth() === month - 1) {
        if (daysSet.has(cursor.getDay())) {
            const y = cursor.getFullYear();
            const m = String(cursor.getMonth() + 1).padStart(2, '0');
            const d = String(cursor.getDate()).padStart(2, '0');
            const ymd = `${y}-${m}-${d}`;
            result.push({
                key: ymd,
                date: ymd,
                label: `${cursor.toLocaleDateString('es-AR', { weekday: 'long', day: '2-digit', month: '2-digit' })}`,
            });
        }
        cursor.setDate(cursor.getDate() + 1);
    }

    return result;
});

const mesReferenciaLabel = computed(() => {
    if (!props.form.mes_referencia || !/^\d{4}-(0[1-9]|1[0-2])$/.test(props.form.mes_referencia)) {
        return '';
    }
    const [year, month] = props.form.mes_referencia.split('-').map(Number);
    return monthFormatter.format(new Date(year, month - 1, 1));
});

watch(
    generatedSessionDates,
    (dates) => {
        if (!props.form.titulos_por_fecha || Array.isArray(props.form.titulos_por_fecha)) {
            props.form.titulos_por_fecha = {};
        }

        const allowedKeys = new Set(dates.map(item => item.key));
        Object.keys(props.form.titulos_por_fecha).forEach((key) => {
            if (!allowedKeys.has(key)) {
                delete props.form.titulos_por_fecha[key];
            }
        });

        dates.forEach((item) => {
            if (typeof props.form.titulos_por_fecha[item.key] !== 'string') {
                props.form.titulos_por_fecha[item.key] = '';
            }
        });
    },
    { immediate: true }
);
</script>

<template>
    <FormSection :no-aside="hideHeader" @submitted="$emit('submit')">
        <template #title></template>
        <template #description></template>

        <template #form>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-if="!hideHeader" class="md:col-span-3">
                    <h2 class="text-2xl font-semibold text-indigo-600">
                        {{ updating ? 'Actualizar Clase' : 'Nueva Clase' }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ updating ? 'Edita la informacion de la clase seleccionada.' : 'Completa los datos para registrar una nueva clase.' }}
                    </p>
                </div>

                <div class="w-full col-span-6 sm:col-span-6">
                    <InputLabel for="nombre" class="text-indigo-400" value="Nombre" :required="true" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="off" class="mt-1 block w-full" />
                    <InputError :message="$page.props.errors.nombre" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="ciclo_id" class="text-indigo-400" value="Ciclo" :required="true" />
                    <div class="flex gap-2 items-center mt-1">
                        <Dropdown
                            id="ciclo_id"
                            v-model="form.ciclo_id"
                            :options="ciclos"
                            optionLabel="nombre"
                            optionValue="id"
                            placeholder="Seleccione un ciclo"
                            class="w-full border border-gray-300"
                        />
                        <Link
                            :href="route('ciclos.create')"
                            class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
                            v-tooltip="'Crear nuevo ciclo'"
                        >
                            <i class="pi pi-file-plus"></i>
                        </Link>
                    </div>
                    <InputError :message="$page.props.errors.ciclo_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="entidad_id" class="text-indigo-400" value="Entidad" :required="true" />
                    <div class="flex gap-2 items-center mt-1">
                        <Dropdown
                            id="entidad_id"
                            v-model="form.entidad_id"
                            :options="entidades"
                            optionLabel="nombre"
                            optionValue="id"
                            placeholder="Seleccione una entidad"
                            class="w-full border border-gray-300"
                        />
                        <Link
                            :href="route('entidades.create')"
                            class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
                            v-tooltip="'Crear nueva entidad'"
                        >
                            <i class="pi pi-file-plus"></i>
                        </Link>
                    </div>
                    <InputError :message="$page.props.errors.entidad_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="mes_referencia" class="text-indigo-400" value="Mes" :required="true" />
                    <input
                        id="mes_referencia"
                        v-model="form.mes_referencia"
                        type="month"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <p v-if="mesReferenciaLabel" class="mt-1 text-xs text-gray-500">
                        Seleccionado: {{ mesReferenciaLabel }}
                    </p>
                    <InputError :message="$page.props.errors.mes_referencia" class="mt-2" />
                </div>

                <div class="w-full col-span-6 sm:col-span-6">
                    <InputLabel for="descripcion" class="text-indigo-400" value="Descripcion" />
                    <Textarea id="descripcion" v-model="form.descripcion" rows="4" class="mt-1 block w-full border border-gray-300 rounded" />
                    <InputError :message="$page.props.errors.descripcion" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="imagen_id" class="text-indigo-400 mb-2" value="Imagen" />
                    <div class="flex items-start gap-4">
                        <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create entidades')">
                            <SingleImageUploader
                                v-model:imagenId="form.imagen_id"
                                folder="img/clases"
                            />
                        </div>
                        <div v-if="imagenPreviewUrl" class="flex items-center gap-2">
                            <img
                                :src="imagenPreviewUrl"
                                alt="Imagen actual"
                                class="h-16 w-16 rounded border border-gray-200 object-cover"
                            />
                            <span class="text-xs text-gray-500">Actual</span>
                        </div>
                    </div>
                    <InputError :message="$page.props.errors.imagen_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel value="Dias de la semana" class="text-indigo-400" :required="true" />

                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <label
                            v-for="diaOption in diasSemanaOptions"
                            :key="diaOption.value"
                            class="flex items-center gap-2 rounded border border-gray-200 px-3 py-2"
                        >
                            <input
                                v-model="form.dias_semana"
                                type="checkbox"
                                :value="diaOption.value"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="text-sm text-gray-700">{{ diaOption.label }}</span>
                        </label>
                    </div>

                    <label class="mt-3 inline-flex items-center gap-2 rounded border border-indigo-200 bg-indigo-50 px-3 py-2">
                        <input
                            v-model="allDaysChecked"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        />
                        <span class="text-sm font-medium text-indigo-700">Todos los dias</span>
                    </label>

                    <InputError :message="$page.props.errors.dias_semana" class="mt-2" />
                </div>

                <div v-if="generatedSessionDates.length > 0" class="col-span-6 sm:col-span-6">
                    <InputLabel class="text-indigo-400" value="Titulos por fecha" :required="true" />
                    <p class="mt-1 text-xs text-gray-500">
                        Se detectaron {{ generatedSessionDates.length }} clase(s) en el mes seleccionado.
                    </p>
                    <div class="mt-2 space-y-2">
                        <div
                            v-for="sessionDate in generatedSessionDates"
                            :key="sessionDate.key"
                            class="grid grid-cols-1 gap-2 sm:grid-cols-3 items-center rounded border border-gray-200 p-2"
                        >
                            <span class="text-sm text-gray-700 sm:col-span-1">{{ sessionDate.label }}</span>
                            <div class="sm:col-span-2">
                                <TextInput
                                    v-model="form.titulos_por_fecha[sessionDate.key]"
                                    type="text"
                                    class="block w-full"
                                    :placeholder="`Titulo para ${sessionDate.date}`"
                                />
                            </div>
                        </div>
                    </div>
                    <InputError :message="$page.props.errors.titulos_por_fecha" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="horario_desde" class="text-indigo-400" value="Horario desde" :required="true" />
                    <input
                        id="horario_desde"
                        v-model="form.horario_desde"
                        type="time"
                        step="60"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="$page.props.errors.horario_desde" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                    <InputLabel for="horario_hasta" class="text-indigo-400" value="Horario hasta" :required="true" />
                    <input
                        id="horario_hasta"
                        v-model="form.horario_hasta"
                        type="time"
                        step="60"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="$page.props.errors.horario_hasta" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="maestro_id" class="text-indigo-400" value="Maestro" />
                    <Dropdown
                        id="maestro_id"
                        v-model="form.maestro_id"
                        :options="maestros"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccione un maestro"
                        class="w-full mt-1 border border-gray-300"
                        showClear
                    />
                    <InputError :message="$page.props.errors.maestro_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="coordinador_id" class="text-indigo-400" value="Coordinador" />
                    <Dropdown
                        id="coordinador_id"
                        v-model="form.coordinador_id"
                        :options="coordinadores"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccione un coordinador"
                        class="w-full mt-1 border border-gray-300"
                        showClear
                    />
                    <InputError :message="$page.props.errors.coordinador_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="esquema_precio_id" class="text-indigo-400" value="Esquema de precios" />
                    <Dropdown
                        id="esquema_precio_id"
                        v-model="form.esquema_precio_id"
                        :options="esquemaPrecios"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccione un esquema"
                        class="w-full mt-1 border border-gray-300"
                        showClear
                    />
                    <InputError :message="$page.props.errors.esquema_precio_id" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-6 mt-2">
                    <div class="flex items-center">
                        <InputSwitch v-model="form.mostrar_en_calendario" class="mr-3" />
                        <label for="mostrar_en_calendario" class="block text-sm text-indigo-400">
                            Mostrar en calendario
                        </label>
                    </div>
                    <InputError :message="$page.props.errors.mostrar_en_calendario" class="mt-2" />
                </div>
            </div>
        </template>

        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
