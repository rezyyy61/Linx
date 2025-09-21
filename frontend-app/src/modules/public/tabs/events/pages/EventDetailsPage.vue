<script setup lang="ts">
import { ref, computed, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { Icon } from "@iconify/vue"
import { DateTime } from "luxon"
import AvatarUser from "@/components/shared/AvatarUser.vue"
import AddToCalendarButton from "../components/AddToCalendarButton.vue"
import StatPills from "../components/StatPills.vue"
import { fetchEvent, getJoinStatus, joinEvent, unjoinEvent } from "../api/events"
import { formatRange, isUpcoming, isOngoing } from "../utils/datetime"
import type { EventPublic } from "../types"
import { ShareModal } from "@/modules/share"

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const notFound = ref(false)
const event = ref<EventPublic | null>(null)
const joined = ref(false)
const goingCount = ref(0)

const kind = computed(() => {
  if (!event.value) return "upcoming"
  if (isOngoing(event.value.starts_at, event.value.ends_at)) return "live"
  if (isUpcoming(event.value.starts_at)) return "upcoming"
  return "past"
})

function copyToClipboard(text: string) {
  const t = text || ''
  try {
    if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
      return navigator.clipboard.writeText(t)
    }
  } catch { /* empty */ }
  const ta = document.createElement('textarea')
  ta.value = t
  ta.setAttribute('readonly', '')
  ta.style.position = 'absolute'
  ta.style.left = '-9999px'
  document.body.appendChild(ta)
  ta.select()
  document.execCommand('copy')
  document.body.removeChild(ta)
}


const typeLabel = computed(() => {
  const t = event.value?.join?.platform || event.value?.settings?.type
  if (!t) return null
  if (t === "zoom") return "Zoom"
  if (t === "meet") return "Google Meet"
  if (t === "custom") return "Online"
  if (t === "in_person") return "In person"
  if (t === "online") return "Online"
  if (t === "hybrid") return "Hybrid"
  return String(t)
})

const visibilityLabel = computed(() => {
  const v = event.value?.settings?.visibility
  if (!v) return null
  return v === "public" ? "Public" : v === "unlisted" ? "Unlisted" : "Private"
})

const canShowJoinInfo = computed(() => {
  const e = event.value
  if (!e?.join?.url && !e?.settings?.join_url) return false
  const minutes = e?.join?.visible_minutes_before ?? e?.settings?.join_visible_minutes_before ?? 60
  const start = DateTime.fromISO(e!.starts_at, { zone: "utc" })
  const threshold = start.minus({ minutes })
  return DateTime.utc() >= threshold
})

function docIconByMime(m?: string) {
  if (!m) return "mdi:file-outline"
  if (m.includes("pdf")) return "mdi:file-pdf-box"
  if (m.includes("word") || m.includes("msword") || m.includes("officedocument.wordprocessingml")) return "mdi:file-word-box"
  if (m.includes("excel") || m.includes("spreadsheet")) return "mdi:file-excel-box"
  if (m.startsWith?.("image/")) return "mdi:file-image"
  return "mdi:file-outline"
}

