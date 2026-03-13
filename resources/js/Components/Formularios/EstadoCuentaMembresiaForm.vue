<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    estadoCuenta: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
});

defineEmits(['submit', 'open-comprobante']);

const modosPago = [
    'Efectivo',
    'Transferencia',
    'Suscripción',
    'Tarjeta Crédito',
    'Tarjeta Débito',
    'Otro',
];

const formatearMes = (mesPagado) => {
    const [year, month] = mesPagado.split('-');
    const fecha = new Date(year, parseInt(month, 10) - 1);
    return fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
};
</script>

<template>
    <div class="p-6 text-gray-900">
        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-info-circle mr-2 text-indigo-600"></i>
                Informacion
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Usuario</p>
                    <p class="text-lg font-semibold text-gray-800">{{ estadoCuenta.user?.name || '-' }}</p>
                    <p class="text-sm text-gray-600">{{ estadoCuenta.user?.email || '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Membresia</p>
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

        <form @submit.prevent="$emit('submit')">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Estado de Pago
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input
                            v-model="form.pagado"
                            type="radio"
                            :value="false"
                            class="mr-2"
                        >
                        <span class="text-gray-700">Pendiente</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            v-model="form.pagado"
                            type="radio"
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
                    v-model="form.fecha_pago"
                    type="date"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Modo de Pago
                </label>
                <select
                    v-model="form.modo"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">Seleccionar modo</option>
                    <option v-for="modo in modosPago" :key="modo" :value="modo">
                        {{ modo }}
                    </option>
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Informacion de pago
                </label>
                <input
                    v-model="form.info_pago"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Texto breve de informacion de pago"
                >
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Comprobante
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                        @click="$emit('open-comprobante')"
                    >
                        <i class="fas fa-upload mr-2"></i>
                        Adjuntar comprobante
                    </button>
                    <span v-if="estadoCuenta.comprobante" class="text-sm text-gray-600">
                        Archivo actual: {{ estadoCuenta.comprobante }}
                    </span>
                </div>
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
</template>
