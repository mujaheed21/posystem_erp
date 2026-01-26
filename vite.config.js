import { defineConfig } from 'vite';
import react from '@vitejs/react-swc'; // or @vitejs/plugin-react
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        react(),
        tailwindcss(),
    ],
    server: {
        port: 3000,
        // No proxy here! We want Axios to handle the port 8000 routing directly.
    },
});