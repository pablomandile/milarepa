<script setup>
import { computed, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import DataView from 'primevue/dataview';
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

const layout = ref('list'); // Usar layout de lista
const toast = useToast();
const deleteModalVisible = ref(false);
const inscripcionToDelete = ref(null);
const comprobanteModalVisible = ref(false);
const inscripcionParaComprobante = ref(null);
const comprobanteFile = ref(null);

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

    router.post(
        route('inscripciones.comprobante', { inscripcion: inscripcionParaComprobante.value.id }),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                comprobanteModalVisible.value = false;
                inscripcionParaComprobante.value = null;
                comprobanteFile.value = null;
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
const comprobanteUrl = ref('');
const comprobanteIsPdf = computed(() => (comprobanteUrl.value || '').toLowerCase().includes('.pdf'));

const urlComprobante = (inscripcion) => {
    const raw = inscripcion?.comprobante_url || inscripcion?.comprobante;
    if (!raw) return null;
    if (/^https?:\/\//i.test(raw)) return raw;
    return `/storage/${raw}`;
};

const abrirComprobante = (url) => {
    if (!url) return;
    comprobanteUrl.value = url;
    comprobanteModal.value = true;
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
                        <DataView
                        :value="inscripciones"
                        :layout="layout"
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[5, 10, 20]"
                        class="mb-6"
                        >

                        <!-- Template list -->
                        <template #list="slotProps">
                            <div class="grid grid-cols-1 gap-4">
                                <div
                                    v-for="(inscripcion, index) in slotProps.items"
                                    :key="inscripcion.id"
                                    class="col-12 p-4 border border-gray-200 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors min-h-[12rem]"
                                >
                                    <div class="grid grid-cols-1 md:grid-cols-[180px_minmax(0,1fr)_auto] gap-4 h-full">
                                        <!-- Imagen fija adaptada a la altura de la card -->
                                        <Link
                                            :href="route('inscripciones.show', { inscripcion: inscripcion.id })"
                                            class="h-32 md:h-40 flex items-center justify-center bg-gray-100 rounded hover:ring-2 hover:ring-indigo-300 transition group overflow-hidden"
                                            title="Ver inscripción"
                                        >
                                            <img
                                                v-if="inscripcion.actividad?.imagen"
                                                :src="'/storage/' + inscripcion.actividad.imagen.ruta"
                                                :alt="'Imagen de ' + inscripcion.actividad.nombre"
                                                class="max-w-full max-h-32 md:max-h-40 object-contain transition-transform duration-300 ease-out transform group-hover:scale-110 cursor-pointer"
                                            />
                                            <img
                                                v-else
                                                src="/storage/img/actividades/imagen-no-disponible.jpg"
                                                alt="Sin imagen"
                                                class="max-w-full max-h-32 md:max-h-40 object-contain transition-transform duration-300 ease-out transform group-hover:scale-110 cursor-pointer"
                                            />
                                        </Link>
                                        <!-- Información principal -->
                                        <div class="flex flex-col justify-between text-sm self-center">
                                            <div class="p-3 md:p-4 bg-white rounded border border-gray-200 shadow-sm w-full md:max-w-3xl">
                                                <div class="flex items-start justify-between gap-2 mb-2">
                                                    <h3 class="text-lg font-semibold text-gray-800 mb-3">
                                                        {{ inscripcion.actividad.nombre }}
                                                    </h3>
                                                    <p class="text-sm text-gray-500 shrink-0">
                                                        Inscrito el: {{ new Date(inscripcion.created_at).toLocaleDateString() }}
                                                    </p>
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-700">
                                                    <p><strong>Fecha: </strong> {{ inscripcion.actividad.fecha_inicio_formateada }}</p>
                                                    <p><strong>Lugar: </strong> {{ inscripcion.actividad.entidad?.direccion }}</p>
                                                    <p><strong>Membresía: </strong> {{ inscripcion.membresia }}</p>
                                                    <p><strong>Precio General: </strong> ${{ formatMoney(inscripcion.precioGeneral) }}</p>
                                                    <p><strong>Monto a Pagar: </strong> <span class="text-green-600">${{ formatMoney(inscripcion.montoapagar) }}</span></p>
                                                    <p><strong>Estado de Pago: </strong>
                                                        <span :class="{
                                                            'text-green-600': inscripcion.pago === 'Saldado',
                                                            'text-yellow-600': inscripcion.pago === 'Parcial',
                                                            'text-yellow-600': inscripcion.pago === 'Pendiente'
                                                        }">
                                                            {{ inscripcion.pago }}
                                                        </span>
                                                    </p>
                                                    <p v-if="inscripcion.asistencia !== 'Pendiente'">
                                                        <strong>Asistencia:</strong> {{ inscripcion.asistencia }}
                                                    </p>
                                                    <p><strong>Online:</strong> {{ inscripcion.online ? 'Sí' : 'No' }}</p>
                                                    <p v-if="inscripcion.envioLinkStream && inscripcion.envioLinkStream !== 'No aplica'"><strong>Stream:</strong> {{ inscripcion.envioLinkStream }}</p>
                                                    <p v-if="inscripcion.envioGrabación && inscripcion.envioGrabación !== 'No aplica'"><strong>Grabación:</strong> {{ inscripcion.envioGrabación }}</p>
                                                </div>
                                                <div v-if="inscripcion.hospedaje || inscripcion.comida || inscripcion.transporte" class="mt-2">
                                                    <p class="text-sm font-medium text-gray-700">Servicios Adicionales:</p>
                                                    <ul class="text-sm text-gray-600 ml-4">
                                                        <li v-if="inscripcion.hospedaje">Hospedaje: {{ inscripcion.hospedaje.nombre }}</li>
                                                        <li v-if="inscripcion.comida">Comida: {{ inscripcion.comida.nombre }}</li>
                                                        <li v-if="inscripcion.transporte">Transporte: {{ inscripcion.transporte.nombre }}</li>
                                                    </ul>
                                                </div>
                                            <div class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                                <span><strong>Comprobante: </strong></span>
                                                <button
                                                    v-if="urlComprobante(inscripcion)"
                                                    type="button"
                                                    @click="abrirComprobante(urlComprobante(inscripcion))"
                                                    class="inline-flex items-center justify-center text-indigo-600 hover:text-indigo-700"
                                                    title="Ver comprobante"
                                                >
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                                <span v-else class="text-xs text-gray-400">Sin comprobante</span>
                                            </div>
                                            </div>
                                        </div>

                                        <!-- Fecha de inscripción y acciones -->
                                        <div class="text-right flex flex-col items-end justify-between ">
                                            <div class="mt-2 flex flex-col gap-2 w-full items-end">
                                                <div class="flex flex-wrap justify-end gap-2 p-2 bg-white border border-gray-200 rounded shadow-sm">
                                                    <Link
                                                        v-if="inscripcion.pago !== 'Pendiente'"
                                                        :href="route('inscripciones.ticket', { inscripcion: inscripcion.id })"
                                                        class="w-24 px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-700 transition-colors text-center"
                                                        title="Ver Ticket"
                                                    >
                                                        Ver Ticket
                                                    </Link>
                                                    <button 
                                                        @click="confirmDelete(inscripcion.id)"
                                                        class="w-24 px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-700 transition-colors"
                                                        title="Eliminar inscripción"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </div>
                                                <div
                                                    v-if="inscripcion.pago === 'Pendiente'"
                                                    class="flex flex-wrap justify-end gap-2 p-2 bg-white border border-gray-200 rounded shadow-sm"
                                                >
                                                    <button
                                                        type="button"
                                                        disabled
                                                        class="w-24 px-3 py-1 bg-green-600 text-white text-sm rounded opacity-70 cursor-not-allowed text-center inline-flex items-center justify-center"
                                                        title="Disponible próximamente"
                                                    >
                                                        Pagar
                                                    </button>
                                                    <button
                                                        @click="openComprobanteModal(inscripcion)"
                                                        class="w-24 px-3 py-1 bg-indigo-500 text-white text-sm rounded hover:bg-indigo-700 transition-colors"
                                                    >
                                                        Informar pago
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Mensaje cuando no hay inscripciones -->
                        <template #empty>
                            <div class="text-center py-8">
                                <p class="text-gray-500 text-lg">No tienes inscripciones registradas.</p>
                                <p class="text-gray-400 mt-2">¡Inscríbete en una actividad para comenzar!</p>
                            </div>
                        </template>
                        </DataView>
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
                    <div class="max-h-[70vh] overflow-y-auto">
                        <template v-if="comprobanteIsPdf">
                            <iframe :src="comprobanteUrl" class="w-full h-[60vh] rounded"></iframe>
                        </template>
                        <template v-else>
                            <img v-if="comprobanteUrl" :src="comprobanteUrl" class="w-full rounded" alt="Comprobante" />
                        </template>
                    </div>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>







