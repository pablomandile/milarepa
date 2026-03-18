<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    configuraciones: {
        type: Array,
        required: true,
    },
    emails: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    configuraciones: props.configuraciones.map((item) => ({
        id: item.id,
        plantilla_archivo: item.plantilla_archivo,
    })),
});

const procesos = computed(() => {
    const byId = new Map(form.configuraciones.map((item) => [item.id, item]));

    return props.configuraciones.map((item) => ({
        ...item,
        selected: byId.get(item.id),
    }));
});

const guardar = () => {
    form.put(route('email-envio-configuraciones.update'));
};

const getLabelPlantilla = (archivo) => {
    const email = props.emails.find((item) => item.plantilla_archivo === archivo);
    return email ? `${email.nombre} (${email.plantilla_archivo})` : archivo;
};

const colorByProceso = {
    inscripcion_registrada: {
        border: 'border-indigo-200',
        fondo: 'bg-indigo-50',
        titulo: 'text-indigo-700',
        foco: 'focus:border-indigo-500 focus:ring-indigo-500',
    },
    inscripcion_confirmada: {
        border: 'border-green-200',
        fondo: 'bg-green-50',
        titulo: 'text-green-700',
        foco: 'focus:border-green-500 focus:ring-green-500',
    },
    envio_grabacion: {
        border: 'border-orange-200',
        fondo: 'bg-orange-50',
        titulo: 'text-orange-700',
        foco: 'focus:border-orange-500 focus:ring-orange-500',
    },
    informacion_membresias: {
        border: 'border-blue-200',
        fondo: 'bg-blue-50',
        titulo: 'text-blue-700',
        foco: 'focus:border-blue-500 focus:ring-blue-500',
    },
    inscripcion_tk_registrada: {
        border: 'border-pink-200',
        fondo: 'bg-pink-50',
        titulo: 'text-pink-700',
        foco: 'focus:border-pink-500 focus:ring-pink-500',
    },
    envio_actividades_online: {
        border: 'border-violet-200',
        fondo: 'bg-violet-50',
        titulo: 'text-violet-700',
        foco: 'focus:border-violet-500 focus:ring-violet-500',
    },
};

const getColorClasses = (procesoKey) => {
    return colorByProceso[procesoKey] || {
        border: 'border-gray-200',
        fondo: 'bg-gray-50',
        titulo: 'text-gray-700',
        foco: 'focus:border-indigo-500 focus:ring-indigo-500',
    };
};
</script>

<template>
    <AppLayout title="Configuración de Envíos">
        <template #header>
            <h1 class="font-extrabold text-2xl text-gray-900 leading-tight tracking-tight">Configuración de Envíos</h1>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 sm:rounded-lg">
                    <p class="text-lg text-gray-700 mb-6">
                        Configura qué plantilla se usa en cada uno de los procesos de envío de emails.
                    </p>

                    <div class="space-y-4">
                        <div
                            v-for="proceso in procesos"
                            :key="proceso.id"
                            class="grid grid-cols-1 md:grid-cols-2 gap-3 border rounded-md p-4"
                            :class="[getColorClasses(proceso.proceso_key).border, getColorClasses(proceso.proceso_key).fondo]"
                        >
                            <div>
                                <p class="text-sm font-bold" :class="getColorClasses(proceso.proceso_key).titulo">{{ proceso.proceso_nombre }}</p>
                                <p class="text-xs text-gray-500">Clave: {{ proceso.proceso_key }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Plantilla Email</label>
                                <select
                                    v-model="proceso.selected.plantilla_archivo"
                                    class="w-full border-gray-300 rounded-md shadow-sm"
                                    :class="getColorClasses(proceso.proceso_key).foco"
                                >
                                    <option
                                        v-for="email in emails"
                                        :key="`${proceso.id}-${email.id}`"
                                        :value="email.plantilla_archivo"
                                    >
                                        {{ getLabelPlantilla(email.plantilla_archivo) }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button
                            type="button"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded disabled:opacity-60"
                            :disabled="form.processing"
                            @click="guardar"
                        >
                            {{ form.processing ? 'Guardando...' : 'Guardar configuración' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
