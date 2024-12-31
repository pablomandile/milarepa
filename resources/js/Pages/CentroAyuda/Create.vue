<script>
    export default {
        name: 'TicketsCreate'
    }
</script>

<script setup>
    import { useForm, usePage  } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import TicketForm from '@/Components/Formularios/TicketForm.vue';
    import { Link } from '@inertiajs/vue3';

    const page = usePage();

    const now = new Date().toISOString().slice(0, 10);

    const form = useForm({
        asunto: '',
        descripcion: '',
        fecha_apertura: now,
        user_id: page.props.auth.user.id,
        estadoticket_id: 1,
        responsable_id: '',
    })

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar Nuevo Ticket</h1>
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
                            :updating="false"
                            :form="form" @submit="form.post(route('centroayuda.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>