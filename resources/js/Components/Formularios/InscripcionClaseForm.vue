<script setup>
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
    form: { type: Object, required: true },
    clases: { type: Array, default: () => [] },
    entidades: { type: Array, default: () => [] },
    librosTharpa: { type: Array, default: () => [] },
    oracionesTharpa: { type: Array, default: () => [] },
    arteTharpa: { type: Array, default: () => [] },
    otrosTharpa: { type: Array, default: () => [] },
    provincias: { type: Array, default: () => [] },
    municipios: { type: Array, default: () => [] },
    barrios: { type: Array, default: () => [] },
    metodosPago: { type: Array, default: () => [] },
    buttonLabel: { type: String, default: 'Registrar' },
});

const emit = defineEmits(['submit']);

// Categorías de producto Tharpa: campo de IDs en el form + prop con el catálogo inicial.
const CATS = [
    { key: 'libro', idsField: 'libro_ids', label: 'Libros', prop: 'librosTharpa' },
    { key: 'oracion', idsField: 'oracion_ids', label: 'Oraciones', prop: 'oracionesTharpa' },
    { key: 'arte', idsField: 'arte_ids', label: 'Arte', prop: 'arteTharpa' },
    { key: 'otro', idsField: 'otro_ids', label: 'Otros', prop: 'otrosTharpa' },
];

const catalogoInicial = () => ({
    libro: [...(props.librosTharpa || [])],
    oracion: [...(props.oracionesTharpa || [])],
    arte: [...(props.arteTharpa || [])],
    otro: [...(props.otrosTharpa || [])],
});

const buscandoUsuario = ref(false);
const cargandoProductos = ref(false);
const usuarioEncontrado = ref(false);
const membresiaPendiente = ref('');
const productosEntidad = ref(catalogoInicial());
const precioData = ref({ precio_general: 0, membresias: [] });

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
    if (!props.form.entidad_id) return props.clases || [];
    return (props.clases || []).filter((clase) => Number(clase.entidad_id) === Number(props.form.entidad_id));
});

const membresiasDisponibles = computed(() => precioData.value.membresias || []);

const normalizarTexto = (valor) => String(valor || '')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .trim()
    .toLowerCase();

const aplicarMembresiaPendiente = () => {
    if (!membresiaPendiente.value) return;
    const opcion = membresiasDisponibles.value.find((m) => normalizarTexto(m.nombre) === normalizarTexto(membresiaPendiente.value));
    if (!opcion) return;
    props.form.membresia = opcion.nombre;
    membresiaPendiente.value = '';
};

const seleccionarSinMembresia = () => {
    if (!mostrarCamposUsuario.value) return;
    const opcion = membresiasDisponibles.value.find((m) => {
        const nombre = normalizarTexto(m.nombre);
        return nombre === 'sin membresia' || nombre.includes('sin membresia');
    });
    if (!opcion) return;
    props.form.membresia = opcion.nombre;
};

const recalcularMontos = () => {
    const membresiaSel = membresiasDisponibles.value.find((m) => String(m.nombre) === String(props.form.membresia));
    const montoActividad = Number(membresiaSel?.precio || 0);

    let montoTharpa = 0;
    const titulos = [];
    for (const cat of CATS) {
        const ids = (props.form[cat.idsField] || []).map((id) => Number(id));
        const disponibles = productosEntidad.value[cat.key] || [];
        const seleccionados = disponibles.filter((p) => ids.includes(Number(p.id)));
        montoTharpa += seleccionados.reduce((acc, p) => acc + Number(p.precio || 0), 0);
        seleccionados.forEach((p) => titulos.push(p.titulo));
    }

    props.form.articulos_tharpa = titulos.join(', ');
    props.form.montoTharpa = Number(montoTharpa.toFixed(2));
    const montoTienda = Number(props.form.montoTienda || 0);
    props.form.precioGeneral = Number(precioData.value.precio_general || 0);
    props.form.montoActividad = montoActividad;
    props.form.montoApagar = Number((montoActividad + montoTharpa + montoTienda).toFixed(2));
};

const cargarProductosPorEntidad = async () => {
    const entidadId = Number(props.form.entidad_id || 0);

    if (!entidadId) {
        productosEntidad.value = catalogoInicial();
        recalcularMontos();
        return;
    }

    cargandoProductos.value = true;
    try {
        for (const cat of CATS) {
            const { data } = await axios.get(route('pos.productos'), { params: { categoria: cat.key, entidad_id: entidadId } });
            productosEntidad.value[cat.key] = Array.isArray(data.productos) ? data.productos : [];
        }
    } catch (error) {
        Swal.fire({
            toast: true, position: 'top-end', icon: 'error',
            title: 'No se pudieron cargar los productos por entidad.',
            showConfirmButton: false, timer: 3000,
        });
    } finally {
        // Purgar selecciones que ya no tienen stock en la entidad.
        for (const cat of CATS) {
            const disponibles = new Set((productosEntidad.value[cat.key] || []).map((p) => Number(p.id)));
            props.form[cat.idsField] = (props.form[cat.idsField] || []).filter((id) => disponibles.has(Number(id)));
        }
        recalcularMontos();
        cargandoProductos.value = false;
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
            throw new Error(data?.errors?.clase_id?.[0] || 'No se pudo obtener el precio de la clase.');
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
            toast: true, position: 'top-end', icon: 'error',
            title: error.message || 'falta precio general para la clase',
            showConfirmButton: false, timer: 3500,
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
    cargarProductosPorEntidad();
}, { immediate: true });

