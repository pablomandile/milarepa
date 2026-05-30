<script>
    export default {
        name: 'BotonesPagoIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';
    import { computed, ref } from 'vue';

    const props = defineProps({
        botones: {
            type: Array,
            required: true
        }
    })

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const botonesFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.botones;
        return props.botones.filter((b) => {
            const campos = [b.nombre, b.descripcion, b.metodo_pago?.nombre];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });

    const deleteBotonPago = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('botonespago.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Botón de Pago ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Botón de Pago.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Botones de Pago</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-5xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create botonpago')">
                        <Link :href="route('botonespago.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO BOTÓN DE PAGO
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="botones.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="botonesFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="boton in botonesFiltradosMobile"
                            :key="boton.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-credit-card text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ boton.nombre }}</p>
                                        <p v-if="boton.metodo_pago?.nombre" class="text-sm text-gray-600 dark:text-gray-400">{{ boton.metodo_pago.nombre }}</p>
                                    </div>
                                    <a
                                        v-if="boton.link"
                                        :href="boton.link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200 flex-shrink-0"
                                        :title="boton.link"
                                    >
                                        <i class="fas fa-link"></i>
                                    </a>
                                </div>
                                <div v-if="boton.descripcion" class="space-y-2">
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Descripción</span>
                                        <span class="text-right">{{ boton.descripcion }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update botonpago')"
                                        :href="route('botonespago.edit', parseInt(boton.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar botón"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete botonpago')"
                                        @click="deleteBotonPago(parseInt(boton.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar botón"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="botones.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="botones"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'descripcion', 'metodo_pago.nombre']"
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
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="descripcion" header="Descripción"></Column>
                            <Column header="Links">
                                <template #body="{ data }">
                                    <a
                                        v-if="data.link"
                                        :href="data.link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200"
                                        :title="data.link"
                                        :aria-label="data.link"
                                    >
                                        <i class="fas fa-link"></i>
                                        <span class="sr-only">{{ data.link }}</span>
                                    </a>
                                    <span v-else class="text-gray-400">-</span>
                                </template>
                            </Column>
                            <Column header="Método de Pago">
                                <template #body="{ data }">
                                    {{ data.metodo_pago?.nombre || 'Sin método' }}
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2" >
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('botonespago.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update botonpago')"
                                            v-tooltip="'Editar botón'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteBotonPago(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete botonpago')"
                                            v-tooltip="'Borrar botón'"
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
