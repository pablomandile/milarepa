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

</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Perfil de usuario
            </h2>
        </template>

        <div>
            <div class="max-w-5xl mx-auto py-10 sm:px-6 lg:px-8">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <UpdateProfileInformationForm :user="$page.props.auth.user" />

                    <SectionBorder />

                    <!-- Mostrar datos extras en una tarjeta PrimeVue -->
                    <Card class="mb-6">
                        <template #header>
                            <h2 class="text-lg font-semibold ml-6 pt-4">Datos adicionales</h2>
                        </template>

                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 ml-6 mr-10">
                                 <!-- Columna 1 -->
                                    <div>
                                        <div class="mb-2">
                                            País<br>
                                            <span class="text-indigo-600">
                                                <strong>
                                                    <p>{{ $page.props.auth.user.pais.nombre }}</p>
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            Localidad<br>
                                            <span class="text-indigo-600">
                                                <strong>
                                                    {{ $page.props.auth.user.localidad.nombre }}
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            Dirección<br>
                                            <span class="text-indigo-600">
                                                <strong>
                                                    {{ $page.props.auth.user.direccion ?? 'No especificado' }}
                                                </strong>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Columna 2 -->
                                    <div class="ml-8">
                                        <div class="mb-2">
                                            Teléfono<br>
                                            <span class="text-indigo-600">
                                                <strong>
                                                    {{ $page.props.auth.user.telefono ?? 'No especificado' }}
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            WhatsApp<br>
                                            <span class="text-indigo-600">
                                                <strong>
                                                    {{ $page.props.auth.user.whatsapp ?? 'No especificado' }}
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            ¿Recibe info por Whatsapp?<br>
                                            <strong class="text-indigo-600">
                                                <div v-if="$page.props.auth.user.msgxwapp">Sí</div>
                                                <div v-else>No</div>
                                            </strong>
                                        </div>
                                    </div>

                                    <!-- Columna 3 -->
                                    <div class="ml-8">
                                        
                                        <div class="mb-2">
                                            Edad<br>
                                            <span class="text-indigo-600">
                                                <strong>
                                                    {{ $page.props.auth.user.edad ?? 'No especificado' }}
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            Sexo<br>
                                            <span class="text-indigo-600">
                                                <strong>
                                                    {{ $page.props.auth.user.sexo.sexo }}
                                                </strong>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Columna 4 -->
                                    <div>
                                        <div class="mb-2">
                                            Membresía<br>
                                            <span class="text-indigo-500">
                                                <strong>
                                                    {{ $page.props.auth.user.membresia.nombre }}
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            Asiste a:<br>
                                            <span class="text-indigo-500">
                                                <strong>
                                                    {{ $page.props.auth.user.membresia.entidad.nombre }}
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            ¿Recibe info por correo electrónico?<br>
                                            <strong class="text-indigo-600">
                                                <div v-if="$page.props.auth.user.msgxmail">Sí</div>
                                                <div v-else>No</div>
                                            </strong>
                                        </div>
                                       
                                    </div>
                                    <div class="col-span-4">
                                        <div class="mb-2">
                                            
                                            Necesidades especiales
                                            <div v-if="$page.props.auth.user.accesibilidad"> 
                                                <span class="text-indigo-600">
                                                   <strong>Sí</strong>
                                                </span>
                                                <div class="mt-1">
                                                    <p>Detalle de necesidad especial</p><br>
                                                    <span style="font-size: 1.5rem" class="pi pi-exclamation-triangle text-white bg-yellow-300 p-3" >
                                                        <strong style="font-size: 1rem" class="ml-2">{{ $page.props.auth.user.accesibilidad_desc ?? 'No especificado' }} </strong>
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else class="text-indigo-600">
                                                <strong>No</strong>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </template>

                        <template #footer>
                            <!-- Botón que lleva a la página separada -->
                            <Link :href="route('profile.complete.edit')">
                                <PrimaryButton class="mt-4 ml-4">
                                    Completar / Editar Datos Adicionales
                                </PrimaryButton>
                            </Link>
                        </template>
                    </Card>

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
