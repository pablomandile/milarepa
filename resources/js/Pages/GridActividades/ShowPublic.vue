<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import GuestUserForm from '@/Components/Formularios/GuestUserForm.vue';

const props = defineProps({
  actividad: {
    type: Object,
    required: true,
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
  userInscripcionesActividadIds: {
    type: Array,
    default: () => [],
  },
  returnUrl: {
    type: String,
    default: null,
  },
});

const page = usePage();
const toast = useToast();

const showFullDescription = ref(false);
const fullDescription = computed(() => props.actividad.descripcion?.descripcion || 'No hay descripción disponible');

const hasMoreDescription = computed(() => {
  let count = 0;
  for (const ch of fullDescription.value) {
    if (ch === '.') count++;
    if (count > 3) return true;
  }
  return false;
});

const truncatedDescription = computed(() => {
  if (showFullDescription.value) return fullDescription.value;
  const text = fullDescription.value;
  let count = 0;
  for (let i = 0; i < text.length; i++) {
    if (text[i] === '.') {
      count++;
      if (count === 3) return text.slice(0, i + 1);
    }
  }
  return text;
});

const formatPrice = (price) => {
  if (!price || Number.isNaN(price)) return '0,00';
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
};

const precioGeneral = computed(() => {
  return (
    props.actividad.esquema_precio?.membresias?.find(
      (epm) => epm.membresia?.nombre === 'Sin membresía'
    )?.precio || 0
  );
});

const precioMembresia = computed(() => {
  const membresiaId = userByEmail.value?.membresia?.id;
  if (!membresiaId) return null;
  const pivot = props.actividad.esquema_precio?.membresias?.find(
    (epm) => epm.membresia_id === membresiaId
  );
  return pivot?.precio ?? null;
});

const mostrarDescuento = computed(() => {
  return !!userByEmail.value?.membresia && precioMembresia.value !== null;
});

const emailInput = ref('');
const lookupError = ref('');
const isLookingUp = ref(false);
const userByEmail = ref(null);
const guestModalVisible = ref(false);
const guestErrors = ref({});
const isGuestSubmitting = ref(false);
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
  registrar_datos: false,
});

const isAuthenticated = computed(() => !!page.props?.auth?.user);
const userContext = computed(() => userByEmail.value || page.props?.auth?.user || null);

const inscripcionesIds = computed(() => {
  if (userByEmail.value?.inscripciones_actividad_ids) {
    return userByEmail.value.inscripciones_actividad_ids;
  }
  return props.userInscripcionesActividadIds || [];
});

const esInscrito = computed(() => inscripcionesIds.value.includes(props.actividad.id));
const backUrl = computed(() => props.returnUrl || route('grid-actividades.index'));

function resetGuestForm() {
  guestForm.value = {
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
    registrar_datos: false,
  };
  guestErrors.value = {};
}

async function buscarPorEmail() {
  lookupError.value = '';
  userByEmail.value = null;
  const email = emailInput.value.trim();
  if (!email) {
    lookupError.value = 'Ingresá un email válido.';
    return;
  }
  isLookingUp.value = true;
  try {
    const response = await axios.post(route('grid-actividades.lookup-email'), { email });
    if (response.data?.found) {
      userByEmail.value = response.data.user;
    } else {
      lookupError.value = 'No encontramos un usuario con ese email.';
    }
  } catch (error) {
    lookupError.value = 'No se pudo validar el email. Probá de nuevo.';
  } finally {
    isLookingUp.value = false;
  }
}


async function iniciarPagoParaUsuario() {
  if (!userContext.value) return;
  await axios.post(route('grid-actividades.pago.prepare'), {
    actividad_id: props.actividad.id,
    user_id: userContext.value.id,
  });
  router.visit(route('grid-actividades.pago', props.actividad.id));
}

function inscribir() {
  if (!userContext.value) {
    if (emailInput.value && !guestForm.value.email) {
      guestForm.value.email = emailInput.value.trim();
    }
    guestModalVisible.value = true;
    return;
  }
  iniciarPagoParaUsuario();
}

async function enviarInscripcionGuest() {
  guestErrors.value = {};
  isGuestSubmitting.value = true;
  try {
    const payload = {
      actividad_id: props.actividad.id,
      guest: {
        ...guestForm.value,
      },
    };
    await axios.post(route('grid-actividades.pago.prepare'), payload);
    guestModalVisible.value = false;
    router.visit(route('grid-actividades.pago', props.actividad.id));
  } catch (error) {
    guestErrors.value = error?.response?.data?.errors || {};
    const mensaje = error?.response?.data?.message || 'No se pudo procesar la inscripción.';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: mensaje,
      life: 5000,
    });
  } finally {
    isGuestSubmitting.value = false;
  }
}

onMounted(() => {
  localStorage.removeItem('gridActividadesEmail');
});

