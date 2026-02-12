<script>
    export default {
        name: 'PermisosCreate'
    }
</script>

<script setup>
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Swal from 'sweetalert2';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Dropdown from 'primevue/dropdown';
import { computed, ref } from 'vue';

const props = defineProps({
    permissions: {
        type: Array,
        required: true
    }
});

const page = usePage();
const toast = useToast();

const actionFilter = ref(null);
const actionOptions = [
    { label: 'Todos', value: null },
    { label: 'Create', value: 'create' },
    { label: 'Read', value: 'read' },
    { label: 'Update', value: 'update' },
    { label: 'Delete', value: 'delete' },
    { label: 'Asign', value: 'asign' },
];

const form = useForm({
    name: ''
});

const submit = () => {
    form.post(route('permisos.store'), {
        onSuccess: () => {
            form.reset('name');
            toast.add({
                severity: 'success',
                summary: 'Permisos',
                detail: page.props.flash?.success || 'Permiso creado correctamente.',
                life: 4000
            });
        }
    });
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

const filteredPermissions = computed(() => {
    if (!actionFilter.value) return props.permissions;
    return props.permissions.filter((p) => p.name?.startsWith(`${actionFilter.value} `));
});
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Permisos</h1>
        </template>
        <Toast position="top-right" />

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
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">Nuevo Permiso</h2>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="name" value="Nombre" :required="true" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                                <InputError :message="$page.props.errors.name" class="mt-2" />
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 py-2 px-4 rounded">
                                    Crear
                                </button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <h3 class="text-md font-semibold text-gray-800 mb-3">Permisos existentes</h3>
                        <div class="mb-3 flex items-center gap-3">
                            <span class="text-sm text-gray-600">Filtrar por acción:</span>
                            <Dropdown
                                v-model="actionFilter"
                                :options="actionOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Todos"
                                class="w-48"
                            />
                        </div>
                        <DataTable :value="filteredPermissions" stripedRows paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
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
