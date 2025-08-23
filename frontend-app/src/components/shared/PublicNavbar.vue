<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { Icon } from "@iconify/vue";
import BrandLogo from "@/components/shared/BrandLogo.vue";
import PreferencesMenu from "@/components/shared/PreferencesMenu.vue";
import { useAuthStore } from "@/stores/auth/auth";
import UserMenu from "@/components/shared/UserMenu.vue";

const { t } = useI18n();
const menuOpen = ref(false);

const auth = useAuthStore();
const isAuth = computed(() => auth.isAuthenticated);

onMounted(async () => {
  if (!auth.bootstrapDone && !auth.loading) {
    await auth.bootstrap();
  }
});

function toggleMenu() {
  menuOpen.value = !menuOpen.value;
}
</script>

<template>
  <nav
    dir="ltr"
    class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-gray-200 dark:bg-gray-900/80"
  >
    <div
      class="max-w-screen-xl mx-auto px-4 py-3 flex items-center justify-between gap-3"
    >
      <RouterLink
        to="/"
        class="flex items-center gap-3"
      >
        <BrandLogo />
      </RouterLink>

      <div class="hidden md:flex items-center gap-1">
        <RouterLink
          v-slot="{ isActive }"
          to="/about"
        >
          <span
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm',
              isActive
                ? 'text-red-600 dark:text-red-400'
                : 'text-gray-700 hover:bg-gray-100 dark:text-zinc-200 dark:hover:bg-zinc-800',
            ]"
          >
            <Icon
              icon="mdi:information-outline"
              class="h-4 w-4"
            />
            {{ t("nav.about") }}
          </span>
        </RouterLink>
        <RouterLink
          v-slot="{ isActive }"
          to="/contact"
        >
          <span
            :class="[
              'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm',
              isActive
                ? 'text-red-600 dark:text-red-400'
                : 'text-gray-700 hover:bg-gray-100 dark:text-zinc-200 dark:hover:bg-zinc-800',
            ]"
          >
            <Icon
              icon="mdi:email-outline"
              class="h-4 w-4"
            />
            {{ t("nav.contact") }}
          </span>
        </RouterLink>
      </div>

      <div class="flex items-center gap-2 md:gap-3">
        <PreferencesMenu />

        <div
          v-if="isAuth"
          class="relative"
        >
          <UserMenu />
        </div>

        <div
          v-else
          class="hidden md:flex items-center gap-2"
        >
          <RouterLink
            to="/auth/login"
            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
          >
            <Icon
              icon="mdi:login"
              class="h-4 w-4"
            />
            {{ t("nav.login") }}
          </RouterLink>
          <RouterLink
            to="/auth/register"
            class="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
          >
            <Icon
              icon="mdi:account-plus-outline"
              class="h-4 w-4"
            />
            {{ t("nav.register") }}
          </RouterLink>
        </div>

        <button
          type="button"
          class="md:hidden inline-flex h-10 w-10 items-center justify-center rounded-md border border-gray-300 bg-white hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
          :aria-expanded="menuOpen ? 'true' : 'false'"
          @click="toggleMenu"
        >
          <Icon
            icon="mdi:menu"
            class="h-5 w-5 text-gray-700 dark:text-zinc-200"
          />
        </button>
      </div>
    </div>

    <div
      :class="[
        'md:hidden border-t border-gray-100 dark:border-zinc-800 bg-white dark:bg-zinc-900',
        menuOpen ? 'block' : 'hidden',
      ]"
    >
      <div class="px-4 py-3 grid gap-2">
        <RouterLink
          to="/about"
          class="rounded-md px-3 py-2 hover:bg-gray-50 dark:hover:bg-zinc-800"
          @click="menuOpen = false"
        >
          {{ t("nav.about") }}
        </RouterLink>
        <RouterLink
          to="/contact"
          class="rounded-md px-3 py-2 hover:bg-gray-50 dark:hover:bg-zinc-800"
          @click="menuOpen = false"
        >
          {{ t("nav.contact") }}
        </RouterLink>
        <div
          v-if="!isAuth"
          class="grid grid-cols-2 gap-2 pt-1"
        >
          <RouterLink
            to="/auth/login"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-center text-sm hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
            @click="menuOpen = false"
          >
            {{ t("nav.login") }}
          </RouterLink>
          <RouterLink
            to="/auth/register"
            class="rounded-md bg-red-600 px-3 py-2 text-center text-sm text-white hover:bg-red-700"
            @click="menuOpen = false"
          >
            {{ t("nav.register") }}
          </RouterLink>
        </div>
      </div>
    </div>
  </nav>
</template>
