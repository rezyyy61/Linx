<template>
  <div class="inline-block">
    <button
      :class="btnClasses"
      type="button"
      @click="onClick"
    >
      <Icon
        icon="mdi:share-variant"
        class="h-5 w-5"
      />
      <span
        v-if="label"
        class="ml-1"
      >{{ label }}</span>
    </button>

    <ShareModal
      v-if="open"
      :open="open"
      :shareable-type="shareableType"
      :shareable-id="shareableId"
      :title="title"
      :text="text"
      @close="open = false"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';
import ShareModal from './ShareModal.vue';
import { useShare } from '../composables/useShare';

interface Props {
  shareableType: string;
  shareableId: number;
  label?: string;
  title?: string;
  text?: string;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'solid' | 'outline' | 'ghost';
}
const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  variant: 'solid',
  label: '',
  title: '',
  text: '',
})


const open = ref(false);

const { shareViaWebApi } = useShare({
  shareableType: props.shareableType,
  shareableId: props.shareableId,
  defaultTitle: props.title,
  defaultText: props.text,
});

const btnClasses = computed(() => {
  const sizeMap = {
    sm: 'px-2 py-1 text-sm rounded-lg',
    md: 'px-3 py-2 text-sm rounded-xl',
    lg: 'px-4 py-2.5 text-base rounded-2xl',
  } as const;
  const variantMap = {
    solid:
      'bg-gray-900 text-white hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 shadow-sm',
    outline:
      'border border-gray-300 text-gray-900 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-100 dark:hover:bg-gray-800',
    ghost:
      'text-gray-900 hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-800',
  } as const;
  return [
    'inline-flex items-center transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900',
    sizeMap[props.size],
    variantMap[props.variant],
  ].join(' ');
});

async function onClick() {
  const ok = await shareViaWebApi(props.title, props.text);
  if (!ok) open.value = true;
}
</script>
