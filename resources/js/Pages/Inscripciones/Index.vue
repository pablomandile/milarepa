<script setup>
import { ref } from 'vue';
import DataView from 'primevue/dataview';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';

const $page = usePage();

const props = defineProps({
  inscripciones: {
    type: Array,
    required: true,
    default: () => []
  }
});

const layout = ref('list'); // Usar layout de lista
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Mis Inscripciones</h1>
        </template>
        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
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
                                    class="col-12 p-4 border border-gray-200 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors"
                                >
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <!-- Información principal -->
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                                {{ inscripcion.actividad.nombre }}
                                            </h3>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm text-gray-600">
                                                <p><strong>Fecha:</strong> {{ inscripcion.actividad.fecha_inicio_formateada }}</p>
                                                <p><strong>Lugar:</strong> {{ inscripcion.actividad.entidad?.direccion }}</p>
                                                <p><strong>Membresía:</strong> {{ inscripcion.membresia }}</p>
                                                <p><strong>Precio General:</strong> ${{ inscripcion.precioGeneral }}</p>
                                                <p><strong>Monto a Pagar:</strong> ${{ inscripcion.montoapagar }}</p>
                                                <p><strong>Estado de Pago:</strong>
                                                    <span :class="{
                                                        'text-green-600': inscripcion.pago === 'total',
                                                        'text-yellow-600': inscripcion.pago === 'parcial',
                                                        'text-red-600': inscripcion.pago === 'impago'
                                                    }">
                                                        {{ inscripcion.pago }}
                                                    </span>
                                                </p>
                                                <p><strong>Estado:</strong> {{ inscripcion.estado.estado }}</p>
                                                <p><strong>Asistencia:</strong> {{ inscripcion.asistencia }}</p>
                                                <p><strong>Online:</strong> {{ inscripcion.online ? 'Sí' : 'No' }}</p>
                                                <p v-if="inscripcion.envioLinkStream"><strong>Link Stream:</strong> {{ inscripcion.envioLinkStream }}</p>
                                                <p v-if="inscripcion.envioGrabación"><strong>Grabación:</strong> {{ inscripcion.envioGrabación }}</p>
                                            </div>
                                            <div v-if="inscripcion.hospedaje || inscripcion.comida || inscripcion.transporte" class="mt-2">
                                                <p class="text-sm font-medium text-gray-700">Servicios Adicionales:</p>
                                                <ul class="text-sm text-gray-600 ml-4">
                                                    <li v-if="inscripcion.hospedaje">Hospedaje: {{ inscripcion.hospedaje.nombre }}</li>
                                                    <li v-if="inscripcion.comida">Comida: {{ inscripcion.comida.nombre }}</li>
                                                    <li v-if="inscripcion.transporte">Transporte: {{ inscripcion.transporte.nombre }}</li>
                                                </ul>
                                            </div>
                                            <p v-if="inscripcion.comprobante" class="text-sm text-gray-600 mt-2">
                                                <strong>Comprobante:</strong> {{ inscripcion.comprobante }}
                                            </p>
                                        </div>

                                        <!-- Fecha de inscripción -->
                                        <div class="mt-4 md:mt-0 md:ml-4 text-right">
                                            <p class="text-sm text-gray-500">
                                                Inscrito el: {{ new Date(inscripcion.created_at).toLocaleDateString() }}
                                            </p>
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
            </div>
        </div>
    </AppLayout>
</template>