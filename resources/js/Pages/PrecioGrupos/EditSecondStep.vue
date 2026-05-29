<script setup>
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import { route } from 'ziggy-js';

const props = defineProps({
    precioGrupo: {
        type: Object,
        required: true
    },
    membresias: {
        type: Array,
        default: () => []
    }
});

const formLinea = useForm({
    membresia_id: null,
    valor: null
});

const editingRows = ref([]);

const formatArs = (valor) => {
    if (valor === null || valor === undefined) return '$ 0,00';
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2
    }).format(valor);
};

const formatFecha = (fecha) => {
    if (!fecha) return null;
    const d = new Date(fecha);
    if (Number.isNaN(d.getTime())) return fecha;
    return d.toLocaleDateString('es-AR', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

function handleAddLinea() {
    router.post(route('precio-grupos.storeMembresia', props.precioGrupo.id), {
        membresia_id: formLinea.membresia_id,
        valor: formLinea.valor,
    }, {
        onSuccess: () => {
            formLinea.reset();
        }
    });
}

function onRowEditSave(event) {
    const linea = event.newData ?? event.data;
    router.put(route('precio-grupos.updateMembresia', linea.id), {
        membresia_id: linea.membresia_id,
        valor: linea.valor,
    });
}

function deleteLinea(linea) {
    Swal.fire({
        title: '¿Borrar esta línea?',
        text: 'Se eliminará el precio de esa membresía en este grupo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, borrar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('precio-grupos.destroyMembresia', linea.id));
        }
    });
}

function getMembresiaLabel(membresiaId) {
    const found = props.membresias.find(m => m.id === membresiaId);
    return found ? found.label : '—';
}

const editandoNombre = ref(false);
const nombreEditado = ref(props.precioGrupo.nombre);
const guardandoNombre = ref(false);

function iniciarEdicionNombre() {
    nombreEditado.value = props.precioGrupo.nombre;
    editandoNombre.value = true;
}

function cancelarEdicionNombre() {
    editandoNombre.value = false;
}

function guardarNombre() {
    const nuevo = (nombreEditado.value || '').trim();
    if (!nuevo) return;
    if (nuevo === props.precioGrupo.nombre) {
        editandoNombre.value = false;
        return;
    }
    guardandoNombre.value = true;
    router.put(route('precio-grupos.update', props.precioGrupo.id), { nombre: nuevo }, {
        preserveScroll: true,
        onSuccess: () => {
            editandoNombre.value = false;
        },
        onFinish: () => {
            guardandoNombre.value = false;
        },
    });
}
</script>

<template>
    <AppLayout title="Editar Grupo de Precios">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Precios del Grupo
            </h1>
        </template>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

                <div class="flex justify-end mr-5 mb-6 mt-3">
                    <Link
                        :href="route('precio-grupos.index')"
                        class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                        Volver
                    </Link>
                </div>

                <div class="flex items-center gap-2 mb-1">
                    <template v-if="!editandoNombre">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ precioGrupo.nombre }}</h2>
                        <button
                            type="button"
                            @click="iniciarEdicionNombre"
                            v-tooltip="'Editar nombre'"
                            class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            <i class="fas fa-pen-to-square text-lg"></i>
                        </button>
                    </template>
                    <template v-else>
                        <input
                            v-model="nombreEditado"
                            type="text"
                            maxlength="100"
                            class="text-2xl font-bold border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                            @keydown.enter="guardarNombre"
                            @keydown.escape="cancelarEdicionNombre"
                        />
                        <button
                            type="button"
                            @click="guardarNombre"
                            :disabled="guardandoNombre"
                            v-tooltip="'Guardar'"
                            class="text-green-600 hover:text-green-700 disabled:opacity-50"
                        >
                            <i class="fas fa-check text-lg"></i>
                        </button>
                        <button
                            type="button"
                            @click="cancelarEdicionNombre"
                            :disabled="guardandoNombre"
                            v-tooltip="'Cancelar'"
                            class="text-red-600 hover:text-red-700 disabled:opacity-50"
                        >
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </template>
                </div>
                <p v-if="precioGrupo.fecha_desde" class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Vigencia desde: {{ formatFecha(precioGrupo.fecha_desde) }}
                </p>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg mb-6 mt-6 p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Agregar precio para una membresía</h3>
                    <form @submit.prevent="handleAddLinea" class="flex flex-col md:flex-row md:items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Membresía</label>
                            <Dropdown
                                v-model="formLinea.membresia_id"
                                :options="membresias"
                                optionLabel="label"
                                optionValue="id"
                                placeholder="Elige membresía"
                                class="w-full border border-gray-300 dark:border-gray-600"
                            />
                        </div>
                        <div class="md:w-48">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor</label>
                            <InputNumber
                                v-model="formLinea.valor"
                                mode="currency"
                                currency="ARS"
                                locale="es-AR"
                                :minFractionDigits="2"
                                class="w-full"
                                inputClass="w-full border border-gray-300 dark:border-gray-600"
                            />
                        </div>
                        <div>
                            <button
                                type="submit"
                                :disabled="!formLinea.membresia_id || formLinea.valor === null"
                                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50">
                                Agregar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card p-fluid">
                    <DataTable
                        :value="precioGrupo.lineas"
                        editMode="row"
                        dataKey="id"
                        v-model:editingRows="editingRows"
                        @row-edit-save="onRowEditSave"
                        :showGridlines="true"
                        class="mb-4"
                        stripedRows
                    >
                        <Column field="membresia_id" header="Membresía">
                            <template #body="{ data }">
                                {{ getMembresiaLabel(data.membresia_id) }}
                            </template>
                            <template #editor="{ data }">
                                <Dropdown
                                    v-model="data.membresia_id"
                                    :options="membresias"
                                    optionLabel="label"
                                    optionValue="id"
                                    placeholder="Elige membresía"
                                    class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                                />
                            </template>
                        </Column>

                        <Column field="valor" header="Valor">
                            <template #body="{ data }">
                                {{ formatArs(data.valor) }}
                            </template>
                            <template #editor="{ data }">
                                <InputText
                                    v-model="data.valor"
                                    type="number"
                                    step="0.01"
                                    class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                                />
                            </template>
                        </Column>

                        <Column rowEditor headerStyle="width:10rem" bodyStyle="text-align:center" />

                        <Column
                            header=""
                            headerStyle="width:3rem; text-align:center"
                            bodyStyle="text-align:center"
                        >
                            <template #body="slotProps">
                                <a
                                    @click="deleteLinea(slotProps.data)"
                                    class="text-red-500 cursor-pointer"
                                    v-tooltip="'Borrar línea'">
                                    <i class="pi pi-trash text-red-300"></i>
                                </a>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
