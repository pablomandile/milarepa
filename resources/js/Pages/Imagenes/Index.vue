<script>
    export default {
        name: 'ImagenesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router, useForm } from '@inertiajs/vue3';
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
        router.delete(route('entidades.destroy', id), {
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

    function submitUpload() {
        form.post(route('imagenes.store'), {
            onSuccess: () => {
                Swal.fire("¡Subida!", "La Imagen se ha subido con éxito.", "success");
                form.reset('imagen');
            },
            onError: () => {
                Swal.fire("Error", "Hubo un problema al subir la Imagen.", "error");
            },
        });
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
                            accept="image/*" 
                            chooseLabel="Elegir imagen"
                            cancelLabel="Cancelar"
                            uploadLabel="Subir"
                            @select="(e) => form.imagen = e.files[0]"
                         />
                         <div v-if="form.errors.imagen" class="text-red-500 text-sm mt-1">
                            {{ form.errors.imagen }}
                        </div>

                        <button
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded h-12"
                            @click.prevent="submitUpload"
                            :disabled="form.processing"
                        >
                            Subir
                        </button>

                    </div>
                    <div class="mt-4">
                        <DataView :value="imagenes" :layout="layout">
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
                                                        :src="`/storage/img/actividades/${item.nombre}`"
                                                        :alt="item.nombre"
                                                        style="max-width: 90px"
                                                    />
                                                </div>
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