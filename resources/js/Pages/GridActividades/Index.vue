<script setup>
import { ref, watch } from 'vue';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage, Link, router } from '@inertiajs/vue3';

const $page = usePage();

const props = defineProps({
  actividades: {
    type: Array,
    required: true,
    default: () => []
  },
  userInscripcionesActividadIds: {
    type: Array,
    required: false,
    default: () => []
  }
});

const layout = ref('grid');
// Para controlar cuáles actividades están "giradas"
const flippedCards = ref({}); 
// Modal de confirmación de inscripción
const confirmModalVisible = ref(false);
const actividadAInscribir = ref(null);
const toast = useToast();

// Función para formatear precios con punto para miles y coma para decimales
const formatPrice = (price) => {
  if (!price || isNaN(price)) return '0,00';
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price);
};

// Mostrar toast cuando cambian los mensajes flash (éxito / error)
watch(() => $page.props.flash, (flash) => {
    if (flash?.error) {
        toast.add({
            severity: 'warn',
            summary: 'Aviso',
            detail: flash.error || 'Ya está inscripto a esa actividad!',
            life: 10000,
        });
    } else if (flash?.success) {
        toast.add({
            severity: 'success',
            summary: 'Inscripción',
            detail: flash.success,
            life: 5000,
        });
    }
}, { immediate: true });

// Función que alterna el estado flipped
function toggleFlip(id) {
  flippedCards.value[id] = !flippedCards.value[id];
}
// Acción al pulsar “Inscribirme”
function inscribir(actividad) {
  actividadAInscribir.value = actividad;
  confirmModalVisible.value = true;
}
// Función para confirmar la inscripción después del modal
function confirmarInscripcion() {
  const actividad = actividadAInscribir.value;
  
  // Calcular precios
  const precioGeneral = actividad.esquema_precio?.membresias
    ?.find(epm => epm.membresia?.nombre === 'Sin membresía')
    ?.precio || 0;

  const precioMembresia = precioMembresiaUsuario(actividad);

  // Datos para la inscripción
  const data = {
    actividad_id: actividad.id,
    user_id: user.id,
    membresia: user.membresia?.nombre || 'Sin membresía',
    precioGeneral: precioGeneral,
    montoapagar: precioMembresia,
    pago: 'impago', // Por defecto impago
    estado_id: 1, // Asumiendo ID 1 para pendiente
    envioLinkStream: 'pendiente',
    envioGrabación: 'pendiente',
    comprobante: null,
    asistencia: 'ausente', // Por defecto ausente
    online: actividad.modalidad?.nombre === 'Online' ? true : false,
    hospedaje_id: null, // Por ahora null
    comida_id: null,
    transporte_id: null,
  };

  // Cerrar modal
  confirmModalVisible.value = false;
  actividadAInscribir.value = null;

    // Enviar POST a la ruta de inscripciones
    // Nota: dejamos que el watcher de $page.props.flash dispare el toast.
    router.post('/inscripciones', data, {
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo procesar la inscripción.',
                life: 5000,
            });
        },
    });
}
const user = $page.props.auth.user;

function precioMembresiaUsuario(actividad) {
  if (!actividad.esquema_precio?.membresias) return 0;

  const userMemId = user.membresia?.id; 
  // O user.membresia_id si guardas IDs directos en user.

  // Buscar en 'membresias' la pivot con 'membresia_id' = userMemId
  const pivot = actividad.esquema_precio.membresias.find(epm => epm.membresia_id === userMemId);

  // Si lo halla, retorna pivot.precio, sino 0 o "N/D"
  return pivot ? pivot.precio : 0;
}

function esInscrito(actividadId) {
  return props.userInscripcionesActividadIds.includes(actividadId);
}

const actividadesActivas = ref([]);

