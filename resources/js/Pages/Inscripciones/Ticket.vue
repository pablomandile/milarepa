<script>
  export default {
    name: 'InscripcionesTicket'
  }
</script>

<script setup>
  import AppLayout from '@/Layouts/AppLayout.vue'
  import { Link } from '@inertiajs/vue3'
  import { router } from '@inertiajs/vue3'
  import ticketBg from '@/../images/Fondo ticket.svg'

  const props = defineProps({
    inscripcion: {
      type: Object,
      required: true,
    },
    actividad: {
      type: Object,
      required: true,
    }
  })

  const formatDateTime = (fecha, formatted) => {
    if (formatted) return formatted
    try {
      const d = new Date(fecha)
      const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
      return d.toLocaleString('es-AR', opts)
    } catch (e) {
      return fecha
    }
  }

  const printTicket = () => {
    window.print()
  }
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Ticket</h1>
    </template>

    <div class="min-h-screen bg-gray-50">
      <div id="ticket-print" class="max-w-md mx-auto px-4 py-6 sm:px-6">
        <div class="relative border border-gray-200 shadow-lg rounded-xl p-6 overflow-hidden bg-white">
          <div
            aria-hidden="true"
                class="absolute inset-0 pointer-events-none opacity-25 z-0"
            :style="{
              backgroundColor: 'rgb(14,165,233)',
              maskImage: `url(${ticketBg})`,
              WebkitMaskImage: `url(${ticketBg})`,
              maskSize: 'cover',
              WebkitMaskSize: 'cover',
              maskPosition: '20% center',
              WebkitMaskPosition: '35% center',
              maskRepeat: 'no-repeat',
              WebkitMaskRepeat: 'no-repeat'
            }"
          ></div>
              <div class="relative z-10">
                <!-- Imagen principal -->
                <div class="w-full aspect-[3/2] bg-transparent rounded-lg mb-4 flex items-center justify-center">
                  <img
                    v-if="actividad?.imagen"
                    :src="'/storage/' + actividad.imagen.ruta"
                    :alt="'Imagen de ' + actividad.nombre"
                    class="max-w-full max-h-full object-contain"
                  />
                  <img
                    v-else
                    src="/storage/img/actividades/imagen-no-disponible.jpg"
                    alt="Sin imagen"
                    class="max-w-full max-h-full object-contain"
                  />
                </div>

                <!-- Título -->
                <h2 class="text-2xl sm:text-xl font-bold text-gray-900 mb-2 text-center">
                  {{ actividad.nombre }}
                </h2>
            
                <!-- Fecha y hora -->
                <p class="text-center text-gray-600 font-medium mb-4">
                  {{ formatDateTime(actividad.fecha_inicio, actividad.fecha_inicio_formateada) }}
                </p>

                <!-- QR para asistencia -->
                <div class="mt-4">
                  <h3 class="text-center text-gray-800 font-semibold mb-2">Presenta este QR al ingresar</h3>
                  <div class="flex justify-center">
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                      <img :src="route('inscripciones.ticketQr', { inscripcion: inscripcion.id })" alt="QR Asistencia" class="w-48 h-48" />
                    </div>
                  </div>
                </div>
              </div>
        </div>

        <!-- Botones fuera de la card -->
        <div class="flex justify-center mt-6 gap-3 no-print">
          <button type="button" @click="printTicket" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            Descargar PDF
          </button>
          <Link :href="route('inscripciones.index')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
            Volver
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
@media print {
  /* Oculta todo excepto el contenedor del ticket */
  body * {
    visibility: hidden;
  }
  #ticket-print,
  #ticket-print * {
    visibility: visible;
  }
  #ticket-print {
    break-inside: avoid-page;
    page-break-inside: avoid;
  }
  /* Evita que elementos externos ocupen espacio */
  .no-print,
  header,
  nav,
  footer {
    display: none !important;
  }
  /* Ajusta márgenes de página para que sólo se vea la card */
  @page {
    size: A4;
    margin: 10mm;
  }
}
</style>
