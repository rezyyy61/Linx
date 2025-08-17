import axios, { AxiosError, AxiosRequestConfig } from 'axios'

export const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
})

api.defaults.xsrfCookieName = 'XSRF-TOKEN'
api.defaults.xsrfHeaderName = 'X-XSRF-TOKEN'

let csrfFetched = false
export async function ensureCsrfCookie(): Promise<void> {
    if (csrfFetched) return
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
    csrfFetched = true
}

api.interceptors.response.use(
    (res) => res,
    async (error: AxiosError) => {
        const status = error.response?.status
        const cfg = (error.config || {}) as AxiosRequestConfig & { _retry?: boolean }
        if ((status === 419 || status === 401) && !cfg._retry) {
            try {
                await ensureCsrfCookie()
                cfg._retry = true
                return api.request(cfg)
            } catch {
                // fallthrough
            }
        }
        return Promise.reject(error)
    }
)
