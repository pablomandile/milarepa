<script>
    export default {
        name: 'ComidasIndex'
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
        comidas: {
            type: Array,
            required: true
        }
    })

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const formatPrice = (valor) => `$ ${parseFloat(valor || 0).toLocaleString('es-AR', { minimumFractionDigits: 0 })}`;

    const comidasFiltradasMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.comidas;
        return props.comidas.filter((c) => {
            const campos = [c.nombre, c.descripcion, c.boton_pago?.nombre];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });

    const deleteComida = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('comidas.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Comida ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Comida.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Comidas</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create comidas')">
                        <Link :href="route('comidas.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA COMIDA
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="comidas.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="comidasFiltradasMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="comida in comidasFiltradasMobile"
                            :key="comida.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-utensils text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ comida.nombre }}</p>
                                        <p class="text-lg font-bold text-green-700 mt-1">{{ formatPrice(comida.precio) }}</p>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span v-if="comida.vegano" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="pi pi-check"></i> Vegano
                                            </span>
                                            <span v-if="comida.celiaco" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="pi pi-check"></i> Celíaco
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Descripción</span>
                                        <span class="text-right">{{ comida.descripcion || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Botón de Pago</span>
                                        <span class="text-right">{{ comida.boton_pago?.nombre || 'Sin botón' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update comidas')"
                                        :href="route('comidas.edit', parseInt(comida.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar comida"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete comidas')"
                                        @click="deleteComida(parseInt(comida.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar comida"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="comidas.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="comidas"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'descripcion', 'boton_pago.nombre']"
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
                            <Column header="Botón de Pago">
                                <template #body="slotProps">
                                    {{ slotProps.data.boton_pago?.nombre || 'Sin botón' }}
                                </template>
                            </Column>
                            <Column field="precio" header="Precio"></Column>
                            <Column header="Vegano">
                                <template #body="slotProps">
                                    <div class="flex justify-center">
                                    <!-- Muestra el icono "check" en verde si es true, "times" en rojo si es false -->
                                    <i v-if="slotProps.data.vegano" class="pi pi-check text-green-500"></i>
                                    <i v-else class="pi pi-times text-red-500"></i>
                                    </div>
                                </template>
                            </Column>
                            <Column header="Celí­aco">
                                <template #body="slotProps">
                                    <div class="flex justify-center">
                                    <!-- Muestra el icono "check" en verde si es true, "times" en rojo si es false -->
                                    <i v-if="slotProps.data.celiaco" class="pi pi-check text-green-500"></i>
                                    <i v-else class="pi pi-times text-red-500"></i>
                                    </div>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('comidas.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update comidas')"
                                            v-tooltip="'Editar comida'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteComida(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete comidas')"
                                            v-tooltip="'Borrar comida'"
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
