<script>
export default {
    name: 'ProgramaEstudiosAsignacionUsuarios',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    programaEstudios: {
        type: Array,
        required: true,
    },
});

const filtroPrograma = ref('todos');
const filtroBusqueda = ref('');

const editingUserId = ref(null);
const editingValor = ref(null);
const editingDistancia = ref(false);
const guardando = ref(false);

const opcionesFiltro = [
    { label: 'Todos', value: 'todos' },
    { label: 'Con programa', value: 'con' },
    { label: 'Sin programa', value: 'sin' },
];

const opcionesProgramaParaEditar = computed(() => [
    { id: null, nombre: 'Sin Programa', abreviacion: null },
    ...props.programaEstudios,
]);

const usersFiltrados = computed(() => {
    const termino = filtroBusqueda.value.trim().toLowerCase();
    return props.users.filter((u) => {
        if (filtroPrograma.value === 'con' && !u.programa_estudio_id) return false;
        if (filtroPrograma.value === 'sin' && u.programa_estudio_id) return false;
        if (termino) {
            const matchNombre = (u.name || '').toLowerCase().includes(termino);
            const matchEmail = (u.email || '').toLowerCase().includes(termino);
            if (!matchNombre && !matchEmail) return false;
        }
        return true;
    });
});

function nombrePrograma(u) {
    if (!u.programa_estudio) return null;
    const abrev = u.programa_estudio.abreviacion ? ` (${u.programa_estudio.abreviacion})` : '';
    return `${u.programa_estudio.nombre}${abrev}`;
}

function iniciarEdicion(u) {
    editingUserId.value = u.id;
    editingValor.value = u.programa_estudio_id ?? null;
    editingDistancia.value = !!u.programa_a_distancia;
}

function cancelarEdicion() {
    editingUserId.value = null;
    editingValor.value = null;
    editingDistancia.value = false;
}

function guardarEdicion(u) {
    if (guardando.value) return;
    guardando.value = true;
    router.patch(
        route('programa-estudios.asignacion-usuarios.update', u.id),
        {
            programa_estudio_id: editingValor.value,
            programa_a_distancia: editingValor.value ? editingDistancia.value : null,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                cancelarEdicion();
            },
            onFinish: () => {
                guardando.value = false;
            },
        }
    );
}
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Asignación Usuario / Programa de Estudio
            </h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 rounded-lg shadow">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Mostrando {{ usersFiltrados.length }} de {{ users.length }} usuarios
                        </p>
                        <Link
                            :href="route('programa-estudios.index')"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            Volver
                        </Link>
                    </div>

                    <!-- Filtros -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Filtrar por programa
                            </label>
                            <Dropdown
                                v-model="filtroPrograma"
                                :options="opcionesFiltro"
                                optionLabel="label"
                                optionValue="value"
                                class="w-full border border-gray-300 dark:border-gray-600"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Buscar por nombre o email
                            </label>
                            <InputText
                                v-model="filtroBusqueda"
                                placeholder="Escribí para filtrar..."
                                class="w-full border border-gray-300 dark:border-gray-600"
                            />
                        </div>
                    </div>

                    <DataTable
                        :value="usersFiltrados"
                        stripedRows
                        paginator
                        :rows="20"
                        :rowsPerPageOptions="[10, 20, 50, 100]"
                        tableStyle="min-width: 50rem"
                        dataKey="id"
                    >
                        <Column field="name" header="Nombre" sortable></Column>
                        <Column field="email" header="Email" sortable></Column>
                        <Column header="Programa de Estudio">
                            <template #body="{ data }">
                                <Dropdown
                                    v-if="editingUserId === data.id"
                                    v-model="editingValor"
                                    :options="opcionesProgramaParaEditar"
                                    optionLabel="nombre"
                                    optionValue="id"
                                    placeholder="Sin Programa"
                                    class="w-full border border-gray-300 dark:border-gray-600"
                                    showClear
                                />
                                <span v-else-if="data.programa_estudio" class="text-sm text-gray-800 dark:text-gray-100">
                                    {{ nombrePrograma(data) }}
                                </span>
                                <span v-else class="inline-block px-2 py-1 text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-900/50 dark:text-gray-400 rounded">
                                    Sin Programa
                                </span>
                            </template>
                        </Column>
                        <Column header="Distancia" style="width: 7rem">
                            <template #body="{ data }">
                                <div v-if="editingUserId === data.id" class="flex items-center justify-center">
                                    <input
                                        type="checkbox"
                                        v-model="editingDistancia"
                                        :disabled="!editingValor"
                                        class="h-5 w-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                </div>
                                <div v-else class="flex items-center justify-center">
                                    <i
                                        v-if="data.programa_a_distancia"
                                        class="fas fa-check text-emerald-600 text-lg"
                                        v-tooltip="'A distancia'"
                                    ></i>
                                </div>
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 9rem">
                            <template #body="{ data }">
                                <div class="flex justify-center items-center space-x-4">
                                    <template v-if="editingUserId === data.id">
                                        <button
                                            type="button"
                                            @click="guardarEdicion(data)"
                                            :disabled="guardando"
                                            v-tooltip="'Guardar'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i
                                                class="fas fa-check"
                                                style="font-size: 18px !important; line-height: 1; color: rgb(16, 185, 129);"
                                            ></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="cancelarEdicion"
                                            :disabled="guardando"
                                            v-tooltip="'Cancelar'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i
                                                class="fas fa-xmark"
                                                style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"
                                            ></i>
                                        </button>
                                    </template>
                                    <button
                                        v-else
                                        type="button"
                                        @click="iniciarEdicion(data)"
                                        v-tooltip="'Editar programa'"
                                        style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                    >
                                        <i
                                            class="fas fa-pen-to-square"
                                            style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"
                                        ></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
