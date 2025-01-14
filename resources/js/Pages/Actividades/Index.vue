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
            type: Object,
            required: true
        }
    });

    const verActividad = (id) => {
        const entidad = actividades.data.find((ent) => ent.id === id);
        if (entidad) {
            actividadSeleccionada.value = entidad;
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
                        <DataTable :value="actividades.data" stripedRows removableSort paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column field="direccion" header="Dirección" sortable></Column>
                            <Column field="fecha_inicio" header="Fecha" sortable></Column>
                            <Column field="modalidad" header="Modalidad" sortable></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <a
                                            @click.prevent="verActividad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read actividades')"
                                            v-tooltip="'Ver entidad'">
                                            <i class="pi pi-eye cursor-pointer text-indigo-400 mr-2"></i>
                                        </a>
                                        <Link
                                            :href="route('actividades.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update actividades')"
                                            v-tooltip="'Editar entidad'">
                                            <i class="pi pi-pencil text-indigo-400 mr-2"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteActividad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete actividades')"
                                            v-tooltip="'Borrar entidad'">
                                            <i class="pi pi-trash cursor-pointer text-red-300"></i>
                                        </a>
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
    :header="actividadSeleccionada ? `Detalles de ${actividadSeleccionada.nombre}` : 'Detalles...'"

    :style="{ width: '50rem' }" 
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
>
    <template v-if="actividadSeleccionada">
        <!-- Mostrar imagen solo si logo_url no está vacío -->
        <div class="mb-5" v-if="actividadSeleccionada.logo_uri">
            <img :src="actividadSeleccionada.imagen" alt="Logo de la Actividad" style="max-width: 200px; max-height: 100px;" />
            
        </div>
        
        <p class="mb-3" v-if="actividadSeleccionada.descripcion && actividadSeleccionada.descripcion.trim() !== ''">
            {{ actividadSeleccionada.descripcion }}
        </p>
        <p v-if="actividadSeleccionada.abreviacion && actividadSeleccionada.abreviacion.trim() !== ''">
            <i class="pi pi-arrow-down-left-and-arrow-up-right-to-center mr-1"></i> {{ actividadSeleccionada.abreviacion }}
        </p>
        <p v-if="actividadSeleccionada.direccion && actividadSeleccionada.direccion.trim() !== ''">
            <i class="pi pi-map-marker mr-1"></i> {{ actividadSeleccionada.direccion }}
        </p>
        <p v-if="actividadSeleccionada.telefono && actividadSeleccionada.telefono.trim() !== ''">
            <i class="pi pi-phone mr-1"></i> {{ actividadSeleccionada.telefono }}
        </p>
        <p v-if="actividadSeleccionada.whatsapp && actividadSeleccionada.whatsapp.trim() !== ''">
            <i class="pi pi-whatsapp mr-1"></i> {{ actividadSeleccionada.whatsapp }}
        </p>
        <p v-if="actividadSeleccionada.web_uri && actividadSeleccionada.web_uri.trim() !== ''">
            <i class="pi pi-link mr-1"></i> <a :href="actividadSeleccionada.web_uri" target="_blank" rel="noopener noreferrer">
                {{ actividadSeleccionada.web_uri }} </a>
        </p>
        <p v-if="actividadSeleccionada.instagram_uri && actividadSeleccionada.instagram_uri.trim() !== ''">
            <i class="pi pi-instagram mr-1"></i> <a :href="actividadSeleccionada.instagram_uri" target="_blank" rel="noopener noreferrer">
                {{ actividadSeleccionada.instagram_uri }} </a>
        </p>
        <p v-if="actividadSeleccionada.facebook_uri && actividadSeleccionada.facebook_uri.trim() !== ''">
            <i class="pi pi-facebook mr-1"></i> <a :href="actividadSeleccionada.facebook_uri" target="_blank" rel="noopener noreferrer">
                {{ actividadSeleccionada.facebook_uri }} </a>
        </p>
        <p v-if="actividadSeleccionada.twitter_uri && actividadSeleccionada.twitter_uri.trim() !== ''">
            <i class="pi pi-twitter mr-1"></i> <a :href="actividadSeleccionada.twitter_uri" target="_blank" rel="noopener noreferrer">
                {{ actividadSeleccionada.twitter_uri }} </a>
        </p>
        <p v-if="actividadSeleccionada.youtube_uri && actividadSeleccionada.youtube_uri.trim() !== ''">
            <i class="pi pi-youtube mr-1"></i> <a :href="actividadSeleccionada.youtube_uri" target="_blank" rel="noopener noreferrer">
            {{ actividadSeleccionada.youtube_uri }} </a>
        </p>
        <p v-if="actividadSeleccionada.spotify_uri && actividadSeleccionada.spotify_uri.trim() !== ''">
            <i class="pi pi-headphones mr-1"></i> <a :href="actividadSeleccionada.spotify_uri" target="_blank" rel="noopener noreferrer">
                {{ actividadSeleccionada.spotify_uri }} </a>
        </p>
        <p v-if="actividadSeleccionada.email1 && actividadSeleccionada.email1.trim() !== ''">
            <i class="pi pi-envelope mr-1"></i> {{ actividadSeleccionada.email1 }}
        </p>
        <p v-if="actividadSeleccionada.email2 && actividadSeleccionada.email2.trim() !== ''">
            <i class="pi pi-envelope mr-1"></i> {{ actividadSeleccionada.email2 }}
        </p>

        <!-- Mostrar solo si entidad_principal es true -->
        <p class="mt-3 text-center text-indigo-500" v-if="actividadSeleccionada.entidad_principal === 1">
            {{ actividadSeleccionada.nombre }}<strong> es la entidad principal.</strong>
        </p>
        </template>
        <p v-else>Cargando datos...</p>
    </Dialog>
</template>