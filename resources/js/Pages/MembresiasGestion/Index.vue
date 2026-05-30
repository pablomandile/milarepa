<template>
    <AppLayout title="Gestionar Membresías - Usuarios">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                <i class="fas fa-users-cog mr-2 text-indigo-600"></i>
                Gestión de Membresías de Usuarios
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Mensaje de éxito -->
                <div v-if="$page.props.flash.success" class="mb-4 p-4 rounded-lg bg-green-100 text-green-800 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ $page.props.flash.success }}
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Filtros móvil -->
                        <div v-if="usuarios.length > 0" class="sm:hidden mb-4 space-y-2">
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
                                    <option value="con_membresia">Con membresia</option>
                                    <option value="suscripcion">Solo suscripción</option>
                                    <option value="todos">Mostrar todos</option>
                                </select>
                            </div>
                            <IconField iconPosition="right" class="w-full">
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="filters['global'].value"
                                    placeholder="Buscar..."
                                    class="w-full"
                                />
                            </IconField>
                        </div>

                        <!-- Tarjetas móvil -->
                        <div v-if="usuariosFiltradosMobile.length > 0" class="space-y-4 sm:hidden">
                            <div
                                v-for="usuario in usuariosFiltradosMobile"
                                :key="usuario.id"
                                class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                            >
                                <div class="space-y-3 p-4">
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ usuario.name }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ usuario.email }}</p>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="text-sm text-gray-500">Membresía</span>
                                            <div class="text-right">
                                                <select
                                                    v-if="editandoUserId === usuario.id"
                                                    v-model="editForm.membresia_id"
                                                    class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-900"
                                                >
                                                    <option v-for="m in membresias" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                                                </select>
                                                <span v-else-if="tieneMembresiaReal(usuario)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-crown mr-1"></i>
                                                    {{ usuario.membresia.nombre }}
                                                </span>
                                                <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">
                                                    Sin membresía
                                                </span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Inscripción</span>
                                            <span>
                                                <input
                                                    v-if="editandoUserId === usuario.id"
                                                    type="date"
                                                    v-model="editForm.membresia_inscripcion_fecha"
                                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-900"
                                                />
                                                <span v-else-if="tieneMembresiaReal(usuario) && fechaInscripcion(usuario)">
                                                    {{ formatearFecha(fechaInscripcion(usuario)) }}
                                                </span>
                                                <span v-else class="text-gray-500">-</span>
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Modalidad</span>
                                            <span>
                                                <select
                                                    v-if="editandoUserId === usuario.id"
                                                    v-model="editForm.membresia_online"
                                                    class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-900"
                                                >
                                                    <option :value="false">Presencial</option>
                                                    <option :value="true">Online</option>
                                                </select>
                                                <span v-else-if="tieneMembresiaReal(usuario)">
                                                    {{ modalidadTexto(usuario) }}
                                                </span>
                                                <span v-else class="text-gray-500">-</span>
                                            </span>
                                        </div>

                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Suscripción</span>
                                            <span>
                                                <input
                                                    v-if="editandoUserId === usuario.id"
                                                    type="checkbox"
                                                    v-model="editForm.suscripcion"
                                                    class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                                />
                                                <i
                                                    v-else-if="tieneMembresiaReal(usuario) && usuario.membresia_usuario?.suscripcion"
                                                    class="fas fa-check text-emerald-600 text-lg"
                                                    v-tooltip="'Suscrito'"
                                                ></i>
                                                <span v-else class="text-gray-500">-</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <template v-if="editandoUserId === usuario.id">
                                            <button
                                                type="button"
                                                @click="guardarEdicion(usuario)"
                                                :disabled="guardando"
                                                class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-emerald-500 text-white px-3 text-xs font-semibold hover:bg-emerald-600 disabled:opacity-50 transition"
                                                title="Guardar" aria-label="Guardar"
                                            >
                                                <i class="fas fa-check"></i>
                                                <span>Guardar</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="cancelarEdicion"
                                                :disabled="guardando"
                                                class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-gray-400 text-white px-3 text-xs font-semibold hover:bg-gray-500 transition"
                                                title="Cancelar" aria-label="Cancelar"
                                            >
                                                <i class="fas fa-times"></i>
                                                <span>Cancelar</span>
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button
                                                v-if="tieneMembresiaReal(usuario)"
                                                @click="iniciarEdicion(usuario)"
                                                class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                                title="Editar membresía" aria-label="Editar membresía"
                                            >
                                                <i class="fas fa-pen-to-square"></i>
                                                <span>Editar</span>
                                            </button>
                                            <button
                                                v-else
                                                @click="abrirModalAsignar(usuario)"
                                                class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-green-500 text-white px-3 text-xs font-semibold hover:bg-green-600 transition"
                                                title="Asignar membresía" aria-label="Asignar membresía"
                                            >
                                                <i class="fas fa-plus-circle"></i>
                                                <span>Asignar</span>
                                            </button>
                                            <button
                                                v-if="tieneMembresiaReal(usuario)"
                                                @click="eliminarMembresia(usuario)"
                                                class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                                title="Eliminar membresía" aria-label="Eliminar membresía"
                                            >
                                                <i class="fas fa-trash"></i>
                                                <span>Eliminar</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="usuarios.length > 0" class="sm:hidden text-center py-8 text-gray-500 dark:text-gray-400">
                            No hay resultados con los filtros actuales
                        </div>

                        <!-- Tabla de Usuarios (desktop) -->
                        <div v-if="usuarios.length > 0" class="hidden sm:block overflow-x-auto">
                            <DataTable
                                v-model:filters="filters"
                                :value="usuariosFiltrados"
                                filterDisplay="menu"
                                :globalFilterFields="['name', 'email', 'membresia_nombre', 'modalidad_texto', 'membresia_inscripcion_fecha']"
                                stripedRows
                                paginator
                                :rows="20"
                                :rowsPerPageOptions="[10, 20, 50, 100]"
                                tableStyle="min-width: 50rem"
                            >
                                <template #header>
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
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
                                                <option value="con_membresia">Con membresia</option>
                                                <option value="todos">Mostrar todos</option>
                                            </select>
                                        </div>
                                        <IconField iconPosition="right" class="w-full sm:w-auto">
                                            <InputIcon>
                                                <i class="pi pi-search" />
                                            </InputIcon>
                                            <InputText
                                                v-model="filters['global'].value"
                                                placeholder="Buscar..."
                                                class="w-full sm:w-auto"
                                            />
                                        </IconField>
                                    </div>
                                </template>
                                <Column field="name" header="Usuario" :showFilterMatchModes="false">
                                    <template #body="{ data }">
                                        <p class="font-semibold text-gray-800 dark:text-gray-100">{{ data.name }}</p>
                                    </template>
                                    <template #filter="{ filterModel }">
                                        <InputText v-model="filterModel.value" type="text" placeholder="Buscar por usuario" class="p-column-filter" />
                                    </template>
                                </Column>
                                <Column field="email" header="Email" :showFilterMatchModes="false">
                                    <template #filter="{ filterModel }">
                                        <InputText v-model="filterModel.value" type="text" placeholder="Buscar por email" class="p-column-filter" />
                                    </template>
                                </Column>
                                <Column field="membresia_nombre" header="Membresía Actual" :showFilterMatchModes="false">
                                    <template #body="{ data }">
                                        <select
                                            v-if="editandoUserId === data.id"
                                            v-model="editForm.membresia_id"
                                            class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-900"
                                        >
                                            <option v-for="m in membresias" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                                        </select>
                                        <span v-else-if="tieneMembresiaReal(data)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-crown mr-1"></i>
                                            {{ data.membresia.nombre }}
                                            <span v-if="data.membresia_online" class="ml-2 text-xs font-semibold text-indigo-600">Online</span>
                                        </span>
                                        <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">
                                            Sin membresía
                                        </span>
                                    </template>
                                    <template #filter="{ filterModel }">
                                        <InputText v-model="filterModel.value" type="text" placeholder="Buscar por membresía" class="p-column-filter" />
                                    </template>
                                </Column>
                                <Column field="membresia_inscripcion_fecha" header="Fecha de Inscripción" :showFilterMatchModes="false">
                                    <template #body="{ data }">
                                        <input
                                            v-if="editandoUserId === data.id"
                                            type="date"
                                            v-model="editForm.membresia_inscripcion_fecha"
                                            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-900"
                                        />
                                        <span v-else-if="tieneMembresiaReal(data) && fechaInscripcion(data)" class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ formatearFecha(fechaInscripcion(data)) }}
                                        </span>
                                        <span v-else class="text-sm text-gray-500">-</span>
                                    </template>
                                    <template #filter="{ filterModel }">
                                        <InputText v-model="filterModel.value" type="text" placeholder="Buscar por fecha" class="p-column-filter" />
                                    </template>
                                </Column>
                                <Column field="modalidad_texto" header="Modalidad" :showFilterMatchModes="false">
                                    <template #body="{ data }">
                                        <select
                                            v-if="editandoUserId === data.id"
                                            v-model="editForm.membresia_online"
                                            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-900"
                                        >
                                            <option :value="false">Presencial</option>
                                            <option :value="true">Online</option>
                                        </select>
                                        <span v-else-if="tieneMembresiaReal(data)" class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ modalidadTexto(data) }}
                                        </span>
                                        <span v-else class="text-sm text-gray-500">-</span>
                                    </template>
                                    <template #filter="{ filterModel }">
                                        <Dropdown
                                            v-model="filterModel.value"
                                            :options="modalidadOptions"
                                            optionLabel="label"
                                            optionValue="value"
                                            placeholder="Todas"
                                            class="p-column-filter w-full"
                                            showClear
                                        />
                                    </template>
                                </Column>
                                <Column header="Suscripción" style="width: 8rem">
                                    <template #body="{ data }">
                                        <div v-if="editandoUserId === data.id" class="flex items-center justify-center">
                                            <input
                                                type="checkbox"
                                                v-model="editForm.suscripcion"
                                                class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            />
                                        </div>
                                        <div v-else-if="tieneMembresiaReal(data)" class="flex items-center justify-center">
                                            <i
                                                v-if="data.membresia_usuario?.suscripcion"
                                                class="fas fa-check text-emerald-600 text-lg"
                                                v-tooltip="'Suscrito'"
                                            ></i>
                                            <span v-else class="text-sm text-gray-400">—</span>
                                        </div>
                                        <span v-else class="text-sm text-gray-500">-</span>
                                    </template>
                                </Column>
                                <Column header="Acciones" style="width: 11rem">
                                    <template #body="{ data }">
                                        <div class="flex justify-center gap-2">
                                            <!-- Modo edición -->
                                            <template v-if="editandoUserId === data.id">
                                                <button
                                                    type="button"
                                                    @click="guardarEdicion(data)"
                                                    :disabled="guardando"
                                                    class="px-3 py-1 bg-emerald-500 text-white rounded hover:bg-emerald-600 disabled:opacity-50 transition text-sm"
                                                    title="Guardar" aria-label="Guardar"
                                                >
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="cancelarEdicion"
                                                    :disabled="guardando"
                                                    class="px-3 py-1 bg-gray-400 text-white rounded hover:bg-gray-500 transition text-sm"
                                                    title="Cancelar" aria-label="Cancelar"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </template>
                                            <!-- Modo normal -->
                                            <template v-else>
                                                <button
                                                    v-if="tieneMembresiaReal(data)"
                                                    @click="iniciarEdicion(data)"
                                                    class="px-3 py-1 bg-indigo-500 text-white rounded hover:bg-indigo-600 transition text-sm"
                                                    title="Editar membresía" aria-label="Editar membresía"
                                                >
                                                    <i class="fas fa-pen-to-square"></i>
                                                </button>
                                                <button
                                                    v-else
                                                    @click="abrirModalAsignar(data)"
                                                    class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition text-sm"
                                                    title="Asignar membresía" aria-label="Asignar membresía"
                                                >
                                                    <i class="fas fa-plus-circle"></i>
                                                </button>
                                                <button
                                                    v-if="tieneMembresiaReal(data)"
                                                    @click="eliminarMembresia(data)"
                                                    class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition text-sm"
                                                    title="Eliminar membresía" aria-label="Eliminar membresía"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- Mensaje si no hay usuarios -->
                        <div v-else class="text-center py-12">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400 text-lg">No hay usuarios registrados</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para asignar membresía -->
        <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    Asignar Membresía a {{ usuarioSeleccionado?.name }}
                </h3>

                <form @submit.prevent="asignarMembresia">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Seleccionar Membresía
                        </label>
                        <select 
                            v-model="formMembresiaId"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        >
                            <option value="">-- Selecciona una membresía --</option>
                            <option v-for="membresia in membresias" :key="membresia.id" :value="membresia.id">
                                {{ membresia.nombre }} - {{ membresia.entidad.nombre }}
                            </option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Modalidad
                        </label>
                        <select
                            v-model="formMembresiaOnline"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        >
                            <option value="presencial">Presencial</option>
                            <option value="online">Online</option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <button 
                            type="submit"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                        >
                            <i class="fas fa-check mr-2"></i>
                            Asignar
                        </button>
                        <button 
                            type="button"
                            @click="cerrarModal"
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-400 transition font-medium"
                        >
                            <i class="fas fa-times mr-2"></i>
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';

