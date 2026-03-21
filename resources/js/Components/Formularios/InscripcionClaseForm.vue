<script setup>
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    clases: {
        type: Array,
        default: () => [],
    },
    entidades: {
        type: Array,
        default: () => [],
    },
    librosTharpa: {
        type: Array,
        default: () => [],
    },
    provincias: {
        type: Array,
        default: () => [],
    },
    municipios: {
        type: Array,
        default: () => [],
    },
    barrios: {
        type: Array,
        default: () => [],
    },
    buttonLabel: {
        type: String,
        default: 'Registrar',
    },
});

const emit = defineEmits(['submit']);

const buscandoUsuario = ref(false);
const cargandoLibrosEntidad = ref(false);
const usuarioEncontrado = ref(false);
const membresiaPendiente = ref('');
const librosEntidad = ref([...(props.librosTharpa || [])]);
const precioData = ref({
    precio_general: 0,
    membresias: [],
});

const provinciasFiltradas = computed(() => props.provincias || []);

const municipiosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id, 10);
    if (!provId || Number.isNaN(provId)) return [];

    return (props.municipios || []).filter((m) => parseInt(m.provincia_id, 10) === provId);
});

const barriosFiltrados = computed(() => {
    const provId = parseInt(props.form.provincia_id, 10);
    if (provId !== 24) return [];

    return (props.barrios || []).filter((b) => parseInt(b.provincia_id, 10) === 24);
});

const mostrarCamposUsuario = computed(() => !props.form.existing_user_id);

const clasesFiltradas = computed(() => {
    if (!props.form.entidad_id) {
        return props.clases || [];
    }

    return (props.clases || []).filter((clase) => Number(clase.entidad_id) === Number(props.form.entidad_id));
});

const membresiasDisponibles = computed(() => precioData.value.membresias || []);
const librosTharpaDisponibles = computed(() => librosEntidad.value || []);

const normalizarTexto = (valor) => String(valor || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase();

const aplicarMembresiaPendiente = () => {
    if (!membresiaPendiente.value) {
        return;
    }

    const opcion = membresiasDisponibles.value.find((m) =>
        normalizarTexto(m.nombre) === normalizarTexto(membresiaPendiente.value)
    );

    if (!opcion) {
        return;
    }

    props.form.membresia = opcion.nombre;
    membresiaPendiente.value = '';
};

const seleccionarSinMembresia = () => {
    if (!mostrarCamposUsuario.value) {
        return;
    }

    const opcionSinMembresia = membresiasDisponibles.value.find((m) => {
        const nombre = normalizarTexto(m.nombre);
        return nombre === 'sin membresia' || nombre.includes('sin membresia');
    });

    if (!opcionSinMembresia) {
        return;
    }

    props.form.membresia = opcionSinMembresia.nombre;
};

const recalcularMontos = () => {
    const membresiaSeleccionada = membresiasDisponibles.value.find(
        (m) => String(m.nombre) === String(props.form.membresia)
    );

    const montoActividad = Number(membresiaSeleccionada?.precio || 0);
    const idsSeleccionados = (props.form.libros_tharpa_ids || []).map((id) => Number(id));
    const librosSeleccionados = librosTharpaDisponibles.value.filter((libro) => idsSeleccionados.includes(Number(libro.id)));
    const montoTharpa = librosSeleccionados.reduce((acumulado, libro) => acumulado + Number(libro.precio || 0), 0);
    props.form.articulos_tharpa = librosSeleccionados.map((libro) => libro.titulo).join(', ');
    props.form.montoTharpa = Number(montoTharpa.toFixed(2));
    const montoTienda = Number(props.form.montoTienda || 0);

    props.form.precioGeneral = Number(precioData.value.precio_general || 0);
    props.form.montoActividad = montoActividad;
    props.form.montoApagar = Number((montoActividad + montoTharpa + montoTienda).toFixed(2));
};

const cargarLibrosPorEntidad = async () => {
    const entidadId = Number(props.form.entidad_id || 0);

    if (!entidadId) {
        librosEntidad.value = [...(props.librosTharpa || [])];
        return;
    }

    cargandoLibrosEntidad.value = true;

    try {
        const response = await fetch(`${route('inscripciones-clases.libros-por-entidad')}?entidad_id=${entidadId}`);
        const data = await response.json();

        if (!response.ok) {
            const errorText = data?.message || data?.errors?.entidad_id?.[0] || 'No se pudo obtener libros por entidad.';
            throw new Error(errorText);
        }

        librosEntidad.value = Array.isArray(data.libros) ? data.libros : [];
    } catch (error) {
        librosEntidad.value = [];
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: error.message || 'No se pudo filtrar libros por entidad.',
            showConfirmButton: false,
            timer: 3000,
        });
    } finally {
        const idsDisponibles = new Set(librosEntidad.value.map((libro) => Number(libro.id)));
        props.form.libros_tharpa_ids = (props.form.libros_tharpa_ids || []).filter((id) => idsDisponibles.has(Number(id)));
        recalcularMontos();
        cargandoLibrosEntidad.value = false;
    }
};

