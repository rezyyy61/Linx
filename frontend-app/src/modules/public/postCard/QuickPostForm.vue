<!--<script setup lang="ts">-->
<!--import { ref, computed, onUnmounted, type Ref } from 'vue'-->
<!--import { Icon } from '@iconify/vue'-->
<!--import type { Post } from '@/stores/post/post'-->
<!--import { usePostForm } from '@/modules/dashboard/pages/posts/usePostForm'-->
<!--import { normalizeApiError } from '@/modules/dashboard/pages/posts/lib/errors'-->
<!--import { useMediaStore } from '@/stores/post/post.media'-->
<!--import { useAuthStore } from '@/stores/auth/auth'-->

<!--type Mode = 'create' | 'edit'-->

<!--const props = defineProps<{-->
<!--  initial?: Post | null-->
<!--  mode?: Mode-->
<!--  userName?: string-->
<!--  userAvatar?: string | null-->
<!--}>()-->

<!--const emit = defineEmits<{-->
<!--  (e: 'published', post: Post): void-->
<!--  (e: 'draft_saved', post: Post): void-->
<!--  (e: 'error', payload: any): void-->
<!--}>()-->

<!--const auth = useAuthStore()-->
<!--const name = computed(() => props.userName || (auth.user as any)?.name || 'You')-->
<!--const avatar = computed<string | undefined>(() =>-->
<!--  props.userAvatar-->
<!--  || (auth.user as any)?.avatarUrl-->
<!--  || (auth.user as any)?.avatar_url-->
<!--  || undefined-->
<!--)-->

<!--const fm = usePostForm(props.initial || undefined)-->
<!--const mediaStore = useMediaStore()-->

<!--const isEdit = computed(() => props.mode === 'edit')-->
<!--const saving = fm.isSaving-->
<!--const done = new Set(['ready','done','processed','complete','completed'])-->
<!--const hasPending = computed(() => mediaStore.list.some(t => !done.has(String(t.status ?? '').toLowerCase())))-->
<!--const canSubmit = computed(() => !saving.value && !hasPending.value)-->

<!--const content = computed<string>({-->
<!--  get: () => (fm as any).content?.value ?? '',-->
<!--  set: v => ((fm as any).content.value = v && v.length ? v : null),-->
<!--})-->

<!--const visibility = computed({-->
<!--  get: () => (fm as any).visibility.value,-->
<!--  set: v => ((fm as any).visibility.value = v),-->
<!--})-->

<!--const gallery = (fm as any).gallery as Ref<any[]>-->
<!--const fileUrlMap = new Map<File|Blob, string>()-->
<!--function getMime(item: any) { return String(item?.mime || item?.mime_type || item?.type || '').toLowerCase() }-->
<!--function srcFor(item: any): string | null {-->
<!--  if (item?.preview_url) return item.preview_url-->
<!--  if (item?.previewUrl) return item.previewUrl-->
<!--  if (item?.thumb_url) return item.thumb_url-->
<!--  if (item?.thumbnail_url) return item.thumbnail_url-->
<!--  if (item?.thumbnailUrl) return item.thumbnailUrl-->
<!--  if (item?.url) return item.url-->
<!--  const f = item?.file || item?.blob || item?._file || item?.raw-->
<!--  if (f instanceof File || f instanceof Blob) {-->
<!--    if (!fileUrlMap.has(f)) fileUrlMap.set(f, URL.createObjectURL(f))-->
<!--    return fileUrlMap.get(f) || null-->
<!--  }-->
<!--  return null-->
<!--}-->
<!--onUnmounted(() => { for (const u of fileUrlMap.values()) URL.revokeObjectURL(u); fileUrlMap.clear() })-->

<!--const pickEl = ref<HTMLInputElement|null>(null)-->
<!--function triggerPick() { pickEl.value?.click() }-->
<!--function onPicked(e: Event) {-->
<!--  const input = e.target as HTMLInputElement-->
<!--  const files = Array.from(input.files || [])-->
<!--  if (files.length) fm.addFiles(files)-->
<!--  input.value = ''-->
<!--}-->

<!--function removeAt(i: number) {-->
<!--  const it = gallery.value[i]-->
<!--  const key = it?.key ?? it?.id ?? String(i)-->
<!--  try { fm.removeItem(key) } catch {}-->
<!--}-->

<!--function move(i: number, dir: -1|1) {-->
<!--  try { fm.move(i, dir) } catch {}-->
<!--}-->

<!--async function publish() {-->
<!--  if (!canSubmit.value) return-->
<!--  try {-->
<!--    const p = isEdit.value && (props.initial as any)?.id-->
<!--      ? await fm.publishUpdate(Number((props.initial as any).id))-->
<!--      : await fm.publishCreate()-->
<!--    emit('published', p as any)-->
<!--  } catch (e: any) {-->
<!--    emit('error', normalizeApiError(e))-->
<!--  }-->
<!--}-->