const modalidadOptions = [
    { label: 'Presencial', value: 'Presencial' },
    { label: 'Online', value: 'Online' },
];

const props = defineProps({
    usuarios: Array,
    membresias: Array
});

const filtroMembresia = ref('con_membresia');

const usuariosConMembresia = computed(() =>
    props.usuarios.map((usuario) => ({
        ...usuario,
        membresia_nombre: usuario.membresia && usuario.membresia.nombre ? usuario.membresia.nombre : '',
        modalidad_texto: usuario.membresia && Number(usuario.membresia.id) !== 1
            ? ((usuario.membresia_online || usuario.membresia_usuario?.membresia_online) ? 'Online' : 'Presencial')
            : ''
    }))
);

const usuariosFiltrados = computed(() => {
    if (filtroMembresia.value === 'todos') {
        return usuariosConMembresia.value;
    }

    if (filtroMembresia.value === 'suscripcion') {
        return usuariosConMembresia.value.filter((usuario) =>
            usuario.membresia
            && Number(usuario.membresia.id) !== 1
            && usuario.membresia_usuario?.suscripcion
        );
    }

    return usuariosConMembresia.value.filter((usuario) =>
        usuario.membresia && Number(usuario.membresia.id) !== 1
    );
});

const usuariosFiltradosMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    const lista = usuariosFiltrados.value;
    if (!term) return lista;
    return lista.filter((usuario) => {
        const campos = [
            usuario.name,
            usuario.email,
            usuario.membresia_nombre,
            usuario.modalidad_texto,
            usuario.membresia_inscripcion_fecha,
        ];
        return campos.some((valor) => String(valor ?? '').toLowerCase().includes(term));
    });
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    membresia_nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
    membresia_inscripcion_fecha: { value: null, matchMode: FilterMatchMode.CONTAINS },
    modalidad_texto: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const clearFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { value: null, matchMode: FilterMatchMode.CONTAINS },
        email: { value: null, matchMode: FilterMatchMode.CONTAINS },
        membresia_nombre: { value: null, matchMode: FilterMatchMode.CONTAINS },
        membresia_inscripcion_fecha: { value: null, matchMode: FilterMatchMode.CONTAINS },
        modalidad_texto: { value: null, matchMode: FilterMatchMode.EQUALS },
    };
    filtroMembresia.value = 'con_membresia';
};

