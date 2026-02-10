import { ref, onMounted } from 'vue';

export function useTheme() {
    const isDark = ref(false);

    const checkTheme = () => {
        isDark.value = document.documentElement.classList.contains('dark');
    };

    const toggleTheme = (onSelect2ThemeUpdate) => {
        const html = document.documentElement;
        const body = document.body;
        const currentlyDark = html.classList.contains('dark');

        if (currentlyDark) {
            html.classList.remove('dark');
            body.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            isDark.value = false;
        } else {
            html.classList.add('dark');
            body.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            isDark.value = true;
        }

        // Callback for components that need to react to theme change (e.g. Select2)
        if (typeof onSelect2ThemeUpdate === 'function') {
            onSelect2ThemeUpdate();
        }
    };

    onMounted(() => {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const shouldBeDark = savedTheme === 'dark' || (!savedTheme && systemPrefersDark);

        if (shouldBeDark) {
            document.documentElement.classList.add('dark');
            document.body.classList.add('dark');
            isDark.value = true;
        } else {
            document.documentElement.classList.remove('dark');
            document.body.classList.remove('dark');
            isDark.value = false;
        }

        window.addEventListener('storage', (e) => {
            if (e.key === 'theme') {
                if (e.newValue === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.body.classList.add('dark');
                    isDark.value = true;
                } else {
                    document.documentElement.classList.remove('dark');
                    document.body.classList.remove('dark');
                    isDark.value = false;
                }
            }
        });
    });

    return { isDark, checkTheme, toggleTheme };
}
