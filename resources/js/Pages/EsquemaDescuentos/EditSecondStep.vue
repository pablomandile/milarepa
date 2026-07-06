<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EsquemaMembresiaForm from '@/Components/Formularios/EsquemaMembresiaForm.vue';
import { Link } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { computed, ref, watch } from 'vue';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import { route } from 'ziggy-js';



const props = defineProps({
    esquemaDescuento: {
        type: Object,
        required: true
    },
    membresias: {
        type: Array,
        default: () => []
    },
    monedas: {
        type: Array,
        default: () => []
    },
    botonesPago: {
        type: Array,
        default: () => []
    }
});

function getDefaultMonedaId() {
  return props.monedas.length ? props.monedas[0].id : null;
}

// Este form se usará para “Agregar Membresía”
const formMembresia = useForm({
    membresia_id: null,
    botonpago_id: null,
    precio: null,
    moneda_id: getDefaultMonedaId()
});

// Preselecciona la primera moneda (pesos argentinos), igual que en Esquema de Precios.
watch(
  () => props.monedas,
  (monedas) => {
    if (!formMembresia.moneda_id && monedas.length) {
      formMembresia.moneda_id = monedas[0].id;
    }
  },
  { immediate: true }
);

// Membresías que todavía no tienen precio en este esquema (para el dropdown del alta).
const membresiasDisponibles = computed(() => {
  const usadas = new Set((props.esquemaDescuento.membresias || []).map((l) => l.membresia_id));
  return props.membresias.filter((m) => !usadas.has(m.id));
});

// Al hacer submit en el formulario de membresía
function handleAddMembresia() {
  router.post(route('esquemadescuentos.storeMembresia', props.esquemaDescuento.id), {
    membresia_id: formMembresia.membresia_id,
    botonpago_id: formMembresia.botonpago_id,
    precio: formMembresia.precio,
    moneda_id: formMembresia.moneda_id,
  }, {
    onSuccess: () => {
      // Limpiar
      formMembresia.reset();
      formMembresia.moneda_id = getDefaultMonedaId();
    }
  });
}

// "Todos iguales": copia moneda + importe + botón de pago a todas las membresías
// de la misma entidad que aún no tengan precio en el esquema.
function handleAddIguales() {
  router.post(route('esquemadescuentos.storeMembresiasIguales', props.esquemaDescuento.id), {
    membresia_id: formMembresia.membresia_id,
    botonpago_id: formMembresia.botonpago_id,
    precio: formMembresia.precio,
    moneda_id: formMembresia.moneda_id,
  }, {
    onSuccess: () => {
      formMembresia.reset();
      formMembresia.moneda_id = getDefaultMonedaId();
    }
  });
}

// "Gratis con TK": pone en $0 las membresías faltantes de las entidades que ya tienen precio.
function handleAddGratis() {
  router.post(route('esquemadescuentos.storeMembresiasGratis', props.esquemaDescuento.id), {}, {
    preserveScroll: true,
  });
}

// DataTable en modo edición por fila
const editingRows = ref([]);

function onRowEditSave(event) {
  const membershipLine = event.newData ?? event.data;
  // membershipLine tendrá { id, membresia_id, precio, moneda_id, ... }
  router.put(route('esquemadescuentos.updateMembresia', membershipLine.id), membershipLine, {
    onSuccess: () => {
      // row editing final
    }
  });
}

function deleteLine(line) {
  router.delete(route('esquemadescuentos.destroyMembresia', line.id));
}

// Mostrar el nombre de la membresía (fuera de edición)
function getMembresiaLabel(membresiaId) {
  const found = props.membresias.find(m => m.id === membresiaId);
  return found ? found.label : '—';
}

// Mostrar el nombre de la moneda (fuera de edición)
function getMonedaLabel(monedaId) {
  const found = props.monedas.find(m => m.id === monedaId);
  return found ? found.nombre : '—';
}

function getBotonPagoLabel(botonPagoId) {
  const found = props.botonesPago.find((b) => b.id === botonPagoId);
  return found ? found.nombre : '—';
}

function onRowEditCancel(event) {
  // Opcional: revertir cambios, loguear, etc.
}

const editandoNombre = ref(false);
const nombreEditado = ref(props.esquemaDescuento.nombre);
const guardandoNombre = ref(false);

function iniciarEdicionNombre() {
  nombreEditado.value = props.esquemaDescuento.nombre;
  editandoNombre.value = true;
}

function cancelarEdicionNombre() {
  editandoNombre.value = false;
}

