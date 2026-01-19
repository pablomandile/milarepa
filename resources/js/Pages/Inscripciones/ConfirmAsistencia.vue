<script>
  export default {
    name: 'ConfirmAsistencia'
  }
</script>

<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue'
  import { Link } from '@inertiajs/vue3'

  const props = defineProps({
    inscripcion: {
      type: Object,
      required: true,
    },
    actividad: {
      type: Object,
      required: true,
    },
    status: {
      type: String,
      default: 'ok'
    }
  })
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Asistencia</h1>
    </template>

    <div class="min-h-screen bg-white">
      <div class="max-w-md mx-auto px-4 py-6 sm:px-6 text-center">
        <div class="mb-4">
          <h2 class="text-2xl font-bold text-green-600">Asistencia registrada</h2>
          <p class="text-gray-700 mt-2">{{ actividad.nombre }}</p>
        </div>
        <p class="text-sm text-gray-500">Inscripción #{{ inscripcion.id }}</p>
        <div class="mt-6">
          <Link :href="route('inscripciones.index')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
            Volver
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
