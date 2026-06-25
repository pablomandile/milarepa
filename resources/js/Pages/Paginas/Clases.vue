<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    headerImageUrl: {
        type: String,
        default: null,
    },
    monthLabel: {
        type: String,
        default: '',
    },
    cycleName: {
        type: String,
        default: null,
    },
    entidades: {
        type: Array,
        default: () => [],
    },
    clases: {
        type: Array,
        default: () => [],
    },
});

const fallbackImage = '/storage/img/actividades/imagen-no-disponible.jpg';
const weekdayOrder = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

const diaCanonOrder = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
const diaLabels = {
    lunes: 'Lunes',
    martes: 'Martes',
    miercoles: 'Miércoles',
    jueves: 'Jueves',
    viernes: 'Viernes',
    sabado: 'Sábado',
    domingo: 'Domingo',
};

const diasLabel = (dias) => {
    const arr = Array.isArray(dias) ? dias.map((d) => String(d).toLowerCase()) : [];
    const ordered = diaCanonOrder.filter((d) => arr.includes(d));
    if (ordered.length === 0) return '-';
    if (ordered.length === 7) return 'Todos los días';

    // Si los días son consecutivos (3 o más), se muestra como rango "De X a Y".
    const indices = ordered.map((d) => diaCanonOrder.indexOf(d));
    const consecutivos = indices.every((v, i) => i === 0 || v === indices[i - 1] + 1);
    if (consecutivos && ordered.length >= 3) {
        return `De ${diaLabels[ordered[0]]} a ${diaLabels[ordered[ordered.length - 1]]}`;
    }

    return ordered.map((d) => diaLabels[d]).join(', ');
};

const maestrosLabel = (maestros) => {
    const arr = Array.isArray(maestros) ? maestros.filter(Boolean) : [];
    return arr.length ? arr.join(', ') : '-';
};

const parseLocalDate = (value) => {
    if (!value) return null;
    const str = String(value);
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        const [y, m, d] = str.split('-').map(Number);
        return new Date(y, m - 1, d);
    }
    const parsed = new Date(str);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

// ----- Filtro por entidad -----
const selectedEntidadId = ref(null);

const clasesFiltradas = computed(() => {
    if (selectedEntidadId.value === null) return props.clases;
    return props.clases.filter((clase) => clase.entidad_id === selectedEntidadId.value);
});

const seleccionarEntidad = (id) => {
    selectedEntidadId.value = id;
};

// ----- Formatos de fecha/hora -----
const formatMonthDay = (value) => {
    if (!value) return '-';
    const date = parseLocalDate(value);
    if (!date) return '-';
    const raw = date.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric' });
    const [weekday, day] = raw.replace(',', '').split(' ');
    const weekdayCap = weekday ? weekday.charAt(0).toUpperCase() + weekday.slice(1) : '';
    return `${weekdayCap} ${day || ''}`.trim();
};

const formatItemTime = (item) => {
    if (!item?.hora) return '-';
    return `${item.hora} hs.`;
};

const formatMesRef = (ym) => {
    if (!/^\d{4}-\d{2}$/.test(String(ym || ''))) return '';
    const [y, m] = ym.split('-').map(Number);
    const raw = new Date(y, m - 1, 1).toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
    return raw.charAt(0).toUpperCase() + raw.slice(1);
};

const getWeekdayLabel = (value) => {
    if (!value) return '-';
    const date = parseLocalDate(value);
    if (!date) return '-';
    const raw = date.toLocaleDateString('es-AR', { weekday: 'long' });
    return raw.charAt(0).toUpperCase() + raw.slice(1);
};

const groupFechasByWeekday = (fechas) => {
    const map = new Map();

    for (const fh of fechas || []) {
        const label = getWeekdayLabel(fh.fecha);
        if (!map.has(label)) {
            map.set(label, []);
        }
        map.get(label).push(fh);
    }

    return Array.from(map.entries())
        .sort(([a], [b]) => weekdayOrder.indexOf(a) - weekdayOrder.indexOf(b))
        .map(([weekday, items]) => ({ weekday, items }));
};
</script>

