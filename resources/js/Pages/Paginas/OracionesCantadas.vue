<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    monthLabel: {
        type: String,
        default: '',
    },
    oraciones: {
        type: Array,
        default: () => [],
    },
});

const fallbackImage = '/storage/img/actividades/imagen-no-disponible.jpg';
const weekdayOrder = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

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
    <AppLayout title="Oraciones cantadas">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Oraciones cantadas</h1>
        </template>

        <div class="py-10">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                    <!-- Título del mes -->
                    <div class="px-3 sm:px-6 pt-5">
                        <div class="mb-4 text-center">
                            <p class="text-2xl font-semibold text-indigo-800">Oraciones cantadas de {{ monthLabel }}</p>
                        </div>
                    </div>

                    <!-- Listado de oraciones cantadas -->
                    <div class="px-0 sm:px-6 pb-6 pt-5 space-y-8">
                        <article
                            v-for="oracion in oraciones"
                            :key="`oracion-${oracion.id}`"
                            class="activity-card mt-8 mb-10 first:mt-0 last:mb-0 rounded-lg border border-gray-200 dark:border-gray-700 p-3"
                        >
                            <div class="activity-image-col">
                                <img
                                    :src="oracion.image_url || fallbackImage"
                                    :alt="oracion.nombre"
                                    class="activity-card-image h-auto rounded object-contain border border-gray-200 dark:border-gray-700"
                                />
                            </div>
                            <div class="activity-right-col">
                                <h4 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 mb-4 mt-2 leading-tight whitespace-pre-line break-words">
                                    {{ oracion.nombre }}
                                </h4>

                                <!-- Info de la oración: día/días y horario -->
                                <div class="mb-6 space-y-2 text-lg text-slate-700 dark:text-gray-200">
                                    <div class="flex items-start gap-3">
                                        <i class="pi pi-calendar mt-1.5 text-slate-400"></i>
                                        <span><span class="font-semibold">Día:</span> {{ oracion.dia_label || '-' }}</span>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <i class="pi pi-clock mt-1.5 text-slate-400"></i>
                                        <span><span class="font-semibold">Horario:</span> {{ oracion.hora_label || '-' }}</span>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <Link
                                        :href="route('oracionescantadas.show-public', { oracionCantada: oracion.id, return_url: route('paginas.oraciones-cantadas') })"
                                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                    >
                                        <i class="pi pi-info-circle"></i>
                                        <span>+ INFO</span>
                                    </Link>
                                </div>

                                <div v-if="oracion.fechas && oracion.fechas.length" class="space-y-3">
                                    <div
                                        v-for="(weekdayGroup, gIdx) in groupFechasByWeekday(oracion.fechas)"
                                        :key="`oracion-${oracion.id}-weekday-${gIdx}`"
                                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-gray-600"
                                    >
                                        <div class="bg-slate-100 dark:bg-gray-600 px-3 py-2 text-left text-lg font-semibold text-slate-700 dark:text-gray-100">
                                            {{ weekdayGroup.weekday }}
                                        </div>
                                        <table class="w-full text-lg">
                                            <tbody>
                                                <tr
                                                    v-for="(fh, idx) in weekdayGroup.items"
                                                    :key="`oracion-${oracion.id}-fh-${gIdx}-${idx}`"
                                                    class="border-t border-slate-100 dark:border-gray-700 odd:bg-white dark:odd:bg-gray-700 even:bg-slate-50 dark:even:bg-gray-800"
                                                >
                                                    <td class="px-3 py-3 text-slate-700 dark:text-gray-200 whitespace-nowrap align-top">{{ formatMonthDay(fh.fecha) }}</td>
                                                    <td class="px-3 py-3 text-slate-600 dark:text-gray-300 whitespace-nowrap align-top">{{ formatItemTime(fh) }}</td>
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

                        <div v-if="oraciones.length === 0" class="py-12 text-center text-slate-500 dark:text-gray-400">
                            No hay oraciones cantadas para mostrar.
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
