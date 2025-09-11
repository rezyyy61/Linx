<template>
  <div
    v-if="items?.length"
    class="space-y-2"
  >
    <template
      v-for="(m, idx) in items"
      :key="m.id"
    >
      <MediaImage
        v-if="m.type === 'image'"
        :media="m"
        :gid="gid"
        :index-in-group="idx"
      />
      <MediaVideo
        v-else-if="m.type === 'video'"
        :media="m"
        :gid="gid"
        :index-in-group="idx"
      />
      <MediaGallery
        v-else-if="m.type === 'gallery'"
        :media="m"
        :gid="gid"
      />
      <MediaDocument
        v-else-if="m.type === 'document'"
        :media="m"
      />
      <MediaAudio
        v-else-if="m.type === 'audio'"
        :media="m"
      />
      <MediaLinkPreview
        v-else-if="m.type === 'link'"
        :media="m"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import type { AnyMedia } from '@/modules/public/postCard/types/media.types'
import MediaImage from './image/MediaImage.vue'
import MediaVideo from './video/MediaVideo.vue'
import MediaGallery from './gallery/MediaGallery.vue'
import MediaLinkPreview from './link/MediaLinkPreview.vue'
import {computed} from "vue";
import MediaDocument from "@/modules/public/postCard/content/document/MediaDocument.vue";
import MediaAudio from "@/modules/public/postCard/content/audio/MediaAudio.vue";

const props = defineProps<{ items: AnyMedia[] | null | undefined; gid?: string }>()
const gid = computed(() => props.gid || 'default')
</script>
