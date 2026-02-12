<script>
    export default {
        name: 'PermisosEdit'
    }
</script>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Swal from 'sweetalert2';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    permiso: {
        type: Object,
        required: true
    },
    permissions: {
        type: Array,
        required: true
    }
});

const form = useForm({
    name: props.permiso.name || ''
});

const submit = () => {
    form.put(route('permisos.update', props.permiso.id));
};

const deletePermiso = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('permisos.destroy', id));
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Permisos</h1>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg p-6">
                    <div class="flex justify-between mb-6">
                        <Link
                            :href="route('perfiles.index')"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            Volver
                        </Link>
                    </div>

                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">Editar Permiso</h2>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="name" value="Nombre" :required="true" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                                <InputError :message="$page.props.errors.name" class="mt-2" />
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 py-2 px-4 rounded">
                                    Actualizar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <h3 class="text-md font-semibold text-gray-800 mb-3">Permisos existentes</h3>
                        <DataTable :value="permissions" stripedRows paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="name" header="Nombre"></Column>
                            <Column header="Acciones" class="text-center" style="width: 10%">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('permisos.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update permisos')"
                                            v-tooltip="'Editar permiso'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deletePermiso(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete permisos')"
                                            v-tooltip="'Borrar permiso'"
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
