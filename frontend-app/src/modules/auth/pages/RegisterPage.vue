<script setup lang="ts">
import { reactive, ref, computed, onMounted, nextTick } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth/auth'
import type { AxiosError } from 'axios'
import { Icon } from '@iconify/vue'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()

// ---------------- UI State ----------------
const nameRef = ref<HTMLInputElement | null>(null)
const loading = ref(false)
const showPassword = ref(false)
const showConfirm = ref(false)
const capsLockOn = ref(false)
const errors = ref<Record<string, string[]>>({})
const touched = reactive({ name: false, email: false, password: false, confirm: false })

// ---------------- Form Model ----------------
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

// ---------------- Validation ----------------
const emailRegex = /^(?:[a-zA-Z0-9_'^&+\-])+(?:\.(?:[a-zA-Z0-9_'^&+\-])+)*@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/
const nameValid = computed(() => form.name.trim().length >= 2)
const emailValid = computed(() => emailRegex.test(form.email.trim()))
const passwordValid = computed(() => form.password.length >= 8)
const confirmValid = computed(() => !!form.password && form.password === form.password_confirmation)
const formValid = computed(() => nameValid.value && emailValid.value && passwordValid.value && confirmValid.value)

function clientErrors() {
  const out: Record<string, string[]> = {}
  if (touched.name && !nameValid.value) out.name = [t('auth.register.errors.name')]
  if (touched.email && !emailValid.value) out.email = [t('auth.register.errors.email')]
  if (touched.password && !passwordValid.value) out.password = [t('auth.register.errors.passwordLen')]
  if (touched.confirm && !confirmValid.value) out.password_confirmation = [t('auth.register.errors.passwordMismatch')]
  return out
}

function setErrorsFrom422(e: AxiosError<any>) {
  const data = e.response?.data as any
  errors.value = (data?.errors ?? {}) as Record<string, string[]>
}

onMounted(() => {
  nextTick(() => nameRef.value?.focus())
})

// CapsLock detector
function onKeyEvent(e: KeyboardEvent) {
  if (typeof e.getModifierState === 'function') {
    capsLockOn.value = !!e.getModifierState('CapsLock')
  }
}

// Password strength (UX feedback only)
const pwdScore = computed(() => {
  const v = form.password
  let s = 0
  if (v.length >= 8) s++
  if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++
  if (/\d/.test(v)) s++
  if (/[^\w\s]/.test(v)) s++
  if (v.length >= 12) s++
  return Math.min(s, 4)
})
const pwdLabel = computed(() => [
  t('auth.register.strength.veryWeak'),
  t('auth.register.strength.weak'),
  t('auth.register.strength.fair'),
  t('auth.register.strength.good'),
  t('auth.register.strength.strong'),
][pwdScore.value])

