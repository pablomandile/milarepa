<template>
    <AppLayout title="Editar Estado de Cuenta">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Estado de Cuenta
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Información de la Membresía -->
                        <div class="mb-6 pb-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Información</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Usuario</p>
                                    <p class="text-lg font-semibold text-gray-800">{{ estadoCuenta.user.name }}</p>
                                    <p class="text-sm text-gray-600">{{ estadoCuenta.user.email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Membresía</p>
                                    <p class="text-lg font-semibold text-indigo-600">{{ estadoCuenta.membresia.nombre }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Entidad</p>
                                    <p class="text-lg font-semibold text-gray-800">{{ estadoCuenta.membresia.entidad.nombre }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Mes</p>
                                    <p class="text-lg font-semibold text-gray-800">{{ formatearMes(estadoCuenta.mes_pagado) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Importe</p>
                                    <p class="text-lg font-semibold text-gray-800">${{ parseFloat(estadoCuenta.importe).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario -->
                        <form @submit.prevent="guardarCambios">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Estado de Pago
                                </label>
                                <div class="flex gap-4">
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            v-model="form.pagado" 
                                            :value="false"
                                            class="mr-2"
                                        >
                                        <span class="text-gray-700">Impago</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            v-model="form.pagado" 
                                            :value="true"
                                            class="mr-2"
                                        >
                                        <span class="text-gray-700">Pagado</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fecha de Pago
                                </label>
                                <input 
                                    type="date" 
                                    v-model="form.fecha_pago"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                >
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Observaciones
                                </label>
                                <textarea 
                                    v-model="form.observaciones"
                                    rows="4"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Agrega notas o comentarios (opcional)"
                                />
                            </div>

                            <!-- Botones -->
                            <div class="flex gap-4">
                                <button 
                                    type="submit"
                                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                                >
                                    <i class="fas fa-save mr-2"></i>
                                    Guardar Cambios
                                </button>
                                <Link 
                                    href="/estado-cuenta-membresias"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-medium"
                                >
                                    <i class="fas fa-arrow-left mr-2"></i>
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
import { reactive } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    estadoCuenta: Object
});

const form = useForm({
    pagado: props.estadoCuenta.pagado,
    fecha_pago: props.estadoCuenta.fecha_pago,
    observaciones: props.estadoCuenta.observaciones || ''
});

const formatearMes = (mesPagado) => {
    const [year, month] = mesPagado.split('-');
    const fecha = new Date(year, parseInt(month) - 1);
    return fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
};

const guardarCambios = () => {
    form.put(route('estado-cuenta-membresias.update', props.estadoCuenta.id), {
        onSuccess: () => {
            // El redirect se maneja automáticamente
        }
    });
};
</script>
