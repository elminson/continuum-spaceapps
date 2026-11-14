import { ref } from 'vue';

/** True while the page is in dark mode; follows the class on <html>. */
export const isDark = ref(typeof document !== 'undefined' && document.documentElement.classList.contains('dark'));

if (typeof document !== 'undefined') {
    new MutationObserver(() => (isDark.value = document.documentElement.classList.contains('dark'))).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}
