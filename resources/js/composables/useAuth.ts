import api, { initializeCSRF } from '@/lib/api';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, readonly, ref } from 'vue';

// Global auth state
const user = ref(null);
const isAuthenticated = computed(() => !!user.value);

export function useAuth() {
    const login = async (credentials: { email: string; password: string }) => {
        try {
            // Make sure we have a CSRF token first
            await initializeCSRF();

            // Attempt login via web routes (since we're using session-based auth)
            await axios.post('/login', credentials);

            // After successful login, get the user via API
            await getUser();

            return { success: true, user: user.value };
        } catch (error: any) {
            return {
                success: false,
                message: error.response?.data?.message || 'Login failed',
            };
        }
    };

    const logout = async () => {
        try {
            await axios.post('/logout');
            user.value = null;
            router.visit('/login');
        } catch (error) {
            // Even if logout fails, clear local state
            user.value = null;
            router.visit('/login');
        }
    };

    const getUser = async () => {
        try {
            const response = await api.get('/user');
            user.value = response.data;
            return response.data;
        } catch (error) {
            user.value = null;
            return null;
        }
    };

    const initializeAuth = async () => {
        try {
            // First get CSRF token
            await initializeCSRF();
            // Then try to get the current user
            await getUser();
        } catch (error) {
            // User is not authenticated
            user.value = null;
        }
    };

    return {
        user: readonly(user),
        isAuthenticated,
        login,
        logout,
        getUser,
        initializeAuth,
    };
}
