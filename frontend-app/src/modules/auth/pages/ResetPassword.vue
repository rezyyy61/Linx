<script setup lang="ts">
import { reactive, ref, computed, onMounted, nextTick } from "vue"
import { useRouter, useRoute, RouterLink } from "vue-router"
import { useAuthStore } from "@/stores/auth/auth"
import type { AxiosError } from "axios"
import { Icon } from "@iconify/vue"

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const loading = ref(false)
const showPassword = ref(false)
const showConfirm = ref(false)
const capsLockOn = ref(false)
const errors = ref<Record<string, string[]>>({})
const touched = reactive({ password: false, confirm: false })
const alertMsg = ref<string | null>(null)

const token = ref<string>((route.query.token as string) || "")
const email = ref<string>((route.query.email as string) || "")

const form = reactive({ password: "", password_confirmation: "" })

const passwordValid = computed(() => form.password.trim().length >= 8)
const confirmValid = computed(() => form.password && form.password === form.password_confirmation)
const formValid = computed(() => passwordValid.value && confirmValid.value)

function clientErrors() {
  const out: Record<string, string[]> = {}
  if (touched.password && !passwordValid.value) out.password = ["Use at least 8 characters."]
  if (touched.confirm && !confirmValid.value) out.password_confirmation = ["Passwords don't match."]
  return out
}

function setErrorsFrom422(e: AxiosError<any>) {
  const data = e.response?.data as any
  errors.value = (data?.errors ?? {}) as Record<string, string[]>
  alertMsg.value = data?.message || "Validation error"
}

function onKeyEvent(e: KeyboardEvent) {
  if (typeof e.getModifierState === "function") {
    capsLockOn.value = e.getModifierState("CapsLock")
  }
}

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

onMounted(() => {
  nextTick(() => {
    const el = document.getElementById("password") as HTMLInputElement | null
    el?.focus()
  })
})

