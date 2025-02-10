<script setup>
import { ref } from 'vue';
import DataView from 'primevue/dataview';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';

const $page = usePage();

const props = defineProps({
  actividades: {
    type: Array,
    required: true,
    default: () => []
  }
});

const layout = ref('grid');

// Para controlar cuáles actividades están “giradas”
const flippedCards = ref({}); 
/*
  Será un objeto { [actividadId]: boolean }, 
  donde true => tarjeta girada (muestra descripción).
*/

// Función que alterna el estado flipped
function toggleFlip(id) {
  flippedCards.value[id] = !flippedCards.value[id];
}

// Acción al pulsar “Inscribirme”
function inscribir(actividad) {
  // Aquí tu lógica, p.e. router visit a una ruta 
  // o abrir un formulario. Ejemplo:
  console.log('Inscribiendo en', actividad.nombre);
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades Activas</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-7xl mx-auto">
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
                                class="col-12 sm:col-6 md:col-4 xl:col-3 p-2"
                            >
                                <!-- Contenedor flip-card -->
                                <div
                                class="flip-card-container border-round bg-white shadow-sm hover:shadow-md transition-shadow"
                                :class="{ 'flipped': flippedCards[actividad.id] }"
                                >
                                <!-- flip-card-inner envuelve front y back -->
                                <div class="flip-card-inner">

                                    <!-- FRONT: imagen e info breve -->
                                    <div class="flip-card-front p-3 flex flex-col h-full">
                                        <div class="grow">
                                            <img
                                                class="w-full border-round cursor-pointer"
                                                :src="actividad.imagen?.ruta 
                                                    ? `/storage/${actividad.imagen.ruta}` 
                                                    : '/storage/img/actividades/imagen-no-disponible.jpg'"
                                                :alt="actividad.nombre"
                                                style="max-height: 230px; object-fit: contain;"
                                                @click="toggleFlip(actividad.id)"
                                            />
                                            <h3 class="text-md font-semibold mt-2">
                                                {{ actividad.nombre }}
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                <strong>Fecha y hora:</strong> {{ actividad.fecha_inicio_formateada  }}
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <strong>Lugar:</strong> {{ actividad.entidad?.direccion }}
                                            </p>
                                            <!-- {{ actividad.valorGeneral }} -->
                                            <p
                                            class="text-sm mt-2"
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
                                            <!-- {{ actividad.Valor TK si tiene }} -->
                                            </p>
                                            <p v-if="user.membresia?.nombre !== 'Sin membresía'" class="text-sm text-gray-600 mt-2">
                                                <strong>Con {{ user.membresia?.nombre }} :</strong>
                                                ${{ precioMembresiaUsuario(actividad) }}
                                            </p>
                                        </div>

                                        <!-- Footer (botón) -->
                                        <div class="mt-4">
                                            <button
                                            class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded w-full"
                                            @click="inscribir(actividad)"
                                            >
                                            Inscribirme
                                            </button>
                                        </div>
                                    </div>

                                    <!-- BACK: texto descripción -->
                                    <div class="flip-card-back p-3" @click="toggleFlip(actividad.id)">
                                    <h3 class="text-lg font-semibold mb-2">Descripción</h3>
                                    <p class="text-sm text-gray-700">
                                        {{ actividad.descripcion?.descripcion.substring(0, 540) }} ...
                                    </p>
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
  min-height: 500px;
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
  flex-direction: column; 
  justify-content: space-between; /* Espacio entre la parte superior y el botón */
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
}
</style>
