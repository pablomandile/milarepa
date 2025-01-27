<script>
    export default {
        name: 'ImagenesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { router, useForm } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import { ref } from 'vue';
    import DataView from 'primevue/dataview';
    import DataViewLayoutOptions from 'primevue/dataviewlayoutoptions';
    import FileUpload from 'primevue/fileupload';


    const layout = ref('grid');

    const form = useForm({
        imagen: null,
    });
    
    defineProps({
        imagenes: {
            type: Array,
            required: true,
            default: () => []
        }
    });

    const deleteImagen = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('imagenes.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Imagen ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Imagen.", "error");
                },
            });
            }
        });
    };

    function handleUpload(event) {
        console.log('handleUpload disparado con archivos:', event.files);

        // event.files es un array de archivos seleccionados
        if (event.files && event.files.length > 0) {
            // Tomamos el primer archivo (o todos, si quieres múltiples)
            form.imagen = event.files[0];

            // Enviamos la petición con Inertia
            form.post(route('imagenes.store'), {
            onSuccess: () => {
                // Opcional: limpieza, mensaje, etc.
                form.reset('imagen');
            },
            onError: (errors) => {
                // Manejo de errores
            }
            });
        }
    }

    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Imagenes</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-7xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create entidades')">
                        <FileUpload 
                            :auto="false"
                            name="demo[]"
                            :customUpload="true"
                            accept="image/*" 
                            chooseLabel="Elegir imagen"
                            cancelLabel="Cancelar"
                            uploadLabel="Subir"
                            :uploadHandler="handleUpload"

                         />
                         <div v-if="form.errors.imagen" class="text-red-500 text-sm mt-1">
                            {{ form.errors.imagen }}
                        </div>

                    </div>
                    <div class="mt-4">
                        <DataView :value="imagenes" :layout="layout" paginator :rows="16" :rowsPerPageOptions="[5, 10, 20, 50]">
                            <template #header>
                                <div class="flex justify-content-end">
                                    <DataViewLayoutOptions v-model="layout" />
                                </div>
                            </template>

                            <template #list="slotProps">
                                <div class="grid grid-nogutter">
                                    <div v-for="(item, index) in slotProps.items" :key="index" class="col-12 sm:col-6 md:col-4 xl:col-12 p-2">
                                        <div class="p-4 border-1 surface-border surface-card border-round flex flex-column">
                                            <div class="surface-50 flex justify-content-left border-round p-3">
                                                <div class="relative mx-10">
                                                    <img
                                                        class="border-round w-full"
                                                        :src="`/storage/img/actividades/${item.nombre}`"
                                                        :alt="item.nombre"
                                                        style="max-width: 50px"
                                                    />
                                                </div>
                                                <div>{{ item.nombre }}</div>
                                            </div>
                                            <!-- Botón para eliminar debajo de la imagen -->
                                            <div class="mt-2 flex justify-content-start">
                                                <!-- Ejemplo usando un simple botón con Tailwind -->
                                                <button
                                                    class="bg-red-400 hover:bg-red-700 text-white py-1 px-2 rounded flex items-center gap-1"
                                                    @click="deleteImagen(item.id)"
                                                >
                                                    <i class="pi pi-trash"></i>
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template #grid="slotProps">
                                <div class="grid grid-nogutter">
                                    <div v-for="(item, index) in slotProps.items" :key="index" class="col-3 p-2">
                                        <div class="p-4 border-1 surface-border surface-card border-round flex flex-column">
                                            <div class="surface-50 flex justify-content-center border-round p-3">
                                                <div class="relative mx-auto">
                                                    <img
                                                        class="border-round w-full"
                                                        :src="`${item.ruta}`"
                                                        :alt="item.nombre"
                                                        style="max-width: 90px"
                                                    />
                                                </div>
                                            </div>
                                            <!-- Botón para eliminar debajo de la imagen -->
                                            <div class="mt-2 flex justify-content-center">
                                                <button
                                                    class="bg-red-400 hover:bg-red-700 text-white py-1 px-2 rounded flex items-center gap-1"
                                                    @click="deleteImagen(item.id)"
                                                >
                                                    <i class="pi pi-trash"></i>
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataView>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>