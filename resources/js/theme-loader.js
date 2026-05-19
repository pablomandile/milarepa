import lightUrl from 'primevue/resources/themes/lara-light-blue/theme.css?url';
import darkUrl from 'primevue/resources/themes/lara-dark-blue/theme.css?url';

function ensureLink() {
    let link = document.getElementById('primevue-theme');
    if (!link) {
        link = document.createElement('link');
        link.id = 'primevue-theme';
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }
    return link;
}

export function applyPrimeVueTheme(mode) {
    ensureLink().href = mode === 'dark' ? darkUrl : lightUrl;
}

export function preloadPrimeVueThemes() {
    [lightUrl, darkUrl].forEach((href) => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'style';
        link.href = href;
        document.head.appendChild(link);
    });
}
