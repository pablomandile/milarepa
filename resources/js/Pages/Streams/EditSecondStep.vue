<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StreamLinkForm from '@/Components/Formularios/StreamLinkForm.vue';
import { Link } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import { route } from 'ziggy-js';



const props = defineProps({
    stream: {
        type: Object,
        required: true
    },

});

// Este form se usará para “Agregar Membresía”
const formLink = useForm({
    nombre: '',
    link: ''
});

// Al hacer submit en el formulario de membresía
function handleAddLink() {
  router.post(route('streams.storeLink', props.stream.id), {
    nombre: formLink.nombre,
    link: formLink.link,
  }, {
    onSuccess: () => {
      // Limpiar
      formLink.reset();
    }
  });
}

// DataTable en modo edición por fila
const editingRows = ref([]);

function onRowEditSave(event) {
  const linkLine = event.newData ?? event.data;
  router.put(route('streams.updateLink', linkLine.id), linkLine, {
    onSuccess: () => {
      // row editing final
    }
  });
}

function deleteLine(line) {
  router.delete(route('streams.destroyLink', line.id));
}


</script>


<template>
    <AppLayout title="Editar Stream">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">
                Agregar Link
            </h1>
        </template>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

                <!-- Botón de Volver -->
                <div class="flex justify-end mr-5 mb-6 mt-3">
                    <Link 
                        :href="route('streams.index')" 
                        class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                        Volver
                    </Link>
                </div>

                <!-- Mostrar el nombre del esquema -->
                <h2 class="text-2xl font-bold mb-4">
                    {{ stream.nombre }}
                </h2>

                <!-- Formulario para AGREGAR una nueva membresía -->
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg mb-6 mt-6 p-4">
                    <StreamLinkForm
                        :form="formLink"
                        @submit="handleAddLink"
                    />
                </div>
                <div class="card p-fluid">
                  <!-- DataTable con membresías ya existentes -->
                  <DataTable
                      :value="stream.links"
                      editMode="row"
                      dataKey="id"
                      v-model:editingRows="editingRows"
                      @row-edit-save="onRowEditSave"
                      :showGridlines="true"
                      class="mb-4"
                      stripedRows 
                  >
                      <!-- Columna Nombre -->
                      <Column field="nombre" header="Nombre">
                        <!-- Modo lectura: simplemente mostramos data.nombre -->
                        <template #body="{ data }">
                          {{ data.nombre }}
                        </template>

                        <!-- Modo edición: Input para nombre -->
                        <template #editor="{ data }">
                          <InputText
                            v-model="data.nombre"
                            type="text"
                            class="w-full mt-1 border border-gray-300"
                          />
                        </template>
                      </Column>

                      <!-- Columna Link -->
                      <Column field="link" header="Link">
                        <!-- Modo lectura: simplemente mostramos data.link -->
                        <template #body="{ data }">
                          {{ data.link }}
                        </template>

                        <!-- Modo edición: Input para link -->
                        <template #editor="{ data }">
                          <InputText
                            v-model="data.link"
                            type="text"
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