async function onSubmit() {
  touched.password = true
  touched.confirm = true
  errors.value = {}
  alertMsg.value = null

  if (!token.value || !email.value) {
    alertMsg.value = "Reset link is invalid. Request a new link."
    return
  }
  if (!formValid.value) {
    errors.value = clientErrors()
    return
  }

  loading.value = true
  try {
    const ok = await auth.resetPassword({
      email: email.value.trim(),
      token: token.value,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    if (ok) router.replace({ path: "/auth/login", query: { reset: "1" } })
  } catch (e: any) {
    if (e?.response?.status === 422) setErrorsFrom422(e)
    else alertMsg.value = e?.response?.data?.message || e?.message || "Password reset failed"
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="min-h-screen w-full bg-surface relative overflow-hidden">
    <div
      aria-hidden="true"
      class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-gradient-to-br from-brand to-pink-400 opacity-30 blur-3xl dark:opacity-20"
    />
    <div
      aria-hidden="true"
      class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-gradient-to-tr from-orange-400 to-amber-500 opacity-30 blur-3xl dark:opacity-20"
    />

    <div class="mx-auto grid min-h-screen w-full max-w-6xl grid-cols-1 items-stretch gap-8 px-4 py-10 md:grid-cols-2 md:py-16 lg:gap-12">
      <div class="relative hidden md:flex">
        <div class="m-auto">
          <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-gray-700 shadow ring-1 ring-gray-200 backdrop-blur dark:bg-zinc-700/70 dark:text-zinc-200 dark:ring-zinc-800">
            <span class="inline-flex h-2 w-2 rounded-full bg-brand" />
            Secure by Linxx
          </div>
          <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
            Set a new password
          </h1>
          <p class="mt-3 max-w-md text-base leading-relaxed text-gray-600 dark:text-zinc-400">
            Choose a strong password and keep it safe.
          </p>
        </div>
      </div>

      <div class="flex items-center">
        <div class="relative w-full overflow-hidden rounded-2xl border border-gray-200/70 bg-white/70 p-6 shadow-2xl backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:border-zinc-800/80 dark:bg-zinc-900/60">
          <div
            aria-hidden="true"
            class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand via-pink-500 to-orange-400"
          />

          <div class="text-center">
            <Icon
              icon="mdi:lock-reset"
              class="mx-auto h-10 w-10 text-brand"
              aria-hidden="true"
            />
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
              Reset password
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
              Enter and confirm your new password.
            </p>
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
                  autocomplete="new-password"
                  placeholder=""
                  class="input pl-10 pr-12"
                  :aria-invalid="touched.password && !passwordValid"
                  :disabled="loading"
                  @blur="touched.password = true"
                >
                <label
                  for="password"
                  class="float-label"
                >New password</label>
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
                  Use at least 8 characters.
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
                  Strength:
                  <span :class="['font-medium', pwdScore >= 3 ? 'text-emerald-600 dark:text-emerald-400' : pwdScore === 2 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400']">{{ pwdLabel }}</span>
                </p>
                <p
                  v-if="capsLockOn"
                  class="text-xs font-medium text-amber-700 dark:text-amber-400"
                >
                  Caps Lock is ON
                </p>
              </div>
            </div>

            <div class="grid gap-1.5">
              <div
                class="group relative"
                @keyup="onKeyEvent"
                @keydown="onKeyEvent"
              >
                <Icon
                  icon="mdi:lock-check-outline"
                  class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400 group-focus-within:text-brand"
                  aria-hidden="true"
                />
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  :type="showConfirm ? 'text' : 'password'"
                  autocomplete="new-password"
                  placeholder=""
                  class="input pl-10 pr-12"
                  :aria-invalid="touched.confirm && !confirmValid"
                  :disabled="loading"
                  @blur="touched.confirm = true"
                >
                <label
                  for="password_confirmation"
                  class="float-label"
                >Confirm password</label>
                <button
                  type="button"
                  :aria-pressed="showConfirm"
                  class="abs-eye"
                  @click="showConfirm = !showConfirm"
                >
                  <Icon
                    :icon="showConfirm ? 'mdi:eye-off-outline' : 'mdi:eye-outline'"
                    class="h-5 w-5"
                  />
                </button>
              </div>
              <p
                v-if="touched.confirm && !confirmValid"
                class="error"
              >
                Passwords don't match.
              </p>
              <p
                v-else-if="errors.password_confirmation"
                class="error"
              >
                {{ errors.password_confirmation[0] }}
              </p>
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
                <span>Update password</span>
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
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                  />
                </svg>
                Updating…
              </span>
            </button>

            <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
              Link expired or invalid?
              <RouterLink
                to="/auth/forgot-password"
                class="text-brand underline-offset-2 hover:underline"
              >
                Request a new link
              </RouterLink>
            </p>
          </form>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
:root { --brand: 239 68 68; }
:root.dark { --brand: 239 68 68; }
.fade-enter-active, .fade-leave-active { transition: opacity 150ms ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.bg-surface { @apply bg-gradient-to-br from-white via-white to-gray-50 dark:from-zinc-950 dark:via-zinc-950 dark:to-black; }
.text-brand { color: rgb(var(--brand)); }
.bg-brand { background-color: rgb(var(--brand)); }
.input { @apply block w-full rounded-xl border border-gray-300/90 bg-white/90 px-3 py-3 text-sm text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-[color:rgb(var(--brand))] focus:border-[color:rgb(var(--brand))] disabled:opacity-60 disabled:cursor-not-allowed dark:border-zinc-700 dark:bg-zinc-900/90 dark:text-zinc-100; }
.float-label { @apply pointer-events-none absolute left-10 top-1 text-xs text-gray-500 dark:text-zinc-400; }
.btn-primary { @apply inline-flex items-center justify-center rounded-xl bg-[color:rgb(var(--brand))] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:rgb(var(--brand))] focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed dark:focus-visible:ring-offset-zinc-900; }
.error { @apply mt-1 text-xs text-red-600 dark:text-red-400; }
.abs-eye { @apply absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-9 w-9 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:rgb(var(--brand))] dark:text-zinc-400 dark:hover:bg-zinc-800; }
</style>
