<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  inscripcion: {
    type: Object,
    required: true,
  },
});

const formatPrice = (value) => {
  if (value === null || value === undefined || isNaN(value)) return '0,00';
  return new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
};

const serviciosInvitado = (invitado) => {
  const items = [];
  if (invitado.incluye_grabacion) items.push('Grabación');
  (invitado.comidas || []).forEach((c) => items.push(c.nombre));
  (invitado.transportes || []).forEach((t) => items.push(t.descripcion || 'Transporte'));
  (invitado.hospedajes || []).forEach((h) => items.push(h.nombre));
  return items.length ? items.join(', ') : 'Sin servicios';
};
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
                {{ inscripcion.estado || 'Registrada' }}
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

          <div v-if="inscripcion.invitados && inscripcion.invitados.length" class="mt-6 rounded-2xl border border-violet-100 bg-violet-50 p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Invitados ({{ inscripcion.invitados.length }})
            </h3>
            <div class="space-y-2">
              <div
                v-for="invitado in inscripcion.invitados"
                :key="invitado.id"
                class="flex flex-wrap items-center justify-between gap-2 border-b border-violet-100 last:border-0 pb-2 last:pb-0"
              >
                <div class="text-sm text-gray-700">
                  <span class="font-semibold text-gray-900">{{ invitado.nombre }} {{ invitado.apellido }}</span>
                  <span v-if="invitado.telefono" class="text-gray-500"> · {{ invitado.telefono }}</span>
                  <span v-if="invitado.online" class="ml-1 text-xs text-indigo-600">(Online)</span>
                  <span class="block text-xs text-gray-500">{{ serviciosInvitado(invitado) }}</span>
                </div>
                <span class="text-sm font-semibold text-green-700">${{ formatPrice(invitado.montoapagar) }}</span>
              </div>
            </div>
          </div>

          <div class="mt-6 rounded-2xl border border-gray-100 bg-gray-50 p-4 text-xl text-gray-800">
            Gracias por inscribirte. Recibirás un <strong>correo de confirmación</strong> con los detalles de tu inscripción.
            Te contactaremos cuando el pago sea verificado.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
