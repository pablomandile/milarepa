<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import backgroundImage from '../../../images/7036360.svg';
import InscripcionModoDialog from '@/Components/Dialogs/InscripcionModoDialog.vue';

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

function serviciosDisponibles(actividad) {
  return Boolean(
    actividad?.hospedajes?.length ||
    actividad?.comidas?.length ||
    actividad?.transportes?.length ||
    actividad?.grabacion ||
    actividad?.grabacion_id
  );
}

function obtenerFechaFinActividad(actividad) {
  const valor = actividad?.fecha_fin ?? actividad?.fechaFin ?? actividad?.fecha_hasta ?? null;
  if (!valor) return null;

  if (typeof valor === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(valor)) {
    const fechaFinDia = new Date(`${valor}T23:59:59`);
    return Number.isNaN(fechaFinDia.getTime()) ? null : fechaFinDia;
  }

  const fecha = new Date(valor);
  return Number.isNaN(fecha.getTime()) ? null : fecha;
}

function actividadFinalizada(actividad) {
  const fechaFin = obtenerFechaFinActividad(actividad);
  if (!fechaFin) return false;
  return new Date() > fechaFin;
}

function actividadSinInscripcionDisponible(actividad) {
  return esInscrito(actividad.id) || actividadFinalizada(actividad);
}

