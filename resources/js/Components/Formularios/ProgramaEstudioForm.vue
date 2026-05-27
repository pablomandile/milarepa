<script>
export default {
    name: 'ProgramaEstudioForm',
};
</script>

<script setup>
import { ref } from 'vue';
import FormSection from '@/Components/FormSection.vue';
import SectionTitle from '@/Components/SectionTitle.vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    updating: {
        type: Boolean,
        required: true,
        default: false,
    },
    compromisosPdfUrl: {
        type: String,
        default: '',
    },
    compromisosPdfNombre: {
        type: String,
        default: '',
    },
});

defineEmits(['submit']);

const archivoSeleccionadoNombre = ref('');

function seleccionarPdf(event) {
    const f = event.target.files?.[0];
    if (!f) {
        props.form.compromisos_pdf = null;
        archivoSeleccionadoNombre.value = '';
        return;
    }
    props.form.compromisos_pdf = f;
    props.form.compromisos_pdf_borrar = false;
    archivoSeleccionadoNombre.value = f.name;
}

function quitarPdfSeleccionado() {
    props.form.compromisos_pdf = null;
    archivoSeleccionadoNombre.value = '';
}

function marcarBorrarPdf() {
    props.form.compromisos_pdf = null;
    props.form.compromisos_pdf_borrar = true;
    archivoSeleccionadoNombre.value = '';
}
</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Programa de Estudio' : 'Crear Programa de Estudio' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el programa de estudio seleccionado.' : 'Creando un nuevo programa de estudio (PG, PF, PFM, etc.).' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true" />
                <TextInput
                    id="nombre"
                    v-model="form.nombre"
                    type="text"
                    autocomplete="off"
                    class="mt-1 block w-full"
                    placeholder="Ej: Programa General"
                />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="abreviacion" value="Abreviación" :required="false" />
                <TextInput
                    id="abreviacion"
                    v-model="form.abreviacion"
                    type="text"
                    maxlength="10"
                    autocomplete="off"
                    class="mt-1 block w-full uppercase"
                    placeholder="Ej: PG, PF, PFM"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Código corto único (hasta 10 caracteres). Se usa al importar usuarios desde CSV para identificar el programa.
                </p>
                <InputError :message="$page.props.errors.abreviacion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="descripcion" value="Descripción" :required="false" />
                <textarea
                    id="descripcion"
                    v-model="form.descripcion"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Descripción opcional del programa"
                ></textarea>
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="compromisos_pdf" value="Compromisos (PDF)" :required="false" />

                <!-- PDF actual (solo en modo update y si existe) -->
                <div
                    v-if="updating && compromisosPdfUrl && !archivoSeleccionadoNombre && !form.compromisos_pdf_borrar"
                    class="mt-1 mb-2 p-3 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 rounded flex items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-2 text-sm text-indigo-800 dark:text-indigo-200 min-w-0">
                        <i class="fas fa-file-pdf text-red-500"></i>
                        <a :href="compromisosPdfUrl" target="_blank" rel="noopener" class="underline truncate">
                            {{ compromisosPdfNombre || 'Ver compromisos actuales' }}
                        </a>
                    </div>
                    <button
                        type="button"
                        @click="marcarBorrarPdf"
                        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 inline-flex items-center gap-1"
                    >
                        <i class="fas fa-trash"></i>
                        Quitar
                    </button>
                </div>

                <!-- Mensaje cuando se marcó para borrar -->
                <div
                    v-if="updating && form.compromisos_pdf_borrar"
                    class="mt-1 mb-2 p-2 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded text-xs text-amber-800 dark:text-amber-200 flex items-center justify-between"
                >
                    <span>El PDF actual se eliminará al guardar.</span>
                    <button
                        type="button"
                        @click="form.compromisos_pdf_borrar = false"
                        class="underline hover:no-underline"
                    >
                        Cancelar
                    </button>
                </div>

                <!-- Selector de archivo -->
                <div class="flex items-center gap-3 flex-wrap mt-1">
                    <label class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded inline-flex items-center gap-2">
                        <i class="pi pi-upload"></i>
                        {{ updating && compromisosPdfUrl && !form.compromisos_pdf_borrar ? 'Reemplazar PDF' : 'Elegir PDF' }}
                        <input
                            type="file"
                            accept=".pdf,application/pdf"
                            class="hidden"
                            @change="seleccionarPdf"
                        />
                    </label>
                    <span v-if="archivoSeleccionadoNombre" class="text-sm text-gray-700 dark:text-gray-300 inline-flex items-center gap-2">
                        <i class="fas fa-file-pdf text-red-500"></i>
                        {{ archivoSeleccionadoNombre }}
                        <button type="button" @click="quitarPdfSeleccionado" class="text-xs text-gray-500 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Solo PDF. Tamaño máximo: 5 MB.
                </p>
                <InputError :message="$page.props.errors.compromisos_pdf" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
