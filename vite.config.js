import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.jsx'],
            refresh: true,
        }),
        react({
            // 👇 මේ config එක add කරන්න
            include: "**/*.{jsx,tsx}",
            babel: {
                plugins: ['@babel/plugin-transform-react-jsx-self', '@babel/plugin-transform-react-jsx-source'],
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        // 👇 HMR config එක add කරන්න
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
    optimizeDeps: {
        include: ['react', 'react-dom', 'react/jsx-runtime'],
    },
});