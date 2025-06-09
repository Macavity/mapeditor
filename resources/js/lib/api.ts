import axios from 'axios';

// Create a centralized axios instance for API calls
export const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// Add response interceptor to handle authentication errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        return Promise.reject(error);
    },
);

// Helper function to initialize CSRF protection
export const initializeCSRF = async (): Promise<void> => {
    await axios.get('/sanctum/csrf-cookie');
};

export default api;
