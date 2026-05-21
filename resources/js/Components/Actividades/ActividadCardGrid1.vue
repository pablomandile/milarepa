<script setup>
import {
    formatPrice,
    formatFechaLarga,
    direccionActividad,
    descuentoVigente,
    formatoFechaLimite,
    precioSinMembresiaNormal,
    precioSinMembresiaVigente,
    precioMembresiaUsuario,
    serviciosDisponibles,
    esInscrito,
    actividadSinInscripcionDisponible,
    textoBotonInscripcion,
    renderMarkdown,
} from '@/composables/useActividadHelpers';

const props = defineProps({
    actividad: { type: Object, required: true },
    flipped: { type: Boolean, default: false },
    expandedServicios: { type: Boolean, default: false },
    userContext: { type: Object, default: null },
    inscripcionesIds: { type: Array, default: () => [] },
    cardMobileTall: { type: Boolean, default: false },
    backgroundImage: { type: String, default: '' },
});

const emit = defineEmits(['toggle-flip', 'toggle-servicios', 'inscribir', 'mas-info', 'abrir-mapa']);
</script>

<template>
    <div
        class="flip-card-container border-round shadow-sm hover:shadow-md transition-shadow"
        :class="{ flipped: flipped, 'card-mobile-tall': cardMobileTall }"
    >
        <div class="flip-card-inner">
            <!-- FRONT -->
            <div
                class="flip-card-front flex flex-col h-full p-4 pb-8"
                :style="{
                    backgroundImage: `linear-gradient(135deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0.75) 100%), url(${backgroundImage})`,
                    backgroundSize: 'cover',
                    backgroundPosition: 'center',
                    backgroundRepeat: 'no-repeat',
                }"
            >
                <div class="flip-card-header w-full mb-3">
                    <h3 class="text-base md:text-lg font-semibold leading-tight flex items-start gap-2">
                        <i class="fa-solid fa-dharmachakra" aria-hidden="true"></i>
                        <span class="flex flex-col">
                            <span
                                v-if="actividad.tipo_actividad?.abreviacion"
                                class="text-xs md:text-sm text-indigo-700 font-semibold uppercase tracking-wide"
                            >
                                {{ actividad.tipo_actividad.abreviacion }}
                            </span>
                            <span class="text-sm md:text-lg font-semibold text-gray-800">
                                {{ actividad.nombre }}
                            </span>
                        </span>
                    </h3>
                </div>
                <div class="flex flex-col md:flex-row flex-1">
                    <div
                        class="w-full md:w-1/2 md:pr-3 mb-3 md:mb-0 flex items-center justify-center bg-transparent cursor-pointer rounded h-56 md:h-full"
                        @click="emit('toggle-flip', actividad.id)"
                    >
                        <img
                            class="w-full h-full object-contain rounded"
                            :src="actividad.imagen?.ruta ? `/storage/${actividad.imagen.ruta}` : '/storage/img/actividades/imagen-no-disponible.jpg'"
                            :alt="actividad.nombre"
                        />
                    </div>
                    <div class="w-full md:w-1/2 flex flex-col justify-between md:pl-2">
                        <div class="flex-1">
                            <p class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                <span class="sr-only">Fecha</span>
                                <span>{{ formatFechaLarga(actividad.fecha_inicio) }}</span>
                            </p>
                            <p v-if="actividad.fecha_inicio" class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                <span class="sr-only">Hora</span>
                                <span>
                                    {{ new Date(actividad.fecha_inicio).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) }} hs.
                                </span>
                            </p>
                            <p class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                <span class="sr-only">Lugar</span>
                                <span class="inline-flex items-center gap-2">
                                    <span>{{ direccionActividad(actividad) }}</span>
                                    <button
                                        v-if="direccionActividad(actividad)"
                                        type="button"
                                        class="inline-flex items-center justify-center p-0 text-sky-700 hover:text-sky-900 shrink-0"
                                        title="Ver en mapa"
                                        aria-label="Ver en mapa"
                                        @click.stop="emit('abrir-mapa', direccionActividad(actividad))"
                                    >
                                        <i class="pi pi-map"></i>
                                    </button>
                                </span>
                            </p>
                            <p v-if="actividad.modalidad?.nombre" class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                <i class="fa-solid fa-video" aria-hidden="true"></i>
                                <span class="sr-only">Modalidad</span>
                                <span>{{ actividad.modalidad.nombre }}</span>
                            </p>
                            <p
                                v-if="actividad.esquema_precio?.nombre === 'Actividad Gratuita'"
                                class="text-sm mb-1 flex items-center gap-2 font-bold text-green-700"
                            >
                                <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                                <span>Actividad Gratuita</span>
                            </p>
                            <template v-else>
                                <p
                                    class="text-xs md:text-sm mb-0.5 md:mb-1 leading-tight flex items-center gap-2"
                                    :class="{
                                        'font-bold': !userContext?.membresia || userContext?.membresia?.nombre === 'Sin membresía',
                                        'text-gray-800 line-through': userContext?.membresia && userContext?.membresia?.nombre !== 'Sin membresía',
                                    }"
                                >
                                    <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                                    <span class="sr-only">Valor</span>
                                    <span class="font-bold text-gray-700">
                                        ${{ formatPrice(precioSinMembresiaVigente(actividad)) }}
                                    </span>
                                </p>
                                <p
                                    v-if="descuentoVigente(actividad)"
                                    class="text-[11px] md:text-xs text-amber-700 mb-0.5 md:mb-1 leading-tight"
                                >
                                    Después de {{ formatoFechaLimite(actividad) }}:
                                    <strong>
                                        <span> ${{ formatPrice(precioSinMembresiaNormal(actividad)) }}</span>
                                    </strong>
                                </p>
                                <p v-if="userContext?.membresia && userContext.membresia?.nombre !== 'Sin membresía'" class="text-xs md:text-sm text-gray-600 mb-1 md:mb-2 leading-tight">
                                    <strong>Con {{ userContext.membresia?.nombre }}:</strong>
                                    <span class="font-bold text-green-700"> ${{ formatPrice(precioMembresiaUsuario(actividad, userContext)) }}</span>
                                </p>
                            </template>
                            <div
                                v-if="serviciosDisponibles(actividad)"
                                class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 text-left text-xs md:text-sm font-medium text-slate-700"
                                    @click.stop="emit('toggle-servicios', actividad.id)"
                                >
                                    <span>Más servicios...</span>
                                    <span class="inline-flex h-6 w-2 items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-semibold text-slate-700">
                                        {{ expandedServicios ? '-' : '+' }}
                                    </span>
                                </button>
                                <div
                                    v-if="expandedServicios"
                                    class="mt-2 space-y-1 border-t border-slate-200 pt-2"
                                >
                                    <p v-if="actividad.hospedajes && actividad.hospedajes.length > 0" class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2">
                                        <i class="pi pi-home text-indigo-600" aria-hidden="true"></i>
                                        <span>Ofrece Hospedaje</span>
                                    </p>
                                    <p v-if="actividad.comidas && actividad.comidas.length > 0" class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2">
                                        <i class="pi pi-shopping-bag text-amber-600" aria-hidden="true"></i>
                                        <span>Ofrece Comidas</span>
                                    </p>
                                    <p v-if="actividad.transportes && actividad.transportes.length > 0" class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2">
                                        <i class="pi pi-car text-sky-600" aria-hidden="true"></i>
                                        <span>Ofrece Transporte</span>
                                    </p>
                                    <p v-if="actividad.grabacion || actividad.grabacion_id" class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2">
                                        <i class="pi pi-headphones text-violet-600" aria-hidden="true"></i>
                                        <span>Ofrece Grabaciones</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flip-card-footer w-full mt-4 p-2 flex gap-2" style="background: transparent; border-radius: 6px;">
                    <button
                        class="more-info-button bg-gray-500 hover:bg-gray-600 text-white py-2 px-2 rounded text-xs flex-1 transition-colors text-center flex items-center justify-center gap-1 whitespace-nowrap"
                        @click="emit('mas-info', actividad)"
                    >
                        <i class="pi pi-plus"></i>
                        <span>Más info.</span>
                    </button>
                    <button
                        :disabled="actividadSinInscripcionDisponible(actividad, inscripcionesIds)"
                        class="py-2 px-3 rounded text-sm flex-1 transition-colors flex items-center justify-center gap-1"
                        :class="actividadSinInscripcionDisponible(actividad, inscripcionesIds) ? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                        @click="emit('inscribir', actividad)"
                    >
                        <i v-if="esInscrito(actividad.id, inscripcionesIds)" class="pi pi-heart-fill"></i>
                        {{ textoBotonInscripcion(actividad, inscripcionesIds) }}
                    </button>
                </div>
            </div>

            <!-- BACK -->
            <div class="flip-card-back p-4 md:p-6 flex flex-col h-full" @click="emit('toggle-flip', actividad.id)">
                <h3 class="text-lg md:text-xl font-semibold mb-3 md:mb-4 text-gray-800">Descripción</h3>
                <div class="flex-1 overflow-y-auto">
                    <div
                        class="prose prose-xs md:prose-sm max-w-none text-gray-700"
                        v-html="renderMarkdown(actividad.descripcion?.descripcion || 'No hay descripción disponible')"
                    ></div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <button
                        :disabled="actividadSinInscripcionDisponible(actividad, inscripcionesIds)"
                        class="py-1.5 px-4 rounded w-full transition-colors flex items-center justify-center gap-2"
                        :class="actividadSinInscripcionDisponible(actividad, inscripcionesIds) ? 'bg-gray-400 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                        @click.stop="emit('inscribir', actividad)"
                    >
                        <i v-if="esInscrito(actividad.id, inscripcionesIds)" class="pi pi-heart-fill"></i>
                        {{ textoBotonInscripcion(actividad, inscripcionesIds) }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.flip-card-container {
    perspective: 1000px;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    min-height: 620px;
    height: clamp(620px, 96vh, 760px);
}

@media (min-width: 768px) {
    .flip-card-container {
        min-height: 580px;
        height: clamp(580px, 70vh, 620px);
    }
}

@media (max-width: 767px) {
    .flip-card-container.card-mobile-tall {
        min-height: 700px;
        height: clamp(700px, 100vh, 840px);
    }
}

.flip-card-inner {
    width: 100%;
    height: 100%;
    transition: transform 0.7s;
    transform-style: preserve-3d;
    position: relative;
}

.flip-card-container.flipped .flip-card-inner {
    transform: rotateY(180deg);
}

.flip-card-front {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    background-color: #ffffff;
    color: #374151;
}

.flip-card-front h3,
.flip-card-front p,
.flip-card-front strong {
    color: #374151;
    border-radius: 6px;
    padding: 8px 12px;
}

.flip-card-front span:not(.button-span) {
    color: #374151;
}

.more-info-button,
.more-info-button span {
    color: white !important;
}

.flip-card-front,
.flip-card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    top: 0;
    left: 0;
    border-radius: 8px;
}

.flip-card-back {
    background-color: #f8fafc;
    transform: rotateY(180deg);
    cursor: pointer;
}
</style>
