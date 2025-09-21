<script setup lang="ts">
import type { EventPublic } from '../types'
import { formatDateTime } from '../utils/datetime'

const props = defineProps<{ event: EventPublic }>()

function downloadICS() {
  const start = formatDateTime(props.event.starts_at, props.event.timezone, "yyyyMMdd'T'HHmmss")
  const end = formatDateTime(props.event.ends_at, props.event.timezone, "yyyyMMdd'T'HHmmss")

  const ics = `BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SUMMARY:${props.event.title}
DTSTART:${start}
DTEND:${end}
DESCRIPTION:${props.event.description}
END:VEVENT
END:VCALENDAR`

  const blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${props.event.slug}.ics`
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <button
    class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition"
    @click="downloadICS"
  >
    Add to Calendar
  </button>
</template>
