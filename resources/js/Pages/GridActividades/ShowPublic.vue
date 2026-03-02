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

const descripcionHtml = computed(() => renderMaestroMarkdown(truncatedDescription.value));

const formatPrice = (price) => {
  if (!price || Number.isNaN(price)) return '0,00';
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
};

const normalizarNombre = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .trim();

const fechaLimiteDescuento = computed(() => {
  if (!props.actividad?.pagoAmticipado) return null;
  const fecha = new Date(props.actividad.pagoAmticipado);
  return Number.isNaN(fecha.getTime()) ? null : fecha;
});

const descuentoVigente = computed(() => {
  if (!props.actividad?.esquema_descuento || !fechaLimiteDescuento.value) return false;
  return new Date() <= fechaLimiteDescuento.value;
});

const esquemaVigente = computed(() => {
  if (descuentoVigente.value && props.actividad?.esquema_descuento) {
    return props.actividad.esquema_descuento;
  }
  return props.actividad?.esquema_precio || null;
});

const precioDesdeEsquema = (esquema, membresiaId = null) => {
  const membresias = esquema?.membresias || [];
  if (!Array.isArray(membresias) || membresias.length === 0) return null;

  if (membresiaId) {
    const pivot = membresias.find((epm) => epm.membresia_id === membresiaId);
    if (pivot?.precio !== undefined && pivot?.precio !== null) return Number(pivot.precio);
  }

  const general = membresias.find((epm) => {
    const nombre = normalizarNombre(epm?.membresia?.nombre);
    return nombre === 'sin membresia' || nombre.includes('sin membres');
  });

  if (general?.precio !== undefined && general?.precio !== null) return Number(general.precio);
  return null;
};

const precioGeneral = computed(() => {
  return precioDesdeEsquema(esquemaVigente.value, null) ?? 0;
});

const precioGeneralDespuesAnticipado = computed(() => {
  return precioDesdeEsquema(props.actividad?.esquema_precio, null) ?? 0;
});

const fechaPagoAnticipadoTexto = computed(() => {
  if (!fechaLimiteDescuento.value) return '';
  return fechaLimiteDescuento.value.toLocaleDateString('es-AR');
});

const actividadEsGratuita = computed(() => {
  const nombreEsquema = (props.actividad?.esquema_precio?.nombre || '').toString().toLowerCase();
  return (
    props.actividad?.gratuita === true ||
    props.actividad?.es_gratuita === true ||
    nombreEsquema.includes('gratuita')
  );
});

const membresiaIdUsuario = computed(() => {
  return (
    userByEmail.value?.membresia?.id ||
    page.props?.auth?.user?.membresia?.id ||
    page.props?.auth?.user?.membresia_id ||
    null
  );
});

const membresiaNombreUsuario = computed(() => {
  if (userByEmail.value?.membresia?.nombre) return userByEmail.value.membresia.nombre;
  if (page.props?.auth?.user?.membresia?.nombre) return page.props.auth.user.membresia.nombre;

  const match = (esquemaVigente.value?.membresias || []).find(
    (epm) => epm.membresia_id === membresiaIdUsuario.value
  );
  return match?.membresia?.nombre || null;
});

const precioMembresia = computed(() => {
  const membresiaId = membresiaIdUsuario.value;
  if (!membresiaId) return null;
  return precioDesdeEsquema(esquemaVigente.value, membresiaId);
});

const precioMembresiaDespuesAnticipado = computed(() => {
  const membresiaId = membresiaIdUsuario.value;
  if (!membresiaId) return null;
  return precioDesdeEsquema(props.actividad?.esquema_precio, membresiaId);
});

const mostrarDescuento = computed(() => {
  return !actividadEsGratuita.value && !!membresiaIdUsuario.value && precioMembresia.value !== null;
});

const mostrarHintMembresia = computed(() => {
  return !actividadEsGratuita.value && !mostrarDescuento.value;
});

const emailInput = ref('');
const lookupError = ref('');
const isLookingUp = ref(false);
const userByEmail = ref(null);
const guestModalVisible = ref(false);
const guestErrors = ref({});
const isGuestSubmitting = ref(false);
const maestroImageDialogVisible = ref(false);
const selectedMaestroImageUrl = ref('');
const maestroSobreExpandido = ref({});
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
const maestrosConImagen = computed(() => {
  if (!Array.isArray(props.actividad?.maestros)) return [];
  return props.actividad.maestros
    .map((maestro) => {
      let url = null;
      if (maestro?.imagen?.ruta) url = `/storage/${maestro.imagen.ruta}`;
      else if (maestro?.imagen_ruta) url = `/storage/${maestro.imagen_ruta}`;
      else if (maestro?.imagen_url) url = maestro.imagen_url;
      else if (maestro?.foto) url = maestro.foto;
      if (!url) return null;
      return {
        id: maestro.id,
        nombre: maestro.nombre,
        url,
      };
    })
    .filter(Boolean);
});

const escapeHtml = (value) => {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
};

const formatInlineMarkdown = (value) => {
  return value
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.+?)\*/g, '<em>$1</em>');
};

