<script>
    export default {
        name: 'LugaresHospedajeIndex'
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
    const lugarHospedajeSeleccionado = ref(null);
    
    const { lugareshospedaje } = defineProps({
        lugareshospedaje: {
            type: Object,
            required: true
        }
    });

    const verLugarHospedaje = (id) => {
        const lugar = lugareshospedaje.data.find((ent) => ent.id === id);
        if (lugar) {
            lugarHospedajeSeleccionado.value = lugar;
            visible.value = true;
        }
    };
    
    const deleteLugarHospedaje = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('lugareshospedaje.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Lugares de Hospedaje ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema el eliminar la Lugares de Hospedaje.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Lugares de Hospedaje</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create lugares_hospedaje')">
                        <Link :href="route('lugareshospedaje.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA LUGARES DE HOSPEDAJE
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="lugareshospedaje.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="direccion" header="Dirección"></Column>
                            <Column field="telefono" header="Teléfono"></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            @click="verLugarHospedaje(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read entidades')"
                                            v-tooltip="'Ver lugar'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </button>
                                        <Link
                                            :href="route('lugareshospedaje.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update lugares_hospedaje')"
                                            v-tooltip="'Editar lugar'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteLugarHospedaje(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete lugares_hospedaje')"
                                            v-tooltip="'Borrar lugar'"
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
    <!-- Dialog para mostrar detalles -->
    <Dialog 
        v-model:visible="visible" 
        modal 
        :header="lugarHospedajeSeleccionado ? `Detalles de ${lugarHospedajeSeleccionado.nombre}` : 'Detalles...'"

        :style="{ width: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        dismissableMask
    >
    <template v-if="lugarHospedajeSeleccionado">
        <p class="mb-3" v-if="lugarHospedajeSeleccionado.descripcion && lugarHospedajeSeleccionado.descripcion.trim() !== ''">
            <i class="pi pi-sparkles mr-1"></i> {{ lugarHospedajeSeleccionado.descripcion }}
        </p>
        <p v-if="lugarHospedajeSeleccionado.direccion && lugarHospedajeSeleccionado.direccion.trim() !== ''">
            <i class="pi pi-map-marker mr-1"></i> {{ lugarHospedajeSeleccionado.direccion }}
        </p>
        <p v-if="lugarHospedajeSeleccionado.telefono && lugarHospedajeSeleccionado.telefono.trim() !== ''">
            <i class="pi pi-phone mr-1"></i> {{ lugarHospedajeSeleccionado.telefono }}
        </p>
        <p v-if="lugarHospedajeSeleccionado.email && lugarHospedajeSeleccionado.email.trim() !== ''">
            <i class="pi pi-envelope mr-1"></i> {{ lugarHospedajeSeleccionado.email }}
        </p>
        <p v-if="lugarHospedajeSeleccionado.web && lugarHospedajeSeleccionado.web.trim() !== ''">
            <a :href="lugarHospedajeSeleccionado.web" target="_blank" rel="noopener noreferrer">
                <i class="pi pi-link mr-1"></i>
                {{ lugarHospedajeSeleccionado.web }} </a>
        </p>
        </template>
        <p v-else>Cargando datos...</p>
    </Dialog>
</template>