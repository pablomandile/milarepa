<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';
import InscripcionClaseForm from '@/Components/Formularios/InscripcionClaseForm.vue';
import InscripcionActividadPosForm from '@/Components/Formularios/InscripcionActividadPosForm.vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import AutoComplete from 'primevue/autocomplete';
import axios from 'axios';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const props = defineProps({
    entidades: { type: Array, default: () => [] },
    entidadPrincipalId: { type: Number, default: null },
    metodosPago: { type: Array, default: () => [] },
    categorias: { type: Array, default: () => [] },
    categoriasTienda: { type: Array, default: () => [] },
    clases: { type: Array, default: () => [] },
    actividades: { type: Array, default: () => [] },
    librosTharpa: { type: Array, default: () => [] },
    oracionesTharpa: { type: Array, default: () => [] },
    arteTharpa: { type: Array, default: () => [] },
    otrosTharpa: { type: Array, default: () => [] },
    provincias: { type: Array, default: () => [] },
    municipios: { type: Array, default: () => [] },
    barrios: { type: Array, default: () => [] },
});

const CATEGORIA_LABELS = { libro: 'Libro', oracion: 'Oración', arte: 'Arte', otro: 'Otro' };

// Ámbitos del primer select. El producto termina viajando al backend con un `tipo`
// concreto (categoría Tharpa o 'articulo_tienda'); el ámbito/categoría son solo UI.
const AMBITO_OPTIONS = [
    { value: 'tienda', label: 'Tienda' },
    { value: 'tharpa', label: 'Tharpa' },
    { value: 'inscripcion_clase', label: 'Inscripción a clase' },
    { value: 'inscripcion_actividad', label: 'Inscripción a actividad' },
];

const hoy = new Date().toISOString().split('T')[0];

const form = useForm({
    fecha: hoy,
    entidad_id: props.entidadPrincipalId || null,
    cliente_user_id: null,
    metodo_pago_id: null,
    observaciones: '',
    comprobante: null,
    items: [],
});

const cart = ref([]);
const total = computed(() => cart.value.reduce((s, i) => s + Number(i.subtotal || 0), 0));

const esInscripcion = computed(() => ['inscripcion_clase', 'inscripcion_actividad'].includes(ambito.value));
const esProducto = computed(() => ['tienda', 'tharpa'].includes(ambito.value));

const categoriaOptions = computed(() => {
    if (ambito.value === 'tharpa') {
        return (props.categorias || []).map((c) => ({ value: c, label: CATEGORIA_LABELS[c] || c }));
    }
    if (ambito.value === 'tienda') {
        return (props.categoriasTienda || []).map((c) => ({ value: c.id, label: c.nombre }));
    }
    return [];
});

const formatPrice = (v) =>
    new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 2 }).format(Number(v || 0));

/* ---------- Cliente (AutoComplete) ---------- */
const clienteSeleccionado = ref(null);
const clientesSugeridos = ref([]);
const buscarClientes = async (event) => {
    const q = String(event?.query || '').trim();
    if (q.length < 2) { clientesSugeridos.value = []; return; }
    try {
        const { data } = await axios.get(route('pos.buscar-usuarios'), { params: { q } });
        clientesSugeridos.value = (data.usuarios || []).map((u) => ({ ...u, label: `${u.name} — ${u.email}` }));
    } catch (e) {
        clientesSugeridos.value = [];
    }
};
const onClienteSelect = () => { form.cliente_user_id = clienteSeleccionado.value?.id ?? null; };
const onClienteClear = () => { form.cliente_user_id = null; };

const onEntidadChange = () => {
    if (cart.value.length) {
        cart.value = [];
        Swal.fire({ icon: 'info', title: 'Carrito vaciado', text: 'Cambió la entidad: se vació el carrito.', timer: 1800, showConfirmButton: false });
    }
};

/* ---------- Dialog "Agregar ítem" ---------- */
const dialogVisible = ref(false);
const ambito = ref(null);
const categoriaSel = ref(null);
const productosDisponibles = ref([]);
const productoSeleccionado = ref(null);
const lineaCantidad = ref(1);
const cargandoProductos = ref(false);

