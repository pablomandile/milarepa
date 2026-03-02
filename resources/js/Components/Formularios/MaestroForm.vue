<script>
    export default {
        name: 'MaestrosForm'
    }
</script>

<script setup>
import { nextTick, ref } from 'vue';
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';

    const props = defineProps({
        form: {
        type: Object,
        required: true
    },
        updating: {
        type: Boolean,
        required: true,
        default: false
    },
    imagenPreviewUrl: {
        type: String,
        default: ''
    }
    })

    defineEmits(['submit'])

    const sobreTextarea = ref(null);
    const titleLevel = ref(1);

    const applyToSelection = (transformer) => {
        const textarea = sobreTextarea.value;
        if (!textarea) return;

        const start = textarea.selectionStart ?? 0;
        const end = textarea.selectionEnd ?? 0;
        if (start === end) return;

        const value = props.form.sobre_maestro || '';
        const selected = value.slice(start, end);
        const replaced = transformer(selected);
        if (typeof replaced !== 'string') return;

        props.form.sobre_maestro = value.slice(0, start) + replaced + value.slice(end);

        nextTick(() => {
            textarea.focus();
            textarea.setSelectionRange(start, start + replaced.length);
        });
    };

    const applyTitle = () => {
        const marker = '#'.repeat(titleLevel.value);
        applyToSelection((selected) => selected
            .split('\n')
            .map((line) => `${marker} ${line.trim()}`.trim())
            .join('\n')
        );
        titleLevel.value = titleLevel.value === 3 ? 1 : titleLevel.value + 1;
    };

    const applyBold = () => {
        applyToSelection((selected) => `**${selected}**`);
    };

    const applyItalic = () => {
        applyToSelection((selected) => `*${selected}*`);
    };

    const applyList = () => {
        applyToSelection((selected) => selected
            .split('\n')
            .map((line) => `- ${line.trim()}`)
            .join('\n')
        );
    };

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Maestro' : 'Nuevo Maestro' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Maestro seleccionado' : 'Agregando un nuevo Maestro/a para estar a cargo de actividades.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="email" value="Correo electrónico" :required="true"/>
                <TextInput id="email" v-model="form.email" type="text" autocomplete="email" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="telefono" value="Telefono" :required="false"/>
                <TextInput id="telefono" v-model="form.telefono" type="text" autocomplete="telefono" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.telefono" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="sobre_maestro" value="Sobre el maestr@" :required="false"/>
                <div class="mt-1 mb-2 w-full flex items-center gap-2 rounded-md border border-gray-300 bg-gray-50 p-2">
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100" title="Título (#, ##, ###)" aria-label="Título" @click="applyTitle">T</button>
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100" title="Negrita (**texto**)" aria-label="Negrita" @click="applyBold">N</button>
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100 italic" title="Cursiva (*texto*)" aria-label="Cursiva" @click="applyItalic">C</button>
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100" title="Lista (- item)" aria-label="Lista" @click="applyList">L</button>
                </div>
                <textarea
                    ref="sobreTextarea"
                    id="sobre_maestro"
                    v-model="form.sobre_maestro"
                    rows="5"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Agrega una descripcion del maestr@..."
                ></textarea>
                <InputError :message="$page.props.errors.sobre_maestro" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel
                    for="imagen_id"
                    class="text-indigo-400 mb-2"
                    value="Foto del Maestro"
                    :required="false"
                />
                <div class="flex items-start gap-4">
                    <SingleImageUploader
                        v-model:imagenId="form.imagen_id"
                        folder="img/maestros"
                    />
                    <div v-if="imagenPreviewUrl" class="flex items-center gap-2">
                        <img
                            :src="imagenPreviewUrl"
                            alt="Imagen actual"
                            class="h-16 w-16 rounded border border-gray-200 object-cover"
                        />
                        <span class="text-sm text-gray-500">Actual</span>
                    </div>
                </div>
                <InputError :message="$page.props.errors.imagen_id" class="mt-2" />
            </div>

        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>


