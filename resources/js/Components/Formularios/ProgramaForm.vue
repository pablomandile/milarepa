<script>
    export default {
        name: 'ProgramaForm'
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
import Textarea from 'primevue/textarea';


    const props = defineProps({
        form: {
        type: Object,
        required: true
    },
        updating: {
        type: Boolean,
        required: true,
        default: false
    }
    })

    defineEmits(['submit'])
    const programaTextarea = ref(null);
    const titleLevel = ref(1);

    const applyToSelection = (transformer) => {
        const textarea = programaTextarea.value?.$el?.querySelector('textarea')
            || programaTextarea.value?.$el
            || programaTextarea.value;
        if (!textarea) return;

        const start = textarea.selectionStart ?? 0;
        const end = textarea.selectionEnd ?? 0;
        if (start === end) return;

        const value = props.form.programa || '';
        const selected = value.slice(start, end);
        const replaced = transformer(selected);
        if (typeof replaced !== 'string') return;

        props.form.programa = value.slice(0, start) + replaced + value.slice(end);

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
                    {{ updating ? 'Actualizar Programa' : 'Nuevo Programa' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Programa seleccionado' : 'Agregando un nuevo Programa.' }}
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
                <InputLabel for="programa" value="Programa" :required="true"/>
                <div class="mt-1 mb-2 w-full flex items-center gap-2 rounded-md border border-gray-300 bg-gray-50 p-2">
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100" title="Título (#, ##, ###)" aria-label="Título" @click="applyTitle">T</button>
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100" title="Negrita (**texto**)" aria-label="Negrita" @click="applyBold">N</button>
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100 italic" title="Cursiva (*texto*)" aria-label="Cursiva" @click="applyItalic">C</button>
                    <button type="button" class="h-9 w-12 rounded border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-100" title="Lista (- item)" aria-label="Lista" @click="applyList">L</button>
                </div>
                <Textarea ref="programaTextarea" id="programa" v-model="form.programa" autoResize rows="5" cols="30" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                <InputError :message="$page.props.errors.programa" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
