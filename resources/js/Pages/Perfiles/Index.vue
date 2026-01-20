<script>
    export default {
        name: 'PerfilesIndex'
    }
</script>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    roles: Object,
    permissions: Array,
});

const page = usePage();

const getPermissionsByRole = (roleId) => {
    const role = props.roles.data.find(r => r.id === roleId);
    if (role && role.permissions) {
        return role.permissions.map(p => p.name).join(', ');
    }
    return 'Sin permisos';
};

const formatRoleName = (roleName) => {
    if (roleName === 'admin') return 'Administrador';
    if (roleName === 'asistant') return 'Asistente';
    return roleName.charAt(0).toUpperCase() + roleName.slice(1);
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Perfiles y Permisos</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Roles y sus Permisos</h2>
                        <p class="text-sm text-gray-600 mt-1">Visualiza los permisos asignados a cada rol del sistema.</p>
                    </div>
                    <div class="mt-6">
                        <DataTable :value="roles.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="name" header="Rol" style="width: 15%">
                                <template #body="slotProps">
                                    {{ formatRoleName(slotProps.data.name) }}
                                </template>
                            </Column>
                            <Column field="guard_name" header="Guard" style="width: 15%"></Column>
                            <Column header="Permisos" style="width: 70%">
                                <template #body="slotProps">
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="permission in slotProps.data.permissions"
                                            :key="permission.id"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                        >
                                            {{ permission.name }}
                                        </span>
                                        <span v-if="!slotProps.data.permissions || slotProps.data.permissions.length === 0" class="text-gray-500 text-sm italic">
                                            Sin permisos asignados
                                        </span>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
