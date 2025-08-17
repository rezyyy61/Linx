<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth/auth'
import type { AxiosError } from 'axios'

const auth = useAuthStore()

const form = reactive({
name: '',
email: '',
password: '',
password_confirmation: '',
})

const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const alertMsg = ref<string | null>(null)

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
  await auth.register({ ...form })
  alertMsg.value = 'Account created. Please check your email to verify your address.'
  } catch (e: any) {
  if (e?.response?.status === 422) {
  setErrorsFrom422(e)
  } else {
  alertMsg.value = e?.response?.data?.message || e?.message || 'Registration failed'
  }
  } finally {
  loading.value = false
  }
  }
  </script>

<template>
  <section class="mx-auto max-w-md">
    <h1 class="text-2xl font-bold tracking-tight">
      Create an account
    </h1>
    <p class="mt-2 text-sm text-gray-600">
      Join to access your dashboard.
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
          for="name"
          class="text-sm font-medium text-gray-700"
        >Name</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          autocomplete="name"
          class="input"
          placeholder="Your name"
        >
        <p
          v-if="errors.name"
          class="error"
        >
          {{ errors.name[0] }}
        </p>
      </div>

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
          autocomplete="new-password"
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

      <div class="grid gap-1.5">
        <label
          for="password_confirmation"
          class="text-sm font-medium text-gray-700"
        >Confirm password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          class="input"
          placeholder="••••••••"
        >
        <p
          v-if="errors.password_confirmation"
          class="error"
        >
          {{ errors.password_confirmation[0] }}
        </p>
      </div>

      <button
        type="submit"
        class="btn w-full"
        :disabled="loading"
      >
        <span v-if="!loading">Create account</span>
        <span v-else>Creating…</span>
      </button>
    </form>

    <p class="mt-6 text-sm text-gray-600">
      Already have an account?
      <RouterLink
        to="/auth/login"
        class="text-blue-600 hover:underline"
      >
        Sign in
      </RouterLink>
    </p>
  </section>
</template>

<style scoped>
.input {
  @apply block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600;
}
.btn {
  @apply inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition disabled:opacity-60 disabled:cursor-not-allowed;
}
.error {
  @apply mt-1 text-xs text-red-600;
}
</style>
