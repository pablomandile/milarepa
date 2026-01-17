<script>
export default {
  name: 'ActividadesShow'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const $page = usePage();

const { actividad } = defineProps({
  actividad: {
    type: Object,
    required: true,
  },
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
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ actividad.nombre }}
      </h1>
    </template>

    <Toast position="top-right" />

    <div class="py-12">
      <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
              <img
                v-if="actividad.imagen"
                :src="'/storage/' + actividad.imagen.ruta"
                :alt="'Imagen de ' + actividad.nombre"
                class="w-full h-auto rounded"
              />
              <img
                v-else
                src="/storage/img/actividades/imagen-no-disponible.jpg"
                alt="Sin imagen"
                class="w-full h-auto rounded"
              />
            </div>

            <div class="md:col-span-2 space-y-3">
              <p class="text-gray-600" v-if="actividad.fecha_inicio_formateada">
                <span class="font-medium">Fecha:</span>
                {{ actividad.fecha_inicio_formateada }}
              </p>

              <p class="text-gray-600" v-if="actividad.tipo_actividad">
                <span class="font-medium">Tipo:</span>
                {{ actividad.tipo_actividad.nombre }}
              </p>

              <p class="text-gray-600" v-if="actividad.modalidad">
                <span class="font-medium">Modalidad:</span>
                {{ actividad.modalidad.nombre }}
              </p>

              <p class="text-gray-600" v-if="actividad.entidad">
                <span class="font-medium">Lugar:</span>
                {{ actividad.entidad.abreviacion }}
                <span v-if="actividad.entidad.direccion && actividad.entidad.direccion.trim() !== ''">
                  — {{ actividad.entidad.direccion }}
                </span>
              </p>

              <div v-if="actividad.descripcion && actividad.descripcion.descripcion" class="prose max-w-none">
                <h2 class="text-lg font-semibold">Descripción</h2>
                <p class="mt-2 text-gray-700">{{ actividad.descripcion.descripcion }}</p>
              </div>

              <div v-if="actividad.programa" class="mt-4">
                <h2 class="text-lg font-semibold">Programa</h2>
                <p class="mt-2 text-gray-700">{{ actividad.programa.nombre }}</p>
              </div>

              <div v-if="actividad.stream" class="mt-4">
                <h2 class="text-lg font-semibold">Stream</h2>
                <p class="mt-2 text-gray-700">{{ actividad.stream.titulo || 'Disponible' }}</p>
              </div>

              <div v-if="actividad.grabacion" class="mt-4">
                <h2 class="text-lg font-semibold">Grabación</h2>
                <p class="mt-2 text-gray-700">{{ actividad.grabacion.titulo || 'Disponible' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
