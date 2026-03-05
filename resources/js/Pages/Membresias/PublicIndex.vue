<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import Swal from 'sweetalert2';
import GuestUserForm from '@/Components/Formularios/GuestUserForm.vue';

const page = usePage();

const props = defineProps({
  membresias: {
    type: Object,
    required: true,
  },
  user_membresia: {
    type: Object,
    default: null,
  },
  selected_user_id: {
    type: Number,
    default: null,
  },
  paises: {
    type: Array,
    default: () => [],
  },
  provincias: {
    type: Array,
    default: () => [],
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  barrios: {
    type: Array,
    default: () => [],
  },
});

const isAuthenticated = computed(() => !!page.props?.auth?.user);
const currentUser = computed(() => page.props?.auth?.user || null);
const selectedUserId = computed(() => currentUser.value?.id || props.selected_user_id || null);

const layout = ref('grid');
const showDialog = ref(false);
const isSubmitting = ref(false);
const membresiaPendiente = ref(null);
const modalidad = ref('PRESENCIAL');
const motivoOnline = ref('');
const guestErrors = ref({});

const guestForm = ref({
  name: '',
  email: '',
  telefono: '',
  whatsapp: '',
  pais_id: '',
  provincia_id: '',
  municipio_id: '',
  barrio_id: '',
  direccion: '',
  msgxmail: false,
  msgxwapp: false,
  accesibilidad: false,
  accesibilidad_desc: '',
  registrar_datos: true,
});

function resetDialogState() {
  modalidad.value = 'PRESENCIAL';
  motivoOnline.value = '';
  guestErrors.value = {};
}

function openSubscribeDialog(membresia) {
  if (props.user_membresia && props.user_membresia.id === membresia.id) {
    Swal.fire('Informacion', 'Ya tienes esta membresia activa.', 'info');
    return;
  }

  membresiaPendiente.value = membresia;
  resetDialogState();
  showDialog.value = true;
}

function isDisabledButton(membresia) {
  return props.user_membresia && props.user_membresia.id === membresia.id;
}

async function confirmarSuscripcion() {
  if (!membresiaPendiente.value) return;

  isSubmitting.value = true;
  guestErrors.value = {};

  try {
    const payload = {
      membresia_id: membresiaPendiente.value.id,
      modalidad: modalidad.value,
      motivo_online: modalidad.value === 'ONLINE' ? motivoOnline.value : null,
    };

    if (selectedUserId.value) {
      payload.user_id = selectedUserId.value;
    } else {
      payload.guest = { ...guestForm.value };
    }

    const response = await axios.post(route('membresias.public.subscribe'), payload);
    const newUserId = response?.data?.user_id || selectedUserId.value;

    showDialog.value = false;
    Swal.fire('Exito', 'Te has inscrito correctamente a la membresia.', 'success');

    const destino = newUserId
      ? `${route('membresias.public.index')}?user_id=${encodeURIComponent(newUserId)}`
      : route('membresias.public.index');
    router.visit(destino);
  } catch (error) {
    guestErrors.value = error?.response?.data?.errors || {};
    const mensaje = error?.response?.data?.message || 'No se pudo completar la suscripcion.';
    Swal.fire('Error', mensaje, 'error');
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <AppLayout>
    <div class="py-12">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="bg-white border-round shadow-1">
          <div class="p-6 border-bottom-1 border-200">
            <div class="flex justify-content-between align-items-center mb-4">
              <div>
                <h2 class="text-2xl font-bold text-900 m-0">Membresias Disponibles</h2>
                <p v-if="user_membresia" class="text-base text-600 mt-2">
                  <span class="font-semibold">Tu membresia actual: </span>
                  <span class="font-bold text-green-600">{{ user_membresia.nombre }}</span>
                </p>
                <p v-else class="text-base text-600 mt-2">
                  No tienes una membresia activa. Elige una para suscribirte.
                </p>
              </div>
              <div class="flex gap-2">
                <button
                  @click="layout = 'grid'"
                  :class="['p-button p-button-text p-button-rounded', layout === 'grid' ? 'p-button-primary' : 'p-button-secondary']"
                >
                  <i class="pi pi-th-large"></i>
                </button>
                <button
                  @click="layout = 'list'"
                  :class="['p-button p-button-text p-button-rounded', layout === 'list' ? 'p-button-primary' : 'p-button-secondary']"
                >
                  <i class="pi pi-list"></i>
                </button>
              </div>
            </div>

            <DataView
              :value="membresias.data"
              :layout="layout"
              paginator
              :rows="9"
              :rowsPerPageOptions="[3, 6, 9]"
              class="mb-6"
            >
              <template #grid="slotProps">
                <div class="grid grid-nogutter">
                  <div
                    v-for="membresia in slotProps.items"
                    :key="membresia.id"
                    class="col-12 md:col-6 xl:col-4 p-2"
                  >
                    <div class="p-card bg-white border-1 surface-border border-round shadow-2 hover:shadow-4 transition-all transition-duration-300">
                      <div class="p-card-body p-4">
                        <div class="flex flex-col h-full">
                          <div class="flex align-items-center mb-3">
                            <div class="bg-primary-100 border-circle w-3rem h-3rem flex align-items-center justify-content-center mr-3">
                              <i class="pi pi-heart text-primary text-xl"></i>
                            </div>
                            <div class="flex-1">
                              <h3 class="p-card-title text-lg font-bold text-900 m-0">
                                {{ membresia.nombre }}
                              </h3>
                            </div>
                          </div>
                          <p class="p-card-subtitle text-sm text-600 mb-3 flex-1">
                            {{ membresia.descripcion || 'Sin descripcion disponible' }}
                          </p>
                          <div class="flex align-items-center mb-4">
                            <i class="pi pi-building text-400 mr-2"></i>
                            <span class="text-sm text-500"><strong>{{ membresia.entidad?.nombre || 'No especificada' }}</strong></span>
                          </div>
                          <div class="mt-auto">
                            <button
                              @click="openSubscribeDialog(membresia)"
                              :disabled="isDisabledButton(membresia)"
                              :class="[
                                'w-full py-2 px-4 rounded-md font-medium transition-colors flex items-center justify-center gap-2',
                                isDisabledButton(membresia)
                                  ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                  : 'bg-indigo-600 text-white hover:bg-indigo-700'
                              ]"
                            >
                              <i class="pi pi-plus-circle"></i>
                              {{ isDisabledButton(membresia) ? 'Mi membresia actual' : 'Inscribirme' }}
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              <template #list="slotProps">
                <div class="grid grid-cols-1 gap-4">
                  <div
                    v-for="membresia in slotProps.items"
                    :key="membresia.id"
                    class="col-12 p-card bg-white border-1 surface-border border-round shadow-2 hover:shadow-4 transition-all transition-duration-300"
                  >
                    <div class="p-card-body p-4">
                      <div class="flex flex-col md:flex-row md:align-items-center md:justify-between">
                        <div class="flex-1">
                          <div class="flex align-items-center mb-3">
                            <div class="bg-primary-100 border-circle w-3rem h-3rem flex align-items-center justify-content-center mr-3">
                              <i class="pi pi-heart text-primary text-xl"></i>
                            </div>
                            <div>
                              <h3 class="p-card-title text-lg font-bold text-900 m-0">{{ membresia.nombre }}</h3>
                              <div class="flex align-items-center mt-1">
                                <i class="pi pi-building text-400 mr-2"></i>
                                <span class="text-sm text-500">{{ membresia.entidad?.nombre || 'No especificada' }}</span>
                              </div>
                            </div>
                          </div>
                          <p class="p-card-subtitle text-sm text-600">{{ membresia.descripcion || 'Sin descripcion disponible' }}</p>
                        </div>
                        <div class="mt-4 md:mt-0 md:ml-4">
                          <button
                            @click="openSubscribeDialog(membresia)"
                            :disabled="isDisabledButton(membresia)"
                            :class="[
                              'py-2 px-4 rounded-md font-medium transition-colors flex items-center justify-center gap-2',
                              isDisabledButton(membresia)
                                ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                : 'bg-indigo-600 text-white hover:bg-indigo-700'
                            ]"
                          >
                            <i class="pi pi-plus-circle"></i>
                            {{ isDisabledButton(membresia) ? 'Mi membresia actual' : 'Inscribirme' }}
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </DataView>

            <div class="mt-4">
              <Link
                :href="route('welcome')"
                class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
              >
                Volver
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="showDialog"
      modal
      :header="membresiaPendiente ? membresiaPendiente.nombre : 'Membresia'"
      :style="{ width: '56rem' }"
      :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
    >
      <template v-if="membresiaPendiente">
        <div class="flex flex-col gap-4">
          <div>
            <p class="text-sm font-semibold text-gray-800 mb-2">Modalidad</p>
            <div class="flex items-center gap-4">
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" value="PRESENCIAL" v-model="modalidad" />
                PRESENCIAL
              </label>
              <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="radio" value="ONLINE" v-model="modalidad" />
                ONLINE
              </label>
            </div>
          </div>

          <div v-if="modalidad === 'ONLINE'">
            <label class="block text-sm font-semibold text-gray-800 mb-2">
              Motivo de modalidad online
            </label>
            <input
              v-model="motivoOnline"
              type="text"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
              placeholder="Escribe tu motivo"
            />
          </div>

          <div v-if="!isAuthenticated" class="border-t border-gray-200 pt-4">
            <GuestUserForm
              :form="guestForm"
              :errors="guestErrors"
              :paises="paises"
              :provincias="provincias"
              :municipios="municipios"
              :barrios="barrios"
              :mostrarRegistrarDatos="false"
              :forzarRegistrarDatos="true"
            />
          </div>
        </div>
      </template>

      <template #footer>
        <div class="flex justify-end space-x-2">
          <button
            @click="showDialog = false"
            class="py-2 px-4 rounded-md font-medium transition-colors bg-gray-500 text-white hover:bg-gray-600"
          >
            Cancelar
          </button>
          <button
            @click="confirmarSuscripcion"
            :disabled="isSubmitting"
            class="py-2 px-4 rounded-md font-medium transition-colors bg-green-600 text-white hover:bg-green-700 disabled:opacity-60"
          >
            {{ isSubmitting ? 'Enviando...' : 'Terminar' }}
          </button>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
.p-card {
  transition: all 0.3s ease;
}

.p-card:hover {
  transform: translateY(-2px);
}

.p-card-body {
  height: 100%;
  display: flex;
  flex-direction: column;
}
</style>
