<script setup>
import { ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { router, Link } from '@inertiajs/vue3';

const $page = usePage();

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
                                    class="col-12 p-4 border border-gray-200 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors h-48 overflow-hidden"
                                >
                                    <div class="flex flex-row gap-4 h-full">
                                        <!-- Imagen fija adaptada a la altura de la card -->
                                        <div class="w-40 md:w-56 h-full flex items-center justify-center bg-gray-100 rounded">
                                            <img
                                                v-if="inscripcion.actividad?.imagen"
                                                :src="'/storage/' + inscripcion.actividad.imagen.ruta"
                                                :alt="'Imagen de ' + inscripcion.actividad.nombre"
                                                class="max-w-full max-h-full object-contain"
                                            />
                                            <img
                                                v-else
                                                src="/storage/img/actividades/imagen-no-disponible.jpg"
                                                alt="Sin imagen"
                                                class="max-w-full max-h-full object-contain"
                                            />
                                        </div>
                                        <!-- Información principal -->
                                        <div class="flex-1 flex flex-col justify-between">
                                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                                {{ inscripcion.actividad.nombre }}
                                            </h3>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm text-gray-600">
                                                <p><strong>Fecha: </strong> {{ inscripcion.actividad.fecha_inicio_formateada }}</p>
                                                <p><strong>Lugar: </strong> {{ inscripcion.actividad.entidad?.direccion }}</p>
                                                <p><strong>Membresía: </strong> {{ inscripcion.membresia }}</p>
                                                <p><strong>Precio General: </strong> ${{ inscripcion.precioGeneral }}</p>
                                                <p><strong>Monto a Pagar: </strong> ${{ inscripcion.montoapagar }}</p>
                                                <p><strong>Estado de Pago: </strong>
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

                                        <!-- Fecha de inscripción y acciones -->
                                        <div class="md:ml-4 text-right flex flex-col items-end justify-between">
                                            <p class="text-sm text-gray-500">
                                                Inscrito el: {{ new Date(inscripcion.created_at).toLocaleDateString() }}
                                            </p>
                                            <!-- Botón Ver Ticket -->
                                            <Link
                                                :href="route('inscripciones.ticket', { inscripcion: inscripcion.id })"
                                                class="mt-2 px-3 py-1 bg-indigo-500 text-white text-sm rounded hover:bg-indigo-700 transition-colors"
                                                title="Ver Ticket"
                                            >
                                                <i class="pi pi-ticket"></i> Ver Ticket
                                            </Link>
                                            <!-- Botón de eliminar -->
                                            <button 
                                                @click="confirmDelete(inscripcion.id)"
                                                class="mt-2 px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-700 transition-colors"
                                                title="Eliminar inscripción"
                                            >
                                                <i class="pi pi-trash"></i> Eliminar
                                            </button>
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
            </div>
        </div>
    </AppLayout>
</template>