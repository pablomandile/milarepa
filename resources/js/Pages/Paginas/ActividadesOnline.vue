<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    monthLabel: {
        type: String,
        required: true
    },
    headerImageUrl: {
        type: String,
        default: null
    },
    oraciones: {
        type: Array,
        default: () => []
    },
    clases: {
        type: Array,
        default: () => []
    },
    cursos: {
        type: Array,
        default: () => []
    }
});

const fallbackImage = '/storage/img/actividades/imagen-no-disponible.jpg';

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

const sections = computed(() => [
    { key: 'oraciones', title: 'Oraciones Online', items: props.oraciones },
    { key: 'clases', title: 'Clases Online', items: props.clases },
    { key: 'cursos', title: 'Cursos Online', items: props.cursos },
]);

const oracionesAgrupadas = computed(() => {
    const map = new Map();
    for (const item of props.oraciones || []) {
        const key = item.id;
        if (!map.has(key)) {
            map.set(key, {
                id: item.id,
                nombre: item.nombre,
                image_url: item.image_url || null,
                titulos_por_fecha: item.titulos_por_fecha || {},
                fechas: [],
            });
        }
        map.get(key).fechas.push({
            fecha: item.fecha,
            hora: item.hora,
            titulo_fecha: item.titulo_fecha || null,
            links: Array.isArray(item.links) ? item.links : [],
        });
    }

    return Array.from(map.values()).map((row) => ({
        ...row,
        fechas: row.fechas.sort((a, b) => {
            const aKey = `${a.fecha || ''} ${a.hora || ''}`;
            const bKey = `${b.fecha || ''} ${b.hora || ''}`;
            return aKey.localeCompare(bKey);
        }),
    }));
});

const clasesAgrupadas = computed(() => {
    const map = new Map();
    for (const item of props.clases || []) {
        const key = item.id;
        if (!map.has(key)) {
            map.set(key, {
                id: item.id,
                nombre: item.nombre,
                image_url: item.image_url || null,
                fechas: [],
            });
        }
        map.get(key).fechas.push({
            fecha: item.fecha,
            hora: item.hora,
            button_text: item.button_text || item.titulo_fecha || 'Abrir link',
            links: Array.isArray(item.links) ? item.links : [],
        });
    }

    return Array.from(map.values()).map((row) => ({
        ...row,
        fechas: row.fechas.sort((a, b) => {
            const aKey = `${a.fecha || ''} ${a.hora || ''}`;
            const bKey = `${b.fecha || ''} ${b.hora || ''}`;
            return aKey.localeCompare(bKey);
        }),
    }));
});

const visibleSections = computed(() =>
    sections.value.filter((section) => {
        if (section.key === 'oraciones') return oracionesAgrupadas.value.length > 0;
        if (section.key === 'clases') return clasesAgrupadas.value.length > 0;
        return (section.items || []).length > 0;
    })
);

