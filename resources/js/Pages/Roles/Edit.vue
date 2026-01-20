<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SectionTitle from '@/Components/SectionTitle.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    role: Object,
});

const form = useForm({
    name: props.role.name,
    guard_name: props.role.guard_name,
});

const submit = () => {
    form.put(route('roles.update', props.role.id), {
        onFinish: () => form.reset(),
    });
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

        <div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
            <FormSection @submitted="submit">
                <template #title>
                    <SectionTitle>
                        <h3 class="text-lg font-medium text-gray-900">
                            Editar Rol
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Editando el rol "{{ props.role.name }}".
                        </p>
                    </SectionTitle>
                </template>

                <template #form>
                    <div class="col-span-6 sm:col-span-4">
                        <InputLabel for="name" value="Nombre del Rol" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="col-span-6 sm:col-span-4 mb-4">
                        <InputLabel for="guard_name" value="Guard" />
                        <TextInput
                            id="guard_name"
                            v-model="form.guard_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.guard_name" />
                    </div>
                </template>

                <template #actions>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Actualizar Rol
                    </PrimaryButton>
                </template>
            </FormSection>
        </div>
    </AppLayout>
</template>
