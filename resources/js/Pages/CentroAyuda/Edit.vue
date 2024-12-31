<script>
    export default {
        name: 'TicketsEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import TicketForm from '@/Components/Formularios/TicketForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        ticket:{
            type: Object,
            required: true
        },

        usuariosResponsables: {
        type: Array,
        default: () => []
        },

        estados: {
        type: Array,
        default: () => []
        }
    });

    // console.log(props.ticket);
    if (!props.ticket) {
        console.error('El ticket no está definido');
    }
    const form = useForm({
        asunto: props.ticket.asunto,
        descripcion: props.ticket.descripcion,
        fecha_apertura: props.ticket.fecha_apertura,
        user_id: props.ticket.user_id,
        responsable_id: props.ticket.responsable_id,
        estado_ticket_id: props.ticket.estado_ticket_id,
    });

    const handleSubmit = () => {
        form.put(route('centroayuda.update', props.ticket.id), {
            onSuccess: () => {
                console.log('Ticket actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Ticket">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar ticket</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('centroayuda.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <TicketForm 
                            :updating="true" 
                            :form="form" 
                            :usuariosResponsables="usuariosResponsables"
                            :estados="estados"
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>