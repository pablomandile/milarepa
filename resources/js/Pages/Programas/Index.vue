<script>
    export default {
        name: 'ProgramasIndex'
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
    import Dialog from 'primevue/dialog';
    import Textarea from 'primevue/textarea';


    const props = defineProps({
        programas: {
            type: Array,
            required: true
        }
    });
    const programas = props.programas;

    const visible = ref(false);
    const programaSeleccionado = ref('');

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS }
    });

    const programasFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.programas;
        return props.programas.filter((p) => {
            const campos = [p.nombre, p.programa];
            return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
        });
    });
    
    const deletePrograma = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('programas.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Programa ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema el eliminar la Programa.", "error");
                },
            });
            }
        });
    };

    const verPrograma = (id) => {
        const programa = programas.find((pro) => pro.id === id);
        if (programa) {
            programaSeleccionado.value = programa;
            visible.value = true;
        }
    };
    
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Programas</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create programas')">
                        <Link :href="route('programas.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO PROGRAMA
                        </Link>
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="programas.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="programasFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="programa in programasFiltradosMobile"
                            :key="programa.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-book-open text-2xl text-indigo-600 mt-1"></i>
                                    <p class="text-base font-semibold text-gray-800 dark:text-gray-100 flex-1 min-w-0 break-words">{{ programa.nombre }}</p>
                                </div>
                                <p v-if="programa.programa" class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">
                                    {{ programa.programa }}
                                </p>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        v-if="$page.props.user.permissions.includes('read programas')"
                                        @click="verPrograma(parseInt(programa.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-100 text-indigo-700 px-3 text-xs font-semibold hover:bg-indigo-200 transition"
                                        title="Ver programa"
                                    >
                                        <i class="fas fa-eye"></i>
                                        <span>Ver detalle</span>
                                    </button>
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update programas')"
                                        :href="route('programas.edit', parseInt(programa.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar programa"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete programas')"
                                        @click="deletePrograma(parseInt(programa.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar programa"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="programas.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable v-model:filters="filters" :value="programas" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem"
                        :globalFilterFields="['programa', 'nombre']">
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
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column field="programa" header="Programa">
                                <template #body="slotProps">
                                    {{ slotProps.data.programa.substring(0, 200) }} ...
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            @click="verPrograma(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read programas')"
                                            v-tooltip="'Ver programa'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1;"></i>
                                        </button>
                                        <Link
                                            :href="route('programas.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update programas')"
                                            v-tooltip="'Editar programa'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deletePrograma(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete programas')"
                                            v-tooltip="'Borrar programa'"
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
    <Dialog 
        v-model:visible="visible" 
        maximizable 
        modal 
        :header="programaSeleccionado ? `${programaSeleccionado.nombre}` : 'Detalles...'" 
        :style="{ width: '60rem', minHeight: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        dismissableMask
    >
        <template v-if="programaSeleccionado">
            <div class="flex items-center justify-center max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Textarea v-model="programaSeleccionado.programa" variant="filled" rows="20" cols="120" readonly />
            </div>
        </template>
        <p v-else>Cargando datos...</p>
    </Dialog>
</template>