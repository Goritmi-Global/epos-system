import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Initial token setup
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Refresh CSRF token every 10 minutes proactively
setInterval(async () => {
    try {
        await axios.get('/sanctum/csrf-cookie');
        const newToken = document.head.querySelector('meta[name="csrf-token"]');
        if (newToken) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken.content;
            console.log('CSRF token refreshed');
        }
    } catch (error) {
        console.error('Failed to refresh CSRF token:', error);
    }
}, 10 * 60 * 1000); // 10 minutes

// Refresh token before each request
window.axios.interceptors.request.use(config => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    return config;
});

// Handle 419 gracefully with retry
window.axios.interceptors.response.use(
    response => response,
    async error => {
        if (error.response?.status === 419 && !error.config._retry) {
            error.config._retry = true;
            
            try {
                await axios.get('/sanctum/csrf-cookie');
                const newToken = document.head.querySelector('meta[name="csrf-token"]');
                if (newToken) {
                    error.config.headers['X-CSRF-TOKEN'] = newToken.content;
                }
                return window.axios(error.config);
            } catch (e) {
                // Silently fail - user will see original error
            }
        }
        return Promise.reject(error);
    }
);