<script setup lang="ts">
import { reactive, ref, computed, onMounted, nextTick } from "vue"
import { useRouter, useRoute, RouterLink } from "vue-router"
import { useAuthStore } from "@/stores/auth/auth"
import type { AxiosError } from "axios"
import { Icon } from "@iconify/vue"

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

// ---------------- UI State ----------------
const emailRef = ref<HTMLInputElement | null>(null)
const loading = ref(false)
const showPassword = ref(false)
const capsLockOn = ref(false)
const errors = ref<Record<string, string[]>>({})
const touched = reactive({ email: false, password: false })

// Pre-fill alert when redirected after register
const alertMsg = ref<string | null>(
    route.query.registered ? "Account created. Please check your email." : null
)

// ---------------- Form Model ----------------
const form = reactive({ email: "", password: "", remember: true })

// ---------------- Validation ----------------
const emailRegex = /^(?:[a-zA-Z0-9_'^&+\-])+(?:\.(?:[a-zA-Z0-9_'^&+\-])+)*@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/
const emailValid = computed(() => emailRegex.test(form.email.trim()))
const passwordValid = computed(() => form.password.trim().length >= 6)
const formValid = computed(() => emailValid.value && passwordValid.value)

function clientErrors() {
  const out: Record<string, string[]> = {}
  if (touched.email && !emailValid.value) out.email = ["Please enter a valid email address."]
  if (touched.password && !passwordValid.value)
    out.password = ["Password must be at least 6 characters."]
  return out
}

function setErrorsFrom422(e: AxiosError<any>) {
  const data = e.response?.data as any
  errors.value = (data?.errors ?? {}) as Record<string, string[]>
  alertMsg.value = data?.message || "Validation error"
}

onMounted(() => {
  // Autofocus email for quicker sign-in
  nextTick(() => emailRef.value?.focus())
})

async function onSubmit() {
  touched.email = true
  touched.password = true
  errors.value = {}

  if (!formValid.value) {
    // Force showing client-side errors
    errors.value = clientErrors()
    return
  }

  loading.value = true
  alertMsg.value = null
  try {
    const res = await auth.login({ email: form.email.trim(), password: form.password })
    if (res.ok) {
      const redirect = (route.query.redirect as string) || "/"
      router.replace(redirect)
      return
    }
  } catch (e: any) {
    if (e?.response?.status === 422) setErrorsFrom422(e)
    else if (e?.response?.status === 403 && e?.response?.data?.code === "email_unverified")
      alertMsg.value = "Email not verified. We sent you another verification link."
    else alertMsg.value = e?.response?.data?.message || e?.message || "Login failed"
  } finally {
    loading.value = false
  }
}

function oauth(provider: "google" | "facebook") {
  const redirect = (route.query.redirect as string) || "/dashboard"
  const url = `/api/oauth/redirect/${provider}?redirect=${encodeURIComponent(redirect)}`
  window.location.href = url
}

// CapsLock detector
function onKeyEvent(e: KeyboardEvent) {
  if (typeof e.getModifierState === "function") {
    capsLockOn.value = e.getModifierState("CapsLock")
  }
}

// Password strength meter (for UX feedback only)
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
const pwdLabel = computed(() => ["Very weak", "Weak", "Fair", "Good", "Strong"][pwdScore.value])
</script>

