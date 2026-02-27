<script setup>
import { computed, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { router, Link } from '@inertiajs/vue3';

const $page = usePage();
const isAsistant = computed(() => {
    const roles = ($page.props.user?.roles || []).map((role) => String(role).toLowerCase());
    return roles.includes('asistant');
});

const props = defineProps({
  inscripciones: {
    type: Array,
    required: true,
    default: () => []
  }
});

const toast = useToast();
const deleteModalVisible = ref(false);
const inscripcionToDelete = ref(null);
const expandedRows = ref([]);
const comprobanteModalVisible = ref(false);
const inscripcionParaComprobante = ref(null);
const comprobanteFile = ref(null);
const comprobanteDescripcion = ref('');

const confirmDelete = (id) => {
    inscripcionToDelete.value = id;
    deleteModalVisible.value = true;
};

const deleteInscripcion = () => {
    if (inscripcionToDelete.value) {
        router.delete(route('inscripciones.destroy', inscripcionToDelete.value), {
            onSuccess: () => {
                deleteModalVisible.value = false;
                inscripcionToDelete.value = null;
            },
            onError: () => {
                deleteModalVisible.value = false;
                inscripcionToDelete.value = null;
            },
        });
    }
};

const cancelDelete = () => {
    deleteModalVisible.value = false;
    inscripcionToDelete.value = null;
};

const openComprobanteModal = (inscripcion) => {
    inscripcionParaComprobante.value = inscripcion;
    comprobanteFile.value = null;
    comprobanteDescripcion.value = '';
    comprobanteModalVisible.value = true;
};

const onComprobanteChange = (event) => {
    const files = event.target.files;
    comprobanteFile.value = files && files[0] ? files[0] : null;
};

const subirComprobante = () => {
    if (!inscripcionParaComprobante.value || !comprobanteFile.value) return;

    const formData = new FormData();
    formData.append('comprobante', comprobanteFile.value);
    if (comprobanteDescripcion.value) {
        formData.append('descripcion', comprobanteDescripcion.value);
    }

    router.post(
        route('inscripciones.comprobante', { inscripcion: inscripcionParaComprobante.value.id }),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                comprobanteModalVisible.value = false;
                inscripcionParaComprobante.value = null;
                comprobanteFile.value = null;
                comprobanteDescripcion.value = '';
            },
        }
    );
};

const formatMoney = (value) => {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) return value;
    return numeric.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const comprobanteModal = ref(false);
const comprobantesParaVer = ref([]);
const mapModalVisible = ref(false);
const selectedAddress = ref('');

const mapEmbedUrl = computed(() => {
    if (!selectedAddress.value) return '';
    return `https://maps.google.com/maps?q=${encodeURIComponent(selectedAddress.value)}&output=embed`;
});

const normalizarComprobante = (ruta, descripcion) => {
    if (!ruta) return null;
    const url = /^https?:\/\//i.test(ruta) ? ruta : `/storage/${ruta}`;
    return {
        url,
        descripcion: descripcion || '',
        isPdf: url.toLowerCase().includes('.pdf'),
    };
};

