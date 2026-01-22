<template>
    <AppLayout title="Estado de Cuenta - Membresías">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    <i class="fas fa-receipt mr-2 text-indigo-600"></i>
                    Estado de Cuenta de Membresías - Sistema
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="w-full p-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Tabla de Estado de Cuentas -->
                        <div v-if="estadoCuentas.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-300">
                                <thead class="bg-indigo-600 text-white">
                                    <tr>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Usuario</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Membresía</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Mes</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Importe</th>
                                        <th class="border border-gray-300 px-6 py-3 text-center">Estado de Pago</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Fecha de Pago</th>
                                        <th class="border border-gray-300 px-6 py-3 text-left">Observaciones</th>
                                        <th class="border border-gray-300 px-6 py-3 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="cuenta in estadoCuentas.data" :key="cuenta.id" class="hover:bg-gray-50">
                                        <td class="border border-gray-300 px-6 py-4">
                                            <div>
                                                <p class="font-semibold text-gray-800">{{ cuenta.user.name }}</p>
                                                <p class="text-sm text-gray-600">{{ cuenta.user.email }}</p>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            <div>
                                                <p class="font-semibold text-gray-800">{{ cuenta.membresia.nombre }}</p>
                                                <p class="text-sm text-gray-600">{{ cuenta.membresia.entidad.nombre }}</p>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            {{ formatearMes(cuenta.mes_pagado) }}
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            ${{ parseFloat(cuenta.importe).toFixed(2) }}
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4 text-center">
                                            <span v-if="cuenta.pagado" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Pagado
                                            </span>
                                            <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Impago
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            {{ cuenta.fecha_pago ? formatearFecha(cuenta.fecha_pago) : '-' }}
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4">
                                            {{ cuenta.observaciones || '-' }}
                                        </td>
                                        <td class="border border-gray-300 px-6 py-4 text-center">
                                            <div class="flex justify-center gap-2">
                                                <Link 
                                                    :href="route('estado-cuenta-membresias.edit', cuenta.id)"
                                                    class="text-indigo-600 hover:text-indigo-900 transition"
                                                    title="Editar"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </Link>
                                                <button 
                                                    @click="eliminarRegistro(cuenta.id)"
                                                    class="text-red-600 hover:text-red-900 transition"
                                                    title="Eliminar"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Mensaje si no hay datos -->
                        <div v-else class="text-center py-12">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 text-lg">No hay registros de estado de cuenta</p>
                            <p class="text-gray-500 text-sm mt-2">
                                Los registros aparecerán cuando los usuarios se inscriban en membresías
                            </p>
                        </div>

                        <!-- Paginación -->
                        <div v-if="estadoCuentas.links.length > 3" class="mt-6 flex justify-center gap-2">
                            <Link 
                                v-for="link in estadoCuentas.links" 
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-4 py-2 rounded transition',
                                    link.active 
                                        ? 'bg-indigo-600 text-white' 
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

defineProps({
    estadoCuentas: Object
});

const formatearMes = (mesPagado) => {
    const [year, month] = mesPagado.split('-');
    const fecha = new Date(year, parseInt(month) - 1);
    return fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

const eliminarRegistro = (id) => {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Este registro será eliminado permanentemente',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('estado-cuenta-membresias.destroy', id));
        }
    });
};
</script>
