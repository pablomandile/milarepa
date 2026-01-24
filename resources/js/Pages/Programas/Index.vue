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
    import { ref } from 'vue';
    import { FilterMatchMode } from 'primevue/api';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import InputText from 'primevue/inputtext';
    import Dialog from 'primevue/dialog';
    import Textarea from 'primevue/textarea';

    
    const { programas } = defineProps({
        programas: {
            type: Object,
            required: true
        }
    });

    const visible = ref(false);
    const programaSeleccionado = ref('');

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS }
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
        const programa = programas.data.find((pro) => pro.id === id);
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Programas</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create programas')">
                        <Link :href="route('programas.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO PROGRAMA
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable v-model:filters="filters" :value="programas.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem"
                        :globalFilterFields="['programa', 'nombre']">
                            <template #header>
                                <div class="flex justify-end">
                                    <IconField 
                                        iconPosition="left"
                                        class="border border-gray-300 rounded-md px-3 py-2 flex items-center">
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