<!--async function saveDraft() {-->
<!--  if (!canSubmit.value) return-->
<!--  try {-->
<!--    const p = isEdit.value && (props.initial as any)?.id-->
<!--      ? await fm.saveDraftUpdate(Number((props.initial as any).id))-->
<!--      : await fm.saveDraft()-->
<!--    emit('draft_saved', p as any)-->
<!--  } catch (e: any) {-->
<!--    emit('error', normalizeApiError(e))-->
<!--  }-->
<!--}-->
<!--</script>-->

<!--<template>-->
<!--  <div class="w-full">-->
<!--    <div class="flex items-center gap-3">-->
<!--      <img-->
<!--        v-if="avatar"-->
<!--        :src="avatar"-->
<!--        class="h-10 w-10 rounded-full object-cover"-->
<!--        alt="avatar"-->
<!--      >-->
<!--      <div-->
<!--        v-else-->
<!--        class="h-10 w-10 rounded-full bg-zinc-300 dark:bg-zinc-700"-->
<!--      />-->
<!--      <div class="min-w-0">-->
<!--        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">-->
<!--          {{ name }}-->
<!--        </div>-->
<!--        <div class="mt-1">-->
<!--          <label class="inline-flex items-center gap-1 rounded-lg bg-zinc-200 px-2 py-0.5 text-[11px] text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 cursor-pointer">-->
<!--            <Icon-->
<!--              icon="solar:shield-check-bold-duotone"-->
<!--              class="h-3.5 w-3.5"-->
<!--            />-->
<!--            <select-->
<!--              v-model="visibility"-->
<!--              class="bg-transparent text-[11px] focus:outline-none"-->
<!--            >-->
<!--              <option value="public">Public</option>-->
<!--              <option value="unlisted">Unlisted</option>-->
<!--              <option value="private">Private</option>-->
<!--            </select>-->
<!--          </label>-->
<!--        </div>-->
<!--      </div>-->
<!--    </div>-->

<!--    <div class="mt-4 rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">-->
<!--      <textarea-->
<!--        v-model="content"-->
<!--        placeholder="What's on your mind?"-->
<!--        class="min-h-[120px] w-full resize-none bg-transparent text-[15px] leading-6 outline-none placeholder:text-zinc-400 dark:text-zinc-100"-->
<!--      />-->
<!--    </div>-->

<!--    <div-->
<!--      v-if="gallery.length"-->
<!--      class="mt-3 rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800"-->
<!--    >-->
<!--      <div class="relative">-->
<!--        <div class="absolute left-3 top-3 z-10 flex gap-2">-->
<!--          <button-->
<!--            type="button"-->
<!--            class="inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1 text-xs text-zinc-900 shadow hover:bg-white dark:bg-zinc-900/90 dark:text-zinc-100"-->
<!--            @click="saveDraft"-->
<!--          >-->
<!--            Edit-->
<!--          </button>-->
<!--          <button-->
<!--            type="button"-->
<!--            class="inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1 text-xs text-zinc-900 shadow hover:bg-white dark:bg-zinc-900/90 dark:text-zinc-100"-->
<!--            @click="triggerPick"-->
<!--          >-->
<!--            Add photos/videos-->
<!--          </button>-->
<!--        </div>-->
<!--        <button-->
<!--          type="button"-->
<!--          class="absolute right-3 top-3 z-10 inline-flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/60"-->
<!--          aria-label="remove"-->
<!--          @click="removeAt(0)"-->
<!--        >-->
<!--          <Icon-->
<!--            icon="solar:close-circle-bold-duotone"-->
<!--            class="h-5 w-5"-->
<!--          />-->
<!--        </button>-->
<!--        <div class="aspect-[4/5] w-full overflow-hidden rounded-xl">-->
<!--          <img-->
<!--            v-if="srcFor(gallery[0]) && !getMime(gallery[0]).startsWith('video')"-->
<!--            :src="srcFor(gallery[0]) as string"-->
<!--            class="h-full w-full object-cover"-->
<!--            alt="media"-->
<!--          >-->
<!--          <video-->
<!--            v-else-if="srcFor(gallery[0])"-->
<!--            :src="srcFor(gallery[0]) as string"-->
<!--            class="h-full w-full object-cover"-->
<!--            controls-->
<!--            playsinline-->
<!--          />-->
<!--        </div>-->
<!--      </div>-->

