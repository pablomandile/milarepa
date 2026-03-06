<script>
export default {
    name: 'LugaresForm',
};
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue';
import SectionTitle from '@/Components/SectionTitle.vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import InputMask from 'primevue/inputmask';

defineProps({
    form: {
        type: Object,
        required: true,
    },
    updating: {
        type: Boolean,
        required: true,
        default: false,
    },
});

defineEmits(['submit']);
</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Lugar' : 'Nuevo Lugar' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el lugar seleccionado' : 'Configura los datos principales del lugar' }}
                </template>
            </SectionTitle>
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" class="text-indigo-400" value="Nombre" :required="true" />
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" maxlength="200" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="descripcion" class="text-indigo-400" value="Descripcion" :required="true" />
                <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="direccion" class="text-indigo-400" value="Direccion" :required="true" />
                <TextInput id="direccion" v-model="form.direccion" type="text" autocomplete="direccion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.direccion" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="telefono" class="text-indigo-400" value="Telefono" :required="true" />
                <InputMask
                    id="telefono"
                    v-model="form.telefono"
                    mask="+99 9 99 9999 9999"
                    placeholder="+54 9 11 1234 5678"
                    slotChar="_"
                    :unmask="true"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
                <InputError :message="$page.props.errors.telefono" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="whatsapp" class="text-indigo-400" value="WhatsApp" :required="false" />
                <InputMask
                    id="whatsapp"
                    v-model="form.whatsapp"
                    mask="+99 9 99 9999 9999"
                    placeholder="+54 9 11 1234 5678"
                    slotChar="_"
                    :unmask="true"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
                <InputError :message="$page.props.errors.whatsapp" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="web_uri" class="text-indigo-400" value="Web" :required="false" />
                <TextInput id="web_uri" v-model="form.web_uri" type="text" autocomplete="web_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.web_uri" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="instagram_uri" class="text-indigo-400" value="Instagram" :required="false" />
                <TextInput id="instagram_uri" v-model="form.instagram_uri" type="text" autocomplete="instagram_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.instagram_uri" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="logo_uri" class="text-indigo-400" value="Logo url" :required="false" />
                <TextInput id="logo_uri" v-model="form.logo_uri" type="text" autocomplete="logo_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.logo_uri" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="email1" class="text-indigo-400" value="Correo electronico" :required="false" />
                <TextInput id="email1" v-model="form.email1" type="text" autocomplete="email1" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email1" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="email2" class="text-indigo-400" value="Correo electronico secundario" :required="false" />
                <TextInput id="email2" v-model="form.email2" type="text" autocomplete="email2" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email2" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>

