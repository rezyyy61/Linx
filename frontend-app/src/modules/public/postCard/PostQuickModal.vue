<!--<script setup lang="ts">-->
<!--import { ref, computed } from 'vue'-->
<!--import { Icon } from '@iconify/vue'-->
<!--import type { Post } from '@/stores/post/post'-->
<!--import QuickPostForm from "@/modules/public/postCard/utils/QuickPostForm.vue";-->

<!--type Mode = 'create' | 'edit'-->

<!--const props = defineProps<{-->
<!--  modelValue: boolean-->
<!--  initial?: Post | null-->
<!--  mode?: Mode-->
<!--  title?: string-->
<!--  closeOnSuccess?: boolean-->
<!--}>()-->

<!--const emit = defineEmits<{-->
<!--  (e: 'update:modelValue', v: boolean): void-->
<!--  (e: 'success', post: Post): void-->
<!--  (e: 'error', payload: any): void-->
<!--}>()-->

<!--const open = computed({-->
<!--  get: () => props.modelValue,-->
<!--  set: (v: boolean) => emit('update:modelValue', v),-->
<!--})-->

<!--const okMsg = ref<string | null>(null)-->
<!--const errMsg = ref<string | null>(null)-->

<!--const effectiveMode = computed<Mode>(() => {-->
<!--  if (props.mode) return props.mode-->
<!--  return props.initial && (props.initial as any).id ? 'edit' : 'create'-->
<!--})-->

<!--function close() {-->
<!--  open.value = false-->
<!--  okMsg.value = null-->
<!--  errMsg.value = null-->
<!--}-->

<!--function onSaved(post: Post) {-->
<!--  okMsg.value = effectiveMode.value === 'create' ? 'Post saved' : 'Update saved'-->
<!--  errMsg.value = null-->
<!--  emit('success', post)-->
<!--  if (props.closeOnSuccess !== false) close()-->
<!--}-->

<!--function onPublished(post: Post) {-->
<!--  okMsg.value = effectiveMode.value === 'create' ? 'Post published' : 'Post updated & published'-->
<!--  errMsg.value = null-->
<!--  emit('success', post)-->
<!--  if (props.closeOnSuccess !== false) close()-->
<!--}-->

<!--function onDraftSaved(post: Post) {-->
<!--  okMsg.value = 'Saved as draft'-->
<!--  errMsg.value = null-->
<!--  emit('success', post)-->
<!--  if (props.closeOnSuccess !== false) close()-->
<!--}-->

<!--function onError(e: any) {-->
<!--  errMsg.value = e?.message || 'Error saving/publishing'-->
<!--  okMsg.value = null-->
<!--  emit('error', e)-->
<!--}-->

<!--function onBackdrop(e: MouseEvent) {-->
<!--  if ((e.target as HTMLElement)?.dataset?.backdrop === 'true') {-->
<!--    close()-->
<!--  }-->
<!--}-->
<!--</script>-->

<!--<template>-->
<!--  <teleport to="body">-->
<!--    <transition-->
<!--      name="fade"-->
<!--      appear-->
<!--    >-->
<!--      <div-->
<!--        v-if="open"-->
<!--        class="fixed inset-0 z-[1000] flex items-center justify-center"-->
<!--        @click="onBackdrop"-->
<!--        @keydown.esc.window.prevent="close"-->
<!--      >-->
<!--        <div-->
<!--          class="absolute inset-0 bg-black/40 backdrop-blur-[1px]"-->
<!--          data-backdrop="true"-->
<!--          aria-hidden="true"-->
<!--        />-->

<!--        <div-->
<!--          role="dialog"-->
<!--          aria-modal="true"-->
<!--          class="relative mx-3 max-w-[750px] rounded-2xl border border-gray-200 bg-white p-3 shadow-xl outline-none dark:border-gray-700 dark:bg-gray-900"-->
<!--        >-->
<!--          <div class="mb-2 flex items-center justify-between">-->
<!--            <h3 class="text-sm font-semibold">-->
<!--              {{ title || (effectiveMode === 'create' ? 'Quick Create Post' : 'Edit Post') }}-->
<!--            </h3>-->
<!--            <button-->
<!--              class="inline-flex h-7 w-7 items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"-->
<!--              aria-label="Close"-->
<!--              @click="close"-->
<!--            >-->
<!--              <Icon-->
<!--                icon="solar:close-circle-bold-duotone"-->
<!--                class="h-5 w-5"-->
<!--              />-->
<!--            </button>-->
<!--          </div>-->

<!--          <div-->
<!--            v-if="errMsg"-->
<!--            class="mb-2 rounded-lg bg-red-50 p-2 text-xs text-red-700 dark:bg-red-950 dark:text-red-200"-->
<!--          >-->
<!--            {{ errMsg }}-->
<!--          </div>-->
<!--          <div-->
<!--            v-if="okMsg"-->
<!--            class="mb-2 rounded-lg bg-emerald-50 p-2 text-xs text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200"-->
<!--          >-->
<!--            {{ okMsg }}-->
<!--          </div>-->

<!--          <div class="max-h-[80vh] overflow-y-auto">-->
<!--            <QuickPostForm-->
<!--              :initial="(initial || undefined) as any"-->
<!--              :mode="effectiveMode"-->
<!--              @saved="onSaved"-->
<!--              @published="onPublished"-->
<!--              @draft_saved="onDraftSaved"-->
<!--              @error="onError"-->
<!--            />-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->
<!--    </transition>-->
<!--  </teleport>-->
<!--</template>-->

<!--<style scoped>-->
<!--.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }-->
<!--.fade-enter-from, .fade-leave-to { opacity: 0; }-->
<!--</style>-->
