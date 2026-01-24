<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import DataView from 'primevue/dataview';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const page = usePage();

const props = defineProps({
    membresias: {
        type: Object,
        required: true
    },
    user_membresia: {
        type: Object,
        default: null
    }
});

const layout = ref('grid');
const showConfirmDialog = ref(false);
const membresiaPendiente = ref(null);

const userMembresia = props.user_membresia;

const inscrbirme = (membresia) => {
    const userActive = userMembresia;

    if (!userActive) {
        // Si no tiene membresía, inscribirse directamente
        router.post(route('membresias.subscribe'), {
            membresia_id: membresia.id
        }, {
            onSuccess: () => {
                Swal.fire('¡Éxito!', 'Te has inscrito a la membresía correctamente', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'Hubo un problema al inscribirse', 'error');
            }
        });
    } else if (userActive.id === membresia.id) {
        // Si es la misma membresía
        Swal.fire('Información', 'Ya tienes esta membresía activa', 'info');
    } else {
        // Si tiene otra membresía, mostrar modal de confirmación
        membresiaPendiente.value = membresia;
        showConfirmDialog.value = true;
    }
};

const confirmarCambio = () => {
    router.post(route('membresias.subscribe'), {
        membresia_id: membresiaPendiente.value.id
    }, {
        onSuccess: () => {
            showConfirmDialog.value = false;
            Swal.fire('¡Éxito!', 'Tu membresía ha sido actualizada', 'success');
        },
        onError: () => {
            Swal.fire('Error', 'Hubo un problema al cambiar la membresía', 'error');
        }
    });
};

const isDisabledButton = (membresia) => {
    return userMembresia && userMembresia.id === membresia.id;
};
</script>

