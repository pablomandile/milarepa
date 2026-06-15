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
            type: Array,
            required: true
        }
    })

    const usuariosConRol = computed(() =>
        (props.usuarios || []).map((usuario) => ({
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

    const filtroMembresia = ref('todos');

    const usuariosFiltrados = computed(() => {
        if (filtroMembresia.value === 'todos') {
            return usuariosConRol.value;
        }

        return usuariosConRol.value.filter((usuario) =>
            usuario.membresia && Number(usuario.membresia.id) !== 1
        );
    });

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        telefono: { value: null, matchMode: FilterMatchMode.CONTAINS },
        membresia_nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
        role_name: { value: null, matchMode: FilterMatchMode.EQUALS }
    });

    const usuariosFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        const lista = usuariosFiltrados.value;
        if (!term) return lista;
        return lista.filter((u) => {
            const campos = [u.name, u.email, u.telefono, u.membresia_nombre, u.role_name];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
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
        filtroMembresia.value = 'todos';
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
                preserveScroll: true,
                onSuccess: (page) => {
                const flash = page?.props?.flash || {};
                if (flash.error) {
                    Swal.fire("No se pudo eliminar", flash.error, "warning");
                } else {
                    Swal.fire("¡Eliminado!", flash.success || "El usuario ha sido eliminado.", "success");
                }
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
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Usuarios</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create usuarios')">
                        <Link :href="route('usuarios.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO USUARIO
                        </Link>
                    </div>
                    <!-- Filtros móvil -->
                    <div v-if="usuarios.length > 0" class="sm:hidden mt-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                icon="pi pi-filter-slash"
                                label="Limpiar"
                                outlined
                                @click="clearFilters()"
                            />
                            <select
                                v-model="filtroMembresia"
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="con_membresia">Con membresía</option>
                                <option value="todos">Mostrar todos</option>
                            </select>
                        </div>
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="usuariosFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="usuario in usuariosFiltradosMobile"
                            :key="usuario.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-user-circle text-3xl text-indigo-600 mt-0.5"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ usuario.name }}</p>
                                        <p v-if="usuario.role_name" class="mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                <i class="fas fa-shield-halved mr-1"></i>
                                                {{ usuario.role_name }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Email</span>
                                        <span class="text-right break-all">
                                            <a v-if="usuario.email" :href="`mailto:${usuario.email}`" class="text-indigo-600 hover:text-indigo-800">
                                                {{ usuario.email }}
                                            </a>
                                            <span v-else>-</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Teléfono</span>
                                        <span class="text-right">
                                            <a v-if="usuario.telefono" :href="`tel:${usuario.telefono}`" class="text-indigo-600 hover:text-indigo-800">
                                                {{ usuario.telefono }}
                                            </a>
                                            <span v-else>-</span>
                                        </span>
                                    </div>
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Membresía</span>
                                        <span class="text-right">
                                            <span v-if="usuario.membresia_nombre" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-crown mr-1"></i>
                                                {{ usuario.membresia_nombre }}
                                                <span v-if="usuario.membresia_online" class="ml-1 text-xs font-semibold">Online</span>
                                            </span>
                                            <span v-else class="text-gray-400">Sin membresía</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update usuarios')"
                                        :href="route('usuarios.profile.show', parseInt(usuario.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-slate-100 text-slate-700 px-3 text-xs font-semibold hover:bg-slate-200 transition"
                                        title="Ver perfil"
                                    >
                                        <i class="fas fa-eye"></i>
                                        <span>Ver perfil</span>
                                    </Link>
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update usuarios')"
                                        :href="route('usuarios.edit', parseInt(usuario.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar usuario"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete usuarios')"
                                        @click="deleteUsuario(parseInt(usuario.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar usuario"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="usuarios.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            v-model:filters="filters"
                            :value="usuariosFiltrados"
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
                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            icon="pi pi-filter-slash"
                                            label="Limpiar"
                                            outlined
                                            @click="clearFilters()"
                                        />
                                        <select
                                            v-model="filtroMembresia"
                                            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        >
                                            <option value="con_membresia">Con membresía</option>
                                            <option value="todos">Mostrar todos</option>
                                        </select>
                                    </div>
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
                                            :href="route('usuarios.profile.show', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update usuarios')"
                                            v-tooltip="'Ver perfil'"
                                            class="text-slate-600 hover:text-slate-800"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
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



