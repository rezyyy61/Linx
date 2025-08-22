<script setup lang="ts">
defineOptions({ name: 'ResetPasswordPage' })

import { computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { Icon } from '@iconify/vue'
import AuthLayout from '../components/AuthLayout.vue'
import UiPasswordField from '../components/ui/UiPasswordField.vue'
import UiFormAlert from '../components/ui/UiFormAlert.vue'
import { useForm } from '../lib/forms/useForm'
import { required, minLen, match } from '../lib/forms/validators'
import { useAuthStore } from '@/stores/auth/auth'
import { handleApiError } from '../lib/notify/handleApiError'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const token = String(route.query.token || '')
const email = String(route.query.email || '')

const rtlLocales = ['fa','ar','ckb','ku','kur','ps','ur','he','dv','syr']
const isRTL = computed(() => rtlLocales.some(c => locale.value.toLowerCase().startsWith(c)))

const invalidLinkMsg = computed(() => (!token || !email) ? t('auth.reset.invalidLink') : null)

const { values, touched, errors, valid, validateField, validateAll, setTouchedAll, setServerErrors } =
  useForm({ password: '', password_confirmation: '' }, {
    password: [ required(t('auth.reset.errors.passwordRequired')), minLen(8, t('auth.reset.errors.passwordMin')) ],
    password_confirmation: [ required(t('auth.reset.errors.confirmRequired')), match('password', t('auth.reset.errors.passwordMismatch')) ]
  })

const loading = computed(() => auth.loading)

async function onSubmit() {
  setTouchedAll()
  if (!token || !email) return
  validateAll()
  if (!valid.value) return
  try {
    const ok = await auth.resetPassword({
      email,
      token,
      password: values.password,
      password_confirmation: values.password_confirmation
    })
    if (ok) router.replace({ path: '/auth/login', query: { reset: '1' } })
  } catch (e) {
    handleApiError(e, { setFieldErrors: (errs) => setServerErrors(errs), t })
  }
}
</script>

<template>
  <AuthLayout>
    <template #form>
      <div class="text-center">
        <Icon
          icon="mdi:lock-reset"
          class="mx-auto h-10 w-10 text-[rgb(var(--color-brand))]"
          aria-hidden="true"
        />
        <h2
          class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ $t('auth.reset.title') }}
        </h2>
        <p
          class="mt-1 text-sm text-gray-600 dark:text-zinc-400"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ $t('auth.reset.subtitle') }}
        </p>
      </div>

      <UiFormAlert
        :message="invalidLinkMsg"
        tone="warning"
      >
        <template #icon>
          <Icon
            icon="mdi:information-outline"
            class="h-5 w-5 shrink-0"
          />
        </template>
      </UiFormAlert>

      <form
        class="mt-6 grid gap-5"
        novalidate
        @submit.prevent="onSubmit"
      >
        <UiPasswordField
          id="password"
          v-model="values.password"
          :label="$t('auth.reset.fields.password')"
          :error="touched.password && errors.password ? errors.password[0] : ''"
          :locale="locale"
          left-icon="mdi:lock-outline"
          autocomplete="new-password"
          @blur="touched.password = true; validateField('password')"
        />

        <UiPasswordField
          id="password_confirmation"
          v-model="values.password_confirmation"
          :label="$t('auth.reset.fields.confirm')"
          :error="touched.password_confirmation && errors.password_confirmation ? errors.password_confirmation[0] : ''"
          :locale="locale"
          left-icon="mdi:lock-check-outline"
          autocomplete="new-password"
          @blur="touched.password_confirmation = true; validateField('password_confirmation')"
        />

        <button
          type="submit"
          :disabled="loading"
          :aria-busy="loading || undefined"
          class="inline-flex h-11 w-full select-none items-center justify-center rounded-xl
                 px-4 text-sm font-medium text-white
                 shadow-sm ring-1 ring-indigo-600/20
                 bg-gradient-to-r from-indigo-600 to-violet-600
                 hover:from-indigo-600/90 hover:to-violet-600/90
                 dark:from-indigo-500 dark:to-violet-500
                 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500
                 focus-visible:ring-offset-2 focus-visible:ring-offset-white
                 dark:focus-visible:ring-offset-zinc-900
                 active:scale-[0.99] disabled:opacity-60 disabled:cursor-not-allowed"
        >
          <span class="inline-flex items-center gap-2">
            <span class="inline-flex h-4 w-4 items-center justify-center">
              <svg
                v-if="loading"
                class="h-4 w-4 animate-spin"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                  fill="none"
                  opacity=".25"
                />
                <path
                  d="M22 12a10 10 0 0 1-10 10"
                  fill="currentColor"
                />
              </svg>
            </span>
            <span>{{ $t('auth.reset.actions.submit') }}</span>
          </span>
        </button>

        <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
          {{ $t('auth.reset.linkHelp') }}
          <RouterLink
            to="/auth/forgot-password"
            class="text-[rgb(var(--color-brand))] hover:opacity-90"
          >
            {{ $t('auth.reset.actions.requestNew') }}
          </RouterLink>
        </p>
      </form>
    </template>
  </AuthLayout>
</template>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity 150ms ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
