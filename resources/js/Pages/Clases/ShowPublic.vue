<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';

const props = defineProps({
  clase: {
    type: Object,
    required: true,
  },
  returnUrl: {
    type: String,
    default: null,
  },
  descripcionesCards: {
    type: Object,
    default: () => ({}),
  },
});

const dayLabels = {
  lunes: 'Lunes',
  martes: 'Martes',
  miercoles: 'Miercoles',
  jueves: 'Jueves',
  viernes: 'Viernes',
  sabado: 'Sabado',
  domingo: 'Domingo',
};

const diasSemanaTexto = computed(() => {
  const dias = Array.isArray(props.clase?.dias_semana) ? props.clase.dias_semana : [];
  if (dias.length === 0) return '-';

  const ordered = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']
    .filter((d) => dias.includes(d));

  if (ordered.length === 7) return 'Todos los dias';
  return ordered.map((d) => dayLabels[d] || d).join(', ');
});

const horarioTexto = computed(() => {
  const desde = props.clase?.horario_desde ? String(props.clase.horario_desde).slice(0, 5) : null;
  const hasta = props.clase?.horario_hasta ? String(props.clase.horario_hasta).slice(0, 5) : null;
  if (!desde && !hasta) return '-';
  if (desde && hasta) return `${desde} - ${hasta} hs.`;
  return `${desde || hasta} hs.`;
});

const mesTexto = computed(() => {
  const mes = props.clase?.mes_referencia;
  if (!mes || !/^\d{4}-(0[1-9]|1[0-2])$/.test(mes)) return '-';
  const [year, month] = mes.split('-').map(Number);
  return new Date(year, month - 1, 1).toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
});

const titulosPorFecha = computed(() => {
  const raw = props.clase?.titulos_por_fecha;
  if (!raw || typeof raw !== 'object') return [];

  return Object.entries(raw)
    .filter(([fecha, titulo]) => !!fecha && !!titulo)
    .sort((a, b) => String(a[0]).localeCompare(String(b[0])))
    .map(([fecha, titulo]) => ({
      fecha,
      fechaTexto: new Date(`${fecha}T00:00:00`).toLocaleDateString('es-AR', {
        weekday: 'long',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      }),
      titulo,
    }));
});

const backUrl = computed(() => props.returnUrl || route('calendario.index'));
const clasePgDescripcion = computed(() => {
  return props.descripcionesCards?.clasePg?.descripcion
    || 'Sin descripcion configurada para "Clase PG".';
});
const estructuraSesionDescripcion = computed(() => {
  return props.descripcionesCards?.estructuraSesion?.descripcion
    || 'Sin descripcion configurada para "Estructura de una sesion".';
});
const mapModalVisible = ref(false);
const selectedAddress = ref('');
const maestroImageDialogVisible = ref(false);
const selectedMaestroImageUrl = ref('');

const mapEmbedUrl = computed(() => {
  if (!selectedAddress.value) return '';
  return `https://maps.google.com/maps?q=${encodeURIComponent(selectedAddress.value)}&output=embed`;
});

function abrirMapa(direccion) {
  if (!direccion) return;
  selectedAddress.value = direccion;
  mapModalVisible.value = true;
}

function abrirImagenMaestro(url) {
  if (!url) return;
  selectedMaestroImageUrl.value = url;
  maestroImageDialogVisible.value = true;
}
</script>

