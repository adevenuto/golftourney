import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Realtime via hosted Pusher. Echo auto-attaches the X-Socket-ID header to
// axios/Inertia requests, which lets the server broadcast with ->toOthers().
// Auth rides the existing session (POST /broadcasting/auth, web middleware).
// If the Pusher key isn't configured yet, we skip init so the app still runs.
window.Pusher = Pusher;

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
    });
}
