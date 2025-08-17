import { defineStore } from 'pinia'
import { api, ensureCsrfCookie } from '@/lib/http'

export type User = {
    id: number
    name: string
    email: string
    email_verified_at?: string | null
}

type LoginPayload = { email: string; password: string }
type RegisterPayload = {
    name: string
    email: string
    password: string
    password_confirmation?: string
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        loading: false,
        bootstrapDone: false,
    }),
    getters: {
        isAuthenticated: (s) => !!s.user,
        isVerified: (s) => !!s.user?.email_verified_at,
    },
    actions: {
        async bootstrap() {
            try {
                this.loading = true
                await this.me(true)
            } finally {
                this.loading = false
                this.bootstrapDone = true
            }
        },

        async me(silent = true) {
            try {
                const res = await api.get('/auth/me') // GET -> نیازی به ensureCsrfCookie نیست
                this.user = res.data.user ?? res.data
            } catch (e) {
                if (!silent) throw e
                this.user = null
            }
            return this.user
        },

        async login(payload: LoginPayload) {
            await ensureCsrfCookie()
            try {
                await api.post('/auth/login', payload)
                await this.me(false)
                return { ok: true }
            } catch (e: any) {
                // هندل ایمیل تایید نشده
                if (e?.response?.status === 403 && e?.response?.data?.code === 'email_unverified') {
                    return { ok: false, reason: 'email_unverified' as const }
                }
                const msg = e?.response?.data?.message || 'Login failed'
                throw new Error(msg)
            }
        },

        async register(payload: RegisterPayload) {
            await ensureCsrfCookie()
            const res = await api.post('/auth/register', payload)
            await this.me(false)
            return res.data
        },

        async resendVerification() {
            await ensureCsrfCookie()
            await api.post('/auth/email/verification-notification')
            return true
        },

        async logout() {
            await ensureCsrfCookie()
            await api.post('/auth/logout')
            this.user = null
            this.bootstrapDone = true
        },
    },
})