<template>
    <AppLayout>
        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="bg-white border-round shadow-1">
                    <div class="p-6 border-bottom-1 border-200">
                        <div class="flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="text-2xl font-bold text-900 m-0">Membresías Disponibles</h2>
                                <p v-if="userMembresia" class="text-base text-600 mt-2">
                                    <span class="font-semibold">Tu membresía actual:</span> <span class="font-bold text-green-600">{{ userMembresia.nombre }}</span>
                                </p>
                                <div v-if="userMembresia" class="flex gap-2 mt-3">
                                    <button
                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition"
                                    >
                                        <i class="pi pi-credit-card mr-2"></i>
                                        Pagar
                                    </button>
                                    <button
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition"
                                    >
                                        <i class="pi pi-upload mr-2"></i>
                                        Informar Pago
                                    </button>
                                </div>
                                <p v-else class="text-base text-600 mt-2">
                                    No tienes una membresía activa. ¡Elige una y únete ahora!
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="layout = 'grid'"
                                    :class="['p-button p-button-text p-button-rounded', layout === 'grid' ? 'p-button-primary' : 'p-button-secondary']"
                                >
                                    <i class="pi pi-th-large"></i>
                                </button>
                                <button
                                    @click="layout = 'list'"
                                    :class="['p-button p-button-text p-button-rounded', layout === 'list' ? 'p-button-primary' : 'p-button-secondary']"
                                >
                                    <i class="pi pi-list"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <DataView
                                :value="membresias.data"
                                :layout="layout"
                                paginator
                                :rows="9"
                                :rowsPerPageOptions="[3, 6, 9]"
                                class="mb-6"
                            >
                                <template #grid="slotProps">
                                    <div class="grid grid-nogutter">
                                        <div
                                            v-for="(membresia, index) in slotProps.items"
                                            :key="membresia.id"
                                            class="col-12 md:col-6 xl:col-4 p-2"
                                        >
                                            <div class="p-card bg-white border-1 surface-border border-round shadow-2 hover:shadow-4 transition-all transition-duration-300">
                                                <div class="p-card-body p-4">
                                                    <div class="flex flex-col h-full">
                                                        <div class="flex align-items-center mb-3">
                                                            <div class="bg-primary-100 border-circle w-3rem h-3rem flex align-items-center justify-content-center mr-3">
                                                                <i class="pi pi-heart text-primary text-xl"></i>
                                                            </div>
                                                            <div class="flex-1">
                                                                <h3 class="p-card-title text-lg font-bold text-900 m-0">
                                                                    {{ membresia.nombre }}
                                                                </h3>
                                                            </div>
                                                        </div>
                                                        <p class="p-card-subtitle text-sm text-600 mb-3 flex-1">
                                                            {{ membresia.descripcion || 'Sin descripción disponible' }}
                                                        </p>
                                                        <div class="flex align-items-center mb-4">
                                                            <i class="pi pi-building text-400 mr-2"></i>
                                                            <span class="text-sm text-500">
                                                                <strong>{{ membresia.entidad?.nombre || 'No especificada' }}</strong>
                                                            </span>
                                                        </div>
                                                        <div class="mt-auto">
                                                            <button
                                                                @click="inscrbirme(membresia)"
                                                                :disabled="isDisabledButton(membresia)"
                                                                :class="[
                                                                    'w-full py-2 px-4 rounded-md font-medium transition-colors flex items-center justify-center gap-2',
                                                                    isDisabledButton(membresia) 
                                                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                                                                        : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                                ]"
                                                            >
                                                                <i class="pi pi-plus-circle"></i>
                                                                {{ isDisabledButton(membresia) ? 'Mi membresía actual' : 'Inscribirme' }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #list="slotProps">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div
                                            v-for="(membresia, index) in slotProps.items"
                                            :key="membresia.id"
                                            class="col-12 p-card bg-white border-1 surface-border border-round shadow-2 hover:shadow-4 transition-all transition-duration-300"
                                        >
                                            <div class="p-card-body p-4">
                                                <div class="flex flex-col md:flex-row md:align-items-center md:justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex align-items-center mb-3">
                                                            <div class="bg-primary-100 border-circle w-3rem h-3rem flex align-items-center justify-content-center mr-3">
                                                                <i class="pi pi-heart text-primary text-xl"></i>
                                                            </div>
                                                            <div>
                                                                <h3 class="p-card-title text-lg font-bold text-900 m-0">
                                                                    {{ membresia.nombre }}
                                                                </h3>
                                                                <div class="flex align-items-center mt-1">
                                                                    <i class="pi pi-building text-400 mr-2"></i>
                                                                    <span class="text-sm text-500">
                                                                        {{ membresia.entidad?.nombre || 'No especificada' }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <p class="p-card-subtitle text-sm text-600">
                                                            {{ membresia.descripcion || 'Sin descripción disponible' }}
                                                        </p>
                                                    </div>
                                                    <div class="mt-4 md:mt-0 md:ml-4">
                                                        <button
                                                            @click="inscrbirme(membresia)"
                                                            :disabled="isDisabledButton(membresia)"
                                                            :class="[
                                                                'py-2 px-4 rounded-md font-medium transition-colors flex items-center justify-center gap-2',
                                                                isDisabledButton(membresia) 
                                                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                                                                    : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                            ]"
                                                        >
                                                            <i class="pi pi-plus-circle"></i>
                                                            {{ isDisabledButton(membresia) ? 'Mi membresía actual' : 'Inscribirme' }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template #empty>
                                    <div class="text-center py-8 px-4">
                                        <div class="bg-gray-50 border-round p-6 border-1 border-dashed border-300">
                                            <i class="pi pi-info-circle text-4xl text-400 mb-4 block"></i>
                                            <h3 class="text-xl font-medium text-700 mb-2">No hay membresías disponibles</h3>
                                            <p class="text-600 m-0">En este momento no tenemos membresías activas.</p>
                                        </div>
                                    </div>
                                </template>
                            </DataView>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modal de confirmación para cambio de membresía -->
    <Dialog 
        v-model:visible="showConfirmDialog" 
        modal 
        header="Cambiar membresía"
        :style="{ width: '30rem' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <template v-if="membresiaPendiente">
            <div class="flex flex-col items-center justify-center mb-4">
                <i class="pi pi-exclamation-triangle text-yellow-500 mb-3" style="font-size: 3rem"></i>
                <p class="text-center mb-3">
                    Actualmente tienes la membresía <strong>{{ userMembresia?.nombre }}</strong>.
                </p>
                <p class="text-center mb-4">
                    ¿Deseas cambiar a la membresía <strong>{{ membresiaPendiente.nombre }}</strong>?
                </p>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-end space-x-2">
                <button 
                    @click="showConfirmDialog = false"
                    class="py-2 px-4 rounded-md font-medium transition-colors bg-gray-500 text-white hover:bg-gray-600"
                >
                    Cancelar
                </button>
                <button 
                    @click="confirmarCambio"
                    class="py-2 px-4 rounded-md font-medium transition-colors bg-green-600 text-white hover:bg-green-700"
                >
                    Sí, cambiar
                </button>
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
.p-card {
    transition: all 0.3s ease;
}

.p-card:hover {
    transform: translateY(-2px);
}

.p-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.p-card-title {
    color: #374151;
    font-weight: 700;
}

.p-card-subtitle {
    color: #6b7280;
    margin-bottom: 1rem;
}

.p-button {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.p-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>