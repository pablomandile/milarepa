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
    <div class="relative flex flex-col justify-center items-center min-h-screen bg-dots-darker bg-center bg-indigo-100 dark:bg-dots-lighter dark:bg-indigo-900 selection:bg-red-500 selection:text-white">
        
        <!-- Sección de texto -->
        <div class="mb-2">
            <h1 class="text-white text-center text-[60px] font-extrabold">Sistema de inscripciones</h1>
            <h1 class="text-white text-center text-[60px] font-extrabold">Milarepa 1.0</h1>

        </div>
        
        <!-- Sección de botones -->
        <div v-if="canLogin" class="flex justify-center p-8 z-10">
            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Dashboard</Link>

            <template v-else>
                <Link :href="route('login')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">Log in</Link>

                <Link v-if="canRegister" :href="route('register')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded ml-10">Register</Link>
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
</style>
