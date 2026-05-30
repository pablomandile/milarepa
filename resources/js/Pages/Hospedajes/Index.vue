<script>
    export default {
        name: 'HospedajesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import Dialog from 'primevue/dialog';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';
    import { computed, ref } from 'vue';

    const props = defineProps({
        hospedajes: {
            type: Array,
            required: true
        }
    });
    const hospedajes = props.hospedajes;

    const visible = ref(false);
    const hospedajeSeleccionado = ref(null);

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const formatPrice = (valor) => `$ ${parseFloat(valor || 0).toLocaleString('es-AR', { minimumFractionDigits: 0 })}`;

    const hospedajesFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.hospedajes;
        return props.hospedajes.filter((h) => {
            const campos = [h.nombre, h.descripcion, h.lugar_hospedaje?.nombre, h.boton_pago?.nombre];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });

    const verHospedaje = (id) => {
        const hospedaje = hospedajes.find((hosp) => hosp.id === id);
        if (hospedaje) {
            hospedajeSeleccionado.value = hospedaje;
            visible.value = true;
        }
    };

    const deleteHospedaje = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('hospedajes.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Hospedaje ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Hospedaje.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Acomodaciones de Hospedaje</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create hospedajes')">
                        <Link :href="route('hospedajes.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA ACOMODACIÓN
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="hospedajes.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="hospedajesFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="hospedaje in hospedajesFiltradosMobile"
                            :key="hospedaje.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-bed text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ hospedaje.nombre }}</p>
                                        <p class="text-lg font-bold text-green-700 mt-1">{{ formatPrice(hospedaje.precio) }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Descripción</span>
                                        <span class="text-right">{{ hospedaje.descripcion || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Lugar</span>
                                        <span class="text-right">{{ hospedaje.lugar_hospedaje?.nombre || '—' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Botón de Pago</span>
                                        <span class="text-right">{{ hospedaje.boton_pago?.nombre || 'Sin botón' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        v-if="$page.props.user.permissions.includes('read hospedajes')"
                                        @click="verHospedaje(parseInt(hospedaje.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-100 text-indigo-700 px-3 text-xs font-semibold hover:bg-indigo-200 transition"
                                        title="Ver acomodación"
                                    >
                                        <i class="fas fa-eye"></i>
                                        <span>Ver detalle</span>
                                    </button>
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update hospedajes')"
                                        :href="route('hospedajes.edit', parseInt(hospedaje.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar acomodación"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete hospedajes')"
                                        @click="deleteHospedaje(parseInt(hospedaje.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar acomodación"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="hospedajes.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="hospedajes"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'descripcion', 'lugar_hospedaje.nombre', 'boton_pago.nombre']"
                            stripedRows
                            paginator
                            :rows="5"
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
                            <Column field="lugar_hospedaje" header="Lugar">
                                <template #body="slotProps">
                                    {{ slotProps.data.lugar_hospedaje ? slotProps.data.lugar_hospedaje.nombre : 'â€”' }}
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            @click="verHospedaje(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read hospedajes')"
                                            v-tooltip="'Ver acomodación'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1;"></i>
                                        </button>
                                        <Link
                                            :href="route('hospedajes.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update hospedajes')"
                                            v-tooltip="'Editar acomodación'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteHospedaje(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete hospedajes')"
                                            v-tooltip="'Borrar acomodación'"
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

    <!-- Modal de informaciÃ³n de hospedaje -->
    <Dialog 
        v-model:visible="visible" 
        modal 
        :header="hospedajeSeleccionado ? hospedajeSeleccionado.nombre : 'Detalles de AcomodaciÃ³n'"
        :style="{ width: '45rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <template v-if="hospedajeSeleccionado">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 shadow-inner">
                <!-- Nombre y Precio destacados -->
                <div class="bg-white dark:bg-gray-800 rounded-lg p-5 mb-4 shadow-sm border-l-4 border-indigo-500">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-bed text-indigo-600 text-xl mr-3"></i>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ hospedajeSeleccionado.nombre }}</h3>
                            </div>
                            <div class="flex items-center mt-3 bg-green-50 rounded-md px-3 py-2 w-fit">
                                <i class="fas fa-dollar-sign text-green-600 text-lg mr-2"></i>
                                <span class="text-2xl font-bold text-green-700">
                                    {{ parseFloat(hospedajeSeleccionado.precio).toLocaleString('es-AR', { minimumFractionDigits: 0 }) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DescripciÃ³n -->
                <div v-if="hospedajeSeleccionado.descripcion && hospedajeSeleccionado.descripcion.trim() !== ''" 
                     class="bg-white dark:bg-gray-800 rounded-lg p-5 mb-4 shadow-sm">
                    <div class="flex items-start">
                        <i class="fas fa-align-left text-indigo-600 text-lg mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">DescripciÃ³n</h4>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ hospedajeSeleccionado.descripcion }}</p>
                        </div>
                    </div>
                </div>

                <!-- Lugar de Hospedaje -->
                <div v-if="hospedajeSeleccionado.lugar_hospedaje" 
                     class="bg-white dark:bg-gray-800 rounded-lg p-5 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt text-indigo-600 text-lg mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Lugar de Hospedaje</h4>
                            <p class="text-gray-600 dark:text-gray-400">{{ hospedajeSeleccionado.lugar_hospedaje.nombre }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <p v-else class="text-center text-gray-500">Cargando datos...</p>
    </Dialog>
</template>
