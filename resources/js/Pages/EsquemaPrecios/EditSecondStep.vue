<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EsquemaMembresiaForm from '@/Components/Formularios/EsquemaMembresiaForm.vue';
import { Link } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import { route } from 'ziggy-js';



const props = defineProps({
    esquemaPrecio: {
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

// Este form se usará para “Agregar Membresía”
const formMembresia = useForm({
    membresia_id: null,
    botonpago_id: null,
    precio: null,
    moneda_id: null
});

// Al hacer submit en el formulario de membresía
function handleAddMembresia() {
  router.post(route('esquemaprecios.storeMembresia', props.esquemaPrecio.id), {
    membresia_id: formMembresia.membresia_id,
    botonpago_id: formMembresia.botonpago_id,
    precio: formMembresia.precio,
    moneda_id: formMembresia.moneda_id,
  }, {
    onSuccess: () => {
      // Limpiar
      formMembresia.reset();
    }
  });
}

// DataTable en modo edición por fila
const editingRows = ref([]);

function onRowEditSave(event) {
  const membershipLine = event.newData ?? event.data;
  // membershipLine tendrá { id, membresia_id, precio, moneda_id, ... }
  router.put(route('esquemaprecios.updateMembresia', membershipLine.id), membershipLine, {
    onSuccess: () => {
      // row editing final
    }
  });
}

function deleteLine(line) {
  router.delete(route('esquemaprecios.destroyMembresia', line.id));
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

</script>


<template>
    <AppLayout title="Editar Esquema de Precios">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">
                Agregar Membresía y Precio
            </h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- Botón de Volver -->
                <div class="flex justify-end mr-5 mb-6 mt-3">
                    <Link 
                        :href="route('esquemaprecios.index')" 
                        class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                        Volver
                    </Link>
                </div>

                <!-- Mostrar el nombre del esquema -->
                <h2 class="text-2xl font-bold mb-4">
                    {{ esquemaPrecio.nombre }}
                </h2>

                <!-- Formulario para AGREGAR una nueva membresía -->
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg mb-6 mt-6 p-4">
                    <EsquemaMembresiaForm
                        :form="formMembresia"
                        :membresias="membresias"
                        :monedas="monedas"
                        :botonesPago="botonesPago"
                        @submit="handleAddMembresia"
                    />
                </div>
                <div class="card p-fluid">
                  <!-- DataTable con membresías ya existentes -->
                  <DataTable
                      :value="esquemaPrecio.membresias"
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
                            class="w-full mt-1 border border-gray-300"
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
                            class="w-full mt-1 border border-gray-300"
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
                            class="w-full mt-1 border border-gray-300"
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
                            class="w-full mt-1 border border-gray-300"
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
