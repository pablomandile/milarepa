<script>
export default {
  name: 'ActividadesShow'
}
</script>

<script setup>
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, usePage } from '@inertiajs/vue3'

const $page = usePage()

const props = defineProps({
  actividad: {
    type: Object,
    required: true,
  },
  userInscripcionesActividadIds: {
    type: Array,
    required: false,
    default: () => []
  }
})

const user = $page.props.auth.user

// Función para formatear precios con punto para miles y coma para decimales
const formatPrice = (price) => {
  if (!price || isNaN(price)) return '0,00';
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price);
};

const showFullDescription = ref(false)

const fullDescription = computed(() => props.actividad.descripcion?.descripcion || 'No hay descripción disponible')

const hasMoreDescription = computed(() => {
  let count = 0
  for (const ch of fullDescription.value) {
    if (ch === '.') count++
    if (count > 3) return true
  }
  return false
})

const truncatedDescription = computed(() => {
  if (showFullDescription.value) return fullDescription.value
  const text = fullDescription.value
  let count = 0
  for (let i = 0; i < text.length; i++) {
    if (text[i] === '.') {
      count++
      if (count === 3) return text.slice(0, i + 1)
    }
  }
  return text
})

const precioMembresiaUsuario = computed(() => {
  if (!props.actividad.esquema_precio?.membresias || !user.membresia?.id) return 0
  const pivot = props.actividad.esquema_precio.membresias.find(epm => epm.membresia_id === user.membresia.id)
  return pivot ? parseInt(pivot.precio) : 0
})

const inscribir = () => {
  const actividad = props.actividad

  const precioGeneral = actividad.esquema_precio?.membresias
    ?.find(epm => epm.membresia?.nombre === 'Sin membresía')
    ?.precio || 0

  const precioMembresia = precioMembresiaUsuario.value || 0

  const data = {
    actividad_id: actividad.id,
    user_id: user.id,
    membresia: user.membresia?.nombre || 'Sin membresía',
    precioGeneral,
    montoapagar: precioMembresia,
    pago: 'impago',
    estado_id: 1,
    envioLinkStream: 'pendiente',
    envioGrabación: 'pendiente',
    comprobante: null,
    asistencia: 'ausente',
    online: actividad.modalidad?.nombre === 'Online',
    hospedaje_id: null,
    comida_id: null,
    transporte_id: null,
  }

  router.post('/inscripciones', data)
}

const esInscrito = computed(() => {
  return props.userInscripcionesActividadIds.includes(props.actividad.id)
})
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades</h1>
    </template>

    <div class="py-12">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Tarjeta principal -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8" style="background: linear-gradient(135deg, #ffffff 0%, #ddd6fe 100%);">
          <div class="p-8">
            <!-- Imagen reducida -->
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

            <!-- Título -->
            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4 text-center">
              {{ actividad.nombre }}
            </h1>

            <!-- Descripción principal debajo del título -->
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

            <!-- Cards de información -->
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
                      {{ actividad.maestros.map(m => m.nombre).join(', ') }}
                    </span>
                    <span v-else>No especificado</span>
                  </p>
                </div>
              </div>

              <div class="bg-white rounded-lg shadow-md p-5 flex items-start gap-3">
                <i class="pi pi-dollar text-indigo-600 text-2xl"></i>
                <div>
                  <h3 class="text-sm font-semibold text-gray-800">Valor</h3>
                  <p class="text-gray-700 text-base">
                    <strong>General:</strong> 
                    <span :class="user?.membresia && user.membresia?.nombre !== 'Sin membresía' ? 'line-through text-gray-400' : 'font-bold'">
                      ${{ formatPrice(actividad.esquema_precio?.membresias?.find(epm => epm.membresia?.nombre === 'Sin membresía')?.precio || 0) }}
                    </span>
                  </p>
                  <p v-if="user?.membresia && user.membresia?.nombre !== 'Sin membresía'" class="text-indigo-600 text-base">
                    <strong>Con {{ user.membresia?.nombre }}:</strong> 
                    <span v-if="parseInt(precioMembresiaUsuario) === 0" class="font-bold text-green-700"> Incluído</span>
                    <span v-else class="font-bold text-green-700"> ${{ formatPrice(precioMembresiaUsuario) }}</span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-3">
              <Link 
                :href="route('grid-actividades.index')" 
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
  </AppLayout>
</template>

<style scoped>
</style>
