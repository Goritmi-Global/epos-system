import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

// Track if we're showing error to prevent multiple alerts
let isShowingError = false;

window.axios.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config;
        
        if (error.response?.status === 419 && !originalRequest._retry) {
            originalRequest._retry = true;
            
            try {
                // Silently try to refresh token
                await axios.get('/sanctum/csrf-cookie');
                
                const newToken = document.head.querySelector('meta[name="csrf-token"]');
                if (newToken) {
                    originalRequest.headers['X-CSRF-TOKEN'] = newToken.content;
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken.content;
                }
                
                // Retry the request
                return window.axios(originalRequest);
            } catch (refreshError) {
                // Only show error once
                if (!isShowingError) {
                    isShowingError = true;
                    
                    // Show user-friendly message
                    const retry = confirm('Your session has expired. Click OK to continue or Cancel to go to login page.');
                    
                    if (retry) {
                        window.location.reload();
                    } else {
                        window.location.href = '/login';
                    }
                }
            }
        }
        
        return Promise.reject(error);
    }
);