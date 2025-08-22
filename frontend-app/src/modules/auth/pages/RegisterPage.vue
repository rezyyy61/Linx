<script setup lang="ts">
defineOptions({ name: 'RegisterPage' })
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AuthLayout from '../components/AuthLayout.vue'
import AuthOAuthButtons from '../components/AuthOAuthButtons.vue'
import UiTextField from '../components/ui/UiTextField.vue'
import UiPasswordField from '../components/ui/UiPasswordField.vue'
import { Icon } from '@iconify/vue'

import { useForm } from '../lib/forms/useForm'
import { required, email as emailRule, minLen } from '../lib/forms/validators'
import { useAuthStore } from '@/stores/auth/auth'
import { handleApiError } from '../lib/notify/handleApiError'
import { useNotify } from '../lib/notify/useNotify'

const { t, locale } = useI18n()
const auth = useAuthStore()
const notify = useNotify()
const submitting = ref(false)

const rtlLocales = ['fa','ar','ckb','ku','kur','ps','ur','he','dv','syr']
const isRTL = computed(() => rtlLocales.some(c => locale.value.toLowerCase().startsWith(c)))

const confirmField = (key: string, msg: string) =>
  (value: any, all: Record<string, any>) => (value === all[key] ? undefined : msg)

const { values, touched, errors, validateField, validateAll, setTouchedAll, setServerErrors } =
  useForm({ name: '', email: '', password: '', password_confirmation: '' }, {
    name: [required(t('auth.register.errors.nameRequired')), minLen(2, t('auth.register.errors.nameMin'))],
    email: [required(t('auth.register.errors.emailRequired')), emailRule(t('auth.register.errors.emailInvalid'))],
    password: [required(t('auth.register.errors.passwordRequired')), minLen(8, t('auth.register.errors.passwordMin'))],
    password_confirmation: [
      required(t('auth.register.errors.confirmRequired')),
      confirmField('password', t('auth.register.errors.passwordMismatch')),
    ],
  })

async function onSubmit() {
  setTouchedAll()
  const ok = validateAll()
  if (!ok || submitting.value) return
  submitting.value = true
  try {
    await auth.register({
      name: values.name,
      email: values.email,
      password: values.password,
      password_confirmation: values.password_confirmation
    })

    notify.success({
      title: t('auth.register.notify.successTitle'),
      description: t('auth.register.notify.verifyNotice'),
      duration: 6000
    })

  } catch (e) {
    handleApiError(e, { setFieldErrors: (errs) => setServerErrors(errs), t })
  } finally {
    submitting.value = false
  }
}

function oauth(provider: 'google'|'facebook'|'apple'|'github') {
  if (submitting.value) return
  const url = `/api/oauth/redirect/${provider}?redirect=${encodeURIComponent('/')}`
  window.location.href = url
}
</script>



<template>
  <AuthLayout>
    <template #oauth>
      <h2
        class="mb-4 font-semibold text-base md:text-lg text-gray-900 dark:text-white"
        :class="isRTL ? 'text-right' : 'text-left'"
        :dir="isRTL ? 'rtl' : 'ltr'"
      >
        {{ t('auth.oauth.title') }}
      </h2>
      <p
        class="mt-1 text-gray-500 dark:text-gray-400"
        :class="isRTL ? 'text-right' : 'text-left'"
        :dir="isRTL ? 'rtl' : 'ltr'"
      >
        {{ $t('auth.oauth.subtitle') }}
      </p>
      <AuthOAuthButtons
        :providers="['google','facebook','apple']"
        :disabled="submitting"
        @click="oauth"
      />
    </template>

    <template #form>
      <div class="mb-6">
        <h2
          class="text-xl font-semibold text-gray-900 dark:text-gray-100"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ $t('auth.register.title') }}
        </h2>
        <p
          class="mt-2 text-gray-500 dark:text-gray-400"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ $t('auth.register.subtitle') }}
        </p>
      </div>

      <form
        class="grid gap-5"
        novalidate
        @submit.prevent="onSubmit"
      >
        <UiTextField
          id="name"
          v-model="values.name"
          :label="$t('auth.register.fields.name')"
          :error="touched.name && errors.name ? errors.name[0] : ''"
          :locale="locale"
          autocomplete="name"
          @blur="touched.name = true; validateField('name')"
        >
          <template #icon-left>
            <Icon
              icon="mdi:account-outline"
              class="h-5 w-5"
            />
          </template>
        </UiTextField>

        <UiTextField
          id="email"
          v-model="values.email"
          :label="$t('auth.register.fields.email')"
          :error="touched.email && errors.email ? errors.email[0] : ''"
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
          :label="$t('auth.register.fields.password')"
          :error="touched.password && errors.password ? errors.password[0] : ''"
          :locale="locale"
          autocomplete="new-password"
          @blur="touched.password = true; validateField('password')"
        />

        <UiPasswordField
          id="password_confirmation"
          v-model="values.password_confirmation"
          :label="$t('auth.register.fields.confirm')"
          :error="touched.password_confirmation && errors.password_confirmation ? errors.password_confirmation[0] : ''"
          :locale="locale"
          autocomplete="new-password"
          @blur="touched.password_confirmation = true; validateField('password_confirmation')"
        />

        <button
          type="submit"
          :disabled="submitting"
          :aria-busy="submitting || undefined"
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
                v-if="submitting"
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
            <span>{{ $t('auth.register.actions.submit') }}</span>
          </span>
        </button>


        <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
          {{ $t('auth.register.haveAccount') }}
          <RouterLink
            to="/auth/login"
            class="text-brand underline-offset-2 hover:underline"
          >
            {{ $t('auth.register.actions.login') }}
          </RouterLink>
        </p>
      </form>
    </template>
  </AuthLayout>
</template>
