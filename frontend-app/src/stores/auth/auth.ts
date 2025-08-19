import { defineStore } from 'pinia'
import { api, ensureCsrfCookie } from '@/lib/http'

export type User = {
    id: number
    name: string
    email: string
    email_verified_at?: string | null
}

type LoginPayload = { email: string; password: string; remember?: boolean }
type RegisterPayload = { name: string; email: string; password: string; password_confirmation?: string }
type ResetPayload = { email: string; token: string; password: string; password_confirmation: string }

function hasSessionCookie(): boolean {
    if (typeof document === 'undefined') return false
    return document.cookie.split('; ').some(c => c.startsWith('laravel_session='))
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null as User | null,
        loading: false,
        bootstrapDone: false,
    }),
    getters: {
        isAuthenticated: s => !!s.user,
        isVerified: s => !!s.user?.email_verified_at,
    },
    actions: {
        async bootstrap() {
            if (!hasSessionCookie()) {
                this.user = null
                this.bootstrapDone = true
                return
            }
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
                const res = await api.get('/me')
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
                await api.post('/login', {
                    email: payload.email,
                    password: payload.password,
                    remember: !!payload.remember,
                })
                await this.me(false)
                return { ok: true as const }
            } catch (e: any) {
                if (e?.response?.status === 403 && e?.response?.data?.code === 'email_unverified') {
                    return { ok: false as const, reason: 'email_unverified' as const }
                }
                const msg = e?.response?.data?.message || 'Login failed'
                throw new Error(msg)
            }
        },

        async register(payload: RegisterPayload) {
            await ensureCsrfCookie()
            const res = await api.post('/register', payload)
            await this.me(false)
            return res.data
        },

        async resendVerification() {
            await ensureCsrfCookie()
            const res = await api.post('/email/verification-notification')
            return res.status === 202 || res.status === 200
        },

        async forgotPassword(email: string) {
            await ensureCsrfCookie()
            const res = await api.post('/password/forgot', { email })
            if (res.status === 202 || res.data?.message === 'reset_link_sent') return true
            throw new Error(res.data?.message || 'Could not send reset link')
        },

        async resetPassword(payload: ResetPayload) {
            await ensureCsrfCookie()
            const res = await api.post('/password/reset', payload)
            if (res.status === 200 || res.data?.message === 'password_reset') return true
            throw new Error(res.data?.message || 'Password reset failed')
        },

        async logout() {
            await ensureCsrfCookie()
            await api.post('/logout')
            this.user = null
            this.bootstrapDone = true
        },
    },
})