<template>
  <section class="min-h-screen w-full bg-surface relative overflow-hidden">
    <!-- Decorative blobs -->
    <div
      aria-hidden="true"
      class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-gradient-to-br from-brand to-pink-400 opacity-30 blur-3xl dark:opacity-20"
    />
    <div
      aria-hidden="true"
      class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-gradient-to-tr from-orange-400 to-amber-500 opacity-30 blur-3xl dark:opacity-20"
    />

    <div class="mx-auto grid min-h-screen w-full max-w-6xl grid-cols-1 items-stretch gap-8 px-4 py-10 md:grid-cols-2 md:py-16 lg:gap-12">
      <!-- Left: brand / hero -->
      <div class="relative hidden md:flex">
        <div class="m-auto">
          <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-gray-700 shadow ring-1 ring-gray-200 backdrop-blur dark:bg-zinc-700/70 dark:text-zinc-200 dark:ring-zinc-800">
            <span class="inline-flex h-2 w-2 rounded-full bg-brand" />
            Secure by Linxx
          </div>
          <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
            Welcome back 👋
          </h1>
          <p class="mt-3 max-w-md text-base leading-relaxed text-gray-600 dark:text-zinc-400">
            Sign in to access your dashboard, manage projects, and sync across devices. Fast, secure, and delightful.
          </p>

          <ul class="mt-6 grid max-w-md gap-3 text-sm text-gray-700 dark:text-zinc-300">
            <li class="flex items-start gap-2">
              <Icon
                icon="mdi:check-decagram"
                class="mt-0.5 h-5 w-5 text-brand"
              /> Passwordless-ready
            </li>
            <li class="flex items-start gap-2">
              <Icon
                icon="mdi:check-decagram"
                class="mt-0.5 h-5 w-5 text-brand"
              /> OAuth with Google & Facebook
            </li>
            <li class="flex items-start gap-2">
              <Icon
                icon="mdi:check-decagram"
                class="mt-0.5 h-5 w-5 text-brand"
              /> Dark mode perfected
            </li>
          </ul>

          <div class="mt-8 hidden text-xs text-gray-500 dark:text-zinc-500 md:block">
            By continuing you agree to our
            <RouterLink
              to="/legal/terms"
              class="text-brand underline-offset-2 hover:underline"
            >
              Terms
            </RouterLink>
            and
            <RouterLink
              to="/legal/privacy"
              class="text-brand underline-offset-2 hover:underline"
            >
              Privacy Policy
            </RouterLink>.
          </div>
        </div>
      </div>

      <!-- Right: form card -->
      <div class="flex items-center">
        <div
          class="relative w-full overflow-hidden rounded-2xl border border-gray-200/70 bg-white/70 p-6 shadow-2xl backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:border-zinc-800/80 dark:bg-zinc-900/60"
        >
          <!-- Top gradient bar -->
          <div
            aria-hidden="true"
            class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand via-pink-500 to-orange-400"
          />

          <div class="text-center">
            <Icon
              icon="mdi:shield-lock"
              class="mx-auto h-10 w-10 text-brand"
              aria-hidden="true"
            />
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
              Sign in
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
              Use your email and password or continue with a provider.
            </p>
          </div>

          <!-- OAuth buttons -->
          <div class="mt-6 grid gap-3">
            <button
              class="oauth-btn oauth-google group"
              :disabled="loading"
              @click="oauth('google')"
            >
              <Icon
                icon="logos:google-icon"
                class="h-5 w-5"
                aria-hidden="true"
              />
              <span class="transition group-hover:translate-x-0.5 motion-safe:transform">Continue with Google</span>
            </button>
            <button
              class="oauth-btn oauth-facebook group"
              :disabled="loading"
              @click="oauth('facebook')"
            >
              <Icon
                icon="logos:facebook"
                class="h-5 w-5"
                aria-hidden="true"
              />
              <span class="transition group-hover:translate-x-0.5 motion-safe:transform">Continue with Facebook</span>
            </button>
          </div>

          <div class="mt-6 flex items-center gap-4">
            <div class="h-px w-full bg-gray-200 dark:bg-zinc-800" />
            <div class="text-xs uppercase tracking-wider text-gray-500 dark:text-zinc-500">
              or
            </div>
            <div class="h-px w-full bg-gray-200 dark:bg-zinc-800" />
          </div>

          <Transition name="fade">
            <div
              v-if="alertMsg"
              class="mt-4 rounded-md bg-blue-50 px-3 py-2 text-sm text-blue-800 ring-1 ring-inset ring-blue-200 dark:bg-blue-950/40 dark:text-blue-200 dark:ring-blue-900"
              role="status"
              aria-live="polite"
            >
              {{ alertMsg }}
            </div>
          </Transition>

          <!-- Error summary (a11y) -->
          <Transition name="fade">
            <div
              v-if="Object.keys(errors).length"
              class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-800 ring-1 ring-inset ring-red-200 dark:bg-red-950/30 dark:text-red-200 dark:ring-red-900"
              role="alert"
              aria-live="assertive"
            >
              <p class="font-medium">
                Please fix the following:
              </p>
              <ul class="mt-1 list-disc pl-5">
                <li
                  v-for="(msgs, key) in errors"
                  :key="key"
                >
                  {{ msgs[0] }}
                </li>
              </ul>
            </div>
          </Transition>

          <form
            class="mt-6 grid gap-5"
            novalidate
            :aria-busy="loading"
            @submit.prevent="onSubmit"
          >
            <!-- Email (floating label) -->
            <div class="grid gap-1.5">
              <div class="group relative">
                <Icon
                  icon="mdi:email-outline"
                  class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400 group-focus-within:text-brand"
                  aria-hidden="true"
                />
                <input
                  id="email"
                  ref="emailRef"
                  v-model.trim="form.email"
                  type="email"
                  inputmode="email"
                  autocomplete="username"
                  placeholder=" "
                  class="input peer pl-10"
                  :aria-invalid="touched.email && !emailValid"
                  :aria-describedby="touched.email && !emailValid ? 'email-help' : undefined"
                  :disabled="loading"
                  @blur="touched.email = true"
                >
                <label
                  for="email"
                  class="float-label"
                >Email</label>
              </div>
              <p
                v-if="touched.email && !emailValid"
                id="email-help"
                class="error"
              >
                Please enter a valid email address.
              </p>
              <p
                v-else-if="errors.email"
                class="error"
              >
                {{ errors.email[0] }}
              </p>
            </div>

            <!-- Password (floating label) -->
            <div class="grid gap-1.5">
              <div
                class="group relative"
                @keyup="onKeyEvent"
                @keydown="onKeyEvent"
              >
                <Icon
                  icon="mdi:lock-outline"
                  class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400 group-focus-within:text-brand"
                  aria-hidden="true"
                />
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  placeholder=" "
                  class="input peer pl-10 pr-12"
                  :aria-invalid="touched.password && !passwordValid"
                  :disabled="loading"
                  @blur="touched.password = true"
                >
                <label
                  for="password"
                  class="float-label"
                >Password</label>
                <button
                  type="button"
                  :aria-label="showPassword ? 'Hide password' : 'Show password'"
                  :aria-pressed="showPassword"
                  class="abs-eye"
                  @click="showPassword = !showPassword"
                >
                  <Icon
                    :icon="showPassword ? 'mdi:eye-off-outline' : 'mdi:eye-outline'"
                    class="h-5 w-5"
                  />
                </button>
              </div>
              <div class="flex items-center justify-between">
                <p
                  v-if="touched.password && !passwordValid"
                  class="error"
                >
                  Password must be at least 6 characters.
                </p>
                <p
                  v-else-if="errors.password"
                  class="error"
                >
                  {{ errors.password[0] }}
                </p>
                <p
                  v-else-if="form.password"
                  class="text-xs text-gray-500 dark:text-zinc-400"
                >
                  Strength: <span :class="['font-medium', pwdScore >= 3 ? 'text-emerald-600 dark:text-emerald-400' : pwdScore === 2 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400']">{{ pwdLabel }}</span>
                </p>
                <p
                  v-if="capsLockOn"
                  class="text-xs font-medium text-amber-700 dark:text-amber-400"
                >
                  Caps Lock is ON
                </p>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <!-- Custom switch -->
              <button
                type="button"
                role="switch"
                :aria-checked="form.remember"
                class="inline-flex select-none items-center gap-2 text-sm text-gray-700 dark:text-zinc-300"
                @click="form.remember = !form.remember"
              >
                <span :class="['relative inline-flex h-5 w-9 items-center rounded-full transition', form.remember ? 'bg-brand/90' : 'bg-gray-300 dark:bg-zinc-700']">
                  <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition', form.remember ? 'translate-x-5' : 'translate-x-1']" />
                </span>
                <span>Remember me</span>
              </button>

              <RouterLink
                to="/auth/forgot-password"
                class="text-sm text-brand underline-offset-2 hover:underline"
              >
                Forgot password?
              </RouterLink>
            </div>

            <button
              type="submit"
              class="btn-primary w-full group"
              :disabled="loading"
            >
              <span
                v-if="!loading"
                class="inline-flex items-center gap-2"
              >
                <span>Sign in</span>
                <Icon
                  icon="mdi:arrow-right"
                  class="h-5 w-5 transition group-hover:translate-x-0.5 motion-safe:transform"
                />
              </span>
              <span
                v-else
                class="inline-flex items-center gap-2"
              >
                <svg
                  class="h-4 w-4 animate-spin"
                  viewBox="0 0 24 24"
                  fill="none"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a 8 8 0 018-8v4a4 4 0 00-4 4H4z"
                  />
                </svg>
                Signing in…
              </span>
            </button>

            <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
              Don’t have an account?
              <RouterLink
                to="/auth/register"
                class="text-brand underline-offset-2 hover:underline"
              >
                Create one
              </RouterLink>
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
  @apply block w-full rounded-xl border border-gray-300/90 bg-white/90 px-3 py-3 text-sm text-gray-900 shadow-sm placeholder:text-transparent transition focus:outline-none focus:ring-2 focus:ring-[color:rgb(var(--brand))] focus:border-[color:rgb(var(--brand))] disabled:opacity-60 disabled:cursor-not-allowed dark:border-zinc-700 dark:bg-zinc-900/90 dark:text-zinc-100;
}
.float-label {
  @apply pointer-events-none absolute left-10 top-1/2 -translate-y-1/2 origin-left bg-transparent px-1 text-sm text-gray-500 transition-all duration-150 ease-out peer-placeholder-shown:translate-y-0 peer-placeholder-shown:scale-100 peer-focus:-translate-y-4 peer-focus:scale-90 peer-focus:text-[color:rgb(var(--brand))] dark:text-zinc-400;
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
