<script>
    export default {
        name: 'ActividadesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import InputSwitch from 'primevue/inputswitch';
    import Tag from 'primevue/tag';
    import { ref } from 'vue';
    import Dialog from 'primevue/dialog';
    import { usePage } from '@inertiajs/vue3';

    const page = usePage();
    const visible = ref(false);
    const actividadSeleccionada = ref(null);
    const programaVisible = ref(false);
    const programaSeleccionado = ref(null);
    const esquemaPrecioVisible = ref(false);
    const esquemaPrecioSeleccionado = ref(null);
    
    const { actividades } = defineProps({
        actividades: {
            type: Array,
            required: true
        }
    });

    // Controla qué filas de la tabla principal están expandidas
    const expandedRows = ref([]);

    const verActividad = (id) => {
        const actividad = actividades.find((ent) => ent.id === id);
        if (actividad) {
            actividadSeleccionada.value = actividad;
            visible.value = true;
        }
    };

    const verPrograma = (programa) => {
        programaSeleccionado.value = programa;
        programaVisible.value = true;
    };

    const verEsquemaPrecio = (esquema) => {
        esquemaPrecioSeleccionado.value = esquema;
        esquemaPrecioVisible.value = true;
    };

    const updateEstado = (row, nuevoEstado) => {
        const previous = row.estado;
        row.estado = nuevoEstado; // optimistic UI
        router.patch(route('actividades.updateEstado', { actividad: row.id }), {
            estado: nuevoEstado
        }, {
            preserveScroll: true,
            onError: () => {
                row.estado = previous;
                Swal.fire('Error', 'No se pudo actualizar el estado', 'error');
            },
            onSuccess: () => {
                Swal.fire('Éxito', 'Estado actualizado correctamente', 'success');
            }
        });
    };

    const deleteActividad = (id) => {
        Swal.fire({
            title: "¿Estás seguro?",
            text: "Esta acción no se puede deshacer.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('actividades.destroy', { actividad: id }), {
                    onSuccess: () => {
                        Swal.fire("¡Eliminado!", "La Actividad ha sido eliminada.", "success");
                    },
                    onError: () => {
                        Swal.fire("Error", "Hubo un problema al eliminar la Actividad.", "error");
                    },
                });
            }
        });
    };
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Actividades</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-7xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create actividades')">
                        <Link :href="route('actividades.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA ACTIVIDAD
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable 
                        :value="actividades" 
                        stripedRows 
                        removableSort 
                        paginator 
                        :rows="10" 
                        v-model:expandedRows="expandedRows"
                        dataKey="id"
                        :rowsPerPageOptions="[5, 10, 20, 50]" 
                        tableStyle="min-width: 50rem"
                        >
                            <Column expander style="width: 5rem" />
                            <Column header="Imagen">
                                <template #body="slotProps">
                                    <div>
                                        <img
                                            v-if="slotProps.data.imagen"
                                            :src="'/storage/' + slotProps.data.imagen.ruta"
                                            alt="Imagen de la Actividad"
                                            style="max-width: 50px; max-height: 50px;"
                                        />
                                        <img
                                            v-else
                                            src="/storage/img/actividades/imagen-no-disponible.jpg"
                                            alt="Sin imagen"
                                            style="max-width: 50px; max-height: 50px;"
                                        />
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column field="tipo_actividad.abreviacion" header="Tipo" sortable></Column>
                            <Column header="Fecha Inicio" sortable field="fecha_inicio">
                                <template #body="slotProps">
                                    {{ slotProps.data.fecha_inicio ? new Date(slotProps.data.fecha_inicio).toLocaleDateString('es-AR') : '-' }}
                                </template>
                            </Column>
                            <Column header="Hora Inicio" sortable field="fecha_inicio">
                                <template #body="slotProps">
                                    {{ slotProps.data.fecha_inicio ? new Date(slotProps.data.fecha_inicio).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) + ' hs.' : '-' }}
                                </template>
                            </Column>
                            <Column field="modalidad.nombre" header="Modalidad" sortable></Column>
                            <Column header="Estado">
                                <template #body="slotProps">
                                    <div class="flex justify-center">
                                        <InputSwitch 
                                            :modelValue="slotProps.data.estado" 
                                            @update:modelValue="updateEstado(slotProps.data, $event)"
                                        />
                                    </div>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <!-- <button
                                            @click="verActividad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('read actividades')"
                                            v-tooltip="'Ver actividad'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1;"></i>
                                        </button> -->
                                        <Link
                                            :href="route('actividades.edit', { actividad: parseInt(slotProps.data.id) })"
                                            v-if="$page.props.user.permissions.includes('update actividades')"
                                            v-tooltip="'Editar actividad'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteActividad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete actividades')"
                                            v-tooltip="'Borrar actividad'"
                                            class="text-red-600 hover:text-red-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1;"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>
                            <template #expansion="{ data }">
                                <div class="p-4 bg-gray-50">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <!-- Lugar -->
                                        <div v-if="data.entidad">
                                            <span class="font-semibold text-gray-700">Lugar:</span>
                                            <span class="ml-2">{{ data.entidad.abreviacion }}</span>
                                            <div v-if="data.entidad.direccion" class="text-sm text-gray-600 ml-2">
                                                {{ data.entidad.direccion }}
                                            </div>
                                        </div>

                                        <!-- Descripción -->
                                        <div v-if="data.descripcion" class="md:col-span-2">
                                            <span class="font-semibold text-gray-700">Descripción:</span>
                                            <p class="ml-2 text-sm">{{ data.descripcion.descripcion }}</p>
                                        </div>

                                        <!-- Observaciones -->
                                        <div v-if="data.observaciones" class="md:col-span-2">
                                            <span class="font-semibold text-gray-700">Observaciones:</span>
                                            <p class="ml-2 text-sm">{{ data.observaciones }}</p>
                                        </div>

                                        <!-- Disponibilidad -->
                                        <div v-if="data.disponibilidad">
                                            <span class="font-semibold text-gray-700">Disponibilidad:</span>
                                            <span class="ml-2">{{ data.disponibilidad.descripcion }}</span>
                                        </div>

                                        <!-- Maestros -->
                                        <div v-if="data.maestros && data.maestros.length > 0">
                                            <span class="font-semibold text-gray-700">Maestros:</span>
                                            <div class="ml-2">
                                                <span v-for="(maestro, index) in data.maestros" :key="maestro.id" class="text-sm">
                                                    {{ maestro.nombre }}<span v-if="index < data.maestros.length - 1">, </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Coordinadores -->
                                        <div v-if="data.coordinadores && data.coordinadores.length > 0">
                                            <span class="font-semibold text-gray-700">Coordinadores:</span>
                                            <div class="ml-2">
                                                <span v-for="(coordinador, index) in data.coordinadores" :key="coordinador.id" class="text-sm">
                                                    {{ coordinador.nombre }}<span v-if="index < data.coordinadores.length - 1">, </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Programa -->
                                        <div v-if="data.programa" class="flex items-center">
                                            <span class="font-semibold text-gray-700">Programa:</span>
                                            <span class="ml-2">{{ data.programa.nombre }}</span>
                                            <button 
                                                @click="verPrograma(data.programa)" 
                                                class="ml-2 text-indigo-600 hover:text-indigo-800"
                                                style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                                title="Ver programa completo"
                                            >
                                                <i class="fas fa-eye" style="font-size: 16px;"></i>
                                            </button>
                                        </div>

                                        <!-- Esquema de Precios -->
                                        <div v-if="data.esquema_precio" class="flex items-center">
                                            <span class="font-semibold text-gray-700">Esquema de precios:</span>
                                            <span class="ml-2">{{ data.esquema_precio.nombre }}</span>
                                            <button 
                                                @click="verEsquemaPrecio(data.esquema_precio)" 
                                                class="ml-2 text-indigo-600 hover:text-indigo-800"
                                                style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                                title="Ver esquema de precios completo"
                                            >
                                                <i class="fas fa-eye" style="font-size: 16px;"></i>
                                            </button>
                                        </div>

                                        <!-- Esquema de Descuentos -->
                                        <div v-if="data.esquema_descuento">
                                            <span class="font-semibold text-gray-700">Esquema Descuentos:</span>
                                            <span class="ml-2">{{ data.esquema_descuento.nombre }}</span>
                                        </div>

                                        <!-- Lugares de Hospedajes -->
                                        <div v-if="data.hospedajes && data.hospedajes.length > 0">
                                            <span class="font-semibold text-gray-700">Hospedajes:</span>
                                            <div class="ml-2">
                                                <span v-for="(hospedaje, index) in data.hospedajes" :key="hospedaje.id" class="text-sm">
                                                    {{ hospedaje.nombre }}<span v-if="index < data.hospedajes.length - 1">, </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Hospedajes -->
                                        <div v-if="data.hospedajes && data.hospedajes.length > 0">
                                            <span class="font-semibold text-gray-700">Hospedajes:</span>
                                            <div class="ml-2">
                                                <span v-for="(hospedaje, index) in data.hospedajes" :key="hospedaje.id" class="text-sm">
                                                    {{ hospedaje.nombre }}<span v-if="index < data.hospedajes.length - 1">, </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Comidas -->
                                        <div v-if="data.comidas && data.comidas.length > 0">
                                            <span class="font-semibold text-gray-700">Comidas:</span>
                                            <div class="ml-2">
                                                <span v-for="(comida, index) in data.comidas" :key="comida.id" class="text-sm">
                                                    {{ comida.nombre }}<span v-if="index < data.comidas.length - 1">, </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Transportes -->
                                        <div v-if="data.transportes && data.transportes.length > 0">
                                            <span class="font-semibold text-gray-700">Transportes:</span>
                                            <div class="ml-2">
                                                <span v-for="(transporte, index) in data.transportes" :key="transporte.id" class="text-sm">
                                                    {{ transporte.descripcion }}<span v-if="index < data.transportes.length - 1">, </span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Stream -->
                                        <div v-if="data.stream">
                                            <span class="font-semibold text-gray-700">Stream:</span>
                                            <span class="ml-2">{{ data.stream.titulo || 'Disponible' }}</span>
                                        </div>

                                        <!-- Grabación -->
                                        <div v-if="data.grabacion">
                                            <span class="font-semibold text-gray-700">Grabación:</span>
                                            <span class="ml-2">{{ data.grabacion.titulo || 'Disponible' }}</span>
                                        </div>

                                        <!-- Fecha Fin -->
                                        <div v-if="data.fecha_fin">
                                            <span class="font-semibold text-gray-700">Fecha Fin:</span>
                                            <span class="ml-2">{{ new Date(data.fecha_fin).toLocaleDateString('es-AR') }}</span>
                                        </div>

                                        <!-- Hora Fin -->
                                        <div v-if="data.fecha_fin">
                                            <span class="font-semibold text-gray-700">Hora Fin:</span>
                                            <span class="ml-2">{{ new Date(data.fecha_fin).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) }} hs.</span>
                                        </div>

                                        <!-- Pago Anticipado -->
                                        <div v-if="data.pagoAmticipado">
                                            <span class="font-semibold text-gray-700">Fecha Pago Anticipado:</span>
                                            <span class="ml-2">{{ new Date(data.pagoAmticipado).toLocaleDateString('es-AR') }}</span>
                                        </div>

                                        <!-- Tipo de actividad -->
                                        <div v-if="data.tipo_actividad.nombre">
                                            <span class="font-semibold text-gray-700">Tipo de Actividad:</span>
                                            <span class="ml-2">{{ data.tipo_actividad.nombre }}</span>
                                        </div>

                                        <!-- Métodos de Pago -->
                                        <div v-if="data.metodos_pago && data.metodos_pago.length > 0" class="md:col-span-2">
                                            <span class="font-semibold text-gray-700">Métodos de Pago:</span>
                                            <div class="ml-2 flex flex-wrap gap-2 mt-1">
                                                <Tag v-for="metodo in data.metodos_pago" :key="metodo.id" severity="info" :value="metodo.nombre"></Tag>
                                            </div>
                                            <div class="ml-2 mt-2">
                                                <span v-for="(metodo, index) in data.metodos_pago" :key="metodo.id" class="text-sm block">
                                                    {{ metodo.descripcion }}<span v-if="index < data.metodos_pago.length - 1"></span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Link Web -->
                                        <div v-if="data.link_web" class="md:col-span-2">
                                            <span class="font-semibold text-gray-700">Link Web:</span>
                                            <a :href="data.link_web" target="_blank" class="ml-2 text-blue-500 hover:underline text-sm">{{ data.link_web }}</a>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
    <!-- Dialog para mostrar detalles -->
    <Dialog 
        v-model:visible="visible" 
        maximizable 
        modal 
        :header="actividadSeleccionada ? `Detalles de ${actividadSeleccionada.nombre}` : 'Detalles...'"
        :style="{ width: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        dismissableMask
