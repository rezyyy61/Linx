import { defineStore } from "pinia";
import { api, ensureCsrfCookie } from "@/lib/http";

export type User = {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string | null;
};

type LoginPayload = { email: string; password: string; remember?: boolean };
type RegisterPayload = { name: string; email: string; password: string; password_confirmation?: string };
type ResetPayload = { email: string; token: string; password: string; password_confirmation: string };

function normalizeEmail(v: string) {
  return v.trim().toLowerCase();
}
function trimText(v: string) {
  return v.trim();
}

export const useAuthStore = defineStore("auth", {
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
        this.loading = true;
        await this.me(true);
      } finally {
        this.loading = false;
        this.bootstrapDone = true;
      }
    },

    async me(silent = true) {
      try {
        const res = await api.get("auth/me");
        this.user = res.data.user ?? res.data;
      } catch (e) {
        if (!silent) throw e;
        this.user = null;
      }
      return this.user;
    },

    async login(payload: LoginPayload) {
      await ensureCsrfCookie();
      // eslint-disable-next-line no-useless-catch
      try {
        await api.post("auth/login", {
          email: normalizeEmail(payload.email),
          password: payload.password,
          remember: !!payload.remember,
        });
        await this.me(false);
        return { ok: true as const };
      } catch (e) {
        throw e;
      }
    },

    async register(payload: RegisterPayload) {
      await ensureCsrfCookie();
      const res = await api.post("auth/register", {
        name: trimText(payload.name),
        email: normalizeEmail(payload.email),
        password: payload.password,
        password_confirmation: payload.password_confirmation,
      });
      if (res.data?.message !== "registered" || !res.data?.user) {
        const err: any = new Error(res.data?.message || "register_failed");
        err.response = { status: res.status, data: res.data };
        throw err;
      }
      return res.data;
    },

    async resendVerification(email?: string) {
      await ensureCsrfCookie().catch(() => {})
      if (this.user) {
        const res = await api.post('auth/email/verification-notification')
        return res.status === 200 || res.status === 202
      }
      if (!email) throw new Error('email_required_for_public_resend')
      const res = await api.post('auth/resend-verification', { email: email.trim().toLowerCase() })
      return res.status === 200 || (res.data as any)?.ok === true
    },

    async forgotPassword(email: string) {
      await ensureCsrfCookie();
      const res = await api.post("auth/password/forgot", { email: normalizeEmail(email) });
      if (res.status === 202 || res.data?.message === "reset_link_sent") return true;
      throw new Error(res.data?.message || "Could not send reset link");
    },

    async resetPassword(payload: ResetPayload) {
      await ensureCsrfCookie();
      const res = await api.post("auth/password/reset", {
        email: normalizeEmail(payload.email),
        token: payload.token,
        password: payload.password,
        password_confirmation: payload.password_confirmation,
      });
      if (res.status === 200 || res.data?.message === "password_reset") return true;
      throw new Error(res.data?.message || "Password reset failed");
    },

    async logout() {
      await ensureCsrfCookie();
      await api.post("auth/logout");
      this.user = null;
      this.bootstrapDone = true;
    },
  },
});
