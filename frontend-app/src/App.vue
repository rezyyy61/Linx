<script setup lang="ts">
import { ref, onMounted } from 'vue'

type Ping = { ok: boolean; time: string }

const loading = ref(false)
const data = ref<Ping | null>(null)
const error = ref<string | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    const res = await fetch('/api/ping', { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    data.value = (await res.json()) as Ping
  } catch (e: any) {
    error.value = e?.message ?? String(e)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-slate-800 w-full max-w-md">
      <h1 class="text-xl font-semibold text-emerald-700">app frontend</h1>

      <p class="mt-2 text-sm text-slate-600">
        Tailwind <span class="text-emerald-600 font-semibold">OK?</span>
      </p>

      <div class="mt-4">
        <button
            class="rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-50"
            :disabled="loading"
            @click="load"
        >
          {{ loading ? 'Loading…' : 'Call /api/ping' }}
        </button>
      </div>

      <div class="mt-4 space-y-2">
        <div v-if="error" class="text-red-600 text-sm">Error: {{ error }}</div>
        <pre v-else-if="data" class="text-xs bg-slate-50 p-3 rounded border border-slate-200">{{ data }}</pre>
        <div v-else class="text-slate-500 text-sm">برای تست روی دکمه بزن.</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* همه چیز با Tailwind هندل می‌شود */
</style>
