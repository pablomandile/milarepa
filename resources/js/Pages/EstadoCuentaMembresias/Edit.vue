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
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-info-circle mr-2 text-indigo-600"></i>
                                Información
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Usuario</p>
                                    <p class="text-lg font-semibold text-gray-800">{{ estadoCuenta.user?.name || '-' }}</p>
                                    <p class="text-sm text-gray-600">{{ estadoCuenta.user?.email || '-' }}</p>
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
                                        <span class="text-gray-700">Pendiente</span>
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
                                    Modo de Pago
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.modo"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ej: efectivo, transferencia, tarjeta"
                                >
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Comprobante
                                </label>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button
                                        type="button"
                                        @click="comprobanteModal = true"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
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

    <Dialog
        v-model:visible="comprobanteModal"
        modal
        header="Subir comprobante"
        :style="{ width: '500px' }"
    >
        <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="seleccionarComprobante" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <button class="px-4 py-2 bg-gray-500 text-white rounded" @click="comprobanteModal = false">
                    Cancelar
                </button>
                <button
                    class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-60"
                    :disabled="isUploading || !comprobanteFile"
                    @click="subirComprobante"
                >
                    {{ isUploading ? 'Subiendo...' : 'Subir' }}
                </button>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';

const props = defineProps({
    estadoCuenta: Object
});

const form = useForm({
    pagado: props.estadoCuenta.pagado,
    fecha_pago: props.estadoCuenta.fecha_pago ? String(props.estadoCuenta.fecha_pago).split('T')[0] : '',
    observaciones: props.estadoCuenta.observaciones || '',
    modo: props.estadoCuenta.modo || ''
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
watch(
    () => form.pagado,
    (nuevoValor) => {
        if (nuevoValor && !form.fecha_pago) {
            const today = new Date();
            form.fecha_pago = today.toISOString().split('T')[0];
        }
    }
);

const comprobanteModal = ref(false);
const comprobanteFile = ref(null);
const isUploading = ref(false);

function seleccionarComprobante(event) {
    comprobanteFile.value = event.target.files?.[0] || null;
}

async function subirComprobante() {
    if (!comprobanteFile.value) return;
    isUploading.value = true;
    try {
        const data = new FormData();
        data.append('comprobante', comprobanteFile.value);
        data.append('estado_cuenta_id', props.estadoCuenta.id);
        await axios.post(route('estado-cuenta-membresias.comprobante'), data);
        comprobanteModal.value = false;
        comprobanteFile.value = null;
    } catch (error) {
        const mensaje = error?.response?.data?.message || error?.response?.data?.errors?.comprobante?.[0] || 'No se pudo subir el comprobante.';
        alert(mensaje);
    } finally {
        isUploading.value = false;
    }
}
</script>
