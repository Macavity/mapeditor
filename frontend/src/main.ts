import { createApp } from 'vue';
import { createPinia } from 'pinia';
import VueToast from 'vue-toast-notification';
import 'vue-toast-notification/dist/theme-sugar.css';
import './assets/base.scss';
import piniaPluginPersistedState from 'pinia-plugin-persistedstate';
import vfmPlugin from 'vue-final-modal';

import App from './App.vue';
import router from './router';

const app = createApp(App);
const pinia = createPinia();
pinia.use(piniaPluginPersistedState);

app.use(pinia);
app.use(router);
app.use(VueToast);
app.use(vfmPlugin);

app.mount('#app');
