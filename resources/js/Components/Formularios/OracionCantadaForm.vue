<script>
export default {
    name: 'OracionCantadaForm'
}
</script>

<script setup>
import { computed, onUnmounted, ref } from 'vue';
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
    }
})

defineEmits(['submit'])

const periodicidades = [
    { label: 'Diaria', value: 'Diaria' },
    { label: 'Mensual', value: 'Mensual' },
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

const allDaysChecked = computed({
    get() {
        const selected = Array.isArray(props.form.dias_semana) ? props.form.dias_semana : [];
        return allDaysValues.every(day => selected.includes(day));
    },
    set(checked) {
        props.form.dias_semana = checked ? [...allDaysValues] : [];
    }
});

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
</script>

<template>
    <FormSection @submitted="$emit('submit')">
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
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                    class="mt-1 w-full border border-gray-300"
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
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError :message="$page.props.errors.hora" class="mt-2" />
            </div>

            <div v-if="isDiaria" class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel value="Dias de la semana" :required="true" />

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

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="imagen" value="Imagen (URL o direccion)" :required="false" />

                <div class="mt-2 rounded-lg border border-gray-200 p-3">
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
                            class="inline-flex items-center rounded bg-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300"
                            @click="clearSelectedImage"
                        >
                            Cancelar seleccion
                        </button>
                    </div>

                    <div v-if="selectedImageFile" class="mt-3 text-sm text-gray-600">
                        Archivo: <span class="font-medium">{{ selectedImageFile.name }}</span>
                    </div>

                    <div v-if="currentImagePreview" class="mt-3 flex items-center gap-3">
                        <img :src="currentImagePreview" alt="Vista previa" class="h-16 w-16 rounded border border-gray-200 object-cover" />
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
