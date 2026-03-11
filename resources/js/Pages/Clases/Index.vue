<script>
export default {
    name: 'ClasesIndex'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputSwitch from 'primevue/inputswitch';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref } from 'vue';

const { clases } = defineProps({
    clases: {
        type: Object,
        required: true
    }
});

const filtroMesReferencia = ref('mes_actual');
const filtroMaestroManual = ref('');
const toast = useToast();
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    'entidad.nombre': { value: null, matchMode: FilterMatchMode.EQUALS },
});

const entidadOptions = computed(() => {
    const map = new Map();
    (clases?.data || []).forEach((clase) => {
        if (clase?.entidad?.id) {
            map.set(clase.entidad.id, clase.entidad.nombre || `Entidad ${clase.entidad.id}`);
        }
    });

    return Array.from(map.entries())
        .map(([id, nombre]) => ({ id, nombre }))
        .sort((a, b) => a.nombre.localeCompare(b.nombre));
});

const maestroOptions = computed(() => {
    const map = new Map();
    (clases?.data || []).forEach((clase) => {
        (clase?.maestros || []).forEach((maestro) => {
            if (maestro?.id) {
                map.set(maestro.id, maestro.nombre || `Maestro ${maestro.id}`);
            }
        });
    });

    return Array.from(map.entries())
        .map(([id, nombre]) => ({ id, nombre }))
        .sort((a, b) => a.nombre.localeCompare(b.nombre));
});

const clasesFiltradas = computed(() => {
    const rows = clases?.data || [];
    const ahora = new Date();
    const inicioMesActual = new Date(ahora.getFullYear(), ahora.getMonth(), 1, 0, 0, 0, 0);
    const inicioMesAnterior = new Date(ahora.getFullYear(), ahora.getMonth() - 1, 1, 0, 0, 0, 0);
    const finMesAnterior = new Date(ahora.getFullYear(), ahora.getMonth(), 0, 23, 59, 59, 999);
    const maestroFilterId = filtroMaestroManual.value;

    let limite = null;
    if (filtroMesReferencia.value === 'ultimos_tres_meses') {
        limite = new Date(inicioMesActual);
        limite.setMonth(limite.getMonth() - 3);
    }

    return rows.filter((clase) => {
        const mesRef = clase?.mes_referencia;
        if (!mesRef || !/^\d{4}-(0[1-9]|1[0-2])$/.test(mesRef)) return false;
        const [year, month] = mesRef.split('-').map(Number);
        const fechaMes = new Date(year, month - 1, 1);
        if (Number.isNaN(fechaMes.getTime())) return false;

        if (filtroMesReferencia.value === 'mes_actual' && fechaMes < inicioMesActual) return false;
        if (filtroMesReferencia.value === 'ultimo_mes' && !(fechaMes >= inicioMesAnterior && fechaMes <= finMesAnterior)) return false;
        if (filtroMesReferencia.value === 'ultimos_tres_meses' && limite && fechaMes < limite) return false;

        if (maestroFilterId !== null && maestroFilterId !== undefined && maestroFilterId !== '') {
            const maestros = Array.isArray(clase?.maestros) ? clase.maestros : [];
            const coincide = maestros.some((m) => String(m?.id) === String(maestroFilterId));
            if (!coincide) return false;
        }

        return true;
    });
});

const dayLabels = {
    lunes: 'Lun',
    martes: 'Mar',
    miercoles: 'Mie',
    jueves: 'Jue',
    viernes: 'Vie',
    sabado: 'Sab',
    domingo: 'Dom',
};

const formatDias = (diasSemana) => {
    if (!Array.isArray(diasSemana) || diasSemana.length === 0) return '-';

    const ordered = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']
        .filter(day => diasSemana.includes(day));

    if (ordered.length === 7) return 'Todos';

    return ordered.map(day => dayLabels[day] || day).join(', ');
};