watch(
    () => [props.form.membresia, props.form.libro_ids, props.form.oracion_ids, props.form.arte_ids, props.form.otro_ids, props.form.montoTienda],
    () => recalcularMontos(),
    { deep: true, immediate: true }
);

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
            throw new Error(data?.message || data?.errors?.email?.[0] || 'No se pudo buscar el usuario.');
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
        if (!props.form.nombre) props.form.nombre = '';
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" placeholder="email@ejemplo.com" required />
                <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
            </div>
            <div class="flex items-end">
                <button type="button" class="w-full text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" :disabled="buscandoUsuario" @click="buscarUsuario">
                    {{ buscandoUsuario ? 'Buscando...' : 'Buscar' }}
                </button>
            </div>
        </div>

        <div v-if="usuarioEncontrado" class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            Usuario encontrado. Solo se muestran los datos de inscripción de clase.
        </div>

        <div v-if="mostrarCamposUsuario" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Datos del participante</h3>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                <input v-model="form.nombre" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" required />
                <div v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Provincia</label>
                <select v-model="form.provincia_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="provincia in provinciasFiltradas" :key="provincia.id" :value="provincia.id">{{ provincia.nombre }}</option>
                </select>
            </div>
            <div v-if="municipiosFiltrados.length">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Municipio</label>
                <select v-model="form.municipio_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="municipio in municipiosFiltrados" :key="municipio.id" :value="municipio.id">{{ municipio.nombre }}</option>
                </select>
            </div>
            <div v-if="barriosFiltrados.length">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Barrio</label>
                <select v-model="form.barrio_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option value="">Seleccione</option>
                    <option v-for="barrio in barriosFiltrados" :key="barrio.id" :value="barrio.id">{{ barrio.nombre }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección</label>
                <input v-model="form.direccion" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                <input v-model="form.telefono" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" />
            </div>
            <div class="md:col-span-3">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input v-model="form.registrar_datos" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm" />
                    Deseo registrar mis datos
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-3">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Inscripción de clase</h3>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Entidad</label>
                <select v-model="form.entidad_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option value="">Todas</option>
                    <option v-for="entidad in entidades" :key="entidad.id" :value="entidad.id">{{ entidad.nombre }}</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Clase</label>
                <select v-model="form.clase_id" :disabled="!form.entidad_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm disabled:bg-gray-100 disabled:text-gray-500" required>
                    <option value="">{{ form.entidad_id ? 'Seleccione' : 'Seleccione entidad primero' }}</option>
                    <option v-for="clase in clasesFiltradas" :key="clase.id" :value="clase.id">{{ clase.nombre }}</option>
                </select>
                <div v-if="form.errors.clase_id" class="mt-1 text-sm text-red-600">{{ form.errors.clase_id }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Membresía</label>
                <select v-model="form.membresia" :disabled="mostrarCamposUsuario" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm disabled:bg-gray-100 disabled:text-gray-500" required>
                    <option value="">Seleccione</option>
                    <option v-for="m in membresiasDisponibles" :key="m.nombre" :value="m.nombre">{{ m.nombre }}</option>
                </select>
                <div v-if="form.errors.membresia" class="mt-1 text-sm text-red-600">{{ form.errors.membresia }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio general (Sin membresía)</label>
                <input :value="Number(form.precioGeneral || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900" readonly />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto actividad</label>
                <input :value="Number(form.montoActividad || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900" readonly />
            </div>

            <!-- Productos Tharpa: 4 categorías -->
            <div v-for="cat in CATS" :key="cat.key">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ cat.label }}</label>
                <MultiSelect
                    v-model="form[cat.idsField]"
                    :options="productosEntidad[cat.key]"
                    optionLabel="titulo"
                    optionValue="id"
                    :loading="cargandoProductos"
                    filter
                    display="chip"
                    :maxSelectedLabels="3"
                    placeholder="Seleccione"
                    class="mt-1 w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm"
                >
                    <template #option="slotProps">
                        <div class="flex w-full items-center justify-between gap-3">
                            <span>{{ slotProps.option.titulo }}<span v-if="slotProps.option.tipo" class="text-gray-400"> ({{ slotProps.option.tipo }})</span></span>
                            <span class="text-xs text-gray-500">${{ Number(slotProps.option.precio || 0).toFixed(2) }}</span>
                        </div>
                    </template>
                </MultiSelect>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto Tharpa</label>
                <input :value="Number(form.montoTharpa || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900" readonly />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artículo tienda (suelto)</label>
                <input v-model="form.articulos_tienda" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" placeholder="Texto libre (opcional)" />
                <div v-if="form.errors.articulos_tienda" class="mt-1 text-sm text-red-600">{{ form.errors.articulos_tienda }}</div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto Tienda (suelto)</label>
                <input v-model="form.montoTienda" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" @focus="onMontoTiendaFocus" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto total</label>
                <input :value="Number(form.montoApagar || 0).toFixed(2)" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900" readonly />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pago</label>
                <select v-model="form.pago" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" required>
                    <option value="">Seleccione</option>
                    <option value="Saldado">Saldado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Parcial">Parcial</option>
                </select>
                <div v-if="form.errors.pago" class="mt-1 text-sm text-red-600">{{ form.errors.pago }}</div>
            </div>

            <div v-if="form.pago && form.pago !== 'Pendiente'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Medio de pago</label>
                <select v-model="form.metodo_pago_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm">
                    <option :value="null">—</option>
                    <option v-for="m in metodosPago" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                </select>
            </div>

            <div v-if="form.pago === 'Parcial'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Monto cobrado</label>
                <input v-model.number="form.monto_cobrado" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm" />
            </div>

            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input v-model="form.online" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm" />
                    Online
                </label>
            </div>
        </div>

        <div v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</div>

        <div class="flex justify-end">
            <button type="submit" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" :disabled="form.processing">
                {{ buttonLabel }}
            </button>
        </div>
    </form>
</template>
