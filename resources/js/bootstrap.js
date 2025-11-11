import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Force WS connection for localhost
const isLocalhost = import.meta.env.VITE_PUSHER_HOST === '127.0.0.1' || 
                    import.meta.env.VITE_PUSHER_HOST === 'localhost';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT,
    forceTLS: false, // Force HTTP for localhost
    encrypted: false, // No encryption for localhost
    disableStats: true,
    enabledTransports: isLocalhost ? ['ws'] : ['ws', 'wss'], // Only WS for localhost
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
});

console.log('✅ Echo initialized:', {
    host: import.meta.env.VITE_PUSHER_HOST,
    port: import.meta.env.VITE_PUSHER_PORT,
    forceTLS: false,
    transports: isLocalhost ? ['ws'] : ['ws', 'wss']
});