const urlComprobante = (inscripcion) => {
    const raw = inscripcion?.comprobantes?.[0]?.ruta || inscripcion?.comprobante_url || inscripcion?.comprobante;
    if (!raw) return null;
    if (/^https?:\/\//i.test(raw)) return raw;
    return `/storage/${raw}`;
};

const comprobantesExtras = (inscripcion) => {
    const count = inscripcion?.comprobantes?.length || 0;
    return count > 1 ? count - 1 : 0;
};

const abrirComprobante = (inscripcionOrUrl) => {
    const items = [];
    if (inscripcionOrUrl && typeof inscripcionOrUrl === 'object') {
        const inscripcion = inscripcionOrUrl;
        if (Array.isArray(inscripcion.comprobantes) && inscripcion.comprobantes.length) {
            inscripcion.comprobantes.forEach((comprobante) => {
                const item = normalizarComprobante(comprobante.ruta, comprobante.descripcion);
                if (item) items.push(item);
            });
        } else {
            const raw = inscripcion?.comprobante_url || inscripcion?.comprobante;
            const item = normalizarComprobante(raw, '');
            if (item) items.push(item);
        }
    } else {
        const item = normalizarComprobante(inscripcionOrUrl, '');
        if (item) items.push(item);
    }

    if (!items.length) return;
    comprobantesParaVer.value = items;
    comprobanteModal.value = true;
};

const abrirMapa = (direccion) => {
    if (!direccion) return;
    selectedAddress.value = direccion;
    mapModalVisible.value = true;
};

// Mostrar toasts basados en mensajes flash compartidos
watch(() => $page.props.flash, (flash) => {
    if (flash?.success) {
        toast.add({ severity: 'success', summary: 'Inscripción', detail: flash.success, life: 5000 });
    } else if (flash?.error) {
        toast.add({ severity: 'warn', summary: 'Aviso', detail: flash.error, life: 10000 });
    }
}, { immediate: true });
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Mis Inscripciones</h1>
        </template>
        <Toast position="top-right" />
        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                <div v-if="isAsistant" class="mb-4">
                    <Link
                        :href="route('asistant.panel')"
                        class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
                    >
                        Volver al panel
                    </Link>
                </div>
                <!-- Mensajes de éxito -->
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ $page.props.flash.success }}
                </div>
                
                <!-- Mensajes de error -->
                <div v-if="$page.props.flash?.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ $page.props.flash.error }}
                </div>
                
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mt-4">
                        <DataTable
                            :value="inscripciones"
                            dataKey="id"
                            v-model:expandedRows="expandedRows"
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20]"
                            responsiveLayout="scroll"
                            class="p-datatable-sm"
                        >
                            <Column expander style="width: 3rem" />
                            <Column header="Imagen" style="width: 90px">
                                <template #body="{ data }">
                                    <Link
                                        :href="route('inscripciones.show', { inscripcion: data.id })"
                                        class="block w-16 h-16 bg-gray-100 rounded overflow-hidden"
                                        title="Ver inscripción"
                                    >
                                        <img
                                            v-if="data.actividad?.imagen"
                                            :src="'/storage/' + data.actividad.imagen.ruta"
                                            :alt="'Imagen de ' + data.actividad.nombre"
                                            class="w-full h-full object-contain"
                                        />
                                        <img
                                            v-else
                                            src="/storage/img/actividades/imagen-no-disponible.jpg"
                                            alt="Sin imagen"
                                            class="w-full h-full object-contain"
                                        />
                                    </Link>
                                </template>
                            </Column>

                            <Column header="Actividad">
                                <template #body="{ data }">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ data.actividad?.nombre || '-' }}
                                        </p>
                                        <p class="text-xs text-gray-600">
                                            {{ data.actividad?.entidad?.nombre || '-' }}
                                        </p>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Fecha">
                                <template #body="{ data }">
                                    <span class="text-sm">{{ data.actividad?.fecha_inicio_formateada || '-' }}</span>
                                </template>
                            </Column>

                            <Column header="Lugar">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm break-words">{{ data.actividad?.entidad?.direccion || '-' }}
                                        <button
                                            v-if="data.actividad?.entidad?.direccion"
                                            type="button"
                                            class="inline-flex items-center justify-center p-0 text-sky-700 hover:text-sky-900 shrink-0"
                                            title="Ver en mapa"
                                            aria-label="Ver en mapa"
                                            @click="abrirMapa(data.actividad.entidad.direccion)"
                                        >
                                            <i class="pi pi-map"></i>
                                        </button></span>
                                    </div>
                                </template>
                            </Column>


                            <Column header="Precio General">
                                <template #body="{ data }">
                                    <span class="text-sm">${{ formatMoney(data.precioGeneral) }}</span>
                                </template>
                            </Column>

                            <Column header="Monto a pagar">
                                <template #body="{ data }">
                                    <span class="text-sm text-green-700 font-semibold">${{ formatMoney(data.montoapagar) }}</span>
                                </template>
                            </Column>

                            <Column header="Pago">
                                <template #body="{ data }">
                                    <span
                                        class="text-xs font-semibold px-2 py-1 rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-700': data.pago === 'Saldado',
                                            'bg-yellow-100 text-yellow-700': data.pago === 'Parcial',
                                            'bg-yellow-100 text-yellow-700': data.pago === 'Pendiente'
                                        }"
                                    >
                                        {{ data.pago }}
                                    </span>
                                </template>
                            </Column>

                            <Column header="Asistencia">
                                <template #body="{ data }">
                                    <span class="text-sm">{{ data.asistencia || '-' }}</span>
                                </template>
                            </Column>


                            <Column header="Comprobante" class="text-center">
                                <template #body="{ data }">
                                    <button
                                        v-if="urlComprobante(data)"
                                        type="button"
                                        @click="abrirComprobante(data)"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200"
                                        title="Ver comprobante"
                                        aria-label="Ver comprobante"
                                    >
                                        <i class="pi pi-file"></i>
                                    </button>
                                    <span
                                        v-if="comprobantesExtras(data) > 0"
                                        class="ms-1 text-xs font-semibold text-gray-500"
                                    >
                                        +{{ comprobantesExtras(data) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">Sin comprobante</span>
                                </template>
                            </Column>

                            <Column header="Inscripto">
                                <template #body="{ data }">
                                    <span class="text-sm">{{ new Date(data.created_at).toLocaleDateString() }}</span>
                                </template>
                            </Column>

                            <Column header="Acciones" class="text-center">
                                <template #body="{ data }">
                                    <div class="flex items-center justify-center gap-2">
                                        <Link
                                            v-if="data.pago === 'Saldado'"
                                            :href="route('inscripciones.ticket', { inscripcion: data.id })"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200"
                                            title="Ver ticket"
                                            aria-label="Ver ticket"
                                        >
                                            <i class="pi pi-ticket"></i>
                                        </Link>
                                        <button
                                            type="button"
                                            @click="openComprobanteModal(data)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200"
                                            title="Informar pago"
                                            aria-label="Informar pago"
                                        >
                                            <i class="pi pi-upload"></i>
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmDelete(data.id)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-700 border border-red-200"
                                            title="Eliminar inscripción"
                                            aria-label="Eliminar inscripción"
                                        >
                                            <i class="pi pi-trash"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-8">
                                    <p class="text-gray-500 text-lg">No tienes inscripciones registradas.</p>
                                    <p class="text-gray-400 mt-2">¡Inscríbete en una actividad para comenzar!</p>
                                </div>
                            </template>

                            <template #expansion="{ data }">
                                <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-7">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Membresía</p>
                                            <p class="text-sm text-gray-800">{{ data.membresia || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Online</p>
                                            <p class="text-sm text-gray-800">{{ data.online ? 'Sí' : 'No' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Stream</p>
                                            <p class="text-sm text-gray-800">{{ data.envioLinkStream || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Grabación</p>
                                            <p class="text-sm text-gray-800">{{ data.envioGrabación || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Transporte</p>
                                            <p class="text-sm text-gray-800">
                                                {{ data.transporte?.descripcion || data.transporte?.nombre || '-' }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Comida</p>
                                            <p class="text-sm text-gray-800">
                                                {{ data.comida?.nombre || '-' }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hospedaje</p>
                                            <p class="text-sm text-gray-800">
                                                {{ data.hospedaje?.nombre || '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>

                <!-- Modal de confirmación de eliminación -->
                <Dialog 
                    v-model:visible="deleteModalVisible" 
                    modal 
                    header="Confirmar eliminación" 
                    :style="{ width: '450px' }"
                    :closable="false"
                >
                    <div class="flex items-center mb-4">
                        <i class="pi pi-exclamation-triangle text-yellow-500 text-2xl mr-3"></i>
                        <span class="text-lg">¿Está seguro de eliminar la inscripción?</span>
                    </div>
                    <p class="text-gray-600 mb-4">
                        Esta acción no se puede deshacer. Se eliminará permanentemente la inscripción.
                    </p>
                    
                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <button 
                                @click="cancelDelete"
                                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button 
                                @click="deleteInscripcion"
                                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-700 transition-colors"
                            >
                                Sí, eliminar
                            </button>
                        </div>
                    </template>
                </Dialog>

                <Dialog
                    v-model:visible="comprobanteModalVisible"
                    modal
                    header="Informar pago"
                    :style="{ width: '450px' }"
                >
                    <p class="text-sm text-gray-600 mb-3">
                        Subí un comprobante (PDF, JPG o PNG).
                    </p>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="inscripcion_comprobante_descripcion">
                            Descripción (opcional)
                        </label>
                        <input
                            id="inscripcion_comprobante_descripcion"
                            v-model="comprobanteDescripcion"
                            type="text"
                            class="block w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-700"
                            placeholder="Ej: Transferencia febrero"
                        />
                    </div>
                    <input
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        @change="onComprobanteChange"
                        class="block w-full text-sm text-gray-700"
                    />
                    <template #footer>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
                                @click="comprobanteModalVisible = false"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors"
                                :disabled="!comprobanteFile"
                                @click="subirComprobante"
                            >
                                Subir
                            </button>
                        </div>
                    </template>
                </Dialog>

                <Dialog v-model:visible="comprobanteModal" modal header="Comprobante" :style="{ width: '700px' }">
                    <div class="max-h-[70vh] overflow-y-auto space-y-4">
                        <div
                            v-for="(item, index) in comprobantesParaVer"
                            :key="`${item.url}-${index}`"
                            class="rounded border border-gray-200 p-3 bg-white"
                        >
                            <p v-if="item.descripcion" class="text-xs text-gray-500 mb-2">
                                {{ item.descripcion }}
                            </p>
                            <iframe v-if="item.isPdf" :src="item.url" class="w-full h-[60vh] rounded"></iframe>
                            <img v-else :src="item.url" class="w-full rounded" alt="Comprobante" />
                        </div>
                    </div>
                </Dialog>

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
            </div>
        </div>
    </AppLayout>
</template>







