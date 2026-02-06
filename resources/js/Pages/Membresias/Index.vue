<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Swal from 'sweetalert2';

const page = usePage();
const isAsistant = computed(() => {
    const roles = (page.props.user?.roles || []).map((role) => String(role).toLowerCase());
    return roles.includes('asistant');
});

const props = defineProps({
    membresias: {
        type: Object,
        required: true
    },
    user_membresia: {
        type: Object,
        default: null
    },
    estado_cuenta: {
        type: Object,
        default: null
    },
    estados_cuenta: {
        type: Array,
        default: () => []
    }
});

const layout = ref('grid');
const showConfirmDialog = ref(false);
const membresiaPendiente = ref(null);
const modalidad = ref('PRESENCIAL');
const motivoOnline = ref('');
const comprobanteModal = ref(false);
const comprobanteFile = ref(null);
const isUploading = ref(false);
const mesImputar = ref('');

const userMembresia = props.user_membresia;

const inscrbirme = (membresia) => {
    const userActive = userMembresia;

    if (userActive && userActive.id === membresia.id) {
        // Si es la misma membresía
        Swal.fire('Información', 'Ya tienes esta membresía activa', 'info');
    } else {
        // Mostrar modal de confirmación con modalidad
        membresiaPendiente.value = membresia;
        modalidad.value = 'PRESENCIAL';
        motivoOnline.value = '';
        showConfirmDialog.value = true;
    }
};

const confirmarCambio = () => {
    router.post(route('membresias.subscribe'), {
        membresia_id: membresiaPendiente.value.id,
        modalidad: modalidad.value,
        motivo_online: motivoOnline.value
    }, {
        onSuccess: () => {
            showConfirmDialog.value = false;
            Swal.fire('¡Éxito!', 'Tu membresía ha sido actualizada', 'success');
        },
        onError: () => {
            Swal.fire('Error', 'Hubo un problema al cambiar la membresía', 'error');
        }
    });
};

const isDisabledButton = (membresia) => {
    return userMembresia && userMembresia.id === membresia.id;
};

const formatoMes = (mesPagado) => {
    if (!mesPagado) return '';
    const [year, month] = String(mesPagado).split('-');
    const fecha = new Date(Number(year), Number(month) - 1);
    return fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
};

const mesActualKey = computed(() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
});

const estadoMesActual = computed(() => {
    return props.estados_cuenta.find((estado) => estado.mes_pagado === mesActualKey.value) || null;
});

const mesesDisponibles = computed(() => {
    const ahora = new Date();
    const year = ahora.getFullYear();
    const startMonth = ahora.getMonth();
    const meses = [];
    for (let m = startMonth; m < 12; m += 1) {
        const fecha = new Date(year, m, 1);
        const value = `${year}-${String(m + 1).padStart(2, '0')}`;
        const label = fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
        meses.push({ value, label });
    }
    return meses;
});

const estadoMesSeleccionado = computed(() => {
    if (!mesImputar.value) return null;
    return props.estados_cuenta.find((estado) => estado.mes_pagado === mesImputar.value) || null;
});

const mesPagado = computed(() => !!estadoMesSeleccionado.value?.pagado);

function seleccionarComprobante(event) {
    comprobanteFile.value = event.target.files?.[0] || null;
}