async function onSubmit() {
  touched.name = true
  touched.email = true
  touched.password = true
  touched.confirm = true
  errors.value = {}

  if (!formValid.value) {
    errors.value = clientErrors()
    return
  }

  loading.value = true
  try {
    await auth.register({
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    router.replace({ path: '/auth/login', query: { registered: '1' } })
  } catch (e: any) {
    if (e?.response?.status === 422) setErrorsFrom422(e)
  } finally {
    loading.value = false
  }
}

function oauth(provider: 'google' | 'facebook') {
  const url = `/api/oauth/redirect/${provider}?redirect=${encodeURIComponent('/')}`
  window.location.href = url
}
</script>

<template>
  <section class="min-h-screen w-full bg-surface relative overflow-hidden">
    <!-- Decorative blobs -->
    <div aria-hidden="true" class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-gradient-to-br from-brand to-pink-400 opacity-30 blur-3xl dark:opacity-20" />
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-gradient-to-tr from-orange-400 to-amber-500 opacity-30 blur-3xl dark:opacity-20" />

    <div class="mx-auto grid min-h-screen w-full max-w-6xl grid-cols-1 items-stretch gap-8 px-4 py-10 md:grid-cols-2 md:py-16 lg:gap-12">
      <!-- Left: hero/brand (match login) -->
      <div class="relative hidden md:flex">
        <div class="m-auto">
          <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-gray-700 shadow ring-1 ring-gray-200 backdrop-blur dark:bg-zinc-900/70 dark:text-zinc-200 dark:ring-zinc-800">
            <span class="inline-flex h-2 w-2 rounded-full bg-brand"></span>
            Linxx
          </div>
          <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
            {{ t('auth.register.heroTitle') }}
          </h1>
          <p class="mt-3 max-w-md text-base leading-relaxed text-gray-600 dark:text-zinc-400">
            {{ t('auth.register.subtitle') }}
          </p>

          <ul class="mt-6 grid max-w-md gap-3 text-sm text-gray-700 dark:text-zinc-300">
            <li class="flex items-start gap-2"><Icon icon="mdi:check-decagram" class="mt-0.5 h-5 w-5 text-brand"/> OAuth Google & Facebook</li>
            <li class="flex items-start gap-2"><Icon icon="mdi:check-decagram" class="mt-0.5 h-5 w-5 text-brand"/> Dark mode</li>
            <li class="flex items-start gap-2"><Icon icon="mdi:check-decagram" class="mt-0.5 h-5 w-5 text-brand"/> A11y ready</li>
          </ul>
        </div>
      </div>

      <!-- Right: form card -->
      <div class="flex items-center">
        <div class="relative w-full overflow-hidden rounded-2xl border border-gray-200/70 bg-white/70 p-6 shadow-2xl backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:border-zinc-800/80 dark:bg-zinc-900/60">
          <!-- Top gradient bar -->
          <div aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand via-pink-500 to-orange-400" />

          <div class="text-center">
            <Icon icon="mdi:account-plus" class="mx-auto h-10 w-10 text-brand" aria-hidden="true" />
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ t('auth.register.title') }}</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">{{ t('auth.register.subtitle') }}</p>
          </div>

          <!-- OAuth buttons (match login) -->
          <div class="mt-6 grid gap-3">
            <button class="oauth-btn oauth-google group" :disabled="loading" @click="oauth('google')">
              <Icon icon="logos:google-icon" class="h-5 w-5" aria-hidden="true" />
              <span class="transition group-hover:translate-x-0.5 motion-safe:transform">{{ t('auth.register.oauth.google') }}</span>
            </button>
            <button class="oauth-btn oauth-facebook group" :disabled="loading" @click="oauth('facebook')">
              <Icon icon="logos:facebook" class="h-5 w-5" aria-hidden="true" />
              <span class="transition group-hover:translate-x-0.5 motion-safe:transform">{{ t('auth.register.oauth.facebook') }}</span>
            </button>
          </div>

          <div class="mt-6 flex items-center gap-4">
            <div class="h-px w-full bg-gray-200 dark:bg-zinc-800" />
            <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-zinc-500">{{ t('auth.register.or') }}</div>
            <div class="h-px w-full bg-gray-200 dark:bg-zinc-800" />
          </div>

          <!-- Error summary (a11y) -->
          <transition name="fade">
            <div v-if="Object.keys(errors).length" class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800 ring-1 ring-inset ring-red-200 dark:bg-red-950/30 dark:text-red-200 dark:ring-red-900" role="alert" aria-live="assertive">
              <p class="font-medium">{{ t('auth.register.errors.summaryTitle') }}</p>
              <ul class="mt-1 list-disc pl-5">
                <li v-for="(msgs, key) in errors" :key="key">{{ msgs[0] }}</li>
              </ul>
            </div>
          </transition>

          <form class="mt-6 grid gap-4" novalidate @submit.prevent="onSubmit" :aria-busy="loading">
            <!-- Name -->
            <div class="grid gap-1.5">
              <label for="name" class="text-sm font-medium text-gray-700 dark:text-zinc-200">{{ t('auth.register.name.label') }}</label>
              <div class="relative">
                <Icon icon="mdi:account-outline" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input id="name" ref="nameRef" v-model.trim="form.name" type="text" autocomplete="name" class="input pl-10" placeholder=" " @blur="touched.name = true" :aria-invalid="touched.name && !nameValid" :disabled="loading" />
              </div>
              <p v-if="touched.name && !nameValid" class="error">{{ t('auth.register.errors.name') }}</p>
              <p v-else-if="errors.name" class="error">{{ errors.name[0] }}</p>
            </div>

            <!-- Email -->
            <div class="grid gap-1.5">
              <label for="email" class="text-sm font-medium text-gray-700 dark:text-zinc-200">{{ t('auth.register.email.label') }}</label>
              <div class="relative">
                <Icon icon="mdi:email-outline" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input id="email" v-model.trim="form.email" type="email" inputmode="email" autocomplete="email" class="input pl-10" placeholder=" " @blur="touched.email = true" :aria-invalid="touched.email && !emailValid" :disabled="loading" />
              </div>
              <p v-if="touched.email && !emailValid" class="error">{{ t('auth.register.errors.email') }}</p>
              <p v-else-if="errors.email" class="error">{{ errors.email[0] }}</p>
            </div>

            <!-- Password -->
            <div class="grid gap-1.5">
              <label for="password" class="text-sm font-medium text-gray-700 dark:text-zinc-200">{{ t('auth.register.password.label') }}</label>
              <div class="relative" @keyup="onKeyEvent" @keydown="onKeyEvent">
                <Icon icon="mdi:lock-outline" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" class="input pl-10 pr-12" placeholder=" " @blur="touched.password = true" :aria-invalid="touched.password && !passwordValid" :disabled="loading" />
                <button type="button" :aria-label="showPassword ? 'Hide password' : 'Show password'" :aria-pressed="showPassword" class="abs-eye" @click="showPassword = !showPassword">
                  <Icon :icon="showPassword ? 'mdi:eye-off-outline' : 'mdi:eye-outline'" class="h-5 w-5" />
                </button>
              </div>
              <div class="flex items-center justify-between">
                <p v-if="touched.password && !passwordValid" class="error">{{ t('auth.register.errors.passwordLen') }}</p>
                <p v-else-if="errors.password" class="error">{{ errors.password[0] }}</p>
                <p v-else-if="form.password" class="text-xs text-gray-500 dark:text-zinc-400">{{ t('auth.register.strength.label') }}:
                  <span :class="['font-medium', pwdScore >= 3 ? 'text-emerald-600 dark:text-emerald-400' : pwdScore === 2 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400']">{{ pwdLabel }}</span>
                </p>
                <p v-if="capsLockOn" class="text-xs font-medium text-amber-700 dark:text-amber-400">{{ t('auth.register.capsLock') }}</p>
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="grid gap-1.5">
              <label for="password_confirmation" class="text-sm font-medium text-gray-700 dark:text-zinc-200">{{ t('auth.register.confirm.label') }}</label>
              <div class="relative" @keyup="onKeyEvent" @keydown="onKeyEvent">
                <Icon icon="mdi:lock-check-outline" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input id="password_confirmation" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'" autocomplete="new-password" class="input pl-10 pr-12" placeholder=" " @blur="touched.confirm = true" :aria-invalid="touched.confirm && !confirmValid" :disabled="loading" />
                <button type="button" :aria-label="showConfirm ? 'Hide confirm password' : 'Show confirm password'" :aria-pressed="showConfirm" class="abs-eye" @click="showConfirm = !showConfirm">
                  <Icon :icon="showConfirm ? 'mdi:eye-off-outline' : 'mdi:eye-outline'" class="h-5 w-5" />
                </button>
              </div>
              <p v-if="touched.confirm && !confirmValid" class="error">{{ t('auth.register.errors.passwordMismatch') }}</p>
              <p v-else-if="errors.password_confirmation" class="error">{{ errors.password_confirmation[0] }}</p>
            </div>

            <button type="submit" class="btn-primary w-full group" :disabled="loading">
              <span v-if="!loading" class="inline-flex items-center gap-2">
                <span>{{ t('auth.register.submit') }}</span>
                <Icon icon="mdi:arrow-right" class="h-5 w-5 transition group-hover:translate-x-0.5 motion-safe:transform" />
              </span>
              <span v-else class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                {{ t('auth.register.loading') }}
              </span>
            </button>

            <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
              {{ t('auth.register.haveAccount') }}
              <RouterLink to="/auth/login" class="text-brand underline-offset-2 hover:underline">{{ t('auth.register.signInLink') }}</RouterLink>
            </p>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/***** Theme tokens *****/
