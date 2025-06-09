import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

// Add response interceptor to handle 401/419 responses
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        return Promise.reject(error);
    },
);

// Initialize authentication state
import { useAuth } from '@/composables/useAuth';
const { initializeAuth } = useAuth();

// Check if user is authenticated when app loads
initializeAuth().catch(() => {
    // User is not authenticated, which is fine
});
