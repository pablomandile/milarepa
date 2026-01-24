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

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

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
        :header="lugarHospedajeSeleccionado ? lugarHospedajeSeleccionado.nombre : 'Detalles del Lugar'"
        :style="{ width: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <template v-if="lugarHospedajeSeleccionado">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 shadow-inner">
                <!-- Nombre destacado -->
                <div class="bg-white rounded-lg p-5 mb-4 shadow-sm border-l-4 border-indigo-500">
                    <div class="flex items-center">
                        <i class="fas fa-hotel text-indigo-600 text-2xl mr-3"></i>
                        <h3 class="text-xl font-bold text-gray-800">{{ lugarHospedajeSeleccionado.nombre }}</h3>
                    </div>
                </div>

                <!-- Descripción -->
                <div v-if="lugarHospedajeSeleccionado.descripcion && lugarHospedajeSeleccionado.descripcion.trim() !== ''" 
                     class="bg-white rounded-lg p-5 mb-4 shadow-sm">
                    <div class="flex items-start">
                        <i class="fas fa-align-left text-indigo-600 text-lg mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Descripción</h4>
                            <p class="text-gray-600 leading-relaxed">{{ lugarHospedajeSeleccionado.descripcion }}</p>
                        </div>
                    </div>
                </div>

                <!-- Información de contacto -->
                <div class="bg-white rounded-lg p-5 shadow-sm space-y-4">
                    <h4 class="font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-address-card text-indigo-600 text-lg mr-2"></i>
                        Información de Contacto
                    </h4>
                    
                    <div v-if="lugarHospedajeSeleccionado.direccion && lugarHospedajeSeleccionado.direccion.trim() !== ''" 
                         class="flex items-start pl-2">
                        <i class="fas fa-map-marker-alt text-indigo-600 text-lg mr-3 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Dirección</p>
                            <p class="text-gray-700">{{ lugarHospedajeSeleccionado.direccion }}</p>
                        </div>
                    </div>

                    <div v-if="lugarHospedajeSeleccionado.telefono && lugarHospedajeSeleccionado.telefono.trim() !== ''" 
                         class="flex items-start pl-2">
                        <i class="fas fa-phone text-indigo-600 text-lg mr-3 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Teléfono</p>
                            <a :href="`tel:${lugarHospedajeSeleccionado.telefono}`" 
                               class="text-gray-700 hover:text-indigo-600 transition-colors">
                                {{ lugarHospedajeSeleccionado.telefono }}
                            </a>
                        </div>
                    </div>

                    <div v-if="lugarHospedajeSeleccionado.email && lugarHospedajeSeleccionado.email.trim() !== ''" 
                         class="flex items-start pl-2">
                        <i class="fas fa-envelope text-indigo-600 text-lg mr-3 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Email</p>
                            <a :href="`mailto:${lugarHospedajeSeleccionado.email}`" 
                               class="text-gray-700 hover:text-indigo-600 transition-colors">
                                {{ lugarHospedajeSeleccionado.email }}
                            </a>
                        </div>
                    </div>

                    <div v-if="lugarHospedajeSeleccionado.web && lugarHospedajeSeleccionado.web.trim() !== ''" 
                         class="flex items-start pl-2">
                        <i class="fas fa-globe text-indigo-600 text-lg mr-3 mt-0.5"></i>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Sitio Web</p>
                            <a :href="lugarHospedajeSeleccionado.web" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="text-indigo-600 hover:text-indigo-800 transition-colors flex items-center">
                                {{ lugarHospedajeSeleccionado.web }}
                                <i class="fas fa-external-link-alt text-xs ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <p v-else class="text-center text-gray-500">Cargando datos...</p>
    </Dialog>
</template>