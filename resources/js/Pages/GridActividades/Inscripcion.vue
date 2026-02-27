<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  inscripcion: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Inscripción registrada</h1>
    </template>
        <div class="py-12">
      <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
          <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-2xl font-semibold text-gray-800">
                {{ inscripcion.actividad?.nombre || 'Actividad' }}
              </h2>
              <p class="text-sm text-gray-600 mt-1">
                Fecha: {{ inscripcion.actividad?.fecha_inicio_formateada || '-' }}
              </p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700">
              Inscripción registrada
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-indigo-100 p-5 shadow-sm">
              <p class="text-xs uppercase tracking-wide text-indigo-500">Estado</p>
              <p class="mt-3 text-base font-semibold text-indigo-900">
                {{ inscripcion.estado?.estado || 'Pendiente' }}
              </p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-emerald-100 p-5 shadow-sm">
              <p class="text-xs uppercase tracking-wide text-emerald-500">Pago</p>
              <p class="mt-3 text-base font-semibold text-emerald-900">
                {{ inscripcion.pago }}
              </p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-amber-100 p-5 shadow-sm">
              <p class="text-xs uppercase tracking-wide text-amber-600">Comprobante</p>
              <p class="mt-3 text-base font-semibold text-amber-900">
                {{ inscripcion.comprobantes?.length ? 'Cargado' : 'No cargado' }}
              </p>
            </div>
          </div>

          <div class="mt-6 rounded-2xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-600">
            Gracias por inscribirte. Te contactaremos cuando el pago sea verificado.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
