<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth/auth'
import type { AxiosError } from 'axios'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const form = reactive({
  email: '',
  password: '',
})
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const alertMsg = ref<string | null>(route.query.registered ? 'Account created. Please check your email.' : null)

function setErrorsFrom422(e: AxiosError<any>) {
  const data = e.response?.data as any
  errors.value = (data?.errors ?? {}) as Record<string, string[]>
  alertMsg.value = data?.message || 'Validation error'
}

async function onSubmit() {
  loading.value = true
  errors.value = {}
  alertMsg.value = null
  try {
    const res = await auth.login({ ...form })
    if (res.ok) {
      const redirect = (route.query.redirect as string) || '/dashboard'
      router.replace(redirect)
      return
    }
  } catch (e: any) {
    if (e?.response?.status === 422) {
      setErrorsFrom422(e)
    } else if (e?.response?.status === 403 && e?.response?.data?.code === 'email_unverified') {
      alertMsg.value = 'Email not verified. We sent you another verification link.'
    } else {
      alertMsg.value = e?.response?.data?.message || e?.message || 'Login failed'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="mx-auto max-w-md">
    <h1 class="text-2xl font-bold tracking-tight">
      Sign in
    </h1>
    <p class="mt-2 text-sm text-gray-600">
      Access your dashboard with your credentials.
    </p>

    <div
      v-if="alertMsg"
      class="mt-4 rounded-md bg-blue-50 p-3 text-sm text-blue-800"
    >
      {{ alertMsg }}
    </div>

    <form
      class="mt-6 grid gap-4"
      novalidate
      @submit.prevent="onSubmit"
    >
      <div class="grid gap-1.5">
        <label
          for="email"
          class="text-sm font-medium text-gray-700"
        >Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          class="input"
          placeholder="you@example.com"
        >
        <p
          v-if="errors.email"
          class="error"
        >
          {{ errors.email[0] }}
        </p>
      </div>

      <div class="grid gap-1.5">
        <label
          for="password"
          class="text-sm font-medium text-gray-700"
        >Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          class="input"
          placeholder="••••••••"
        >
        <p
          v-if="errors.password"
          class="error"
        >
          {{ errors.password[0] }}
        </p>
      </div>

      <button
        type="submit"
        class="btn w-full"
        :disabled="loading"
      >
        <span v-if="!loading">Sign in</span>
        <span v-else>Signing in…</span>
      </button>
    </form>
  </section>
</template>

<style scoped>
.input {
  @apply block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600;
}
.btn {
  @apply inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition disabled:opacity-60 disabled:cursor-not-allowed;
}
.error { @apply mt-1 text-xs text-red-600; }
</style>
