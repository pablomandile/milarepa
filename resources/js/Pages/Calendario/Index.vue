<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  calendar: {
    type: Object,
    required: true,
  },
  actividades: {
    type: Array,
    default: () => [],
  },
  oracionesCantadas: {
    type: Array,
    default: () => [],
  },
  clases: {
    type: Array,
    default: () => [],
  },
  clasesSedes: {
    type: Array,
    default: () => [],
  },
});

const diasSemana = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
const diasSemanaLargos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
const showActividades = ref(true);
const showClases = ref(true);
const showOraciones = ref(true);
const clasesSedeFilters = ref({});
const showClasesSedesFilters = ref(false);

function parseYmd(value) {
  const [y, m, d] = String(value).split('-').map(Number);
  return new Date(y, (m || 1) - 1, d || 1);
}

function formatYmd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function addDays(date, days) {
  const copy = new Date(date);
  copy.setDate(copy.getDate() + days);
  return copy;
}

const actividadesPorDia = computed(() => {
  const map = {};
  const monthStart = parseYmd(props.calendar.firstDayOfMonth);
  const monthEnd = addDays(monthStart, props.calendar.daysInMonth - 1);

  for (const actividad of props.actividades) {
    const inicio = parseYmd(actividad.fecha_inicio);
    const fin = parseYmd(actividad.fecha_fin || actividad.fecha_inicio);

    const desde = inicio > monthStart ? inicio : monthStart;
    const hasta = fin < monthEnd ? fin : monthEnd;

    if (hasta < desde) continue;

    for (let cursor = new Date(desde); cursor <= hasta; cursor = addDays(cursor, 1)) {
      const key = formatYmd(cursor);
      if (!map[key]) map[key] = [];
      map[key].push(actividad);
    }
  }

  for (const oracion of props.oracionesCantadas) {
    if (!oracion?.fecha) continue;
    if (!map[oracion.fecha]) map[oracion.fecha] = [];
    map[oracion.fecha].push({
      ...oracion,
      esOracionCantada: true,
    });
  }

  for (const clase of props.clases) {
    if (!clase?.fecha) continue;
    if (!map[clase.fecha]) map[clase.fecha] = [];
    map[clase.fecha].push({
      ...clase,
      esClase: true,
    });
  }

  Object.keys(map).forEach((key) => {
    map[key].sort((a, b) => {
      const horaA = a?.hora || a?.hora_inicio || '99:99';
      const horaB = b?.hora || b?.hora_inicio || '99:99';
      if (horaA !== horaB) return horaA.localeCompare(horaB);
      return String(a?.nombre || '').localeCompare(String(b?.nombre || ''));
    });
  });

  return map;
});

watch(
  () => props.clasesSedes,
  (sedes) => {
    const next = {};
    for (const sede of sedes || []) {
      next[sede.id] = clasesSedeFilters.value[sede.id] ?? true;
    }
    clasesSedeFilters.value = next;
  },
  { immediate: true }
);

function claseTitle(item) {
  return [itemLabel(item), item?.sede_nombre, item?.sede_direccion].filter(Boolean).join(' — ');
}

const calendarCells = computed(() => {
  const cells = [];
  const leadingBlanks = Math.max(0, (props.calendar.startWeekday || 1) - 1);

  for (let i = 0; i < leadingBlanks; i += 1) {
    cells.push({ empty: true, key: `blank-start-${i}` });
  }

  for (let day = 1; day <= props.calendar.daysInMonth; day += 1) {
    const date = `${props.calendar.month}-${String(day).padStart(2, '0')}`;
    cells.push({
      empty: false,
      key: date,
      day,
      date,
      isToday: date === props.calendar.today,
      actividades: (actividadesPorDia.value[date] || []).filter(isVisibleItem),
    });
  }

  while (cells.length % 7 !== 0) {
    cells.push({ empty: true, key: `blank-end-${cells.length}` });
  }

  return cells;
});

function actividadHref(actividadId) {
  const returnUrl = `${route('calendario.index')}?month=${props.calendar.month}`;
  return `${route('grid-actividades.show-public', actividadId)}?return_url=${encodeURIComponent(returnUrl)}`;
}

