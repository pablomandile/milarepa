<script>
    export default {
        name: 'UsersEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import UserForm from '@/Components/Formularios/UserForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        usuario:{
            type: Object,
            required: true
        },
        roles: {
            type: Array,
            default: () => []
        }
    });

    if (!props.usuario) {
        console.error('El usuario no está definido');
    }

    const form = useForm({
        name: props.usuario.name,
        email: props.usuario.email,
        password: '',
        password_confirmation: '',
        telefono: props.usuario.telefono || '',
        whatsapp: props.usuario.whatsapp || '',
        es_maestro: props.usuario.es_maestro === 1 || props.usuario.es_maestro === true,
        es_coordinador: props.usuario.es_coordinador === 1 || props.usuario.es_coordinador === true,
        roles: props.usuario.roles && props.usuario.roles.length > 0 ? props.usuario.roles[0].name : ''
    });

    const handleSubmit = () => {
        form.put(route('usuarios.update', props.usuario.id), {
            onSuccess: () => {
                console.log('Usuario actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Usuario">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar usuario</h1>
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
                            :updating="true" 
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