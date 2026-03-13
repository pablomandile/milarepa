<template>
    <AppLayout title="Editar Estado de Cuenta">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar Estado de Cuenta
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <EstadoCuentaMembresiaForm
                        :estado-cuenta="estadoCuenta"
                        :form="form"
                        @submit="guardarCambios"
                        @open-comprobante="comprobanteModal = true"
                    />
                </div>
            </div>
        </div>
    </AppLayout>

    <Dialog
        v-model:visible="comprobanteModal"
        modal
        header="Subir comprobante"
        :style="{ width: '500px' }"
    >
        <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="seleccionarComprobante" />
        <template #footer>
            <div class="flex justify-end gap-2">
                <button class="px-4 py-2 bg-gray-500 text-white rounded" @click="comprobanteModal = false">
                    Cancelar
                </button>
                <button
                    class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-60"
                    :disabled="isUploading || !comprobanteFile"
                    @click="subirComprobante"
                >
                    {{ isUploading ? 'Subiendo...' : 'Subir' }}
                </button>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';
import EstadoCuentaMembresiaForm from '@/Components/Formularios/EstadoCuentaMembresiaForm.vue';

const props = defineProps({
    estadoCuenta: Object
});

const form = useForm({
    pagado: props.estadoCuenta.pagado,
    fecha_pago: props.estadoCuenta.fecha_pago ? String(props.estadoCuenta.fecha_pago).split('T')[0] : '',
    observaciones: props.estadoCuenta.observaciones || '',
    modo: props.estadoCuenta.modo || '',
    info_pago: props.estadoCuenta.info_pago || '',
});

const guardarCambios = () => {
    form.put(route('estado-cuenta-membresias.update', props.estadoCuenta.id), {
        onSuccess: () => {
            // El redirect se maneja automaticamente
        }
    });
};

watch(
    () => form.pagado,
    (nuevoValor) => {
        if (nuevoValor && !form.fecha_pago) {
            const today = new Date();
            form.fecha_pago = today.toISOString().split('T')[0];
        }
    }
);

const comprobanteModal = ref(false);
const comprobanteFile = ref(null);
const isUploading = ref(false);

function seleccionarComprobante(event) {
    comprobanteFile.value = event.target.files?.[0] || null;
}

async function subirComprobante() {
    if (!comprobanteFile.value) return;
    isUploading.value = true;
    try {
        const data = new FormData();
        data.append('comprobante', comprobanteFile.value);
        data.append('estado_cuenta_id', props.estadoCuenta.id);
        await axios.post(route('estado-cuenta-membresias.comprobante'), data);
        comprobanteModal.value = false;
        comprobanteFile.value = null;
    } catch (error) {
        const mensaje = error?.response?.data?.message || error?.response?.data?.errors?.comprobante?.[0] || 'No se pudo subir el comprobante.';
        alert(mensaje);
    } finally {
        isUploading.value = false;
    }
}
</script>