const nuevoClaseForm = () => ({
    email: '', nombre: '', provincia_id: '', municipio_id: '', barrio_id: '', direccion: '', telefono: '',
    registrar_datos: true, existing_user_id: null, entidad_id: '', clase_id: '', membresia: '',
    precioGeneral: 0, montoActividad: 0, montoTharpa: 0, articulos_tharpa: '', montoTienda: 0, articulos_tienda: '',
    libro_ids: [], oracion_ids: [], arte_ids: [], otro_ids: [], montoApagar: 0,
    pago: 'Pendiente', metodo_pago_id: null, monto_cobrado: null, online: false, errors: {},
});
const claseForm = ref(nuevoClaseForm());

const nuevoActividadForm = () => ({
    actividad_id: '', email: '', nombre: '', provincia_id: '', municipio_id: '', barrio_id: '', telefono: '',
    registrar_datos: true, incluye_grabacion: false, modalidad_cursada: null,
    comidas_ids: [], transportes_ids: [], hospedajes_ids: [],
    montoActividad: 0, montoApagar: 0, membresia: '', errors: {},
});
const actividadForm = ref(nuevoActividadForm());

const abrirDialog = () => {
    if (!form.entidad_id) {
        Swal.fire({ icon: 'warning', title: 'Elegí la entidad', text: 'Seleccioná la entidad (caja) antes de agregar ítems.' });
        return;
    }
    ambito.value = null;
    categoriaSel.value = null;
    productosDisponibles.value = [];
    productoSeleccionado.value = null;
    lineaCantidad.value = 1;
    claseForm.value = nuevoClaseForm();
    actividadForm.value = nuevoActividadForm();
    dialogVisible.value = true;
};

const onAmbitoChange = () => {
    categoriaSel.value = null;
    productoSeleccionado.value = null;
    productosDisponibles.value = [];
    if (ambito.value === 'inscripcion_clase') {
        claseForm.value = nuevoClaseForm();
        return;
    }
    if (ambito.value === 'inscripcion_actividad') {
        actividadForm.value = nuevoActividadForm();
        return;
    }
    // Producto (tienda/tharpa): esperar a que elija la categoría.
};

const onCategoriaChange = () => {
    productoSeleccionado.value = null;
    if (esProducto.value && categoriaSel.value != null && categoriaSel.value !== '') {
        cargarProductos();
    } else {
        productosDisponibles.value = [];
    }
};

const cargarProductos = async () => {
    productosDisponibles.value = [];
    if (!esProducto.value || categoriaSel.value == null || categoriaSel.value === '') return;
    cargandoProductos.value = true;
    try {
        const params = { categoria: categoriaSel.value, entidad_id: form.entidad_id };
        if (ambito.value === 'tienda') params.ambito = 'tienda';
        const { data } = await axios.get(route('pos.productos'), { params });
        productosDisponibles.value = (data.productos || []).map((p) => ({
            ...p,
            label: `${p.titulo}${p.tipo ? ' (' + p.tipo + ')' : ''} — ${formatPrice(p.precio)} · stock ${p.stock_disponible}`,
        }));
    } catch (e) {
        productosDisponibles.value = [];
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los productos.' });
    } finally {
        cargandoProductos.value = false;
    }
};

const stockMax = computed(() => Number(productoSeleccionado.value?.stock_disponible || 0));

const categoriaLabelActual = () => {
    if (ambito.value === 'tharpa') return CATEGORIA_LABELS[categoriaSel.value] || categoriaSel.value;
    if (ambito.value === 'tienda') {
        const c = (props.categoriasTienda || []).find((x) => Number(x.id) === Number(categoriaSel.value));
        return `Tienda · ${c?.nombre || 'Artículo'}`;
    }
    return '';
};

