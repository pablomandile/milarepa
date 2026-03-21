<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';

const props = defineProps({
    prestamos: {
        type: Array,
        default: () => [],
    },
});

const toast = useToast();

const receptoraFiltro = ref(null);
const showConfirmDialog = ref(false);
const prestamoAEliminar = ref(null);

const receptorasDisponibles = computed(() => {
    const mapa = new Map();

    props.prestamos.forEach((prestamo) => {
        const id = prestamo?.receptora?.id;
        const nombre = prestamo?.receptora?.nombre;

        if (!id || !nombre) {
            return;
        }

        if (!mapa.has(id)) {
            mapa.set(id, { id, nombre });
        }
    });

    return Array.from(mapa.values()).sort((a, b) => a.nombre.localeCompare(b.nombre));
});

const prestamosFiltrados = computed(() => {
    if (!receptoraFiltro.value) {
        return props.prestamos;
    }

    return props.prestamos.filter((prestamo) => Number(prestamo?.receptora?.id) === Number(receptoraFiltro.value));
});

const formatDate = (value) => {
    if (!value) return '-';

    const fecha = new Date(value);
    if (!Number.isNaN(fecha.getTime())) {
        return fecha.toLocaleDateString('es-AR');
    }

    const soloFecha = String(value).split('T')[0];
    const [year, month, day] = soloFecha.split('-').map(Number);

    if (!year || !month || !day) {
        return '-';
    }

    return new Date(year, month - 1, day).toLocaleDateString('es-AR');
};

const eliminar = (id) => {
    prestamoAEliminar.value = id;
    showConfirmDialog.value = true;
};

const confirmarEliminacion = () => {
    if (!prestamoAEliminar.value) {
        showConfirmDialog.value = false;
        return;
    }

    router.delete(route('inventario-libros.prestamos-anexos.destroy', prestamoAEliminar.value), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmDialog.value = false;
            prestamoAEliminar.value = null;
            toast.add({
                severity: 'success',
                summary: 'Préstamos',
                detail: 'Préstamo eliminado correctamente.',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Préstamos',
                detail: 'No se pudo eliminar el préstamo.',
                life: 3500,
            });
        },
    });
};

const cancelarEliminacion = () => {
    showConfirmDialog.value = false;
    prestamoAEliminar.value = null;
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Prestamos Anexos" />

    <AppLayout>
        <Toast position="top-right" />
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Prestamos Anexos</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between flex-wrap gap-2">
                        <div class="flex gap-2">
                            <Link :href="route('inventario-libros.prestamos-anexos.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                                NUEVO PRESTAMO
                            </Link>
                            <Link :href="route('inventario-libros.index')" class="text-gray-800 bg-slate-200 hover:bg-slate-300 py-2 px-4 rounded">
                                VOLVER
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="mb-4 max-w-sm">
                            <label for="receptora_filtro" class="block text-sm font-medium text-gray-700 mb-1">
                                Filtrar por receptora
                            </label>
                            <select
                                id="receptora_filtro"
                                v-model="receptoraFiltro"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option :value="null">Todas</option>
                                <option v-for="receptora in receptorasDisponibles" :key="receptora.id" :value="receptora.id">
                                    {{ receptora.nombre }}
                                </option>
                            </select>
                        </div>

                        <DataTable
                            :value="prestamosFiltrados"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50]"
                            tableStyle="min-width: 60rem"
                        >
                            <Column header="Fecha">
                                <template #body="{ data }">
                                    {{ formatDate(data.fecha) }}
                                </template>
                            </Column>
                            <Column header="Prestadora">
                                <template #body="{ data }">
                                    {{ data.prestadora?.nombre ?? '-' }}
                                </template>
                            </Column>
                            <Column header="Receptora">
                                <template #body="{ data }">
                                    {{ data.receptora?.nombre ?? '-' }}
                                </template>
                            </Column>
                            <Column header="Libro">
                                <template #body="{ data }">
                                    {{ data.libro?.titulo ?? '-' }}
                                </template>
                            </Column>
                            <Column field="cantidad" header="Cantidad" />
                            <Column header="Usuario">
                                <template #body="{ data }">
                                    {{ data.usuario?.name ?? '-' }}
                                </template>
                            </Column>
                            <Column header="Acciones" style="width: 180px">
                                <template #body="{ data }">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('inventario-libros.prestamos-anexos.edit', data.id)"
                                            v-tooltip="'Editar préstamo'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="eliminar(data.id)"
                                            v-tooltip="'Borrar préstamo'"
                                            class="text-red-600 hover:text-red-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
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

    <Dialog
        v-model:visible="showConfirmDialog"
        modal
        header="Confirmar borrado"
        :style="{ width: '34rem' }"
        :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
    >
        <div class="text-sm text-gray-700">
            ¿Desea eliminar este préstamo anexo?
        </div>

        <template #footer>
            <div class="flex justify-end space-x-2">
                <button
                    @click="cancelarEliminacion"
                    class="py-2 px-4 rounded-md font-medium transition-colors bg-gray-500 text-white hover:bg-gray-600"
                >
                    Cancelar
                </button>
                <button
                    @click="confirmarEliminacion"
                    class="py-2 px-4 rounded-md font-medium transition-colors bg-red-600 text-white hover:bg-red-700"
                >
                    Sí, eliminar
                </button>
            </div>
        </template>
    </Dialog>
</template>
