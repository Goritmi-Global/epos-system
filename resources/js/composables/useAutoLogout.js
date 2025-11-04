// Create: resources/js/composables/useAutoLogout.js

import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export function useAutoLogout() {
    let intervalId = null;

    const checkAutoLogout = async () => {
    try {
        const response = await fetch('/check-auto-logout', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        });

        console.log('Response status:', response.status);
        console.log('Content-Type:', response.headers.get('content-type'));

        // ✅ If we get HTML, it means we're being redirected to login
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('text/html')) {
            console.warn('Received HTML response - likely session expired');
            if (intervalId) {
                clearInterval(intervalId);
            }
            router.visit('/login', {
                method: 'get',
                replace: true,
            });
            return;
        }

        // Handle 401 Unauthorized
        if (response.status === 401) {
            if (intervalId) {
                clearInterval(intervalId);
            }
            router.visit('/login', {
                method: 'get',
                replace: true,
            });
            return;
        }

        // Check if response is JSON
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.warn('Non-JSON response received:', text.substring(0, 200));
            // Don't redirect here, just stop checking
            if (intervalId) {
                clearInterval(intervalId);
            }
            return;
        }

        const data = await response.json();
        console.log('Auto-logout check:', data);

        if (data.should_logout) {
            if (intervalId) {
                clearInterval(intervalId);
            }
            router.visit('/login', {
                method: 'get',
                replace: true,
            });
        }
    } catch (error) {
        console.error('Auto-logout check failed:', error);
    }
};

    onMounted(() => {
        // Check immediately on mount
        checkAutoLogout();

        // Then check every 10 seconds
        intervalId = setInterval(checkAutoLogout, 10000);
    });

    onUnmounted(() => {
        // Clean up interval when component unmounts
        if (intervalId) {
            clearInterval(intervalId);
        }
    });

    return {
        checkAutoLogout
    };
}