<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
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
const pagoEfectivo = ref(false);
const fallbackMembresiaImage = '/storage/img/actividades/imagen-no-disponible.jpg';
const flippedCards = ref({});

const userMembresia = props.user_membresia;

const inscrbirme = (membresia) => {
    const userActive = userMembresia;

    if (userActive && userActive.id === membresia.id) {
        // Si es la misma membresÃ­a
        Swal.fire('InformaciÃ³n', 'Ya tienes esta membresÃ­a activa', 'info');
    } else {
        // Mostrar modal de confirmaciÃ³n con modalidad
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
            Swal.fire('Â¡Ã‰xito!', 'Tu membresÃ­a ha sido actualizada', 'success');
        },
        onError: () => {
            Swal.fire('Error', 'Hubo un problema al cambiar la membresÃ­a', 'error');
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

const pagoPendiente = computed(() => {
    return !!userMembresia && !estadoMesActual.value?.pagado;
});

const botonPagoLink = computed(() => {
    return userMembresia?.boton_pago?.link || '';
});

const mesesDisponibles = computed(() => {
    const ahora = new Date();
    const year = ahora.getFullYear();
    const startMonth = ahora.getMonth();
    const mesesConPagoInformado = new Set(
        (props.estados_cuenta || [])
            .filter((estado) => Boolean(estado?.pagado) || Boolean(estado?.comprobante))
            .map((estado) => estado.mes_pagado)
    );
    const meses = [];
    for (let m = startMonth; m < 12; m += 1) {
        const fecha = new Date(year, m, 1);
        const value = `${year}-${String(m + 1).padStart(2, '0')}`;
        if (mesesConPagoInformado.has(value)) {
            continue;
        }
        const label = fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
        meses.push({ value, label });
    }
    return meses;
});

const estadoMesSeleccionado = computed(() => {
    if (!mesImputar.value) return null;
    return props.estados_cuenta.find((estado) => estado.mes_pagado === mesImputar.value) || null;
});

const mesInformado = computed(() => {
    const estado = estadoMesSeleccionado.value;
    return !!estado && (!!estado.pagado || !!estado.comprobante);
});

const imagenMembresia = (membresia) => {
    if (membresia?.imagen?.ruta) {
        return `/storage/${membresia.imagen.ruta}`;
    }
    return fallbackMembresiaImage;
};

const toggleFlip = (id) => {
    flippedCards.value[id] = !flippedCards.value[id];
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatInlineMarkdown(value) {
    return value
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>');
}

function renderInfoMarkdown(value) {
    const safeText = escapeHtml(value).replace(/\r\n/g, '\n');
    const lines = safeText.split('\n');
    const html = [];
    let inList = false;

    for (const rawLine of lines) {
        const line = rawLine.trim();

        if (!line) {
            if (inList) {
                html.push('</ul>');
                inList = false;
            }
            continue;
        }

        if (line.startsWith('- ')) {
            if (!inList) {
                html.push('<ul class="list-disc pl-5 my-2 space-y-1">');
                inList = true;
            }
            html.push(`<li>${formatInlineMarkdown(line.slice(2).trim())}</li>`);
            continue;
        }

        if (inList) {
            html.push('</ul>');
            inList = false;
        }

        if (line.startsWith('### ')) {
            html.push(`<h5 class="text-sm font-semibold text-gray-900 mt-2">${formatInlineMarkdown(line.slice(4).trim())}</h5>`);
        } else if (line.startsWith('## ')) {
            html.push(`<h4 class="text-base font-semibold text-gray-900 mt-2">${formatInlineMarkdown(line.slice(3).trim())}</h4>`);
        } else if (line.startsWith('# ')) {
            html.push(`<h3 class="text-lg font-bold text-gray-900 mt-2">${formatInlineMarkdown(line.slice(2).trim())}</h3>`);
        } else {
            html.push(`<p class="text-xs text-gray-700 leading-relaxed mt-1">${formatInlineMarkdown(line)}</p>`);
        }
    }

    if (inList) {
        html.push('</ul>');
    }

    return html.join('');
}

function seleccionarComprobante(event) {
    comprobanteFile.value = event.target.files?.[0] || null;
}

async function subirComprobante() {
    if (!mesImputar.value) {
        Swal.fire('Mes', 'Seleccioná a qué mes imputar el pago.', 'info');
        return;
    }
    if (!comprobanteFile.value && !pagoEfectivo.value) {
        Swal.fire('Pago', 'Subí un comprobante o marcá que pagaste en efectivo.', 'info');
        return;
    }
    if (mesInformado.value) {
        Swal.fire('Información', 'Ya tiene un pago informado para ese mes.', 'info');
        return;
    }
    isUploading.value = true;
    try {
        const data = new FormData();
        if (comprobanteFile.value) {
            data.append('comprobante', comprobanteFile.value);
        }
        data.append('mes_pagado', mesImputar.value);
        data.append('modo', pagoEfectivo.value ? 'Efectivo' : 'Transferencia');
        if (props.estado_cuenta?.id) {
            data.append('estado_cuenta_id', props.estado_cuenta.id);
        }
        await axios.post(route('estado-cuenta-membresias.comprobante'), data);
        comprobanteModal.value = false;
        comprobanteFile.value = null;
        mesImputar.value = '';
        pagoEfectivo.value = false;
        Swal.fire('Pago', 'Pago informado correctamente', 'success');
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
                                    <a
                                        v-if="pagoPendiente && botonPagoLink"
                                        :href="botonPagoLink"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition"
                                    >
                                        <i class="pi pi-credit-card mr-2"></i>
                                        Pagar
                                    </a>
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
                                                        <div
                                                            class="mb-3 overflow-hidden rounded border border-gray-200 bg-gray-50 flip-card-container cursor-pointer"
                                                            :class="{ flipped: flippedCards[membresia.id] }"
                                                            @click="toggleFlip(membresia.id)"
                                                            :title="flippedCards[membresia.id] ? 'Ver imagen' : 'Ver info'"
                                                        >
                                                            <div class="flip-card-inner">
                                                                <div class="flip-card-front">
                                                                    <img
                                                                        :src="imagenMembresia(membresia)"
                                                                        :alt="`Imagen de ${membresia.nombre}`"
                                                                        class="h-full w-full object-contain"
                                                                    />
                                                                </div>
                                                                <div class="flip-card-back">
                                                                    <div class="h-full w-full overflow-y-auto rounded border border-gray-200 bg-white p-2">
                                                                        <div v-html="renderInfoMarkdown(membresia.info || 'Sin info cargada.')"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
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
                                                            {{ membresia.descripcion || 'Sin descripciÃ³n disponible' }}
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
                                                                {{ isDisabledButton(membresia) ? 'Mi membresÃ­a actual' : 'Inscribirme' }}
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
                                                        <div
                                                            class="mb-3 overflow-hidden rounded border border-gray-200 bg-gray-50 flip-card-container cursor-pointer"
                                                            :class="{ flipped: flippedCards[membresia.id] }"
                                                            @click="toggleFlip(membresia.id)"
                                                            :title="flippedCards[membresia.id] ? 'Ver imagen' : 'Ver info'"
                                                        >
                                                            <div class="flip-card-inner">
                                                                <div class="flip-card-front">
                                                                    <img
                                                                        :src="imagenMembresia(membresia)"
                                                                        :alt="`Imagen de ${membresia.nombre}`"
                                                                        class="h-full w-full object-contain"
                                                                    />
                                                                </div>
                                                                <div class="flip-card-back">
                                                                    <div class="h-full w-full overflow-y-auto rounded border border-gray-200 bg-white p-2">
                                                                        <div v-html="renderInfoMarkdown(membresia.info || 'Sin info cargada.')"></div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
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
                                                            {{ membresia.descripcion || 'Sin descripciÃ³n disponible' }}
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
                                                            {{ isDisabledButton(membresia) ? 'Mi membresÃ­a actual' : 'Inscribirme' }}
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
                                            <h3 class="text-xl font-medium text-700 mb-2">No hay membresÃ­as disponibles</h3>
                                            <p class="text-600 m-0">En este momento no tenemos membresÃ­as activas.</p>
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

    <!-- Modal de confirmaciÃ³n para cambio de membresÃ­a -->
        <Dialog 
        v-model:visible="showConfirmDialog" 
        modal 
        :header="membresiaPendiente ? membresiaPendiente.nombre : 'Membresï¿½a'"
        :style="{ width: '34rem' }"
        :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
    >
        <template v-if="membresiaPendiente">
            <div class="flex flex-col gap-4">
                <p v-if="userMembresia" class="text-sm text-gray-700">
                    Ud cambiarÃ¡ de membresÃ­a, actual: <strong>{{ userMembresia.nombre }}</strong>
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
                        Â¿Puedes comentarnos el impedimento para concurrir presencialmente?
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
                    ¿A qué mes imputar el pago?
                </label>
                <select
                    v-model="mesImputar"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                >
                    <option value="" disabled>Selecciona un mes</option>
                    <option v-for="mes in mesesDisponibles" :key="mes.value" :value="mes.value">
                        {{ mes.label }}
                    </option>
                </select>
                <p v-if="mesInformado" class="mt-2 text-sm text-amber-700">
                    Ya tiene un pago informado en ese mes.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Comprobante (PDF, JPG, PNG)
                </label>
                <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="seleccionarComprobante" />
            </div>
            <div class="flex items-center gap-2">
                <input
                    id="pago_efectivo"
                    v-model="pagoEfectivo"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label for="pago_efectivo" class="text-sm text-gray-700">Pagué en efectivo</label>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-end gap-2">
                <button class="px-4 py-2 bg-gray-500 text-white rounded" @click="comprobanteModal = false">
                    Cancelar
                </button>
                <button
                    class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-60"
                    :disabled="isUploading || (!(comprobanteFile || pagoEfectivo)) || mesInformado || !mesImputar"
                    @click="subirComprobante"
                >
                    {{ isUploading ? 'Informando...' : 'Informar' }}
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

.flip-card-container {
    perspective: 1000px;
    height: clamp(240px, 32vw, 340px);
}

.flip-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    transition: transform 0.6s;
    transform-style: preserve-3d;
}

.flip-card-container.flipped .flip-card-inner {
    transform: rotateY(180deg);
}

.flip-card-front,
.flip-card-back {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flip-card-back {
    transform: rotateY(180deg);
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
