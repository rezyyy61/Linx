<script setup lang="ts">
defineOptions({ name: 'ForgotPasswordPage' })
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AuthLayout from '../components/AuthLayout.vue'
import UiTextField from '../components/ui/UiTextField.vue'
import { useForm } from '../lib/forms/useForm'
import { required, email as emailRule } from '../lib/forms/validators'
import { useAuthStore } from '@/stores/auth/auth'
import { handleApiError } from '../lib/notify/handleApiError'
import { useNotify } from '../lib/notify/useNotify'
import { Icon } from '@iconify/vue'

const { t, locale } = useI18n()
const notify = useNotify()
const auth = useAuthStore()
const submitting = ref(false)

const rtlLocales = ['fa','ar','ckb','ku','kur','ps','ur','he','dv','syr']
const isRTL = computed(() => rtlLocales.some(c => locale.value.toLowerCase().startsWith(c)))

const { values, touched, errors, validateField, validateAll, setTouchedAll, setServerErrors } =
  useForm({ email: '' }, { email: [required(t('auth.forgot.errors.emailRequired')), emailRule(t('auth.forgot.errors.emailInvalid'))] })

async function onSubmit() {
  setTouchedAll()
  if (!validateAll() || submitting.value) return
  submitting.value = true
  try {
    await auth.forgotPassword(values.email)
    notify.success({ title: t('auth.forgot.notify.sent'), duration: 9000 })
  } catch (e) {
    handleApiError(e, { setFieldErrors: (errs) => setServerErrors(errs), t })
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <template #form>
      <div class="mb-6">
        <h2
          class="text-xl font-semibold text-gray-900 dark:text-gray-100"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ $t('auth.forgot.title') }}
        </h2>
        <p
          class="mt-1 text-gray-500 dark:text-gray-400"
          :class="isRTL ? 'text-right' : 'text-left'"
          :dir="isRTL ? 'rtl' : 'ltr'"
        >
          {{ $t('auth.forgot.subtitle') }}
        </p>
      </div>
      

      <form
        class="mt-6 grid gap-5"
        novalidate
        @submit.prevent="onSubmit"
      >
        <UiTextField
          id="email"
          v-model="values.email"
          :label="$t('auth.forgot.fields.email')"
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
            <span>{{ $t('auth.forgot.actions.submit') }}</span>
          </span>
        </button>

        <p class="text-center text-sm text-gray-600 dark:text-zinc-400">
          {{ $t('auth.forgot.backTo') }}
          <RouterLink
            to="/auth/login"
            class="text-indigo-600 hover:opacity-90"
          >
            {{ $t('auth.forgot.actions.login') }}
          </RouterLink>
        </p>
      </form>
    </template>
  </AuthLayout>
</template>
