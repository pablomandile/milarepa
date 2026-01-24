<script setup>
import { ref } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    role: Object,
    allPermissions: Array,
    rolePermissions: Array,
});

const page = usePage();

const form = useForm({
    name: props.role.name,
    guard_name: props.role.guard_name,
    permissions: props.rolePermissions,
});

const searchQuery = ref('');

const filteredPermissions = () => {
    return props.allPermissions.filter(p => 
        p.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
};

const togglePermission = (permissionId) => {
    const index = form.permissions.indexOf(permissionId);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permissionId);
    }
};

const isPermissionSelected = (permissionId) => {
    return form.permissions.includes(permissionId);
};

const selectAll = () => {
    form.permissions = props.allPermissions.map(p => p.id);
};

const deselectAll = () => {
    form.permissions = [];
};

const submit = () => {
    form.put(route('roles.update', props.role.id));
};
</script>

<template>
    <Head title="Editar Rol" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar Rol
            </h2>
        </template>

        <div class="max-w-6xl mx-auto py-10 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form @submit.prevent="submit">
                        <!-- Información básica del rol -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Información del Rol</h3>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <InputLabel for="name" value="Nombre del Rol" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        disabled
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <div>
                                    <InputLabel for="guard_name" value="Guard" />
                                    <TextInput
                                        id="guard_name"
                                        v-model="form.guard_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        disabled
                                    />
                                    <InputError class="mt-2" :message="form.errors.guard_name" />
                                </div>
                            </div>
                        </div>

                        <!-- Gestión de permisos -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Asignar Permisos</h3>
                            
                            <!-- Búsqueda -->
                            <div class="mb-4">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Buscar permisos..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600"
                                />
                            </div>

                            <!-- Botones Seleccionar/Deseleccionar todo -->
                            <div class="mb-4 flex gap-2">
                                <button
                                    type="button"
                                    @click="selectAll"
                                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                >
                                    Seleccionar Todo
                                </button>
                                <button
                                    type="button"
                                    @click="deselectAll"
                                    class="px-4 py-2 text-sm bg-gray-400 text-white rounded-lg hover:bg-gray-500"
                                >
                                    Deseleccionar Todo
                                </button>
                            </div>

                            <!-- Lista de permisos -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-96 overflow-y-auto border border-gray-200 p-4 rounded-lg">
                                <div
                                    v-for="permission in filteredPermissions()"
                                    :key="permission.id"
                                    class="flex items-center"
                                >
                                    <input
                                        :id="`permission-${permission.id}`"
                                        type="checkbox"
                                        :checked="isPermissionSelected(permission.id)"
                                        @change="togglePermission(permission.id)"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                    />
                                    <label
                                        :for="`permission-${permission.id}`"
                                        class="ml-2 text-sm text-gray-700 cursor-pointer"
                                    >
                                        {{ permission.name }}
                                    </label>
                                </div>
                            </div>

                            <div v-if="filteredPermissions().length === 0" class="text-center text-gray-500 mt-4">
                                No se encontraron permisos
                            </div>

                            <InputError class="mt-2" :message="form.errors.permissions" />
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex justify-between items-center mt-6">
                            <Link 
                                :href="route('perfiles.index')" 
                                class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:bg-gray-600 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Cancelar
                            </Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Actualizar Rol
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
