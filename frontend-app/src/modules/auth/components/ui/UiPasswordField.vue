<template>
  <UiTextField
    :id="id"
    :model-value="modelValue"
    :label="label"
    :placeholder="placeholder"
    :type="show ? 'text' : 'password'"
    :disabled="disabled"
    :error="error"
    :autocomplete="autocomplete || 'new-password'"
    @update:model-value="$emit('update:modelValue', $event)"
    @blur="$emit('blur')"
  >
    <template #icon-left>
      <Icon
        :icon="leftIcon"
        class="h-5 w-5"
      />
    </template>

    <template #icon-right>
      <button
        type="button"
        class="inline-flex items-center text-gray-400 hover:text-gray-600 dark:text-gray-300/80 dark:hover:text-white transition"
        :aria-pressed="show"
        :title="show ? 'Hide password' : 'Show password'"
        @click="show = !show"
      >
        <Icon
          :icon="show ? 'mdi:eye-off-outline' : 'mdi:eye-outline'"
          class="h-5 w-5"
        />
      </button>
    </template>
  </UiTextField>
</template>

<script setup lang="ts">
defineOptions({ name: 'UiPasswordField' })
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import UiTextField from './UiTextField.vue'

withDefaults(defineProps<{
  id: string
  modelValue: string
  label?: string
  placeholder?: string
  disabled?: boolean
  error?: string
  autocomplete?: string
  leftIcon?: string
}>(), { leftIcon: 'mdi:lock-outline' })

defineEmits<{ 'update:modelValue':[string], blur:[] }>()
const show = ref(false)
</script>