const formatHora = (hora) => {
    if (!hora) return '-';
    return String(hora).slice(0, 5);
};

const formatMes = (mesReferencia) => {
    if (!mesReferencia || !/^\d{4}-(0[1-9]|1[0-2])$/.test(mesReferencia)) return '-';
    const [year, month] = mesReferencia.split('-').map(Number);
    return new Date(year, month - 1, 1).toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
};

const imageDialogVisible = ref(false);
const selectedImageUrl = ref('');
const expandedRows = ref([]);

const openImageDialog = (imageUrl) => {
    if (!imageUrl) return;
    selectedImageUrl.value = imageUrl;
    imageDialogVisible.value = true;
};

const deleteClase = (id) => {
    Swal.fire({
        title: 'Estas seguro?',
        text: 'Esta accion no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('clases.destroy', id), {
                onSuccess: () => Swal.fire('Eliminado!', 'La clase ha sido eliminada.', 'success'),
                onError: () => Swal.fire('Error', 'Hubo un problema al eliminar la clase.', 'error'),
            });
        }
    });
};

const updateEstado = (row, nuevoEstado) => {
    const previous = row.activa;
    row.activa = nuevoEstado;

    router.patch(route('clases.updateEstado', { clase: row.id }), {
        activa: nuevoEstado
    }, {
        preserveScroll: true,
        onError: () => {
            row.activa = previous;
            Swal.fire('Error', 'No se pudo actualizar el estado', 'error');
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Estado actualizado',
                detail: nuevoEstado ? 'Clase activada correctamente.' : 'Clase desactivada correctamente.',
                life: 3000,
            });
        }
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';

:deep(.clases-table .p-datatable-thead > tr:first-child > th) {
    padding-bottom: 0.1rem;
}

:deep(.clases-table .p-datatable-thead > tr.p-filter-row > th) {
    padding-top: 0.1rem;
}
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Clases</h1>
        </template>
        <Toast position="top-right" />
        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-[108rem] mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create clases')">
                        <Link :href="route('clases.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            NUEVA CLASE
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable
                            :value="clasesFiltradas"
                            v-model:filters="filters"
                            filterDisplay="row"
                            :globalFilterFields="['nombre', 'ciclo.nombre', 'entidad.nombre', 'coordinador.nombre']"
                            class="clases-table"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 90rem"
                            dataKey="id"
                            v-model:expandedRows="expandedRows"
                        >
                            <template #header>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                    <select
                                        v-model="filtroMesReferencia"
                                        class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-400 focus:ring-indigo-400"
                                    >
                                        <option value="mes_actual">Mes actual en adelante</option>
                                        <option value="ultimo_mes">Ultimo mes</option>
                                        <option value="ultimos_tres_meses">Ultimos tres meses</option>
                                        <option value="todo">Mostrar todo</option>
                                    </select>
                                    <IconField>
                                        <InputIcon class="pi pi-search" />
                                        <InputText v-model="filters.global.value" placeholder="Buscar..." />
                                    </IconField>
                                </div>
                            </template>
                            <Column expander style="width: 3rem" />
                            <Column header="Imagen">
                                <template #body="slotProps">
                                    <div class="flex items-center justify-center">
                                        <button
                                            v-if="slotProps.data.imagen"
                                            type="button"
                                            class="inline-flex"
                                            v-tooltip="'Ver imagen'"
                                            @click="openImageDialog('/storage/' + slotProps.data.imagen.ruta)"
                                        >
                                            <img
                                                :src="'/storage/' + slotProps.data.imagen.ruta"
                                                alt="Imagen de clase"
                                                class="h-12 w-12 rounded object-cover border border-gray-200"
                                            />
                                        </button>
                                        <span v-else class="text-sm text-gray-400">Sin imagen</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" />
                            <Column header="Mes">
                                <template #body="slotProps">
                                    {{ formatMes(slotProps.data.mes_referencia) }}
                                </template>
                            </Column>
                            <Column field="ciclo.nombre" header="Ciclo" />
                            <Column field="entidad.nombre" header="Entidad" :showFilterMenu="false" filterField="entidad.nombre">
                                <template #filter="{ filterModel, filterCallback }">
                                    <Dropdown
                                        v-model="filterModel.value"
                                        @change="filterCallback()"
                                        :options="entidadOptions"
                                        optionLabel="nombre"
                                        optionValue="nombre"
                                        placeholder="Todas"
                                        class="p-column-filter"
                                        :showClear="true"
                                    />
                                </template>
                            </Column>
                            <Column header="ONL">
                                <template #body="slotProps">
                                    <span :class="(slotProps.data.stream_id || slotProps.data.stream?.id) ? 'text-green-600 font-semibold' : 'text-gray-500'">
                                        {{ (slotProps.data.stream_id || slotProps.data.stream?.id) ? 'Si' : 'No' }}
                                    </span>
                                </template>
                            </Column>
                            <Column header="Dias">
                                <template #body="slotProps">
                                    {{ formatDias(slotProps.data.dias_semana) }}
                                </template>
                            </Column>
                            <Column header="Horario">
                                <template #body="slotProps">
                                    {{ formatHora(slotProps.data.horario_desde) }} - {{ formatHora(slotProps.data.horario_hasta) }}
                                </template>
                            </Column>
                            <Column header="Maestros">
                                <template #body="slotProps">
                                    <span v-if="Array.isArray(slotProps.data.maestros) && slotProps.data.maestros.length > 0">
                                        {{ slotProps.data.maestros.map((m) => m.nombre).join(', ') }}
                                    </span>
                                    <span v-else class="text-gray-500">-</span>
                                </template>
                                <template #filter>
                                    <Dropdown
                                        v-model="filtroMaestroManual"
                                        :options="maestroOptions"
                                        optionLabel="nombre"
                                        optionValue="id"
                                        placeholder="Todos"
                                        class="p-column-filter"
                                        :showClear="true"
                                    />
                                </template>
                            </Column>
                            <Column header="Activa">
                                <template #body="slotProps">
                                    <div class="flex justify-center">
                                        <InputSwitch
                                            :modelValue="slotProps.data.activa"
                                            @update:modelValue="updateEstado(slotProps.data, $event)"
                                            :disabled="!$page.props.user.permissions.includes('update clases')"
                                        />
                                    </div>
                                </template>
                            </Column>
                            <Column header="En calendario">
                                <template #body="slotProps">
                                    <span :class="slotProps.data.mostrar_en_calendario ? 'text-green-600 font-semibold' : 'text-gray-500'">
                                        {{ slotProps.data.mostrar_en_calendario ? 'Si' : 'No' }}
                                    </span>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="`${route('clases.show-public', { clase: parseInt(slotProps.data.id) })}?return_url=${encodeURIComponent(route('clases.index'))}`"
                                            v-tooltip="'Ver landing publica'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1; color: rgb(14, 116, 144);"></i>
                                        </Link>
                                        <Link
                                            :href="route('clases.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update clases')"
                                            v-tooltip="'Editar clase'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteClase(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete clases')"
                                            v-tooltip="'Borrar clase'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>
                            <template #expansion="{ data }">
                                <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Coordinador</p>
                                            <p class="text-sm text-gray-800">{{ data.coordinador?.nombre || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Esquema de precios</p>
                                            <p class="text-sm text-gray-800">{{ data.esquema_precio?.nombre || '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="imageDialogVisible"
            modal
            header="Imagen de Clase"
            :style="{ width: '720px' }"
        >
            <div class="w-full">
                <img
                    v-if="selectedImageUrl"
                    :src="selectedImageUrl"
                    alt="Imagen de Clase"
                    class="w-full max-h-[70vh] object-contain"
                />
            </div>
        </Dialog>
    </AppLayout>
</template>
