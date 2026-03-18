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
    import { computed, ref } from 'vue';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';
    import Dialog from 'primevue/dialog';
    import { usePage } from '@inertiajs/vue3';
    import Toast from 'primevue/toast';
    import { useToast } from 'primevue/usetoast';

    const page = usePage();
    const toast = useToast();
    const visible = ref(false);
    const actividadSeleccionada = ref(null);
    const imagenVisible = ref(false);
    const imagenSeleccionada = ref(null);
    const programaVisible = ref(false);
    const programaSeleccionado = ref(null);
    const esquemaPrecioVisible = ref(false);
    const esquemaPrecioSeleccionado = ref(null);
    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });
    const filtroFechaInicio = ref('mes_actual');

    const actividadesFiltradas = computed(() => {
        if (filtroFechaInicio.value === 'todo') {
            return actividades;
        }

        const ahora = new Date();
        const inicioHoy = new Date(ahora);
        inicioHoy.setHours(0, 0, 0, 0);

        const inicioMesActual = new Date(ahora.getFullYear(), ahora.getMonth(), 1, 0, 0, 0, 0);

        let limite = null;
        if (filtroFechaInicio.value === 'ultimo_mes') {
            limite = new Date(inicioHoy);
            limite.setMonth(limite.getMonth() - 1);
        } else if (filtroFechaInicio.value === 'ultimos_tres_meses') {
            limite = new Date(inicioHoy);
            limite.setMonth(limite.getMonth() - 3);
        }

        return actividades.filter((actividad) => {
            if (!actividad?.fecha_inicio) return false;
            const fechaInicio = new Date(actividad.fecha_inicio);
            if (Number.isNaN(fechaInicio.getTime())) return false;

            if (filtroFechaInicio.value === 'mes_actual') {
                return fechaInicio >= inicioMesActual;
            }

            return limite ? fechaInicio >= limite : true;
        });
    });
    
    const { actividades } = defineProps({
        actividades: {
            type: Array,
            required: true
        }
    });

    // Controla qué filas de la tabla principal están expandidas
    const expandedRows = ref([]);
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

    const verImagen = (actividad) => {
        if (!actividad) return;
        const src = actividad.imagen
            ? `/storage/${actividad.imagen.ruta}`
            : '/storage/img/actividades/imagen-no-disponible.jpg';
        imagenSeleccionada.value = {
            src,
            nombre: actividad.nombre || 'Actividad'
        };
        imagenVisible.value = true;
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
                                toast.add({
                    severity: 'success',
                    summary: 'Estado actualizado',
                    detail: nuevoEstado ? 'Actividad activada correctamente.' : 'Actividad desactivada correctamente.',
                    life: 3000,
                });
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

    const direccionActividad = (actividad) => {
        return actividad?.lugar?.direccion || actividad?.entidad?.direccion || '';
    };

    const normalizeText = (value) => String(value ?? '').toLowerCase();

    const actividadesFiltradasMobile = computed(() => {
        const query = normalizeText(filters.value?.global?.value);
        if (!query) return actividadesFiltradas.value;

        return actividadesFiltradas.value.filter((actividad) => {
            const fields = [
                actividad?.nombre,
                actividad?.tipo_actividad?.abreviacion,
                actividad?.modalidad?.nombre,
                actividad?.entidad?.abreviacion,
                actividad?.lugar?.abreviacion,
            ];
            return fields.some((field) => normalizeText(field).includes(query));
        });
    });
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Cursos, Retiros y Eventos especiales</h1>
        </template>
        <Toast position="top-right" />
        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white border-b border-gray-200 max-w-[108rem] mx-auto p-0 sm:p-6">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create actividades')">
                        <Link :href="route('actividades.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA ACTIVIDAD
                        </Link>
                    </div>
                    <div class="mt-4">
                        <div class="sm:hidden px-4">
                            <div class="flex flex-col gap-2">
                                <select
                                    v-model="filtroFechaInicio"
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
                        </div>

                        <div class="space-y-4 sm:hidden">
                            <div
                                v-for="actividad in actividadesFiltradasMobile"
                                :key="actividad.id"
                                class="overflow-hidden border border-gray-200 bg-white shadow-sm"
                            >
                                <button
                                    type="button"
                                    class="block w-full bg-gray-100"
                                    title="Ver imagen"
                                    @click="verImagen(actividad)"
                                >
                                    <img
                                        v-if="actividad.imagen"
                                        :src="'/storage/' + actividad.imagen.ruta"
                                        :alt="`Imagen de ${actividad.nombre || 'Actividad'}`"
                                        class="h-auto w-full object-contain"
                                    />
                                    <img
                                        v-else
                                        src="/storage/img/actividades/imagen-no-disponible.jpg"
                                        alt="Sin imagen"
                                        class="h-auto w-full object-contain"
                                    />
                                </button>

                                <div class="space-y-3 p-4">
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-gray-800">
                                            {{ actividad.nombre || '-' }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            {{ actividad.tipo_actividad?.abreviacion || '-' }}
                                        </p>
                                    </div>

                                    <div class="space-y-2 text-sm text-gray-700">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Lugar</span>
                                            <span>{{ actividad.entidad?.abreviacion || actividad.lugar?.abreviacion || '-' }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Fecha inicio</span>
                                            <span>
                                                {{ actividad.fecha_inicio ? new Date(actividad.fecha_inicio).toLocaleDateString('es-AR') : '-' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Hora inicio</span>
                                            <span>
                                                {{ actividad.fecha_inicio ? new Date(actividad.fecha_inicio).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) + ' hs.' : '-' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Modalidad</span>
                                            <span>{{ actividad.modalidad?.nombre || '-' }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-gray-500">Estado</span>
                                            <InputSwitch
                                                :modelValue="actividad.estado"
                                                @update:modelValue="updateEstado(actividad, $event)"
                                            />
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        @click="toggleCardExpanded(actividad.id)"
                                    >
                                        <span>{{ isCardExpanded(actividad.id) ? 'Ocultar detalles' : 'Ver mas detalles' }}</span>
                                        <i
                                            class="pi"
                                            :class="isCardExpanded(actividad.id) ? 'pi-chevron-up' : 'pi-chevron-down'"
                                        ></i>
                                    </button>

                                    <div v-if="isCardExpanded(actividad.id)" class="rounded-md border border-gray-200 bg-gray-50 p-3">
                                        <div class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                                            <div v-if="actividad.entidad">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lugar</p>
                                                <p>{{ actividad.lugar?.nombre || actividad.entidad.abreviacion }}</p>
                                                <p v-if="direccionActividad(actividad)" class="text-xs text-gray-600">
                                                    {{ direccionActividad(actividad) }}
                                                </p>
                                            </div>
                                            <div v-if="actividad.descripcion">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Descripcion</p>
                                                <p>{{ actividad.descripcion.descripcion }}</p>
                                            </div>
                                            <div v-if="actividad.observaciones">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Observaciones</p>
                                                <p>{{ actividad.observaciones }}</p>
                                            </div>
                                            <div v-if="actividad.disponibilidad">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Disponibilidad</p>
                                                <p>{{ actividad.disponibilidad.descripcion }}</p>
                                            </div>
                                            <div v-if="actividad.maestros && actividad.maestros.length">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Maestros</p>
                                                <p>{{ actividad.maestros.map((maestro) => maestro.nombre).join(', ') }}</p>
                                            </div>
                                            <div v-if="actividad.coordinadores && actividad.coordinadores.length">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Coordinadores</p>
                                                <p>{{ actividad.coordinadores.map((coordinador) => coordinador.nombre).join(', ') }}</p>
                                            </div>
                                            <div v-if="actividad.programa" class="flex items-center gap-2">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Programa</p>
                                                    <p>{{ actividad.programa.nombre }}</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="text-indigo-600 hover:text-indigo-800"
                                                    title="Ver programa completo"
                                                    @click="verPrograma(actividad.programa)"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div v-if="actividad.esquema_precio" class="flex items-center gap-2">
                                                <div>
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Esquema de precios</p>
                                                    <p>{{ actividad.esquema_precio.nombre }}</p>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="text-indigo-600 hover:text-indigo-800"
                                                    title="Ver esquema de precios completo"
                                                    @click="verEsquemaPrecio(actividad.esquema_precio)"
                                                >
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div v-if="actividad.esquema_descuento">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Esquema descuentos</p>
                                                <p>{{ actividad.esquema_descuento.nombre }}</p>
                                            </div>
                                            <div v-if="actividad.hospedajes && actividad.hospedajes.length">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hospedajes</p>
                                                <p>{{ actividad.hospedajes.map((hospedaje) => hospedaje.nombre).join(', ') }}</p>
                                            </div>
                                            <div v-if="actividad.comidas && actividad.comidas.length">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Comidas</p>
                                                <p>{{ actividad.comidas.map((comida) => comida.nombre).join(', ') }}</p>
                                            </div>
                                            <div v-if="actividad.transportes && actividad.transportes.length">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Transportes</p>
                                                <p>{{ actividad.transportes.map((transporte) => transporte.descripcion).join(', ') }}</p>
                                            </div>
                                            <div v-if="actividad.stream">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Stream</p>
                                                <p>{{ actividad.stream.titulo || 'Disponible' }}</p>
                                            </div>
                                            <div v-if="actividad.grabacion">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Grabacion</p>
                                                <p>{{ actividad.grabacion.titulo || 'Disponible' }}</p>
                                            </div>
                                            <div v-if="actividad.fecha_fin">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha fin</p>
                                                <p>{{ new Date(actividad.fecha_fin).toLocaleDateString('es-AR') }}</p>
                                            </div>
                                            <div v-if="actividad.fecha_fin">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hora fin</p>
                                                <p>{{ new Date(actividad.fecha_fin).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', hour12: false }) }} hs.</p>
                                            </div>
                                            <div v-if="actividad.pagoAmticipado">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha pago anticipado</p>
                                                <p>{{ new Date(actividad.pagoAmticipado).toLocaleDateString('es-AR') }}</p>
                                            </div>
                                            <div v-if="actividad.tipo_actividad?.nombre">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de actividad</p>
                                                <p>{{ actividad.tipo_actividad.nombre }}</p>
                                            </div>
                                            <div v-if="actividad.metodos_pago && actividad.metodos_pago.length">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Metodos de pago</p>
                                                <div class="flex flex-wrap gap-2">
                                                    <Tag v-for="metodo in actividad.metodos_pago" :key="metodo.id" severity="info" :value="metodo.nombre"></Tag>
                                                </div>
                                                <div>
                                                    <span v-for="(metodo, index) in actividad.metodos_pago" :key="metodo.id" class="text-xs block">
                                                        {{ metodo.descripcion }}<span v-if="index < actividad.metodos_pago.length - 1"></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-if="actividad.link_web">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Link web</p>
                                                <a :href="actividad.link_web" target="_blank" class="text-blue-500 hover:underline text-sm">{{ actividad.link_web }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 bg-white px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <Link
                                            :href="`${route('grid-actividades.show-public', actividad.id)}?return_url=${encodeURIComponent(route('actividades.index'))}`"
                                            class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 px-3"
                                            title="Ver landing publica"
                                            aria-label="Ver landing publica"
                                        >
                                            <i class="fas fa-eye"></i>
                                            <span class="text-xs font-semibold">Ver landing publica</span>
                                        </Link>
                                        <Link
                                            v-if="$page.props.user.permissions.includes('update actividades')"
                                            :href="route('actividades.edit', { actividad: parseInt(actividad.id) })"
                                            class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 px-3"
                                            title="Editar actividad"
                                            aria-label="Editar actividad"
                                        >
                                            <i class="fas fa-pen-to-square"></i>
                                            <span class="text-xs font-semibold">Editar actividad</span>
                                        </Link>
                                        <button
                                            v-if="$page.props.user.permissions.includes('delete actividades')"
                                            type="button"
                                            @click="deleteActividad(parseInt(actividad.id))"
                                            class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3"
                                            title="Borrar actividad"
                                            aria-label="Borrar actividad"
                                        >
                                            <i class="fas fa-trash"></i>
                                            <span class="text-xs font-semibold">Borrar actividad</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DataTable 
                        :value="actividadesFiltradas" 
                        v-model:filters="filters"
                        :globalFilterFields="['nombre', 'tipo_actividad.abreviacion', 'modalidad.nombre', 'entidad.abreviacion', 'lugar.abreviacion']"
                        stripedRows 
                        removableSort 
                        paginator 
                        :rows="10" 
                        v-model:expandedRows="expandedRows"
                        dataKey="id"
                        :rowsPerPageOptions="[5, 10, 20, 50]" 
                        tableStyle="min-width: 50rem"
                        class="hidden sm:block"
                        >
                            <template #header>
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                    <select
                                        v-model="filtroFechaInicio"
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
                            <Column expander style="width: 5rem" />
                            <Column header="Imagen">
                                <template #body="slotProps">
                                    <div>
                                        <img
                                            v-if="slotProps.data.imagen"
                                            :src="'/storage/' + slotProps.data.imagen.ruta"
                                            alt="Imagen de la Actividad"
                                            style="max-width: 50px; max-height: 50px;"
                                            class="cursor-zoom-in hover:opacity-80"
                                            @click="verImagen(slotProps.data)"
                                        />
                                        <img
                                            v-else
                                            src="/storage/img/actividades/imagen-no-disponible.jpg"
                                            alt="Sin imagen"
                                            style="max-width: 50px; max-height: 50px;"
                                            class="cursor-zoom-in hover:opacity-80"
                                            @click="verImagen(slotProps.data)"
                                        />
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column field="tipo_actividad.abreviacion" header="Tipo" sortable></Column>
                            <Column header="Lugar" sortable>
                                <template #body="slotProps">
                                    {{ slotProps.data.entidad?.abreviacion || slotProps.data.lugar?.abreviacion || '-' }}
                                </template>
                            </Column>
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
                                        <Link
                                            :href="`${route('grid-actividades.show-public', slotProps.data.id)}?return_url=${encodeURIComponent(route('actividades.index'))}`"
                                            v-tooltip="'Ver landing publica'"
                                            class="text-sky-600 hover:text-sky-800"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
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
                                            <span class="ml-2">{{ data.lugar?.nombre || data.entidad.abreviacion }}</span>
                                            <div v-if="direccionActividad(data)" class="text-sm text-gray-600 ml-2">
                                                {{ direccionActividad(data) }}
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

        <p class="mb-3" v-if="direccionActividad(actividadSeleccionada) && direccionActividad(actividadSeleccionada).trim() !== ''">
            {{ direccionActividad(actividadSeleccionada) }}
        </p>

        
        <p class="mb-3" v-if="direccionActividad(actividadSeleccionada) && direccionActividad(actividadSeleccionada).trim() !== ''">
            {{ direccionActividad(actividadSeleccionada) }}
        </p>
        
        <p class="mb-3" v-if="actividadSeleccionada.descripcion && actividadSeleccionada.descripcion.descripcion.trim() !== ''">
            {{ actividadSeleccionada.descripcion.descripcion }}
        </p>



        </template>
        <p v-else>Cargando datos...</p>
    </Dialog>

    <!-- Dialog para mostrar imagen -->
    <Dialog
        v-model:visible="imagenVisible"
        modal
        :header="imagenSeleccionada ? `Imagen de ${imagenSeleccionada.nombre}` : 'Imagen'"
        :style="{ width: '60rem' }"
        :breakpoints="{ '1199px': '85vw', '575px': '95vw' }"
        dismissableMask
    >
        <template v-if="imagenSeleccionada">
            <div class="flex justify-center">
                <img
                    :src="imagenSeleccionada.src"
                    :alt="`Imagen de ${imagenSeleccionada.nombre}`"
                    class="max-w-full h-auto"
                />
            </div>
        </template>
        <p v-else>Cargando...</p>
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
