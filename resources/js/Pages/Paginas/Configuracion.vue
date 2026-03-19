<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import InputSwitch from 'primevue/inputswitch';

const props = defineProps({
    mostrar_logo_entidad_principal_nav: {
        type: Boolean,
        default: false,
    },
    mostrar_logo_entidad_principal_footer: {
        type: Boolean,
        default: false,
    },
    enviar_mail_semanal_inscripciones_actividad: {
        type: Boolean,
        default: false,
    },
    envio_mail_semanal_inscripciones_dia: {
        type: String,
        default: 'viernes',
    },
    envio_mail_semanal_inscripciones_hora: {
        type: String,
        default: '17:00',
    },
    envio_mail_semanal_inscripciones_destinatario: {
        type: String,
        default: '',
    },
});

const form = useForm({
    mostrar_logo_entidad_principal_nav: props.mostrar_logo_entidad_principal_nav,
    mostrar_logo_entidad_principal_footer: props.mostrar_logo_entidad_principal_footer,
    enviar_mail_semanal_inscripciones_actividad: props.enviar_mail_semanal_inscripciones_actividad,
    envio_mail_semanal_inscripciones_dia: props.envio_mail_semanal_inscripciones_dia,
    envio_mail_semanal_inscripciones_hora: props.envio_mail_semanal_inscripciones_hora,
    envio_mail_semanal_inscripciones_destinatario: props.envio_mail_semanal_inscripciones_destinatario,
});

const guardarConfiguracion = () => {
    form.put(route('paginas.configuracion.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Configuración</h1>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6">Páginas</h2>

                    <form @submit.prevent="guardarConfiguracion" class="space-y-6">
                        <div class="flex items-center justify-between gap-4 border border-gray-200 rounded-lg p-4">
                            <span class="text-sm font-medium text-gray-800">Mostrar logo de Entidad Principal en la barra superior</span>
                            <InputSwitch v-model="form.mostrar_logo_entidad_principal_nav" />
                        </div>

                        <div class="flex items-center justify-between gap-4 border border-gray-200 rounded-lg p-4">
                            <span class="text-sm font-medium text-gray-800">Mostrar logo de Entidad Principal en el pie de página</span>
                            <InputSwitch v-model="form.mostrar_logo_entidad_principal_footer" />
                        </div>

                        <div class="flex items-center justify-between gap-4 border border-gray-200 rounded-lg p-4">
                            <span class="text-sm font-medium text-gray-800">Enviar mail semanal de inscripciones por actividad los viernes</span>
                            <InputSwitch v-model="form.enviar_mail_semanal_inscripciones_actividad" />
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4 space-y-4" v-if="form.enviar_mail_semanal_inscripciones_actividad">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Día de la semana</label>
                                    <select
                                        v-model="form.envio_mail_semanal_inscripciones_dia"
                                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="lunes">Lunes</option>
                                        <option value="martes">Martes</option>
                                        <option value="miercoles">Miércoles</option>
                                        <option value="jueves">Jueves</option>
                                        <option value="viernes">Viernes</option>
                                        <option value="sabado">Sábado</option>
                                        <option value="domingo">Domingo</option>
                                    </select>
                                    <p v-if="form.errors.envio_mail_semanal_inscripciones_dia" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.envio_mail_semanal_inscripciones_dia }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                                    <input
                                        v-model="form.envio_mail_semanal_inscripciones_hora"
                                        type="time"
                                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.envio_mail_semanal_inscripciones_hora" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.envio_mail_semanal_inscripciones_hora }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email destinatario</label>
                                <input
                                    v-model="form.envio_mail_semanal_inscripciones_destinatario"
                                    type="email"
                                    placeholder="reporte@dominio.com"
                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">Si se completa, este email será el destinatario del reporte semanal.</p>
                                <p v-if="form.errors.envio_mail_semanal_inscripciones_destinatario" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.envio_mail_semanal_inscripciones_destinatario }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
