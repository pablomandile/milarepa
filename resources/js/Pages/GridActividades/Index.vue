<script setup>
import { ref } from 'vue';
import DataView from 'primevue/dataview';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const $page = usePage();

const props = defineProps({
  actividades: {
    type: Array,
    required: true,
    default: () => []
  }
});

const layout = ref('grid');
// Para controlar cuáles actividades están "giradas"
const flippedCards = ref({}); 

// Función que alterna el estado flipped
function toggleFlip(id) {
  flippedCards.value[id] = !flippedCards.value[id];
}
// Acción al pulsar “Inscribirme”
function inscribir(actividad) {
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

  // Enviar POST a la ruta de inscripciones
  router.post('/inscripciones', data);
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

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades del mes</h1>
        </template>
        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mt-4">
                        <DataView
                        :value="actividades"
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
                                class="flip-card-container border-round bg-white shadow-sm hover:shadow-md transition-shadow"
                                :class="{ 'flipped': flippedCards[actividad.id] }"
                                >
                                <!-- flip-card-inner envuelve front y back -->
                                <div class="flip-card-inner">

                                    <!-- FRONT: imagen e info breve -->
                                    <div class="flip-card-front flex flex-row h-full p-4">
                                        <!-- Imagen a la izquierda -->
                                        <div class="w-1/2 pr-3 flex items-center justify-center bg-gray-50 cursor-pointer rounded h-full" @click="toggleFlip(actividad.id)">
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
                                                    'text-gray-600': user.membresia?.nombre === 'Sin membresía',
                                                    'text-red-300 line-through': user.membresia?.nombre !== 'Sin membresía'
                                                }"
                                                >
                                                    <strong>Valor:</strong> ${{
                                                        actividad.esquema_precio?.membresias
                                                        ?.find(epm => epm.membresia?.nombre === 'Sin membresía')
                                                        ?.precio
                                                        || 'Precio no definido'
                                                    }}
                                                </p>
                                                <p v-if="user.membresia?.nombre !== 'Sin membresía'" class="text-sm text-gray-600 mb-2">
                                                    <strong>Con {{ user.membresia?.nombre }}:</strong>
                                                    ${{ precioMembresiaUsuario(actividad) }}
                                                </p>
                                            </div>

                                            <!-- Botón -->
                                            <div class="mt-2">
                                                <button
                                                class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded text-sm w-full transition-colors"
                                                @click="inscribir(actividad)"
                                                >
                                                Inscribirme
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
                                            class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded w-full transition-colors"
                                            @click="inscribir(actividad)"
                                            >
                                            Inscribirme
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
