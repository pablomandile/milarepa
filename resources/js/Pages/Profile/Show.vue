<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from 'primevue/card';
import { Link } from '@inertiajs/vue3';


defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});

function computeAge(dateStr) {
    if (!dateStr) return 'No especificado';
    const isoPart = String(dateStr).split('T')[0];
    const parts = isoPart.split('-');
    if (parts.length < 3) return 'No especificado';
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    const d = parseInt(parts[2], 10);
    if (Number.isNaN(y) || Number.isNaN(m) || Number.isNaN(d)) return 'No especificado';
    const today = new Date();
    let age = today.getFullYear() - y;
    const mm = today.getMonth() + 1;
    const dd = today.getDate();
    if (mm < m || (mm === m && dd < d)) age--;
    return age;
}

</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Perfil de usuario
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <div class="max-w-xl mx-auto mb-3">
                        <Card class="shadow-md">
                            <template #header>
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4 rounded-t-lg">
                                    <h3 class="text-white font-semibold flex items-center">
                                        <i class="pi pi-user-edit mr-2 text-xl"></i>
                                        Datos básicos
                                    </h3>
                                </div>
                            </template>
                            <template #content>
                                <UpdateProfileInformationForm :user="$page.props.auth.user" />
                            </template>
                        </Card>
                    </div>

                    <SectionBorder />

                    <!-- Mostrar datos extras con cards coloridas -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-bold text-indigo-700">Datos adicionales</h2>
                            <Link :href="route('profile.complete.edit')">
                                <PrimaryButton>
                                    <i class="pi pi-pencil mr-2"></i>
                                    Editar Datos
                                </PrimaryButton>
                            </Link>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <!-- Card Ubicación -->
                            <Card class="shadow-md hover:shadow-lg transition-shadow">
                                <template #header>
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-4 rounded-t-lg">
                                        <h3 class="text-white font-semibold flex items-center">
                                            <i class="pi pi-map-marker mr-2 text-xl"></i>
                                            Ubicación
                                        </h3>
                                    </div>
                                </template>
                                <template #content>
                                    <div class="space-y-3">
                                        <div class="flex items-start">
                                            <i class="pi pi-globe text-indigo-500 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">País</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.pais.nombre }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <i class="pi pi-building text-indigo-500 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Provincia</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.provincia?.nombre ?? 'No especificado' }}</p>
                                            </div>
                                        </div>
                                        <div v-if="$page.props.auth.user.municipio" class="flex items-start">
                                            <i class="pi pi-map text-indigo-500 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Municipio</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.municipio.nombre }}</p>
                                            </div>
                                        </div>
                                        <div v-if="$page.props.auth.user.barrio" class="flex items-start">
                                            <i class="pi pi-home text-indigo-500 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Barrio</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.barrio.nombre }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <i class="pi pi-directions text-indigo-500 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Dirección</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.direccion ?? 'No especificado' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Card>

                            <!-- Card Contacto -->
                            <Card class="shadow-md hover:shadow-lg transition-shadow">
                                <template #header>
                                    <div class="bg-gradient-to-r from-green-500 to-teal-600 p-4 rounded-t-lg">
                                        <h3 class="text-white font-semibold flex items-center">
                                            <i class="pi pi-phone mr-2 text-xl"></i>
                                            Contacto
                                        </h3>
                                    </div>
                                </template>
                                <template #content>
                                    <div class="space-y-3">
                                        <div class="flex items-start">
                                            <i class="pi pi-mobile text-green-600 mr-2 mt-1 text-lg"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Teléfono</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.telefono ?? 'No especificado' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <i class="pi pi-whatsapp text-green-600 mr-2 mt-1 text-lg"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">WhatsApp</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.whatsapp ?? 'No especificado' }}</p>
                                            </div>
                                        </div>
                                        <div class="bg-green-50 p-3 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-700 mr-3">Info por WhatsApp</span>
                                                <i :class="$page.props.auth.user.msgxwapp ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-gray-400'" class="text-xl"></i>
                                            </div>
                                        </div>
                                        <div class="bg-blue-50 p-3 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm text-gray-700 mr-3">Info por Email</span>
                                                <i :class="$page.props.auth.user.msgxmail ? 'pi pi-check-circle text-blue-600' : 'pi pi-times-circle text-gray-400'" class="text-xl"></i>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Card>

                            <!-- Card Perfil Personal -->
                            <Card class="shadow-md hover:shadow-lg transition-shadow">
                                <template #header>
                                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-4 rounded-t-lg">
                                        <h3 class="text-white font-semibold flex items-center">
                                            <i class="pi pi-user mr-2 text-xl"></i>
                                            Perfil Personal
                                        </h3>
                                    </div>
                                </template>
                                <template #content>
                                    <div class="space-y-3">
                                        <div class="flex items-start">
                                            <i class="pi pi-users text-purple-600 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Sexo</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.sexo.sexo }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <i class="pi pi-calendar text-purple-600 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Edad</p>
                                                <p class="font-semibold text-gray-800">{{ computeAge($page.props.auth.user.fecha_nacimiento) }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <i class="pi pi-id-card text-purple-600 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Membresía</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.membresia.nombre }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <i class="pi pi-building text-purple-600 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Asiste a</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.membresia.entidad.nombre }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start">
                                            <i class="pi pi-book text-purple-600 mr-2 mt-1"></i>
                                            <div>
                                                <p class="text-xs text-gray-500">Programa de estudio</p>
                                                <p class="font-semibold text-gray-800">{{ $page.props.auth.user.programa_estudio ? $page.props.auth.user.programa_estudio.nombre : ($page.props.auth.user.programa_estudio_id ? 'ID ' + $page.props.auth.user.programa_estudio_id : 'No especificado') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Card>

                            <!-- Card Accesibilidad (si aplica) -->
                            <Card v-if="$page.props.auth.user.accesibilidad" class="shadow-md hover:shadow-lg transition-shadow md:col-span-2 lg:col-span-3">
                                <template #header>
                                    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 p-4 rounded-t-lg">
                                        <h3 class="text-white font-semibold flex items-center">
                                            <i class="pi pi-exclamation-triangle mr-2 text-xl"></i>
                                            Necesidades Especiales
                                        </h3>
                                    </div>
                                </template>
                                <template #content>
                                    <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                                        <div class="flex items-start">
                                            <i class="pi pi-info-circle text-yellow-600 mr-3 mt-1 text-2xl"></i>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-700 mb-1">Detalle de necesidad especial:</p>
                                                <p class="text-gray-800">{{ $page.props.auth.user.accesibilidad_desc ?? 'No especificado' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Card>
                        </div>
                    </div>

                    <SectionBorder />

                </div>

                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <UpdatePasswordForm class="mt-10 sm:mt-0" />

                    <SectionBorder />
                </div>

                <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                    <TwoFactorAuthenticationForm
                        :requires-confirmation="confirmsTwoFactorAuthentication"
                        class="mt-10 sm:mt-0"
                    />

                    <SectionBorder />
                </div>

                <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />

                <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                    <SectionBorder />

                    <DeleteUserForm class="mt-10 sm:mt-0" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
