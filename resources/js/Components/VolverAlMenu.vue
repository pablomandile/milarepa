<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// Botón "Volver al menú" de las páginas a las que se entra desde el panel del
// usuario (Asistant/Panel). Solo se muestra al rol asistant (o al admin en
// "Ver como usuario"): su barra superior no tiene menú, así que sin esto no
// hay una forma visible de volver. Mismo estilo que el de la Grilla.
const page = usePage();
const isAsistant = computed(() =>
    (page.props.user?.roles || []).map((role) => String(role).toLowerCase()).includes('asistant')
);
</script>

<template>
    <div v-if="isAsistant" class="mb-4">
        <Link
            :href="route('asistant.panel')"
            class="inline-flex items-center rounded-md border border-indigo-600 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors dark:border-indigo-400 dark:text-indigo-300 dark:hover:bg-indigo-500 dark:hover:text-white"
        >
            Volver al menú
        </Link>
    </div>
</template>