const renderMaestroMarkdown = (value) => {
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
      html.push(`<p class="text-gray-700 leading-relaxed mt-2">${formatInlineMarkdown(line)}</p>`);
    }
  }

  if (inList) {
    html.push('</ul>');
  }

  return html.join('');
};

const maestrosConSobre = computed(() => {
  if (!Array.isArray(props.actividad?.maestros)) return [];

  return props.actividad.maestros
    .filter((maestro) => (maestro?.sobre_maestro || '').trim().length > 0);
});

const tituloSobreMaestros = computed(() => {
  return maestrosConSobre.value.length > 1 ? 'Sobre los Maestr@s' : 'Sobre el maestr@';
});

const programaTexto = computed(() => {
  return (props.actividad?.programa?.programa || '').toString().trim();
});

const programaHtml = computed(() => {
  if (!programaTexto.value) return '';
  return renderMaestroMarkdown(programaTexto.value);
});

const mapEmbedUrl = computed(() => {
  if (!selectedAddress.value) return '';
  return `https://maps.google.com/maps?q=${encodeURIComponent(selectedAddress.value)}&output=embed`;
});

function abrirImagenMaestro(url) {
  if (!url) return;
  selectedMaestroImageUrl.value = url;
  maestroImageDialogVisible.value = true;
}

function tieneMasSobreMaestro(maestro) {
  const texto = String(maestro?.sobre_maestro || '');
  return texto.length > 310;
}

function textoSobreMaestro(maestro) {
  const texto = String(maestro?.sobre_maestro || '');
  if (maestroSobreExpandido.value[maestro.id]) return texto;
  if (texto.length <= 310) return texto;
  return `${texto.slice(0, 310)}...`;
}

function toggleSobreMaestro(maestroId) {
  maestroSobreExpandido.value[maestroId] = !maestroSobreExpandido.value[maestroId];
}