<!--      <div-->
<!--        v-if="gallery.length > 1"-->
<!--        class="grid grid-cols-4 gap-2 p-2"-->
<!--      >-->
<!--        <div-->
<!--          v-for="(item, i) in gallery.slice(1)"-->
<!--          :key="item?.key ?? item?.id ?? i"-->
<!--          class="group relative aspect-square overflow-hidden rounded-lg"-->
<!--        >-->
<!--          <img-->
<!--            v-if="srcFor(item) && !getMime(item).startsWith('video')"-->
<!--            :src="srcFor(item) as string"-->
<!--            class="h-full w-full object-cover"-->
<!--            alt="thumb"-->
<!--          >-->
<!--          <video-->
<!--            v-else-if="srcFor(item)"-->
<!--            :src="srcFor(item) as string"-->
<!--            class="h-full w-full object-cover"-->
<!--            muted-->
<!--            playsinline-->
<!--          />-->
<!--          <div class="absolute inset-0 hidden items-center justify-between p-1 group-hover:flex">-->
<!--            <button-->
<!--              type="button"-->
<!--              class="rounded-lg bg-white/90 p-1 text-zinc-800 shadow hover:bg-white"-->
<!--              @click="move(i+1,-1)"-->
<!--            >-->
<!--              <Icon-->
<!--                icon="solar:alt-arrow-left-bold-duotone"-->
<!--                class="h-4 w-4"-->
<!--              />-->
<!--            </button>-->
<!--            <button-->
<!--              type="button"-->
<!--              class="rounded-lg bg-white/90 p-1 text-zinc-800 shadow hover:bg-white"-->
<!--              @click="removeAt(i+1)"-->
<!--            >-->
<!--              <Icon-->
<!--                icon="solar:trash-bin-2-bold-duotone"-->
<!--                class="h-4 w-4"-->
<!--              />-->
<!--            </button>-->
<!--            <button-->
<!--              type="button"-->
<!--              class="rounded-lg bg-white/90 p-1 text-zinc-800 shadow hover:bg-white"-->
<!--              @click="move(i+1,1)"-->
<!--            >-->
<!--              <Icon-->
<!--                icon="solar:alt-arrow-right-bold-duotone"-->
<!--                class="h-4 w-4"-->
<!--              />-->
<!--            </button>-->
<!--          </div>-->
<!--        </div>-->
<!--      </div>-->
<!--    </div>-->

<!--    <div class="mt-3 rounded-xl border border-zinc-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900">-->
<!--      <div class="flex items-center justify-between">-->
<!--        <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">-->
<!--          Add to your post-->
<!--        </div>-->
<!--        <div class="flex items-center gap-2">-->
<!--          <button-->
<!--            type="button"-->
<!--            class="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800"-->
<!--            @click="triggerPick"-->
<!--          >-->
<!--            <Icon-->
<!--              icon="solar:gallery-add-bold-duotone"-->
<!--              class="h-5 w-5"-->
<!--            />-->
<!--          </button>-->
<!--          <button-->
<!--            type="button"-->
<!--            class="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800"-->
<!--          >-->
<!--            <Icon-->
<!--              icon="solar:users-group-two-rounded-bold-duotone"-->
<!--              class="h-5 w-5"-->
<!--            />-->
<!--          </button>-->
<!--          <button-->
<!--            type="button"-->
<!--            class="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800"-->
<!--          >-->
<!--            <Icon-->
<!--              icon="solar:smile-circle-bold-duotone"-->
<!--              class="h-5 w-5"-->
<!--            />-->
<!--          </button>-->
<!--          <button-->
<!--            type="button"-->
<!--            class="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800"-->
<!--          >-->
<!--            <Icon-->
<!--              icon="solar:map-point-wave-bold-duotone"-->
<!--              class="h-5 w-5"-->
<!--            />-->
<!--          </button>-->
<!--          <button-->
<!--            type="button"-->
<!--            class="inline-flex h-9 w-9 items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800"-->
<!--          >-->
<!--            <Icon-->
<!--              icon="solar:gif-bold-duotone"-->
<!--              class="h-5 w-5"-->
<!--            />-->
<!--          </button>-->
<!--        </div>-->
<!--      </div>-->
<!--      <input-->
<!--        ref="pickEl"-->
<!--        type="file"-->
<!--        class="hidden"-->
<!--        multiple-->
<!--        @change="onPicked"-->
<!--      >-->
<!--    </div>-->

<!--    <div class="mt-3 flex items-center justify-between">-->
<!--      <button-->
<!--        type="button"-->
<!--        class="rounded-xl bg-zinc-200 px-4 py-2 text-sm text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700"-->
<!--        :disabled="!canSubmit"-->
<!--        @click="saveDraft"-->
<!--      >-->
<!--        Save draft-->
<!--      </button>-->
<!--      <button-->
<!--        type="button"-->
<!--        class="rounded-xl bg-indigo-600 px-6 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"-->
<!--        :disabled="!canSubmit"-->
<!--        @click="publish"-->
<!--      >-->
<!--        {{ isEdit ? 'Update' : 'Post' }}-->
<!--      </button>-->
<!--    </div>-->
<!--    <div class="mt-1 text-[12px] text-zinc-500 dark:text-zinc-400">-->
<!--      <span v-if="hasPending">Uploading…</span>-->
<!--      <span v-else>Ready</span>-->
<!--    </div>-->
<!--  </div>-->
<!--</template>-->
