<script setup lang="ts">
import {ref, watchEffect} from 'vue'
import { Icon } from '@iconify/vue'
import EventCard from '@/modules/public/tabs/events/components/EventCard.vue'
import { api } from '@/lib/http'
import { createRepostGeneric } from '@/modules/share/services/repostsApi'

interface Props { open: boolean; eventId: number; initialEvent?: any }
const props = defineProps<Props>()
const emit = defineEmits<{ (e:'close'):void; (e:'done', r:{id:number;url:string}):void }>()

const text = ref(''); const loading = ref(false); const errorMsg = ref('')
const previewEvent = ref<any>(null)

function normalizeApiEvent(api:any){
  return {
    id: Number(api.id),
    slug: api.slug || String(api.id),
    title: api.title || '',
    description: api.description || '',
    cover_url: api.cover_url ?? null,
    starts_at: api.starts_at ?? null,
    ends_at: api.ends_at ?? null,
    timezone: api.timezone ?? 'UTC',
    location: api.location ?? null,
    organizer: api.organizer ?? { name: 'Organizer' },
    going_count: api.going_count ?? null,
    capacity: api.capacity ?? null,
  }
}


watchEffect(() => {
  if (!props.open) return

  if (props.initialEvent) {
    previewEvent.value = normalizeApiEvent(props.initialEvent)
    return
  }

  if (props.eventId) {
    api.get(`/public/v1/events/${props.eventId}`)
      .then(({ data }) => {
        previewEvent.value = normalizeApiEvent(data?.data ?? data)
      })
      .catch(() => { previewEvent.value = null })
  }
})

async function onSubmit(){
  loading.value = true; errorMsg.value = ''
  try {
    const idForApi = props.initialEvent?.id ?? props.eventId
    const r = await createRepostGeneric({
      shareable_alias: 'event',
      shareable_id: idForApi,
      text: text.value || null,
      visibility: 'public'
    })
    emit('done', r)
  } catch { errorMsg.value = 'Failed' } finally { loading.value = false }
}
</script>

<template>
  <transition
    appear
    enter-active-class="transition duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="open"
      class="fixed inset-0 z-[60]"
    >
      <div
        class="absolute inset-0 bg-black/50 dark:bg-black/70"
        @click="emit('close')"
      />
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 shadow-xl ring-1 ring-black/5 dark:ring-white/10 flex max-h-[85vh] flex-col">
          <div class="flex items-center justify-between px-5 py-2 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              Repost Event
            </h3>
            <button
              class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="emit('close')"
            >
              <Icon
                icon="mdi:close"
                class="h-5 w-5 text-gray-500 dark:text-gray-400"
              />
            </button>
          </div>

          <div class="px-5 py-4 space-y-4 flex-1 min-h-0 overflow-hidden">
            <div
              v-if="previewEvent"
              class="flex-1 max-h-[45vh] overflow-y-auto rounded-xl border"
            >
              <EventCard
                :key="previewEvent.id"
                :event="previewEvent"
              />
            </div>

            <div class="space-y-2 shrink-0">
              <textarea
                v-model="text"
                rows="4"
                dir="auto"
                maxlength="5000"
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 resize-y"
                placeholder="Add a note…"
              />
              <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ text.length }}/5000</span>
                <span
                  v-if="errorMsg"
                  class="text-red-600 dark:text-red-400"
                >{{ errorMsg }}</span>
              </div>
            </div>
          </div>

          <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
            <button
              class="px-3 py-2 text-sm rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="emit('close')"
            >
              Cancel
            </button>
            <button
              class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-xl bg-indigo-600 text-white hover:bg-indigo-500 disabled:opacity-60"
              :disabled="loading"
              @click="onSubmit"
            >
              <Icon
                icon="mdi:repeat-variant"
                class="h-5 w-5"
              />
              <span>Repost</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>