>
    <template v-if="actividadSeleccionada">
        <!-- Mostrar imagen solo si logo_url no está vacío -->
        <div class="mb-5" v-if="actividadSeleccionada.imagen">
            <img :src="'/storage/' + actividadSeleccionada.imagen.ruta" alt="Imagen de la Actividad" style="max-width: 640px; max-height: 480px;" />
            
        </div>

        <p class="mb-3" v-if="actividadSeleccionada.entidad.direccion && actividadSeleccionada.entidad.direccion.trim() !== ''">
            {{ actividadSeleccionada.entidad.direccion }}
        </p>

        
        <p class="mb-3" v-if="actividadSeleccionada.entidad.direccion && actividadSeleccionada.entidad.direccion.trim() !== ''">
            {{ actividadSeleccionada.entidad.direccion }}
        </p>
        
        <p class="mb-3" v-if="actividadSeleccionada.descripcion && actividadSeleccionada.descripcion.descripcion.trim() !== ''">
            {{ actividadSeleccionada.descripcion.descripcion }}
        </p>



        </template>
        <p v-else>Cargando datos...</p>
    </Dialog>

    <!-- Dialog para mostrar programa -->
    <Dialog 
        v-model:visible="programaVisible" 
        modal 
        :header="programaSeleccionado ? `Programa: ${programaSeleccionado.nombre}` : 'Programa'"
        :style="{ width: '50rem' }" 
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        dismissableMask
    >
        <template v-if="programaSeleccionado">
            <div class="prose max-w-none">
                <p style="white-space: pre-wrap;">{{ programaSeleccionado.programa }}</p>
            </div>
        </template>
        <p v-else>Cargando...</p>
    </Dialog>

    <!-- Dialog para mostrar esquema de precios -->
    <Dialog 
        v-model:visible="esquemaPrecioVisible" 
        modal 
        :header="esquemaPrecioSeleccionado ? `Esquema de Precios: ${esquemaPrecioSeleccionado.nombre}` : 'Esquema de Precios'"
        :style="{ width: '70rem' }" 
        :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
        dismissableMask
    >
        <template v-if="esquemaPrecioSeleccionado && esquemaPrecioSeleccionado.membresias">
            <DataTable 
                :value="esquemaPrecioSeleccionado.membresias"
                stripedRows
                tableStyle="min-width: 50rem"
            >
                <!-- Columna Membresía / Entidad -->
                <Column header="Membresía - Entidad">
                    <template #body="{ data: mem }">
                        {{ mem.membresia
                            ? mem.membresia.nombre + ' - ' + mem.membresia.entidad.abreviacion
                            : '—'
                        }}
                    </template>
                </Column>

                <!-- Columna Precio -->
                <Column header="Precio">
                    <template #body="{ data: mem }">
                        $ {{ parseFloat(mem.precio).toLocaleString('es-AR', { minimumFractionDigits: 0 }) }}
                    </template>
                </Column>

                <!-- Columna Moneda -->
                <Column header="Moneda">
                    <template #body="{ data: mem }">
                        {{ mem.moneda ? mem.moneda.nombre : '—' }}
                    </template>
                </Column>
            </DataTable>
        </template>
        <p v-else>No hay información de precios disponible</p>
    </Dialog>
</template>