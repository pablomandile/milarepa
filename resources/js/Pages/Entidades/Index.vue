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
        maximizable 
        modal 
        :header="entidadSeleccionada ? `Detalles de ${entidadSeleccionada.nombre}` : 'Detalles...'"
        :style="{ width: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        dismissableMask
    >
    <template v-if="entidadSeleccionada">
        <!-- Mostrar imagen solo si logo_url no está vacío -->
        <div class="mb-5" v-if="entidadSeleccionada.logo_uri">
            <img :src="entidadSeleccionada.logo_uri" alt="Logo de la Entidad" style="max-width: 200px; max-height: 100px;" />
            
        </div>
        
        <p class="mb-3" v-if="entidadSeleccionada.descripcion && entidadSeleccionada.descripcion.trim() !== ''">
            {{ entidadSeleccionada.descripcion }}
        </p>
        <p v-if="entidadSeleccionada.abreviacion && entidadSeleccionada.abreviacion.trim() !== ''">
            <i class="pi pi-arrow-down-left-and-arrow-up-right-to-center mr-1"></i> {{ entidadSeleccionada.abreviacion }}
        </p>
        <p v-if="entidadSeleccionada.direccion && entidadSeleccionada.direccion.trim() !== ''">
            <i class="pi pi-map-marker mr-1"></i> {{ entidadSeleccionada.direccion }}
        </p>
        <p v-if="entidadSeleccionada.telefono && entidadSeleccionada.telefono.trim() !== ''">
            <i class="pi pi-phone mr-1"></i> {{ entidadSeleccionada.telefono }}
        </p>
        <p v-if="entidadSeleccionada.whatsapp && entidadSeleccionada.whatsapp.trim() !== ''">
            <i class="pi pi-whatsapp mr-1"></i> {{ entidadSeleccionada.whatsapp }}
        </p>
        <p v-if="entidadSeleccionada.web_uri && entidadSeleccionada.web_uri.trim() !== ''">
            <i class="pi pi-link mr-1"></i> <a :href="entidadSeleccionada.web_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.web_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.instagram_uri && entidadSeleccionada.instagram_uri.trim() !== ''">
            <i class="pi pi-instagram mr-1"></i> <a :href="entidadSeleccionada.instagram_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.instagram_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.facebook_uri && entidadSeleccionada.facebook_uri.trim() !== ''">
            <i class="pi pi-facebook mr-1"></i> <a :href="entidadSeleccionada.facebook_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.facebook_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.twitter_uri && entidadSeleccionada.twitter_uri.trim() !== ''">
            <i class="pi pi-twitter mr-1"></i> <a :href="entidadSeleccionada.twitter_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.twitter_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.youtube_uri && entidadSeleccionada.youtube_uri.trim() !== ''">
            <i class="pi pi-youtube mr-1"></i> <a :href="entidadSeleccionada.youtube_uri" target="_blank" rel="noopener noreferrer">
            {{ entidadSeleccionada.youtube_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.spotify_uri && entidadSeleccionada.spotify_uri.trim() !== ''">
            <i class="pi pi-headphones mr-1"></i> <a :href="entidadSeleccionada.spotify_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.spotify_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.email1 && entidadSeleccionada.email1.trim() !== ''">
            <i class="pi pi-envelope mr-1"></i> {{ entidadSeleccionada.email1 }}
        </p>
        <p v-if="entidadSeleccionada.email2 && entidadSeleccionada.email2.trim() !== ''">
            <i class="pi pi-envelope mr-1"></i> {{ entidadSeleccionada.email2 }}
        </p>

        <!-- Mostrar solo si entidad_principal es true -->
        <p class="mt-3 text-center text-indigo-500" v-if="entidadSeleccionada.entidad_principal === 1">
            {{ entidadSeleccionada.nombre }}<strong> es la entidad principal.</strong>
        </p>
        </template>
        <p v-else>Cargando datos...</p>
    </Dialog>
</template>