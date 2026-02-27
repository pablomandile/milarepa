<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Default Title'
  },
});

const page = usePage();
const showingNavigationDropdown = ref(false);
const isAsistant = computed(() => {
    const roles = (page.props.user?.roles || []).map((role) => String(role).toLowerCase());
    return roles.includes('asistant');
});

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="flex flex-col min-h-screen bg-gray-100">
        <Head>
            <title>{{ title }}</title>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
            <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Parisienne&display=swap" rel="stylesheet" />
        </Head>

        <Banner />

        <nav v-if="$page.props.auth?.user" class="bg-white border-b border-gray-100">
            <!-- Primary Navigation Menu -->
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationMark class="block h-9 w-auto" />
                            </Link>
                        </div>

                        <template v-if="!isAsistant">
                            <!-- Navigation Links -->
                            <!-- Gestión Dropdown -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Inicio
                                </NavLink>
                            </div>
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                                <Dropdown>
                                    <template #trigger>
                                        <button @click.prevent class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-indigo-700 transition duration-150 ease-in-out">
                                            Gestión
                                            <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        </template>

                                    <template #content>
                                        <DropdownLink :href="route('maestros.index')" :active="route().current('maestros.*')">
                                            Maestros
                                        </DropdownLink>
                                        <DropdownLink :href="route('coordinadores.index')" :active="route().current('coordinadores.*')">
                                            Coordinadores
                                        </DropdownLink>
                                        <DropdownLink :href="route('entidades.index')" :active="route().current('entidades.*')">
                                            Entidades
                                        </DropdownLink>
                                        <DropdownLink :href="route('membresias.gestion')" :active="route().current('membresias.gestion')">
                                            Membresías
                                        </DropdownLink>
                                        <DropdownLink :href="route('comidas.index')" :active="route().current('comidas.*')">
                                            Comidas
                                        </DropdownLink>
                                        <DropdownLink :href="route('lugareshospedaje.index')" :active="route().current('lugareshospedaje.*')">
                                            Lugares de Hospedaje
                                        </DropdownLink>
                                        <DropdownLink :href="route('hospedajes.index')" :active="route().current('hospedajes.*')">
                                            Acomodaciones
                                        </DropdownLink>
                                        <DropdownLink :href="route('transportes.index')" :active="route().current('transportes.*')">
                                            Transportes
                                        </DropdownLink>
                                        <DropdownLink :href="route('imagenes.index')" :active="route().current('imagenes.*')">
                                            Imagenes
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                            <Dropdown>
                                <template #trigger>
                                    <button @click.prevent class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-indigo-700 transition duration-150 ease-in-out">
                                        Actividades
                                        <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    </template>

                                <template #content>
                                    <DropdownLink :href="route('actividades.index')" :active="route().current('actividades.*')">
                                        Todas los Cursos y Retiros 
                                    </DropdownLink>
                                    <DropdownLink :href="route('grid-actividades.index')" :active="route().current('gridactividades.*')">
                                        Grilla de Cursos y Retiros Activos
                                    </DropdownLink>
                                    <DropdownLink :href="route('calendario.index')" :active="route().current('calendario.index')">
                                        Calendario
                                    </DropdownLink>
                                    <DropdownLink :href="route('tiposactividad.index')" :active="route().current('tipoactividad.*')">
                                        Tipos de Actividad
                                    </DropdownLink>
                                    <DropdownLink :href="route('descripciones.index')" :active="route().current('descripciones.*')">
                                        Descripciones
                                    </DropdownLink>
                                    <DropdownLink :href="route('programas.index')" :active="route().current('programas.*')">
                                        Programas
                                    </DropdownLink>
                                    <DropdownLink :href="route('oracionescantadas.index')" :active="route().current('oracionescantadas.*')">
                                        Oraciones Cantadas
                                    </DropdownLink>
                                    <DropdownLink :href="route('disponibilidades.index')" :active="route().current('disponibilidades.*')">
                                        Disponibilidades
                                    </DropdownLink>
                                    <DropdownLink :href="route('modalidades.index')" :active="route().current('modalidades.*')">
                                        Modalidades
                                    </DropdownLink>
                                    <DropdownLink :href="route('streams.index')" :active="route().current('streams.*')">
                                        Streams
                                    </DropdownLink>
                                    <DropdownLink :href="route('grabaciones.index')" :active="route().current('grabaciones.*')">
                                        Grabaciones
                                    </DropdownLink>
                                    <DropdownLink href="/email-preview" :active="route().current('email.preview.landing')">
                                        Plantillas de Emails
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                            <Dropdown>
                                <template #trigger>
                                    <button @click.prevent class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-indigo-700 transition duration-150 ease-in-out">
                                        Inscripciones
                                        <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    </template>

                                <template #content>
                                    <DropdownLink :href="route('inscripciones.index')" :active="route().current('inscripciones.*')">
                                        Mis Inscripciones
                                    </DropdownLink>
                                    <DropdownLink :href="route('inscripciones.por-actividad')" :active="route().current('inscripciones.por-actividad')">
                                        Inscripciones por Actividad
                                    </DropdownLink>
                                    <DropdownLink :href="route('estadoinscripciones.index')" :active="route().current('estado-inscripciones.*')">
                                        Estado de inscripciones
                                    </DropdownLink>
                                    <DropdownLink :href="route('estadoinscripciones.index')" :active="route().current('estado-inscripciones.*')">
                                        Histórico
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                            <Dropdown>
                                <template #trigger>
                                    <button @click.prevent class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-indigo-700 transition duration-150 ease-in-out">
                                        Membresías
                                        <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    </template>

                                <template #content>
                                    <DropdownLink :href="route('membresias.index')" :active="route().current('membresias.*')">
                                        Membresías (Tarjetas Kadampa)
                                    </DropdownLink>
                                    <DropdownLink :href="route('estado-cuenta-membresias.index')" :active="route().current('estado-cuenta-membresias.*')">
                                        Estado de Cuenta Membresías
                                    </DropdownLink>
                                    <DropdownLink :href="route('membresias.gestion-usuarios')" :active="route().current('membresias.gestion-usuarios')">
                                        Adminnistrar Membresías
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                            <Dropdown>
                                <template #trigger>
                                    <button @click.prevent class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-indigo-700 transition duration-150 ease-in-out">
                                        Pagos
                                        <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    </template>

                                <template #content>
                                    <DropdownLink :href="route('monedas.index')" :active="route().current('monedas.*')">
                                        Monedas
                                    </DropdownLink>
                                    <DropdownLink :href="route('metodospago.index')" :active="route().current('metodos-pago.*')">
                                        Métodos de Pago
                                    </DropdownLink>
                                    <DropdownLink :href="route('esquemaprecios.index')" :active="route().current('esquema-precios.*')">
                                        Esquema de Precios
                                    </DropdownLink>
                                    <DropdownLink :href="route('esquemadescuentos.index')" :active="route().current('esquema-descuentos.*')">
                                        Esquema de Descuentos
                                    </DropdownLink>
                                    <DropdownLink :href="route('botonespago.index')" :active="route().current('botonespago.*')">
                                        Botones de Pago
                                    </DropdownLink>
                                    <DropdownLink :href="route('excencionpago.index')" :active="route().current('excencionpago.*')">
                                        Exención de pagos
                                    </DropdownLink>

                                </template>
                            </Dropdown>
                        </div>
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                            <Dropdown>
                                <template #trigger>
                                    <button @click.prevent class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-indigo-700 transition duration-150 ease-in-out">
                                        Usuarios
                                        <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    </template>

                                <template #content>
                                    <DropdownLink :href="route('usuarios.index')" :active="route().current('usuarios.*')">
                                        Usuarios
                                    </DropdownLink>
                                    <DropdownLink :href="route('roles.index')" :active="route().current('roles.*')">
                                        Roles
                                    </DropdownLink>
                                    <DropdownLink :href="route('perfiles.index')" :active="route().current('perfiles.*')">
                                        Permisos
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                            <Dropdown>
                                <template #trigger>
                                    <button @click.prevent class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 focus:border-indigo-700 transition duration-150 ease-in-out">
                                        Ayuda
                                        <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    </template>

                                <template #content>
                                    <DropdownLink :href="route('centroayuda.index')" :active="route().current('centroayuda.*')">
                                        Centro de ayuda
                                    </DropdownLink>
                                    <DropdownLink :href="route('novedades.index')" :active="route().current('novedades.*')">
                                        Novedades
                                    </DropdownLink>
                                    <DropdownLink :href="route('versiones.index')" :active="route().current('versiones.*')">
                                        Versiones
                                    </DropdownLink>
                                    <DropdownLink :href="route('reporteerror.index')" :active="route().current('reporteerror.*')">
                                        Reportar un error
                                    </DropdownLink>
                                    <div class="border-t border-gray-200" />
                                    <DropdownLink :href="route('acercade.index')" :active="route().current('acercade.*')">
                                        <i class="fas fa-info-circle mr-1" style="color: slateblue"></i>
                                        Acerca de
                                    </DropdownLink>
                                    
                                </template>
                            </Dropdown>

                        </div>
                        </template>
                    </div>

                    <div v-if="$page.props.auth?.user" class="hidden sm:flex sm:items-center sm:ms-6">
                        <div class="ms-3 relative">
                            <!-- Teams Dropdown -->
                            <Dropdown v-if="!isAsistant && $page.props.jetstream.hasTeamFeatures" align="right" width="60">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                            {{ $page.props.auth.user.current_team.name }}

                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <div class="w-60">
                                        <!-- Team Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Manage Team
                                        </div>

                                        <!-- Team Settings -->
                                        <DropdownLink :href="route('teams.show', $page.props.auth.user.current_team)">
                                            Team Settings
                                        </DropdownLink>

                                        <DropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">
                                            Create New Team
                                        </DropdownLink>

                                        <!-- Team Switcher -->
                                        <template v-if="$page.props.auth.user.all_teams.length > 1">
                                            <div class="border-t border-gray-200" />

                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Switch Teams
                                            </div>

                                            <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                                <form @submit.prevent="switchToTeam(team)">
                                                    <DropdownLink as="button">
                                                        <div class="flex items-center">
                                                            <svg v-if="team.id == $page.props.auth.user.current_team_id" class="me-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>

                                                            <div>{{ team.name }}</div>
                                                        </div>
                                                    </DropdownLink>
                                                </form>
                                            </template>
                                        </template>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="ms-3 relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                        <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                    </button>

                                    <span v-else class="inline-flex rounded-md">
                                        <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-900 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                            ¡Bienvenido {{ $page.props.auth.user.name }}!

                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <!-- Account Management -->
                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Administrar cuenta
                                    </div>
                                    <div class="block px-4 py-2 text-xs text-gray-600">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                    <div class="mb-2 border-t border-gray-200" />
                                    <DropdownLink :href="route('profile.show')">
                                        Mi Perfil
                                    </DropdownLink>
                                    <DropdownLink :href="route('membresias.index')" :active="route().current('monedas.*')">
                                        Mi Membresía
                                    </DropdownLink>
                                    <DropdownLink :href="route('monedas.index')" :active="route().current('monedas.*')">
                                        Mi camino Budista
                                    </DropdownLink>

                                    <DropdownLink v-if="!isAsistant && $page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                        API Tokens
                                    </DropdownLink>

                                    <div class="mt-2 border-t border-gray-200" />

                                    <!-- Authentication -->
                                    <form @submit.prevent="logout">
                                        <DropdownLink as="button">
                                            Salir
                                        </DropdownLink>
                                    </form>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Hamburger -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                <div v-if="!isAsistant" class="pt-2 pb-3 space-y-4">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        Inicio
                    </ResponsiveNavLink>

                    <div class="space-y-1">
                        <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Gestión</div>
                        <ResponsiveNavLink :href="route('maestros.index')" :active="route().current('maestros.*')">
                            Maestros
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('coordinadores.index')" :active="route().current('coordinadores.*')">
                            Coordinadores
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('entidades.index')" :active="route().current('entidades.*')">
                            Entidades
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('membresias.gestion')" :active="route().current('membresias.gestion')">
                            Membresías
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('comidas.index')" :active="route().current('comidas.*')">
                            Comidas
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('lugareshospedaje.index')" :active="route().current('lugareshospedaje.*')">
                            Lugares de Hospedaje
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('hospedajes.index')" :active="route().current('hospedajes.*')">
                            Acomodaciones
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('transportes.index')" :active="route().current('transportes.*')">
                            Transportes
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('imagenes.index')" :active="route().current('imagenes.*')">
                            Imagenes
                        </ResponsiveNavLink>
                    </div>

                    <div class="space-y-1">
                        <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Actividades</div>
                        <ResponsiveNavLink :href="route('actividades.index')" :active="route().current('actividades.*')">
                            Todas las Actividades
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('grid-actividades.index')" :active="route().current('gridactividades.*')">
                            Actividades del mes
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('calendario.index')" :active="route().current('calendario.index')">
                            Calendario
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('tiposactividad.index')" :active="route().current('tipoactividad.*')">
                            Tipos de Actividad
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('descripciones.index')" :active="route().current('descripciones.*')">
                            Descripciones
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('programas.index')" :active="route().current('programas.*')">
                            Programas
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('oracionescantadas.index')" :active="route().current('oracionescantadas.*')">
                            Oraciones Cantadas
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('disponibilidades.index')" :active="route().current('disponibilidades.*')">
                            Disponibilidades
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('modalidades.index')" :active="route().current('modalidades.*')">
                            Modalidades
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('streams.index')" :active="route().current('streams.*')">
                            Streams
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('grabaciones.index')" :active="route().current('grabaciones.*')">
                            Grabaciones
                        </ResponsiveNavLink>
                        <ResponsiveNavLink href="/email-preview" :active="route().current('email.preview.landing')">
                            Plantillas de Emails
                        </ResponsiveNavLink>
                    </div>

                    <div class="space-y-1">
                        <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Inscripciones</div>
                        <ResponsiveNavLink :href="route('inscripciones.index')" :active="route().current('inscripciones.*')">
                            Mis Inscripciones
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('inscripciones.por-actividad')" :active="route().current('inscripciones.por-actividad')">
                            Inscripciones por Actividad
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('estadoinscripciones.index')" :active="route().current('estado-inscripciones.*')">
                            Estado de inscripciones
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('estadoinscripciones.index')" :active="route().current('estado-inscripciones.*')">
                            Histórico
                        </ResponsiveNavLink>
                    </div>

                    <div class="space-y-1">
                        <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Membresías</div>
                        <ResponsiveNavLink :href="route('membresias.index')" :active="route().current('membresias.*')">
                            Membresías (Tarjetas Kadampa)
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('estado-cuenta-membresias.index')" :active="route().current('estado-cuenta-membresias.*')">
                            Estado de Cuenta Membresías
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('membresias.gestion-usuarios')" :active="route().current('membresias.gestion-usuarios')">
                            Adminnistrar Membresías
                        </ResponsiveNavLink>
                    </div>

                    <div class="space-y-1">
                        <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Pagos</div>
                        <ResponsiveNavLink :href="route('monedas.index')" :active="route().current('monedas.*')">
                            Monedas
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('metodospago.index')" :active="route().current('metodos-pago.*')">
                            Métodos de Pago
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('esquemaprecios.index')" :active="route().current('esquema-precios.*')">
                            Esquema de Precios
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('esquemadescuentos.index')" :active="route().current('esquema-descuentos.*')">
                            Esquema de Descuentos
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('botonespago.index')" :active="route().current('botonespago.*')">
                            Botones de Pago
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('excencionpago.index')" :active="route().current('excencionpago.*')">
                            Exención de pagos
                        </ResponsiveNavLink>
                    </div>

                    <div class="space-y-1">
                        <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Usuarios</div>
                        <ResponsiveNavLink :href="route('usuarios.index')" :active="route().current('usuarios.*')">
                            Usuarios
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('roles.index')" :active="route().current('roles.*')">
                            Roles
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('perfiles.index')" :active="route().current('perfiles.*')">
                            Permisos
                        </ResponsiveNavLink>
                    </div>

                    <div class="space-y-1">
                        <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Ayuda</div>
                        <ResponsiveNavLink :href="route('centroayuda.index')" :active="route().current('centroayuda.*')">
                            Centro de ayuda
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('novedades.index')" :active="route().current('novedades.*')">
                            Novedades
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('versiones.index')" :active="route().current('versiones.*')">
                            Versiones
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('reporteerror.index')" :active="route().current('reporteerror.*')">
                            Reportar un error
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('acercade.index')" :active="route().current('acercade.*')">
                            Acerca de
                        </ResponsiveNavLink>
                    </div>
                </div>

                <!-- Responsive Settings Options -->
                <div v-if="$page.props.auth?.user" class="pt-4 pb-1 border-t border-gray-200">
                    <div class="flex items-center px-4">
                        <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                            <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                        </div>

                        <div>
                            <div class="font-medium text-base text-gray-800">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="font-medium text-sm text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                            Profile
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('membresias.index')" :active="route().current('membresias.*')">
                            Mi Membresia
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('monedas.index')" :active="route().current('monedas.*')">
                            Mi camino Budista
                        </ResponsiveNavLink>

                        <ResponsiveNavLink v-if="!isAsistant && $page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                            API Tokens
                        </ResponsiveNavLink>

                        <!-- Authentication -->
                        <form method="POST" @submit.prevent="logout">
                            <ResponsiveNavLink as="button">
                                Log Out
                            </ResponsiveNavLink>
                        </form>

                        <!-- Team Management -->
                        <template v-if="!isAsistant && $page.props.jetstream.hasTeamFeatures">
                            <div class="border-t border-gray-200" />

                            <div class="block px-4 py-2 text-xs text-gray-400">
                                Manage Team
                            </div>

                            <!-- Team Settings -->
                            <ResponsiveNavLink :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current('teams.show')">
                                Team Settings
                            </ResponsiveNavLink>

                            <ResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')" :active="route().current('teams.create')">
                                Create New Team
                            </ResponsiveNavLink>

                            <!-- Team Switcher -->
                            <template v-if="$page.props.auth.user.all_teams.length > 1">
                                <div class="border-t border-gray-200" />

                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Switch Teams
                                </div>

                                <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                    <form @submit.prevent="switchToTeam(team)">
                                        <ResponsiveNavLink as="button">
                                            <div class="flex items-center">
                                                <svg v-if="team.id == $page.props.auth.user.current_team_id" class="me-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div>{{ team.name }}</div>
                                            </div>
                                        </ResponsiveNavLink>
                                    </form>
                                </template>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="bg-white shadow">
            <div class="w-full py-4 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-grow">
            <slot />
        </main>
        <Footer v-if="$page.props.auth?.user" />
    </div>
</template>