function guardarNombre() {
  const nuevo = (nombreEditado.value || '').trim();
  if (!nuevo) return;
  if (nuevo === props.esquemaDescuento.nombre) {
    editandoNombre.value = false;
    return;
  }
  guardandoNombre.value = true;
  router.put(route('esquemadescuentos.update', props.esquemaDescuento.id), { nombre: nuevo }, {
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
    <AppLayout title="Editar Esquema de Precios">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Agregar Membresía y Precio
            </h1>
        </template>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

                <!-- Botón de Volver -->
                <div class="flex justify-end mr-5 mb-6 mt-3">
                    <Link 
                        :href="route('esquemadescuentos.index')" 
                        class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                        Volver
                    </Link>
                </div>

                <!-- Mostrar el nombre del esquema -->
                <div class="flex items-center gap-2 mb-4">
                    <template v-if="!editandoNombre">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ esquemaDescuento.nombre }}</h2>
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
                            maxlength="50"
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

                <!-- Formulario para AGREGAR una nueva membresía -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg mb-6 mt-6 p-4">
                    <EsquemaMembresiaForm
                        :form="formMembresia"
                        :membresias="membresiasDisponibles"
                        :monedas="monedas"
                        :botonesPago="botonesPago"
                        :acciones-masivas="true"
                        :tiene-precios="(esquemaDescuento.membresias?.length || 0) > 0"
                        @submit="handleAddMembresia"
                        @submit-iguales="handleAddIguales"
                        @submit-gratis="handleAddGratis"
                    />
                </div>
                <div class="card p-fluid">
                  <!-- DataTable con membresías ya existentes -->
                  <DataTable
                      :value="esquemaDescuento.membresias"
                      editMode="row"
                      dataKey="id"
                      v-model:editingRows="editingRows"
                      @row-edit-save="onRowEditSave"
                      @row-edit-cancel="onRowEditCancel"
                      :showGridlines="true"
                      class="mb-4"
                      stripedRows 
                  >
                      <Column field="membresia_id" header="Membresía">
                        <!-- Modo lectura: mostramos el nombre de la membresía -->
                        <template #body="{ data }">
                          {{ getMembresiaLabel(data.membresia_id) }}
                        </template>

                        <!-- Modo edición: Dropdown para membresías -->
                        <template #editor="{ data, field }">
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
                      
                       <!-- Columna Moneda -->
                      <Column field="moneda_id" header="Moneda">
                        <!-- Modo lectura: mostramos el nombre de la moneda -->
                        <template #body="{ data }">
                          {{ getMonedaLabel(data.moneda_id) }}
                        </template>

                        <!-- Modo edición: Dropdown para monedas -->
                        <template #editor="{ data, field }">
                          <Dropdown
                            v-model="data.moneda_id"
                            :options="monedas"
                            optionLabel="nombre"
                            optionValue="id"
                            placeholder="Elige moneda"
                            class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                          />
                        </template>
                      </Column>

                      <!-- Columna Boton de Pago -->
                      <Column field="botonpago_id" header="Boton de Pago">
                        <template #body="{ data }">
                          {{ getBotonPagoLabel(data.botonpago_id) }}
                        </template>

                        <template #editor="{ data }">
                          <Dropdown
                            v-model="data.botonpago_id"
                            :options="botonesPago"
                            optionLabel="nombre"
                            optionValue="id"
                            placeholder="Elige boton de pago"
                            class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                            showClear
                          />
                        </template>
                      </Column>

                      <!-- Columna Precio -->
                      <Column field="precio" header="Precio">
                        <!-- Modo lectura: simplemente mostramos data.precio -->
                        <template #body="{ data }">
                          $ {{ parseFloat(data.precio).toLocaleString('es-AR', { minimumFractionDigits: 0 }) }}
                        </template>

                        <!-- Modo edición: Input para precio -->
                        <template #editor="{ data }">
                          <InputText
                            v-model="data.precio"
                            type="number"
                            class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                          />
                        </template>
                      </Column>

                      <Column rowEditor headerStyle="width:10rem" bodyStyle="text-align:center" />

                      <Column 
                        header="" 
                        body="deleteTemplate"
                        headerStyle="width:3rem; text-align:center"
                        bodyStyle="text-align:center"
                      >
                        <template #body="slotProps ">
                          <a
                            @click="deleteLine(slotProps.data)"
                            class="text-red-500 cursor-pointer"
                            v-tooltip="'Borrar fila'">
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
