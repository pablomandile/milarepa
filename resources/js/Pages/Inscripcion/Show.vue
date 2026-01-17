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

watch(() => $page.props.flash, (flash) => {
  if (flash?.success) {
    toast.add({
      severity: 'success',
      summary: 'Inscripción',
      detail: flash.success,
      life: 5000,
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

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-4">¡Inscripción Exitosa!</h2>
            <p class="text-gray-600 mb-6">Tu inscripción ha sido registrada correctamente. Aquí están los detalles:</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-lg font-semibold mb-2">Actividad</h3>
                <p><strong>Nombre:</strong> {{ inscripcion.actividad.nombre }}</p>
                <p><strong>Fecha:</strong> {{ inscripcion.actividad.fecha_inicio_formateada }}</p>
                <p><strong>Lugar:</strong> {{ inscripcion.actividad.entidad?.direccion }}</p>
              </div>

              <div>
                <h3 class="text-lg font-semibold mb-2">Usuario</h3>
                <p><strong>Nombre:</strong> {{ inscripcion.user.name }}</p>
                <p><strong>Email:</strong> {{ inscripcion.user.email }}</p>
              </div>

              <div>
                <h3 class="text-lg font-semibold mb-2">Detalles de Pago</h3>
                <p><strong>Membresía:</strong> {{ inscripcion.membresia }}</p>
                <p><strong>Precio General:</strong> ${{ inscripcion.precioGeneral }}</p>
                <p><strong>Monto a Pagar:</strong> ${{ inscripcion.montoapagar }}</p>
                <p><strong>Estado de Pago:</strong> {{ inscripcion.pago }}</p>
              </div>

              <div>
                <h3 class="text-lg font-semibold mb-2">Estado</h3>
                <p><strong>Estado:</strong> {{ inscripcion.estado.estado }}</p>
                <p><strong>Envío Link Stream:</strong> {{ inscripcion.envioLinkStream }}</p>
                <p><strong>Envío Grabación:</strong> {{ inscripcion.envioGrabación }}</p>
                <p><strong>Asistencia:</strong> {{ inscripcion.asistencia }}</p>
                <p><strong>Online:</strong> {{ inscripcion.online ? 'Sí' : 'No' }}</p>
              </div>

              <div v-if="inscripcion.hospedaje">
                <h3 class="text-lg font-semibold mb-2">Hospedaje</h3>
                <p>{{ inscripcion.hospedaje.nombre }}</p>
              </div>

              <div v-if="inscripcion.comida">
                <h3 class="text-lg font-semibold mb-2">Comida</h3>
                <p>{{ inscripcion.comida.nombre }}</p>
              </div>

              <div v-if="inscripcion.transporte">
                <h3 class="text-lg font-semibold mb-2">Transporte</h3>
                <p>{{ inscripcion.transporte.nombre }}</p>
              </div>

              <div v-if="inscripcion.comprobante">
                <h3 class="text-lg font-semibold mb-2">Comprobante</h3>
                <p>{{ inscripcion.comprobante }}</p>
              </div>
            </div>

            <div class="mt-6">
              <button
                @click="$inertia.visit('/grid-actividades')"
                class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded"
              >
                Volver a Actividades
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>