<template>
    <AppLayout title="Estado de Inscripciones">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    <i class="fas fa-clipboard-check mr-2 text-indigo-600"></i>
                    Estado de Inscripciones - Sistema
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="w-full p-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
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
                                        <th class="border border-gray-300 px-2 py-2 text-left">Nombre</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Email</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">País</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Provincia</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Municipio/Barrio</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Actividad</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Membresía</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Monto</th>
                                        <th class="border border-gray-300 px-2 py-2 text-center">Pago</th>
                                        <th class="border border-gray-300 px-2 py-2 text-center">Comprobante</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Estado</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Inscripto</th>
                                        <th class="border border-gray-300 px-2 py-2 text-left">Auditoría</th>
                                        <th class="border border-gray-300 px-2 py-2 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="inscripcion in filtradas" :key="inscripcion.id" class="hover:bg-gray-50">
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            <div class="flex flex-col gap-1">
                                                <span class="font-semibold text-gray-800">{{ nombreUsuario(inscripcion) }}</span>
                                                <span
                                                    class="inline-flex w-fit items-center px-2 py-1 rounded-full text-xs font-medium"
                                                    :class="isInvitado(inscripcion) ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                                >
                                                    {{ isInvitado(inscripcion) ? 'Invitado' : 'Registrado' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ emailUsuario(inscripcion) }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ paisUsuario(inscripcion) }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ provinciaUsuario(inscripcion) }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ municipioBarrioUsuario(inscripcion) }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800">
                                                    {{ inscripcion.actividad?.nombre || '-' }}
                                                </p>
                                                <p class="text-xs text-gray-600">
                                                    {{ inscripcion.actividad?.entidad?.nombre || '-' }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ inscripcion.membresia || '-' }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            <input
                                                v-if="isEditing(inscripcion)"
                                                v-model.number="editForm.montoapagar"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="w-28 rounded border border-gray-300 px-2 py-1 text-sm"
                                            />
                                            <span v-else>
                                                ${{ formatearMonto(inscripcion.montoapagar) }}
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center">
                                            <select
                                                v-if="isEditing(inscripcion)"
                                                v-model="editForm.pago"
                                                class="rounded border border-gray-300 px-2 py-1 text-sm"
                                            >
                                                <option value="Saldado">Saldado</option>
                                                <option value="Parcial">Parcial</option>
                                                <option value="Pendiente">Pendiente</option>
                                            </select>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="badgePagoClass(inscripcion.pago)"
                                            >
                                                {{ inscripcion.pago || '-' }}
                                            </span>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center">
                                            <button
                                                v-if="urlComprobante(inscripcion)"
                                                type="button"
                                                @click="abrirComprobante(urlComprobante(inscripcion))"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200"
                                                title="Ver comprobante"
                                            >
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <span v-else class="text-xs text-gray-400">Sin comprobante</span>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ inscripcion.estado?.estado || '-' }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            {{ formatearFecha(inscripcion.created_at) }}
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-sm">
                                            <div class="flex flex-col gap-1">
                                                <span class="font-semibold text-gray-800">
                                                    {{ inscripcion.auditor_user?.name || '-' }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{ formatearFecha(inscripcion.auditoria_fecha) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="border border-gray-300 px-2 py-2 text-center">
                                            <div v-if="canEdit" class="flex justify-center gap-2">
                                                <template v-if="isEditing(inscripcion)">
                                                    <button
                                                        class="inline-flex items-center justify-center rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700 disabled:opacity-60"
                                                        :disabled="isSaving"
                                                        @click="guardarEdicion(inscripcion)"
                                                        aria-label="Guardar"
                                                        title="Guardar"
                                                    >
                                                        ✓
                                                    </button>
                                                    <button
                                                        class="inline-flex items-center justify-center rounded bg-gray-200 px-2 py-1 text-xs text-gray-700 hover:bg-gray-300"
                                                        @click="cancelarEdicion"
                                                        aria-label="Cancelar"
                                                        title="Cancelar"
                                                    >
                                                        ✕
                                                    </button>
                                                </template>
                                                <button
                                                    v-else
                                                    class="inline-flex items-center justify-center rounded bg-indigo-50 px-2 py-1 text-xs text-indigo-700 hover:bg-indigo-100"
                                                    @click="iniciarEdicion(inscripcion)"
                                                    aria-label="Editar"
                                                    title="Editar"
                                                >
                                                    ✎
                                                </button>
                                            </div>
                                            <span v-else class="text-xs text-gray-400">Sin permisos</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center py-12">
                            <i class="fas fa-inbox text-5xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 text-lg">No hay inscripciones activas</p>
                            <p class="text-gray-500 text-sm mt-2">
                                Las inscripciones aparecerán cuando los usuarios se registren en actividades
                            </p>
                        </div>

                        <div v-if="filtroPeriodo === 'all' && inscripciones.links.length > 3" class="mt-6 flex justify-center gap-2">
                            <Link
                                v-for="link in inscripciones.links"
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

        <Dialog v-model:visible="comprobanteModal" modal header="Comprobante" :style="{ width: '700px' }">
            <div class="max-h-[70vh] overflow-y-auto">
                <template v-if="comprobanteIsPdf">
                    <iframe :src="comprobanteUrl" class="w-full h-[60vh] rounded"></iframe>
                </template>
                <template v-else>
                    <img v-if="comprobanteUrl" :src="comprobanteUrl" class="w-full rounded" alt="Comprobante" />
                </template>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';

const props = defineProps({
    inscripciones: Object
});

const page = usePage();
const filtroPeriodo = ref('last1');
const editRowId = ref(null);
const editForm = ref({
    montoapagar: 0,
    pago: 'Pendiente'
});
const isSaving = ref(false);

const canEdit = computed(() => {
    const roles = (page.props.user?.roles || []).map((role) => String(role).toLowerCase());
    return roles.includes('admin') || roles.includes('editor');
});

const nombreUsuario = (inscripcion) => {
    if (isInvitado(inscripcion) && inscripcion.guest_user?.name) {
        return inscripcion.guest_user.name;
    }
    return inscripcion.user?.name || inscripcion.guest_user?.name || 'Sin datos';
};

const emailUsuario = (inscripcion) => {
    if (isInvitado(inscripcion) && inscripcion.guest_user?.email) {
        return inscripcion.guest_user.email;
    }
    return inscripcion.user?.email || inscripcion.guest_user?.email || '-';
};

const isInvitado = (inscripcion) => {
    return inscripcion.user?.name === 'Invitado';
};

const isEditing = (inscripcion) => editRowId.value === inscripcion.id;

const iniciarEdicion = (inscripcion) => {
    editRowId.value = inscripcion.id;
    editForm.value = {
        montoapagar: Number(inscripcion.montoapagar || 0),
        pago: inscripcion.pago || 'Pendiente',
    };
};

const cancelarEdicion = () => {
    editRowId.value = null;
};

const guardarEdicion = async (inscripcion) => {
    if (!canEdit.value || isSaving.value) return;
    isSaving.value = true;
    try {
        await axios.put(route('estadoinscripciones.update', inscripcion.id), {
            montoapagar: editForm.value.montoapagar,
            pago: editForm.value.pago,
        });
        inscripcion.montoapagar = editForm.value.montoapagar;
        inscripcion.pago = editForm.value.pago;
        editRowId.value = null;
    } catch (error) {
        alert('No se pudo guardar la edición.');
    } finally {
        isSaving.value = false;
    }
};

const paisUsuario = (inscripcion) => {
    if (isInvitado(inscripcion)) {
        return inscripcion.guest_user?.pais?.nombre || '-';
    }
    return inscripcion.user?.pais?.nombre || inscripcion.guest_user?.pais?.nombre || '-';
};

const provinciaUsuario = (inscripcion) => {
    if (isInvitado(inscripcion)) {
        return inscripcion.guest_user?.provincia?.nombre || '-';
    }
    return inscripcion.user?.provincia?.nombre || inscripcion.guest_user?.provincia?.nombre || '-';
};

const municipioBarrioUsuario = (inscripcion) => {
    if (isInvitado(inscripcion)) {
        return (
            inscripcion.guest_user?.municipio?.nombre ||
            inscripcion.guest_user?.barrio?.nombre ||
            '-'
        );
    }
    return (
        inscripcion.user?.municipio?.nombre ||
        inscripcion.user?.barrio?.nombre ||
        inscripcion.guest_user?.municipio?.nombre ||
        inscripcion.guest_user?.barrio?.nombre ||
        '-'
    );
};

const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatearMonto = (monto) => {
    const valor = Number(monto);
    if (Number.isNaN(valor)) return '0.00';
    return valor.toFixed(2);
};

const badgePagoClass = (pago) => {
    if (pago === 'Saldado') return 'bg-green-100 text-green-800';
    if (pago === 'Parcial') return 'bg-yellow-100 text-yellow-800';
    if (pago === 'Pendiente') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-700';
};

const urlComprobante = (inscripcion) => {
    const raw = inscripcion?.comprobante_url || inscripcion?.comprobante;
    if (!raw) return null;
    // Si ya es URL absoluta, usarla; de lo contrario, asumir ruta en storage
    if (/^https?:\/\//i.test(raw)) return raw;
    return `/storage/${raw}`;
};

const comprobanteModal = ref(false);
const comprobanteUrl = ref('');
const comprobanteIsPdf = computed(() => (comprobanteUrl.value || '').toLowerCase().includes('.pdf'));

const abrirComprobante = (url) => {
    if (!url) return;
    comprobanteUrl.value = url;
    comprobanteModal.value = true;
};

const filtradas = computed(() => {
    const data = props.inscripciones?.data || [];
    if (filtroPeriodo.value === 'all') return data;
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    return data.filter((inscripcion) => {
        if (!inscripcion.created_at) return false;
        const fecha = new Date(inscripcion.created_at);
        return fecha >= start;
    });
});
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>
