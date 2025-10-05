<!-- /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/public/tabs/publication/pages/PublicationDetailsPage.vue -->
<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue"
import { Icon } from "@iconify/vue"
import { useRoute, useRouter } from "vue-router"
import { fetchPublication } from "../api"
import type { Publication } from "../types"
import { ShareModal } from "@/modules/share"

const route = useRoute()
const router = useRouter()
const slug = computed(() => String(route.params.slug || ""))

const item = ref<Publication | null>(null)
const loading = ref(false)
const hardReloading = ref(false)
const notFound = ref(false)
const isShareOpen = ref(false)

async function loadBySlug(s: string) {
  if (!s) return
  item.value ? (hardReloading.value = true) : (loading.value = true)
  notFound.value = false
  try {
    const data = await fetchPublication(s)
    if (!data) {
      notFound.value = true
      item.value = null
    } else {
      item.value = data as Publication
    }
  } finally {
    loading.value = false
    hardReloading.value = false
  }
}

onMounted(() => loadBySlug(slug.value))
watch(slug, async (s, p) => {
  if (s === p) return
  await loadBySlug(s)
  window.scrollTo({ top: 0, behavior: "auto" })
})

function goBack() {
  router.push({ name: "home", query: { tab: "publication" } })
}

const df = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" })
const cover = computed(() => item.value?.cover_url || "")
const title = computed(() => item.value?.title || "")
const issue = computed(() => item.value?.issue || "")
const language = computed(() => item.value?.language || "")
const isPublished = computed(() => !!item.value?.is_published)
const publishAt = computed(() => (item.value?.publish_at ? df.format(new Date(item.value.publish_at)) : ""))
const ownerName = computed(() => item.value?.owner?.name || "")
const ownerAvatar = computed(() => item.value?.owner?.avatar_url || "")
const isVerified = computed(() => !!item.value?.owner?.verified)

const rtlSet = new Set(["fa", "ar", "ur", "he", "ps", "ku"])
const isRTL = computed(() => rtlSet.has((language.value || "").toLowerCase()))
const contentDir = computed<"ltr" | "rtl">(() => (isRTL.value ? "rtl" : "ltr"))

