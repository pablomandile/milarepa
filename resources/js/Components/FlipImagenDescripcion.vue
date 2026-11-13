<script setup>
/**
 * Imagen que se da vuelta al tocarla (o con el botón "Descripción") y muestra
 * la descripción en el reverso. Si no hay descripción, queda la imagen sola,
 * sin botón ni flip.
 * La imagen se ve completa (con su alto natural) y define el tamaño de la
 * tarjeta; el reverso se superpone con ese mismo tamaño y scrollea si el texto
 * no entra. Lo usan la Grilla de cursos y retiros (Grid 2) y las páginas
 * públicas de Clases y de Oraciones cantadas.
 */
import { computed } from 'vue';
import { renderMarkdown } from '@/composables/useActividadHelpers';

const props = defineProps({
    src: { type: String, required: true },
    alt: { type: String, default: '' },
    descripcion: { type: String, default: '' },
    flipped: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle']);

const descripcionTexto = computed(() => props.descripcion?.trim() || '');
const tieneDescripcion = computed(() => descripcionTexto.value !== '');
const dadaVuelta = computed(() => tieneDescripcion.value && props.flipped);

const toggle = () => {
    if (tieneDescripcion.value) emit('toggle');
};

// La letra se agranda según cuánto texto haya, para que llene la tarjeta sin
// necesitar scroll (en celular queda siempre en text-base).
const descripcionTamanio = computed(() => {
    const largo = descripcionTexto.value.length;
    if (largo > 750) return '';
    if (largo > 450) return 'md:text-lg';
    return 'md:text-lg xl:text-xl';
});
</script>

<template>
    <!-- Toda la tarjeta se puede tocar; los botones son la vía visible y la de teclado. -->
    <div
        class="flip-image-wrapper w-full"
        :class="{ 'is-flipped': dadaVuelta, 'tiene-descripcion': tieneDescripcion }"
        @click="toggle"
    >
        <div class="flip-image-inner">
            <div class="flip-image-front">
                <img class="block w-full h-auto rounded-sm" :src="src" :alt="alt" />
                <button
                    v-if="tieneDescripcion"
                    type="button"
                    class="absolute bottom-3 right-3 inline-flex items-center gap-2 rounded-full bg-sky-600 hover:bg-sky-700 px-4 py-2 text-xs md:text-sm font-bold uppercase tracking-wide text-white shadow-md transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-sky-600"
                    :tabindex="dadaVuelta ? -1 : 0"
                    @click.stop="toggle"
                >
                    <i class="pi pi-refresh text-xs md:text-sm" aria-hidden="true"></i>
                    Descripción
                </button>
            </div>
            <div
                v-if="tieneDescripcion"
                class="flip-image-back flex flex-col overflow-y-auto rounded-sm bg-gradient-to-br from-indigo-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950 text-gray-700 dark:text-gray-300 p-6 md:p-10">
                <!-- my-auto centra el texto en el alto de la tarjeta sin cortar el principio si es largo -->
                <div class="my-auto">
                    <h3 class="text-2xl md:text-3xl font-semibold text-indigo-700 dark:text-indigo-300">Descripción</h3>
                    <span class="block h-1 w-16 mt-3 mb-5 md:mb-6 rounded-full bg-amber-400" aria-hidden="true"></span>
                    <div
                        class="descripcion-flip text-base dark:[--descripcion-strong:theme(colors.indigo.300)]"
                        :class="descripcionTamanio"
                        v-html="renderMarkdown(descripcionTexto)"
                    ></div>
                </div>
                <button
                    type="button"
                    class="mt-6 self-start flex items-center gap-2 text-xs md:text-sm text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                    :tabindex="dadaVuelta ? 0 : -1"
                    @click.stop="toggle"
                >
                    <i class="pi pi-replay" aria-hidden="true"></i>
                    Tocá para volver a la imagen
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.flip-image-wrapper {
    perspective: 1200px;
}

.flip-image-wrapper.tiene-descripcion {
    cursor: pointer;
}

.flip-image-inner {
    width: 100%;
    position: relative;
    transition: transform 0.7s;
    transform-style: preserve-3d;
}

.flip-image-wrapper.is-flipped .flip-image-inner {
    transform: rotateY(180deg);
}

.flip-image-front,
.flip-image-back {
    width: 100%;
    backface-visibility: hidden;
}

.flip-image-front {
    position: relative;
}

.flip-image-back {
    position: absolute;
    inset: 0;
    transform: rotateY(180deg);
}

/* renderMarkdown() le pone text-sm, leading y colores a cada párrafo (lo comparten
   otras pantallas); acá se pisan para que el texto tome el tamaño de la tarjeta.
   Van con !important porque Tailwind está con important:true (tailwind.config.js). */
.descripcion-flip :deep(p),
.descripcion-flip :deep(li) {
    font-size: inherit !important;
    line-height: 1.7 !important;
    color: inherit !important;
}

.descripcion-flip :deep(p) {
    margin-top: 0.9em !important;
}

.descripcion-flip :deep(p:first-child) {
    margin-top: 0 !important;
}

.descripcion-flip :deep(strong) {
    font-weight: 600;
    color: var(--descripcion-strong, rgb(67 56 202)); /* indigo-700; en oscuro, indigo-300 */
}

.descripcion-flip :deep(a) {
    color: rgb(79 70 229); /* indigo-600 */
    text-decoration: underline;
}
</style>
