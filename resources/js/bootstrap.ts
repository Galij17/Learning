import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

function getCookieValue(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : null;
}

window.axios.interceptors.request.use((config) => {
    const xsrfToken = getCookieValue('XSRF-TOKEN');
    if (xsrfToken) {
        config.headers.set('X-XSRF-TOKEN', xsrfToken);
    } else {
        const metaToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        if (metaToken) {
            config.headers.set('X-CSRF-TOKEN', metaToken);
        }
    }
    return config;
});