function textoBotonInscripcion(actividad) {
  if (esInscrito(actividad.id)) return 'Inscripto';
  if (actividadFinalizada(actividad)) return 'Actividad finalizada';
  return 'Inscribirme';
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
function getFechaLimiteDescuento(actividad) {
  if (!actividad?.pagoAmticipado) return null;
  const fecha = new Date(actividad.pagoAmticipado);
  return Number.isNaN(fecha.getTime()) ? null : fecha;
}

function tieneDescuentoAnticipado(actividad) {
  return !!actividad?.esquema_descuento && !!getFechaLimiteDescuento(actividad);
}

function descuentoVigente(actividad) {
  if (!tieneDescuentoAnticipado(actividad)) return false;
  const limite = getFechaLimiteDescuento(actividad);
  const ahora = new Date();
  return limite ? ahora <= limite : false;
}

function precioDesdeEsquema(esquema, membresiaId = null) {
  const membresias = esquema?.membresias || [];
  if (!Array.isArray(membresias) || membresias.length === 0) return null;

  if (membresiaId) {
    const pivot = membresias.find((epm) => epm.membresia_id === membresiaId);
    if (pivot?.precio !== undefined && pivot?.precio !== null) return Number(pivot.precio);
  }

  const normalizar = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

  const general = membresias.find((epm) => {
    const nombre = normalizar(epm?.membresia?.nombre);
    return nombre === 'sin membresia' || nombre.includes('sin membres');
  });
  if (general?.precio !== undefined && general?.precio !== null) return Number(general.precio);

  return null;
}

function precioSinMembresiaNormal(actividad) {
  return precioDesdeEsquema(actividad?.esquema_precio, null) ?? 0;
}

function precioSinMembresiaVigente(actividad) {
  if (descuentoVigente(actividad)) {
    const precioConDescuento = precioDesdeEsquema(actividad?.esquema_descuento, null);
    if (precioConDescuento !== null) return precioConDescuento;
  }
  return precioSinMembresiaNormal(actividad);
}

function precioMembresiaUsuario(actividad, user) {
  const userMemId = user?.membresia?.id || user?.membresia_id;
  if (!userMemId) return 0;

  if (descuentoVigente(actividad)) {
    const precioConDescuento = precioDesdeEsquema(actividad?.esquema_descuento, userMemId);
    if (precioConDescuento !== null) return precioConDescuento;
  }

  return precioDesdeEsquema(actividad?.esquema_precio, userMemId) ?? 0;
}

function formatoFechaLimite(actividad) {
  const limite = getFechaLimiteDescuento(actividad);
  if (!limite) return '-';
  return limite.toLocaleDateString('es-AR');
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
    lookupError.value = 'No se pudo validar el email. Probá de nuevo.';
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
    user_id: user.id,
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

function direccionActividad(actividad) {
  return actividad?.lugar?.direccion || actividad?.entidad?.direccion || '';
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

function renderMarkdown(value) {
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
      html.push(`<h5 class="text-base font-semibold text-gray-900 mt-3">${formatInlineMarkdown(line.slice(4).trim())}</h5>`);
    } else if (line.startsWith('## ')) {
      html.push(`<h4 class="text-lg font-semibold text-gray-900 mt-3">${formatInlineMarkdown(line.slice(3).trim())}</h4>`);
    } else if (line.startsWith('# ')) {
      html.push(`<h3 class="text-xl font-bold text-gray-900 mt-3">${formatInlineMarkdown(line.slice(2).trim())}</h3>`);
    } else {
      html.push(`<p class="text-sm text-gray-700 leading-relaxed mt-2">${formatInlineMarkdown(line)}</p>`);
    }
  }

  if (inList) {
    html.push('</ul>');
  }

  return html.join('');
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
                            <!-- Recorremos las actividades -->
                            <div
                                v-for="(actividad, index) in slotProps.items"
                                :key="actividad.id"
                                class="col-12 md:col-6 xl:col-6 px-0 md:px-2 mb-4"
                            >
                                <!-- Contenedor flip-card -->
                                <div
                                class="flip-card-container border-round shadow-sm hover:shadow-md transition-shadow"
                                :class="{
                                  'flipped': flippedCards[actividad.id],
                                  'card-mobile-tall': cardConMuchoTexto(actividad),
                                }"
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
                                            <h3 class="text-base md:text-lg font-semibold leading-tight flex items-start gap-2">
                                                <i class="fa-solid fa-dharmachakra" aria-hidden="true"></i>
                                                <span class="flex flex-col">
                                                    <span
                                                        v-if="actividad.tipo_actividad?.abreviacion"
                                                        class="text-xs md:text-sm text-indigo-700 font-semibold uppercase tracking-wide"
                                                    >
                                                        {{ actividad.tipo_actividad.abreviacion }}
                                                    </span>
                                                    <span class="text-sm md:text-lg font-semibold text-gray-800">
                                                        {{ actividad.nombre }}
                                                    </span>
                                                </span>
                                            </h3>
                                        </div>
                                        <div class="flex flex-col md:flex-row flex-1">
                                            <!-- Imagen a la izquierda -->
                                            <div class="w-full md:w-1/2 md:pr-3 mb-3 md:mb-0 flex items-center justify-center bg-transparent cursor-pointer rounded h-56 md:h-full" @click="toggleFlip(actividad.id)">
                                                <img
                                                    class="w-full h-full object-contain rounded"
                                                    :src="actividad.imagen?.ruta
                                                        ? `/storage/${actividad.imagen.ruta}`
                                                        : '/storage/img/actividades/imagen-no-disponible.jpg'"
                                                    :alt="actividad.nombre"
                                                />
                                            </div>
                                            <!-- Texto a la derecha -->
                                            <div class="w-full md:w-1/2 flex flex-col justify-between md:pl-2">
                                                <div class="flex-1">
                                                    <p class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                                        <span class="sr-only">Fecha</span>
                                                        <span>
                                                            {{ formatFechaLarga(actividad.fecha_inicio) }}
                                                        </span>
                                                    </p>
                                                    <p v-if="actividad.fecha_inicio" class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                                        <span class="sr-only">Hora</span>
                                                        <span>{{ new Date(actividad.fecha_inicio).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) }} hs.</span>
                                                    </p>
                                                    <p class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                                        <span class="sr-only">Lugar</span>
                                                        <span class="inline-flex items-center gap-2">
                                                          <span>{{ direccionActividad(actividad) }}</span>
                                                          <button
                                                            v-if="direccionActividad(actividad)"
                                                            type="button"
                                                            class="inline-flex items-center justify-center p-0 text-sky-700 hover:text-sky-900 shrink-0"
                                                            title="Ver en mapa"
                                                            aria-label="Ver en mapa"
                                                            @click.stop="abrirMapa(direccionActividad(actividad))"
                                                          >
                                                            <i class="pi pi-map"></i>
                                                          </button>
                                                        </span>
                                                    </p>
                                                    <p v-if="actividad.modalidad?.nombre" class="text-sm md:text-base text-gray-600 mb-0.5 md:mb-1 leading-tight flex items-center gap-2">
                                                        <i class="fa-solid fa-video" aria-hidden="true"></i>
                                                        <span class="sr-only">Modalidad</span>
                                                        <span>{{ actividad.modalidad.nombre }}</span>
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
                                                            class="text-xs md:text-sm mb-0.5 md:mb-1 leading-tight flex items-center gap-2"
                                                            :class="{
                                                                'font-bold': !userContext?.membresia || userContext?.membresia?.nombre === 'Sin membresía',
                                                                'text-gray-800 line-through': userContext?.membresia && userContext?.membresia?.nombre !== 'Sin membresía'
                                                            }"
                                                        >
                                                            <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                                                            <span class="sr-only">Valor</span>
                                                            <span class="font-bold text-gray-700">
                                                                ${{ formatPrice(precioSinMembresiaVigente(actividad)) }}
                                                            </span>
                                                        </p>
                                                        <p
                                                          v-if="descuentoVigente(actividad)"
                                                          class="text-[11px] md:text-xs text-amber-700 mb-0.5 md:mb-1 leading-tight"
                                                        >
                                                          Después de {{ formatoFechaLimite(actividad) }}:
                                                          <strong>
                                                            <span> ${{ formatPrice(precioSinMembresiaNormal(actividad)) }}</span>
                                                          </strong>
                                                        </p>
                                                        <p v-if="userContext?.membresia && userContext.membresia?.nombre !== 'Sin membresía'" class="text-xs md:text-sm text-gray-600 mb-1 md:mb-2 leading-tight">
                                                            <strong>Con {{ userContext.membresia?.nombre }}:</strong>
                                                            <span class="font-bold text-green-700"> ${{ formatPrice(precioMembresiaUsuario(actividad, userContext)) }}</span>
                                                        </p>
                                                    </template>
                                                    <div
                                                      v-if="serviciosDisponibles(actividad)"
                                                      class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2"
                                                    >
                                                      <button
                                                        type="button"
                                                        class="flex w-full items-center justify-between gap-3 text-left text-xs md:text-sm font-medium text-slate-700"
                                                        @click.stop="toggleServicios(actividad.id)"
                                                      >
                                                        <span>Más servicios...</span>
                                                        <span class="inline-flex h-6 w-2 items-center justify-center rounded-full border border-slate-300 bg-white text-sm font-semibold text-slate-700">
                                                          {{ expandedServicios[actividad.id] ? '-' : '+' }}
                                                        </span>
                                                      </button>
                                                      <div
                                                        v-if="expandedServicios[actividad.id]"
                                                        class="mt-2 space-y-1 border-t border-slate-200 pt-2"
                                                      >
                                                        <p
                                                          v-if="actividad.hospedajes && actividad.hospedajes.length > 0"
                                                          class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2"
                                                        >
                                                          <i class="pi pi-home text-indigo-600" aria-hidden="true"></i>
                                                          <span>Ofrece Hospedaje</span>
                                                        </p>
                                                        <p
                                                          v-if="actividad.comidas && actividad.comidas.length > 0"
                                                          class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2"
                                                        >
                                                          <i class="pi pi-shopping-bag text-amber-600" aria-hidden="true"></i>
                                                          <span>Ofrece Comidas</span>
                                                        </p>
                                                        <p
                                                          v-if="actividad.transportes && actividad.transportes.length > 0"
                                                          class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2"
                                                        >
                                                          <i class="pi pi-car text-sky-600" aria-hidden="true"></i>
                                                          <span>Ofrece Transporte</span>
                                                        </p>
                                                        <p
                                                          v-if="actividad.grabacion || actividad.grabacion_id"
                                                          class="text-xs md:text-sm text-gray-700 leading-tight flex items-center gap-2"
                                                        >
                                                          <i class="pi pi-headphones text-violet-600" aria-hidden="true"></i>
                                                          <span>Ofrece Grabaciones</span>
                                                        </p>
                                                      </div>
                                                    </div>
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
                                              :disabled="actividadSinInscripcionDisponible(actividad)"
                                                class="py-2 px-3 rounded text-sm flex-1 transition-colors flex items-center justify-center gap-1"
                                              :class="actividadSinInscripcionDisponible(actividad) ? 'bg-gray-200 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                                @click="inscribir(actividad)"
                                            >
                                                <i v-if="esInscrito(actividad.id)" class="pi pi-heart-fill"></i>
                                              {{ textoBotonInscripcion(actividad) }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- BACK: descripción completa -->
                                    <div class="flip-card-back p-4 md:p-6 flex flex-col h-full" @click="toggleFlip(actividad.id)">
                                        <h3 class="text-lg md:text-xl font-semibold mb-3 md:mb-4 text-gray-800">Descripción</h3>
                                        <div class="flex-1 overflow-y-auto">
                                            <div
                                                class="prose prose-xs md:prose-sm max-w-none text-gray-700"
                                                v-html="renderMarkdown(actividad.descripcion?.descripcion || 'No hay descripción disponible')"
                                            ></div>
                                        </div>
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <button
                                            :disabled="actividadSinInscripcionDisponible(actividad)"
                                            class="py-1.5 px-4 rounded w-full transition-colors flex items-center justify-center gap-2"
                                            :class="actividadSinInscripcionDisponible(actividad) ? 'bg-gray-400 text-gray-700 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 text-white'"
                                            @click="inscribir(actividad)"
                                            >
                                            <i v-if="esInscrito(actividad.id)" class="pi pi-heart-fill"></i>
                                            {{ textoBotonInscripcion(actividad) }}
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

<style scoped>
/* Estilos para flip-card */
 .flip-card-container {
    perspective: 1000px;
    /* Un poco de borde redondo: */
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    min-height: 620px;
    height: clamp(620px, 96vh, 760px);
}

@media (min-width: 768px) {
  .flip-card-container {
    min-height: 580px;
    height: clamp(580px, 70vh, 620px);
  }
}

@media (max-width: 767px) {
  .flip-card-container.card-mobile-tall {
    min-height: 700px;
    height: clamp(700px, 100vh, 840px);
  }
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
