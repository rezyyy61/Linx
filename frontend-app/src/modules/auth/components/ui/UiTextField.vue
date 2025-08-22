<template>
  <div class="grid gap-1.5">
    <label
      v-if="label"
      :for="id"
      :dir="isRTL ? 'rtl' : 'ltr'"
      :class="['block text-sm font-medium', isRTL ? 'text-right' : 'text-left', 'text-gray-700 dark:text-gray-300']"
    >
      {{ label }}
    </label>

    <div class="relative">
      <div
        v-if="hasLeft"
        :class="[
          'pointer-events-none absolute inset-y-0 flex items-center text-gray-400 dark:text-gray-300/80',
          isRTL && !hasRight ? 'right-3' : 'left-3'
        ]"
      >
        <slot name="icon-left" />
      </div>

      <input
        :id="id"
        :name="name || id"
        :type="type"
        :value="modelValue"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :inputmode="inputmode"
        :aria-invalid="!!error || undefined"
        :aria-describedby="error ? `${id}-error` : undefined"
        :dir="inputDir"
        class="block w-full h-11 md:h-12 rounded-xl shadow-sm transition ring-1 focus:ring-2 focus:outline-none
               ring-gray-300 bg-white text-gray-900 hover:bg-gray-50
               dark:ring-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10
               disabled:opacity-60 disabled:cursor-not-allowed"
        :class="[
          paddingClass,
          error ? 'ring-red-400/70 focus:ring-red-500 dark:focus:ring-red-400 bg-red-50 dark:bg-red-500/10'
          : 'focus:ring-indigo-500 dark:focus:ring-indigo-400',
          inputClass
        ]"
        :placeholder="''"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        @blur="$emit('blur')"
      >

      <div
        v-if="hasRight"
        class="absolute inset-y-0 right-3 flex items-center text-gray-400 dark:text-gray-300/80"
      >
        <slot name="icon-right" />
      </div>
    </div>

    <p
      v-if="error"
      :id="`${id}-error`"
      class="text-xs text-red-500"
    >
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
defineOptions({ name: 'UiTextField' })
import { useSlots, computed } from 'vue'

const props = withDefaults(defineProps<{
  id: string
  modelValue: string
  label?: string
  type?: string
  disabled?: boolean
  error?: string
  autocomplete?: string
  inputmode?: string
  inputClass?: string
  name?: string
  dir?: 'ltr' | 'rtl' | 'auto'
  locale?: string
}>(), { type: 'text', disabled: false, inputClass: '', dir: 'auto' })

defineEmits<{ 'update:modelValue':[string], blur:[] }>()

const rtlLocales = ['fa', 'ar', 'ckb', 'ku', 'kur', 'ps', 'ur', 'he', 'dv', 'syr']
const slots = useSlots()
const hasLeft  = computed(() => !!slots['icon-left'])
const hasRight = computed(() => !!slots['icon-right'])

const isRTLLocale = computed(() =>
  props.locale ? rtlLocales.some(code => props.locale.toLowerCase().startsWith(code)) : false
)

const isRTL = computed(() => {
  if (props.dir === 'rtl') return true
  if (props.dir === 'ltr') return false
  if (isRTLLocale.value) return true
  if (typeof document !== 'undefined') {
    const d = document.documentElement.getAttribute('dir') || document.body.getAttribute('dir') || ''
    return d.toLowerCase() === 'rtl'
  }
  return false
})

const forceLTRTypes = ['email', 'url', 'tel', 'number', 'search']
const forceLTRInputmodes = ['email', 'url', 'tel', 'numeric', 'decimal']
const inputDir = computed(() =>
  forceLTRTypes.includes((props.type || '').toLowerCase()) ||
  forceLTRInputmodes.includes((props.inputmode || '').toLowerCase())
    ? 'ltr'
    : (isRTL.value ? 'rtl' : 'ltr')
)

const paddingClass = computed(() => {
  const basePad = 'px-4'
  if (hasLeft.value && !hasRight.value) return isRTL.value ? 'pr-10 pl-4' : 'pl-10 pr-4'
  if (!hasLeft.value && hasRight.value) return isRTL.value ? 'pl-4 pr-10' : 'pl-4 pr-10'
  if (hasLeft.value && hasRight.value)   return 'pl-10 pr-10'
  return basePad
})
</script>
