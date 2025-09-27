<script setup lang="ts">
import { computed, onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import type { EventPublic } from "../types"
import { formatRange, isOngoing, isUpcoming } from "../utils/datetime"
import { useEvents } from "../composables/useEvents"
import { Icon } from "@iconify/vue"
import AvatarUser from "@/components/shared/AvatarUser.vue"
import StatusBadge from "./StatusBadge.vue"
import { ShareModal } from "@/modules/share"

const props = defineProps<{ event: EventPublic }>()
const router = useRouter()
const { ensureJoinStatus, isJoined, join, unjoin, toggleBookmark, isBookmarked } = useEvents()

const joined = computed(() => isJoined(props.event.id))
const kind = computed(() => {
  if (isOngoing(props.event.starts_at, props.event.ends_at)) return "live"
  if (isUpcoming(props.event.starts_at)) return "upcoming"
  return "past"
})
const hasProfileRoute = computed(() => router.hasRoute("profile.public") && !!props.event.organizer?.slug)

onMounted(() => ensureJoinStatus(props.event.id))

async function onJoin() {
  try { await join(props.event.id) }
  catch (e: any) { if (e?.response?.status === 401) router.push({ name: "login", query: { redirect: `/events/${props.event.slug}` } }) }
}
async function onLeave() { try { await unjoin(props.event.id) } catch { /* empty */ } }

const shareOpen = ref(false)
</script>

<template>
  <article class="rounded-2xl bg-white dark:bg-neutral-900 ring-1 ring-neutral-200/60 dark:ring-neutral-800 hover:shadow-md transition overflow-hidden flex flex-col">
    <div class="relative">
      <div class="w-full h-52 bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center">
        <img
          v-if="event.cover_url"
          :src="event.cover_url"
          alt=""
          class="max-h-full max-w-full object-contain"
        >
        <Icon
          v-else
          icon="mdi:image-off-outline"
          class="w-12 h-12 text-neutral-400 dark:text-neutral-500"
        />
      </div>
      <div class="absolute top-3 left-3">
        <StatusBadge :kind="kind as any" />
      </div>
      <button
        class="absolute top-3 right-3 rounded-full bg-white/90 dark:bg-black/60 backdrop-blur p-2 ring-1 ring-neutral-200 dark:ring-neutral-700"
        @click.stop="toggleBookmark(event.id)"
      >
        <Icon
          :icon="isBookmarked(event.id) ? 'mdi:bookmark' : 'mdi:bookmark-outline'"
          class="w-5 h-5 text-indigo-600 dark:text-indigo-400"
        />
      </button>
    </div>

    <div class="p-5 flex-1 flex flex-col gap-4">
      <div class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
        <AvatarUser
          :src="event.organizer?.avatar"
          :name="event.organizer?.name"
          size="sm"
          ring
        />
        <template v-if="hasProfileRoute">
          <router-link
            :to="{ name: 'profile.public', params: { slug: event.organizer!.slug } }"
            class="hover:underline"
          >
            {{ event.organizer?.name || 'Organizer' }}
          </router-link>
        </template>
        <template v-else>
          <span>{{ event.organizer?.name || 'Organizer' }}</span>
        </template>
      </div>

      <h3 class="text-lg md:text-xl font-semibold leading-tight text-neutral-900 dark:text-neutral-100 line-clamp-2 min-h-[3.25rem]">
        {{ event.title }}
      </h3>

      <template v-if="event.description">
        <p class="text-sm text-neutral-600 dark:text-neutral-400 line-clamp-2 min-h-[2.5rem]">
          {{ event.description }}
        </p>
      </template>
      <template v-else>
        <div class="min-h-[2.5rem]" />
      </template>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-neutral-600 dark:text-neutral-400">
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
          <span class="truncate max-w-[10rem]">{{ event.location }}</span>
        </div>
        <div
          v-if="event.going_count != null"
          class="inline-flex items-center gap-1"
        >
          <Icon
            icon="mdi:account-multiple-outline"
            class="w-4 h-4"
          />
          <span>{{ event.going_count }} going</span>
        </div>
        <div
          v-if="event.capacity"
          class="inline-flex items-center gap-1"
        >
          <Icon
            icon="mdi:seat-outline"
            class="w-4 h-4"
          />
          <span>{{ event.capacity }}</span>
        </div>
      </div>

      <div class="mt-auto pt-2 flex items-center justify-between">
        <router-link
          :to="{ name: 'events.details', params: { slug: event.slug } }"
          class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
        >
          Details
        </router-link>

        <div class="flex items-center gap-2">
          <button
            class="px-3 py-1.5 rounded-lg text-sm ring-1 ring-neutral-300 dark:ring-neutral-700 hover:bg-neutral-100 dark:hover:bg-neutral-800"
            @click.stop="shareOpen = true"
          >
            Share
          </button>

          <button
            v-if="isUpcoming(event.starts_at) && !joined"
            class="px-3 py-1.5 rounded-lg text-sm bg-indigo-600 text-white hover:bg-indigo-700"
            @click="onJoin"
          >
            Join
          </button>

          <button
            v-else-if="isUpcoming(event.starts_at) && joined"
            class="px-3 py-1.5 rounded-lg text-sm bg-red-600 text-white hover:bg-red-700"
            @click="onLeave"
          >
            Leave
          </button>

          <span
            v-else-if="kind==='live'"
            class="flex items-center gap-1 text-green-600 text-sm"
          >
            <Icon
              icon="mdi:circle"
              class="w-3 h-3 animate-pulse"
            />
            Live
          </span>
        </div>
      </div>
    </div>
  </article>

  <ShareModal
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
</template>