const agregarAlCarrito = () => {
    const p = productoSeleccionado.value;
    if (!esProducto.value || !p) return;
    let cant = Math.max(1, parseInt(lineaCantidad.value || 1, 10));
    if (cant > stockMax.value) cant = stockMax.value;
    if (cant < 1) return;

    // El `tipo` que viaja al backend: categoría Tharpa concreta, o 'articulo_tienda'.
    const tipoLinea = ambito.value === 'tienda' ? 'articulo_tienda' : categoriaSel.value;

    const existente = cart.value.find((i) => i.tipo === tipoLinea && i.producto_id === p.id);
    if (existente) {
        existente.cantidad = Math.min(stockMax.value, existente.cantidad + cant);
        existente.subtotal = Number((existente.precio_unitario * existente.cantidad).toFixed(2));
    } else {
        cart.value.push({
            tipo: tipoLinea,
            producto_id: p.id,
            titulo: p.titulo,
            tipo_producto: p.tipo,
            categoria_label: categoriaLabelActual(),
            precio_unitario: Number(p.precio),
            cantidad: cant,
            subtotal: Number((Number(p.precio) * cant).toFixed(2)),
        });
    }
    dialogVisible.value = false;
};

const agregarClaseAlCarrito = () => {
    const f = claseForm.value;
    if (!f.clase_id || !f.email) {
        Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Completá al menos email y clase.' });
        return;
    }
    const subtotal = Number(Number(f.montoApagar || 0).toFixed(2));
    const claseNombre = (props.clases.find((c) => Number(c.id) === Number(f.clase_id))?.nombre) || 'Clase';

    cart.value.push({
        tipo: 'inscripcion_clase',
        categoria_label: 'Inscripción a clase',
        titulo: `${claseNombre} — ${f.nombre || f.email}`,
        tipo_producto: null,
        precio_unitario: subtotal,
        cantidad: 1,
        subtotal,
        inscripcion: {
            email: f.email, nombre: f.nombre, provincia_id: f.provincia_id, municipio_id: f.municipio_id,
            barrio_id: f.barrio_id, direccion: f.direccion, telefono: f.telefono, registrar_datos: f.registrar_datos,
            existing_user_id: f.existing_user_id, clase_id: f.clase_id, membresia: f.membresia, pago: f.pago || 'Pendiente',
            online: f.online, montoTienda: f.montoTienda, articulos_tienda: f.articulos_tienda,
            libro_ids: f.libro_ids, oracion_ids: f.oracion_ids, arte_ids: f.arte_ids, otro_ids: f.otro_ids,
        },
    });
    dialogVisible.value = false;
};

const agregarActividadAlCarrito = () => {
    const f = actividadForm.value;
    if (!f.actividad_id || !f.email) {
        Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Completá al menos email y actividad.' });
        return;
    }
    const subtotal = Number(Number(f.montoApagar || 0).toFixed(2));
    const actNombre = (props.actividades.find((a) => Number(a.id) === Number(f.actividad_id))?.nombre) || 'Actividad';

    cart.value.push({
        tipo: 'inscripcion_actividad',
        categoria_label: 'Inscripción a actividad',
        titulo: `${actNombre} — ${f.nombre || f.email}`,
        tipo_producto: null,
        precio_unitario: subtotal,
        cantidad: 1,
        subtotal,
        inscripcion: {
            actividad_id: f.actividad_id, email: f.email, nombre: f.nombre, provincia_id: f.provincia_id,
            municipio_id: f.municipio_id, barrio_id: f.barrio_id, telefono: f.telefono, registrar_datos: f.registrar_datos,
            incluye_grabacion: f.incluye_grabacion, modalidad_cursada: f.modalidad_cursada,
            comidas_ids: f.comidas_ids, transportes_ids: f.transportes_ids, hospedajes_ids: f.hospedajes_ids,
        },
    });
    dialogVisible.value = false;
};

const quitarDelCarrito = (idx) => { cart.value.splice(idx, 1); };

