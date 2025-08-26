<script setup lang="ts">
import {ref, watchEffect} from 'vue'
import type {Config} from 'dompurify'
import DOMPurify from 'dompurify'

const props = defineProps<{ html: string }>()
const el = ref<HTMLElement|null>(null)

const config: Config = {
  ALLOWED_ATTR: ['href','target','rel','title','alt','src','width','height','class','id','style','dir'],
  ALLOWED_URI_REGEXP: /^(?:https?:|mailto:|tel:|data:image\/(?:png|jpeg|gif|webp);)/i,
  RETURN_TRUSTED_TYPE: false
}

watchEffect(() => {
  if (!el.value) return
  el.value.innerHTML = DOMPurify.sanitize(props.html ?? '', config) as string
})
</script>

<template>
  <div
    ref="el"
    v-bind="$attrs"
  />
</template>
