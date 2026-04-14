<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  userProfile: {
    type: Object,
    required: true,
  },
});

function computeAge(dateStr) {
  if (!dateStr) return 'No especificado';
  const isoPart = String(dateStr).split('T')[0];
  const parts = isoPart.split('-');
  if (parts.length < 3) return 'No especificado';
  const y = parseInt(parts[0], 10);
  const m = parseInt(parts[1], 10);
  const d = parseInt(parts[2], 10);
  if (Number.isNaN(y) || Number.isNaN(m) || Number.isNaN(d)) return 'No especificado';
  const today = new Date();
  let age = today.getFullYear() - y;
  const mm = today.getMonth() + 1;
  const dd = today.getDate();
  if (mm < m || (mm === m && dd < d)) age--;
  return age;
}
</script>

<template>
  <AppLayout title="Perfil del usuario">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Perfil de {{ userProfile?.name || 'Usuario' }}
      </h2>
    </template>

    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 flex items-center">
          <h2 class="text-2xl font-bold text-indigo-700">Datos adicionales</h2>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4 flex flex-col sm:flex-row lg:flex-col gap-3 lg:items-stretch">
          <Link
            :href="route('usuarios.profile.complete.edit', userProfile.id)"
            class="w-full"
          >
            <PrimaryButton class="w-full justify-center">
              <i class="pi pi-pencil mr-2"></i>
              Editar Datos
            </PrimaryButton>
          </Link>
          <Link
            :href="route('usuarios.index')"
            class="w-full text-center text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
          >
            Volver
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <Card class="shadow-md hover:shadow-lg transition-shadow">
          <template #header>
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-4 rounded-t-lg">
              <h3 class="text-white font-semibold flex items-center">
                <i class="pi pi-map-marker mr-2 text-xl"></i>
                Ubicación
              </h3>
            </div>
          </template>
          <template #content>
            <div class="space-y-3">
              <div class="flex items-start">
                <i class="pi pi-globe text-indigo-500 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">País</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.pais?.nombre ?? 'No especificado' }}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="pi pi-building text-indigo-500 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Provincia</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.provincia?.nombre ?? 'No especificado' }}</p>
                </div>
              </div>
              <div class="flex items-start" v-if="userProfile?.municipio">
                <i class="pi pi-map text-indigo-500 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Municipio</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.municipio?.nombre }}</p>
                </div>
              </div>
              <div class="flex items-start" v-if="userProfile?.barrio">
                <i class="pi pi-home text-indigo-500 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Barrio</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.barrio?.nombre }}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="pi pi-directions text-indigo-500 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Dirección</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.direccion ?? 'No especificado' }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="shadow-md hover:shadow-lg transition-shadow">
          <template #header>
            <div class="bg-gradient-to-r from-green-500 to-teal-600 p-4 rounded-t-lg">
              <h3 class="text-white font-semibold flex items-center">
                <i class="pi pi-phone mr-2 text-xl"></i>
                Contacto
              </h3>
            </div>
          </template>
          <template #content>
            <div class="space-y-3">
              <div class="flex items-start">
                <i class="pi pi-mobile text-green-600 mr-2 mt-1 text-lg"></i>
                <div>
                  <p class="text-xs text-gray-500">Teléfono</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.telefono ?? 'No especificado' }}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="pi pi-whatsapp text-green-600 mr-2 mt-1 text-lg"></i>
                <div>
                  <p class="text-xs text-gray-500">WhatsApp</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.whatsapp ?? 'No especificado' }}</p>
                </div>
              </div>
              <div class="bg-green-50 p-3 rounded-lg">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700 mr-3">Info por WhatsApp</span>
                  <i :class="userProfile?.msgxwapp ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-gray-400'" class="text-xl"></i>
                </div>
              </div>
              <div class="bg-blue-50 p-3 rounded-lg">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700 mr-3">Info por Email</span>
                  <i :class="userProfile?.msgxmail ? 'pi pi-check-circle text-blue-600' : 'pi pi-times-circle text-gray-400'" class="text-xl"></i>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="shadow-md hover:shadow-lg transition-shadow">
          <template #header>
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-4 rounded-t-lg">
              <h3 class="text-white font-semibold flex items-center">
                <i class="pi pi-user mr-2 text-xl"></i>
                Perfil Personal
              </h3>
            </div>
          </template>
          <template #content>
            <div class="space-y-3">
              <div class="flex items-start">
                <i class="pi pi-users text-purple-600 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Sexo</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.sexo?.sexo ?? 'No especificado' }}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="pi pi-calendar text-purple-600 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Edad</p>
                  <p class="font-semibold text-gray-800">{{ computeAge(userProfile?.fecha_nacimiento) }}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="pi pi-id-card text-purple-600 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Membresía</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.membresia?.nombre ?? userProfile?.membresia_usuario?.membresia?.nombre ?? 'No especificado' }}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="pi pi-building text-purple-600 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Asiste a</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.membresia?.entidad?.nombre ?? userProfile?.membresia_usuario?.entidad?.nombre ?? 'No especificado' }}</p>
                </div>
              </div>
              <div class="flex items-start">
                <i class="pi pi-book text-purple-600 mr-2 mt-1"></i>
                <div>
                  <p class="text-xs text-gray-500">Programa de estudio</p>
                  <p class="font-semibold text-gray-800">{{ userProfile?.programa_estudio?.nombre ?? (userProfile?.programa_estudio_id ? 'ID ' + userProfile.programa_estudio_id : 'No especificado') }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card v-if="userProfile?.accesibilidad" class="shadow-md hover:shadow-lg transition-shadow md:col-span-2 lg:col-span-3">
          <template #header>
            <div class="bg-gradient-to-r from-yellow-400 to-orange-500 p-4 rounded-t-lg">
              <h3 class="text-white font-semibold flex items-center">
                <i class="pi pi-exclamation-triangle mr-2 text-xl"></i>
                Necesidades Especiales
              </h3>
            </div>
          </template>
          <template #content>
            <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
              <div class="flex items-start">
                <i class="pi pi-info-circle text-yellow-600 mr-3 mt-1 text-2xl"></i>
                <div>
                  <p class="text-sm font-semibold text-gray-700 mb-1">Detalle de necesidad especial:</p>
                  <p class="text-gray-800">{{ userProfile?.accesibilidad_desc ?? 'No especificado' }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
