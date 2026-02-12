<script>
    export default {
        name: 'BotonesPagoIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        botones: {
            type: Object,
            required: true
        }
    })

    const deleteBotonPago = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('botonespago.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Botón de Pago ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Botón de Pago.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Botones de Pago</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-5xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create botonpago')">
                        <Link :href="route('botonespago.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO BOTÓN DE PAGO
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="botones.data" stripedRows paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="descripcion" header="Descripción"></Column>
                            <Column header="Link">
                                <template #body="{ data }">
                                    <span v-if="data.link">
                                        {{ data.link.length > 30 ? data.link.slice(0, 30) + '...' : data.link }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </template>
                            </Column>
                            <Column header="Método de Pago">
                                <template #body="{ data }">
                                    {{ data.metodo_pago?.nombre || 'Sin método' }}
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2" >
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('botonespago.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update botonpago')"
                                            v-tooltip="'Editar botón'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteBotonPago(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete botonpago')"
                                            v-tooltip="'Borrar botón'"
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
    </AppLayout>
</template>
