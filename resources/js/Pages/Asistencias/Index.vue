<script>
export default {
    name: 'AsistenciasIndex'
}
</script>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';

const props = defineProps({
    asistencias: {
        type: Array,
        required: true,
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const asistenciasFiltradasMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    if (!term) return props.asistencias;
    return props.asistencias.filter((a) => {
        const campos = [
            a.usuario?.name,
            a.inscripcion?.actividad?.nombre,
            a.inscripcion_clase?.clase?.nombre,
            a.asistencia,
            String(a.id ?? ''),
            String(a.inscripcion_id ?? ''),
        ];
        return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
    });
});

const getEstadoClass = (estado) => {
    if (estado === 'Presente') {
        return 'bg-green-100 text-green-700';
    }
    if (estado === 'Ausente') {
        return 'bg-red-100 text-red-700';
    }
    return 'bg-yellow-100 text-yellow-700';
};

const toast = useToast();
const mostrarFormularioAsistencia = ref(false);
const leyendoQr = ref(false);
const errorQr = ref('');
const videoRef = ref(null);
let mediaStream = null;
let detector = null;
let scannerFrame = null;
let procesandoQr = false;

const toggleFormularioAsistencia = () => {
    mostrarFormularioAsistencia.value = !mostrarFormularioAsistencia.value;
    if (!mostrarFormularioAsistencia.value) {
        detenerCamara();
    }
};

const detenerCamara = () => {
    leyendoQr.value = false;
    procesandoQr = false;

    if (scannerFrame) {
        cancelAnimationFrame(scannerFrame);
        scannerFrame = null;
    }

    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }

    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
};

const registrarAsistenciaQr = async (qrContent) => {
    try {
        await axios.post(route('asistencias.registrar-qr'), {
            qr_content: qrContent,
        });

        toast.add({
            severity: 'success',
            summary: 'Asistencia',
            detail: 'Asistencia registrada correctamente.',
            life: 3500,
        });

        detenerCamara();
    } catch (error) {
        const message = error?.response?.data?.message || 'No se pudo registrar la asistencia.';
        errorQr.value = message;
        toast.add({
            severity: 'error',
            summary: 'Asistencia',
            detail: message,
            life: 4000,
        });
        procesandoQr = false;
    }
};

const loopScanner = async () => {
    if (!leyendoQr.value || !videoRef.value || !detector) {
        return;
    }

    try {
        if (!procesandoQr && videoRef.value.readyState >= 2) {
            const barcodes = await detector.detect(videoRef.value);
            const qr = barcodes?.find((item) => item.rawValue);

            if (qr?.rawValue) {
                procesandoQr = true;
                await registrarAsistenciaQr(qr.rawValue);
                return;
            }
        }
    } catch (error) {
        errorQr.value = 'No se pudo leer el QR en este dispositivo.';
    }

    scannerFrame = requestAnimationFrame(loopScanner);
};

const iniciarLecturaQr = async () => {
    errorQr.value = '';

    if (!(window.isSecureContext || window.location.hostname === 'localhost')) {
        errorQr.value = 'La cámara requiere HTTPS o localhost.';
        return;
    }

    if (!('BarcodeDetector' in window)) {
        errorQr.value = 'Tu dispositivo no soporta lectura QR en este navegador.';
        return;
    }

    try {
        detector = new window.BarcodeDetector({ formats: ['qr_code'] });
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
            },
            audio: false,
        });

        if (!videoRef.value) {
            return;
        }

        videoRef.value.srcObject = mediaStream;
        await videoRef.value.play();
        leyendoQr.value = true;
        procesandoQr = false;
        loopScanner();
    } catch (error) {
        errorQr.value = 'No se pudo abrir la cámara. Verifica permisos del navegador.';
        detenerCamara();
    }
};

