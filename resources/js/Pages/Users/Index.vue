<script>
    export default {
        name: 'UsersIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import { computed, ref } from 'vue';
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Dropdown from 'primevue/dropdown';
    import { FilterMatchMode } from 'primevue/api';
    import Button from 'primevue/button';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    
    const props = defineProps({
        usuarios: {
            type: Object,
            required: true
        }
    })

    const usuariosConRol = computed(() =>
        props.usuarios.data.map((usuario) => ({
            ...usuario,
            role_name: usuario.roles && usuario.roles.length > 0 ? usuario.roles[0].name : '',
            membresia_nombre: usuario.membresia && usuario.membresia.nombre ? usuario.membresia.nombre : ''
        }))
    );

    const rolesDisponibles = computed(() =>
        [...new Set(usuariosConRol.value.map((usuario) => usuario.role_name))]
            .filter((role) => role !== '')
            .map((role) => ({ label: role, value: role }))
    );

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        telefono: { value: null, matchMode: FilterMatchMode.CONTAINS },
        membresia_nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
        role_name: { value: null, matchMode: FilterMatchMode.EQUALS }
    });

    const clearFilters = () => {
        filters.value = {
            global: { value: null, matchMode: FilterMatchMode.CONTAINS },
            name: { value: null, matchMode: FilterMatchMode.CONTAINS },
            email: { value: null, matchMode: FilterMatchMode.CONTAINS },
            telefono: { value: null, matchMode: FilterMatchMode.CONTAINS },
            membresia_nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
            role_name: { value: null, matchMode: FilterMatchMode.EQUALS }
        };
    };

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
                        <DataTable
                            v-model:filters="filters"
                            :value="usuariosConRol"
                            filterDisplay="menu"
                            :globalFilterFields="['name', 'email', 'telefono', 'membresia_nombre', 'role_name']"
                            stripedRows
                            paginator
                            :rows="20"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 50rem"
                        >
                            <template #header>
                                <div class="flex justify-between items-center">
                                    <Button
                                        type="button"
                                        icon="pi pi-filter-slash"
                                        label="Limpiar"
                                        outlined
                                        @click="clearFilters()"
                                    />
                                    <IconField iconPosition="right">
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText
                                            v-model="filters['global'].value"
                                            placeholder="Buscar..."
                                        />
                                    </IconField>
                                </div>
                            </template>
                            <Column field="name" header="Nombre" :showFilterMatchModes="false">
                                <template #filter="{ filterModel }">
                                    <InputText v-model="filterModel.value" type="text" placeholder="Buscar por nombre" class="p-column-filter" />
                                </template>
                            </Column>
                            <Column field="email" header="Correo electrónico" :showFilterMatchModes="false">
                                <template #filter="{ filterModel }">
                                    <InputText v-model="filterModel.value" type="text" placeholder="Buscar por correo" class="p-column-filter" />
                                </template>
                            </Column>
                            <Column field="telefono" header="Teléfono" :showFilterMatchModes="false">
                                <template #filter="{ filterModel }">
                                    <InputText v-model="filterModel.value" type="text" placeholder="Buscar por teléfono" class="p-column-filter" />
                                </template>
                            </Column>
                            <Column field="membresia_nombre" header="Membresía" :showFilterMatchModes="false">
                                <template #body="{ data }">
                                    <span v-if="data.membresia_nombre">
                                        {{ data.membresia_nombre }}
                                        <span
                                            v-if="data.membresia_online"
                                            class="text-blue-600 text-xs ml-2"
                                        >
                                            Online
                                        </span>
                                    </span>
                                    <span v-else class="text-gray-400">Sin membresía</span>
                                </template>
                                <template #filter="{ filterModel }">
                                    <InputText v-model="filterModel.value" type="text" placeholder="Buscar por membresía" class="p-column-filter" />
                                </template>
                            </Column>
                            <Column
                                field="role_name"
                                header="Roles"
                            >
                                <template #body="slotProps">
                                    <span v-if="slotProps.data.role_name">
                                        {{ slotProps.data.role_name }}
                                    </span>
                                    <span v-else class="text-gray-400">Sin rol</span>
                                </template>
                                <template #filter="{ filterModel, filterCallback }">
                                    <Dropdown
                                        v-model="filterModel.value"
                                        :options="rolesDisponibles"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Filtrar rol"
                                        showClear
                                        class="p-column-filter"
                                        @change="filterCallback()"
                                    />
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



