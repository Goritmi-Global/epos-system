import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

function getCsrfToken() {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    console.log('Getting CSRF token from meta:', token ? token.content.substring(0, 20) + '...' : 'NOT FOUND');
    return token ? token.content : null;
}

// Set initial token
const initialToken = getCsrfToken();
if (initialToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = initialToken;
    console.log('✅ Initial CSRF token set');
} else {
    console.error('❌ CSRF token not found in meta tag');
}

// Before each request
window.axios.interceptors.request.use(
    config => {
        const token = getCsrfToken();
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token;
            console.log('📤 Request:', config.method.toUpperCase(), config.url);
        }
        return config;
    },
    error => {
        console.error('❌ Request error:', error);
        return Promise.reject(error);
    }
);

// Handle responses
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
    failedQueue.forEach(prom => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve(token);
        }
    });
    failedQueue = [];
};

window.axios.interceptors.response.use(
    response => {
        console.log('✅ Response:', response.config.url, response.status);
        return response;
    },
    async error => {
        console.error('❌ Response error:', {
            url: error.config?.url,
            status: error.response?.status,
            data: error.response?.data
        });

        const originalRequest = error.config;

        if (error.response?.status === 419) {
            console.warn('⚠️ 419 CSRF Token Mismatch detected');

            if (!originalRequest._retry) {
                if (isRefreshing) {
                    console.log('🔄 Already refreshing, queuing request...');
                    return new Promise((resolve, reject) => {
                        failedQueue.push({ resolve, reject });
                    }).then(token => {
                        originalRequest.headers['X-CSRF-TOKEN'] = token;
                        return axios(originalRequest);
                    }).catch(err => Promise.reject(err));
                }

                originalRequest._retry = true;
                isRefreshing = true;

                console.log('🔄 Attempting to refresh CSRF token...');

                try {
                    await axios.get('/sanctum/csrf-cookie');
                    console.log('✅ CSRF cookie endpoint called');
                    
                    const newToken = getCsrfToken();
                    
                    if (newToken) {
                        console.log('✅ New CSRF token obtained');
                        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
                        originalRequest.headers['X-CSRF-TOKEN'] = newToken;
                        processQueue(null, newToken);
                        isRefreshing = false;
                        
                        console.log('🔄 Retrying original request...');
                        return axios(originalRequest);
                    } else {
                        throw new Error('No CSRF token found after refresh');
                    }
                } catch (refreshError) {
                    console.error('❌ CSRF token refresh failed:', refreshError);
                    processQueue(refreshError, null);
                    isRefreshing = false;
                    
                    alert('Your session has expired. Redirecting to login...');
                    window.location.href = '/login';
                    return Promise.reject(refreshError);
                }
            } else {
                console.error('❌ Already retried once, giving up');
            }
        }

        return Promise.reject(error);
    }
);

console.log('🚀 Axios CSRF interceptors initialized');