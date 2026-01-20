<script>
    export default {
        name: 'UsersCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import UserForm from '@/Components/Formularios/UserForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        roles: {
            type: Array,
            default: () => []
        }
    });

    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        telefono: '',
        whatsapp: '',
        es_maestro: false,
        es_coordinador: false,
        roles: ''
    })

    const handleSubmit = () => {
        form.post(route('usuarios.store'), {
            onSuccess: () => {
                console.log('Usuario creado exitosamente');
            },
            onError: errors => {
                console.log('Errores al crear:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Agregar Nuevo Usuario</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('usuarios.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <UserForm 
                            :updating="false"
                            :form="form" 
                            :roles="roles"
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>