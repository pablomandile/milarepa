<script>
    export default {
        name: 'ImagenesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import { ref } from 'vue';
    import ImageUploader from '@/Components/ImageUploader.vue';
    import Dialog from 'primevue/dialog';

    const showDialog = ref(false);
    const selectedImage = ref(null);

    defineProps({
        imagenes: {
            type: Array,
            required: true,
            default: () => []
        },
        links: {
            type: Array,
            default: () => []
        },
    });

    // Placeholder autocontenido (data-URI): se usa cuando el archivo referenciado no
    // existe en storage o la ruta está rota. Al ser inline nunca da 404.
    const IMAGEN_FALLBACK = 'data:image/svg+xml;utf8,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">' +
        '<rect width="200" height="200" fill="#e5e7eb"/>' +
        '<text x="100" y="105" font-family="sans-serif" font-size="14" fill="#6b7280" text-anchor="middle">Sin imagen</text>' +
        '</svg>'
    );

    // Si el <img> no puede cargar el archivo, lo reemplaza por el placeholder.
    // dataset.fallback evita un bucle si el propio placeholder fallara.
    const onImgError = (event) => {
        const el = event?.target;
        if (!el || el.dataset.fallback) return;
        el.dataset.fallback = '1';
        el.src = IMAGEN_FALLBACK;
    };

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
                        preserveScroll: true,
                        onSuccess: () => {
                        Swal.fire("¡Eliminado!", "La Imagen ha sido eliminada.", "success");
                        },
                        onError: (errors) => {
                        Swal.fire(
                            "No se pudo eliminar",
                            errors?.imagen || "Hubo un problema al eliminar la imagen.",
                            "warning"
                        );
                        },
                });
            }
        });
    };

    // Abre el diálogo con la imagen seleccionada (visor ampliado)
    function onClickImage(item) {
        selectedImage.value = item;
        showDialog.value = true;
    }

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Galería de Imágenes</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg max-w-7xl mx-auto">
                    <!-- Barra superior: uploader + contador -->
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div v-if="$page.props.user.permissions.includes('create entidades')">
                            <ImageUploader />
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 sm:pt-2">
                            <i class="pi pi-images"></i>
                            <span>{{ imagenes.length }} imagen(es) en esta página</span>
                        </div>
                    </div>

                    <!-- Grilla uniforme con overlay -->
                    <div v-if="imagenes.length" class="mt-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        <div
                            v-for="item in imagenes"
                            :key="item.id"
                            class="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 shadow-sm hover:shadow-md transition"
                        >
                            <img
                                :src="`/storage/${item.ruta}`"
                                :alt="item.nombre"
                                loading="lazy"
                                @error="onImgError"
                                @click="onClickImage(item)"
                                class="h-full w-full object-cover cursor-zoom-in transition duration-300 group-hover:scale-105"
                            />

                            <!-- Overlay con el nombre: visible en mobile, al hover en desktop -->
                            <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition">
                                <p class="truncate text-xs font-medium text-white" :title="item.nombre">{{ item.nombre }}</p>
                            </div>

                            <!-- Acciones arriba-derecha -->
                            <div class="absolute right-2 top-2 flex gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition">
                                <button
                                    type="button"
                                    @click="onClickImage(item)"
                                    v-tooltip="'Ver imagen'"
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/85 text-gray-700 shadow hover:bg-white"
                                >
                                    <i class="pi pi-eye"></i>
                                </button>
                                <button
                                    type="button"
                                    @click="deleteImagen(item.id)"
                                    v-tooltip="'Eliminar imagen'"
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/85 text-red-600 shadow hover:bg-red-600 hover:text-white"
                                >
                                    <i class="pi pi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estado vacío -->
                    <div v-else class="mt-6 flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 py-16 text-center">
                        <i class="pi pi-images text-4xl text-gray-400 dark:text-gray-500"></i>
                        <p class="text-base font-medium text-gray-600 dark:text-gray-300">No hay imágenes en la galería</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Subí la primera imagen con el botón de arriba.</p>
                    </div>

                    <!-- Paginación -->
                    <div v-if="links.length > 3" class="mt-8 flex justify-center gap-2">
                        <Link
                            v-for="link in links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-4 py-2 rounded transition',
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Visor / lightbox -->
        <Dialog
            v-model:visible="showDialog"
            modal
            :header="selectedImage ? selectedImage.nombre : 'Imagen'"
            :style="{ width: '60rem' }"
            :breakpoints="{ '1199px': '85vw', '575px': '95vw' }"
            dismissableMask
        >
            <template v-if="selectedImage">
                <div class="flex flex-col items-center gap-4">
                    <img
                        :src="`/storage/${selectedImage.ruta}`"
                        :alt="selectedImage.nombre"
                        @error="onImgError"
                        class="max-w-full h-auto rounded"
                    />
                    <a
                        :href="`/storage/${selectedImage.ruta}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 text-sm text-indigo-500 hover:text-indigo-600 hover:underline"
                    >
                        <i class="pi pi-external-link"></i>
                        Abrir en pestaña nueva
                    </a>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
