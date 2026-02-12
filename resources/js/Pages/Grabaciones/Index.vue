<script>
    export default {
        name: 'GrabacionesIndex'
    }
</script>

<script setup>
    import { ref } from 'vue';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Dialog from 'primevue/dialog';
    
    defineProps({
        grabaciones: {
            type: Array,
            required: true
        }
    })

    // Controla quÃ© filas de la tabla principal estÃ¡n expandidas
    const expandedRows = ref([]);

    const deleteGrabacion = (id) => {
    Swal.fire({
        title: "Â¿EstÃ¡s seguro?",
        text: "Esta acciÃ³n no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "SÃ­, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('grabaciones.destroy', id), {
                onSuccess: () => {
                Swal.fire("Â¡Eliminado!", "La GrabaciÃ³n ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la GrabaciÃ³n.", "error");
                },
            });
            }
        });
    };

    const audioDialogVisible = ref(false);
    const audioLink = ref('');
    const audioNombre = ref('');

    function openAudio(link) {
        audioLink.value = link?.link || '';
        audioNombre.value = link?.nombre || 'GrabaciÃ³n';
        audioDialogVisible.value = true;
    }
    
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Grabaciones</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create grabaciones')">
                        <Link :href="route('grabaciones.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA GRABACIÓN
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable 
                            :value="grabaciones" 
                            stripedRows 
                            paginator 
                            :rows="10" 
                            v-model:expandedRows="expandedRows"
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50]" 
                            tableStyle="min-width: 50rem"
                        >
                            <Column expander style="width: 5rem" />
                            <Column field="nombre" header="Nombre"></Column>
                            <Column header="Botón de Pago">
                                <template #body="{ data }">
                                    {{ data.boton_pago?.nombre || 'Sin botón' }}
                                </template>
                            </Column>
                            <Column field="valor" header="Valor">
                                <template #body="{ data }">
                                    $ {{ Number(data.valor || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('grabaciones.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update grabaciones')"
                                            v-tooltip="'Editar grabación'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <Link
                                            :href="route('grabaciones.links', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update grabaciones')"
                                            v-tooltip="'Agregar links'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-link" style="font-size: 18px !important; line-height: 1; color: rgb(16, 185, 129);"></i>
                                        </Link>
                                        <button
                                            @click="deleteGrabacion(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete grabaciones')"
                                            v-tooltip="'Borrar grabación'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #expansion="{ data }">
                                <DataTable 
                                    :value="data.linksgrabacion"
                                    class="mt-3"
                                    stripedRows
                                    tableStyle="min-width: 50rem"
                                >
                                    <Column field="nombre" header="Nombre"></Column>
                            <Column header="Botón de Pago">
                                <template #body="{ data }">
                                    {{ data.boton_pago?.nombre || 'Sin botón' }}
                                </template>
                            </Column>
                                    <Column field="link" header="Link"></Column>
                                    <Column header="Abrir" bodyStyle="text-align:center;">
                                        <template #body="{ data: linkLine }">
                                            <button
                                                type="button"
                                                class="text-emerald-600 hover:text-emerald-800"
                                                @click="openAudio(linkLine)"
                                                title="Abrir en Drive"
                                            >
                                                <i class="fas fa-external-link-alt"></i>
                                            </button>
                                        </template>
                                    </Column>
                                </DataTable>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <Dialog
        v-model:visible="audioDialogVisible"
        modal
        :header="audioNombre"
        :style="{ width: '520px' }"
    >
        <div class="space-y-3">
            <p class="text-sm text-gray-600">Abrir archivo en Google Drive.</p>
            <a
                v-if="audioLink"
                :href="audioLink"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
            >
                <i class="fas fa-external-link-alt"></i>
                Abrir en Drive
            </a>
            <p v-else class="text-sm text-gray-500">No hay link disponible.</p>
        </div>
    </Dialog>
</template>