watch(() => props.actividades, (newActividades) => {
  actividadesActivas.value = newActividades.filter(a => a.estado === true || a.estado === 1);
}, { immediate: true });

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades del mes</h1>
        </template>
        <Toast position="top-right" />
        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mt-4">
                        <DataView
                        :value="actividadesActivas"
                        :layout="layout"
                        paginator
                        :rows="16"
                        :rowsPerPageOptions="[8, 16, 24]"
                        class="mb-6"
                        >

                        <!-- Template grid -->
                        <template #grid="slotProps">
                            <div class="grid grid-nogutter">
                            <!-- Recorremos las actividades -->
                            <div
                                v-for="(actividad, index) in slotProps.items"
                                :key="actividad.id"
                                class="col-12 md:col-6 xl:col-4 p-2"
                            >
                                <!-- Contenedor flip-card -->
                                <div
                                class="flip-card-container border-round shadow-sm hover:shadow-md transition-shadow"
                                :class="{ 'flipped': flippedCards[actividad.id] }"
                                style="background: linear-gradient(135deg, #f3e8ff 0%, #d8b4f8 100%);"
                                >
                                <!-- flip-card-inner envuelve front y back -->
                                <div class="flip-card-inner">

                                    <!-- FRONT: imagen e info breve -->
                                    <div class="flip-card-front flex flex-row h-full p-4">
                                        <!-- Imagen a la izquierda -->
                                        <div class="w-1/2 pr-3 flex items-center justify-center bg-transparent cursor-pointer rounded h-full" @click="toggleFlip(actividad.id)">
                                            <img
                                                class="w-full h-full object-contain rounded"
                                                :src="actividad.imagen?.ruta
                                                    ? `/storage/${actividad.imagen.ruta}`
                                                    : '/storage/img/actividades/imagen-no-disponible.jpg'"
                                                :alt="actividad.nombre"
                                            />
                                        </div>

                                        <!-- Texto a la derecha -->
                                        <div class="w-1/2 flex flex-col justify-between pl-2">
                                            <div class="flex-1">
                                                <h3 class="text-lg font-semibold mb-2 text-gray-800 leading-tight">
                                                    {{ actividad.nombre }}
                                                </h3>
                                                <p class="text-sm text-gray-600 mb-1">
                                                    <strong>Fecha:</strong> {{ actividad.fecha_inicio_formateada }}
                                                </p>
                                                <p class="text-sm text-gray-600 mb-1">
                                                    <strong>Lugar:</strong> {{ actividad.entidad?.direccion }}
                                                </p>
                                                <p
                                                class="text-sm mb-1"
                                                :class="{
                                                    'font-bold': !user?.membresia || user.membresia?.nombre === 'Sin membresía',
                                                    'text-gray-700 line-through text-gray-400': user?.membresia && user.membresia?.nombre !== 'Sin membresía'
                                                }"
                                                >
                                                    <strong>Valor:</strong> 
                                                    <span v-if="parseInt(actividad.esquema_precio?.membresias?.find(epm => epm.membresia?.nombre === 'Sin membresía')?.precio) === 0" class="font-bold text-gray-700">
                                                      Incluído
                                                    </span>
                                                    <span v-else class="font-bold text-gray-700">
                                                      ${{ formatPrice(actividad.esquema_precio?.membresias?.find(epm => epm.membresia?.nombre === 'Sin membresía')?.precio || 0) }}
                                                    </span>
                                                </p>
                                                <p v-if="user?.membresia && user.membresia?.nombre !== 'Sin membresía'" class="text-sm text-gray-600 mb-2">
                                                    <strong>Con {{ user.membresia?.nombre }}:</strong>
                                                    <span v-if="parseInt(precioMembresiaUsuario(actividad)) === 0" class="font-bold text-green-700"> Incluído</span>
                                                    <span v-else class="font-bold text-green-700"> ${{ formatPrice(precioMembresiaUsuario(actividad)) }}</span>
                                                </p>
                                            </div>

                                            <!-- Botones -->
                                            <div class="mt-2 flex gap-2">
                                                <Link
                                                :href="route('actividades.show', actividad.id)"
                                                class="bg-gray-500 hover:bg-gray-600 text-white py-1 px-2 rounded text-xs flex-1 transition-colors text-center flex items-center justify-center gap-1 whitespace-nowrap"
                                                >
                                                <i class="pi pi-plus"></i>
                                                <span>Más info.</span>
                                                </Link>
                                                <button
                                                :disabled="esInscrito(actividad.id)"
                                                class="py-1 px-3 rounded text-sm flex-1 transition-colors flex items-center justify-center gap-1"
                                                :class="esInscrito(actividad.id) ? 'bg-gray-200 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                                @click="inscribir(actividad)"
                                                >
                                                <i v-if="esInscrito(actividad.id)" class="pi pi-heart-fill"></i>
                                                {{ esInscrito(actividad.id) ? 'Inscripto' : 'Inscribirme' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BACK: descripción completa -->
                                    <div class="flip-card-back p-6 flex flex-col h-full" @click="toggleFlip(actividad.id)">
                                        <h3 class="text-xl font-semibold mb-4 text-gray-800">Descripción</h3>
                                        <div class="flex-1 overflow-y-auto">
                                            <p class="text-sm text-gray-700 leading-relaxed">
                                                {{ actividad.descripcion?.descripcion || 'No hay descripción disponible' }}
                                            </p>
                                        </div>
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <button
                                            :disabled="esInscrito(actividad.id)"
                                            class="py-2 px-4 rounded w-full transition-colors flex items-center justify-center gap-2"
                                            :class="esInscrito(actividad.id) ? 'bg-gray-400 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                            @click="inscribir(actividad)"
                                            >
                                            <i v-if="esInscrito(actividad.id)" class="pi pi-heart-fill"></i>
                                            {{ esInscrito(actividad.id) ? 'Inscripto' : 'Inscribirme' }}
                                            </button>
                                        </div>
                                    </div>
                                </div> <!-- flip-card-inner -->
                                </div> <!-- flip-card-container -->
                            </div>
                            </div>
                        </template>
                        </DataView>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación de inscripción -->
        <Dialog
            v-model:visible="confirmModalVisible"
            modal
            header="Confirmar Inscripción"
            :style="{ width: '450px' }"
            :closable="false"
        >
            <div class="flex items-center">
                <i class="pi pi-exclamation-triangle text-yellow-500 text-3xl mr-3"></i>
                <div>
                    <p class="mb-2">¿Estás seguro de que deseas inscribirte en esta actividad?</p>
                    <p class="text-sm text-gray-600" v-if="actividadAInscribir">
                        <strong>{{ actividadAInscribir.nombre }}</strong><br>
                        <span v-if="actividadAInscribir.modalidad">Modalidad: {{ actividadAInscribir.modalidad.nombre }}</span>
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button 
                        @click="confirmModalVisible = false; actividadAInscribir = null"
                        class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors flex items-center gap-2"
                    >
                        <i class="pi pi-times"></i>
                        Cancelar
                    </button>
                    <button 
                        @click="confirmarInscripcion"
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700 transition-colors flex items-center gap-2"
                    >
                        <i class="pi pi-check"></i>
                        Confirmar Inscripción
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Estilos para flip-card */
.flip-card-container {
  perspective: 1000px;
  /* Un poco de borde redondo: */
  border-radius: 8px;
  overflow: hidden;
  position: relative;
  height: 300px;
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
  flex-direction: row;
  justify-content: space-between;
  height: 100%;
}

/* front y back se apilan, uno rota 0°, el otro 180° */
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

/* la cara posterior */
.flip-card-back {
  background-color: #f8fafc; /* un fondo neutro */
  transform: rotateY(180deg);
  cursor: pointer;
}
</style>
