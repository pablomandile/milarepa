<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import Swal from 'sweetalert2';
import InscripcionModoDialog from '@/Components/Dialogs/InscripcionModoDialog.vue';

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
const showPagoDialog = ref(false);
const isSubmitting = ref(false);
const isFinishing = ref(false);
const membresiaPendiente = ref(null);
const modalidad = ref('PRESENCIAL');
const motivoOnline = ref('');
const preinscripcionPayload = ref(null);
const comprobanteFile = ref(null);
const pagoEfectivo = ref(false);
const showNoMercadoPagoInfo = ref(false);
const guestErrors = ref({});
const inscripcionMode = ref('nuevo'); // 'nuevo' | 'registrado' | 'login'
const emailInput = ref('');
const lookupError = ref('');
const isLookingUp = ref(false);
const userByEmail = ref(null);
const loginError = ref('');
const isLoggingIn = ref(false);
const loginForm = ref({
  email: '',
  password: '',
});
const flippedCards = ref({});
const fallbackMembresiaImage = '/storage/img/actividades/imagen-no-disponible.jpg';

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
  registrar_datos: true,
});

const membresiaSeleccionadaValor = computed(() => Number(membresiaPendiente.value?.valor || 0));
const requiereComprobante = computed(() => membresiaSeleccionadaValor.value > 0);
const botonPagoMembresia = computed(() => membresiaPendiente.value?.boton_pago || membresiaPendiente.value?.botonPago || null);
const botonPagoLink = computed(() => botonPagoMembresia.value?.link || '');
const metodoPagoMembresia = computed(() => botonPagoMembresia.value?.metodo_pago || botonPagoMembresia.value?.metodoPago || null);
const metodoPagoNombre = computed(() => metodoPagoMembresia.value?.nombre || 'Boton de pago');
const imagenMetodoPagoRuta = computed(() => metodoPagoMembresia.value?.imagen?.ruta || '');
const metodosPagoAlternativos = computed(() => membresiaPendiente.value?.metodos_pago_alternativos || {});
const descripcionEfectivo = computed(() => metodosPagoAlternativos.value?.efectivo?.descripcion || '');
const descripcionTransferencia = computed(() => metodosPagoAlternativos.value?.transferencia?.descripcion || '');
const botonGetnetAlternativo = computed(() => membresiaPendiente.value?.boton_getnet || null);
const puedeFinalizar = computed(() => {
  if (!preinscripcionPayload.value) return false;
  if (!requiereComprobante.value) return true;
  return pagoEfectivo.value || !!comprobanteFile.value;
});

const formatCurrency = (amount) => new Intl.NumberFormat('es-AR', {
  style: 'currency',
  currency: 'ARS',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
}).format(Number(amount || 0));

