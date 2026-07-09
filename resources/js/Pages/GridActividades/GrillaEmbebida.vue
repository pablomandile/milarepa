<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { Head } from '@inertiajs/vue3';
import backgroundImage from '../../../images/7036360.svg';
import ActividadCardGrid1 from '@/Components/Actividades/ActividadCardGrid1.vue';
import ActividadCardGrid2 from '@/Components/Actividades/ActividadCardGrid2.vue';
import {
    descuentoVigente,
    direccionActividad,
} from '@/composables/useActividadHelpers';

// Versión embebible de la grilla (iframe en meditarenargentina.org).
// Dentro de un iframe cross-site el browser bloquea las cookies de terceros y
// cualquier POST fallaría con 419: acá no hay lookup por email ni modal de
// inscripción; "Inscribirme" y "Más info" abren milarepa.com.ar en pestaña nueva.

const props = defineProps({
  actividades: {
    type: Array,
    required: true,
    default: () => []
  },
  gridVariante: {
    type: String,
    default: 'grid1',
  }
});

const layout = ref('grid');
// Para controlar cuáles actividades están "giradas"
const flippedCards = ref({});
const expandedServicios = ref({});
const toast = useToast();
const mapModalVisible = ref(false);
const selectedAddress = ref('');

const mapEmbedUrl = computed(() => {
  if (!selectedAddress.value) return '';
  return `https://maps.google.com/maps?q=${encodeURIComponent(selectedAddress.value)}&output=embed`;
});

// Función que alterna el estado flipped
function toggleFlip(id) {
  flippedCards.value[id] = !flippedCards.value[id];
}

function toggleServicios(id) {
  expandedServicios.value[id] = !expandedServicios.value[id];
}

function actividadFinalizada(actividad) {
  const valor = actividad?.fecha_fin ?? actividad?.fechaFin ?? actividad?.fecha_hasta ?? null;
  if (!valor) return false;
  const fechaFin = (typeof valor === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(valor))
    ? new Date(`${valor}T23:59:59`)
    : new Date(valor);
  if (Number.isNaN(fechaFin.getTime())) return false;
  return new Date() > fechaFin;
}

const actividadesActivas = ref([]);

watch(() => props.actividades, (newActividades) => {
  actividadesActivas.value = newActividades.filter(a => a.estado === true || a.estado === 1);
}, { immediate: true });

function abrirEnMilarepa(actividad) {
  window.open(route('grid-actividades.show-public', actividad.id), '_blank', 'noopener');
}

// Acción al pulsar "Inscribirme"
function inscribir(actividad) {
  if (actividadFinalizada(actividad)) {
    toast.add({
      severity: 'warn',
      summary: 'Inscripción cerrada',
      detail: 'La fecha de esta actividad ya finalizó.',
      life: 5000,
    });
    return;
  }

  abrirEnMilarepa(actividad);
}

function irMasInfo(actividad) {
  abrirEnMilarepa(actividad);
}

function abrirMapa(direccion) {
  if (!direccion) return;
  selectedAddress.value = direccion;
  mapModalVisible.value = true;
}

// Auto-alto del iframe: la página que embebe no puede medir contenido
// cross-origin, así que le avisamos la altura real por postMessage y un
// script del lado de WordPress ajusta el height del iframe (sin scroll).
// Se usa body.offsetHeight y no scrollHeight porque scrollHeight nunca baja
// del alto del viewport y el iframe solo podría crecer, nunca achicarse.
let resizeObserver = null;

function publicarAltura() {
  const height = Math.ceil(document.body.offsetHeight);
  window.parent.postMessage({ type: 'grillaembebida:height', height }, '*');
}

onMounted(() => {
  if (window.self === window.top) return;
  publicarAltura();
  resizeObserver = new ResizeObserver(publicarAltura);
  resizeObserver.observe(document.body);
});

onBeforeUnmount(() => {
  resizeObserver?.disconnect();
});

function cardConMuchoTexto(actividad) {
  let items = 0;
  if (actividad?.modalidad?.nombre) items++;
  if (descuentoVigente(actividad)) items++;
  if (actividad?.hospedajes?.length) items++;
  if (actividad?.comidas?.length) items++;
  if (actividad?.transportes?.length) items++;
  if (actividad?.grabacion || actividad?.grabacion_id) items++;

  const direccionLarga = String(direccionActividad(actividad) || '').length > 50;
  return items >= 5 || direccionLarga;
}

</script>

<template>
    <Head title="Grilla de actividades" />
    <Toast position="top-right" />
    <div class="bg-white p-3 sm:p-4">
        <!-- Sin paginación: en el iframe se muestran siempre todas las actividades
             y el alto lo ajusta el auto-resize por postMessage. -->
        <DataView
        :value="actividadesActivas"
        :layout="layout"
        class="mb-6"
        >

        <!-- Template grid -->
        <template #grid="slotProps">
            <div
                :class="gridVariante === 'grid2'
                    ? 'grid grid-cols-1 gap-y-2'
                    : 'grid grid-cols-1 md:grid-cols-2 gap-4'"
            >
                <div
                    v-for="(actividad, index) in slotProps.items"
                    :key="actividad.id"
                    class="min-w-0"
                >
                    <ActividadCardGrid1
                        v-if="gridVariante !== 'grid2'"
                        :actividad="actividad"
                        :flipped="!!flippedCards[actividad.id]"
                        :expanded-servicios="!!expandedServicios[actividad.id]"
                        :user-context="null"
                        :inscripciones-ids="[]"
                        :card-mobile-tall="cardConMuchoTexto(actividad)"
                        :background-image="backgroundImage"
                        @toggle-flip="toggleFlip"
                        @toggle-servicios="toggleServicios"
                        @inscribir="inscribir"
                        @mas-info="irMasInfo"
                        @abrir-mapa="abrirMapa"
                    />
                    <ActividadCardGrid2
                        v-else
                        :actividad="actividad"
                        :flipped="!!flippedCards[actividad.id]"
                        :user-context="null"
                        :inscripciones-ids="[]"
                        :image-side="index % 2 === 0 ? 'right' : 'left'"
                        @toggle-flip="toggleFlip"
                        @inscribir="inscribir"
                        @mas-info="irMasInfo"
                        @abrir-mapa="abrirMapa"
                    />
                </div>
            </div>
        </template>
        </DataView>

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
    </div>
</template>
