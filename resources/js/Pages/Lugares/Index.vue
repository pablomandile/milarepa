<script>
export default {
    name: 'LugaresIndex',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref } from 'vue';

const props = defineProps({
    lugares: {
        type: Array,
        required: true,
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const lugaresFiltradosMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    if (!term) return props.lugares;
    return props.lugares.filter((l) => {
        const campos = [l.nombre, l.direccion, l.telefono, l.email1];
        return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
    });
});

const deleteLugar = (id) => {
    Swal.fire({
        title: 'Estas seguro?',
        text: 'Esta accion no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.delete(route('lugares.destroy', id), {
            onSuccess: () => {
                Swal.fire('Eliminado', 'El lugar ha sido eliminado.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'Hubo un problema al eliminar el lugar.', 'error');
            },
        });
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Lugares</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-7xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create entidades')">
                        <Link :href="route('lugares.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            NUEVO LUGAR
                        </Link>
                    </div>

                    <!-- Buscador móvil -->
                    <div v-if="lugares.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="lugaresFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="lugar in lugaresFiltradosMobile"
                            :key="lugar.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-map-marker-alt text-2xl text-indigo-600"></i>
                                    <p class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ lugar.nombre }}</p>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Dirección</span>
                                        <span class="text-right">{{ lugar.direccion || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Teléfono</span>
                                        <span class="text-right">
                                            <a v-if="lugar.telefono" :href="`tel:${lugar.telefono}`" class="text-indigo-600 hover:text-indigo-800">
                                                {{ lugar.telefono }}
                                            </a>
                                            <span v-else>-</span>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Email</span>
                                        <span class="text-right break-all">
                                            <a v-if="lugar.email1" :href="`mailto:${lugar.email1}`" class="text-indigo-600 hover:text-indigo-800">
                                                {{ lugar.email1 }}
                                            </a>
                                            <span v-else>-</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update entidades')"
                                        :href="route('lugares.edit', parseInt(lugar.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar lugar"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete entidades')"
                                        @click="deleteLugar(parseInt(lugar.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar lugar"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="lugares.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="lugares"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'direccion', 'telefono', 'email1']"
                            stripedRows
                            removableSort
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
                            <Column field="nombre" header="Nombre" sortable />
                            <Column field="direccion" header="Direccion" sortable />
                            <Column field="telefono" header="Telefono" sortable />
                            <Column field="email1" header="Correo electronico" sortable />

                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('lugares.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update entidades')"
                                            v-tooltip="'Editar lugar'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>

                                        <button
                                            @click="deleteLugar(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete entidades')"
                                            v-tooltip="'Borrar lugar'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
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

