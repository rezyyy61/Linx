<template>
  <ImageGrid :items="media.items" />
</template>

<script setup lang="ts">
import type { GalleryMedia } from '@/modules/public/postCard/types/media.types'
import ImageGrid from '../image/ImageGrid.vue'
const { media } = defineProps<{ media: GalleryMedia }>()
</script>
