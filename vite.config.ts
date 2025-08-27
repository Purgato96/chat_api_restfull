import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

const VITE_PORT = Number(process.env.VITE_PORT) || 5173
const HMR_HOST = process.env.VITE_HMR_HOST || 'localhost'

export default defineConfig({
    server: {
        host: true,
        port: VITE_PORT,
        strictPort: true,
        hmr: { host: HMR_HOST, port: VITE_PORT, protocol: 'ws' },
        proxy: {
            '/sanctum': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
                secure: false,
                cookieDomainRewrite: 'localhost',
            },
            '/api': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
                secure: false,
                cookieDomainRewrite: 'localhost',
            },
        },
    },
    plugins: [
        laravel({ input: ['resources/js/app.ts'], refresh: true }),
        tailwindcss(),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
    ],
    resolve: { alias: { '@': path.resolve(__dirname, './resources/js') } },
})