function oracionCantadaHref(oracionId) {
  const returnUrl = `${route('calendario.index')}?month=${props.calendar.month}`;
  return `${route('oracionescantadas.show-public', oracionId)}?month=${props.calendar.month}&return_url=${encodeURIComponent(returnUrl)}`;
}

function claseHref(claseId) {
  const returnUrl = `${route('calendario.index')}?month=${props.calendar.month}`;
  return `${route('clases.show-public', claseId)}?return_url=${encodeURIComponent(returnUrl)}`;
}

function itemLabel(item) {
  if (item?.esOracionCantada && item?.mensaje) {
    return `${item.nombre} — ${item.mensaje}`;
  }
  const hora = item?.hora || item?.hora_inicio;
  return hora ? `${hora} hs. ${item.nombre}` : item.nombre;
}

function isVisibleItem(item) {
  if (item.esClase) {
    if (!showClases.value) return false;
    return clasesSedeFilters.value[item?.sede_key] ?? true;
  }
  if (item.esOracionCantada) return showOraciones.value;
  return showActividades.value;
}

// ============= Vista semanal (móvil) =============
const weeks = computed(() => {
  const result = [];
  const cells = calendarCells.value;
  for (let i = 0; i < cells.length; i += 7) {
    result.push(cells.slice(i, i + 7));
  }
  return result;
});

const initialWeekIndex = computed(() => {
  const todayKey = props.calendar.today;
  for (let i = 0; i < weeks.value.length; i += 1) {
    if (weeks.value[i].some((c) => !c.empty && c.date === todayKey)) return i;
  }
  return 0;
});

const selectedWeekIndex = ref(0);

watch(initialWeekIndex, (v) => {
  selectedWeekIndex.value = v;
}, { immediate: true });

const selectedWeek = computed(() => weeks.value[selectedWeekIndex.value] || []);

const canPrevWeek = computed(() => selectedWeekIndex.value > 0);
const canNextWeek = computed(() => selectedWeekIndex.value < weeks.value.length - 1);

function prevWeek() {
  if (canPrevWeek.value) selectedWeekIndex.value -= 1;
}

function nextWeek() {
  if (canNextWeek.value) selectedWeekIndex.value += 1;
}

const selectedWeekLabel = computed(() => {
  const week = selectedWeek.value;
  const firstDay = week.find((c) => !c.empty);
  const lastDay = [...week].reverse().find((c) => !c.empty);
  if (!firstDay || !lastDay) return '';
  if (firstDay.day === lastDay.day) return `${firstDay.day}`;
  return `${firstDay.day} – ${lastDay.day}`;
});

function diaSemanaLargoFromDate(dateStr) {
  const date = parseYmd(dateStr);
  // getDay(): 0=Sun, 1=Mon, ..., 6=Sat. Convert to 0=Mon, ..., 6=Sun
  const jsDay = date.getDay();
  const idx = jsDay === 0 ? 6 : jsDay - 1;
  return diasSemanaLargos[idx];
}
</script>

