<script>
    export default {
        name: 'MembresiasGestion'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';

    defineProps({
        membresias: {
            type: Object,
            required: true
        }
    })

    const deleteMembresia = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('membresias.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Membresía ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Membresía.", "error");
                },
            });
            }
        });
    };

    const formatArs = (valor) => {
        if (valor === null || valor === undefined) return '$ 0,00';
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 2
        }).format(valor);
    };

</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Membresías</h1>
        </template>
        <div class="py-12">
            <div class="w-full p-4 sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-fullo">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create membresias')">
                        <Link :href="route('membresias.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" >
                            NUEVA MEMBRESÍA
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="membresias.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 60rem">
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="descripcion" header="Descripción"></Column>
                            <Column field="entidad.nombre" header="Entidad"></Column>
                            <Column field="valor" header="Valor">
                                <template #body="slotProps">
                                    {{ formatArs(slotProps.data.valor) }}
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('membresias.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update membresias')"
                                            v-tooltip="'Editar membresía'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteMembresia(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete membresias')"
                                            v-tooltip="'Borrar membresía'"
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