const finalizar = () => {
    if (!cart.value.length) {
        Swal.fire({ icon: 'warning', title: 'Carrito vacío', text: 'Agregá al menos un ítem.' });
        return;
    }
    if (!form.metodo_pago_id) {
        Swal.fire({ icon: 'warning', title: 'Falta el medio de pago' });
        return;
    }
    form.transform((data) => ({
        ...data,
        items: cart.value.map((i) => (
            (i.tipo === 'inscripcion_clase' || i.tipo === 'inscripcion_actividad')
                ? { tipo: i.tipo, inscripcion: i.inscripcion, cantidad: 1 }
                : { tipo: i.tipo, producto_id: i.producto_id, cantidad: i.cantidad }
        )),
    })).post(route('pos.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            clienteSeleccionado.value = null;
            form.reset('cliente_user_id', 'observaciones', 'comprobante');
            Swal.fire({ icon: 'success', title: 'Venta registrada', timer: 1800, showConfirmButton: false });
        },
    });
};
</script>

<template>
    <Head title="Punto de venta" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Punto de venta</h1>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Fecha</label>
                        <input v-model="form.fecha" type="date" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Entidad (caja)</label>
                        <select v-model="form.entidad_id" @change="onEntidadChange" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option :value="null">Seleccionar…</option>
                            <option v-for="e in entidades" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                        </select>
                        <p v-if="form.errors.entidad_id" class="text-xs text-red-500 mt-1">{{ form.errors.entidad_id }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Cliente (opcional)</label>
                        <AutoComplete
                            v-model="clienteSeleccionado"
                            :suggestions="clientesSugeridos"
                            optionLabel="label"
                            :minLength="2"
                            :delay="300"
                            placeholder="Buscar por nombre o email…"
                            class="w-full"
                            inputClass="w-full"
                            @complete="buscarClientes"
                            @item-select="onClienteSelect"
                            @clear="onClienteClear"
                        />
                    </div>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100">Carrito</h2>
                        <button type="button" @click="abrirDialog" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            + Agregar ítem
                        </button>
                    </div>

                    <div v-if="!cart.length" class="text-center text-gray-500 dark:text-gray-400 py-8">
                        El carrito está vacío. Usá "Agregar ítem".
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                    <th class="py-2">Tipo</th>
                                    <th class="py-2">Descripción</th>
                                    <th class="py-2 text-right">Precio</th>
                                    <th class="py-2 text-center">Cant.</th>
                                    <th class="py-2 text-right">Subtotal</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(i, idx) in cart" :key="idx" class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="py-2">{{ i.categoria_label }}</td>
                                    <td class="py-2">{{ i.titulo }}<span v-if="i.tipo_producto" class="text-gray-400"> ({{ i.tipo_producto }})</span></td>
                                    <td class="py-2 text-right">{{ formatPrice(i.precio_unitario) }}</td>
                                    <td class="py-2 text-center">{{ i.cantidad }}</td>
                                    <td class="py-2 text-right font-semibold">{{ formatPrice(i.subtotal) }}</td>
                                    <td class="py-2 text-right">
                                        <button type="button" @click="quitarDelCarrito(idx)" class="text-red-600 hover:text-red-800" title="Quitar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="py-3 text-right font-semibold">Total</td>
                                    <td class="py-3 text-right text-lg font-bold text-emerald-600">{{ formatPrice(total) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p v-if="form.errors.items" class="text-sm text-red-500 mt-2">{{ form.errors.items }}</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Medio de pago</label>
                        <select v-model="form.metodo_pago_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option :value="null">Seleccionar…</option>
                            <option v-for="m in metodosPago" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                        </select>
                        <p v-if="form.errors.metodo_pago_id" class="text-xs text-red-500 mt-1">{{ form.errors.metodo_pago_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Comprobante (opcional)</label>
                        <SingleImageUploader v-model:file="form.comprobante" folder="img/mpago" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="2" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="md:col-span-2 flex items-center justify-end">
                        <button type="button" @click="finalizar" :disabled="form.processing" class="text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 py-2 px-6 rounded font-semibold">
                            Finalizar venta ({{ formatPrice(total) }})
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="dialogVisible" modal header="Agregar ítem" :style="{ width: esInscripcion ? '56rem' : '30rem' }">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Ámbito</label>
                    <select v-model="ambito" @change="onAmbitoChange" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <option :value="null">Seleccionar…</option>
                        <option v-for="a in AMBITO_OPTIONS" :key="a.value" :value="a.value">{{ a.label }}</option>
                    </select>
                </div>

                <!-- Producto (Tienda / Tharpa): categoría → producto -->
                <template v-if="esProducto">
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Categoría</label>
                        <select v-model="categoriaSel" @change="onCategoriaChange" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option :value="null">Seleccionar…</option>
                            <option v-for="c in categoriaOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
                        </select>
                        <p v-if="ambito === 'tienda' && !categoriaOptions.length" class="text-xs text-gray-500 mt-1">
                            No hay categorías de tienda cargadas. Creá una en Tienda → Categorías.
                        </p>
                    </div>

                    <div v-if="categoriaSel != null && categoriaSel !== ''">
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Producto</label>
                        <Dropdown
                            v-model="productoSeleccionado"
                            :options="productosDisponibles"
                            optionLabel="label"
                            :loading="cargandoProductos"
                            filter
                            placeholder="Seleccionar producto…"
                            class="w-full"
                        />
                        <p v-if="!cargandoProductos && !productosDisponibles.length" class="text-xs text-gray-500 mt-1">
                            Sin stock disponible en esta entidad para esta categoría.
                        </p>
                    </div>
                    <div v-if="productoSeleccionado">
                        <label class="block text-sm text-gray-600 dark:text-gray-300 mb-1">Cantidad (máx. {{ stockMax }})</label>
                        <input v-model.number="lineaCantidad" type="number" min="1" :max="stockMax" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                </template>

                <!-- Inscripción a clase -->
                <div v-else-if="ambito === 'inscripcion_clase'">
                    <InscripcionClaseForm
                        :form="claseForm"
                        :clases="clases"
                        :entidades="entidades"
                        :libros-tharpa="librosTharpa"
                        :oraciones-tharpa="oracionesTharpa"
                        :arte-tharpa="arteTharpa"
                        :otros-tharpa="otrosTharpa"
                        :provincias="provincias"
                        :municipios="municipios"
                        :barrios="barrios"
                        :metodos-pago="metodosPago"
                        button-label="Agregar al carrito"
                        @submit="agregarClaseAlCarrito"
                    />
                    <p class="text-xs text-gray-500 mt-2">El pago se cobra con el ticket; la inscripción queda saldada al finalizar la venta.</p>
                </div>

                <!-- Inscripción a actividad -->
                <div v-else-if="ambito === 'inscripcion_actividad'">
                    <InscripcionActividadPosForm
                        :form="actividadForm"
                        :actividades="actividades"
                        :provincias="provincias"
                        :municipios="municipios"
                        :barrios="barrios"
                        button-label="Agregar al carrito"
                        @submit="agregarActividadAlCarrito"
                    />
                    <p class="text-xs text-gray-500 mt-2">El pago se cobra con el ticket; la inscripción queda saldada al finalizar la venta.</p>
                </div>
            </div>

            <template #footer>
                <template v-if="esProducto">
                    <button type="button" @click="dialogVisible = false" class="py-2 px-4 rounded border border-gray-300 dark:border-gray-600 mr-2">Cancelar</button>
                    <button type="button" @click="agregarAlCarrito" :disabled="!productoSeleccionado" class="text-white bg-indigo-500 hover:bg-indigo-700 disabled:opacity-50 py-2 px-4 rounded">Agregar</button>
                </template>
                <template v-else-if="esInscripcion">
                    <button type="button" @click="dialogVisible = false" class="py-2 px-4 rounded border border-gray-300 dark:border-gray-600">Cerrar</button>
                </template>
            </template>
        </Dialog>
    </AppLayout>
</template>