<template>
  <AppLayout title="Clase">
    <Head :title="clase.nombre" />

    <div class="min-h-screen bg-slate-50 py-8">
      <div class="mx-auto w-full max-w-5xl px-4 sm:px-6">
        <div class="mb-4">
          <Link
            :href="backUrl"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
          >
            Volver
          </Link>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="grid gap-0 md:grid-cols-[280px_1fr]">
            <div class="border-b border-slate-200 bg-slate-100 md:border-b-0 md:border-r">
              <div v-if="clase.imagen" class="h-full">
                <img
                  :src="`/storage/${clase.imagen.ruta}`"
                  :alt="clase.nombre"
                  class="h-full min-h-[240px] w-full object-cover"
                />
              </div>
              <div v-else class="flex min-h-[240px] items-center justify-center text-sm text-slate-500">
                Sin imagen
              </div>
            </div>

            <div class="p-5 sm:p-6">
              <h1 class="text-2xl font-semibold text-slate-900">{{ clase.nombre }}</h1>

              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-3">
                  <div class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="pi pi-calendar"></i>
                    <span>Mes</span>
                  </div>
                  <div class="mt-1 text-sm text-slate-900">{{ mesTexto }}</div>
                </div>
                <div class="rounded-lg border border-sky-100 bg-gradient-to-br from-sky-50 to-white p-3">
                  <div class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="pi pi-clock"></i>
                    <span>Horario</span>
                  </div>
                  <div class="mt-1 text-sm text-slate-900">{{ horarioTexto }}</div>
                </div>
                <div class="rounded-lg border border-violet-100 bg-gradient-to-br from-violet-50 to-white p-3">
                  <div class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="pi pi-calendar-plus"></i>
                    <span>Dias de la semana</span>
                  </div>
                  <div class="mt-1 text-sm text-slate-900">{{ diasSemanaTexto }}</div>
                </div>
                <div class="rounded-lg border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-3">
                  <div class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="pi pi-map-marker"></i>
                    <span>Lugar</span>
                  </div>
                    <div class="mt-1 text-sm text-slate-900">{{ clase.entidad?.nombre || '-' }}</div>
                      <div class="mt-1 inline-flex items-center gap-2 text-sm text-slate-700">
                        <span>{{ clase.entidad?.direccion || '-' }}</span>
                        <button
                          v-if="clase.entidad?.direccion"
                          type="button"
                          class="inline-flex items-center justify-center p-0 text-sky-700 hover:text-sky-900"
                          title="Ver en mapa"
                          aria-label="Ver en mapa"
                          @click="abrirMapa(clase.entidad.direccion)"
                        >
                          <i class="pi pi-map"></i>
                        </button>
                      </div>
                      <div class="mt-1 text-sm text-slate-700">
                        <span class="font-medium">Tel:</span> {{ clase.entidad?.telefono || '-' }}
                      </div>
                    </div>
                  </div>

              <div class="mt-5 grid gap-3 sm:grid-cols-2">
                
                <div class="rounded-lg border border-rose-100 bg-gradient-to-br from-rose-50 to-white min-h-[88px] overflow-hidden">
                  <div class="flex items-stretch h-full">
                    <div class="flex-1 p-3">
                      <div class="inline-flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <i class="pi pi-user"></i>
                        <span>Maestr@</span>
                      </div>
                      <div class="mt-1 text-sm text-slate-900">{{ clase.maestro?.nombre || '-' }}</div>
                    </div>
                    <div v-if="clase.maestro?.imagen" class="w-20 shrink-0">
                      <button
                        type="button"
                        class="relative h-full w-full group cursor-zoom-in"
                        @click="abrirImagenMaestro(`/storage/${clase.maestro.imagen.ruta}`)"
                      >
                        <img
                          :src="`/storage/${clase.maestro.imagen.ruta}`"
                          :alt="`Foto de ${clase.maestro.nombre || 'maestrx'}`"
                          class="h-full w-full object-cover border-l border-rose-200"
                        />
                        <span class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity duration-150 group-hover:opacity-100">
                          <i class="pi pi-search-plus text-white text-base"></i>
                        </span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Descripcion</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                  {{ clase.descripcion || 'Sin descripcion.' }}
                </p>
              </div>

              <div class="mt-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Titulos por fecha</h2>
                <div v-if="titulosPorFecha.length === 0" class="mt-2 text-sm text-slate-600">
                  No hay titulos cargados por fecha.
                </div>
                <div v-else class="mt-2 space-y-2">
                  <div
                    v-for="item in titulosPorFecha"
                    :key="item.fecha"
                    class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                  >
                    <div class="text-xs text-slate-500">{{ item.fechaTexto }}</div>
                    <div class="text-sm font-medium text-slate-900">{{ item.titulo }}</div>
                  </div>
                </div>
              </div>

              <div class="mt-8 clase-descripcion-cards">
                <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-indigo-100 p-5 shadow-sm">
                  <div class="inline-flex items-center gap-2 text-indigo-700">
                    <i class="pi pi-book"></i>
                    <h3 class="text-lg font-semibold">Detalles de las clases</h3>
                  </div>
                  <p class="mt-3 whitespace-pre-line text-lg leading-6 text-slate-700">
                    {{ clasePgDescripcion }}
                  </p>
                </div>

                <div class="rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-amber-100 p-5 shadow-sm">
                  <div class="inline-flex items-center gap-2 text-amber-700">
                    <i class="pi pi-sitemap"></i>
                    <h3 class="text-lg font-semibold">Estructura de una sesion</h3>
                  </div>
                  <p class="mt-3 whitespace-pre-line text-lg leading-6 text-slate-700">
                    {{ estructuraSesionDescripcion }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="mapModalVisible"
      modal
      header="Ubicacion"
      :style="{ width: '800px' }"
    >
      <div v-if="selectedAddress" class="space-y-3">
        <p class="text-sm text-gray-700">{{ selectedAddress }}</p>
        <iframe
          :src="mapEmbedUrl"
          class="w-full h-[60vh] rounded border border-gray-200"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
      </div>
    </Dialog>

    <Dialog
      v-model:visible="maestroImageDialogVisible"
      modal
      header="Foto de Maestrx"
      :style="{ width: '720px' }"
    >
      <div class="w-full">
        <img
          v-if="selectedMaestroImageUrl"
          :src="selectedMaestroImageUrl"
          alt="Foto de Maestrx"
          class="w-full max-h-[70vh] object-contain"
        />
      </div>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
.clase-descripcion-cards {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}
</style>