function safeFileName(url: string) {
  try { return decodeURIComponent(new URL(url).pathname.split("/").pop() || "document") } catch { return "document" }
}
function hostFrom(url: string) {
  try { return new URL(url).host } catch { return "" }
}
function extOf(url: string) {
  const n = safeFileName(url)
  const p = n.lastIndexOf(".")
  return p >= 0 ? n.slice(p + 1).toLowerCase() : ""
}
function iconFor(ext: string) {
  if (["pdf"].includes(ext)) return "mdi:file-pdf-box"
  if (["doc", "docx", "rtf", "odt"].includes(ext)) return "mdi:file-word-box"
  if (["xls", "xlsx", "csv", "ods"].includes(ext)) return "mdi:file-excel-box"
  if (["ppt", "pptx", "odp", "key"].includes(ext)) return "mdi:file-powerpoint"
  if (["zip", "rar", "7z", "gz", "tar"].includes(ext)) return "mdi:folder-zip"
  if (["png", "jpg", "jpeg", "webp", "gif", "svg"].includes(ext)) return "mdi:file-image"
  if (["txt", "md"].includes(ext)) return "mdi:file-document-outline"
  return "mdi:file"
}
function extBadgeClass(ext: string) {
  if (ext === "pdf") return "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300"
  if (["doc","docx","rtf","odt"].includes(ext)) return "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
  if (["xls","xlsx","csv","ods"].includes(ext)) return "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
  if (["ppt","pptx","odp","key"].includes(ext)) return "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
  if (["zip","rar","7z","gz","tar"].includes(ext)) return "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300"
  if (["png","jpg","jpeg","webp","gif","svg"].includes(ext)) return "bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-300"
  return "bg-neutral-100 text-neutral-700 dark:bg-neutral-800/60 dark:text-neutral-300"
}
const documents = computed(() =>
  (item.value as any)?.documents?.map((d: any) => {
    const url = String(d.url || "")
    const ext = extOf(url)
    return {
      id: Number(d.id || 0) || url,
      url,
      name: safeFileName(url),
      host: hostFrom(url),
      ext,
      icon: iconFor(ext),
      badgeClass: extBadgeClass(ext),
      downloadName: safeFileName(url)
    }
  }) || []
)
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 py-6 space-y-8">
    <div class="flex items-center justify-between">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
        @click="goBack"
      >
        <Icon
          icon="mdi:arrow-left"
          class="w-4 h-4"
        />
        <span>Back to publications</span>
      </button>
      <button
        v-if="item"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
        @click="isShareOpen = true"
      >
        <Icon
          icon="mdi:share-variant"
          class="w-4 h-4"
        />
        <span>Share</span>
      </button>
    </div>

    <div
      v-if="loading || hardReloading"
      class="rounded-3xl overflow-hidden bg-gradient-to-br from-neutral-100 to-white dark:from-neutral-900 dark:to-neutral-950 ring-1 ring-neutral-200/70 dark:ring-neutral-800/70"
    >
      <div class="h-48 bg-neutral-200/70 dark:bg-neutral-800/70 animate-pulse" />
      <div class="p-6 space-y-4">
        <div class="h-7 w-2/3 bg-neutral-200 dark:bg-neutral-800 rounded animate-pulse" />
        <div class="h-4 w-1/3 bg-neutral-200 dark:bg-neutral-800 rounded animate-pulse" />
        <div class="h-32 bg-neutral-200 dark:bg-neutral-800 rounded-xl animate-pulse" />
      </div>
    </div>

    <div
      v-else-if="notFound"
      class="rounded-2xl border border-red-200 bg-red-50 p-8 dark:border-red-900/40 dark:bg-red-950/40"
    >
      <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
        <Icon
          icon="mdi:alert-circle-outline"
          class="w-5 h-5"
        />
        <span>Publication not found</span>
      </div>
    </div>

    <div
      v-else-if="item"
      class="space-y-8"
    >
      <div class="relative rounded-3xl overflow-hidden ring-1 ring-neutral-200/70 dark:ring-neutral-800/70">
        <div class="relative">
          <div class="absolute inset-0">
            <img
              v-if="cover"
              :src="cover"
              class="h-full w-full object-cover blur-lg scale-110 opacity-60"
              alt=""
            >
            <div
              v-else
              class="h-full w-full bg-gradient-to-r from-emerald-500/20 to-blue-500/20 dark:from-emerald-400/10 dark:to-blue-400/10"
            />
          </div>
          <div class="relative p-4 sm:p-6 md:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
              <div class="lg:col-span-2">
                <div class="rounded-2xl backdrop-blur bg-white/80 dark:bg-neutral-900/70 ring-1 ring-white/40 dark:ring-neutral-700/60 p-4 sm:p-6 md:p-7">
                  <div
                    class="flex flex-wrap items-center gap-2 text-[11px]"
                    :dir="contentDir"
                  >
                    <span class="px-2 py-0.5 rounded-full bg-black/80 text-white">{{ language }}</span>
                    <span
                      v-if="issue"
                      class="px-2 py-0.5 rounded-full bg-neutral-100 dark:bg-neutral-800"
                    >Issue {{ issue }}</span>
                    <span
                      class="px-2 py-0.5 rounded-full"
                      :class="isPublished ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'"
                    >
                      {{ isPublished ? 'Published' : 'Draft' }}
                    </span>
                    <span
                      v-if="publishAt"
                      class="text-neutral-400"
                    >•</span>
                    <span
                      v-if="publishAt"
                      class="inline-flex items-center gap-1 text-neutral-700 dark:text-neutral-300"
                    >
                      <Icon
                        icon="mdi:calendar"
                        class="w-4 h-4"
                      />
                      {{ publishAt }}
                    </span>
                  </div>

                  <h1
                    class="mt-3 text-3xl md:text-4xl font-extrabold leading-tight tracking-tight text-neutral-900 dark:text-neutral-100"
                    :dir="contentDir"
                  >
                    {{ title }}
                  </h1>

                  <div class="mt-4 flex items-center justify-between">
                    <div
                      class="flex items-center gap-3"
                      :dir="contentDir"
                    >
                      <img
                        v-if="ownerAvatar"
                        :src="ownerAvatar"
                        class="h-10 w-10 rounded-full object-cover"
                      >
                      <div class="min-w-0">
                        <div class="flex items-center gap-1 text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                          <span class="truncate">{{ ownerName }}</span>
                          <Icon
                            v-if="isVerified"
                            icon="mdi:check-decagram"
                            class="w-4 h-4 text-emerald-600"
                          />
                        </div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">
                          Publisher
                        </div>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-neutral-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
                      @click="isShareOpen = true"
                    >
                      <Icon
                        icon="mdi:share-variant"
                        class="w-4 h-4"
                      />
                      <span>Share</span>
                    </button>
                  </div>
                  <!-- eslint-disable vue/no-v-html -->
                  <div
                    v-if="item.description"
                    class="mt-6 prose prose-sm md:prose-base dark:prose-invert max-w-none leading-7"
                    :dir="contentDir"
                    v-html="item.description"
                  />
                </div>
              </div>

              <div class="lg:col-span-1">
                <div class="rounded-2xl h-full overflow-hidden ring-1 ring-white/40 dark:ring-neutral-700/60 backdrop-blur bg-white/80 dark:bg-neutral-900/70 p-3 md:p-4 flex items-center justify-center">
                  <div class="relative w-full overflow-hidden rounded-xl bg-neutral-100 dark:bg-neutral-800 p-2 aspect-[4/3] sm:aspect-[16/10]">
                    <img
                      v-if="cover"
                      :src="cover"
                      alt=""
                      class="w-full h-full object-contain block"
                      loading="lazy"
                      decoding="async"
                    >
                    <Icon
                      v-else
                      icon="mdi:image-off-outline"
                      class="absolute inset-0 m-auto w-10 h-10 text-neutral-400"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2" />
        <aside class="lg:col-span-1">
          <div class="rounded-2xl ring-1 ring-neutral-200/70 dark:ring-neutral-800/70 bg-white dark:bg-neutral-900 p-5 space-y-5">
            <div class="flex items-center justify-between">
              <div class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                Files
              </div>
              <div
                v-if="documents.length"
                class="text-xs text-neutral-500 dark:text-neutral-400"
              >
                {{ documents.length }}
              </div>
            </div>

            <div
              v-if="documents.length"
              class="grid grid-cols-1 gap-3"
            >
              <div
                v-for="d in documents"
                :key="d.id"
                class="group rounded-2xl border border-neutral-200 dark:border-neutral-700 p-3 hover:shadow-sm hover:border-neutral-300 dark:hover:border-neutral-600 transition bg-white/70 dark:bg-neutral-900/70"
              >
                <div class="flex items-start gap-3">
                  <div class="h-12 w-12 rounded-xl bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center ring-1 ring-neutral-200/70 dark:ring-neutral-700/70 flex-shrink-0">
                    <Icon
                      :icon="d.icon"
                      class="w-6 h-6 text-neutral-600 dark:text-neutral-300"
                    />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                      <div
                        class="truncate text-sm font-medium text-neutral-800 dark:text-neutral-100"
                        :dir="contentDir"
                      >
                        {{ d.name }}
                      </div>
                      <span
                        class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase"
                        :class="d.badgeClass"
                      >{{ d.ext || 'file' }}</span>
                    </div>
                    <div class="text-xs text-neutral-500 dark:text-neutral-400 truncate">
                      {{ d.host }}
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                      <a
                        :href="d.url"
                        target="_blank"
                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs hover:bg-neutral-50 dark:hover:bg-neutral-800 dark:border-neutral-700"
                      >
                        <Icon
                          icon="mdi:open-in-new"
                          class="w-4 h-4"
                        />
                        Open
                      </a>
                      <a
                        :href="d.url"
                        :download="d.downloadName"
                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs hover:bg-neutral-50 dark:hover:bg-neutral-800 dark:border-neutral-700"
                      >
                        <Icon
                          icon="mdi:download"
                          class="w-4 h-4"
                        />
                        Download
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-else
              class="text-xs text-neutral-500 dark:text-neutral-400"
            >
              No documents
            </div>
          </div>
        </aside>
      </div>
    </div>

    <ShareModal
      v-if="item"
      :open="isShareOpen"
      shareable-type="App\\Models\\Publication\\Publication"
      shareable-alias="publication"
      :shareable-id="Number(item?.id || 0)"
      :publication="item"
      :title="item?.title || 'Publication'"
      :text="item?.description || item?.title || 'Publication'"
      @close="isShareOpen = false"
      @shared="isShareOpen = false"
    />
  </section>
</template>
