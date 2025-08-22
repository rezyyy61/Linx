<template>
  <div class="flex flex-col gap-3">
    <button
      v-for="p in items"
      :key="p.key"
      type="button"
      class="relative h-11 md:h-12 w-full rounded-xl px-4 text-sm font-medium shadow-sm transition active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed"
      :class="p.classes"
      :disabled="disabled"
      :aria-label="$t(`auth.oauth.${p.key}`)"
      @click="emit('click', p.key)"
    >
      <span class="absolute inset-y-0 left-4 inline-flex items-center">
        <Icon
          :icon="p.icon"
          class="h-5 w-5"
        />
      </span>
      <span class="block text-center select-none">{{ $t(`auth.oauth.${p.key}`) }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
defineOptions({ name: 'AuthOAuthButtons' })
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

type Provider = 'google' | 'facebook' | 'apple' | 'github'

const props = withDefaults(defineProps<{
  disabled?: boolean
  providers?: Provider[]
}>(), {
  providers: () => ['google', 'facebook', 'apple']
})

const emit = defineEmits<{ click: [provider: Provider] }>()

const provs = computed<Provider[]>(() =>
  props.providers?.length ? props.providers : ['google', 'facebook', 'apple']
)

type Item = { key: Provider; icon: string; classes: string }

const items = computed<Item[]>(() => {
  const base = 'ring-1'
  const map: Record<Provider, { icon: string; classes: string }> = {
    google:   { icon: 'logos:google-icon', classes: `${base} ring-gray-300 bg-white text-gray-900 hover:bg-gray-50 focus-visible:ring-indigo-500 dark:ring-gray-300 dark:bg-white` },
    facebook: { icon: 'logos:facebook',    classes: `${base} ring-[#1877F2]/25 bg-white text-[#1877F2] hover:bg-[#1877F2]/5 focus-visible:ring-[#1877F2] dark:ring-[#8ab4ff]/25 dark:text-[#8ab4ff] dark:hover:bg-[#8ab4ff]/10` },
    apple:    { icon: 'mdi:apple',         classes: `${base} ring-black/10 bg-black text-white hover:bg-black/90 focus-visible:ring-gray-900 dark:ring-white/10` },
    github:   { icon: 'mdi:github',        classes: `${base} ring-gray-300/80 bg-white text-gray-900 hover:bg-gray-50 focus-visible:ring-gray-900 dark:ring-gray-300 dark:bg-white` },
  }
  return provs.value.map((key) => ({ key, ...map[key] }))
})
</script>
