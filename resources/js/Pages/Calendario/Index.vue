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
  clasesEntidades: {
    type: Array,
    default: () => [],
  },
});

const diasSemana = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
const showActividades = ref(true);
const showClases = ref(true);
const showOraciones = ref(true);
const clasesEntidadFilters = ref({});
const showClasesEntidadesFilters = ref(false);

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

const clasesEntidades = computed(() => {
  if (Array.isArray(props.clasesEntidades) && props.clasesEntidades.length > 0) {
    return props.clasesEntidades;
  }

  const uniqueMap = new Map();
  for (const clase of props.clases || []) {
    const entidadId = clase?.entidad_id;
    if (!entidadId) continue;
    if (!uniqueMap.has(entidadId)) {
      uniqueMap.set(entidadId, {
        id: entidadId,
        nombre: clase?.entidad_nombre || `Entidad ${entidadId}`,
      });
    }
  }

  return Array.from(uniqueMap.values()).sort((a, b) => String(a.nombre).localeCompare(String(b.nombre)));
});

watch(
  clasesEntidades,
  (entidades) => {
    const next = {};
    for (const entidad of entidades) {
      next[entidad.id] = clasesEntidadFilters.value[entidad.id] ?? true;
    }
    clasesEntidadFilters.value = next;
  },
  { immediate: true }
);

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
  return route('oracionescantadas.show-public', oracionId);
}

function claseHref(claseId) {
  const returnUrl = `${route('calendario.index')}?month=${props.calendar.month}`;
  return `${route('clases.show-public', claseId)}?return_url=${encodeURIComponent(returnUrl)}`;
}

function itemLabel(item) {
  const hora = item?.hora || item?.hora_inicio;
  return hora ? `${hora} hs. ${item.nombre}` : item.nombre;
}

function isVisibleItem(item) {
  if (item.esClase) {
    if (!showClases.value) return false;
    const entidadId = item?.entidad_id;
    if (!entidadId) return true;
    return clasesEntidadFilters.value[entidadId] ?? true;
  }
  if (item.esOracionCantada) return showOraciones.value;
  return showActividades.value;
}
</script>

<template>
  <AppLayout title="Calendario de Actividades">
    <Head title="Calendario" />

    <div class="min-h-screen bg-slate-50 py-6">
      <div class="mx-auto w-full max-w-7xl px-3 sm:px-6">
        <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 class="text-2xl font-semibold text-slate-900">Calendario</h1>
              <p class="text-sm text-slate-600">Actividades activas del mes</p>
              <div class="mt-4 flex flex-wrap items-center gap-6 text-sm">
                <label class="inline-flex items-center gap-2 px-1 py-1 text-slate-700">
                  <input
                    v-model="showActividades"
                    type="checkbox"
                    class="calendar-filter-checkbox checkbox-emerald"
                  />
                  Cursos y retiros
                </label>
                <label class="inline-flex items-center gap-2 px-1 py-1 text-slate-700">
                  <input
                    v-model="showClases"
                    type="checkbox"
                    class="calendar-filter-checkbox checkbox-amber"
                  />
                  Clases
                </label>
                <label class="inline-flex items-center gap-2 px-1 py-1 text-slate-700">
                  <input
                    v-model="showOraciones"
                    type="checkbox"
                    class="calendar-filter-checkbox checkbox-sky"
                  />
                  Oraciones Cantadas
                </label>
              </div>
              <div v-if="clasesEntidades.length" class="mt-3">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 hover:text-slate-700"
                  @click="showClasesEntidadesFilters = !showClasesEntidadesFilters"
                >
                  <span>Filtrar Clases por lugar</span>
                  <i :class="showClasesEntidadesFilters ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
                </button>
                <div v-if="showClasesEntidadesFilters" class="mt-2 flex flex-wrap items-center gap-4 text-sm">
                  <label
                    v-for="entidad in clasesEntidades"
                    :key="entidad.id"
                    class="inline-flex items-center gap-2 px-1 py-1 text-slate-700"
                  >
                    <input
                      v-model="clasesEntidadFilters[entidad.id]"
                      type="checkbox"
                      class="calendar-filter-checkbox checkbox-amber"
                    />
                    {{ entidad.nombre }}
                  </label>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <Link
                :href="`${route('calendario.index')}?month=${calendar.prevMonth}`"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
              >
                Anterior
              </Link>
              <div class="min-w-44 rounded-lg bg-slate-900 px-4 py-2 text-center text-sm font-medium text-white">
                {{ calendar.monthLabel }}
              </div>
              <Link
                :href="`${route('calendario.index')}?month=${calendar.nextMonth}`"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
              >
                Siguiente
              </Link>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="calendar-grid min-w-[980px] border-b border-slate-200 bg-slate-100">
            <div
              v-for="diaSemana in diasSemana"
              :key="diaSemana"
              class="border-r border-slate-200 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-600 last:border-r-0"
            >
              {{ diaSemana }}
            </div>
          </div>

          <div class="calendar-grid min-w-[980px]">
            <div
              v-for="cell in calendarCells"
              :key="cell.key"
              class="calendar-cell overflow-hidden border-r border-b border-slate-200 p-2"
              :class="cell.empty ? 'bg-slate-50' : 'bg-white'"
            >
              <template v-if="!cell.empty">
                <div class="flex h-full flex-col">
                  <div class="mb-2 flex items-center justify-between">
                    <span
                      class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold"
                      :class="cell.isToday ? 'bg-emerald-600 text-white' : 'text-slate-800'"
                    >
                      {{ cell.day }}
                    </span>
                    <span v-if="cell.actividades.length" class="text-xs text-slate-500">
                      {{ cell.actividades.length }}
                    </span>
                  </div>

                  <div class="min-h-0 flex-1 space-y-1 overflow-y-auto pr-1">
                    <template v-for="actividad in cell.actividades" :key="`${cell.date}-${actividad.tipo || 'actividad'}-${actividad.id}`">
                      <div
                        v-if="actividad.esClase"
                        class="block rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-xs leading-tight text-amber-900"
                        :title="itemLabel(actividad)"
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
                        class="block rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs leading-tight text-emerald-900 hover:bg-emerald-100"
                        :title="itemLabel(actividad)"
                      >
                        {{ itemLabel(actividad) }}
                      </Link>
                      <Link
                        v-else
                        :href="oracionCantadaHref(actividad.id)"
                        class="block rounded-md border border-sky-200 bg-sky-50 px-2 py-1 text-xs leading-tight text-sky-900"
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

.checkbox-emerald {
  accent-color: #059669;
}

.checkbox-amber {
  accent-color: #d97706;
}

.checkbox-sky {
  accent-color: #0284c7;
}
</style>
