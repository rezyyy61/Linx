<script setup lang="ts">
import { computed } from "vue"
import { useRouter, RouterLink } from "vue-router"
import { useI18n } from "vue-i18n"
import { useAuthStore } from "@/stores/auth/auth"

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const username = computed(() => auth.user?.name ?? "")
async function onLogout() { await auth.logout(); router.push({ name: "home" }) }
</script>

<template>
  <section class="text-center py-12 sm:py-16">
    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
      {{ t('home.hero.title') }}
    </h1>

    <p class="mt-3 text-base text-gray-700 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
      {{ t('home.hero.subtitle') }}
    </p>

    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
      <template v-if="!auth.isAuthenticated">
        <RouterLink
            to="/auth/register"
            class="inline-flex items-center justify-center rounded-md bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
        >
          {{ t('home.hero.ctaGetStarted') }}
        </RouterLink>

        <RouterLink
            to="/auth/login"
            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 transition"
        >
          {{ t('home.hero.ctaSignIn') }}
        </RouterLink>

        <RouterLink
            to="/about"
            class="inline-flex items-center justify-center rounded-md px-5 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition"
        >
          {{ t('home.hero.ctaLearnMore') }}
        </RouterLink>
      </template>

      <template v-else>
        <span class="inline-flex items-center rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-800 dark:bg-zinc-800 dark:text-zinc-100">
          {{ t('home.hero.greeting', { name: username }) }}
        </span>
        <RouterLink
            to="/dashboard"
            class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800 transition"
        >
          {{ t('home.hero.ctaDashboard') }}
        </RouterLink>
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-md bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-700 transition"
            @click="onLogout"
        >
          {{ t('home.hero.ctaLogout') }}
        </button>
      </template>
    </div>
  </section>
</template>
