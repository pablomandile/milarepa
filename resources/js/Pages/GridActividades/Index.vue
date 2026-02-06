<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import backgroundImage from '../../../images/7036360.svg';
import GuestUserForm from '@/Components/Formularios/GuestUserForm.vue';

const $page = usePage();

const props = defineProps({
  actividades: {
    type: Array,
    required: true,
    default: () => []
  },
  userInscripcionesActividadIds: {
    type: Array,
    required: false,
    default: () => []
  },
  paises: {
    type: Array,
    default: () => []
  },
  provincias: {
    type: Array,
    default: () => []
  },
  municipios: {
    type: Array,
    default: () => []
  },
  barrios: {
    type: Array,
    default: () => []
  }
});

const layout = ref('grid');
// Para controlar cuáles actividades están "giradas"
const flippedCards = ref({}); 
// Modal de confirmación de inscripción
const confirmModalVisible = ref(false);
const actividadAInscribir = ref(null);
const toast = useToast();
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

// Función para formatear precios con punto para miles y coma para decimales
const formatPrice = (price) => {
  if (!price || isNaN(price)) return '0,00';
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price);
};

// Mostrar toast cuando cambian los mensajes flash (éxito / error)
watch(() => $page.props.flash, (flash) => {
    if (flash?.error) {
        toast.add({
            severity: 'warn',
            summary: 'Aviso',
            detail: flash.error || 'Ya está inscripto a esa actividad!',
            life: 10000,
        });
    } else if (flash?.success) {
        toast.add({
            severity: 'success',
            summary: 'Inscripción',
            detail: flash.success,
            life: 5000,
        });
    }
}, { immediate: true });

const userContext = computed(() => {
  return userByEmail.value || $page.props?.auth?.user || null;
});
const isAuthenticated = computed(() => !!$page.props?.auth?.user);
const isAsistant = computed(() => {
  const roles = ($page.props.user?.roles || []).map((role) => String(role).toLowerCase());
  return roles.includes('asistant');
});
const inscripcionesIds = computed(() => {
  if (userByEmail.value?.inscripciones_actividad_ids) {
    return userByEmail.value.inscripciones_actividad_ids;
  }
  return props.userInscripcionesActividadIds || [];
});

// Función que alterna el estado flipped
function toggleFlip(id) {
  flippedCards.value[id] = !flippedCards.value[id];
}
// Acción al pulsar “Inscribirme”
function inscribir(actividad) {
  if (!userContext.value) {
    actividadAInscribir.value = actividad;
    if (emailInput.value && !guestForm.value.email) {
      guestForm.value.email = emailInput.value.trim();
    }
    guestModalVisible.value = true;
    return;
  }
  actividadAInscribir.value = actividad;
  iniciarPagoParaUsuario();
}
// Función para confirmar la inscripción después del modal
function confirmarInscripcion() {
  // Deprecated: flujo ahora va a pantalla de pago
}
function precioMembresiaUsuario(actividad, user) {
  if (!actividad.esquema_precio?.membresias) return 0;

  const userMemId = user?.membresia?.id; 
  // O user.membresia_id si guardas IDs directos en user.

  // Buscar en 'membresias' la pivot con 'membresia_id' = userMemId
  const pivot = actividad.esquema_precio.membresias.find(epm => epm.membresia_id === userMemId);

  // Si lo halla, retorna pivot.precio, sino 0 o "N/D"
  return pivot ? pivot.precio : 0;
}

function esInscrito(actividadId) {
  if (!userContext.value) return false;
  return inscripcionesIds.value.includes(actividadId);
}

const actividadesActivas = ref([]);

watch(() => props.actividades, (newActividades) => {
  actividadesActivas.value = newActividades.filter(a => a.estado === true || a.estado === 1);
}, { immediate: true });

function formatFechaLarga(value) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '-';
  const meses = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];
  const dia = d.getDate();
  const mes = meses[d.getMonth()];
  const anio = d.getFullYear();
  return `${dia} de ${mes} ${anio}`;
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
      localStorage.setItem('gridActividadesEmail', email);
    } else {
      lookupError.value = 'No encontramos un usuario con ese email.';
    }
  } catch (error) {
    lookupError.value = 'No se pudo validar el email. Probá de nuevo.';
  } finally {
    isLookingUp.value = false;
  }
}

