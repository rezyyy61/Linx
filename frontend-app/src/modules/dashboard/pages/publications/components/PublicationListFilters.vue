<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { Icon } from "@iconify/vue";

type OrderBy = "publish_at" | "created_at" | "updated_at";
type OrderDir = "asc" | "desc";

const props = withDefaults(defineProps<{
  search?: string;
  published?: string;
  orderBy?: OrderBy;
  orderDir?: OrderDir;
  perPage?: number;
}>(), {
  search: "",
  published: "",
  orderBy: "publish_at",
  orderDir: "desc",
  perPage: 15,
});

const emit = defineEmits<{
  (e: "update:search", v: string): void;
  (e: "update:published", v: string): void;
  (e: "update:orderBy", v: OrderBy): void;
  (e: "update:orderDir", v: OrderDir): void;
  (e: "update:perPage", v: number): void;
  (e: "reset"): void;
}>();

const { t } = useI18n();

const searchModel    = computed({ get: () => props.search,    set: v => emit("update:search", v) });
const publishedModel = computed({ get: () => props.published, set: v => emit("update:published", v) });
const orderByModel   = computed({ get: () => props.orderBy,   set: v => emit("update:orderBy", v as OrderBy) });
const orderDirModel  = computed({ get: () => props.orderDir,  set: v => emit("update:orderDir", v as OrderDir) });
const perPageModel   = computed({ get: () => props.perPage,   set: v => emit("update:perPage", Number(v)) });

function toggleOrder() {
  orderDirModel.value = orderDirModel.value === "asc" ? "desc" : "asc";
}
</script>

<template>
  <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
    <div class="overflow-x-auto">
      <div class="flex items-center gap-3 whitespace-nowrap min-w-[920px]">
        <div class="flex-1 min-w-[260px]">
          <div class="relative h-10">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
              <Icon
                icon="mdi:magnify"
                class="w-5 h-5"
              />
            </span>
            <input
              v-model="searchModel"
              type="text"
              :placeholder="t('publication.list.searchPlaceholder') || 'Search...'"
              class="h-10 w-full rounded-xl border border-gray-300 pl-10 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
          </div>
        </div>

        <div class="w-52">
          <select
            v-model="publishedModel"
            class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
          >
            <option value="">
              {{ t('publication.filter.all') || 'All' }}
            </option>
            <option value="1">
              {{ t('publication.filter.published') || 'Published' }}
            </option>
            <option value="0">
              {{ t('publication.filter.unpublished') || 'Unpublished' }}
            </option>
          </select>
        </div>

        <div class="w-48">
          <select
            v-model="orderByModel"
            class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
          >
            <option value="publish_at">
              {{ t('publication.list.sort.publish') || 'Publish date' }}
            </option>
            <option value="created_at">
              {{ t('publication.list.sort.created') || 'Created' }}
            </option>
            <option value="updated_at">
              {{ t('publication.list.sort.updated') || 'Updated' }}
            </option>
          </select>
        </div>

        <div class="w-28">
          <button
            type="button"
            class="h-10 w-full inline-flex items-center justify-center gap-1 rounded-xl border px-3 text-sm dark:border-gray-700"
            @click="toggleOrder"
          >
            <Icon
              :icon="orderDirModel==='asc' ? 'mdi:arrow-up' : 'mdi:arrow-down'"
              class="w-4 h-4"
            />
            <span class="uppercase">{{ orderDirModel }}</span>
          </button>
        </div>

        <div class="w-24">
          <select
            v-model.number="perPageModel"
            class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
          >
            <option :value="10">
              10
            </option>
            <option :value="15">
              15
            </option>
            <option :value="25">
              25
            </option>
            <option :value="50">
              50
            </option>
            <option :value="100">
              100
            </option>
          </select>
        </div>

        <div>
          <button
            type="button"
            class="h-10 inline-flex items-center gap-2 rounded-xl px-3 text-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
            @click="$emit('reset')"
          >
            <Icon
              icon="mdi:close"
              class="w-4 h-4"
            />
            <span>{{ t('common.clear') || 'Clear' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