const mostrarModal = ref(false);
const usuarioSeleccionado = ref(null);
const formMembresiaId = ref('');
const formMembresiaOnline = ref('presencial');

const editandoUserId = ref(null);
const guardando = ref(false);
const editForm = reactive({
    membresia_id: null,
    membresia_inscripcion_fecha: '',
    membresia_online: false,
    suscripcion: false,
});

function iniciarEdicion(usuario) {
    editandoUserId.value = usuario.id;
    const mu = usuario.membresia_usuario || {};
    editForm.membresia_id = mu.membresia_id ?? usuario.membresia?.id ?? null;
    const fecha = mu.membresia_inscripcion_fecha ?? usuario.membresia_inscripcion_fecha ?? '';
    editForm.membresia_inscripcion_fecha = fecha ? String(fecha).substring(0, 10) : '';
    editForm.membresia_online = !!(mu.membresia_online ?? usuario.membresia_online);
    editForm.suscripcion = !!mu.suscripcion;
}

function cancelarEdicion() {
    editandoUserId.value = null;
    editForm.membresia_id = null;
    editForm.membresia_inscripcion_fecha = '';
    editForm.membresia_online = false;
    editForm.suscripcion = false;
}

function guardarEdicion(usuario) {
    if (guardando.value) return;
    guardando.value = true;
    router.put(
        route('membresias.editar', usuario.id),
        {
            membresia_id: editForm.membresia_id,
            membresia_inscripcion_fecha: editForm.membresia_inscripcion_fecha || null,
            membresia_online: !!editForm.membresia_online,
            suscripcion: !!editForm.suscripcion,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelarEdicion();
            },
            onFinish: () => {
                guardando.value = false;
            },
        }
    );
}