onBeforeUnmount(() => {
    detenerCamara();
});
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <Toast position="top-right" />
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Asistencias</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="mb-4">
                        <button
                            type="button"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                            @click="toggleFormularioAsistencia"
                        >
                            Tomar Asistencia
                        </button>
                    </div>

                    <div v-if="mostrarFormularioAsistencia" class="mb-6 rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-4">
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Escaneo de Ticket</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use el botón para abrir la cámara y leer el QR del ticket.</p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="text-white bg-green-600 hover:bg-green-700 py-2 px-4 rounded"
                                @click="iniciarLecturaQr"
                                :disabled="leyendoQr"
                            >
                                Leer QR
                            </button>
                            <button
                                v-if="leyendoQr"
                                type="button"
                                class="text-white bg-red-600 hover:bg-red-700 py-2 px-4 rounded"
                                @click="detenerCamara"
                            >
                                Detener cámara
                            </button>
                        </div>

                        <div v-if="errorQr" class="mt-3 rounded-md bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">
                            {{ errorQr }}
                        </div>

                        <div v-if="leyendoQr" class="mt-4 overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600 bg-black">
                            <video ref="videoRef" class="w-full max-h-80 object-cover" autoplay muted playsinline></video>
                        </div>
                    </div>

                    <!-- Buscador móvil -->
                    <div v-if="asistencias.length > 0" class="sm:hidden mb-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="asistenciasFiltradasMobile.length > 0" class="space-y-4 sm:hidden">
                        <div
                            v-for="asistencia in asistenciasFiltradasMobile"
                            :key="asistencia.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-clipboard-check text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ asistencia.usuario?.name || '-' }}</p>
                                        <p v-if="asistencia.inscripcion?.actividad?.nombre" class="text-sm text-gray-600 dark:text-gray-400">{{ asistencia.inscripcion.actividad.nombre }}</p>
                                    </div>
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold flex-shrink-0"
                                        :class="getEstadoClass(asistencia.asistencia)"
                                    >
                                        {{ asistencia.asistencia }}
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Clase</span>
                                        <span class="text-right">{{ asistencia.inscripcion_clase?.clase?.nombre || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Inscripción #</span>
                                        <span class="text-right">{{ asistencia.inscripcion_id || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Fecha</span>
                                        <span class="text-right">{{ new Date(asistencia.created_at).toLocaleDateString('es-AR') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="asistencias.length > 0" class="sm:hidden text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <DataTable
                        :value="asistencias"
                        v-model:filters="filters"
                        :globalFilterFields="['usuario.name', 'inscripcion.actividad.nombre', 'inscripcion_clase.clase.nombre', 'asistencia']"
                        stripedRows
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[10, 20, 50]"
                        tableStyle="min-width: 60rem"
                        class="hidden sm:block"
                    >
                        <template #header>
                            <div class="flex justify-end">
                                <IconField iconPosition="right">
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                </IconField>
                            </div>
                        </template>
                        <Column field="id" header="#" style="width: 80px" />

                        <Column field="inscripcion_id" header="Inscripción" />

                        <Column header="Clase">
                            <template #body="slotProps">
                                {{ slotProps.data.inscripcion_clase?.clase?.nombre || '-' }}
                            </template>
                        </Column>

                        <Column header="Usuario">
                            <template #body="slotProps">
                                {{ slotProps.data.usuario?.name || '-' }}
                            </template>
                        </Column>

                        <Column header="Actividad">
                            <template #body="slotProps">
                                {{ slotProps.data.inscripcion?.actividad?.nombre || '-' }}
                            </template>
                        </Column>

                        <Column field="asistencia" header="Asistencia">
                            <template #body="slotProps">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="getEstadoClass(slotProps.data.asistencia)"
                                >
                                    {{ slotProps.data.asistencia }}
                                </span>
                            </template>
                        </Column>

                        <Column header="Fecha">
                            <template #body="slotProps">
                                {{ new Date(slotProps.data.created_at).toLocaleDateString('es-AR') }}
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
