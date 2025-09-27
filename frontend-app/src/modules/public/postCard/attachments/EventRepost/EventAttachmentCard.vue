<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useRouter } from 'vue-router'
import { formatRange, isUpcoming } from '@/modules/public/tabs/events/utils/datetime'
import { useEvents } from '@/modules/public/tabs/events/composables/useEvents'

type EventPreview = {
  id: number
  slug: string
  title: string
  description?: string | null
  cover_url?: string | null
  starts_at?: string | null
  ends_at?: string | null
  timezone?: string | null
  location?: string | null
  organizer?: { name?: string | null; slug?: string | null; avatar?: string | null } | null
  going_count?: number | null
  capacity?: number | null
}

const props = defineProps<{ event: EventPreview }>()
const router = useRouter()
const { ensureJoinStatus, isJoined, join, unjoin } = useEvents()

const when = computed(() =>
  formatRange(
    props.event.starts_at ?? '',
    props.event.ends_at ?? '',
    props.event.timezone ?? 'UTC'
  )
)

const organizerName = computed(() => (props.event.organizer?.name?.trim() ? props.event.organizer!.name! : 'Organizer'))
const joined = computed(() => isJoined(props.event.id))
const canJoin = computed(() => !!props.event.starts_at && isUpcoming(props.event.starts_at))

onMounted(() => ensureJoinStatus(props.event.id))

function open() {
  if (!props.event?.slug) return
  router.push({ name: 'events.details', params: { slug: props.event.slug } })
}

async function onJoin(e: MouseEvent) {
  e.stopPropagation()
  try { await join(props.event.id) } catch { /* empty */ }
}

async function onLeave(e: MouseEvent) {
  e.stopPropagation()
  try { await unjoin(props.event.id) } catch { /* empty */ }
}

</script>

<template>
  <div class="mt-3">
    <button
      class="w-full text-left rounded-xl bg-zinc-100 dark:bg-zinc-900 ring-1 ring-zinc-200 dark:ring-zinc-800 overflow-hidden hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition"
      @click="open"
    >
      <div class="flex gap-3 p-3">
        <div class="h-16 w-16 flex-shrink-0 rounded-lg overflow-hidden ring-1 ring-zinc-200 dark:ring-zinc-700 bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
          <img
            v-if="event.cover_url"
            :src="event.cover_url"
            class="h-full w-full object-cover"
            alt=""
          >
          <Icon
            v-else
            icon="mdi:image-off-outline"
            class="w-6 h-6 text-zinc-400"
          />
        </div>

        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 line-clamp-2">
            {{ event.title }}
          </div>

          <div
            v-if="when"
            class="mt-1 text-xs text-zinc-600 dark:text-zinc-400 inline-flex items-center gap-1"
          >
            <Icon
              icon="mdi:calendar-outline"
              class="w-4 h-4"
            />
            <span class="truncate">{{ when }}</span>
          </div>

          <div
            v-if="event.location"
            class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400 inline-flex items-center gap-1"
          >
            <Icon
              icon="mdi:map-marker-outline"
              class="w-4 h-4"
            />
            <span class="truncate">{{ event.location }}</span>
          </div>

          <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400 min-w-0">
              <div class="inline-flex items-center gap-1 min-w-0">
                <Icon
                  icon="mdi:account-outline"
                  class="w-3.5 h-3.5"
                />
                <span class="truncate max-w-[12rem]">by {{ organizerName }}</span>
              </div>
              <div
                v-if="event.going_count != null"
                class="inline-flex items-center gap-1"
              >
                <Icon
                  icon="mdi:account-multiple-outline"
                  class="w-3.5 h-3.5"
                />
                <span>{{ event.going_count }}</span>
              </div>
              <div
                v-if="event.capacity"
                class="inline-flex items-center gap-1"
              >
                <Icon
                  icon="mdi:seat-outline"
                  class="w-3.5 h-3.5"
                />
                <span>{{ event.capacity }}</span>
              </div>
            </div>

            <div class="shrink-0 flex items-center gap-2">
              <button
                v-if="canJoin && !joined"
                class="px-2.5 py-1 text-[11px] rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"
                @click="onJoin"
              >
                Join
              </button>
              <button
                v-else-if="canJoin && joined"
                class="px-2.5 py-1 text-[11px] rounded-lg bg-red-600 text-white hover:bg-red-700"
                @click="onLeave"
              >
                Leave
              </button>
              <div class="px-1 text-[11px] font-medium text-indigo-600 dark:text-indigo-400">
                View
              </div>
            </div>
          </div>
        </div>
      </div>
    </button>
  </div>
</template>
