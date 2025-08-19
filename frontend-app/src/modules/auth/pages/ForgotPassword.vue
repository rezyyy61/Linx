<script setup lang="ts">
import { reactive, ref, computed, onMounted, nextTick } from "vue"
import { useRouter, useRoute, RouterLink } from "vue-router"
import { useAuthStore } from "@/stores/auth/auth"
import type { AxiosError } from "axios"
import { Icon } from "@iconify/vue"

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const emailRef = ref<HTMLInputElement | null>(null)
const loading = ref(false)
const errors = ref<Record<string, string[]>>({})
const touched = reactive({ email: false })
const alertMsg = ref<string | null>(null)
const form = reactive({ email: "" })

const emailRegex = /^(?:[a-zA-Z0-9_'^&+\-])+(?:\.(?:[a-zA-Z0-9_'^&+\-])+)*@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/
const emailValid = computed(() => emailRegex.test(form.email.trim()))

function clientErrors() {
  const out: Record<string, string[]> = {}
  if (touched.email && !emailValid.value) out.email = ["Please enter a valid email address."]
  return out
}

function setErrorsFrom422(e: AxiosError<any>) {
  const data = e.response?.data as any
  errors.value = (data?.errors ?? {}) as Record<string, string[]>
  alertMsg.value = data?.message || "Validation error"
}

onMounted(() => {
  const q = (route.query.email as string) || ""
  form.email = q
  nextTick(() => emailRef.value?.focus())
})

async function onSubmit() {
  touched.email = true
  errors.value = {}
  alertMsg.value = null
  if (!emailValid.value) {
    errors.value = clientErrors()
    return
  }
  loading.value = true
  try {
    const ok = await auth.forgotPassword(form.email.trim())
    if (ok) alertMsg.value = "If that account exists, we emailed a reset link."
  } catch (e: any) {
    if (e?.response?.status === 422) setErrorsFrom422(e)
    else alertMsg.value = e?.response?.data?.message || e?.message || "Failed to send reset link"
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
            Forgot your password?
          </h1>
          <p class="mt-3 max-w-md text-base leading-relaxed text-gray-600 dark:text-zinc-400">
            Enter your email and we’ll send you a link to reset your password.
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
              icon="mdi:email-fast-outline"
              class="mx-auto h-10 w-10 text-brand"
              aria-hidden="true"
            />
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
              Reset password
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
              We’ll email you a secure link.
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
                  autocomplete="email"
                  placeholder=""
                  class="input pl-10"
                  :aria-invalid="touched.email && !emailValid"
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

            <button
              type="submit"
              class="btn-primary w-full group"
              :disabled="loading"
            >
              <span
                v-if="!loading"
                class="inline-flex items-center gap-2"
              >
                <span>Send reset link</span>
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
                Sending…
              </span>
            </button>

            <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
              Remembered your password?
              <RouterLink
                to="/auth/login"
                class="text-brand underline-offset-2 hover:underline"
              >
                Back to sign in
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
</style>
