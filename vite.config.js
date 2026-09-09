import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react-swc';  // 👈 SWC plugin use කරන්න

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react({
            // 👇 Preamble fix
            tsDecorators: false,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    // 👇 Build options
    build: {
        rollupOptions: {
            input: {
                app: 'resources/js/app.jsx',
            },
        },
    },
});