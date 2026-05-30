<script>
    export default {
        name: 'TransportesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import { computed, ref } from 'vue';
    import { FilterMatchMode } from 'primevue/api';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import InputText from 'primevue/inputtext';

    const props = defineProps({
        transportes: {
            type: Array,
            required: true
        }
    });
    const transportes = props.transportes;

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS }
    });

    const formatPrice = (valor) => `$ ${parseFloat(valor || 0).toLocaleString('es-AR', { minimumFractionDigits: 0 })}`;

    const transportesFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.transportes;
        return props.transportes.filter((t) => {
            const campos = [t.descripcion, t.boton_pago?.nombre];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });
    
    const deleteTransporte = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('transportes.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Transporte ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Transporte.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Transportes</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create transportes')">
                        <Link :href="route('transportes.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO TRANSPORTE
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="transportes.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="transportesFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="transporte in transportesFiltradosMobile"
                            :key="transporte.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-bus text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ transporte.descripcion || '-' }}</p>
                                        <p class="text-lg font-bold text-green-700 mt-1">{{ formatPrice(transporte.precio) }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Botón de Pago</span>
                                        <span class="text-right">{{ transporte.boton_pago?.nombre || 'Sin botón' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update transportes')"
                                        :href="route('transportes.edit', parseInt(transporte.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar transporte"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete transportes')"
                                        @click="deleteTransporte(parseInt(transporte.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar transporte"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="transportes.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable v-model:filters="filters" :value="transportes" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem"
                        :globalFilterFields="['descripcion']">
                            <template #header>
                                <div class="flex justify-end">
                                    <IconField 
                                        iconPosition="left"
                                        class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 flex items-center">
                                        <InputIcon class="text-gray-400">
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText
                                            v-model="filters['global'].value"
                                            placeholder="Búsqueda"
                                            class="w-full border-0 focus:outline-none focus:ring-0 ml-5"
                                        />
                                    </IconField>
                                </div>
                            </template>
                            <Column field="descripcion" header="Descripción" sortable></Column>
                            <Column header="Boton de Pago">
                                <template #body="slotProps">
                                    {{ slotProps.data.boton_pago?.nombre || 'Sin boton' }}
                                </template>
                            </Column>
                            <Column field="precio" header="Precio">
                                <template #body="slotProps">
                                    $ {{ parseFloat(slotProps.data.precio).toLocaleString('es-AR', { minimumFractionDigits: 0 }) }}
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('transportes.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update transportes')"
                                            v-tooltip="'Editar transporte'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteTransporte(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete transportes')"
                                            v-tooltip="'Borrar transporte'"
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
