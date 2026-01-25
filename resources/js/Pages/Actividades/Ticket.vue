<script>
export default {
  name: 'ActividadesShow'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  actividad: {
    type: Object,
    required: true,
  },
})

const formatDateTime = (fecha, fallback) => {
  if (props.actividad?.fecha_inicio_formateada) return props.actividad.fecha_inicio_formateada
  try {
    const d = new Date(fecha)
    const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
    return d.toLocaleString('es-AR', opts)
  } catch (e) {
    return fallback || fecha
  }
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Ticket</h1>
    </template>

    <div class="min-h-screen bg-white">
      <div class="max-w-md mx-auto px-4 py-6 sm:px-6">
        <!-- Imagen principal -->
        <div class="w-full aspect-[3/2] bg-gray-100 rounded-lg overflow-hidden mb-4">
          <img
            v-if="actividad?.imagen"
            :src="'/storage/' + actividad.imagen.ruta"
            :alt="'Imagen de ' + actividad.nombre"
            class="w-full h-full object-cover"
          />
          <img
            v-else
            src="/storage/img/actividades/imagen-no-disponible.jpg"
            alt="Sin imagen"
            class="w-full h-full object-cover"
          />
        </div>

        <!-- Título -->
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2 text-center">
          {{ actividad.nombre }}
        </h2>
        
        <!-- Fecha y hora -->
        <p class="text-center text-indigo-600 font-medium mb-6">
          {{ formatDateTime(actividad.fecha_inicio, actividad.fecha_inicio) }}
        </p>

        <!-- Acciones -->
        <div class="flex justify-center gap-3">
          <Link :href="route('inscripciones.index')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
            Volver
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