:root { --brand: 239 68 68; }
:root.dark { --brand: 239 68 68; }

/***** Helpers *****/
.fade-enter-active, .fade-leave-active { transition: opacity 150ms ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/***** Layout *****/
.bg-surface { @apply bg-gradient-to-br from-white via-white to-gray-50 dark:from-zinc-950 dark:via-zinc-950 dark:to-black; }
.text-brand { color: rgb(var(--brand)); }
.bg-brand { background-color: rgb(var(--brand)); }

/***** Components *****/
.input {
  @apply block w-full rounded-xl border border-gray-300/90 bg-white/90 px-3 py-3 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 transition focus:outline-none focus:ring-2 focus:ring-[color:rgb(var(--brand))] focus:border-[color:rgb(var(--brand))] disabled:opacity-60 disabled:cursor-not-allowed dark:border-zinc-700 dark:bg-zinc-900/90 dark:text-zinc-100;
}
.btn-primary {
  @apply inline-flex items-center justify-center rounded-xl bg-[color:rgb(var(--brand))] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:rgb(var(--brand))] focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed dark:focus-visible:ring-offset-zinc-900;
}
.error { @apply mt-1 text-xs text-red-600 dark:text-red-400; }
.oauth-btn {
  @apply inline-flex w-full items-center justify-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-medium transition shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed;
}
.oauth-google {
  @apply border-gray-300 bg-white text-gray-900 hover:bg-gray-50 focus-visible:ring-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 dark:focus-visible:ring-zinc-700 dark:focus-visible:ring-offset-zinc-900;
}
.oauth-facebook {
  @apply border-transparent bg-[#1877F2] text-white hover:brightness-95 focus-visible:ring-[#1877F2] focus-visible:ring-offset-white dark:focus-visible:ring-offset-zinc-900;
}
.abs-eye {
  @apply absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:rgb(var(--brand))] dark:text-zinc-400 dark:hover:bg-zinc-800;
}
</style>
