import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Initialize Sanctum CSRF protection
await axios.get('/sanctum/csrf-cookie');

// Add response interceptor to handle 401/419 responses
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 || error.response?.status === 419) {
            router.visit('/login');
        }
        return Promise.reject(error);
    },
);
