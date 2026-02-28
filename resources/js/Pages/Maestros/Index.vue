<script>
    export default {
        name: 'MaestrosIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Dialog from 'primevue/dialog';
    import { ref } from 'vue';
    
    defineProps({
        maestros: {
            type: Object,
            required: true
        }
    })

    const imageDialogVisible = ref(false);
    const selectedImageUrl = ref('');

    const openImageDialog = (imageUrl) => {
        if (!imageUrl) return;
        selectedImageUrl.value = imageUrl;
        imageDialogVisible.value = true;
    };

    const deleteMaestro = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('maestros.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Maestro ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Maestro.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Maestros</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-5xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create maestros')">
                        <Link :href="route('maestros.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO MAESTRO
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable 
                            :value="maestros.data" 
                            stripedRows 
                            paginator 
                            :rows="5" 
                            :rowsPerPageOptions="[5, 10, 20, 50]" 
                            tableStyle="min-width: 58rem"
                        >
                            <Column header="Foto">
                                <template #body="slotProps">
                                    <div class="flex items-center justify-center">
                                        <button
                                            v-if="slotProps.data.imagen"
                                            type="button"
                                            class="relative inline-flex group cursor-zoom-in"
                                            @click="openImageDialog('/storage/' + slotProps.data.imagen.ruta)"
                                        >
                                            <img
                                                :src="'/storage/' + slotProps.data.imagen.ruta"
                                                alt="Foto maestro"
                                                class="h-12 w-12 rounded object-cover border border-gray-200"
                                            />
                                            <span class="absolute -top-1 -right-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-white text-indigo-600 border border-indigo-200 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                                <i class="pi pi-search-plus" style="font-size: 11px;"></i>
                                            </span>
                                        </button>
                                        <span v-else class="text-sm text-gray-400">Sin foto</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="telefono" header="Telefono"></Column>
                            <Column field="email" header="Correo electrónico"></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('maestros.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update maestros')"
                                            v-tooltip="'Editar maestro'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteMaestro(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete maestros')"
                                            v-tooltip="'Borrar maestro'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
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
            v-model:visible="imageDialogVisible"
            modal
            header="Foto de Maestro"
            :style="{ width: '720px' }"
        >
            <div class="w-full">
                <img
                    v-if="selectedImageUrl"
                    :src="selectedImageUrl"
                    alt="Foto de Maestro"
                    class="w-full max-h-[70vh] object-contain"
                />
            </div>
        </Dialog>
    </AppLayout>
</template>
