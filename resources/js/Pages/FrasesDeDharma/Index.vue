<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Swal from 'sweetalert2';

defineProps({
    frases: {
        type: Array,
        required: true,
    },
});

const formatoVisible = ref(false);
const fileInput = ref(null);

const form = useForm({
    archivo: null,
});

const triggerFileSelect = () => {
    fileInput.value?.click();
};

const onFileSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    form.archivo = file;
    form.post(route('frases-de-dharma.import'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            const msg = page.props?.flash?.success;
            if (msg) {
                Swal.fire({ title: 'Importación exitosa', text: msg, icon: 'success' });
            }
            form.reset('archivo');
            if (fileInput.value) fileInput.value.value = '';
        },
        onError: (errors) => {
            const errMsg = errors.archivo || 'No se pudo importar el archivo.';
            Swal.fire({ title: 'Error', text: errMsg, icon: 'error' });
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const eliminarFrase = (frase) => {
    Swal.fire({
        title: '¿Eliminar esta frase?',
        text: '"' + frase.cita_textual.substring(0, 80) + (frase.cita_textual.length > 80 ? '…' : '') + '"',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('frases-de-dharma.destroy', frase.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Eliminada', 'La frase fue eliminada.', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'No se pudo eliminar la frase.', 'error');
                },
            });
        }
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Frases de Dharma" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Importar Frases de Dharma
            </h1>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                    <!-- Toolbar superior -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-medium text-sm shadow-sm disabled:opacity-50"
                            :disabled="form.processing"
                            @click="triggerFileSelect"
                        >
                            <i class="pi pi-upload mr-2"></i>
                            {{ form.processing ? 'Importando…' : 'Importar CSV' }}
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 rounded font-medium text-sm shadow-sm"
                            @click="formatoVisible = true"
                        >
                            <i class="pi pi-info-circle mr-2"></i>
                            Formato
                        </button>

                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv,text/csv"
                            class="hidden"
                            @change="onFileSelected"
                        />

                        <div class="ml-auto text-sm text-gray-500 dark:text-gray-400 self-center">
                            Total: {{ frases.length }} frase{{ frases.length === 1 ? '' : 's' }}
                        </div>
                    </div>

                    <!-- DataTable de frases -->
                    <DataTable
                        :value="frases"
                        stripedRows
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[10, 20, 50, 100]"
                        tableStyle="min-width: 50rem"
                    >
                        <Column field="numero" header="N°" style="width: 5rem"></Column>
                        <Column field="cita_textual" header="Cita"></Column>
                        <Column field="libro" header="Libro" style="width: 18rem"></Column>
                        <Column header="Acciones" style="width: 6rem">
                            <template #body="slotProps">
                                <div class="flex justify-center items-center">
                                    <button
                                        type="button"
                                        v-tooltip="'Eliminar frase'"
                                        @click="eliminarFrase(slotProps.data)"
                                        style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                    >
                                        <i class="fas fa-trash" style="font-size: 18px; line-height: 1; color: rgb(239, 68, 68);"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Dialog de formato -->
        <Dialog
            v-model:visible="formatoVisible"
            modal
            header="Formato del archivo CSV"
            :style="{ width: '38rem' }"
            :breakpoints="{ '960px': '85vw' }"
        >
            <div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
                <p>
                    El archivo debe ser un <strong>.csv</strong> codificado en UTF-8 con <strong>3 columnas</strong>
                    en la primera fila (encabezado):
                </p>

                <div class="bg-gray-100 dark:bg-gray-900 rounded p-3 font-mono text-xs overflow-x-auto">
                    <div><strong>numero</strong>,<strong>cita_textual</strong>,<strong>libro</strong></div>
                    <div class="text-gray-500 dark:text-gray-400 mt-2">1,"La verdadera causa de la felicidad es la paz interior.","Cómo transformar tu vida"</div>
                    <div class="text-gray-500 dark:text-gray-400">2,"Si tenemos una mente apacible, seremos felices en todo momento.","Cómo transformar tu vida"</div>
                </div>

                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>numero</strong>: entero (referencia interna).</li>
                    <li><strong>cita_textual</strong>: texto de la frase. Si contiene comas, encerrar entre comillas dobles.</li>
                    <li><strong>libro</strong>: nombre del libro de origen.</li>
                </ul>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Tamaño máximo del archivo: 2&nbsp;MB. La importación agrega las filas a las existentes;
                    los duplicados se pueden eliminar manualmente desde la tabla.
                </p>
            </div>
        </Dialog>
    </AppLayout>
</template>
