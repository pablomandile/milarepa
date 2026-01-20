<script>
    export default {
        name: 'UserForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import InputSwitch from 'primevue/inputswitch';
import Dropdown from 'primevue/dropdown';
import { usePage } from '@inertiajs/vue3';

const $page = usePage();

defineProps({
    form: {
        type: Object,
        required: true
    },
    updating: {
        type: Boolean,
        required: true,
        default: false
    },
    roles: {
        type: Array,
        default: () => []
    }
})

defineEmits(['submit'])

const formatRoleName = (roleName) => {
    if (roleName === 'admin') return 'Administrador';
    if (roleName === 'asistant') return 'Asistente';
    return roleName.charAt(0).toUpperCase() + roleName.slice(1);
}

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Usuario' : 'Nuevo Usuario' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando la información del usuario' : 'Agregando un nuevo usuario.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6 mb-4">
                <InputLabel for="name" value="Nombre Completo" :required="true"/>
                <TextInput id="name" v-model="form.name" type="text" autocomplete="name" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.name" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-4">
                <InputLabel for="email" value="Correo Electrónico" :required="true"/>
                <TextInput id="email" v-model="form.email" type="email" autocomplete="email" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 mb-4">
                <InputLabel for="password" :value="updating ? 'Nueva Contraseña (opcional)' : 'Contraseña'" :required="!updating"/>
                <TextInput id="password" v-model="form.password" type="password" autocomplete="new-password" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.password" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-3 mb-4">
                <InputLabel for="password_confirmation" :value="updating ? 'Confirmar Nueva Contraseña (opcional)' : 'Confirmar Contraseña'" :required="!updating"/>
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.password_confirmation" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-4">
                <InputLabel for="telefono" value="Teléfono"/>
                <TextInput id="telefono" v-model="form.telefono" type="tel" autocomplete="tel" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.telefono" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-4">
                <InputLabel for="whatsapp" value="WhatsApp"/>
                <TextInput id="whatsapp" v-model="form.whatsapp" type="tel" autocomplete="tel" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.whatsapp" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-4">
                <InputLabel for="roles" value="Rol" :required="true"/>
                <Dropdown 
                    id="roles" 
                    v-model="form.roles" 
                    :options="roles" 
                    optionValue="name"
                    :optionLabel="role => formatRoleName(role.name)"
                    placeholder="Seleccionar rol"
                    class="w-full mt-1"
                    input-class="border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError :message="$page.props.errors.roles" class="mt-2" />
            </div>
            <div class="mt-4 flex items-center col-span-6">
                <InputSwitch
                    v-model="form.es_maestro" 
                    class="mr-3"
                    />
                <label for="es_maestro" class="block text-sm text-indigo-400">
                    ¿Es maestro?
                </label>
                <InputError :message="$page.props.errors.es_maestro" class="ml-2" />
            </div>
            <div class="mt-4 flex items-center col-span-6">
                <InputSwitch
                    v-model="form.es_coordinador" 
                    class="mr-3"
                    />
                <label for="es_coordinador" class="block text-sm text-indigo-400">
                    ¿Es coordinador?
                </label>
                <InputError :message="$page.props.errors.es_coordinador" class="ml-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