const tieneMembresiaReal = (usuario) => {
    return usuario.membresia && usuario.membresia.id !== 1;
};

const modalidadTexto = (usuario) => {
    return (usuario.membresia_online || usuario.membresia_usuario?.membresia_online)
        ? 'Online'
        : 'Presencial';
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const fechaInscripcion = (usuario) => {
    return usuario.membresia_usuario?.membresia_inscripcion_fecha
        ?? usuario.membresia_inscripcion_fecha
        ?? null;
};

const abrirModalAsignar = (usuario) => {
    usuarioSeleccionado.value = usuario;
    formMembresiaId.value = '';
    formMembresiaOnline.value = 'presencial';
    mostrarModal.value = true;
};

const cerrarModal = () => {
    mostrarModal.value = false;
    usuarioSeleccionado.value = null;
    formMembresiaId.value = '';
    formMembresiaOnline.value = 'presencial';
};

const asignarMembresia = () => {
    if (!formMembresiaId.value) {
        Swal.fire('Error', 'Debes seleccionar una membresía', 'error');
        return;
    }

    const form = useForm({
        membresia_id: formMembresiaId.value,
        membresia_online: formMembresiaOnline.value === 'online'
    });

    form.put(route('membresias.asignar', usuarioSeleccionado.value.id), {
        onSuccess: () => {
            cerrarModal();
        }
    });
};

const eliminarMembresia = (usuario) => {
    Swal.fire({
        title: '¿Está seguro?',
        text: `Se eliminará la membresía de ${usuario.name}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = useForm({});
            form.delete(route('membresias.eliminar', usuario.id));
        }
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>