watch(() => props.form.provincia_id, () => {
    if (!municipiosFiltrados.value.find((m) => Number(m.id) === Number(props.form.municipio_id))) {
        props.form.municipio_id = '';
    }

    if (Number(props.form.provincia_id) !== 24) {
        props.form.barrio_id = '';
    }
});

watch(() => props.form.clase_id, async (newClaseId) => {
    props.form.membresia = '';
    precioData.value = { precio_general: 0, membresias: [] };

    if (!newClaseId) {
        recalcularMontos();
        return;
    }

    try {
        const response = await fetch(`${route('inscripciones-clases.precios-clase')}?clase_id=${newClaseId}`);
        const data = await response.json();

        if (!response.ok) {
            const errorText = data?.errors?.clase_id?.[0] || 'No se pudo obtener el precio de la clase.';
            throw new Error(errorText);
        }

        precioData.value = {
            precio_general: Number(data.precio_general || 0),
            membresias: Array.isArray(data.membresias) ? data.membresias : [],
        };

        aplicarMembresiaPendiente();
        seleccionarSinMembresia();
        recalcularMontos();
    } catch (error) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: error.message || 'falta precio general para la clase',
            showConfirmButton: false,
            timer: 3500,
        });
    }
});

watch(() => props.form.entidad_id, () => {
    if (!clasesFiltradas.value.find((clase) => Number(clase.id) === Number(props.form.clase_id))) {
        props.form.clase_id = '';
        props.form.membresia = '';
        precioData.value = { precio_general: 0, membresias: [] };
        recalcularMontos();
    }

    cargarLibrosPorEntidad();
}, { immediate: true });

watch(() => [props.form.membresia, props.form.libros_tharpa_ids, props.form.montoTienda], () => {
    recalcularMontos();
}, { deep: true, immediate: true });

watch(() => [membresiasDisponibles.value, props.form.existing_user_id], () => {
    seleccionarSinMembresia();
}, { deep: true, immediate: true });

const buscarUsuario = async () => {
    const email = String(props.form.email || '').trim();

    if (!email) {
        Swal.fire('Atención', 'Ingrese un email para buscar.', 'warning');
        return;
    }

    buscandoUsuario.value = true;

    try {
        const response = await fetch(`${route('inscripciones-clases.buscar-usuario')}?email=${encodeURIComponent(email)}`);
        const data = await response.json();

        if (!response.ok) {
            const errorText = data?.message || data?.errors?.email?.[0] || 'No se pudo buscar el usuario.';
            throw new Error(errorText);
        }

        if (data.found && data.user) {
            usuarioEncontrado.value = true;
            props.form.existing_user_id = data.user.id;
            props.form.nombre = data.user.name || '';
            props.form.email = data.user.email || props.form.email;
            props.form.provincia_id = data.user.provincia_id || '';
            props.form.municipio_id = data.user.municipio_id || '';
            props.form.barrio_id = data.user.barrio_id || '';
            props.form.direccion = data.user.direccion || '';
            props.form.telefono = data.user.telefono || '';
            membresiaPendiente.value = data.user.membresia_activa || '';
            props.form.registrar_datos = true;
            aplicarMembresiaPendiente();
            recalcularMontos();
            return;
        }

        usuarioEncontrado.value = false;
        props.form.existing_user_id = null;
        membresiaPendiente.value = '';
        seleccionarSinMembresia();
        if (!props.form.nombre) {
            props.form.nombre = '';
        }
    } catch (error) {
        Swal.fire('Error', error.message || 'No se pudo realizar la búsqueda.', 'error');
    } finally {
        buscandoUsuario.value = false;
    }
};

const onSubmit = () => {
    recalcularMontos();
    emit('submit');
};

const onMontoTiendaFocus = () => {
    if (Number(props.form.montoTienda) === 0) {
        props.form.montoTienda = '';
    }
};
</script>