function abrirMapa(direccion) {
  if (!direccion) return;
  selectedAddress.value = direccion;
  mapModalVisible.value = true;
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
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
          <div class="p-8">
            <div class="w-full mb-6 rounded-lg overflow-hidden bg-transparent">
              <img
                v-if="actividad?.imagen"
                :src="'/storage/' + actividad.imagen.ruta"
                :alt="'Imagen de ' + actividad.nombre"
                class="block w-full h-auto"
              />
              <img
                v-else
                src="/storage/img/actividades/imagen-no-disponible.jpg"
                alt="Sin imagen"
                class="block w-full h-auto"
              />
            </div>

            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4 text-center">
              {{ actividad.nombre }}
            </h1>

            <div class="bg-white bg-opacity-90 rounded-lg p-5 mb-6 border border-indigo-50">
              <!-- <h2 class="text-lg font-semibold text-gray-900 mb-2">Descripción</h2> -->
              <div
                class="prose prose-sm sm:prose max-w-none text-gray-700"
                v-html="descripcionHtml"
              ></div>
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
              <div class="rounded-lg border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-calendar text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Fecha y Hora</h3>
                  <p class="text-gray-700 text-base">{{ actividad.fecha_inicio_formateada || 'No especificado' }}</p>
                </div>
              </div>

              <div class="rounded-lg border border-amber-100 bg-gradient-to-br from-amber-50 to-white shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-map-marker text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Lugar</h3>
                  <div class="inline-flex items-center gap-2 text-gray-700 text-base">
                    <span>{{ actividad.entidad?.direccion || 'No especificado' }}</span>
                    <button
                      v-if="actividad.entidad?.direccion"
                      type="button"
                      class="inline-flex items-center justify-center p-0 text-sky-700 hover:text-sky-900"
                      title="Ver en mapa"
                      aria-label="Ver en mapa"
                      @click="abrirMapa(actividad.entidad.direccion)"
                    >
                      <i class="pi pi-map"></i>
                    </button>
                  </div>
                </div>
              </div>

              <div class="rounded-lg border border-sky-100 bg-gradient-to-br from-sky-50 to-white shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-video text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Modalidad</h3>
                  <p class="text-gray-700 text-base">{{ actividad.modalidad?.nombre || 'No especificado' }}</p>
                </div>
              </div>

              <div class="rounded-lg border border-rose-100 bg-gradient-to-br from-rose-50 to-white shadow-md min-h-[88px] overflow-hidden">
                <div class="flex h-full items-stretch">
                  <div class="flex flex-1 items-start gap-3 p-5">
                    <i class="pi pi-user text-indigo-600 text-2xl"></i>
                    <div>
                      <h3 class="text-sm font-semibold text-gray-800">Maestr@s</h3>
                      <p class="text-gray-700 text-base">
                        <span v-if="actividad.maestros && actividad.maestros.length > 0">
                          {{ actividad.maestros.map((m) => m.nombre).join(', ') }}
                        </span>
                        <span v-else>No especificado</span>
                      </p>
                    </div>
                  </div>
                  <div v-if="maestrosConImagen.length > 0" class="shrink-0 self-stretch border-l border-rose-100 flex items-stretch overflow-x-auto">
                    <button
                      v-for="maestro in maestrosConImagen"
                      :key="maestro.id"
                      type="button"
                      class="relative w-24 shrink-0 self-stretch overflow-hidden group cursor-zoom-in border-l border-rose-200 first:border-l-0"
                      @click="abrirImagenMaestro(maestro.url)"
                    >
                      <img
                        :src="maestro.url"
                        :alt="`Foto de ${maestro.nombre || 'maestr@'}`"
                        class="h-full w-full object-cover"
                      />
                      <span class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity duration-150 group-hover:opacity-100">
                        <i class="pi pi-search-plus text-white text-xs"></i>
                      </span>
                    </button>
                  </div>
                  <div v-else class="w-24 shrink-0 border-l border-rose-100 bg-rose-50/60 flex items-center justify-center text-slate-400">
                    <i class="pi pi-user text-xl"></i>
                  </div>
                </div>
              </div>

              <div class="rounded-lg border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-dollar text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Valor general</h3>
                  <p v-if="actividadEsGratuita" class="text-emerald-700 text-base font-semibold">
                    ¡Actividad Gratuita!
                  </p>
                  <p v-else class="text-gray-700 text-base">
                    <strong :class="mostrarDescuento ? 'line-through text-gray-400' : ''">
                      $ {{ formatPrice(precioGeneral) }}
                    </strong>
                  </p>
                  <p
                    v-if="descuentoVigente && !actividadEsGratuita && !mostrarDescuento"
                    class="text-xs text-amber-700 mt-1"
                  >
                    ($ {{ formatPrice(precioGeneralDespuesAnticipado) }}) después de {{ fechaPagoAnticipadoTexto }}
                  </p>
                  <p v-if="mostrarHintMembresia" class="text-xs text-gray-500 mt-1">
                    Iniciá sesión para ver precios con membresía.
                  </p>
                </div>
              </div>

              <div v-if="mostrarDescuento" class="rounded-lg border border-violet-100 bg-gradient-to-br from-violet-50 to-white shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-star-fill text-amber-500 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Membresía</h3>
                  <p class="text-gray-700 text-base">
                    <strong>{{ membresiaNombreUsuario || 'Con membresía' }}</strong>
                  </p>
                  <p class="text-emerald-700 text-base mt-1">
                    Valor con membresía: <strong>$ {{ formatPrice(precioMembresia) }}</strong>
                  </p>
                  <p
                    v-if="descuentoVigente && !actividadEsGratuita && precioMembresiaDespuesAnticipado !== null"
                    class="text-xs text-amber-700 mt-1"
                  >
                    ($ {{ formatPrice(precioMembresiaDespuesAnticipado) }}) después de {{ fechaPagoAnticipadoTexto }}
                  </p>
                </div>
              </div>
            </div>

            <div
              v-if="maestrosConSobre.length"
              class="mb-6 rounded-lg border border-rose-100 bg-gradient-to-br from-rose-50 to-white shadow-md p-5"
            >
              <h2 class="text-lg font-semibold text-gray-900 mb-3">{{ tituloSobreMaestros }}</h2>
              <div class="space-y-5">
                <div
                  v-for="maestro in maestrosConSobre"
                  :key="`sobre-maestro-${maestro.id}`"
                  class="rounded-md border border-rose-100 bg-white/70 p-4"
                >
                  <h3 class="text-3xl font-semibold text-gray-900">{{ maestro.nombre }}</h3>
                  <div
                    class="mt-2 prose prose-sm max-w-none prose-headings:my-0 prose-p:my-0 prose-strong:text-gray-900"
                    v-html="renderMaestroMarkdown(textoSobreMaestro(maestro))"
                  ></div>
                  <div v-if="tieneMasSobreMaestro(maestro)" class="mt-3 text-right">
                    <button
                      type="button"
                      class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm"
                      @click="toggleSobreMaestro(maestro.id)"
                    >
                      {{ maestroSobreExpandido[maestro.id] ? 'Ver menos' : 'Ver más' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-if="programaTexto"
              class="mb-6 rounded-lg border border-sky-100 bg-gradient-to-br from-sky-50 to-white shadow-md p-5"
            >
              <h2 class="text-lg font-semibold text-gray-900 mb-3">Programa</h2>
              <div
                class="rounded-md border border-sky-100 bg-white/70 p-4 prose prose-sm max-w-none prose-headings:my-0 prose-p:my-0 prose-strong:text-gray-900"
                v-html="programaHtml"
              ></div>
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
                class="flex-1 px-4 py-3 bg-gray-400 hover:bg-gray-600 text-white rounded-lg transition-colors text-center font-semibold"
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
      v-model:visible="mapModalVisible"
      modal
      header="Ubicación"
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

    <Dialog
      v-model:visible="maestroImageDialogVisible"
      modal
      header="Foto de Maestr@"
      :style="{ width: '720px' }"
    >
      <div class="w-full">
        <img
          v-if="selectedMaestroImageUrl"
          :src="selectedMaestroImageUrl"
          alt="Foto de Maestr@"
          class="w-full max-h-[70vh] object-contain"
        />
      </div>
    </Dialog>

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
            class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-600 transition-colors"
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
