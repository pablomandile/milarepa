<script>
export default {
    name: 'AreaEstudioIndex',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    programa: {
        type: Object,
        default: null,
    },
    grabaciones: {
        type: Array,
        default: () => [],
    },
});

const DESCRIPCION_LIMITE_PALABRAS = 120;
const descripcionExpandida = ref(false);

const descripcionPalabras = computed(() => {
    const texto = props.programa?.descripcion || '';
    return Array.from(texto.matchAll(/\S+/g));
});

const descripcionTruncada = computed(() => descripcionPalabras.value.length > DESCRIPCION_LIMITE_PALABRAS);

const descripcionVisible = computed(() => {
    const texto = props.programa?.descripcion || '';
    if (!descripcionTruncada.value || descripcionExpandida.value) {
        return texto;
    }
    const cortePalabra = descripcionPalabras.value[DESCRIPCION_LIMITE_PALABRAS - 1];
    const fin = cortePalabra.index + cortePalabra[0].length;
    return texto.slice(0, fin).trimEnd() + '…';
});

function formatBytes(bytes) {
    if (!bytes && bytes !== 0) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1073741824) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${(bytes / 1073741824).toFixed(2)} GB`;
}

function formatFecha(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function urlArchivo(ruta) {
    if (!ruta) return '#';
    return `/storage/${ruta}`;
}
</script>

<template>
    <Head title="Área de estudio" />

    <AppLayout title="Área de estudio">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Área de estudio
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Sin programa asignado -->
                <div
                    v-if="!programa"
                    class="bg-white dark:bg-gray-800 shadow rounded-lg p-8 text-center"
                >
                    <i class="pi pi-info-circle text-4xl text-indigo-400 mb-4"></i>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mb-2">
                        Aún no tenés un programa de estudio asignado
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Si creés que es un error, contactá a coordinación.
                    </p>
                </div>

                <template v-else>
                    <!-- Bloque 1: Programa actual -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                            <h1 class="text-white text-xl md:text-2xl font-semibold flex items-center gap-3 flex-wrap">
                                <i class="pi pi-book"></i>
                                <span>{{ programa.nombre }}</span>
                                <span
                                    v-if="programa.abreviacion"
                                    class="px-2 py-0.5 text-xs font-mono bg-white/20 rounded"
                                >
                                    {{ programa.abreviacion }}
                                </span>
                            </h1>
                        </div>
                        <div v-if="programa.descripcion" class="p-6">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                {{ descripcionVisible }}<button
                                    v-if="descripcionTruncada"
                                    type="button"
                                    @click="descripcionExpandida = !descripcionExpandida"
                                    class="ml-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium"
                                >
                                    {{ descripcionExpandida ? 'menos' : 'más' }}
                                </button>
                            </p>
                        </div>
                    </div>

                    <!-- Bloque 2: Compromisos -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                            <i class="pi pi-file"></i>
                            Compromisos
                        </h3>
                        <div v-if="programa.compromisos_pdf">
                            <a
                                :href="urlArchivo(programa.compromisos_pdf)"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded"
                            >
                                <i class="fas fa-file-pdf"></i>
                                Descargar compromisos (PDF)
                            </a>
                        </div>
                        <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">
                            Sin compromisos cargados todavía.
                        </p>
                    </div>

                    <!-- Bloque 3: Grabaciones -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                            <i class="pi pi-volume-up"></i>
                            Grabaciones de clases
                            <span class="ml-auto text-xs font-normal text-gray-500 dark:text-gray-400">
                                {{ grabaciones.length }} grabación(es)
                            </span>
                        </h3>

                        <p
                            v-if="!grabaciones.length"
                            class="text-sm text-gray-500 dark:text-gray-400 italic"
                        >
                            Sin grabaciones cargadas todavía.
                        </p>

                        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li
                                v-for="g in grabaciones"
                                :key="g.id"
                                class="py-3 flex flex-col md:flex-row md:items-center gap-3"
                            >
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                        {{ g.nombre }}
                                    </p>
                                    <p
                                        v-if="g.descripcion"
                                        class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 whitespace-pre-wrap"
                                    >
                                        {{ g.descripcion }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                        {{ formatBytes(g.size_bytes) }} · {{ formatFecha(g.created_at) }}
                                    </p>
                                </div>
                                <audio
                                    :src="urlArchivo(g.archivo)"
                                    controls
                                    preload="none"
                                    class="w-full md:w-72"
                                ></audio>
                                <a
                                    :href="urlArchivo(g.archivo)"
                                    target="_blank"
                                    rel="noopener"
                                    v-tooltip="'Descargar / abrir en nueva pestaña'"
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300"
                                >
                                    <i class="fas fa-download"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