onMounted(() => {
  if (!isAuthenticated.value) {
    const savedEmail = localStorage.getItem('gridActividadesEmail');
    if (savedEmail) {
      emailInput.value = savedEmail;
      buscarPorEmail();
    }
  }
});

function irMasInfo(actividad) {
  const destino = isAuthenticated.value
    ? route('actividades.show', actividad.id)
    : route('grid-actividades.show-public', actividad.id);
  router.visit(destino);
}

async function iniciarPagoParaUsuario() {
  if (!actividadAInscribir.value || !userContext.value) return;
  try {
    await axios.post(route('grid-actividades.pago.prepare'), {
      actividad_id: actividadAInscribir.value.id,
      user_id: userContext.value.id,
    });
    router.visit(route('grid-actividades.pago', actividadAInscribir.value.id));
  } catch (error) {
    const mensaje = error?.response?.data?.message || 'No se pudo iniciar el pago.';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: mensaje,
      life: 5000,
    });
  }
}

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

async function enviarInscripcionGuest() {
  if (!actividadAInscribir.value) {
    return;
  }
  guestErrors.value = {};
  isGuestSubmitting.value = true;
  try {
    const payload = {
      actividad_id: actividadAInscribir.value.id,
      guest: {
        ...guestForm.value,
      },
    };
    await axios.post(route('grid-actividades.pago.prepare'), payload);
    guestModalVisible.value = false;
    router.visit(route('grid-actividades.pago', actividadAInscribir.value.id));
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

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades del mes</h1>
        </template>
        <Toast position="top-right" />
        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div v-if="isAsistant" class="mb-4">
                            <Link
                                :href="route('asistant.panel')"
                                class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
                            >
                                Volver al menú
                            </Link>
                        </div>
                        <div v-if="!isAuthenticated" class="mb-6 rounded-lg border border-indigo-100 bg-indigo-50/40 p-4">
                            <p class="text-sm text-gray-700">
                                Si registraste tus datos y querés ver los precios con descuento podés continuar con tu email.
                            </p>
                        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input
                                v-model="emailInput"
                                type="email"
                                placeholder="tuemail@dominio.com"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-400 sm:max-w-md"
                            />
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                :disabled="isLookingUp"
                                @click="buscarPorEmail"
                            >
                                {{ isLookingUp ? 'Buscando...' : 'Continuar con mi email' }}
                            </button>
                        </div>
                        <p v-if="userByEmail?.membresia" class="mt-2 text-sm text-green-700">
                            Usando precios con descuento para {{ userByEmail.email }}.
                        </p>
                        <p v-if="lookupError" class="mt-2 text-sm text-red-600">
                            {{ lookupError }}
                        </p>
                        <div class="mt-4 text-sm text-gray-700">
                            O si prefieres iniciá sesión.
                        </div>
                            <div class="mt-2">
                                <a
                                    :href="route('login')"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
                                >
                                    Iniciar sesión
                                </a>
                            </div>
                        </div>
                    <div class="mt-4">
                        <DataView
                        :value="actividadesActivas"
                        :layout="layout"
                        paginator
                        :rows="16"
                        :rowsPerPageOptions="[8, 16, 24]"
                        class="mb-6"
                        >

                        <!-- Template grid -->
                        <template #grid="slotProps">
                            <div class="grid grid-nogutter">
                            <!-- Recorremos las actividades -->
                            <div
                                v-for="(actividad, index) in slotProps.items"
                                :key="actividad.id"
                                class="col-12 md:col-6 xl:col-6 p-2 mb-4"
                            >
                                <!-- Contenedor flip-card -->
                                <div
                                class="flip-card-container border-round shadow-sm hover:shadow-md transition-shadow"
                                :class="{ 'flipped': flippedCards[actividad.id] }"
                                style="background: linear-gradient(135deg, #f3e8ff 0%, #d8b4f8 100%);"
                                >
                                <!-- flip-card-inner envuelve front y back -->
                                <div class="flip-card-inner">

                                    <!-- FRONT: header + imagen e info breve -->
                                    <div
                                        class="flip-card-front flex flex-col h-full p-4 pb-8"
                                        :style="{
                                            backgroundImage: `linear-gradient(135deg, rgba(255, 255, 255, 0.92) 0%, rgba(255, 255, 255, 0.75) 100%), url(${backgroundImage})`,
                                            backgroundSize: 'cover',
                                            backgroundPosition: 'center',
                                            backgroundRepeat: 'no-repeat'
                                        }"
                                    >
                                        <!-- Header a lo ancho -->
                                        <div class="flip-card-header w-full mb-3">
                                            <h3 class="text-lg font-semibold leading-tight flex items-center gap-2">
                                                <i class="fa-solid fa-dharmachakra" aria-hidden="true"></i>
                                                <span v-if="actividad.tipo_actividad?.abreviacion">{{ actividad.tipo_actividad.abreviacion }} - </span>{{ actividad.nombre }}
                                            </h3>
                                        </div>
                                        <div class="flex flex-row flex-1">
                                            <!-- Imagen a la izquierda -->
                                            <div class="w-1/2 pr-3 flex items-center justify-center bg-transparent cursor-pointer rounded h-full" @click="toggleFlip(actividad.id)">
                                                <img
                                                    class="w-full h-full object-contain rounded"
                                                    :src="actividad.imagen?.ruta
                                                        ? `/storage/${actividad.imagen.ruta}`
                                                        : '/storage/img/actividades/imagen-no-disponible.jpg'"
                                                    :alt="actividad.nombre"
                                                />
                                            </div>
                                            <!-- Texto a la derecha -->
                                            <div class="w-1/2 flex flex-col justify-between pl-2">
                                                <div class="flex-1">
                                                    <p class="text-base text-gray-600 mb-1 flex items-center gap-2">
                                                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                                        <span class="sr-only">Fecha</span>
                                                        <span>
                                                            {{ formatFechaLarga(actividad.fecha_inicio) }}
                                                        </span>
                                                    </p>
                                                    <p v-if="actividad.fecha_inicio" class="text-base text-gray-600 mb-1 flex items-center gap-2">
                                                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                                        <span class="sr-only">Hora</span>
                                                        <span>{{ new Date(actividad.fecha_inicio).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) }} hs.</span>
                                                    </p>
                                                    <p class="text-base text-gray-600 mb-1 flex items-center gap-2">
                                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                                        <span class="sr-only">Lugar</span>
                                                        <span>{{ actividad.entidad?.direccion }}</span>
                                                    </p>
                                                    <p
                                                        v-if="actividad.esquema_precio?.nombre === 'Actividad Gratuita'"
                                                        class="text-sm mb-1 flex items-center gap-2 font-bold text-green-700"
                                                    >
                                                        <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                                                        <span>Actividad Gratuita</span>
                                                    </p>
                                                    <template v-else>
                                                        <p
                                                            class="text-sm mb-1 flex items-center gap-2"
                                                            :class="{
                                                                'font-bold': !userContext?.membresia || userContext?.membresia?.nombre === 'Sin membresía',
                                                                'text-gray-800 line-through': userContext?.membresia && userContext?.membresia?.nombre !== 'Sin membresía'
                                                            }"
                                                        >
                                                            <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                                                            <span class="sr-only">Valor</span>
                                                            <span v-if="parseInt(actividad.esquema_precio?.membresias?.find(epm => epm.membresia?.nombre === 'Sin membresía')?.precio) === 0" class="font-bold text-gray-700">
                                                                Incluído
                                                            </span>
                                                            <span v-else class="font-bold text-gray-700">
                                                                ${{ formatPrice(actividad.esquema_precio?.membresias?.find(epm => epm.membresia?.nombre === 'Sin membresía')?.precio || 0) }}
                                                            </span>
                                                        </p>
                                                        <p v-if="userContext?.membresia && userContext.membresia?.nombre !== 'Sin membresía'" class="text-sm text-gray-600 mb-2">
                                                            <strong>Con {{ userContext.membresia?.nombre }}:</strong>
                                                            <span v-if="parseInt(precioMembresiaUsuario(actividad, userContext)) === 0" class="font-bold text-green-700"> Incluído</span>
                                                            <span v-else class="font-bold text-green-700"> ${{ formatPrice(precioMembresiaUsuario(actividad, userContext)) }}</span>
                                                        </p>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Footer visual de la card SIEMPRE visible -->
                                        <div class="flip-card-footer w-full mt-4 p-2 flex gap-2" style="background:transparent; border-radius:6px;">
                                              <button
                                                  @click="irMasInfo(actividad)"
                                                  class="more-info-button bg-gray-500 hover:bg-gray-600 text-white py-2 px-2 rounded text-xs flex-1 transition-colors text-center flex items-center justify-center gap-1 whitespace-nowrap"
                                              >
                                                <i class="pi pi-plus"></i>
                                                <span>Más info.</span>
                                            </button>
                                            <button
                                                :disabled="esInscrito(actividad.id)"
                                                class="py-2 px-3 rounded text-sm flex-1 transition-colors flex items-center justify-center gap-1"
                                                :class="esInscrito(actividad.id) ? 'bg-gray-200 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                                @click="inscribir(actividad)"
                                            >
                                                <i v-if="esInscrito(actividad.id)" class="pi pi-heart-fill"></i>
                                                {{ esInscrito(actividad.id) ? 'Inscripto' : 'Inscribirme' }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- BACK: descripción completa -->
                                    <div class="flip-card-back p-6 flex flex-col h-full" @click="toggleFlip(actividad.id)">
                                        <h3 class="text-xl font-semibold mb-4 text-gray-800">Descripción</h3>
                                        <div class="flex-1 overflow-y-auto">
                                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">
                                                {{ actividad.descripcion?.descripcion || 'No hay descripción disponible' }}
                                            </p>
                                        </div>
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <button
                                            :disabled="esInscrito(actividad.id)"
                                            class="py-1.5 px-4 rounded w-full transition-colors flex items-center justify-center gap-2"
                                            :class="esInscrito(actividad.id) ? 'bg-gray-400 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                            @click="inscribir(actividad)"
                                            >
                                            <i v-if="esInscrito(actividad.id)" class="pi pi-heart-fill"></i>
                                            {{ esInscrito(actividad.id) ? 'Inscripto' : 'Inscribirme' }}
                                            </button>
                                        </div>
                                    </div>
                                </div> <!-- flip-card-inner -->
                                </div> <!-- flip-card-container -->
                            </div>
                            </div>
                        </template>
                        </DataView>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de inscripción para invitado -->
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

<style scoped>
/* Estilos para flip-card */
 .flip-card-container {
    perspective: 1000px;
    /* Un poco de borde redondo: */
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    height: 600px; /* Más alto para que se vea todo el contenido y los botones */
    min-height: 580px;
    height: clamp(580px, 70vh, 620px); /* Responsive: min, fluid, max */
}

.flip-card-inner {
  width: 100%;
  height: 100%;
  transition: transform 0.7s;
  transform-style: preserve-3d;
  position: relative;
}

.flip-card-container.flipped .flip-card-inner {
  transform: rotateY(180deg);
}

.flip-card-front {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    background-color: #ffffff;
    color: #374151;
}

.flip-card-front h3,
.flip-card-front p,
.flip-card-front strong {
  color: #374151;
  border-radius: 6px;
  padding: 8px 12px;
}

.flip-card-front span:not(.button-span) {
  color: #374151;
}

.more-info-button,
.more-info-button span {
  color: white !important;
}

/* front y back se apilan, uno rota 0°, el otro 180° */
.flip-card-front,
.flip-card-back {
  position: absolute;
  width: 100%;
  height: 100%;
  backface-visibility: hidden;
  top: 0;
  left: 0;
  border-radius: 8px;
}

/* la cara posterior */
.flip-card-back {
  background-color: #f8fafc; /* un fondo neutro */
  transform: rotateY(180deg);
  cursor: pointer;
}
</style>
