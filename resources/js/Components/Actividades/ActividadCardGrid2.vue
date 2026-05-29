<script setup>
import { computed } from 'vue';
import {
    formatPrice,
    formatFechaLarga,
    formatHora,
    direccionActividad,
    descuentoVigente,
    formatoFechaLimite,
    precioSinMembresiaNormal,
    precioSinMembresiaVigente,
    precioMembresiaUsuario,
    esInscrito,
    actividadSinInscripcionDisponible,
    textoBotonInscripcion,
    renderMarkdown,
    nombresMaestrosYCoordinadores,
} from '@/composables/useActividadHelpers';

const props = defineProps({
    actividad: { type: Object, required: true },
    flipped: { type: Boolean, default: false },
    userContext: { type: Object, default: null },
    inscripcionesIds: { type: Array, default: () => [] },
    imageSide: { type: String, default: 'left' },
});

const emit = defineEmits(['toggle-flip', 'inscribir', 'mas-info', 'abrir-mapa']);

const monthTag = computed(() => {
    if (!props.actividad?.fecha_inicio) return '';
    const meses = [
        'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
        'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE',
    ];
    const d = new Date(props.actividad.fecha_inicio);
    if (Number.isNaN(d.getTime())) return '';
    return meses[d.getMonth()];
});

const tipoLabel = computed(() => {
    return props.actividad?.tipo_actividad?.nombre || props.actividad?.tipo_actividad?.abreviacion || '';
});

const subtituloMaestros = computed(() => {
    const nombres = nombresMaestrosYCoordinadores(props.actividad);
    return nombres ? `con ${nombres}` : '';
});

const telefono = computed(() => {
    return props.actividad?.entidad?.whatsapp || props.actividad?.entidad?.telefono || '';
});

const valorTexto = computed(() => {
    const esGratis = props.actividad?.esquema_precio?.nombre === 'Actividad Gratuita';
    if (esGratis) return 'Actividad Gratuita';
    const precio = precioSinMembresiaVigente(props.actividad);
    return `$${formatPrice(precio)}`;
});

const horarioTexto = computed(() => formatHora(props.actividad?.fecha_inicio) || 'ver en el programa');
</script>