<template>
    <AppLayout title="Clases">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Clases</h1>
        </template>

        <div class="py-10">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                    <div v-if="headerImageUrl" class="px-0 sm:px-6 pt-5 mb-5">
                        <img
                            :src="headerImageUrl"
                            alt="Encabezado Clases"
                            class="w-full h-auto rounded-lg border border-gray-200 dark:border-gray-700 object-contain"
                        />
                    </div>

                    <!-- Filtros por entidad -->
                    <div v-if="entidades.length > 0" class="px-3 sm:px-6 pt-5">
                        <div class="mb-6 text-center">
                            <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 bg-clip-text text-transparent pb-1">
                                Clases de {{ monthLabel }}
                            </h2>
                            <div v-if="cycleName" class="mt-3 flex justify-center">
                                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-900/40 px-5 py-2 text-lg sm:text-xl font-medium text-indigo-700 dark:text-indigo-200 ring-1 ring-inset ring-indigo-200 dark:ring-indigo-700 shadow-sm">
                                    <span class="text-xl">📚</span>
                                    <span>Ciclo: <span class="font-semibold">“{{ cycleName }}”</span></span>
                                </span>
                            </div>
                            <p class="mt-4 text-sm sm:text-base text-slate-500 dark:text-gray-400">Elegí un lugar para ver sus clases</p>
                        </div>
                        <div class="flex flex-wrap justify-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition"
                                :class="selectedEntidadId === null
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600'"
                                @click="seleccionarEntidad(null)"
                            >
                                Todas
                            </button>
                            <button
                                v-for="entidad in entidades"
                                :key="`entidad-btn-${entidad.id}`"
                                type="button"
                                class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition"
                                :class="selectedEntidadId === entidad.id
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600'"
                                @click="seleccionarEntidad(entidad.id)"
                            >
                                {{ entidad.nombre }}
                            </button>
                        </div>
                    </div>

                    <!-- Listado de clases -->
                    <div class="px-0 sm:px-6 pb-6 pt-5 space-y-8">
                        <article
                            v-for="clase in clasesFiltradas"
                            :key="`clase-${clase.id}`"
                            class="activity-card mt-8 mb-10 first:mt-0 last:mb-0 rounded-lg border border-gray-200 dark:border-gray-700 p-3"
                        >
                            <div class="activity-image-col">
                                <img
                                    :src="clase.image_url || fallbackImage"
                                    :alt="clase.nombre"
                                    class="activity-card-image h-auto rounded object-contain border border-gray-200 dark:border-gray-700"
                                />
                            </div>
                            <div class="activity-right-col">
                                <h4 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mb-2 mt-2 leading-tight whitespace-pre-line break-words">
                                    {{ clase.nombre }}
                                </h4>
                                <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 dark:bg-indigo-900/40 px-3 py-1 font-medium text-indigo-700 dark:text-indigo-200">
                                        <i class="pi pi-building"></i>
                                        {{ clase.entidad || 'Sin entidad' }}
                                    </span>
                                    <span v-if="formatMesRef(clase.mes_referencia)" class="inline-flex items-center gap-1 text-slate-500 dark:text-gray-400">
                                        <i class="pi pi-calendar"></i>
                                        {{ formatMesRef(clase.mes_referencia) }}
                                    </span>
                                </div>

                                <!-- Info de la clase: días, maestrxs, horario y precio -->
                                <div class="mb-6 space-y-2 text-lg text-slate-700 dark:text-gray-200">
                                    <div class="flex items-start gap-3">
                                        <i class="pi pi-calendar mt-1.5 text-slate-400"></i>
                                        <span><span class="font-semibold">Día:</span> {{ diasLabel(clase.dias_semana) }}</span>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="pi pi-user mt-1.5 text-slate-400"></i>
                                        <span><span class="font-semibold">Maestr@:</span> {{ maestrosLabel(clase.maestros) }}</span>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="pi pi-clock mt-1.5 text-slate-400"></i>
                                        <span><span class="font-semibold">Horario:</span> {{ clase.horario_label || '-' }}</span>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="pi pi-credit-card mt-1.5 text-slate-400"></i>
                                        <span v-if="clase.precio && clase.precio.es_gratis" class="font-bold text-emerald-600 dark:text-emerald-400">GRATIS</span>
                                        <span v-else-if="clase.precio && clase.precio.label" class="font-semibold">{{ clase.precio.label }} (Gratis con Tarjetas Kadampa)</span>
                                        <span v-else>-</span>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <Link
                                        :href="route('clases.show-public', { clase: clase.id, return_url: route('paginas.clases') })"
                                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                    >
                                        <i class="pi pi-info-circle"></i>
                                        <span>+ INFO</span>
                                    </Link>
                                </div>

                                <div v-if="clase.fechas && clase.fechas.length" class="space-y-3">
                                    <div
                                        v-for="(weekdayGroup, gIdx) in groupFechasByWeekday(clase.fechas)"
                                        :key="`clase-${clase.id}-weekday-${gIdx}`"
                                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-gray-600"
                                    >
                                        <div class="bg-slate-100 dark:bg-gray-600 px-3 py-2 text-left text-lg font-semibold text-slate-700 dark:text-gray-100">
                                            {{ weekdayGroup.weekday }}
                                        </div>
                                        <table class="w-full text-lg">
                                            <tbody>
                                                <tr
                                                    v-for="(fh, idx) in weekdayGroup.items"
                                                    :key="`clase-${clase.id}-fh-${gIdx}-${idx}`"
                                                    class="border-t border-slate-100 dark:border-gray-700 odd:bg-white dark:odd:bg-gray-700 even:bg-slate-50 dark:even:bg-gray-800"
                                                >
                                                    <td class="px-3 py-3 text-slate-700 dark:text-gray-200 whitespace-nowrap align-top">{{ formatMonthDay(fh.fecha) }}</td>
                                                    <td class="px-3 py-3 text-slate-600 dark:text-gray-300 whitespace-nowrap align-top">{{ formatItemTime(fh) }}</td>
                                                    <td class="px-3 py-3 text-slate-700 dark:text-gray-200 align-top">{{ fh.titulo_fecha || '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <p v-else class="text-base text-slate-500 dark:text-gray-400">
                                    Sin fechas cargadas para este mes.
                                </p>
                            </div>
                        </article>

                        <div v-if="clasesFiltradas.length === 0" class="py-12 text-center text-slate-500 dark:text-gray-400">
                            No hay clases para esta entidad.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.activity-card {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

.activity-image-col {
    width: 100%;
}

.activity-right-col {
    width: 100%;
    min-width: 0;
}

.activity-card-image {
    width: 100%;
}

@media (min-width: 768px) {
    .activity-card {
        display: flex;
        align-items: flex-start;
    }

    .activity-image-col {
        flex: 0 0 auto;
        width: auto;
        min-width: 0;
    }

    .activity-right-col {
        flex: 1 1 auto;
        min-width: 0;
    }

    .activity-card-image {
        width: 100%;
    }
}

@media (min-width: 768px) and (max-width: 1366px) {
    .activity-image-col {
        width: 50%;
    }

    .activity-card-image {
        width: 100%;
    }
}
</style>
