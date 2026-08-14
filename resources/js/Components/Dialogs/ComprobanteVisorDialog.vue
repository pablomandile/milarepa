<script setup>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';

// Visor de un comprobante (imagen o PDF) servido desde /storage/{path}.
const props = defineProps({
    // v-model:visible del diálogo.
    visible: { type: Boolean, default: false },
    // Ruta relativa al disk público (ej. "comprobantes/abc.jpg").
    path: { type: String, default: '' },
});

const emit = defineEmits(['update:visible']);

const visibleProxy = computed({
    get: () => props.visible,
    set: (v) => emit('update:visible', v),
});

const isPdf = computed(() => (props.path || '').toLowerCase().endsWith('.pdf'));

// Fallback autocontenido (data-URI) si la imagen del comprobante está rota.
const IMAGEN_FALLBACK = 'data:image/svg+xml;utf8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">' +
    '<rect width="200" height="200" fill="#e5e7eb"/>' +
    '<text x="100" y="105" font-family="sans-serif" font-size="14" fill="#6b7280" text-anchor="middle">Sin imagen</text>' +
    '</svg>'
);
const onImgError = (event) => {
    const el = event?.target;
    if (!el || el.dataset.fallback) return;
    el.dataset.fallback = '1';
    el.src = IMAGEN_FALLBACK;
};
</script>

<template>
    <Dialog
        v-model:visible="visibleProxy"
        modal
        header="Comprobante"
        :style="{ width: '600px' }"
        :breakpoints="{ '575px': '95vw' }"
        dismissableMask
    >
        <div class="max-h-[70vh] overflow-y-auto">
            <template v-if="isPdf">
                <iframe :src="'/storage/' + path" class="w-full h-[60vh] rounded"></iframe>
            </template>
            <template v-else>
                <img
                    v-if="path"
                    :src="'/storage/' + path"
                    class="w-full rounded"
                    alt="Comprobante"
                    @error="onImgError"
                />
            </template>
        </div>
    </Dialog>
</template>