</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividad</h1>
    </template>

    <div class="py-12">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8" style="background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);">
          <div class="p-8">
            <div class="w-full max-w-md mx-auto mb-6 rounded-lg overflow-hidden bg-transparent">
              <img
                v-if="actividad?.imagen"
                :src="'/storage/' + actividad.imagen.ruta"
                :alt="'Imagen de ' + actividad.nombre"
                class="w-full h-78 object-contain"
              />
              <img
                v-else
                src="/storage/img/actividades/imagen-no-disponible.jpg"
                alt="Sin imagen"
                class="w-full h-64 object-contain"
              />
            </div>

            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4 text-center">
              {{ actividad.nombre }}
            </h1>

            <div class="bg-white bg-opacity-90 rounded-lg p-5 mb-6 border border-indigo-50">
              <h2 class="text-lg font-semibold text-gray-900 mb-2">Descripción</h2>
              <p class="text-gray-700 leading-relaxed text-base whitespace-pre-line">
                {{ truncatedDescription }}
              </p>
              <div v-if="hasMoreDescription" class="mt-3 text-right">
                <button
                  class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm"
                  @click="showFullDescription = !showFullDescription"
                >
                  {{ showFullDescription ? 'Ver menos' : 'Ver más' }}
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
              <div class="bg-white rounded-lg shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-calendar text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Fecha y Hora</h3>
                  <p class="text-gray-700 text-base">{{ actividad.fecha_inicio_formateada || 'No especificado' }}</p>
                </div>
              </div>

              <div class="bg-white rounded-lg shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-map-marker text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Lugar</h3>
                  <p class="text-gray-700 text-base">{{ actividad.entidad?.direccion || 'No especificado' }}</p>
                </div>
              </div>

              <div class="bg-white rounded-lg shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-video text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Modalidad</h3>
                  <p class="text-gray-700 text-base">{{ actividad.modalidad?.nombre || 'No especificado' }}</p>
                </div>
              </div>

              <div class="bg-white rounded-lg shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-user text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Maestro</h3>
                  <p class="text-gray-700 text-base">
                    <span v-if="actividad.maestros && actividad.maestros.length > 0">
                      {{ actividad.maestros.map((m) => m.nombre).join(', ') }}
                    </span>
                    <span v-else>No especificado</span>
                  </p>
                </div>
              </div>

              <div class="bg-white rounded-lg shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-dollar text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Valor general</h3>
                  <p class="text-gray-700 text-base">
                    <strong :class="mostrarDescuento ? 'line-through text-gray-400' : ''">
                      $ {{ formatPrice(precioGeneral) }}
                    </strong>
                  </p>
                  <p v-if="!mostrarDescuento" class="text-xs text-gray-500 mt-1">
                    Iniciá sesión para ver precios con membresía.
                  </p>
                </div>
              </div>

              <div v-if="mostrarDescuento" class="bg-white rounded-lg shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-star-fill text-amber-500 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Membresía</h3>
                  <p class="text-gray-700 text-base">
                    <strong>{{ userByEmail.membresia?.nombre }}</strong>
                  </p>
                  <p class="text-emerald-700 text-base mt-1">
                    Valor con membresía: <strong>$ {{ formatPrice(precioMembresia) }}</strong>
                  </p>
                </div>
              </div>
            </div>

            <div v-if="!isAuthenticated && (userByEmail || emailInput)" class="mb-6 rounded-lg border border-emerald-100 bg-emerald-50/60 p-4">
              <p v-if="userByEmail?.membresia" class="text-sm text-emerald-700">
                Usando precios con descuento para {{ userByEmail.email }}.
              </p>
              <p v-else class="text-sm text-gray-700">
                Continuando con {{ emailInput }}.
              </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
              <Link
                :href="backUrl"
                class="flex-1 px-4 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors text-center font-semibold"
              >
                Volver
              </Link>
              <button
                :disabled="esInscrito"
                @click="inscribir"
                class="flex-1 px-4 py-3 rounded-lg transition-colors font-semibold flex items-center justify-center gap-2"
                :class="esInscrito ? 'bg-gray-200 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
              >
                <i v-if="esInscrito" class="pi pi-heart-fill"></i>
                {{ esInscrito ? 'Inscripto' : 'Inscribirme' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="guestModalVisible"
      modal
      header="Completa tus datos para inscribirte"
      :style="{ width: '900px' }"
      :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
    >
      <div class="max-h-[70vh] overflow-y-auto pr-2">
        <GuestUserForm
          :form="guestForm"
          :errors="guestErrors"
          :paises="paises"
          :provincias="provincias"
          :municipios="municipios"
          :barrios="barrios"
        />
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button
            @click="guestModalVisible = false"
            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
          >
            Cancelar
          </button>
          <button
            @click="enviarInscripcionGuest"
            :disabled="isGuestSubmitting"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700 transition-colors disabled:opacity-60"
          >
            {{ isGuestSubmitting ? 'Enviando...' : 'Guardar e Inscribirme' }}
          </button>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>