async function subirComprobante() {
    if (!comprobanteFile.value) return;
    if (!mesImputar.value) {
        Swal.fire('Mes', 'Seleccioná a qué mes imputar el pago.', 'info');
        return;
    }
    if (mesPagado.value) {
        Swal.fire('Información', 'Ya tiene el pago registrado para ese mes.', 'info');
        return;
    }
    isUploading.value = true;
    try {
        const data = new FormData();
        data.append('comprobante', comprobanteFile.value);
        data.append('mes_pagado', mesImputar.value);
        if (props.estado_cuenta?.id) {
            data.append('estado_cuenta_id', props.estado_cuenta.id);
        }
        await axios.post(route('estado-cuenta-membresias.comprobante'), data);
        comprobanteModal.value = false;
        comprobanteFile.value = null;
        mesImputar.value = '';
        Swal.fire('Comprobante', 'Comprobante subido correctamente', 'success');
    } catch (error) {
        const mensaje = error?.response?.data?.message || error?.response?.data?.errors?.comprobante?.[0] || 'No se pudo subir el comprobante.';
        Swal.fire('Error', mensaje, 'error');
    } finally {
        isUploading.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                <div v-if="isAsistant" class="mb-4">
                    <Link
                        :href="route('asistant.panel')"
                        class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
                    >
                        Volver al panel
                    </Link>
                </div>
                <div class="bg-white border-round shadow-1">
                    <div class="p-6 border-bottom-1 border-200">
                        <div class="flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="text-2xl font-bold text-900 m-0">Membresías Disponibles</h2>
                                <p v-if="userMembresia" class="text-base text-600 mt-2">
                                    <span class="font-semibold">Tu membresía actual: </span>
                                    <span class="font-bold text-green-600">
                                        {{ userMembresia.nombre }}
                                        <span v-if="page.props.auth?.user?.membresia_online" class="ml-2 text-xs font-semibold text-indigo-600">ONLINE</span>
                                    </span>
                                </p>
                                <template v-if="userMembresia">
                                    <p v-if="estadoMesActual" class="text-sm mt-1" :class="estadoMesActual.pagado ? 'text-green-700' : 'text-amber-700'">
                                        Pago de {{ formatoMes(estadoMesActual.mes_pagado) }} {{ estadoMesActual.pagado ? 'aprobado' : 'pendiente' }}
                                    </p>
                                    <p v-else class="text-sm mt-1 text-amber-700">
                                        Pago de {{ formatoMes(mesActualKey) }} pendiente
                                    </p>
                                </template>
                                <div v-if="userMembresia" class="flex gap-2 mt-3">
                                    <button
                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition"
                                    >
                                        <i class="pi pi-credit-card mr-2"></i>
                                        Pagar
                                    </button>
                                    <button
                                        @click="comprobanteModal = true"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition"
                                    >
                                        <i class="pi pi-upload mr-2"></i>
                                        Informar Pago
                                    </button>
                                </div>
                                <p v-else class="text-base text-600 mt-2">
                                    No tienes una membresía activa. ¡Elige una y únete ahora!
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="layout = 'grid'"
                                    :class="['p-button p-button-text p-button-rounded', layout === 'grid' ? 'p-button-primary' : 'p-button-secondary']"
                                >
                                    <i class="pi pi-th-large"></i>
                                </button>
                                <button
                                    @click="layout = 'list'"
                                    :class="['p-button p-button-text p-button-rounded', layout === 'list' ? 'p-button-primary' : 'p-button-secondary']"
                                >
                                    <i class="pi pi-list"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <DataView
                                :value="membresias.data"
                                :layout="layout"
                                paginator
                                :rows="9"
                                :rowsPerPageOptions="[3, 6, 9]"
                                class="mb-6"
                            >
                                <template #grid="slotProps">
                                    <div class="grid grid-nogutter">
                                        <div
                                            v-for="(membresia, index) in slotProps.items"
                                            :key="membresia.id"
                                            class="col-12 md:col-6 xl:col-4 p-2"
                                        >
                                            <div class="p-card bg-white border-1 surface-border border-round shadow-2 hover:shadow-4 transition-all transition-duration-300">
                                                <div class="p-card-body p-4">
                                                    <div class="flex flex-col h-full">
                                                        <div class="flex align-items-center mb-3">
                                                            <div class="bg-primary-100 border-circle w-3rem h-3rem flex align-items-center justify-content-center mr-3">
                                                                <i class="pi pi-heart text-primary text-xl"></i>
                                                            </div>
                                                            <div class="flex-1">
                                                                <h3 class="p-card-title text-lg font-bold text-900 m-0">
                                                                    {{ membresia.nombre }}
                                                                </h3>
                                                            </div>
                                                        </div>
                                                        <p class="p-card-subtitle text-sm text-600 mb-3 flex-1">
                                                            {{ membresia.descripcion || 'Sin descripción disponible' }}
                                                        </p>
                                                        <div class="flex align-items-center mb-4">
                                                            <i class="pi pi-building text-400 mr-2"></i>
                                                            <span class="text-sm text-500">
                                                                <strong>{{ membresia.entidad?.nombre || 'No especificada' }}</strong>
                                                            </span>
                                                        </div>
                                                        <div class="mt-auto">
                                                            <button
                                                                @click="inscrbirme(membresia)"
                                                                :disabled="isDisabledButton(membresia)"
                                                                :class="[
                                                                    'w-full py-2 px-4 rounded-md font-medium transition-colors flex items-center justify-center gap-2',
                                                                    isDisabledButton(membresia) 
                                                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                                                                        : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                                ]"
                                                            >
                                                                <i class="pi pi-plus-circle"></i>
                                                                {{ isDisabledButton(membresia) ? 'Mi membresía actual' : 'Inscribirme' }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #list="slotProps">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div
                                            v-for="(membresia, index) in slotProps.items"
                                            :key="membresia.id"
                                            class="col-12 p-card bg-white border-1 surface-border border-round shadow-2 hover:shadow-4 transition-all transition-duration-300"
                                        >
                                            <div class="p-card-body p-4">
                                                <div class="flex flex-col md:flex-row md:align-items-center md:justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex align-items-center mb-3">
                                                            <div class="bg-primary-100 border-circle w-3rem h-3rem flex align-items-center justify-content-center mr-3">
                                                                <i class="pi pi-heart text-primary text-xl"></i>
                                                            </div>
                                                            <div>
                                                                <h3 class="p-card-title text-lg font-bold text-900 m-0">
                                                                    {{ membresia.nombre }}
                                                                </h3>
                                                                <div class="flex align-items-center mt-1">
                                                                    <i class="pi pi-building text-400 mr-2"></i>
                                                                    <span class="text-sm text-500">
                                                                        {{ membresia.entidad?.nombre || 'No especificada' }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <p class="p-card-subtitle text-sm text-600">
                                                            {{ membresia.descripcion || 'Sin descripción disponible' }}
                                                        </p>
                                                    </div>
                                                    <div class="mt-4 md:mt-0 md:ml-4">
                                                        <button
                                                            @click="inscrbirme(membresia)"
                                                            :disabled="isDisabledButton(membresia)"
                                                            :class="[
                                                                'py-2 px-4 rounded-md font-medium transition-colors flex items-center justify-center gap-2',
                                                                isDisabledButton(membresia) 
                                                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                                                                    : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                            ]"
                                                        >
                                                            <i class="pi pi-plus-circle"></i>
                                                            {{ isDisabledButton(membresia) ? 'Mi membresía actual' : 'Inscribirme' }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #empty>
                                    <div class="text-center py-8 px-4">
                                        <div class="bg-gray-50 border-round p-6 border-1 border-dashed border-300">
                                            <i class="pi pi-info-circle text-4xl text-400 mb-4 block"></i>
                                            <h3 class="text-xl font-medium text-700 mb-2">No hay membresías disponibles</h3>
                                            <p class="text-600 m-0">En este momento no tenemos membresías activas.</p>
                                        </div>
                                    </div>
                                </template>
                            </DataView>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modal de confirmación para cambio de membresía -->
        <Dialog 
        v-model:visible="showConfirmDialog" 
        modal 
        :header="membresiaPendiente ? membresiaPendiente.nombre : 'Membres�a'"
        :style="{ width: '34rem' }"
        :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
    >
        <template v-if="membresiaPendiente">
            <div class="flex flex-col gap-4">
                <p v-if="userMembresia" class="text-sm text-gray-700">
                    Ud cambiará de membresía, actual: <strong>{{ userMembresia.nombre }}</strong>
                </p>

                <div>
                    <p class="text-sm font-semibold text-gray-800 mb-2">Modalidad</p>
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" value="PRESENCIAL" v-model="modalidad" />
                            PRESENCIAL
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" value="ONLINE" v-model="modalidad" />
                            ONLINE
                        </label>
                    </div>
                </div>

                <div v-if="modalidad === 'ONLINE'">
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        ¿Puedes comentarnos el impedimento para concurrir presencialmente?
                    </label>
                    <input
                        v-model="motivoOnline"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                        placeholder="Escribe tu motivo"
                    />
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end space-x-2">
                <button 
                    @click="showConfirmDialog = false"
                    class="py-2 px-4 rounded-md font-medium transition-colors bg-gray-500 text-white hover:bg-gray-600"
                >
                    Cancelar
                </button>
                <button 
                    @click="confirmarCambio"
                    class="py-2 px-4 rounded-md font-medium transition-colors bg-green-600 text-white hover:bg-green-700"
                >
                    Terminar
                </button>
            </div>
        </template>
    </Dialog>

    <Dialog
        v-model:visible="comprobanteModal"
        modal
        header="Informar pago"
        :style="{ width: '500px' }"
    >
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    A qué mes imputar el pago
                </label>
                <select
                    v-model="mesImputar"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                >
                    <option value="" disabled>Seleccioná un mes</option>
                    <option v-for="mes in mesesDisponibles" :key="mes.value" :value="mes.value">
                        {{ mes.label }}
                    </option>
                </select>
                <p v-if="mesPagado" class="mt-2 text-sm text-amber-700">
                    Ya tiene una membresía paga en ese mes.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Comprobante (PDF, JPG, PNG)
                </label>
                <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="seleccionarComprobante" />
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <button class="px-4 py-2 bg-gray-500 text-white rounded" @click="comprobanteModal = false">
                    Cancelar
                </button>
                <button
                    class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-60"
                    :disabled="isUploading || !comprobanteFile || mesPagado || !mesImputar"
                    @click="subirComprobante"
                >
                    {{ isUploading ? 'Subiendo...' : 'Subir' }}
                </button>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.p-card {
    transition: all 0.3s ease;
}

.p-card:hover {
    transform: translateY(-2px);
}

.p-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.p-card-title {
    color: #374151;
    font-weight: 700;
}

.p-card-subtitle {
    color: #6b7280;
    margin-bottom: 1rem;
}

.p-button {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.p-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>



