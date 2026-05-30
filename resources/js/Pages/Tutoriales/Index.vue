<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref } from 'vue';

const props = defineProps({
    tutoriales: {
        type: Array,
        required: true,
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const tutorialesFiltradosMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    if (!term) return props.tutoriales;
    return props.tutoriales.filter((t) => {
        const campos = [t.descripcion, t.url];
        return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
    });
});

const dialogVisible = ref(false);
const editandoId = ref(null);

const form = useForm({
    url: '',
    descripcion: '',
});

const abrirDialogoNuevo = () => {
    editandoId.value = null;
    form.reset();
    form.clearErrors();
    dialogVisible.value = true;
};

const abrirDialogoEditar = (tutorial) => {
    editandoId.value = tutorial.id;
    form.url = tutorial.url;
    form.descripcion = tutorial.descripcion;
    form.clearErrors();
    dialogVisible.value = true;
};

const cerrarDialogo = () => {
    dialogVisible.value = false;
    editandoId.value = null;
    form.reset();
    form.clearErrors();
};

const guardarTutorial = () => {
    const opciones = {
        preserveScroll: true,
        onSuccess: () => {
            cerrarDialogo();
        },
    };

    if (editandoId.value) {
        form.put(route('tutoriales.update', editandoId.value), opciones);
    } else {
        form.post(route('tutoriales.store'), opciones);
    }
};

const eliminarTutorial = (tutorial) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminará el tutorial "${tutorial.descripcion}"`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.delete(route('tutoriales.destroy', tutorial.id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('¡Eliminado!', 'El tutorial fue eliminado.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'No se pudo eliminar el tutorial.', 'error');
            },
        });
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout title="Video tutoriales">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                <i class="fab fa-youtube mr-2 text-red-600"></i>
                Video tutoriales
            </h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-5xl mx-auto">
                    <div v-if="$page.props.user.permissions.includes('create tutoriales')" class="flex justify-between">
                        <button
                            type="button"
                            @click="abrirDialogoNuevo"
                            class="inline-flex items-center gap-2 text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            <i class="fas fa-plus"></i>
                            SUMAR TUTORIAL
                        </button>
                    </div>

                    <!-- Buscador móvil -->
                    <div v-if="tutoriales.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="tutorialesFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="tutorial in tutorialesFiltradosMobile"
                            :key="tutorial.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                        >
                            <a
                                v-if="tutorial.thumbnail_url"
                                :href="tutorial.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block relative bg-black"
                            >
                                <img
                                    :src="tutorial.thumbnail_url"
                                    :alt="tutorial.descripcion"
                                    class="w-full h-48 object-cover"
                                />
                                <span class="absolute inset-0 flex items-center justify-center bg-black/30 hover:bg-black/40 transition">
                                    <i class="fab fa-youtube text-red-600 text-5xl"></i>
                                </span>
                            </a>
                            <div class="space-y-3 p-4">
                                <a
                                    :href="tutorial.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block text-base font-semibold text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 break-words"
                                >
                                    {{ tutorial.descripcion }}
                                </a>
                                <p class="text-xs text-gray-500 break-all">{{ tutorial.url }}</p>
                            </div>

                            <div v-if="$page.props.user.permissions.includes('update tutoriales') || $page.props.user.permissions.includes('delete tutoriales')" class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        v-if="$page.props.user.permissions.includes('update tutoriales')"
                                        @click="abrirDialogoEditar(tutorial)"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar tutorial"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </button>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete tutoriales')"
                                        @click="eliminarTutorial(tutorial)"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar tutorial"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="tutoriales.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>
                    <div v-else-if="tutoriales.length === 0" class="sm:hidden mt-4 text-center py-10">
                        <i class="fab fa-youtube text-5xl text-gray-300 dark:text-gray-700 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">Aún no hay tutoriales cargados.</p>
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="tutoriales"
                            v-model:filters="filters"
                            :globalFilterFields="['descripcion', 'url']"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 50rem"
                        >
                            <template #header>
                                <div class="flex justify-end">
                                    <IconField iconPosition="right">
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                    </IconField>
                                </div>
                            </template>
                            <Column header="Video" style="width: 220px">
                                <template #body="{ data }">
                                    <a
                                        v-if="data.thumbnail_url"
                                        :href="data.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="relative block w-48 h-28 overflow-hidden rounded border border-gray-200 dark:border-gray-700 bg-black group"
                                        :title="data.descripcion"
                                    >
                                        <img
                                            :src="data.thumbnail_url"
                                            :alt="data.descripcion"
                                            class="w-full h-full object-cover"
                                        />
                                        <span class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/40 transition">
                                            <i class="fab fa-youtube text-red-600 text-3xl"></i>
                                        </span>
                                    </a>
                                    <span v-else class="text-sm text-gray-400">Sin preview</span>
                                </template>
                            </Column>
                            <Column field="descripcion" header="Descripción">
                                <template #body="{ data }">
                                    <a
                                        :href="data.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-medium text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 inline-flex items-center gap-1"
                                    >
                                        {{ data.descripcion }}
                                        <i class="fas fa-external-link-alt text-xs opacity-60"></i>
                                    </a>
                                </template>
                            </Column>
                            <Column header="Acciones" style="width: 160px">
                                <template #body="{ data }">
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            v-if="$page.props.user.permissions.includes('update tutoriales')"
                                            @click="abrirDialogoEditar(data)"
                                            v-tooltip="'Editar tutorial'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </button>
                                        <button
                                            v-if="$page.props.user.permissions.includes('delete tutoriales')"
                                            @click="eliminarTutorial(data)"
                                            v-tooltip="'Borrar tutorial'"
                                            class="text-red-600 hover:text-red-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1;"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-10">
                                    <i class="fab fa-youtube text-5xl text-gray-300 dark:text-gray-700 mb-3"></i>
                                    <p class="text-gray-500 dark:text-gray-400">Aún no hay tutoriales cargados.</p>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal sumar/editar tutorial -->
        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="editandoId ? 'Editar tutorial' : 'Sumar tutorial'"
            :style="{ width: '32rem' }"
            :breakpoints="{ '640px': '95vw' }"
            :closable="!form.processing"
            @hide="cerrarDialogo"
        >
            <form @submit.prevent="guardarTutorial" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        URL de YouTube
                    </label>
                    <InputText
                        v-model="form.url"
                        type="url"
                        placeholder="https://www.youtube.com/watch?v=..."
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.url }"
                    />
                    <p v-if="form.errors.url" class="mt-1 text-xs text-red-600">{{ form.errors.url }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Descripción
                    </label>
                    <InputText
                        v-model="form.descripcion"
                        type="text"
                        placeholder="Ej: Cómo crear una actividad"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.descripcion }"
                    />
                    <p v-if="form.errors.descripcion" class="mt-1 text-xs text-red-600">{{ form.errors.descripcion }}</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="cerrarDialogo"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Guardando...' : (editandoId ? 'Actualizar' : 'Guardar') }}
                    </button>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