<template>
  <AppLayout title="Calendario de Actividades">
    <Head title="Calendario" />

    <div class="min-h-screen bg-slate-50 dark:bg-gray-900 py-6">
      <div class="mx-auto w-full max-w-7xl px-3 sm:px-6">
        <div class="mb-4 rounded-2xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Calendario</h1>
              <p class="text-sm text-slate-600 dark:text-slate-400">Actividades activas del mes</p>
              <div class="mt-4 flex flex-wrap items-center gap-6 text-sm">
                <label class="inline-flex items-center gap-2 px-1 py-1 text-slate-700 dark:text-slate-300">
                  <input
                    v-model="showActividades"
                    type="checkbox"
                    class="calendar-filter-checkbox checkbox-sky-strong"
                  />
                  Cursos y retiros
                </label>
                <label class="inline-flex items-center gap-2 px-1 py-1 text-slate-700 dark:text-slate-300">
                  <input
                    v-model="showClases"
                    type="checkbox"
                    class="calendar-filter-checkbox checkbox-amber"
                  />
                  Clases
                </label>
                <label class="inline-flex items-center gap-2 px-1 py-1 text-slate-700 dark:text-slate-300">
                  <input
                    v-model="showOraciones"
                    type="checkbox"
                    class="calendar-filter-checkbox checkbox-emerald"
                  />
                  Oraciones Cantadas
                </label>
              </div>
              <div v-if="clasesSedes.length" class="mt-3">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
                  @click="showClasesSedesFilters = !showClasesSedesFilters"
                >
                  <span>Filtrar Clases por lugar</span>
                  <i :class="showClasesSedesFilters ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
                </button>
                <div v-if="showClasesSedesFilters" class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                  <label
                    v-for="sede in clasesSedes"
                    :key="sede.id"
                    class="inline-flex items-center gap-2 px-1 py-1 text-slate-700 dark:text-slate-300"
                    :title="sede.direccion || ''"
                  >
                    <input
                      v-model="clasesSedeFilters[sede.id]"
                      type="checkbox"
                      class="calendar-filter-checkbox checkbox-amber"
                    />
                    <span>
                      {{ sede.nombre }}
                      <span v-if="sede.direccion" class="text-xs text-slate-500 dark:text-slate-400">({{ sede.direccion }})</span>
                    </span>
                  </label>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Link
                :href="`${route('calendario.index')}?month=${calendar.prevMonth}`"
                class="rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-600"
              >
                Anterior
              </Link>
              <div class="min-w-44 rounded-lg bg-slate-900 dark:bg-gray-700 px-4 py-2 text-center text-sm font-medium text-white">
                {{ calendar.monthLabel }}
              </div>
              <Link
                :href="`${route('calendario.index')}?month=${calendar.nextMonth}`"
                class="rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-600"
              >
                Siguiente
              </Link>
            </div>
          </div>
        </div>

        <!-- Vista semanal móvil -->
        <div class="sm:hidden">
          <div class="mb-3 flex items-center justify-between gap-2">
            <button
              type="button"
              @click="prevWeek"
              :disabled="!canPrevWeek"
              class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="pi pi-chevron-left text-xs"></i>
              Anterior
            </button>
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
              Semana {{ selectedWeekLabel }}
            </span>
            <button
              type="button"
              @click="nextWeek"
              :disabled="!canNextWeek"
              class="inline-flex items-center gap-1 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
              <i class="pi pi-chevron-right text-xs"></i>
            </button>
          </div>

          <div class="space-y-3">
            <template v-for="cell in selectedWeek" :key="cell.key">
              <div
                v-if="!cell.empty"
                class="rounded-xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden"
              >
                <div
                  class="flex items-center gap-3 px-4 py-3 border-b border-slate-200 dark:border-gray-700"
                  :class="cell.isToday ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-slate-50 dark:bg-gray-700/40'"
                >
                  <span
                    class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold flex-shrink-0"
                    :class="cell.isToday ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-gray-600'"
                  >
                    {{ cell.day }}
                  </span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ diaSemanaLargoFromDate(cell.date) }}</p>
                    <p v-if="cell.actividades.length" class="text-xs text-slate-500 dark:text-slate-400">{{ cell.actividades.length }} {{ cell.actividades.length === 1 ? 'evento' : 'eventos' }}</p>
                  </div>
                </div>
                <div class="p-3">
                  <p v-if="cell.actividades.length === 0" class="text-sm text-slate-400 dark:text-slate-500 text-center py-2">Sin actividades</p>
                  <div v-else class="space-y-1.5">
                    <template v-for="actividad in cell.actividades" :key="`${cell.date}-${actividad.tipo || 'actividad'}-${actividad.id}`">
                      <div
                        v-if="actividad.esClase"
                        class="block rounded-md border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 px-3 py-2 text-sm leading-tight text-amber-900 dark:text-amber-200"
                      >
                        <Link :href="claseHref(actividad.id)" class="hover:underline">
                          {{ itemLabel(actividad) }}
                        </Link>
                        <p v-if="actividad.sede_direccion" class="mt-1 flex items-start gap-1 text-xs text-amber-800/80 dark:text-amber-300/80">
                          <i class="pi pi-map-marker text-[10px] mt-0.5"></i>
                          <span>{{ actividad.sede_direccion }}</span>
                        </p>
                      </div>
                      <Link
                        v-else-if="!actividad.esOracionCantada"
                        :href="actividadHref(actividad.id)"
                        class="block rounded-md border border-sky-400 dark:border-sky-500 bg-sky-200 dark:bg-sky-800/60 px-3 py-2 text-sm leading-tight text-sky-950 dark:text-sky-100 hover:bg-sky-300 dark:hover:bg-sky-800"
                      >
                        {{ itemLabel(actividad) }}
                      </Link>
                      <Link
                        v-else
                        :href="oracionCantadaHref(actividad.id)"
                        class="block rounded-md border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-2 text-sm leading-tight text-emerald-900 dark:text-emerald-200"
                      >
                        {{ itemLabel(actividad) }}
                      </Link>
                    </template>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Grilla mensual desktop -->
        <div class="hidden sm:block overflow-x-auto rounded-2xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
          <div class="calendar-grid min-w-[980px] border-b border-slate-200 dark:border-gray-700 bg-slate-100 dark:bg-gray-700">
            <div
              v-for="diaSemana in diasSemana"
              :key="diaSemana"
              class="border-r border-slate-200 dark:border-gray-600 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300 last:border-r-0"
            >
              {{ diaSemana }}
            </div>
          </div>

          <div class="calendar-grid min-w-[980px]">
            <div
              v-for="cell in calendarCells"
              :key="cell.key"
              class="calendar-cell overflow-hidden border-r border-b border-slate-200 dark:border-gray-700 p-2"
              :class="cell.empty ? 'bg-slate-50 dark:bg-gray-900' : 'bg-white dark:bg-gray-800'"
            >
              <template v-if="!cell.empty">
                <div class="flex h-full flex-col">
                  <div class="mb-2 flex items-center justify-between">
                    <span
                      class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                      :class="cell.isToday ? 'bg-emerald-600 text-white' : 'text-slate-800 dark:text-slate-200'"
                    >
                      {{ cell.day }}
                    </span>
                    <span v-if="cell.actividades.length" class="text-xs text-slate-500 dark:text-slate-400">
                      {{ cell.actividades.length }}
                    </span>
                  </div>

                  <div class="min-h-0 flex-1 space-y-1 overflow-y-auto pr-1">
                    <template v-for="actividad in cell.actividades" :key="`${cell.date}-${actividad.tipo || 'actividad'}-${actividad.id}`">
                      <div
                        v-if="actividad.esClase"
                        class="block rounded-md border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 text-xs leading-tight text-amber-900 dark:text-amber-200"
                        :title="claseTitle(actividad)"
                      >
                        <Link
                          :href="claseHref(actividad.id)"
                          class="hover:underline"
                        >
                          {{ itemLabel(actividad) }}
                        </Link>
                      </div>
                      <Link
                        v-else-if="!actividad.esOracionCantada"
                        :href="actividadHref(actividad.id)"
                        class="block rounded-md border border-sky-400 dark:border-sky-500 bg-sky-200 dark:bg-sky-800/60 px-2 py-1 text-xs leading-tight text-sky-950 dark:text-sky-100 hover:bg-sky-300 dark:hover:bg-sky-800"
                        :title="itemLabel(actividad)"
                      >
                        {{ itemLabel(actividad) }}
                      </Link>
                      <Link
                        v-else
                        :href="oracionCantadaHref(actividad.id)"
                        class="block rounded-md border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 text-xs leading-tight text-emerald-900 dark:text-emerald-200"
                        :title="itemLabel(actividad)"
                      >
                        {{ itemLabel(actividad) }}
                      </Link>
                    </template>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.calendar-cell {
  aspect-ratio: 1 / 1;
}

.calendar-filter-checkbox {
  -webkit-appearance: checkbox !important;
  appearance: auto !important;
  width: 16px !important;
  height: 16px !important;
  min-width: 16px !important;
  min-height: 16px !important;
  max-width: 16px !important;
  max-height: 16px !important;
  flex: 0 0 16px !important;
  display: inline-block !important;
  margin: 0 !important;
  padding: 0 !important;
  background-image: none !important;
}

.checkbox-amber {
  accent-color: #d97706;
}

.checkbox-emerald {
  accent-color: #059669;
}

.checkbox-sky-strong {
  accent-color: #075985;
}
</style>
