<script>
    export default {
        name: 'UsersIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        usuarios: {
            type: Object,
            required: true
        }
    })

    const deleteUsuario = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('usuarios.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El usuario ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el usuario.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Usuarios</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create usuarios')">
                        <Link :href="route('usuarios.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO USUARIO
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="usuarios.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="name" header="Nombre"></Column>
                            <Column field="email" header="Correo electrónico"></Column>
                            <Column field="telefono" header="Teléfono"></Column>
                            <Column field="membresia.nombre" header="Membresía"></Column>
                            <Column header="Roles">
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.roles && slotProps.data.roles.length > 0">
                                        {{ slotProps.data.roles[0].name }}
                                    </span>
                                    <span v-else class="text-gray-400">Sin rol</span>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('usuarios.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update usuarios')"
                                            v-tooltip="'Editar usuario'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteUsuario(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete usuarios')"
                                            v-tooltip="'Borrar usuario'"
                                            class="text-red-600 hover:text-red-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1;"></i>
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