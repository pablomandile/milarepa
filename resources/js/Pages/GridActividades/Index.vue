<script setup>
import { ref } from 'vue';
import DataView from 'primevue/dataview';
import AppLayout from '@/Layouts/AppLayout.vue';


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
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Descripciones</h1>
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
                                    <div class="flip-card-front p-3">
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
                                        <strong>Fecha y hora:</strong> {{ actividad.fecha_inicio }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <strong>Lugar:</strong> {{ actividad.entidad?.direccion }}
                                    </p>
                                    <p class="text-sm text-gray-600 mt-2">
                                        <strong>Valor:</strong> ${{
                                            actividad.esquemaPrecio?.membresias
                                            ?.find(epm => epm.membresia?.nombre === 'Sin membresía')
                                            ?.precio
                                            || 'Precio no definido'
                                        }}
                                        <!-- {{
                                            actividad.esquemaPrecio?.membresias
                                            ?.find(epm => epm.membresia?.nombre === 'Sin membresía')
                                            ?.precio
                                            || 'Precio no definido'
                                        }} -->
                                        <!-- {{ actividad.valorGeneral }} -->
                                        <br/>
                                        <strong>Con Membresía:</strong> ${{ actividad.valorMembresia }}
                                    </p>

                                    <!-- Botón inscribirme -->
                                    <button
                                        class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded mt-4"
                                        @click="inscribir(actividad)"
                                    >
                                        Inscribirme
                                    </button>
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
