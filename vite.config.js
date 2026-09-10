import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';

export default defineConfig({
    plugins: [
        react({
            tsDecorators: false,
        }),
    ],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
    },
});