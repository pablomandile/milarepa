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
                            <Column header="Descripción">
                                <template #body="slotProps">
                                    <span v-tooltip="{ content: slotProps.data.descripcion }">
                                        {{ slotProps.data.descripcion.substring(0, 10) }}...
                                    </span>
                                </template>
                            </Column>
                            <Column field="direccion" header="Dirección" sortable></Column>
                            <Column field="telefono" header="Teléfono" sortable></Column>
                            <Column field="email1" header="Correo electrónico" sortable></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <a
                                            @click.prevent="verEntidad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read entidades')">
                                            <i class="pi pi-eye cursor-pointer text-indigo-400 mr-2"></i>
                                        </a>
                                        <Link
                                            :href="route('entidades.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update entidades')">
                                            <i class="pi pi-pencil text-indigo-400 mr-2"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteEntidad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete entidades')">
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
    header="Detalles de la Entidad" 
    :style="{ width: '50rem' }" 
    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
>
    <template v-if="entidadSeleccionada">
        <!-- Mostrar imagen solo si logo_url no está vacío -->
        <div class="mb-5" v-if="entidadSeleccionada.logo_uri">
            <img :src="entidadSeleccionada.logo_uri" alt="Logo de la Entidad" style="max-width: 200px; max-height: 100px;" />
            
        </div>
        
        <!-- Las demás líneas se muestran sólo si los campos no están vacíos -->
        <p v-if="entidadSeleccionada.nombre && entidadSeleccionada.nombre.trim() !== ''">
            <strong>Nombre:</strong> {{ entidadSeleccionada.nombre }}
        </p>
        <p v-if="entidadSeleccionada.descripcion && entidadSeleccionada.descripcion.trim() !== ''">
            <strong>Descripción:</strong> {{ entidadSeleccionada.descripcion }}
        </p>
        <p v-if="entidadSeleccionada.abreviacion && entidadSeleccionada.abreviacion.trim() !== ''">
            <strong>Abreviación:</strong> {{ entidadSeleccionada.abreviacion }}
        </p>
        <p v-if="entidadSeleccionada.direccion && entidadSeleccionada.direccion.trim() !== ''">
            <strong>Dirección:</strong> {{ entidadSeleccionada.direccion }}
        </p>
        <p v-if="entidadSeleccionada.telefono && entidadSeleccionada.telefono.trim() !== ''">
            <strong>Teléfono:</strong> {{ entidadSeleccionada.telefono }}
        </p>
        <p v-if="entidadSeleccionada.whatsapp && entidadSeleccionada.whatsapp.trim() !== ''">
            <strong>Whatsapp:</strong> {{ entidadSeleccionada.whatsapp }}
        </p>
        <p v-if="entidadSeleccionada.web_uri && entidadSeleccionada.web_uri.trim() !== ''">
            <strong>Web:</strong> <a :href="entidadSeleccionada.web_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.web_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.instagram_uri && entidadSeleccionada.instagram_uri.trim() !== ''">
            <strong>Instagram:</strong> <a :href="entidadSeleccionada.instagram_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.instagram_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.facebook_uri && entidadSeleccionada.facebook_uri.trim() !== ''">
            <strong>Facebook:</strong> <a :href="entidadSeleccionada.facebook_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.facebook_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.twitter_uri && entidadSeleccionada.twitter_uri.trim() !== ''">
            <strong>Twitter:</strong> <a :href="entidadSeleccionada.twitter_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.twitter_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.youtube_uri && entidadSeleccionada.youtube_uri.trim() !== ''">
            <strong>Youtube:</strong> <a :href="entidadSeleccionada.youtube_uri" target="_blank" rel="noopener noreferrer">
            {{ entidadSeleccionada.youtube_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.spotify_uri && entidadSeleccionada.spotify_uri.trim() !== ''">
            <strong>Spotify:</strong> <a :href="entidadSeleccionada.spotify_uri" target="_blank" rel="noopener noreferrer">
                {{ entidadSeleccionada.spotify_uri }} </a>
        </p>
        <p v-if="entidadSeleccionada.email1 && entidadSeleccionada.email1.trim() !== ''">
            <strong>Email:</strong> {{ entidadSeleccionada.email1 }}
        </p>
        <p v-if="entidadSeleccionada.email2 && entidadSeleccionada.email2.trim() !== ''">
            <strong>Email Secundario:</strong> {{ entidadSeleccionada.email2 }}
        </p>

        <!-- Mostrar solo si entidad_principal es true -->
        <p class="mt-3 text-center text-indigo-500" v-if="entidadSeleccionada.entidad_principal === 1">
            {{ entidadSeleccionada.nombre }}<strong> es la entidad principal.</strong>
        </p>
    </template>
    <p v-else>Cargando datos...</p>
</Dialog>


</template>