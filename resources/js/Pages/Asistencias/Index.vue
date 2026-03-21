<script>
export default {
    name: 'AsistenciasIndex'
}
</script>

<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    asistencias: {
        type: Array,
        required: true,
    },
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Asistencias</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="mb-4">
                        <button
                            type="button"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                            @click="toggleFormularioAsistencia"
                        >
                            Tomar Asistencia
                        </button>
                    </div>

                    <div v-if="mostrarFormularioAsistencia" class="mb-6 rounded-md border border-gray-200 bg-gray-50 p-4">
                        <h2 class="text-base font-semibold text-gray-800">Escaneo de Ticket</h2>
                        <p class="mt-1 text-sm text-gray-600">Use el botón para abrir la cámara y leer el QR del ticket.</p>

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

                        <div v-if="leyendoQr" class="mt-4 overflow-hidden rounded-lg border border-gray-300 bg-black">
                            <video ref="videoRef" class="w-full max-h-80 object-cover" autoplay muted playsinline></video>
                        </div>
                    </div>

                    <DataTable
                        :value="asistencias"
                        stripedRows
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[10, 20, 50]"
                        tableStyle="min-width: 60rem"
                    >
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
