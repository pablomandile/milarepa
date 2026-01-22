<template>
    <AppLayout title="Registrar Membresía">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Registrar Membresía
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submitForm">
                            <!-- Información de la Membresía Seleccionada -->
                            <div v-if="membresiaSeleccionada" class="mb-8 pb-8 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">Membresía Seleccionada</h3>
                                <div class="bg-indigo-50 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600">Nombre</p>
                                    <p class="text-lg font-semibold text-indigo-600 mb-3">{{ membresiaSeleccionada.nombre }}</p>
                                    
                                    <p class="text-sm text-gray-600">Entidad</p>
                                    <p class="text-lg font-semibold text-gray-800 mb-3">{{ membresiaSeleccionada.entidad.nombre }}</p>
                                    
                                    <p class="text-sm text-gray-600">Descripción</p>
                                    <p class="text-gray-700">{{ membresiaSeleccionada.descripcion || 'Sin descripción' }}</p>
                                </div>
                            </div>

                            <!-- Fecha de Inicio -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    Fecha de Inicio
                                </label>
                                <input 
                                    type="date" 
                                    v-model="form.fecha_inicio"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                >
                                <p v-if="errors.fecha_inicio" class="text-red-600 text-sm mt-1">
                                    {{ errors.fecha_inicio[0] }}
                                </p>
                            </div>

                            <!-- Cantidad de Meses -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-hourglass-end mr-2"></i>
                                    Cantidad de Meses
                                </label>
                                <input 
                                    type="number" 
                                    v-model.number="form.cantidad_meses"
                                    min="1"
                                    max="36"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                >
                                <p class="text-sm text-gray-600 mt-1">Mínimo 1 mes, máximo 36 meses</p>
                                <p v-if="errors.cantidad_meses" class="text-red-600 text-sm mt-1">
                                    {{ errors.cantidad_meses[0] }}
                                </p>
                            </div>

                            <!-- Importe (Opcional) -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-dollar-sign mr-2"></i>
                                    Importe Mensual (Opcional)
                                </label>
                                <input 
                                    type="number" 
                                    v-model.number="form.importe"
                                    min="0"
                                    step="0.01"
                                    placeholder="Si no ingresa, se usará el precio configurado"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                <p v-if="errors.importe" class="text-red-600 text-sm mt-1">
                                    {{ errors.importe[0] }}
                                </p>
                            </div>

                            <!-- Resumen -->
                            <div v-if="form.cantidad_meses && form.fecha_inicio" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Resumen de Registro</h4>
                                <p class="text-sm text-blue-800 mb-1">
                                    <strong>Duración:</strong> {{ form.cantidad_meses }} mes(es)
                                </p>
                                <p class="text-sm text-blue-800 mb-1">
                                    <strong>Desde:</strong> {{ formatearFecha(form.fecha_inicio) }}
                                </p>
                                <p class="text-sm text-blue-800">
                                    <strong>Hasta:</strong> {{ formatearFecha(calcularFechaFinal()) }}
                                </p>
                            </div>

                            <!-- Botones -->
                            <div class="flex gap-4">
                                <button 
                                    type="submit"
                                    :disabled="processing"
                                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium disabled:opacity-50"
                                >
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Confirmar Registro
                                </button>
                                <Link 
                                    href="/registromembresias"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-medium"
                                >
                                    <i class="fas fa-times mr-2"></i>
                                    Cancelar
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    membresiaSeleccionada: Object
});

const form = useForm({
    membresia_id: props.membresiaSeleccionada ? props.membresiaSeleccionada.id : null,
    fecha_inicio: '',
    cantidad_meses: 12,
    importe: null
});

const errors = reactive({});
const processing = computed(() => form.processing);

const formatearFecha = (fecha) => {
    if (!fecha) return '';
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha + 'T00:00:00').toLocaleDateString('es-ES', opciones);
};

const calcularFechaFinal = () => {
    if (!form.fecha_inicio || !form.cantidad_meses) return null;
    const fecha = new Date(form.fecha_inicio + 'T00:00:00');
    fecha.setMonth(fecha.getMonth() + form.cantidad_meses - 1);
    // Ir al último día del mes
    const ultimoDia = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);
    return ultimoDia.toISOString().split('T')[0];
};

const submitForm = () => {
    form.post(route('registromembresias.store'), {
        onError: (errors) => {
            Object.assign(errors, errors);
        }
    });
};
</script>
