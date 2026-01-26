<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { watch } from 'vue';

const $page = usePage();
const toast = useToast();

const props = defineProps({
  inscripcion: {
    type: Object,
    required: true,
  }
});

const formatPrice = (value) => {
  if (value === null || value === undefined || isNaN(value)) return '0,00';
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
};

watch(() => $page.props.flash, (flash) => {
  if (flash?.success) {
    toast.add({
      severity: 'success',
      summary: 'Inscripción',
      detail: flash.success,
      life: 5000,
    });
  }
  if (flash?.email_status) {
    const severity = flash.email_status.enviado ? 'success' : 'warn';
    toast.add({
      severity: severity,
      summary: flash.email_status.enviado ? 'Email Enviado' : 'Advertencia de Email',
      detail: flash.email_status.mensaje,
      life: 7000,
    });
  }
  if (flash?.error) {
    toast.add({
      severity: 'warn',
      summary: 'Aviso',
      detail: flash.error,
      life: 10000,
    });
  }
}, { immediate: true });
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Confirmación de Inscripción</h1>
    </template>
    <Toast position="top-right" />

    <div class="py-10">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-indigo-50">
          <div class="relative p-6 sm:p-8 bg-gradient-to-r from-white to-indigo-50">
            <div class="flex flex-col md:flex-row md:items-start gap-6">
              <div class="flex-1">
                <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wide">Inscripción registrada</p>
                <h2 class="text-3xl font-bold text-gray-900 mt-1">¡Inscripción Exitosa!</h2>
                <p class="text-gray-600 mt-3">Tu inscripción fue confirmada. Revisa los detalles y guarda esta información.</p>
              </div>
              <div class="w-48 shrink-0 self-start">
                <div class="w-48 bg-white rounded-xl shadow p-2 border border-indigo-100 group overflow-hidden">
                  <img
                    v-if="inscripcion.actividad?.imagen"
                    :src="'/storage/' + inscripcion.actividad.imagen.ruta"
                    :alt="inscripcion.actividad?.nombre || 'Actividad'"
                    class="w-full h-32 object-contain rounded-lg transition-transform duration-300 ease-out transform group-hover:scale-110 cursor-zoom-in"
                  />
                  <div v-else class="w-full h-32 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                    Sin imagen
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="p-6 sm:p-8 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="bg-indigo-50 rounded-xl border border-indigo-100 shadow-sm p-4 sm:p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Actividad
                </h3>
                <p class="text-gray-800"><strong>Nombre:</strong> {{ inscripcion.actividad.nombre }}</p>
                <p class="text-gray-700 mt-1"><strong>Fecha:</strong> {{ inscripcion.actividad.fecha_inicio_formateada }}</p>
                <p class="text-gray-700 mt-1"><strong>Lugar:</strong> {{ inscripcion.actividad.entidad?.direccion || 'No especificado' }}</p>
                <p class="text-gray-700 mt-1"><strong>Modalidad:</strong> {{ inscripcion.actividad.modalidad?.nombre || 'No especificado' }}</p>
              </div>

              <div class="bg-emerald-50 rounded-xl border border-emerald-100 shadow-sm p-4 sm:p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Usuario
                </h3>
                <p class="text-gray-800"><strong>Nombre:</strong> {{ inscripcion.user.name }}</p>
                <p class="text-gray-700 mt-1"><strong>Email:</strong> {{ inscripcion.user.email }}</p>
                <p class="text-gray-700 mt-1"><strong>Membresía:</strong> {{ inscripcion.membresia }}</p>
              </div>

              <div class="bg-amber-50 rounded-xl border border-amber-100 shadow-sm p-4 sm:p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Detalles de Pago
                </h3>
                <p class="text-gray-800"><strong>Precio General:</strong> ${{ formatPrice(inscripcion.precioGeneral) }}</p>
                <p class="text-green-700 font-bold mt-1"><strong>Monto a Pagar:</strong> ${{ formatPrice(inscripcion.montoapagar) }}</p>
                <p class="text-gray-700 mt-2 flex items-center gap-2">
                  <strong>Estado de Pago:</strong>
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="inscripcion.pago === 'pagado' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                    {{ inscripcion.pago === 'pagado' ? 'Pagado' : 'Pendiente' }}
                  </span>
                </p>
              </div>

              <div class="bg-sky-50 rounded-xl border border-sky-100 shadow-sm p-4 sm:p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Estado
                </h3>
                <p class="text-gray-800"><strong>Estado inscripción:</strong> {{ inscripcion.estado.estado }}</p>
                <p class="text-gray-700 mt-1"><strong>Envío Link Stream:</strong> {{ inscripcion.envioLinkStream }}</p>
                <p class="text-gray-700 mt-1"><strong>Envío Grabación:</strong> {{ inscripcion.envioGrabación }}</p>
                <p class="text-gray-700 mt-1"><strong>Online:</strong> {{ inscripcion.online ? 'Sí' : 'No' }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div v-if="inscripcion.hospedaje" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-1">Hospedaje</h4>
                <p class="text-gray-700">{{ inscripcion.hospedaje.nombre }}</p>
              </div>
              <div v-if="inscripcion.comida" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-1">Comida</h4>
                <p class="text-gray-700">{{ inscripcion.comida.nombre }}</p>
              </div>
              <div v-if="inscripcion.transporte" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-1">Transporte</h4>
                <p class="text-gray-700">{{ inscripcion.transporte.nombre }}</p>
              </div>
              <div v-if="inscripcion.comprobante" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sm:col-span-3">
                <h4 class="text-sm font-semibold text-gray-900 mb-1">Comprobante</h4>
                <p class="text-gray-700">{{ inscripcion.comprobante }}</p>
              </div>
            </div>

            <div class="pt-2 flex flex-wrap gap-3">
              <button
                @click="$inertia.visit('/inscripciones')"
                class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors"
              >
                Volver a Inscripciones
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>