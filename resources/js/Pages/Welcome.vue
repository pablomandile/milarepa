<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Welcome" />

    <!-- Asegurarnos de que los elementos estén en un layout vertical -->
    <div class="relative flex flex-col justify-center items-center min-h-screen welcome-bg selection:bg-red-500 selection:text-white">
        
        <!-- Sección de texto -->
        <div class="mb-2">
            <h1 class="text-white text-center text-[60px] font-extrabold">Sistema de inscripciones</h1>
            <h1 class="text-white text-center text-[60px] font-extrabold">Milarepa</h1>

        </div>
        
        <!-- Sección de botones -->
        <div v-if="canLogin" class="flex justify-center p-8 z-10">
            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Dashboard</Link>

            <template v-else>
                <Link :href="route('login')" class="inline-flex items-center justify-center px-5 py-2 rounded-full bg-white text-indigo-700 font-semibold shadow-sm hover:bg-indigo-50 hover:text-indigo-800 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-indigo-500 transition">
                    Iniciar Sesión
                </Link>

                <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center justify-center px-5 py-2 rounded-full bg-white text-indigo-700 font-semibold shadow-sm hover:bg-indigo-50 hover:text-indigo-800 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-indigo-500 transition ml-10">
                    Registrarse
                </Link>
            </template>
        </div>
    </div>
</template>


<style>
.bg-dots-darker {
    background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(0,0,0,0.07)'/%3E%3C/svg%3E");
}
@media (prefers-color-scheme: dark) {
    .dark\:bg-dots-lighter {
        background-image: url("data:image/svg+xml,%3Csvg width='30' height='30' viewBox='0 0 30 30' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    }
}

.welcome-bg {
    background-image:
        radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 0),
        linear-gradient(135deg, #4338ca, #7c3aed 45%, #312e81);
    background-size: 30px 30px, 100% 100%;
    background-position: center;
    background-repeat: repeat, no-repeat;
}
</style>
