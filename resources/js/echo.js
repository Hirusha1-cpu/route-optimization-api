import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// 👇 Check if token exists
const token = localStorage.getItem('token');
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

// 👇 Only initialize if we have a real key (not placeholder)
if (token && pusherKey && pusherKey !== 'your_pusher_key') {
    try {
        window.Echo = new Echo({
            broadcaster: "pusher",
            key: pusherKey,
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
        console.log('✅ Pusher connected successfully');
    } catch (error) {
        console.warn('⚠️ Pusher initialization failed:', error);
    }
} else {
    console.log('ℹ️ Pusher disabled: No valid credentials found');
}

export default window.Echo;