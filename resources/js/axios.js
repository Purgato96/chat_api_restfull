import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// CSRF once
let sanctumReady = false
export async function ensureSanctum() {
    if (sanctumReady) return
    await axios.get('/sanctum/csrf-cookie')
    sanctumReady = true
}

// Retry on 401/419
axios.interceptors.response.use(
    res => res,
    async err => {
        const status = err?.response?.status
        if ((status === 419 || status === 401) && !err.config.__sanctumRetried) {
            try {
                await ensureSanctum()
                err.config.__sanctumRetried = true
                return axios(err.config)
            } catch {}
        }
        return Promise.reject(err)
    }
)

export default axios
