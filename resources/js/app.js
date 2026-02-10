import './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';

// Mount Vue app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const appElement = document.getElementById('app');
    if (appElement) {
        createApp(App).mount('#app');
    }
});