const resolveStorageImageUrl = (ruta) => {
  const value = (ruta || '').toString().trim();
  if (!value) return '';
  if (/^https?:\/\//i.test(value) || value.startsWith('data:image/')) return value;
  if (value.startsWith('/storage/')) return value;
  if (value.startsWith('storage/')) return `/${value}`;
  if (value.startsWith('img/')) return `/storage/${value}`;
  return `/storage/${value.replace(/^\/+/, '')}`;
};

const imagenMetodoPago = computed(() => resolveStorageImageUrl(imagenMetodoPagoRuta.value));

const imagenMembresia = (membresia) => {
  if (membresia?.imagen?.ruta) {
    return `/storage/${membresia.imagen.ruta}`;
  }
  return fallbackMembresiaImage;
};

const toggleFlip = (id) => {
  flippedCards.value[id] = !flippedCards.value[id];
};

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatInlineMarkdown(value) {
  return value
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>');
}

function renderInfoMarkdown(value) {
  const safeText = escapeHtml(value).replace(/\r\n/g, '\n');
  const lines = safeText.split('\n');
  const html = [];
  let inList = false;

  for (const rawLine of lines) {
    const line = rawLine.trim();

    if (!line) {
      if (inList) {
        html.push('</ul>');
        inList = false;
      }
      continue;
    }

    if (line.startsWith('- ')) {
      if (!inList) {
        html.push('<ul class="list-disc pl-5 my-2 space-y-1">');
        inList = true;
      }
      html.push(`<li>${formatInlineMarkdown(line.slice(2).trim())}</li>`);
      continue;
    }

    if (inList) {
      html.push('</ul>');
      inList = false;
    }

    if (line.startsWith('### ')) {
      html.push(`<h5 class="text-sm font-semibold text-gray-900 mt-2">${formatInlineMarkdown(line.slice(4).trim())}</h5>`);
    } else if (line.startsWith('## ')) {
      html.push(`<h4 class="text-base font-semibold text-gray-900 mt-2">${formatInlineMarkdown(line.slice(3).trim())}</h4>`);
    } else if (line.startsWith('# ')) {
      html.push(`<h3 class="text-lg font-bold text-gray-900 mt-2">${formatInlineMarkdown(line.slice(2).trim())}</h3>`);
    } else {
      html.push(`<p class="text-xs text-gray-700 leading-relaxed mt-1">${formatInlineMarkdown(line)}</p>`);
    }
  }

  if (inList) {
    html.push('</ul>');
  }

  return html.join('');
}

function resetDialogState() {
  modalidad.value = 'PRESENCIAL';
  motivoOnline.value = '';
  guestErrors.value = {};
  preinscripcionPayload.value = null;
  comprobanteFile.value = null;
  pagoEfectivo.value = false;
  showNoMercadoPagoInfo.value = false;
  lookupError.value = '';
  userByEmail.value = null;
  loginError.value = '';
  showPagoDialog.value = false;
  inscripcionMode.value = isAuthenticated.value ? 'registrado' : 'nuevo';
  emailInput.value = currentUser.value?.email || '';
  loginForm.value = {
    email: currentUser.value?.email || '',
    password: '',
  };
}

function openSubscribeDialog(membresia) {
  if (props.user_membresia && props.user_membresia.id === membresia.id) {
    Swal.fire('Informacion', 'Ya tienes esta membresia activa.', 'info');
    return;
  }

  const membresiaCompleta = props.membresias?.data?.find((item) => item.id === membresia.id) || membresia;
  membresiaPendiente.value = membresiaCompleta;
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
  lookupError.value = '';
  loginError.value = '';

  try {
    const payload = {
      membresia_id: membresiaPendiente.value.id,
      modalidad: modalidad.value,
      motivo_online: modalidad.value === 'ONLINE' ? motivoOnline.value : null,
    };

    if (inscripcionMode.value === 'nuevo') {
      payload.guest = { ...guestForm.value };
    } else if (inscripcionMode.value === 'registrado') {
      const user = await obtenerUsuarioPorModoRegistrado();
      if (!user) return;
      payload.user_id = user.id;
    } else if (inscripcionMode.value === 'login') {
      const loginOk = await iniciarSesionModoLogin();
      if (!loginOk) return;
      emailInput.value = loginForm.value.email.trim();
      const user = await obtenerUsuarioPorModoRegistrado();
      if (!user) return;
      payload.user_id = user.id;
    } else if (selectedUserId.value) {
      payload.user_id = selectedUserId.value;
    } else {
      payload.guest = { ...guestForm.value };
    }

    showDialog.value = false;
    preinscripcionPayload.value = payload;
    showPagoDialog.value = true;
  } catch (error) {
    guestErrors.value = error?.response?.data?.errors || {};
    const mensaje = error?.response?.data?.message || 'No se pudo completar la suscripcion.';
    Swal.fire('Error', mensaje, 'error');
  } finally {
    isSubmitting.value = false;
  }
}

function seleccionarComprobante(event) {
  comprobanteFile.value = event.target.files?.[0] || null;
}

function construirFormDataSuscripcion() {
  const payload = preinscripcionPayload.value || {};
  const data = new FormData();
  data.append('membresia_id', payload.membresia_id);
  data.append('modalidad', payload.modalidad || 'PRESENCIAL');

  if (payload.motivo_online) {
    data.append('motivo_online', payload.motivo_online);
  }

  if (payload.user_id) {
    data.append('user_id', payload.user_id);
  }

  if (payload.guest && typeof payload.guest === 'object') {
    Object.entries(payload.guest).forEach(([key, value]) => {
      if (value === null || value === undefined) return;
      data.append(`guest[${key}]`, typeof value === 'boolean' ? (value ? '1' : '0') : value);
    });
  }

  data.append('modo_pago', pagoEfectivo.value ? 'Efectivo' : 'Transferencia');
  if (comprobanteFile.value) {
    data.append('comprobante', comprobanteFile.value);
  }

  return data;
}

async function terminarSuscripcion() {
  if (!preinscripcionPayload.value || !membresiaPendiente.value) return;

  if (!puedeFinalizar.value) {
    Swal.fire('Pago', 'Subi un comprobante o marca que pagaste en efectivo.', 'info');
    return;
  }

  isFinishing.value = true;
  try {
    const data = construirFormDataSuscripcion();
    const response = await axios.post(route('membresias.public.subscribe'), data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    const newUserId = response?.data?.user_id || selectedUserId.value;

    showPagoDialog.value = false;
    showDialog.value = false;
    Swal.fire('Exito', 'Te has inscrito correctamente a la membresia.', 'success');

    const destino = newUserId
      ? `${route('membresias.public.index')}?user_id=${encodeURIComponent(newUserId)}`
      : route('membresias.public.index');
    router.visit(destino);
  } catch (error) {
    const mensaje = error?.response?.data?.message || 'No se pudo completar la suscripcion.';
    Swal.fire('Error', mensaje, 'error');
  } finally {
    isFinishing.value = false;
  }
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
    emailInput.value = currentUser.value?.email || emailInput.value || '';
    return;
  }

  if (mode === 'login') {
    loginForm.value.email = currentUser.value?.email || emailInput.value || '';
    loginForm.value.password = '';
  }
}

async function buscarUsuarioPorEmail(email) {
  lookupError.value = '';
  userByEmail.value = null;

  const emailNormalizado = (email || '').trim();
  if (!emailNormalizado) {
    lookupError.value = 'Ingresá un email válido.';
    return null;
  }

  isLookingUp.value = true;
  try {
    const response = await axios.post(route('grid-actividades.lookup-email'), { email: emailNormalizado });
    if (response.data?.found) {
      userByEmail.value = response.data.user;
      return response.data.user;
    }

    lookupError.value = 'No encontramos un usuario con ese email.';
    return null;
  } catch (error) {
    lookupError.value = 'No se pudo validar el email. Probá de nuevo.';
    return null;
  } finally {
    isLookingUp.value = false;
  }
}

async function obtenerUsuarioPorModoRegistrado() {
  if (selectedUserId.value && !emailInput.value.trim()) {
    return { id: selectedUserId.value };
  }
  return buscarUsuarioPorEmail(emailInput.value);
}

async function iniciarSesionModoLogin() {
  loginError.value = '';

  const email = loginForm.value.email.trim();
  const password = loginForm.value.password;

  if (!email) {
    loginError.value = 'Ingresá tu email.';
    return false;
  }

  if (!password) {
    loginError.value = 'Ingresá tu contraseña.';
    return false;
  }

  isLoggingIn.value = true;
  try {
    await axios.post('/login', {
      email,
      password,
      remember: false,
    });
    return true;
  } catch (error) {
    const status = error?.response?.status;
    loginError.value = status === 422
      ? 'Email o contraseña inválidos.'
      : 'No se pudo iniciar sesión. Intentá nuevamente.';
    return false;
  } finally {
    isLoggingIn.value = false;
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
                          <div
                            class="mb-3 overflow-hidden rounded border border-gray-200 bg-gray-50 flip-card-container cursor-pointer"
                            :class="{ flipped: flippedCards[membresia.id] }"
                            @click="toggleFlip(membresia.id)"
                            :title="flippedCards[membresia.id] ? 'Ver imagen' : 'Ver info'"
                          >
                            <div class="flip-card-inner">
                              <div class="flip-card-front">
                                <img
                                  :src="imagenMembresia(membresia)"
                                  :alt="`Imagen de ${membresia.nombre}`"
                                  class="h-full w-full object-contain"
                                />
                              </div>
                              <div class="flip-card-back">
                                <div class="h-full w-full overflow-y-auto rounded border border-gray-200 bg-white p-2">
                                  <div v-html="renderInfoMarkdown(membresia.info || 'Sin info cargada.')"></div>
                                </div>
                              </div>
                            </div>
                          </div>
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
                          <div
                            class="mb-3 overflow-hidden rounded border border-gray-200 bg-gray-50 flip-card-container cursor-pointer"
                            :class="{ flipped: flippedCards[membresia.id] }"
                            @click="toggleFlip(membresia.id)"
                            :title="flippedCards[membresia.id] ? 'Ver imagen' : 'Ver info'"
                          >
                            <div class="flip-card-inner">
                              <div class="flip-card-front">
                                <img
                                  :src="imagenMembresia(membresia)"
                                  :alt="`Imagen de ${membresia.nombre}`"
                                  class="h-full w-full object-contain"
                                />
                              </div>
                              <div class="flip-card-back">
                                <div class="h-full w-full overflow-y-auto rounded border border-gray-200 bg-white p-2">
                                  <div v-html="renderInfoMarkdown(membresia.info || 'Sin info cargada.')"></div>
                                </div>
                              </div>
                            </div>
                          </div>
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
    <InscripcionModoDialog
      v-model="showDialog"
      :header="membresiaPendiente ? membresiaPendiente.nombre : 'Membresia'"
      width="56rem"
      :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
      :mode="inscripcionMode"
      :loading-nuevo="isSubmitting"
      :loading-registrado="isSubmitting"
      :loading-login="isSubmitting"
      submit-nuevo-label="Continuar al pago"
      submit-registrado-label="Continuar al pago"
      submit-login-label="Continuar al pago"
      submit-nuevo-loading-label="Procesando..."
      submit-registrado-loading-label="Procesando..."
      submit-login-loading-label="Procesando..."
      :guest-form="guestForm"
      :guest-errors="guestErrors"
      :paises="paises"
      :provincias="provincias"
      :municipios="municipios"
      :barrios="barrios"
      :guest-form-extra-props="{ mostrarRegistrarDatos: false, forzarRegistrarDatos: true }"
      guest-container-class="border-t border-gray-200 pt-4 max-h-[55vh] overflow-y-auto pr-2"
      :email="emailInput"
      :registered-error="lookupError"
      registered-container-class="space-y-2 border-t border-gray-200 pt-4"
      registered-input-id="email-registrado-membresias-public"
      registered-input-class="w-full rounded-md border border-gray-300 px-3 py-2"
      :login-email="loginForm.email"
      :login-password="loginForm.password"
      :login-error="loginError"
      login-container-class="space-y-2 border-t border-gray-200 pt-4"
      login-email-id="login-email-membresias-public"
      login-password-id="login-password-membresias-public"
      login-input-class="block w-full rounded-md border border-gray-300 px-3 py-2"
      @update:email="emailInput = $event"
      @update:login-email="loginForm.email = $event"
      @update:login-password="loginForm.password = $event"
      @select-mode="seleccionarModoInscripcion"
      @submit-nuevo="confirmarSuscripcion"
      @submit-registrado="confirmarSuscripcion"
      @submit-login="confirmarSuscripcion"
    >
      <template #afterModeButtons>
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
      </template>
    </InscripcionModoDialog>

    <Dialog
      v-model:visible="showPagoDialog"
      modal
      header="Confirmar pago de membresia"
      :style="{ width: '42rem' }"
      :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
    >
      <div class="space-y-4">
        <div class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-4">
          <p class="text-sm text-gray-700 mb-1">Membresia elegida</p>
          <p class="text-lg font-semibold text-gray-900">{{ membresiaPendiente?.nombre || '-' }}</p>
          <p class="text-sm text-gray-600 mt-2">
            Total a abonar:
            <span class="font-semibold text-gray-900">{{ formatCurrency(membresiaSeleccionadaValor) }}</span>
          </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
          <p class="text-sm text-gray-700">
            🔹 Las membresías se abonan por mes calendario el 1 de cada mes con suscripción mensual en Mercado Pago
            (con tarjeta de crédito, débito o efectivo en cuenta según tu elección).
          </p>
          <p class="text-sm text-gray-700 mt-3">
            Las suscripciones pueden darse de baja en cualquier momento de manera muy sencilla.
          </p>
        </div>

        <div class="rounded-lg border border-gray-200 p-4">
          <p class="text-sm font-semibold text-gray-800 mb-3">Boton de pago</p>
          <a
            v-if="botonPagoLink && imagenMetodoPago"
            :href="botonPagoLink"
            target="_blank"
            rel="noopener"
            class="inline-flex rounded-xl border border-indigo-100 bg-white p-3 shadow-sm transition hover:shadow-md"
          >
            <img
              :src="imagenMetodoPago"
              :alt="metodoPagoNombre"
              class="h-auto w-[150px] object-contain"
            />
          </a>
          <a
            v-else-if="botonPagoLink"
            :href="botonPagoLink"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
          >
            Pagar
          </a>
          <p v-else class="text-sm text-amber-700">
            Esta membresía no tiene botón de pago configurado.
          </p>
        </div>

        <div class="rounded-lg border border-gray-200 p-4">
          <button
            type="button"
            class="w-full inline-flex items-center justify-between rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition-colors"
            @click="showNoMercadoPagoInfo = !showNoMercadoPagoInfo"
          >
            <span>No tengo MercadoPago</span>
            <i :class="showNoMercadoPagoInfo ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
          </button>

          <div v-if="showNoMercadoPagoInfo" class="mt-3 space-y-3">
            <p class="text-sm text-gray-700">Puedes abonar también de estas maneras:</p>

            <div class="rounded-md border border-gray-200 bg-white p-3">
              <p class="text-sm font-semibold text-gray-800">Efectivo</p>
              <p class="text-sm text-gray-700 mt-1">
                {{ descripcionEfectivo || 'Disponible para abonar en efectivo.' }}
              </p>
            </div>

            <div class="rounded-md border border-gray-200 bg-white p-3">
              <p class="text-sm font-semibold text-gray-800">Transferencia</p>
              <p class="text-sm text-gray-700 mt-1">
                {{ descripcionTransferencia || 'Disponible para abonar por transferencia.' }}
              </p>
            </div>

            <a
              v-if="botonGetnetAlternativo?.link"
              :href="botonGetnetAlternativo.link"
              target="_blank"
              rel="noopener"
              class="inline-flex items-center justify-center rounded-md bg-sky-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700"
            >
              Pagar con Getnet
            </a>
          </div>
        </div>

        <div class="rounded-lg border border-gray-200 p-4 space-y-3">
          <p class="text-sm font-semibold text-gray-800">Subir comprobante</p>
          <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="seleccionarComprobante" />
          <label class="inline-flex items-center gap-2 text-sm text-gray-700 ml-2">
            <input v-model="pagoEfectivo" type="checkbox" />
            Pagaré más tarde
          </label>
          <p v-if="requiereComprobante" class="text-xs text-gray-500">
            Para finalizar, subi un comprobante o indica pago en efectivo.
          </p>
          <p v-else class="text-xs text-gray-500">
            Esta membresia no requiere comprobante porque el total es 0.
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
            :disabled="isFinishing"
            @click="showPagoDialog = false"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors disabled:opacity-60"
            :disabled="isFinishing || !puedeFinalizar"
            @click="terminarSuscripcion"
          >
            {{ isFinishing ? 'Registrando...' : 'Terminar' }}
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

.flip-card-container {
  perspective: 1000px;
  height: clamp(240px, 32vw, 340px);
}

.flip-card-inner {
  position: relative;
  width: 100%;
  height: 100%;
  transition: transform 0.6s;
  transform-style: preserve-3d;
}

.flip-card-container.flipped .flip-card-inner {
  transform: rotateY(180deg);
}

.flip-card-front,
.flip-card-back {
  position: absolute;
  inset: 0;
  backface-visibility: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.flip-card-back {
  transform: rotateY(180deg);
}
</style>

