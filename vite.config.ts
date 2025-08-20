import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import path from 'path'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

const VITE_PORT = Number(process.env.VITE_PORT) || 5173
const HMR_HOST = process.env.VITE_HMR_HOST || 'localhost' // use o MESMO host que você abre no navegador

export default defineConfig({
    server: {
        host: true,            // 0.0.0.0 dentro do container
        port: VITE_PORT,       // casa com VITE_PORT do .env
        strictPort: true,      // se a porta estiver ocupada, falha (não muda sozinho)
        hmr: {
            host: HMR_HOST,      // 'localhost' ou '127.0.0.1' — padronize com o que você usa no navegador
            port: VITE_PORT,
            protocol: 'ws',
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
})
