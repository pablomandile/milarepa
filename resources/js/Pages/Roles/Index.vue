<script>
    export default {
        name: 'RolesIndex'
    }
</script>

<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Swal from 'sweetalert2';

const props = defineProps({
    roles: Object,
});

const page = usePage();

const canCreate = () => page.props.auth.user.permissions?.includes('create-roles') ?? false;
const canEdit = () => page.props.auth.user.permissions?.includes('update-roles') ?? false;
const canDelete = () => page.props.auth.user.permissions?.includes('delete-roles') ?? false;

const deleteRole = (id, name) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminará el rol "${name}"`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('roles.destroy', id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Eliminado',
                        text: 'El rol ha sido eliminado exitosamente.',
                        icon: 'success',
                    });
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Roles</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between" v-if="canCreate()">
                        <Link :href="route('roles.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            NUEVO ROL
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="roles.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="name" header="Nombre"></Column>
                            <Column field="guard_name" header="Guard"></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('roles.edit', slotProps.data.id)"
                                            v-if="canEdit()"
                                            v-tooltip="'Editar rol'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteRole(slotProps.data.id, slotProps.data.name)"
                                            v-if="canDelete()"
                                            v-tooltip="'Borrar rol'"
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
