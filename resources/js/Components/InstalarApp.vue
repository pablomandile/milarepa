<script setup>
/**
 * Botón "Instalar app" (PWA). Solo se ve si el navegador puede instalarla (o en
 * iPhone/iPad, donde se instala a mano) y se esconde con la app ya instalada.
 *
 * Variantes:
 *   - dropdown   → ítem del menú de cuenta en escritorio
 *   - responsive → ítem del menú de cuenta en el celular
 *   - tarjeta    → tarjeta destacada arriba del panel del usuario
 */
import { computed, ref } from 'vue';
import Dialog from 'primevue/dialog';
import { usePwaInstall } from '@/composables/usePwaInstall';

// Tiene varias raíces (botón + diálogo): las clases que pase el padre van al botón/tarjeta.
defineOptions({ inheritAttrs: false });

const props = defineProps({
    variant: {
        type: String,
        default: 'dropdown',
        validator: (v) => ['dropdown', 'responsive', 'tarjeta'].includes(v),
    },
});

const { canInstall, isIos, install } = usePwaInstall();
const ayudaVisible = ref(false);
const ocupado = ref(false);

async function instalar() {
    if (ocupado.value) return;
    ocupado.value = true;
    try {
        // 'manual': iPhone/iPad, o el aviso del navegador ya se usó → pasos a mano.
        if ((await install()) === 'manual') ayudaVisible.value = true;
    } finally {
        ocupado.value = false;
    }
}

const itemClass = computed(() =>
    props.variant === 'responsive'
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out'
        : 'block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out'
);
</script>

<template>
    <template v-if="canInstall">
        <div
            v-if="variant === 'tarjeta'"
            v-bind="$attrs"
            class="instalar-app-tarjeta flex flex-col sm:flex-row sm:items-center gap-4 rounded-xl border border-indigo-100 dark:border-indigo-900 bg-gradient-to-r from-indigo-50 to-sky-50 dark:from-gray-800 dark:to-indigo-950 p-4 sm:p-5 shadow-sm"
        >
            <img src="/icons/icon-192.png?v=1" alt="" class="h-14 w-14 shrink-0 rounded-xl shadow-sm bg-white" />
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-semibold text-indigo-800 dark:text-indigo-200">Instalá la app en tu celular</p>
                <p class="text-sm text-gray-600 dark:text-gray-300">Tenela en tu pantalla de inicio y abrila como cualquier otra aplicación.</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-full bg-indigo-600 hover:bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors disabled:opacity-60"
                :disabled="ocupado"
                @click="instalar"
            >
                <i class="pi pi-download" aria-hidden="true"></i>
                Instalar app
            </button>
        </div>

        <button v-else v-bind="$attrs" type="button" :class="itemClass" :disabled="ocupado" @click="instalar">
            <i class="pi pi-download mr-2 text-indigo-500" aria-hidden="true"></i>
            Instalar app
        </button>
    </template>

    <Dialog
        v-model:visible="ayudaVisible"
        modal
        header="Instalar la app"
        :style="{ width: '26rem' }"
        :breakpoints="{ '480px': '92vw' }"
    >
        <ol v-if="isIos" class="list-decimal space-y-2 pl-5 text-gray-700 dark:text-gray-200">
            <li>Tocá <strong>Compartir</strong> <i class="pi pi-upload text-indigo-500" aria-hidden="true"></i> en la barra de Safari.</li>
            <li>Elegí <strong>Agregar a inicio</strong>.</li>
            <li>Tocá <strong>Agregar</strong>: el ícono de Milarepa queda en tu pantalla de inicio.</li>
        </ol>
        <ol v-else class="list-decimal space-y-2 pl-5 text-gray-700 dark:text-gray-200">
            <li>Abrí el menú del navegador <i class="pi pi-ellipsis-v text-indigo-500" aria-hidden="true"></i>.</li>
            <li>Elegí <strong>Instalar aplicación</strong> (o <strong>Agregar a pantalla de inicio</strong>).</li>
            <li>Confirmá: el ícono de Milarepa queda en tu pantalla de inicio.</li>
        </ol>
    </Dialog>
</template>
