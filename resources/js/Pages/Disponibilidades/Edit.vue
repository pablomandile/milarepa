<script>
    export default {
        name: 'DisponibilidadesEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import DisponibilidadForm from '@/Components/Disponibilidades/Form.vue'

    const props = defineProps({
        disponibilidad:{
            type: Object,
            required: true
        }
    });
    // console.log(props.disponibilidad);
    if (!props.disponibilidad) {
        console.error('La disponibilidad no está definida');
    }

    const form = useForm({
        descripcion: props.disponibilidad.descripcion
    });

    const handleSubmit = () => {
        form.put(route('disponibilidades.update', props.disponibilidad.id), {
            onSuccess: () => {
                console.log('Disponibilidad actualizada exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Disponibilidad">
        <template #header>
            <h1 class="font-semibold text-lx text-gray-800 leading-tight" >Editar Disponibilidad</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <DisponibilidadForm 
                            :updating="true" 
                            :form="form" 
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>