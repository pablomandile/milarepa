<script setup>
import { computed } from 'vue';
import { useTheme } from '@/composables/useTheme';

const props = defineProps({
    variant: {
        type: String,
        default: 'dropdown', // 'dropdown' | 'responsive'
        validator: (v) => ['dropdown', 'responsive'].includes(v),
    },
});

const { isDark, toggleTheme } = useTheme();

const iconClass = computed(() => (isDark.value ? 'pi pi-sun' : 'pi pi-moon'));
const label = computed(() => (isDark.value ? 'Modo claro' : 'Modo oscuro'));

const buttonClass = computed(() => {
    if (props.variant === 'responsive') {
        return 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out';
    }
    return 'block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out';
});
</script>

<template>
    <button type="button" :class="buttonClass" @click="toggleTheme">
        <i :class="iconClass" class="mr-2"></i>
        {{ label }}
    </button>
</template>