const formatDate = (value) => {
    if (!value) return '-';
    const date = parseLocalDate(value);
    if (!date) return '-';
    return date.toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatCursoFecha = (item) => {
    const inicio = formatDate(item.fecha_inicio);
    const fin = item.fecha_fin ? formatDate(item.fecha_fin) : null;
    const hora = item.hora_inicio ? `${item.hora_inicio} hs.` : null;

    if (fin && fin !== inicio) {
        return hora ? `${inicio} ${hora} - ${fin}` : `${inicio} - ${fin}`;
    }

    return hora ? `${inicio} ${hora}` : inicio;
};

const formatCursoDay = (item) => {
    if (!item?.fecha_inicio) return '-';
    return formatMonthDay(item.fecha_inicio);
};

const formatCursoHour = (item) => {
    if (!item?.hora_inicio) return '-';
    return `${item.hora_inicio} hs.`;
};

const getMaestrosEnLineas = (maestros) => {
    if (!Array.isArray(maestros) || maestros.length === 0) return [];

    const nombres = maestros
        .map((nombre) => String(nombre || '').trim())
        .filter((nombre) => nombre.length > 0);

    if (nombres.length === 0) return [];

    const primeraLinea = nombres.slice(0, 2).join(', ');
    const segundaLinea = nombres.slice(2).join(', ');

    return [primeraLinea, segundaLinea].filter((linea) => linea.length > 0);
};

const formatItemDate = (sectionKey, item) => {
    if (sectionKey === 'cursos') return formatCursoFecha(item);
    const fecha = formatDate(item.fecha);
    return item.hora ? `${fecha} ${item.hora} hs.` : fecha;
};

const formatItemTime = (item) => {
    if (!item?.hora) return '-';
    return `${item.hora} hs.`;
};

const formatMonthDay = (value) => {
    if (!value) return '-';
    const date = parseLocalDate(value);
    if (!date) return '-';
    const raw = date.toLocaleDateString('es-AR', {
        weekday: 'long',
        day: 'numeric',
    });
    const [weekday, day] = raw.replace(',', '').split(' ');
    const weekdayCap = weekday ? weekday.charAt(0).toUpperCase() + weekday.slice(1) : '';
    return `${weekdayCap} ${day || ''}`.trim();
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

    return Array.from(map.entries()).map(([weekday, items]) => ({
        weekday,
        items,
    }));
};

const expandedClaseRows = ref({});

const getClaseRowKey = (claseId, fh) => `${claseId}|${fh?.fecha || ''}|${fh?.hora || ''}`;

const toggleClaseRow = (claseId, fh) => {
    const key = getClaseRowKey(claseId, fh);
    expandedClaseRows.value[key] = !expandedClaseRows.value[key];
};

const isClaseRowExpanded = (claseId, fh) => {
    const key = getClaseRowKey(claseId, fh);
    return !!expandedClaseRows.value[key];
};
</script>

<template>
    <AppLayout title="Actividades Online">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades Online</h1>
        </template>

        <div class="py-10">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <!-- <div class="px-6 py-5 border-b border-gray-200">
                        <h2 class="text-2xl font-semibold text-gray-800">{{ monthLabel }}</h2>
                    </div> -->

                    <div v-if="headerImageUrl" class="px-6 pt-5">
                        <img
                            :src="headerImageUrl"
                            alt="Encabezado Actividades Online"
                            class="w-full h-auto rounded-lg border border-gray-200 object-contain"
                        />
                    </div>

                    <div class="px-6 pb-6 pt-5 space-y-8">
                        <section v-for="section in visibleSections" :key="section.key">
                            <h3 class="text-2xl font-semibold text-indigo-700 mb-3">{{ section.title }}</h3>
                            <div v-if="section.key === 'oraciones'" class="space-y-3">
                                <article
                                    v-for="item in oracionesAgrupadas"
                                    :key="`oracion-${item.id}`"
                                    class="grid grid-cols-1 md:grid-cols-12 gap-4 rounded-lg border border-gray-200 p-3"
                                >
                                    <div class="order-1 md:order-none md:col-span-3">
                                        <img
                                            :src="item.image_url || fallbackImage"
                                            :alt="item.nombre"
                                            class="w-full h-auto rounded object-contain border border-gray-200"
                                        />
                                    </div>
                                    <div class="order-2 md:order-none md:col-span-9">
                                        <h4
                                            class="text-2xl font-semibold text-gray-800 mb-5 mt-2 leading-tight whitespace-pre-line break-words"
                                        >
                                            {{ item.nombre }}
                                        </h4>
                                        <div class="space-y-3">
                                            <div
                                                v-for="(weekdayGroup, gIdx) in groupFechasByWeekday(item.fechas)"
                                                :key="`oracion-${item.id}-weekday-${gIdx}`"
                                                class="overflow-hidden rounded-lg border border-slate-200"
                                            >
                                                <div class="bg-slate-100 px-3 py-2 text-left font-semibold text-slate-700">
                                                    {{ weekdayGroup.weekday }}
                                                </div>
                                                <table class="w-full text-lg">
                                                    <tbody>
                                                        <tr
                                                            v-for="(fh, idx) in weekdayGroup.items"
                                                            :key="`oracion-${item.id}-fh-${gIdx}-${idx}`"
                                                            class="border-t border-slate-100 odd:bg-white even:bg-slate-50"
                                                        >
                                                            <td class="px-3 py-2 text-slate-700">{{ formatMonthDay(fh.fecha) }}</td>
                                                            <td class="px-3 py-2 text-slate-600">{{ formatItemTime(fh) }}</td>
                                                            <td class="px-3 py-2 text-slate-600">
                                                                <div v-if="fh.links && fh.links.length > 0" class="flex flex-wrap items-center gap-2">
                                                                    <a
                                                                        v-for="link in fh.links"
                                                                        :key="`oracion-link-${item.id}-${fh.fecha}-${link.id}`"
                                                                        :href="link.url"
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                                                        :title="link.nombre || 'Abrir link'"
                                                                    >
                                                                        <i class="pi pi-link"></i>
                                                                    </a>
                                                                </div>
                                                                <span v-else>-</span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else-if="section.key === 'clases'" class="space-y-3">
                                <article
                                    v-for="item in clasesAgrupadas"
                                    :key="`clase-${item.id}`"
                                    class="grid grid-cols-1 md:grid-cols-12 gap-4 rounded-lg border border-gray-200 p-3"
                                >
                                    <div class="order-1 md:order-none md:col-span-3">
                                        <img
                                            :src="item.image_url || fallbackImage"
                                            :alt="item.nombre"
                                            class="w-full h-auto rounded object-contain border border-gray-200"
                                        />
                                    </div>
                                    <div class="order-2 md:order-none md:col-span-9">
                                        <h4
                                            class="text-2xl font-semibold text-gray-800 mb-8 mt-2 leading-tight whitespace-pre-line break-words"
                                        >
                                            {{ item.nombre }}
                                        </h4>
                                        <div class="space-y-3">
                                            <div
                                                v-for="(weekdayGroup, gIdx) in groupFechasByWeekday(item.fechas)"
                                                :key="`clase-${item.id}-weekday-${gIdx}`"
                                                class="overflow-hidden rounded-lg border border-slate-200"
                                            >
                                                <div class="bg-slate-100 px-3 py-2 text-left text-lg font-semibold text-slate-700">
                                                    {{ weekdayGroup.weekday }} de {{ monthLabel }}
                                                </div>
                                                <table class="w-full text-lg">
                                                    <tbody>
                                                        <template
                                                            v-for="(fh, idx) in weekdayGroup.items"
                                                            :key="`clase-${item.id}-fh-group-${gIdx}-${idx}`"
                                                        >
                                                            <tr class="border-t border-slate-100 odd:bg-white even:bg-slate-50">
                                                                <td class="px-3 py-4 text-slate-700">{{ formatMonthDay(fh.fecha) }}</td>
                                                                <td class="px-3 py-4 text-slate-600">{{ formatItemTime(fh) }}</td>
                                                                <td class="px-3 py-4 text-slate-600">
                                                                    <button
                                                                        type="button"
                                                                        class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-2 py-1 text-slate-700 hover:bg-slate-100"
                                                                        @click="toggleClaseRow(item.id, fh)"
                                                                    >
                                                                        <i :class="isClaseRowExpanded(item.id, fh) ? 'pi pi-minus' : 'pi pi-plus'"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            <tr v-if="isClaseRowExpanded(item.id, fh)" class="bg-slate-50 border-t border-slate-100">
                                                                <td colspan="3" class="px-3 py-3">
                                                                    <div v-if="fh.links && fh.links.length > 0" class="flex flex-wrap gap-2">
                                                                        <a
                                                                            v-for="link in fh.links"
                                                                            :key="`clase-link-btn-${item.id}-${fh.fecha}-${link.id}`"
                                                                            :href="link.url"
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                                                            :title="fh.button_text"
                                                                        >
                                                                            <i class="pi pi-link"></i>
                                                                            <span>{{ fh.button_text }}</span>
                                                                        </a>
                                                                    </div>
                                                                    <span v-else class="text-sm text-slate-500">Sin links para esta fecha.</span>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="space-y-3">
                                <article
                                    v-for="item in section.items"
                                    :key="`${section.key}-${item.id}-${item.fecha || item.fecha_inicio || ''}`"
                                    class="grid grid-cols-1 md:grid-cols-12 gap-4 rounded-lg border border-gray-200 p-3"
                                >
                                    <div class="order-1 md:order-none md:col-span-3">
                                        <img
                                            :src="item.image_url || fallbackImage"
                                            :alt="item.nombre"
                                            class="w-full h-auto rounded object-contain border border-gray-200"
                                        />
                                    </div>
                                    <div class="order-2 md:order-none md:col-span-9">
                                        <h4 class="text-2xl font-semibold text-gray-800 mb-5 mt-2 leading-tight whitespace-pre-line break-words">
                                            {{ item.nombre }}
                                        </h4>
                                        <div class="overflow-hidden rounded-lg border border-slate-200">
                                            <table class="w-full text-lg">
                                                <tbody>
                                                    <tr class="border-t border-slate-100 odd:bg-white even:bg-slate-50">
                                                        <td class="px-3 py-2 text-slate-700">
                                                            <div class="flex items-start gap-2">
                                                                <i class="pi pi-users mt-1 text-indigo-600"></i>
                                                                <div>
                                                                    <span class="font-semibold">Maestros:</span>
                                                                    <div class="ml-1 inline-block align-top">
                                                                        <template v-if="item.maestros && item.maestros.length">
                                                                            <span
                                                                                v-for="(linea, idx) in getMaestrosEnLineas(item.maestros)"
                                                                                :key="`curso-${item.id}-maestros-linea-${idx}`"
                                                                                class="block"
                                                                            >
                                                                                {{ linea }}
                                                                            </span>
                                                                        </template>
                                                                        <span v-else>-</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="border-t border-slate-100 odd:bg-white even:bg-slate-50">
                                                        <td class="px-3 py-2 text-slate-700">
                                                            <div class="flex items-center gap-2">
                                                                <i class="pi pi-calendar text-indigo-600"></i>
                                                                <span class="font-semibold">Día:</span>
                                                                <span>{{ formatCursoDay(item) }}</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="border-t border-slate-100 odd:bg-white even:bg-slate-50">
                                                        <td class="px-3 py-2 text-slate-700">
                                                            <div class="flex items-center gap-2">
                                                                <i class="pi pi-clock text-indigo-600"></i>
                                                                <span class="font-semibold">Hora:</span>
                                                                <span>{{ formatCursoHour(item) }}</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr class="border-t border-slate-100 odd:bg-white even:bg-slate-50">
                                                        <td class="px-3 py-2 text-slate-700">
                                                            <a
                                                                :href="route('grid-actividades.show-public', item.id)"
                                                                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                                            >
                                                                <i class="pi pi-info-circle"></i>
                                                                <span>+ Info e Inscripción</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
