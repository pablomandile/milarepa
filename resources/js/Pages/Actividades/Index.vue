<script>
    export default {
        name: 'ActividadesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import { ref } from 'vue';
    import Dialog from 'primevue/dialog';
    import { usePage } from '@inertiajs/vue3';

    const page = usePage();
    const visible = ref(false);
    const actividadSeleccionada = ref(null);
    
    const { actividades } = defineProps({
        actividades: {
            type: Array,
            required: true
        }
    });

    // Controla qué filas de la tabla principal están expandidas
    const expandedRows = ref([]);

    const verActividad = (id) => {
        const actividad = actividades.find((ent) => ent.id === id);
        if (actividad) {
            actividadSeleccionada.value = actividad;
            visible.value = true;
        }
    };

    const deleteActividad = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('actividades.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Actividad ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Actividad.", "error");
                },
            });
            }
        });
    };
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-7xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create actividades')">
                        <Link :href="route('actividades.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA ACTIVIDAD
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable 
                        :value="actividades" 
                        stripedRows 
                        removableSort 
                        paginator 
                        :rows="10" 
                        v-model:expandedRows="expandedRows"
                        dataKey="id"
                        :rowsPerPageOptions="[5, 10, 20, 50]" 
                        tableStyle="min-width: 50rem"
                        >
                            <Column expander style="width: 5rem" />
                            <Column header="Imagen">
                                <template #body="slotProps">
                                    <div>
                                        <img
                                            v-if="slotProps.data.imagen"
                                            :src="'/storage/' + slotProps.data.imagen.ruta"
                                            alt="Imagen de la Actividad"
                                            style="max-width: 50px; max-height: 50px;"
                                        />
                                        <img
                                            v-else
                                            src="/storage/img/actividades/imagen-no-disponible.jpg"
                                            alt="Sin imagen"
                                            style="max-width: 50px; max-height: 50px;"
                                        />
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column field="tipo_actividad.nombre" header="Tipo" sortable></Column>
                            <Column field="fecha_inicio" header="Fecha y hora" sortable></Column>
                            <Column field="modalidad.nombre" header="Modalidad" sortable></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <a
                                            @click.prevent="verActividad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read actividades')"
                                            v-tooltip="'Ver actividad'">
                                            <i class="pi pi-eye cursor-pointer text-indigo-400 mr-2"></i>
                                        </a>
                                        <Link
                                            :href="route('actividades.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update actividades')"
                                            v-tooltip="'Editar actividad'">
                                            <i class="pi pi-pencil text-indigo-400 mr-2"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteActividad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete actividades')"
                                            v-tooltip="'Borrar actividad'">
                                            <i class="pi pi-trash cursor-pointer text-red-300"></i>
                                        </a>
                                    </div>
                                </template>
                            </Column>
                            <template #expansion="{ data }">
                                <DataTable 
                                    :value="data"
                                    class="mt-3"
                                    stripedRows
                                    tableStyle="min-width: 50rem"
                                >
                                    <!-- Columna Membresía / Entidad -->
                                    <Column header="Lugar">
                                        <template #body="{ data: act }">
                                            {{ 
                                            act.entidad.abreviacion
                                            }}
                                        </template>
                                    </Column>

                                </DataTable>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <!-- Dialog para mostrar detalles -->
    <Dialog 
        v-model:visible="visible" 
        maximizable 
        modal 
        :header="actividadSeleccionada ? `Detalles de ${actividadSeleccionada.nombre}` : 'Detalles...'"
        :style="{ width: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        dismissableMask
>
    <template v-if="actividadSeleccionada">
        <!-- Mostrar imagen solo si logo_url no está vacío -->
        <div class="mb-5" v-if="actividadSeleccionada.imagen">
            <img :src="'/storage/' + actividadSeleccionada.imagen.ruta" alt="Imagen de la Actividad" style="max-width: 640px; max-height: 480px;" />
            
        </div>

        <p class="mb-3" v-if="actividadSeleccionada.entidad.direccion && actividadSeleccionada.entidad.direccion.trim() !== ''">
            {{ actividadSeleccionada.entidad.direccion }}
        </p>

        
        <p class="mb-3" v-if="actividadSeleccionada.entidad.direccion && actividadSeleccionada.entidad.direccion.trim() !== ''">
            {{ actividadSeleccionada.entidad.direccion }}
        </p>
        
        <p class="mb-3" v-if="actividadSeleccionada.descripcion && actividadSeleccionada.descripcion.descripcion.trim() !== ''">
            {{ actividadSeleccionada.descripcion.descripcion }}
        </p>



        </template>
        <p v-else>Cargando datos...</p>
    </Dialog>
</template>