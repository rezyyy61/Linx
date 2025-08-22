<script setup lang="ts">
defineOptions({ name: 'LoginPage' })

import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

import AuthLayout from '../components/AuthLayout.vue'
import AuthOAuthButtons from '../components/AuthOAuthButtons.vue'
import UiTextField from '../components/ui/UiTextField.vue'
import UiPasswordField from '../components/ui/UiPasswordField.vue'
import UiFormAlert from '../components/ui/UiFormAlert.vue'
import { Icon } from '@iconify/vue'

import { useAuthStore } from '@/stores/auth/auth'
import { useForm } from '../lib/forms/useForm'
import { required, email as emailRule, minLen } from '../lib/forms/validators'
import { useNotify } from '../lib/notify/useNotify'
import { handleApiError } from '../lib/notify/handleApiError'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const notify = useNotify()

const rtlLocales = ['fa','ar','ckb','ku','kur','ps','ur','he','dv','syr']
const isRTL = computed(() => rtlLocales.some(c => locale.value.toLowerCase().startsWith(c)))

const loading = ref(false)
const resendSending = ref(false)
const showRegisteredInfo = computed(() => route.query.registered === '1')
const alertMsg = computed(() => showRegisteredInfo.value ? (t('auth.register.verifyNotice') || '') : null)

const { values, touched, errors, validateField, validateAll, setTouchedAll, setServerErrors } = useForm(
  { email: '', password: '', remember: true as boolean },
  {
    email: [ required(t('auth.login.errors.emailRequired')), emailRule(t('auth.login.errors.emailInvalid')) ],
    password: [ required(t('auth.login.errors.passwordRequired')), minLen(6, t('auth.login.errors.passwordMin')) ]
  }
)

function errOf(key: 'email'|'password') {
  return touched[key] && errors[key]?.[0] ? errors[key][0] : ''
}

async function resendVerification() {
  if (resendSending.value) return
  resendSending.value = true
  try {
    if (typeof (auth as any).resendVerification === 'function') {
      await (auth as any).resendVerification(values.email.trim())
      notify.success({
        title: t('auth.login.resentTitle'),
        description: t('auth.login.resentDesc'),
        duration: 6000
      })
      return
    }

    const res = await fetch('/api/auth/resend-verification', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify({ email: values.email.trim() })
    })

    if (res.ok) {
      notify.success({
        title: t('auth.login.resentTitle'),
        description: t('auth.login.resentDesc'),
        duration: 6000
      })
      return
    }

    if (res.status === 401) {
      notify.info({
        title: t('auth.login.unverifiedTitle'),
        description: t('auth.login.resentUnauthorized'),
        duration: 7000
      })
      return
    }

    let msg = t('auth.login.resentFail') as string
    try {
      const j = await res.json()
      if (j?.message && j.message.length < 160) msg = j.message
    } catch {
      // intentionally ignore: response body may not be JSON; keep default msg
    }
    notify.error({
      title: t('common.error'),
      description: msg,
      duration: 7000
    })
  } finally {
    resendSending.value = false
  }
}


async function onSubmit() {
  setTouchedAll()
  if (!validateAll() || loading.value) return
  loading.value = true
  try {
    const res = await auth.login({
      email: values.email.trim(),
      password: values.password,
      remember: values.remember
    })
    if ((res as any)?.ok === false) throw res
    notify.success({
      title: t('auth.login.successTitle') || t('common.success'),
      description: t('auth.login.successDesc') || ''
    })
    const redirect = (route.query.redirect as string) || '/'
    setTimeout(() => router.replace(redirect), 600)
  } catch (e: any) {
    if (e?.response?.status === 403 && e?.response?.data?.code === 'email_unverified') {
      notify.warning({
        title: t('auth.login.unverifiedTitle'),
        description: t('auth.login.unverifiedDesc'),
        duration: 12000,
        action: { label: t('auth.login.resend'), onClick: resendVerification }
      })
    } else {
      handleApiError(e, { setFieldErrors: (errs) => setServerErrors(errs), t })
    }
  } finally {
    loading.value = false
  }
}

function oauth(provider: 'google'|'facebook'|'apple'|'github') {
  if (loading.value) return
  const url = `/api/oauth/redirect/${provider}?redirect=${encodeURIComponent('/')}`
  window.location.href = url
}
</script>


<template>
  <AuthLayout>
    <!-- ستون OAuth -->
    <template #oauth>
      <h2
        class="mb-1 font-semibold text-base md:text-lg text-gray-900 dark:text-white"
        :class="isRTL ? 'text-right' : 'text-left'"
        :dir="isRTL ? 'rtl' : 'ltr'"
      >
        {{ t('auth.oauth.title') }}
      </h2>
      <p
        class="mb-4 text-gray-500 dark:text-gray-400"
        :class="isRTL ? 'text-right' : 'text-left'"
        :dir="isRTL ? 'rtl' : 'ltr'"
      >
        {{ t('auth.oauth.subtitle') }}
      </p>
      <AuthOAuthButtons
        :providers="['google','facebook','apple']"
        :disabled="loading"
        @click="oauth"
      />
    </template>

    <!-- ستون فرم -->
    <template #form>
      <div class="mb-6">
        <h2
          class="text-xl font-semibold text-gray-900 dark:text-gray-100"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ t('auth.login.title') }}
        </h2>
        <p
          class="mt-2 text-gray-500 dark:text-gray-400"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ t('auth.login.subtitle') }}
        </p>
      </div>

      <UiFormAlert
        :message="alertMsg"
        tone="info"
      />

      <form
        class="mt-4 grid gap-5"
        novalidate
        @submit.prevent="onSubmit"
      >
        <UiTextField
          id="email"
          v-model="values.email"
          :label="t('auth.login.fields.email')"
          :error="errOf('email')"
          :locale="locale"
          autocomplete="email"
          inputmode="email"
          @blur="touched.email = true; validateField('email')"
        >
          <template #icon-left>
            <Icon
              icon="mdi:email-outline"
              class="h-5 w-5"
            />
          </template>
        </UiTextField>

        <UiPasswordField
          id="password"
          v-model="values.password"
          :label="t('auth.login.fields.password')"
          :error="errOf('password')"
          :locale="locale"
          autocomplete="current-password"
          @blur="touched.password = true; validateField('password')"
        />

        <div class="flex items-center justify-between">
          <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 select-none cursor-pointer">
            <input
              v-model="values.remember"
              type="checkbox"
              class="h-4 w-4 rounded border-gray-300 dark:border-white/20 text-indigo-600 focus:ring-indigo-500 dark:bg-transparent"
            >
            <span>{{ t('auth.login.fields.remember') }}</span>
          </label>

          <RouterLink
            to="/auth/forgot-password"
            class="text-sm font-medium text-indigo-600 hover:opacity-90"
          >
            {{ t('auth.login.actions.forgot') }}
          </RouterLink>
        </div>

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
            <span>{{ t('auth.login.actions.submit') }}</span>
          </span>
        </button>

        <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
          {{ t('auth.login.noAccount') }}
          <RouterLink
            to="/auth/register"
            class="text-indigo-600 hover:opacity-90"
          >
            {{ t('auth.login.actions.register') }}
          </RouterLink>
        </p>
      </form>
    </template>
  </AuthLayout>
</template>