<template>
    <article class="actividad-row-grid2 w-full bg-white dark:bg-gray-800 transition-colors">
        <div
            class="row-inner flex flex-col gap-6 md:gap-10 px-2 md:px-4 py-6 md:py-10"
            :class="imageSide === 'right' ? 'md:flex-row' : 'md:flex-row-reverse'"
        >
            <!-- Bloque de texto (mitad) -->
            <div
                class="md:w-1/2 flex md:px-6"
                :class="imageSide === 'left' ? 'md:justify-end' : 'md:justify-start'"
            >
                <div class="flex flex-col gap-4 w-full md:max-w-[702px]">
                <div class="flex flex-col gap-3">
                    <p
                        v-if="monthTag"
                        class="text-2xl md:text-3xl font-light tracking-wide text-gray-700 dark:text-gray-200"
                        style="font-family: 'Cardo', 'Times New Roman', serif;"
                    >
                        {{ monthTag }}
                    </p>
                    <h2
                        class="text-3xl md:text-4xl leading-tight text-gray-900 dark:text-gray-100 font-light"
                        style="font-family: 'Cardo', 'Times New Roman', serif;"
                    >
                        <span v-if="tipoLabel" class="font-bold">{{ tipoLabel }}:</span>
                        <span>{{ ' ' }}{{ actividad.nombre }}</span>
                    </h2>
                    <p
                        v-if="subtituloMaestros"
                        class="text-lg md:text-xl text-indigo-600 dark:text-indigo-400"
                        style="font-family: 'Cardo', 'Times New Roman', serif;"
                    >
                        {{ subtituloMaestros }}
                    </p>
                </div>

                <ul class="mt-2 space-y-2 text-sm md:text-base text-gray-700 dark:text-gray-300">
                    <li class="flex items-baseline gap-3">
                        <i class="pi pi-calendar text-gray-700 dark:text-gray-300 w-5 text-center" aria-hidden="true"></i>
                        <span><strong class="font-semibold text-gray-900 dark:text-gray-100">Fecha:</strong> {{ formatFechaLarga(actividad.fecha_inicio) }}</span>
                    </li>
                    <li class="flex items-baseline gap-3">
                        <i class="pi pi-clock text-gray-700 dark:text-gray-300 w-5 text-center" aria-hidden="true"></i>
                        <span><strong class="font-semibold text-gray-900 dark:text-gray-100">Horario:</strong> {{ horarioTexto }}</span>
                    </li>
                    <li v-if="direccionActividad(actividad)" class="flex items-baseline gap-3">
                        <i class="pi pi-map-marker text-gray-700 dark:text-gray-300 w-5 text-center" aria-hidden="true"></i>
                        <span class="inline-flex items-baseline gap-2">
                            <span><strong class="font-semibold text-gray-900 dark:text-gray-100">Lugar:</strong> {{ direccionActividad(actividad) }}</span>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center text-sky-700 hover:text-sky-900 dark:text-sky-400 dark:hover:text-sky-300 shrink-0"
                                title="Ver en mapa"
                                aria-label="Ver en mapa"
                                @click="emit('abrir-mapa', direccionActividad(actividad))"
                            >
                                <i class="pi pi-map"></i>
                            </button>
                        </span>
                    </li>
                    <li v-if="telefono" class="flex items-baseline gap-3">
                        <i class="pi pi-whatsapp text-gray-700 dark:text-gray-300 w-5 text-center" aria-hidden="true"></i>
                        <span><strong class="font-semibold text-gray-900 dark:text-gray-100">Teléfono:</strong> {{ telefono }}</span>
                    </li>
                    <li class="flex items-baseline gap-3">
                        <i class="pi pi-credit-card text-gray-700 dark:text-gray-300 w-5 text-center" aria-hidden="true"></i>
                        <span>
                            <strong class="font-semibold text-gray-900 dark:text-gray-100">VALOR:</strong> {{ valorTexto }}
                            <span
                                v-if="descuentoVigente(actividad) && actividad.esquema_precio?.nombre !== 'Actividad Gratuita'"
                                class="block text-xs md:text-sm text-amber-700 dark:text-amber-400 mt-0.5"
                            >
                                Después de {{ formatoFechaLimite(actividad) }}: ${{ formatPrice(precioSinMembresiaNormal(actividad)) }}
                            </span>
                            <span
                                v-if="userContext?.membresia && userContext.membresia?.nombre !== 'Sin membresía'"
                                class="block text-xs md:text-sm text-green-700 dark:text-green-400 mt-0.5"
                            >
                                Con {{ userContext.membresia?.nombre }}: ${{ formatPrice(precioMembresiaUsuario(actividad, userContext)) }}
                            </span>
                        </span>
                    </li>
                    <li class="flex items-baseline gap-3">
                        <i class="pi pi-info-circle text-gray-700 dark:text-gray-300 w-5 text-center" aria-hidden="true"></i>
                        <button
                            type="button"
                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline font-medium"
                            @click="emit('mas-info', actividad)"
                        >
                            + INFO
                        </button>
                    </li>
                </ul>

                <div class="mt-4 flex items-center gap-3">
                    <button
                        :disabled="actividadSinInscripcionDisponible(actividad, inscripcionesIds)"
                        class="px-6 py-3 rounded-md text-sm md:text-base font-medium transition-colors flex items-center gap-2"
                        :class="actividadSinInscripcionDisponible(actividad, inscripcionesIds)
                            ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                            : 'bg-teal-600 hover:bg-teal-700 text-white shadow-sm'"
                        @click="emit('inscribir', actividad)"
                    >
                        <i v-if="esInscrito(actividad.id, inscripcionesIds)" class="pi pi-heart-fill"></i>
                        {{ textoBotonInscripcion(actividad, inscripcionesIds) }}
                    </button>
                </div>
                </div>
            </div>

            <!-- Bloque de imagen (mitad) con flip -->
            <div
                class="md:w-1/2 flex items-center justify-center md:px-6"
                :class="imageSide === 'right' ? 'md:justify-end' : 'md:justify-start'"
            >
                <div
                    class="flip-image-wrapper w-full"
                    :class="{ 'is-flipped': flipped }"
                    @click="emit('toggle-flip', actividad.id)"
                >
                    <div class="flip-image-inner">
                        <div class="flip-image-front">
                            <img
                                class="w-full h-full object-cover rounded-sm"
                                :src="actividad.imagen?.ruta ? `/storage/${actividad.imagen.ruta}` : '/storage/img/actividades/imagen-no-disponible.jpg'"
                                :alt="actividad.nombre"
                            />
                        </div>
                        <div class="flip-image-back bg-slate-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-5 md:p-7 rounded-sm overflow-y-auto">
                            <h3 class="text-lg md:text-xl font-semibold mb-3 text-gray-900 dark:text-gray-100">Descripción</h3>
                            <div
                                class="prose prose-sm dark:prose-invert max-w-none"
                                v-html="renderMarkdown(actividad.descripcion?.descripcion || 'No hay descripción disponible')"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Separador ornamental -->
        <div class="flex items-center justify-center gap-3 pb-2 md:pb-4 opacity-50 text-gray-400 dark:text-gray-600">
            <span class="h-px w-48 md:w-64 bg-current"></span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-7 h-7 shrink-0" fill="currentColor">
                <path d="M224 208C224 128.5 288.5 64 368 64C376.8 64 384 71.2 384 80L384 232.2C399 226.9 415.2 224 432 224C511.5 224 576 288.5 576 368C576 376.8 568.8 384 560 384L407.8 384C413.1 399 416 415.2 416 432C416 511.5 351.5 576 272 576C263.2 576 256 568.8 256 560L256 407.8C241 413.1 224.8 416 208 416C128.5 416 64 351.5 64 272C64 263.2 71.2 256 80 256L232.2 256C226.9 241 224 224.8 224 208zM320 352C337.7 352 352 337.7 352 320C352 302.3 337.7 288 320 288C302.3 288 288 302.3 288 320C288 337.7 302.3 352 320 352z"/>
            </svg>
            <span class="h-px w-48 md:w-64 bg-current"></span>
        </div>
    </article>
</template>

<style scoped>
.flip-image-wrapper {
    perspective: 1200px;
    cursor: pointer;
    aspect-ratio: 1 / 1;
    max-width: 702px;
}

.flip-image-inner {
    width: 100%;
    height: 100%;
    position: relative;
    transition: transform 0.7s;
    transform-style: preserve-3d;
}

.flip-image-wrapper.is-flipped .flip-image-inner {
    transform: rotateY(180deg);
}

.flip-image-front,
.flip-image-back {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
}

.flip-image-back {
    transform: rotateY(180deg);
}

@media (max-width: 767px) {
    .flip-image-wrapper {
        max-width: 100%;
        margin: 0 auto;
    }
}
</style>
