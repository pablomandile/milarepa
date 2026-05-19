import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { applyPrimeVueTheme } from '../theme-loader';

const STORAGE_KEY = 'theme';

function readStoredTheme() {
    try {
        const v = localStorage.getItem(STORAGE_KEY);
        return v === 'dark' || v === 'light' ? v : null;
    } catch (e) {
        return null;
    }
}

function systemPrefersDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function writeStoredTheme(value) {
    try {
        localStorage.setItem(STORAGE_KEY, value);
    } catch (e) {}
}

function applyDocumentClass(mode) {
    const html = document.documentElement;
    if (mode === 'dark') html.classList.add('dark');
    else html.classList.remove('dark');
}

// Estado singleton: si el usuario tiene preferencia en DB, eso manda; sino localStorage; sino sistema.
function resolveInitial() {
    let dbPref = null;
    try {
        const page = usePage();
        dbPref = page?.props?.auth?.theme_preference ?? null;
    } catch (e) {
        // usePage puede fallar fuera de un componente; está OK
    }
    if (dbPref === 'dark' || dbPref === 'light') return dbPref;
    const stored = readStoredTheme();
    if (stored) return stored;
    return systemPrefersDark() ? 'dark' : 'light';
}

const resolvedTheme = ref(resolveInitial());

// Aplicar al cargar (por si la página entró sin pasar por el script anti-FOUC, ej. transición Inertia)
applyDocumentClass(resolvedTheme.value);
applyPrimeVueTheme(resolvedTheme.value);

// Escuchar cambios del sistema solo si el usuario aún no eligió manualmente
const mq = window.matchMedia('(prefers-color-scheme: dark)');
mq.addEventListener?.('change', (e) => {
    const hasExplicit = readStoredTheme() !== null;
    if (hasExplicit) return;
    const next = e.matches ? 'dark' : 'light';
    resolvedTheme.value = next;
    applyDocumentClass(next);
    applyPrimeVueTheme(next);
});

export function useTheme() {
    const isDark = computed(() => resolvedTheme.value === 'dark');

    function setTheme(value) {
        if (value !== 'light' && value !== 'dark') return;
        resolvedTheme.value = value;
        writeStoredTheme(value);
        applyDocumentClass(value);
        applyPrimeVueTheme(value);

        // Persistir en DB si el usuario está autenticado
        let isAuthenticated = false;
        try {
            const page = usePage();
            isAuthenticated = !!page?.props?.auth?.user || !!page?.props?.user;
        } catch (e) {}

        if (isAuthenticated && typeof route === 'function') {
            axios.put(route('user.theme-preference.update'), { theme: value }).catch(() => {
                // Falla silenciosa: la preferencia ya quedó en localStorage
            });
        }
    }

    function toggleTheme() {
        setTheme(resolvedTheme.value === 'dark' ? 'light' : 'dark');
    }

    return {
        resolvedTheme,
        isDark,
        setTheme,
        toggleTheme,
    };
}
