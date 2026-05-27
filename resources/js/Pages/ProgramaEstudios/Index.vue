<script>
export default {
    name: 'ProgramaEstudiosIndex',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';

defineProps({
    programaEstudios: {
        type: Array,
        required: true,
    },
});

const dialogVisible = ref(false);
const programaSeleccionado = ref(null);

function truncar(texto, max = 100) {
    if (!texto) return '';
    const t = String(texto);
    return t.length > max ? t.slice(0, max) + '…' : t;
}

function verDetalle(programa) {
    programaSeleccionado.value = programa;
    dialogVisible.value = true;
}

const deleteProgramaEstudio = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('programa-estudios.destroy', id), {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'El programa de estudio ha sido eliminado.', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'Hubo un problema al eliminar el programa de estudio.', 'error');
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
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Programas de Estudio
            </h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-4xl mx-auto">
                    <div class="flex justify-between items-center flex-wrap gap-3">
                        <Link
                            v-if="$page.props.user.permissions.includes('create programa-estudios')"
                            :href="route('programa-estudios.create')"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            CREAR PROGRAMA DE ESTUDIO
                        </Link>
                        <Link
                            v-if="$page.props.user.permissions.includes('update programa-estudios')"
                            :href="route('programa-estudios.asignacion-usuarios')"
                            class="text-white bg-emerald-600 hover:bg-emerald-700 py-2 px-4 rounded inline-flex items-center gap-2"
                        >
                            <i class="fas fa-user-tag"></i>
                            ASIGNACIÓN USUARIO PROGRAMA
                        </Link>
                        <Link
                            v-if="$page.props.user.permissions.includes('read programa-grabaciones')"
                            :href="route('programa-grabaciones.index')"
                            class="text-white bg-purple-600 hover:bg-purple-700 py-2 px-4 rounded inline-flex items-center gap-2"
                        >
                            <i class="fas fa-music"></i>
                            GRABACIONES
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable
                            :value="programaEstudios"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 50rem"
                        >
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="abreviacion" header="Abreviación">
                                <template #body="slotProps">
                                    <span
                                        v-if="slotProps.data.abreviacion"
                                        class="inline-block px-2 py-1 text-xs font-mono font-semibold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 dark:text-indigo-300 rounded"
                                    >
                                        {{ slotProps.data.abreviacion }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </template>
                            </Column>
                            <Column field="descripcion" header="Descripción">
                                <template #body="slotProps">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ truncar(slotProps.data.descripcion, 100) || '—' }}
                                    </span>
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            @click="verDetalle(slotProps.data)"
                                            v-tooltip="'Ver detalle'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i
                                                class="fas fa-eye"
                                                style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"
                                            ></i>
                                        </button>
                                        <Link
                                            :href="route('programa-estudios.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update programa-estudios')"
                                            v-tooltip="'Editar programa de estudio'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i
                                                class="fas fa-pen-to-square"
                                                style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"
                                            ></i>
                                        </Link>
                                        <button
                                            @click="deleteProgramaEstudio(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete programa-estudios')"
                                            v-tooltip="'Borrar programa de estudio'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i
                                                class="fas fa-trash"
                                                style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"
                                            ></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="dialogVisible"
            header="Detalle del Programa de Estudio"
            :style="{ width: '40rem' }"
            dismissableMask
            modal
        >
            <div v-if="programaSeleccionado" class="space-y-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Nombre</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ programaSeleccionado.nombre }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Abreviación</p>
                    <p v-if="programaSeleccionado.abreviacion" class="text-base">
                        <span class="inline-block px-2 py-1 text-sm font-mono font-semibold text-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 dark:text-indigo-300 rounded">
                            {{ programaSeleccionado.abreviacion }}
                        </span>
                    </p>
                    <p v-else class="text-sm text-gray-400">—</p>
                </div>
                <div v-if="programaSeleccionado.compromisos_pdf">
                    <a
                        :href="`/storage/${programaSeleccionado.compromisos_pdf}`"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded text-sm"
                    >
                        <i class="fas fa-file-pdf"></i>
                        Compromisos
                        <i class="fas fa-external-link-alt text-xs opacity-75"></i>
                    </a>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Descripción</p>
                    <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">
                        {{ programaSeleccionado.descripcion || '—' }}
                    </p>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