async function load() {
  loading.value = true
  notFound.value = false
  event.value = null
  try {
    const slug = String(route.params.slug || "")
    const e = await fetchEvent(slug)
    event.value = e
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
  if (event.value?.id) {
    try {
      const s = await getJoinStatus(event.value.id)
      joined.value = s.joined
      goingCount.value = s.count
    } catch {
      joined.value = false
      goingCount.value = event.value.going_count ?? 0
    }
  }
}

function goBack() {
  router.push({ name: "home", query: { tab: "events" } })
}

async function onJoin() {
  if (!event.value?.id) return
  try {
    await joinEvent(event.value.id)
    const s = await getJoinStatus(event.value.id)
    joined.value = s.joined
    goingCount.value = s.count
  } catch (e: any) {
    if (e?.response?.status === 401) {
      router.push({ name: "login", query: { redirect: route.fullPath } })
    }
  }
}

async function onLeave() {
  if (!event.value?.id) return
  try {
    await unjoinEvent(event.value.id)
    const s = await getJoinStatus(event.value.id)
    joined.value = s.joined
    goingCount.value = s.count
  } catch { /* empty */ }
}

const shareOpen = ref(false)

onMounted(load)
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-8 space-y-6">
    <div class="flex items-center justify-between">
      <button
        class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400 hover:underline"
        @click="goBack"
      >
        <Icon
          icon="mdi:arrow-left"
          class="w-4 h-4"
        />
        Back
      </button>
      <button
        class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg ring-1 ring-neutral-300 dark:ring-neutral-700 text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800"
        @click="shareOpen = true"
      >
        <Icon
          icon="mdi:share-variant"
          class="w-4 h-4"
        />
        Share
      </button>
    </div>

    <div
      v-if="loading"
      class="rounded-2xl overflow-hidden bg-white dark:bg-neutral-900"
    >
      <div class="w-full h-72 bg-neutral-200 dark:bg-neutral-800 animate-pulse" />
      <div class="p-6 space-y-4">
        <div class="h-7 w-2/3 bg-neutral-200 dark:bg-neutral-800 rounded animate-pulse" />
        <div class="h-4 w-48 bg-neutral-200 dark:bg-neutral-800 rounded animate-pulse" />
        <div class="h-24 w-full bg-neutral-200 dark:bg-neutral-800 rounded animate-pulse" />
      </div>
    </div>

    <div
      v-else-if="notFound"
      class="text-center py-20 text-neutral-600 dark:text-neutral-400"
    >
      Event not found.
    </div>

    <div
      v-else-if="event"
      class="rounded-2xl overflow-hidden bg-white dark:bg-neutral-900 ring-1 ring-neutral-200/60 dark:ring-neutral-800"
    >
      <div class="relative w-full bg-neutral-100 dark:bg-neutral-800">
        <div class="w-full h-72 flex items-center justify-center">
          <img
            v-if="event.cover_url"
            :src="event.cover_url"
            class="max-h-full max-w-full object-contain"
            alt=""
          >
          <Icon
            v-else
            icon="mdi:image-off-outline"
            class="w-12 h-12 text-neutral-400 dark:text-neutral-500"
          />
        </div>
        <div class="absolute top-3 right-3">
          <button
            class="rounded-full bg-white/95 dark:bg-black/60 backdrop-blur p-2 ring-1 ring-neutral-200 dark:ring-neutral-700 shadow"
            @click="shareOpen = true"
          >
            <Icon
              icon="mdi:share-variant"
              class="w-5 h-5 text-neutral-700 dark:text-neutral-200"
            />
          </button>
        </div>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="md:col-span-2 space-y-6">
            <div class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
              <AvatarUser
                :src="event.organizer?.avatar"
                :name="event.organizer?.name"
                size="sm"
                ring
              />
              <span>{{ event.organizer?.name || "Organizer" }}</span>
            </div>

            <div class="space-y-2">
              <h1 class="text-2xl md:text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                {{ event.title }}
              </h1>
              <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                <div class="inline-flex items-center gap-1">
                  <Icon
                    icon="mdi:calendar-outline"
                    class="w-4 h-4"
                  />
                  <span>{{ formatRange(event.starts_at, event.ends_at, event.timezone) }}</span>
                </div>
                <div
                  v-if="event.location"
                  class="inline-flex items-center gap-1"
                >
                  <Icon
                    icon="mdi:map-marker-outline"
                    class="w-4 h-4"
                  />
                  <span>{{ event.location }}</span>
                </div>
                <div
                  v-if="typeLabel"
                  class="inline-flex items-center gap-1"
                >
                  <Icon
                    :icon="typeLabel==='In person' ? 'mdi:account-group-outline' : 'mdi:video-outline'"
                    class="w-4 h-4"
                  />
                  <span>{{ typeLabel }}</span>
                </div>
                <div
                  v-if="visibilityLabel"
                  class="inline-flex items-center gap-1"
                >
                  <Icon
                    icon="mdi:eye-outline"
                    class="w-4 h-4"
                  />
                  <span>{{ visibilityLabel }}</span>
                </div>
              </div>
            </div>

            <StatPills
              :capacity="event.capacity"
              :going="goingCount"
              :price="event.price"
            />

            <div
              v-if="event.description"
              class="prose prose-sm md:prose-base dark:prose-invert max-w-none"
            >
              <p class="text-neutral-700 dark:text-neutral-300">
                {{ event.description }}
              </p>
            </div>

            <div
              v-if="event.documents?.length"
              class="space-y-3"
            >
              <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                Documents
              </div>
              <ul class="divide-y divide-neutral-200 dark:divide-neutral-800 rounded-xl overflow-hidden ring-1 ring-neutral-200 dark:ring-neutral-800">
                <li
                  v-for="d in event.documents"
                  :key="d.id"
                  class="flex items-center gap-3 p-3"
                >
                  <Icon
                    :icon="docIconByMime(d.mime)"
                    class="w-5 h-5 text-neutral-500"
                  />
                  <a
                    :href="d.url"
                    target="_blank"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline break-all"
                  >
                    {{ d.title || ("Document #" + d.id) }}
                  </a>
                </li>
              </ul>
            </div>
          </div>

          <aside class="md:col-span-1 md:sticky md:top-6 space-y-4">
            <div class="rounded-xl ring-1 ring-neutral-200 dark:ring-neutral-800 p-4 space-y-4 bg-white dark:bg-neutral-900">
              <div class="flex items-center justify-between">
                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                  Attend
                </div>
                <div
                  v-if="kind==='live'"
                  class="flex items-center gap-1 text-green-600 text-xs"
                >
                  <Icon
                    icon="mdi:circle"
                    class="w-3 h-3 animate-pulse"
                  />
                  Live
                </div>
              </div>
              <div class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                <Icon
                  icon="mdi:account-multiple-outline"
                  class="w-4 h-4"
                />
                <span>{{ goingCount }} going</span>
                <span
                  v-if="event.capacity"
                  class="mx-1"
                >·</span>
                <span
                  v-if="event.capacity"
                  class="text-neutral-500"
                >Cap {{ event.capacity }}</span>
              </div>
              <div class="flex items-center gap-2">
                <button
                  v-if="isUpcoming(event.starts_at) && !joined"
                  class="flex-1 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700"
                  @click="onJoin"
                >
                  Join
                </button>
                <button
                  v-else-if="isUpcoming(event.starts_at) && joined"
                  class="flex-1 px-4 py-2 rounded-lg bg-red-600 text-white text-sm hover:bg-red-700"
                  @click="onLeave"
                >
                  Leave
                </button>
                <AddToCalendarButton :event="event" />
              </div>
              <div
                v-if="canShowJoinInfo"
                class="rounded-lg border border-neutral-200 dark:border-neutral-800 p-3 space-y-2"
              >
                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                  Join info
                </div>
                <div
                  v-if="event.join?.url || event.settings?.join_url"
                  class="flex items-center gap-2"
                >
                  <a
                    :href="(event.join?.url || event.settings?.join_url)!"
                    target="_blank"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline break-all"
                  >
                    {{ event.join?.url || event.settings?.join_url }}
                  </a>
                  <button
                    class="px-2 py-1 rounded-md text-xs ring-1 ring-neutral-300 dark:ring-neutral-700 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    @click="copyToClipboard(event.join?.url || event.settings?.join_url || '')"
                  >
                    Copy
                  </button>
                </div>
                <div
                  v-if="event.join?.passcode || event.settings?.join_passcode"
                  class="text-xs text-neutral-700 dark:text-neutral-300"
                >
                  Passcode: <span class="font-mono">{{ event.join?.passcode || event.settings?.join_passcode }}</span>
                </div>
                <div
                  v-if="event.settings?.join_instructions"
                  class="text-xs text-neutral-600 dark:text-neutral-400 whitespace-pre-wrap"
                >
                  {{ event.settings.join_instructions }}
                </div>
              </div>
            </div>

            <div class="rounded-xl ring-1 ring-neutral-200 dark:ring-neutral-800 p-4 space-y-2 bg-white dark:bg-neutral-900">
              <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                Event info
              </div>
              <div class="text-xs text-neutral-600 dark:text-neutral-400 flex items-center gap-2">
                <Icon
                  icon="mdi:earth"
                  class="w-4 h-4"
                />
                <span>{{ event.timezone }}</span>
              </div>
              <div
                v-if="typeLabel"
                class="text-xs text-neutral-600 dark:text-neutral-400 flex items-center gap-2"
              >
                <Icon
                  :icon="typeLabel==='In person' ? 'mdi:account-group-outline' : 'mdi:video-outline'"
                  class="w-4 h-4"
                />
                <span>{{ typeLabel }}</span>
              </div>
              <div
                v-if="visibilityLabel"
                class="text-xs text-neutral-600 dark:text-neutral-400 flex items-center gap-2"
              >
                <Icon
                  icon="mdi:eye-outline"
                  class="w-4 h-4"
                />
                <span>{{ visibilityLabel }}</span>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </div>

    <button
      class="md:hidden fixed bottom-6 right-6 z-50 rounded-full shadow-lg bg-indigo-600 hover:bg-indigo-700 text-white p-4"
      @click="shareOpen = true"
    >
      <Icon
        icon="mdi:share-variant"
        class="w-6 h-6"
      />
    </button>

    <ShareModal
      v-if="event"
      :open="shareOpen"
      :shareable-alias="'event'"
      :shareable-type="'App\\\\Models\\\\Event\\\\Event'"
      :shareable-id="event.id"
      :event="event"
      :title="event.title"
      :text="event.description"
      @close="shareOpen = false"
      @shared="shareOpen = false"
    />
  </section>
</template>
