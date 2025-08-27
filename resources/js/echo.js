// resources/js/echo.js
import axios from './axios'           // usa o MESMO axios (withCredentials + ensureSanctum)
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,

    // Pusher (cloud) — websockets públicos
    wsHost: `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: 80,
    wssPort: 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],

    // Endpoint padrão do Laravel para auth de canais privados/presença
    authEndpoint: '/broadcasting/auth',

    // ESSENCIAL: envia cookies (laravel_session, XSRF-TOKEN) na requisição de auth
    withCredentials: true,

    // Autorizer custom: garante que usamos o axios acima (com cookies e CSRF)
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios.post(options.authEndpoint || '/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                })
                    .then((response) => {
                        callback(false, response.data)
                    })
                    .catch((error) => {
                        callback(true, error)
                    })
            },
        }
    },
})
