<script setup lang="ts">
import { computed } from "vue";
import { Icon } from "@iconify/vue";
import type { Publication } from "../types";

const props = defineProps<{ item: Publication }>();

const df = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" });
const when = computed(() => props.item.publish_at ? df.format(new Date(props.item.publish_at)) : "");
</script>

<template>
  <div class="group rounded-2xl border border-neutral-200/90 dark:border-neutral-800/80 bg-white dark:bg-neutral-900 overflow-hidden hover:shadow-md transition">
    <div class="relative aspect-[3/4] bg-gradient-to-br from-neutral-100 to-neutral-200 dark:from-neutral-800 dark:to-neutral-900 flex items-center justify-center">
      <img
        v-if="item.cover_url"
        :src="item.cover_url"
        class="h-full w-full object-cover"
      >
      <Icon
        v-else
        icon="mdi:newspaper-variant-outline"
        class="w-10 h-10 text-neutral-400"
      />
      <span class="absolute top-3 right-3 text-[10px] px-2 py-1 rounded-full bg-black/70 text-white uppercase">{{ item.language }}</span>
    </div>

    <div class="p-4 space-y-2">
      <div class="font-semibold line-clamp-2">
        {{ item.title }}
      </div>
      <div class="text-xs text-neutral-500">
        Issue: {{ item.issue }}
      </div>
      <div
        v-if="when"
        class="text-xs text-neutral-500"
      >
        Published: {{ when }}
      </div>

      <div class="flex items-center justify-between pt-1">
        <div
          v-if="item.owner"
          class="flex items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300"
        >
          <div class="h-6 w-6 rounded-full bg-neutral-200 dark:bg-neutral-700 overflow-hidden flex items-center justify-center">
            <Icon
              icon="mdi:account"
              class="w-4 h-4 text-neutral-505"
            />
          </div>
          <span class="truncate max-w-[140px]">{{ item.owner.name }}</span>
          <Icon
            v-if="item.owner.verified"
            icon="mdi:check-decagram"
            class="w-4 h-4 text-indigo-600 dark:text-indigo-400"
          />
        </div>

        <router-link
          :to="{ name:'public.publication.detail', params:{ slug:item.slug } }"
          class="ml-auto inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline"
        >
          <Icon
            icon="mdi:eye"
            class="w-4 h-4"
          />
          <span>View</span>
        </router-link>
      </div>
    </div>
  </div>
</template>
