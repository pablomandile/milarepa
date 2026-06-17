<template>
    <AppLayout title="Estado de Cuenta - Membresías">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    <i class="fas fa-receipt mr-2 text-indigo-600"></i>
                    Estado de Cuenta de Membresías
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="w-full p-0 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-0 sm:p-6 text-gray-900 dark:text-gray-100">
                        <!-- Generar estados del mes próximo -->
                        <div
                            v-if="puedeGenerar && mesProximo"
                            class="mb-4 mx-4 sm:mx-0 flex flex-col md:flex-row md:items-center gap-3 p-4 rounded-md border"
                            :class="hayDesfase
                                ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-700'
                                : 'bg-gray-50 dark:bg-gray-700/40 border-gray-200 dark:border-gray-600'"
                        >
                            <div class="flex-1">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="text-gray-500 dark:text-gray-400">Último generado:</span>
                                    <span class="ml-1 font-medium text-gray-900 dark:text-gray-100 capitalize">
                                        {{ ultimoMesLabel || '— (sin estados aún)' }}
                                    </span>
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                    Próximo a generar:
                                    <span class="ml-1 font-semibold text-gray-900 dark:text-gray-100 capitalize">
                                        {{ mesProximoLabel }}
                                    </span>
                                    <span v-if="hayDesfase" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-200 text-amber-900">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Hay meses pendientes
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Crea un estado de cuenta por cada usuario con membresía activa. Los suscriptores quedan pagados automáticamente.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="generarEstados"
                                    :disabled="generando || revirtiendo"
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                                    {{ generando ? 'Generando…' : 'Generar estados de cuenta' }}
                                </button>
                                <button
                                    v-if="ultimoMesGenerado"
                                    @click="revertirUltima"
                                    :disabled="generando || revirtiendo"
                                    v-tooltip="`Revertir última generación (${ultimoMesLabel})`"
                                    class="inline-flex items-center justify-center px-3 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50">
                                    <i class="fas fa-rotate-left mr-1"></i>
                                    {{ revirtiendo ? 'Revirtiendo…' : 'Revertir' }}
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de Estado de Cuentas -->
                        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 sm:px-0">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtro</label>
                                <select
                                    v-model="filtroPeriodo"
                                    class="rounded border border-gray-300 dark:border-gray-600 px-4 py-1 text-sm w-56"
                                >
                                    <option value="last1">Mes actual</option>
                                    <option value="next1">Mes siguiente</option>
                                    <option value="all">Mostrar todo</option>
                                </select>
                            </div>
                            <IconField iconPosition="right" class="w-full sm:w-auto">
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="busquedaGlobal"
                                    placeholder="Buscar..."
                                    class="w-full sm:w-auto"
                                />
                            </IconField>
                        </div>

                        <p class="px-4 sm:px-0 mb-3 text-xs text-gray-500 dark:text-gray-400">
                            Mostrando {{ filtradas.length }} {{ filtradas.length === 1 ? 'registro' : 'registros' }}
                        </p>

                        <div v-if="filtradas.length > 0" class="space-y-4 sm:hidden">
                            <div
                                v-for="cuenta in filtradas"
                                :key="cuenta.id"
                                class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                            >
                                <div class="space-y-3 p-4">
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ cuenta.user.name }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ cuenta.user.email }}</p>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm text-gray-500">Membresia</span>
                                            <div class="text-right">
                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                    {{ cuenta.membresia.nombre }}
                                                    <span
                                                        v-if="cuenta.user?.membresia_online || cuenta.user?.membresia_usuario?.membresia_online"
                                                        class="ml-2 text-xs font-semibold text-indigo-600"
                                                    >
                                                        Online
                                                    </span>
                                                </p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ cuenta.membresia.entidad.nombre }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Mes</span>
                                            <span>{{ formatearMes(cuenta.mes_pagado) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Importe</span>
                                            <span>${{ parseFloat(cuenta.importe).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Estado</span>
                                            <span
                                                v-if="cuenta.pagado"
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                            >
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Pagado
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                            >
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Pendiente
                                            </span>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        @click="toggleCardExpanded(cuenta.id)"
                                    >
                                        <span>{{ isCardExpanded(cuenta.id) ? 'Ocultar detalles' : 'Ver mas detalles' }}</span>
                                        <i
                                            class="pi"
                                            :class="isCardExpanded(cuenta.id) ? 'pi-chevron-up' : 'pi-chevron-down'"
                                        ></i>
                                    </button>

                                    <div v-if="isCardExpanded(cuenta.id)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                                        <div class="grid grid-cols-1 gap-3 text-sm text-gray-700 dark:text-gray-300">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Modo</p>
                                                <p>{{ cuenta.modo || '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pago</p>
                                                <p>{{ cuenta.fecha_pago ? formatearFecha(cuenta.fecha_pago) : '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Comprobante</p>
                                                <button
                                                    v-if="cuenta.comprobante"
                                                    type="button"
                                                    @click="abrirComprobante(cuenta.comprobante)"
                                                    class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-indigo-600 hover:text-indigo-800"
                                                    title="Ver comprobante"
                                                    aria-label="Ver comprobante"
                                                >
                                                    <i class="fas fa-file"></i>
                                                    <span class="text-xs font-semibold">Ver comprobante</span>
                                                </button>
                                                <span v-else class="text-gray-400">-</span>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Observaciones</p>
                                                <p>{{ cuenta.observaciones || '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <Link
                                            v-if="$page.props.user.permissions.includes('update estado_cuenta_membresias')"
                                            :href="route('estado-cuenta-membresias.edit', cuenta.id)"
                                            class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-blue-600 text-white px-3 text-xs font-semibold hover:bg-blue-700 transition"
                                            title="Editar"
                                            aria-label="Editar"
                                        >
                                            <i class="fas fa-edit"></i>
                                            <span>Editar</span>
                                        </Link>
                                        <span
                                            v-else
                                            class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-gray-300 text-gray-500 px-3 text-xs font-semibold cursor-not-allowed"
                                            title="Editar"
                                            aria-label="Editar"
                                        >
                                            <i class="fas fa-edit"></i>
                                            <span>Editar</span>
                                        </span>
                                        <button
                                            v-if="$page.props.user.permissions.includes('delete estado_cuenta_membresias')"
                                            @click="eliminarEstado(cuenta)"
                                            class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-600 text-white px-3 text-xs font-semibold hover:bg-red-700 transition"
                                            title="Borrar"
                                            aria-label="Borrar"
                                        >
                                            <i class="fas fa-trash"></i>
                                            <span>Borrar</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="filtradas.length > 0" class="hidden overflow-x-auto sm:block">
                            <table class="min-w-full border-collapse border border-gray-300 dark:border-gray-600">
                                <thead class="bg-indigo-300 text-white text-sm">
                                    <tr>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">Usuario</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">Membresía</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">Mes</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">Importe</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center">Estado</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">Modo</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">
                                            <i class="fas fa-calendar mr-1"></i>Pago
                                        </th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center">Comprobante</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-left">Observaciones</th>
                                        <th class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="cuenta in filtradas" :key="cuenta.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ cuenta.user.name }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ cuenta.user.email }}</p>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                    {{ cuenta.membresia.nombre }}
                                                    <span
                                                        v-if="cuenta.user?.membresia_online || cuenta.user?.membresia_usuario?.membresia_online"
                                                        class="ml-2 text-xs font-semibold text-indigo-600"
                                                    >
                                                        Online
                                                    </span>
                                                </p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">{{ cuenta.membresia.entidad.nombre }}</p>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm">
                                            {{ formatearMes(cuenta.mes_pagado) }}
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm">
                                            ${{ parseFloat(cuenta.importe).toFixed(2) }}
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center">
                                            <span v-if="cuenta.pagado" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Pagado
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Pendiente
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm">
                                            {{ cuenta.modo || '-' }}
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm">
                                            {{ cuenta.fecha_pago ? formatearFecha(cuenta.fecha_pago) : '-' }}
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center">
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
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-sm">
                                            {{ cuenta.observaciones || '-' }}
                                        </td>
                                        <td class="border border-gray-300 dark:border-gray-600 px-2 py-2 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <Link
                                                    v-if="$page.props.user.permissions.includes('update estado_cuenta_membresias')"
                                                    :href="route('estado-cuenta-membresias.edit', cuenta.id)"
                                                    v-tooltip="'Editar'"
                                                    class="inline-flex items-center px-2 py-1 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </Link>
                                                <span v-else v-tooltip="'Editar'" class="inline-flex items-center px-2 py-1 bg-gray-300 text-gray-500 text-sm font-semibold rounded-md cursor-not-allowed">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                                <button
                                                    v-if="$page.props.user.permissions.includes('delete estado_cuenta_membresias')"
                                                    @click="eliminarEstado(cuenta)"
                                                    v-tooltip="'Borrar'"
                                                    class="inline-flex items-center px-2 py-1 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700 transition"
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
                            <p class="text-gray-600 dark:text-gray-400 text-lg">No hay registros de estado de cuenta</p>
                            <p class="text-gray-500 text-sm mt-2">
                                Los registros aparecerán cuando los usuarios se inscriban en membresías
                            </p>
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
import { Link, router, usePage } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    estadoCuentas: Array,
    mesProximo: {
        type: String,
        default: ''
    },
    ultimoMesGenerado: {
        type: String,
        default: null
    }
});

const page = usePage();
const generando = ref(false);
const revirtiendo = ref(false);

const puedeGenerar = computed(() => {
    const permisos = page.props.user?.permissions || [];
    return permisos.includes('update estado_cuenta_membresias');
});

const formatYearMonth = (yearMonth) => {
    if (!yearMonth) return '';
    const [year, month] = yearMonth.split('-');
    const fecha = new Date(Number(year), Number(month) - 1, 1);
    return fecha.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
};

const mesProximoLabel = computed(() => formatYearMonth(props.mesProximo));
const ultimoMesLabel = computed(() => formatYearMonth(props.ultimoMesGenerado));

const hayDesfase = computed(() => {
    if (!props.mesProximo) return false;
    const ahora = new Date();
    const mesActualYm = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}`;
    return props.mesProximo <= mesActualYm;
});

const generarEstados = () => {
    if (!props.mesProximo) return;
    Swal.fire({
        title: '¿Generar estados de cuenta?',
        html: `Se creará un estado de cuenta para <strong>${mesProximoLabel.value}</strong> por cada usuario con membresía activa.<br><br>Los usuarios con <strong>suscripción</strong> quedarán como Pagados automáticamente con modo "Suscripción".`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;
        generando.value = true;
        router.post(route('estado-cuenta-membresias.generar'), {
            mes_pagado: props.mesProximo,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('¡Listo!', 'Estados de cuenta generados correctamente.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'No se pudieron generar los estados.', 'error');
            },
            onFinish: () => {
                generando.value = false;
            },
        });
    });
};

const revertirUltima = () => {
    if (!props.ultimoMesGenerado) return;
    Swal.fire({
        title: '¿Revertir la última generación?',
        html: `Se eliminarán los estados de <strong>${ultimoMesLabel.value}</strong> que NO hayan sido modificados manualmente (sin pagos verificados, comprobantes ni ediciones).<br><br>Los estados del mes anterior se restaurarán a <strong>Activa</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, revertir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d97706',
    }).then((result) => {
        if (!result.isConfirmed) return;
        revirtiendo.value = true;
        router.post(route('estado-cuenta-membresias.revertir'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('¡Listo!', 'Última generación revertida.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'No se pudo revertir.', 'error');
            },
            onFinish: () => {
                revirtiendo.value = false;
            },
        });
    });
};

const filtroPeriodo = ref('last1');
const busquedaGlobal = ref('');

const filtradas = computed(() => {
    const data = props.estadoCuentas || [];
    let resultado = data;

    if (filtroPeriodo.value === 'last1') {
        const now = new Date();
        const mesActualYm = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        resultado = resultado.filter((cuenta) => String(cuenta.mes_pagado || '') === mesActualYm);
    } else if (filtroPeriodo.value === 'next1') {
        const now = new Date();
        const siguiente = new Date(now.getFullYear(), now.getMonth() + 1, 1);
        const mesSiguienteYm = `${siguiente.getFullYear()}-${String(siguiente.getMonth() + 1).padStart(2, '0')}`;
        resultado = resultado.filter((cuenta) => String(cuenta.mes_pagado || '') === mesSiguienteYm);
    }

    const term = busquedaGlobal.value.trim().toLowerCase();
    if (term) {
        resultado = resultado.filter((cuenta) => {
            const campos = [
                cuenta.user?.name,
                cuenta.user?.email,
                cuenta.membresia?.nombre,
                cuenta.membresia?.entidad?.nombre,
                cuenta.mes_pagado ? formatearMes(cuenta.mes_pagado) : '',
                cuenta.importe,
                cuenta.modo,
                cuenta.observaciones,
                cuenta.pagado ? 'pagado' : 'pendiente',
            ];
            return campos.some((valor) => String(valor ?? '').toLowerCase().includes(term));
        });
    }

    return resultado;
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
const expandedCardIds = ref([]);

const isCardExpanded = (id) => expandedCardIds.value.includes(id);

const toggleCardExpanded = (id) => {
    const idx = expandedCardIds.value.indexOf(id);
    if (idx === -1) {
        expandedCardIds.value.push(id);
    } else {
        expandedCardIds.value.splice(idx, 1);
    }
};

const abrirComprobante = (path) => {
    comprobantePath.value = path;
    comprobanteModal.value = true;
};

const eliminarEstado = (cuenta) => {
    const mes = cuenta.mes_pagado ? formatearMes(cuenta.mes_pagado) : '—';
    const usuario = cuenta.user?.name || 'usuario';
    Swal.fire({
        title: '¿Borrar este estado de cuenta?',
        html: `Se eliminará el estado de <strong>${usuario}</strong> correspondiente a <strong>${mes}</strong>. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, borrar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.delete(route('estado-cuenta-membresias.destroy', cuenta.id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire('¡Eliminado!', 'El estado de cuenta fue borrado.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'No se pudo borrar el estado.', 'error');
            },
        });
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>


