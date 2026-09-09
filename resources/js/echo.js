import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// 👇 Only initialize if token exists
const token = localStorage.getItem('token');

if (token && typeof window !== 'undefined') {
    try {
        window.Echo = new Echo({
            broadcaster: "pusher",
            key: import.meta.env.VITE_PUSHER_APP_KEY || 'your_pusher_key',
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
            forceTLS: true,
            wsHost: import.meta.env.VITE_PUSHER_HOST || 'ws-mt1.pusher.com',
            wsPort: 443,
            wssPort: 443,
            enabledTransports: ["ws", "wss"],
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    Authorization: `Bearer ${token}`
                }
            }
        });
    } catch (error) {
        console.warn('Echo initialization failed:', error);
    }
}

export default window.Echo;