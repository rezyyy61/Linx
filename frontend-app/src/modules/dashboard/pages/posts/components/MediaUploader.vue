<script setup lang="ts">
import { computed } from 'vue'
import { useMediaStore } from '@/stores/post/post.media'
import { fileKey, acceptMatches } from '@/modules/dashboard/pages/posts/pages/useUploadHelpers'
import UploadDropzone from '@/modules/dashboard/pages/posts/components/upload/UploadDropzone.vue'
import UploadTasks from '@/modules/dashboard/pages/posts/components/upload/UploadTasks.vue'
import MediaPreviewGrid from '@/modules/dashboard/pages/posts/components/mediaPreview/MediaPreviewGrid.vue'

const filesModel = defineModel<File[]>('files', { default: [] })
const existingModel = defineModel<{ id: number; url?: string; mime_type?: string }[]>('existing', { default: [] })

const props = withDefaults(defineProps<{ capacity?: number | null; max?: number | null; accept?: string }>(), {
  capacity: null,
  max: null,
  accept: 'image/*,video/*,audio/*,application/pdf',
})

const emit = defineEmits<{ created: [uids: string[]] }>()

const media = useMediaStore()

const remaining = computed<number>(() => {
  if (typeof props.capacity === 'number') return Math.max(0, props.capacity - filesModel.value.length)
  if (typeof props.max === 'number') {
    const used = existingModel.value.length + filesModel.value.length
    return Math.max(0, props.max - used)
  }
  return Number.POSITIVE_INFINITY
})

const addFiles = (arr: File[]) => {
  if (!arr.length || remaining.value <= 0) return
  const filtered = arr.filter(f => acceptMatches(f, props.accept))
  if (!filtered.length) return
  const keys = new Set(filesModel.value.map(fileKey))
  const uniq: File[] = []
  for (const f of filtered) { const k = fileKey(f); if (!keys.has(k)) { keys.add(k); uniq.push(f) } }
  if (!uniq.length) return
  const take = Math.min(remaining.value, uniq.length)
  const picked = uniq.slice(0, take)
  filesModel.value = [...filesModel.value, ...picked]
  const tasks = media.enqueueFiles(picked)
  emit('created', tasks.map(t => t.uid))
}

const removeFileAt = (i: number) => {
  const list = [...filesModel.value]
  if (i < 0 || i >= list.length) return
  list.splice(i, 1)
  filesModel.value = list
}

const removeExistingAt = (i: number) => {
  const list = [...existingModel.value]
  if (i < 0 || i >= list.length) return
  list.splice(i, 1)
  existingModel.value = list
}
</script>

<template>
  <div class="space-y-4">
    <MediaPreviewGrid
      :files="filesModel"
      :existing="existingModel"
      @remove:file="removeFileAt"
      @remove:existing="removeExistingAt"
    />
    <UploadDropzone
      :accept="props.accept"
      :remaining="remaining"
      @pick="addFiles"
    />
    <UploadTasks />
  </div>
</template>
