<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import backgroundImage from '../../../images/7036360.svg';
import InscripcionModoDialog from '@/Components/Dialogs/InscripcionModoDialog.vue';
import ActividadCardGrid1 from '@/Components/Actividades/ActividadCardGrid1.vue';
import ActividadCardGrid2 from '@/Components/Actividades/ActividadCardGrid2.vue';
import {
    descuentoVigente,
    direccionActividad,
} from '@/composables/useActividadHelpers';

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
  userMembresiaActiva: {
    type: Object,
    default: null,
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
  },
  gridVariante: {
    type: String,
    default: 'grid1',
  }
});

const layout = ref('grid');
// Para controlar cuáles actividades están "giradas"
const flippedCards = ref({}); 
const expandedServicios = ref({});
const actividadAInscribir = ref(null);
const toast = useToast();
const emailInput = ref('');
const lookupError = ref('');
const isLookingUp = ref(false);
const userByEmail = ref(null);
const guestModalVisible = ref(false);
const inscripcionMode = ref(null); // null | 'nuevo' | 'registrado' | 'login'
const guestErrors = ref({});
const isGuestSubmitting = ref(false);
const loginError = ref('');
const isLoggingIn = ref(false);
const loginForm = ref({
  email: '',
  password: '',
});
const mapModalVisible = ref(false);
const selectedAddress = ref('');
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
  info_tarjetas_kadampa: false,
  registrar_datos: false,
});

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
  if (userByEmail.value) return userByEmail.value;

  const authUser = $page.props?.auth?.user;
  if (!authUser) return null;

  if (!props.userMembresiaActiva) return authUser;

  return {
    ...authUser,
    membresia: props.userMembresiaActiva,
    membresia_id: props.userMembresiaActiva.id,
  };
});
const mapEmbedUrl = computed(() => {
  if (!selectedAddress.value) return '';
  return `https://maps.google.com/maps?q=${encodeURIComponent(selectedAddress.value)}&output=embed`;
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

function toggleServicios(id) {
  expandedServicios.value[id] = !expandedServicios.value[id];
}

function actividadFinalizada(actividad) {
  const valor = actividad?.fecha_fin ?? actividad?.fechaFin ?? actividad?.fecha_hasta ?? null;
  if (!valor) return false;
  const fechaFin = (typeof valor === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(valor))
    ? new Date(`${valor}T23:59:59`)
    : new Date(valor);
  if (Number.isNaN(fechaFin.getTime())) return false;
  return new Date() > fechaFin;
}

// Acción al pulsar "Inscribirme"
async function inscribir(actividad) {
  if (actividadFinalizada(actividad)) {
    toast.add({
      severity: 'warn',
      summary: 'Inscripción cerrada',
      detail: 'La fecha de esta actividad ya finalizó.',
      life: 5000,
    });
    return;
  }

  actividadAInscribir.value = actividad;

  if (isAuthenticated.value) {
    emailInput.value = $page.props?.auth?.user?.email || '';
    await continuarUsuarioRegistrado();
    return;
  }

  abrirDialogoInscripcion();
}

const actividadesActivas = ref([]);

watch(() => props.actividades, (newActividades) => {
  actividadesActivas.value = newActividades.filter(a => a.estado === true || a.estado === 1);
}, { immediate: true });

async function buscarPorEmail() {
  lookupError.value = '';
  userByEmail.value = null;

  const email = emailInput.value.trim();
  if (!email) {
    lookupError.value = 'Ingresá un email válido.';
    return null;
  }

  isLookingUp.value = true;
  try {
    const response = await axios.post(route('grid-actividades.lookup-email'), { email });
    if (response.data?.found) {
      userByEmail.value = response.data.user;
      localStorage.setItem('gridActividadesEmail', email);
      return response.data.user;
    } else {
      lookupError.value = 'No encontramos un usuario con ese email.';
      return null;
    }
  } catch (error) {
    if (error?.response?.status === 429) {
      const retryAfter = error.response.headers?.['retry-after'];
      const segundos = retryAfter ? ` (esperá ${retryAfter}s)` : '';
      lookupError.value = `Demasiados intentos${segundos}. Probá de nuevo en un minuto.`;
    } else {
      lookupError.value = 'No se pudo validar el email. Probá de nuevo.';
    }
    return null;
  } finally {
    isLookingUp.value = false;
  }
}

function abrirDialogoInscripcion() {
  guestModalVisible.value = true;
  inscripcionMode.value = null;
  lookupError.value = '';
  loginError.value = '';
  emailInput.value = userByEmail.value?.email || $page.props?.auth?.user?.email || '';
  loginForm.value = {
    email: $page.props?.auth?.user?.email || emailInput.value || '',
    password: '',
  };
}

function seleccionarModoInscripcion(mode) {
  inscripcionMode.value = mode;
  lookupError.value = '';
  loginError.value = '';

  if (mode === 'nuevo') {
    if (emailInput.value && !guestForm.value.email) {
      guestForm.value.email = emailInput.value.trim();
    }
    return;
  }

  if (mode === 'registrado') {
    emailInput.value = $page.props?.auth?.user?.email || emailInput.value || '';
    return;
  }

  if (mode === 'login') {
    loginForm.value.email = $page.props?.auth?.user?.email || emailInput.value || '';
    loginForm.value.password = '';
  }
}

async function continuarUsuarioRegistrado() {
  if (!actividadAInscribir.value) return;
  lookupError.value = '';
  const user = await buscarPorEmail();
  if (!user) return;

  await axios.post(route('grid-actividades.pago.prepare'), {
    actividad_id: actividadAInscribir.value.id,
    // Token opaco (TTL 15 min) emitido por lookup-email. Reemplaza user_id
    // numérico para que el backend no acepte ids ajenos vía IDOR.
    user_lookup_token: user.lookup_token,
  });

  guestModalVisible.value = false;
  router.visit(route('grid-actividades.pago', actividadAInscribir.value.id));
}

async function iniciarSesionYContinuar() {
  if (!actividadAInscribir.value) return;
  loginError.value = '';
  const email = loginForm.value.email.trim();
  const password = loginForm.value.password;

  if (!email) {
    loginError.value = 'Ingresá tu email.';
    return;
  }

  if (!password) {
    loginError.value = 'Ingresá tu contraseña.';
    return;
  }

  isLoggingIn.value = true;
  try {
    await axios.post('/login', {
      email,
      password,
      remember: false,
    });

    guestModalVisible.value = false;
    router.visit(route('grid-actividades.pago', actividadAInscribir.value.id));
  } catch (error) {
    const status = error?.response?.status;
    loginError.value = status === 422
      ? 'Email o contraseña inválidos.'
      : 'No se pudo iniciar sesión. Intentá nuevamente.';
  } finally {
    isLoggingIn.value = false;
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
  const destino = route('grid-actividades.show-public', actividad.id);
  router.visit(destino);
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

function abrirMapa(direccion) {
  if (!direccion) return;
  selectedAddress.value = direccion;
  mapModalVisible.value = true;
}

function cardConMuchoTexto(actividad) {
  let items = 0;
  if (actividad?.modalidad?.nombre) items++;
  if (descuentoVigente(actividad)) items++;
  if (userContext.value?.membresia && userContext.value.membresia?.nombre !== 'Sin membresía') items++;
  if (actividad?.hospedajes?.length) items++;
  if (actividad?.comidas?.length) items++;
  if (actividad?.transportes?.length) items++;
  if (actividad?.grabacion || actividad?.grabacion_id) items++;

  const direccionLarga = String(direccionActividad(actividad) || '').length > 50;
  return items >= 5 || direccionLarga;
}

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Grilla de Cursos, Retiros y Eventos Especiales</h1>
        </template>
        <Toast position="top-right" />
        <div class="py-12">
            <div class="px-0 sm:px-6 lg:px-8">
                    <div class="p-3 sm:p-6 bg-white border-b border-gray-200">
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
                            O si prefieres iniciar sesión.
                        </div>
                            <div class="mt-2">
                                <a
                                    :href="route('welcome')"
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
                                <div
                                    v-for="(actividad, index) in slotProps.items"
                                    :key="actividad.id"
                                    :class="gridVariante === 'grid2' ? 'col-12 mb-2' : 'col-12 md:col-6 xl:col-6 px-0 md:px-2 mb-4'"
                                >
                                    <ActividadCardGrid1
                                        v-if="gridVariante !== 'grid2'"
                                        :actividad="actividad"
                                        :flipped="!!flippedCards[actividad.id]"
                                        :expanded-servicios="!!expandedServicios[actividad.id]"
                                        :user-context="userContext"
                                        :inscripciones-ids="inscripcionesIds"
                                        :card-mobile-tall="cardConMuchoTexto(actividad)"
                                        :background-image="backgroundImage"
                                        @toggle-flip="toggleFlip"
                                        @toggle-servicios="toggleServicios"
                                        @inscribir="inscribir"
                                        @mas-info="irMasInfo"
                                        @abrir-mapa="abrirMapa"
                                    />
                                    <ActividadCardGrid2
                                        v-else
                                        :actividad="actividad"
                                        :flipped="!!flippedCards[actividad.id]"
                                        :user-context="userContext"
                                        :inscripciones-ids="inscripcionesIds"
                                        :image-side="index % 2 === 0 ? 'right' : 'left'"
                                        @toggle-flip="toggleFlip"
                                        @inscribir="inscribir"
                                        @mas-info="irMasInfo"
                                        @abrir-mapa="abrirMapa"
                                    />
                                </div>
                            </div>
                        </template>
                        </DataView>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de inscripción para invitado -->
        <InscripcionModoDialog
            v-model="guestModalVisible"
            :mode="inscripcionMode"
            :highlight-all-when-mode-empty="true"
            :loading-nuevo="isGuestSubmitting"
            :loading-registrado="isLookingUp"
            :loading-login="isLoggingIn"
            :guest-form="guestForm"
            :guest-errors="guestErrors"
            :paises="paises"
            :provincias="provincias"
            :municipios="municipios"
            :barrios="barrios"
            :email="emailInput"
            :registered-error="lookupError"
            :login-email="loginForm.email"
            :login-password="loginForm.password"
            :login-error="loginError"
            registered-input-id="email-registrado-grid-index"
            login-email-id="login-email-grid-index"
            login-password-id="login-password-grid-index"
            @update:email="emailInput = $event"
            @update:login-email="loginForm.email = $event"
            @update:login-password="loginForm.password = $event"
            @select-mode="seleccionarModoInscripcion"
            @submit-nuevo="enviarInscripcionGuest"
            @submit-registrado="continuarUsuarioRegistrado"
            @submit-login="iniciarSesionYContinuar"
        />

        <Dialog
            v-model:visible="mapModalVisible"
            modal
            header="Ubicacion"
            :style="{ width: '800px' }"
        >
            <div v-if="selectedAddress" class="space-y-3">
                <p class="text-sm text-gray-700">{{ selectedAddress }}</p>
                <iframe
                    :src="mapEmbedUrl"
                    class="w-full h-[60vh] rounded border border-gray-200"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>
        </Dialog>
    </AppLayout>
</template>

