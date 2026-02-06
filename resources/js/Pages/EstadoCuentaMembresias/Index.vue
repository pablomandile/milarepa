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
                        <div class="mb-4 flex items-center gap-3">
                            <label class="text-sm font-medium text-gray-700">Filtro</label>
                            <select
                                v-model="filtroPeriodo"
                                class="rounded border border-gray-300 px-4 py-1 text-sm w-56"
                            >
                                <option value="last1">Último mes</option>
                                <option value="all">Mostrar todo</option>
                            </select>
                        </div>

                        <div v-if="filtradas.length > 0" class="overflow-x-auto">
                            <table class="min-w-full border-collapse border border-gray-300">
                                <thead class="bg-indigo-300 text-white text-sm">
                                    <tr>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Usuario</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Membresía</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Mes</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Importe</th>
                                        <th class="border border-gray-300 px-2 py-2 text-center">Estado</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Modo</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">
                                            <i class="fas fa-calendar mr-1"></i>Pago
                                        </th>
                                        <th class="border border-gray-300 px-2 py-2 text-center">Comprobante</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Observaciones</th>
                                        <th class="border border-gray-300 px-2 py-2 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="cuenta in filtradas" :key="cuenta.id" class="hover:bg-gray-50">
                                        <td class="border border-gray-300 px-2 py-2">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800">{{ cuenta.user.name }}</p>
                                                <p class="text-xs text-gray-600">{{ cuenta.user.email }}</p>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800">
                                                    {{ cuenta.membresia.nombre }}
                                                    <span v-if="cuenta.user?.membresia_online" class="ml-2 text-xs font-semibold text-indigo-600">Online</span>
                                                </p>
                                                <p class="text-xs text-gray-600">{{ cuenta.membresia.entidad.nombre }}</p>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ formatearMes(cuenta.mes_pagado) }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            ${{ parseFloat(cuenta.importe).toFixed(2) }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center">
                                            <span v-if="cuenta.pagado" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Pagado
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Pendiente
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ cuenta.modo || '-' }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ cuenta.fecha_pago ? formatearFecha(cuenta.fecha_pago) : '-' }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center">
                                            <button
                                                v-if="cuenta.comprobante"
                                                @click="abrirComprobante(cuenta.comprobante)"
                                                class="inline-flex items-center justify-center px-2 py-1 text-indigo-600 hover:text-indigo-800"
                                                title="Ver comprobante"
                                            >
                                                <i class="fas fa-file"></i>
                                            </button>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ cuenta.observaciones || '-' }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center">
                                            <Link 
                                                v-if="$page.props.user.permissions.includes('update estado_cuenta_membresias')"
                                                :href="route('estado-cuenta-membresias.edit', cuenta.id)"
                                                class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition"
                                            >
                                                <i class="fas fa-edit mr-1"></i>
                                                Editar
                                            </Link>
                                            <span v-else class="inline-flex items-center px-3 py-1 bg-gray-300 text-gray-500 text-sm font-semibold rounded-md cursor-not-allowed">
                                                <i class="fas fa-edit mr-1"></i>
                                                Editar
                                            </span>
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
                        <div v-if="filtroPeriodo === 'all' && estadoCuentas.links.length > 3" class="mt-6 flex justify-center gap-2">
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

    <Dialog v-model:visible="comprobanteModal" modal header="Comprobante" :style="{ width: '600px' }">
        <div class="max-h-[70vh] overflow-y-auto">
            <template v-if="comprobanteIsPdf">
                <iframe :src="'/storage/' + comprobantePath" class="w-full h-[60vh] rounded"></iframe>
            </template>
            <template v-else>
                <img v-if="comprobantePath" :src="'/storage/' + comprobantePath" class="w-full rounded" alt="Comprobante" />
            </template>
        </div>
    </Dialog>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    estadoCuentas: Object
});

const filtroPeriodo = ref('last1');

const filtradas = computed(() => {
    const data = props.estadoCuentas?.data || [];
    if (filtroPeriodo.value === 'all') return data;
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    return data.filter((cuenta) => {
        if (!cuenta.mes_pagado) return false;
        const [year, month] = String(cuenta.mes_pagado).split('-');
        const fecha = new Date(Number(year), Number(month) - 1, 1);
        return fecha >= start;
    });
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

const comprobanteModal = ref(false);
const comprobantePath = ref('');
const comprobanteIsPdf = computed(() => (comprobantePath.value || '').toLowerCase().endsWith('.pdf'));

const abrirComprobante = (path) => {
    comprobantePath.value = path;
    comprobanteModal.value = true;
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>


