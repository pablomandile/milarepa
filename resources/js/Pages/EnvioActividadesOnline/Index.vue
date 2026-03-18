<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({});

const enviarEmails = () => {
    form.post(route('envio-actividades-online.enviar'));
};

defineProps({
    mesActual: {
        type: String,
        required: true,
    },
    cantidadCandidatos: {
        type: Number,
        required: true,
    },
});
</script>

<template>
    <AppLayout title="Envío Actividades Online">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Envío Actividades Online</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 sm:rounded-lg space-y-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Mes de actividades a enviar</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ mesActual }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">Cantidad de emails a enviar</p>
                        <p class="mt-1 text-3xl font-bold text-indigo-600">{{ cantidadCandidatos }}</p>
                        <p class="mt-2 text-sm text-gray-500">
                            Incluye participantes con membresía online y sin envío registrado en el mes actual.
                        </p>
                    </div>

                    <div class="pt-2">
                        <button
                            type="button"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded disabled:opacity-60"
                            :disabled="form.processing || cantidadCandidatos === 0"
                            @click="enviarEmails"
                        >
                            {{ form.processing ? 'Enviando...' : 'Enviar email a todos los destinatarios' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