<template>
    <form @submit.prevent="onSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    placeholder="email@ejemplo.com"
                    required
                />
                <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
            </div>
            <div class="flex items-end">
                <button
                    type="button"
                    class="w-full text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                    :disabled="buscandoUsuario"
                    @click="buscarUsuario"
                >
                    {{ buscandoUsuario ? 'Buscando...' : 'Buscar' }}
                </button>
            </div>
        </div>

        <div v-if="usuarioEncontrado" class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            Usuario encontrado. Solo se muestran los datos de inscripción de clase.
        </div>

        <div v-if="mostrarCamposUsuario" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <h3 class="text-base font-semibold text-gray-800">Datos del participante</h3>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                <input v-model="form.nombre" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required />
                <div v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Provincia</label>
                <select v-model="form.provincia_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="provincia in provinciasFiltradas" :key="provincia.id" :value="provincia.id">
                        {{ provincia.nombre }}
                    </option>
                </select>
            </div>

            <div v-if="municipiosFiltrados.length">
                <label class="block text-sm font-medium text-gray-700">Municipio</label>
                <select v-model="form.municipio_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="municipio in municipiosFiltrados" :key="municipio.id" :value="municipio.id">
                        {{ municipio.nombre }}
                    </option>
                </select>
            </div>

            <div v-if="barriosFiltrados.length">
                <label class="block text-sm font-medium text-gray-700">Barrio</label>
                <select v-model="form.barrio_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="barrio in barriosFiltrados" :key="barrio.id" :value="barrio.id">
                        {{ barrio.nombre }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                <input v-model="form.direccion" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input v-model="form.telefono" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
            </div>

            <div class="md:col-span-3">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.registrar_datos" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                    Deseo registrar mis datos
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <h3 class="text-base font-semibold text-gray-800">Inscripción de clase</h3>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Entidad</label>
                <select v-model="form.entidad_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Todas</option>
                    <option v-for="entidad in entidades" :key="entidad.id" :value="entidad.id">{{ entidad.nombre }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Clase</label>
                <select
                    v-model="form.clase_id"
                    :disabled="!form.entidad_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-500"
                    required
                >
                    <option value="">{{ form.entidad_id ? 'Seleccione' : 'Seleccione entidad primero' }}</option>
                    <option v-for="clase in clasesFiltradas" :key="clase.id" :value="clase.id">{{ clase.nombre }}</option>
                </select>
                <div v-if="form.errors.clase_id" class="mt-1 text-sm text-red-600">{{ form.errors.clase_id }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Membresía</label>
                <select v-model="form.membresia" :disabled="mostrarCamposUsuario" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100 disabled:text-gray-500" required>
                    <option value="">Seleccione</option>
                    <option v-for="m in membresiasDisponibles" :key="m.nombre" :value="m.nombre">
                        {{ m.nombre }}
                    </option>
                </select>
                <div v-if="form.errors.membresia" class="mt-1 text-sm text-red-600">{{ form.errors.membresia }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Precio general (Sin membresía)</label>
                <input :value="Number(form.precioGeneral || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" readonly />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Monto actividad</label>
                <input :value="Number(form.montoActividad || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" readonly />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Libros Tharpa</label>
                <MultiSelect
                    v-model="form.libros_tharpa_ids"
                    :options="librosTharpaDisponibles"
                    optionLabel="titulo"
                    optionValue="id"
                    :loading="cargandoLibrosEntidad"
                    filter
                    display="chip"
                    :maxSelectedLabels="3"
                    selectedItemsLabel="{0} libros seleccionados"
                    placeholder="Seleccione libros"
                    class="mt-1 w-full border border-gray-300 rounded-md shadow-sm"
                >
                    <template #option="slotProps">
                        <div class="flex w-full items-center justify-between gap-3">
                            <span>{{ slotProps.option.titulo }}</span>
                            <span class="text-xs text-gray-500">${{ Number(slotProps.option.precio || 0).toFixed(2) }}</span>
                        </div>
                    </template>
                </MultiSelect>
                <p class="mt-1 text-xs text-gray-500">Puede seleccionar múltiples libros con Ctrl/Cmd + click.</p>
                <div v-if="form.errors.libros_tharpa_ids" class="mt-1 text-sm text-red-600">{{ form.errors.libros_tharpa_ids }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Monto Tharpa</label>
                <input :value="Number(form.montoTharpa || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" readonly />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Artículo tienda</label>
                <input
                    v-model="form.articulos_tienda"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    placeholder="Ej: incienso, cuenco, mala"
                />
                <div v-if="form.errors.articulos_tienda" class="mt-1 text-sm text-red-600">{{ form.errors.articulos_tienda }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Monto Tienda</label>
                <input v-model="form.montoTienda" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" @focus="onMontoTiendaFocus" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Monto total</label>
                <input :value="Number(form.montoApagar || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" readonly />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Pago</label>
                <select v-model="form.pago" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                    <option value="">Seleccione</option>
                    <option value="Saldado">Saldado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Parcial">Parcial</option>
                </select>
                <div v-if="form.errors.pago" class="mt-1 text-sm text-red-600">{{ form.errors.pago }}</div>
            </div>

            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.online" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                    Online
                </label>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" :disabled="form.processing">
                {{ buttonLabel }}
            </button>
        </div>
    </form>
</template>
