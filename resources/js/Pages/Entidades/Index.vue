<script>
    export default {
        name: 'EntidadesIndex'
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
    const entidadSeleccionada = ref(null);
    
    const { entidades } = defineProps({
        entidades: {
            type: Object,
            required: true
        }
    });

    const verEntidad = (id) => {
        const entidad = entidades.data.find((ent) => ent.id === id);
        if (entidad) {
            entidadSeleccionada.value = entidad;
            visible.value = true;
        }
    };

    const deleteEntidad = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('entidades.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Entidad ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Entidad.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Entidades</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-7xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create entidades')">
                        <Link :href="route('entidades.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA ENTIDAD
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="entidades.data" stripedRows removableSort paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column field="direccion" header="Dirección" sortable></Column>
                            <Column field="telefono" header="Teléfono" sortable></Column>
                            <Column field="email1" header="Correo electrónico" sortable></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            @click="verEntidad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read entidades')"
                                            v-tooltip="'Ver entidad'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </button>
                                        <Link
                                            :href="route('entidades.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update entidades')"
                                            v-tooltip="'Editar entidad'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteEntidad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete entidades')"
                                            v-tooltip="'Borrar entidad'"
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
        :header="entidadSeleccionada ? entidadSeleccionada.nombre : 'Detalles de la Entidad'"
        :style="{ width: '55rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <template v-if="entidadSeleccionada">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 shadow-inner">
                <!-- Logo y nombre destacado -->
                <div class="bg-white rounded-lg p-5 mb-4 shadow-sm border-l-4 border-indigo-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1">
                            <i class="fas fa-building text-indigo-600 text-2xl mr-3"></i>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">{{ entidadSeleccionada.nombre }}</h3>
                                <p v-if="entidadSeleccionada.abreviacion && entidadSeleccionada.abreviacion.trim() !== ''" 
                                   class="text-sm text-gray-500 mt-1">
                                    {{ entidadSeleccionada.abreviacion }}
                                </p>
                            </div>
                        </div>
                        <div v-if="entidadSeleccionada.logo_url" class="ml-4">
                            <img :src="entidadSeleccionada.logo_url" 
                                 alt="Logo" 
                                 class="max-w-[120px] max-h-[60px] object-contain" />
                        </div>
                    </div>
                    <!-- Badge de entidad principal -->
                    <div v-if="entidadSeleccionada.entidad_principal === 1" 
                         class="mt-3 inline-flex items-center bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm font-semibold">
                        <i class="fas fa-star text-indigo-600 mr-2"></i>
                        Entidad Principal
                    </div>
                </div>

                <!-- Descripción -->
                <div v-if="entidadSeleccionada.descripcion && entidadSeleccionada.descripcion.trim() !== ''" 
                     class="bg-white rounded-lg p-5 mb-4 shadow-sm">
                    <div class="flex items-start">
                        <i class="fas fa-align-left text-indigo-600 text-lg mr-3 mt-1"></i>
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Descripción</h4>
                            <p class="text-gray-600 leading-relaxed">{{ entidadSeleccionada.descripcion }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Información de contacto -->
                    <div class="bg-white rounded-lg p-5 shadow-sm">
                        <h4 class="font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-address-card text-indigo-600 text-lg mr-2"></i>
                            Contacto
                        </h4>
                        
                        <div class="space-y-3">
                            <div v-if="entidadSeleccionada.direccion && entidadSeleccionada.direccion.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fas fa-map-marker-alt text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Dirección</p>
                                    <p class="text-gray-700 text-sm">{{ entidadSeleccionada.direccion }}</p>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.telefono && entidadSeleccionada.telefono.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fas fa-phone text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Teléfono</p>
                                    <a :href="`tel:${entidadSeleccionada.telefono}`" 
                                       class="text-gray-700 hover:text-indigo-600 transition-colors text-sm">
                                        {{ entidadSeleccionada.telefono }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.whatsapp && entidadSeleccionada.whatsapp.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fab fa-whatsapp text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">WhatsApp</p>
                                    <a :href="`https://wa.me/${entidadSeleccionada.whatsapp.replace(/[^0-9]/g, '')}`" 
                                       target="_blank"
                                       class="text-gray-700 hover:text-indigo-600 transition-colors text-sm">
                                        {{ entidadSeleccionada.whatsapp }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.email1 && entidadSeleccionada.email1.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fas fa-envelope text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Email</p>
                                    <a :href="`mailto:${entidadSeleccionada.email1}`" 
                                       class="text-gray-700 hover:text-indigo-600 transition-colors text-sm break-all">
                                        {{ entidadSeleccionada.email1 }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.email2 && entidadSeleccionada.email2.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fas fa-envelope text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Email 2</p>
                                    <a :href="`mailto:${entidadSeleccionada.email2}`" 
                                       class="text-gray-700 hover:text-indigo-600 transition-colors text-sm break-all">
                                        {{ entidadSeleccionada.email2 }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Redes sociales y web -->
                    <div class="bg-white rounded-lg p-5 shadow-sm">
                        <h4 class="font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-share-nodes text-indigo-600 text-lg mr-2"></i>
                            Redes y Web
                        </h4>
                        
                        <div class="space-y-3">
                            <div v-if="entidadSeleccionada.web_uri && entidadSeleccionada.web_uri.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fas fa-globe text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Sitio Web</p>
                                    <a :href="entidadSeleccionada.web_uri" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-indigo-600 hover:text-indigo-800 transition-colors text-sm flex items-center break-all">
                                        {{ entidadSeleccionada.web_uri }}
                                        <i class="fas fa-external-link-alt text-xs ml-1"></i>
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.instagram_uri && entidadSeleccionada.instagram_uri.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fab fa-instagram text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Instagram</p>
                                    <a :href="entidadSeleccionada.instagram_uri" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-indigo-600 hover:text-indigo-800 transition-colors text-sm break-all">
                                        {{ entidadSeleccionada.instagram_uri }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.facebook_uri && entidadSeleccionada.facebook_uri.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fab fa-facebook text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Facebook</p>
                                    <a :href="entidadSeleccionada.facebook_uri" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-indigo-600 hover:text-indigo-800 transition-colors text-sm break-all">
                                        {{ entidadSeleccionada.facebook_uri }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.twitter_uri && entidadSeleccionada.twitter_uri.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fab fa-twitter text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Twitter</p>
                                    <a :href="entidadSeleccionada.twitter_uri" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-indigo-600 hover:text-indigo-800 transition-colors text-sm break-all">
                                        {{ entidadSeleccionada.twitter_uri }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.youtube_uri && entidadSeleccionada.youtube_uri.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fab fa-youtube text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">YouTube</p>
                                    <a :href="entidadSeleccionada.youtube_uri" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-indigo-600 hover:text-indigo-800 transition-colors text-sm break-all">
                                        {{ entidadSeleccionada.youtube_uri }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="entidadSeleccionada.spotify_uri && entidadSeleccionada.spotify_uri.trim() !== ''" 
                                 class="flex items-start">
                                <i class="fab fa-spotify text-indigo-600 mr-3 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-0.5">Spotify</p>
                                    <a :href="entidadSeleccionada.spotify_uri" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="text-indigo-600 hover:text-indigo-800 transition-colors text-sm break-all">
                                        {{ entidadSeleccionada.spotify_uri }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <p v-else class="text-center text-gray-500">Cargando datos...</p>
    </Dialog>
</template>