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
                            <DataTable
                                :value="filtradas"
                                dataKey="id"
                                v-model:expandedRows="expandedRows"
                                responsiveLayout="scroll"
                                class="p-datatable-sm"
                            >
                                <Column expander style="width: 3rem" />

                                <Column header="Nombre">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-semibold text-gray-800">{{ nombreUsuario(data) }}</span>
                                            <span
                                                class="inline-flex w-fit items-center px-2 py-1 rounded-full text-xs font-medium"
                                                :class="isInvitado(data) ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                                            >
                                                {{ isInvitado(data) ? 'Invitado' : 'Registrado' }}
                                            </span>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Actividad">
                                    <template #body="{ data }">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">
                                                {{ data.actividad?.nombre || '-' }}
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                {{ data.actividad?.entidad?.nombre || '-' }}
                                            </p>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Membresía">
                                    <template #body="{ data }">
                                        <span class="text-sm">{{ data.membresia || '-' }}</span>
                                    </template>
                                </Column>

                                <Column header="Monto">
                                    <template #body="{ data }">
                                        <input
                                            v-if="isEditing(data)"
                                            v-model.number="editForm.montoapagar"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-28 rounded border border-gray-300 px-2 py-1 text-sm"
                                        />
                                        <span v-else class="text-sm">
                                            ${{ formatearMonto(data.montoapagar) }}
                                        </span>
                                    </template>
                                </Column>

                                <Column header="Pago" class="text-center">
                                    <template #body="{ data }">
                                        <select
                                            v-if="isEditing(data)"
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
                                            :class="badgePagoClass(data.pago)"
                                        >
                                            {{ data.pago || '-' }}
                                        </span>
                                    </template>
                                </Column>

                                <Column header="Comprobante" class="text-center">
                                    <template #body="{ data }">
                                        <button
                                            v-if="isEditing(data)"
                                            type="button"
                                            @click="openComprobanteModal(data)"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200"
                                            title="Subir comprobante"
                                        >
                                            <i class="pi pi-upload"></i>
                                        </button>
                                        <template v-else>
                                            <button
                                                v-if="urlComprobante(data)"
                                                type="button"
                                                @click="abrirComprobante(data)"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-600 border border-indigo-200"
                                                title="Ver comprobante"
                                            >
                                                <i class="fas fa-file-alt"></i>
                                            </button>
                                            <span
                                                v-if="comprobantesExtras(data) > 0"
                                                class="text-xs font-semibold text-gray-500"
                                            >
                                                +{{ comprobantesExtras(data) }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">Sin comprobante</span>
                                        </template>
                                    </template>
                                </Column>

                                <Column header="Estado">
                                    <template #body="{ data }">
                                        <span class="text-sm">{{ data.estado?.estado || '-' }}</span>
                                    </template>
                                </Column>

                                <Column header="Acciones" class="text-center">
                                    <template #body="{ data }">
                                        <div v-if="canEdit" class="flex justify-center gap-2">
                                            
                                            <template v-if="isEditing(data)">
                                                <button
                                                    class="inline-flex items-center justify-center rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700 disabled:opacity-60"
                                                    :disabled="isSaving"
                                                    @click="guardarEdicion(data)"
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
                                                @click="iniciarEdicion(data)"
                                                aria-label="Editar"
                                                title="Editar"
                                            >
                                                <i class="pi pi-file-edit"></i>
                                            </button>
                                            <button
                                                v-if="!isEditing(data)"
                                                class="inline-flex items-center justify-center rounded bg-emerald-50 px-2 py-1 text-xs text-emerald-700 hover:bg-emerald-100"
                                                @click="abrirConfirmarSaldado(data)"
                                                aria-label="Saldado"
                                                title="Saldado"
                                            >
                                                <i class="pi pi-check-circle"></i>
                                            </button>
                                        </div>
                                        <span v-else class="text-xs text-gray-400">Sin permisos</span>
                                    </template>
                                </Column>

                                <template #expansion="{ data }">
                                    <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Email</p>
                                                <p class="text-sm text-gray-800">{{ emailUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">País</p>
                                                <p class="text-sm text-gray-800">{{ paisUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Provincia</p>
                                                <p class="text-sm text-gray-800">{{ provinciaUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Municipio/Barrio</p>
                                                <p class="text-sm text-gray-800">{{ municipioBarrioUsuario(data) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Inscripto</p>
                                                <p class="text-sm text-gray-800">{{ formatearFecha(data.created_at) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auditor</p>
                                                <p class="text-sm text-gray-800">{{ data.auditor_user?.name || '-' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Auditado</p>
                                                <p class="text-sm text-gray-800">{{ formatearFecha(data.auditoria_fecha) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>
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
            <div class="max-h-[70vh] overflow-y-auto space-y-4">
                <div
                    v-for="(item, index) in comprobantesParaVer"
                    :key="`${item.url}-${index}`"
                    class="rounded border border-gray-200 p-3 bg-white"
                >
                    <p v-if="item.descripcion" class="text-xs text-gray-500 mb-2">
                        {{ item.descripcion }}
                    </p>
                    <iframe v-if="item.isPdf" :src="item.url" class="w-full h-[60vh] rounded"></iframe>
                    <img v-else :src="item.url" class="w-full rounded" alt="Comprobante" />
                </div>
            </div>
        </Dialog>

        <Dialog
            v-model:visible="confirmSaldadoVisible"
            modal
            header="Confirmar saldado"
            :style="{ width: '420px' }"
        >
            <p class="text-sm text-gray-700">
                ¿Confirmas marcar la inscripción como saldada y dejar el monto en $0?
            </p>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                        @click="confirmSaldadoVisible = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700"
                        @click="confirmarSaldado"
                    >
                        Confirmar
                    </button>
                </div>
            </template>
        </Dialog>

        <Dialog
            v-model:visible="comprobanteModalVisible"
            modal
            header="Subir comprobante"
            :style="{ width: '450px' }"
        >
            <p class="text-sm text-gray-600 mb-3">
                Subí un comprobante (PDF, JPG o PNG).
            </p>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="estado_inscripciones_comprobante_descripcion">
                    Descripción (opcional)
                </label>
                <input
                    id="estado_inscripciones_comprobante_descripcion"
                    v-model="comprobanteDescripcion"
                    type="text"
                    class="block w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-700"
                    placeholder="Ej: Transferencia febrero"
                />
            </div>
            <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                @change="onComprobanteChange"
                class="block w-full text-sm text-gray-700"
            />
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                        @click="comprobanteModalVisible = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 bg-sky-600 text-white rounded hover:bg-sky-700"
                        :disabled="!comprobanteFile"
                        @click="subirComprobante"
                    >
                        Subir
                    </button>
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';

const props = defineProps({
    inscripciones: Object
});

const page = usePage();
const filtroPeriodo = ref('last1');
const expandedRows = ref([]);
const editRowId = ref(null);
const editForm = ref({
    montoapagar: 0,
    pago: 'Pendiente'
});
const isSaving = ref(false);
const comprobanteModalVisible = ref(false);
const inscripcionParaComprobante = ref(null);
const comprobanteFile = ref(null);
const comprobanteDescripcion = ref('');

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

const openComprobanteModal = (inscripcion) => {
    inscripcionParaComprobante.value = inscripcion;
    comprobanteFile.value = null;
    comprobanteDescripcion.value = '';
    comprobanteModalVisible.value = true;
};

const onComprobanteChange = (event) => {
    const files = event.target.files;
    comprobanteFile.value = files && files[0] ? files[0] : null;
};

const subirComprobante = () => {
    if (!inscripcionParaComprobante.value || !comprobanteFile.value) return;

    const formData = new FormData();
    formData.append('comprobante', comprobanteFile.value);
    if (comprobanteDescripcion.value) {
        formData.append('descripcion', comprobanteDescripcion.value);
    }

    const inscripcionId = inscripcionParaComprobante.value.id;

    router.post(
        route('inscripciones.comprobante', { inscripcion: inscripcionId }),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                comprobanteModalVisible.value = false;
                inscripcionParaComprobante.value = null;
                comprobanteFile.value = null;
                comprobanteDescripcion.value = '';
            },
        }
    );
};

const abrirConfirmarSaldado = (inscripcion) => {
    if (!canEdit.value || isSaving.value) return;
    inscripcionParaSaldar.value = inscripcion;
    confirmSaldadoVisible.value = true;
};

const confirmarSaldado = () => {
    const inscripcion = inscripcionParaSaldar.value;
    if (!inscripcion || !canEdit.value || isSaving.value) return;
    editForm.value = {
        montoapagar: 0,
        pago: 'Saldado',
    };
    confirmSaldadoVisible.value = false;
    inscripcionParaSaldar.value = null;
    guardarEdicion(inscripcion);
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
    const raw = inscripcion?.comprobantes?.[0]?.ruta || inscripcion?.comprobante_url || inscripcion?.comprobante;
    if (!raw) return null;
    // Si ya es URL absoluta, usarla; de lo contrario, asumir ruta en storage
    if (/^https?:\/\//i.test(raw)) return raw;
    return `/storage/${raw}`;
};

const comprobantesExtras = (inscripcion) => {
    const count = inscripcion?.comprobantes?.length || 0;
    return count > 1 ? count - 1 : 0;
};

const comprobanteModal = ref(false);
const comprobantesParaVer = ref([]);
const confirmSaldadoVisible = ref(false);
const inscripcionParaSaldar = ref(null);

const normalizarComprobante = (ruta, descripcion) => {
    if (!ruta) return null;
    const url = /^https?:\/\//i.test(ruta) ? ruta : `/storage/${ruta}`;
    return {
        url,
        descripcion: descripcion || '',
        isPdf: url.toLowerCase().includes('.pdf'),
    };
};

const abrirComprobante = (inscripcion) => {
    if (!inscripcion) return;
    const items = [];
    if (Array.isArray(inscripcion.comprobantes) && inscripcion.comprobantes.length) {
        inscripcion.comprobantes.forEach((comprobante) => {
            const item = normalizarComprobante(comprobante.ruta, comprobante.descripcion);
            if (item) items.push(item);
        });
    } else {
        const raw = inscripcion?.comprobante_url || inscripcion?.comprobante;
        const item = normalizarComprobante(raw, '');
        if (item) items.push(item);
    }
    if (!items.length) return;
    comprobantesParaVer.value = items;
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
