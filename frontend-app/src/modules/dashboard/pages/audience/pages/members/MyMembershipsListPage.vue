<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-lg font-semibold">
        My Memberships
      </h1>
      <div class="flex items-center gap-2">
        <select
          v-model="status"
          class="rounded-lg border border-slate-300/70 dark:border-slate-700 bg-white/90 dark:bg-slate-900/60 px-3 py-1.5 text-sm"
        >
          <option :value="null">
            All
          </option>
          <option :value="MembershipStatus.PENDING">
            Pending
          </option>
          <option :value="MembershipStatus.ACCEPTED">
            Accepted
          </option>
          <option :value="MembershipStatus.REJECTED">
            Rejected
          </option>
          <option :value="MembershipStatus.BLOCKED">
            Blocked
          </option>
        </select>
        <button
          class="rounded border px-3 py-1.5 text-xs dark:border-slate-700"
          :disabled="loading"
          @click="refresh"
        >
          Refresh
        </button>
      </div>
    </div>

    <div
      v-if="loading"
      class="text-sm text-slate-500 dark:text-slate-400"
    >
      Loading…
    </div>

    <div
      v-else-if="!items.length && !error"
      class="rounded-2xl border border-dashed p-10 text-center text-sm dark:border-slate-700"
    >
      You’re not a member anywhere yet
    </div>

    <ul
      v-else-if="items.length"
      class="space-y-3"
    >
      <li
        v-for="m in items"
        :key="m.id"
        class="p-4 rounded-xl border dark:border-slate-700 bg-white/60 dark:bg-zinc-900/40"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start gap-3 min-w-0">
            <AvatarUser
              :src="ownerAvatar(m)"
              :name="displayOwner(m)"
              :color="ownerAvatarColor(m)"
              size="md"
              rounded="full"
              ring
              ring-color="indigo"
              zoomable
            />
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <div class="font-medium truncate">
                  {{ displayOwner(m) }}
                </div>
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] border"
                  :class="statusPillClass(m.status)"
                >{{ m.status }}</span>
              </div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-2">
                @{{ ownerSlug(m) }}
                <span v-if="m.consent_at"> · since {{ formatDate(m.consent_at) }}</span>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <template v-if="m.status === MembershipStatus.PENDING">
              <button
                class="px-3 py-1.5 text-xs rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60"
                :disabled="busyId === m.id"
                @click="confirmAccept(m)"
              >
                Accept
              </button>
              <button
                class="px-3 py-1.5 text-xs rounded-lg border text-rose-600 border-rose-300 dark:border-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20 disabled:opacity-60"
                :disabled="busyId === m.id"
                @click="confirmDecline(m)"
              >
                Decline
              </button>
            </template>

            <button
              v-else-if="m.status === MembershipStatus.ACCEPTED"
              class="px-3 py-1.5 text-xs rounded-lg border text-rose-600 border-rose-300 dark:border-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20 disabled:opacity-60"
              :disabled="busyId === m.id"
              @click="confirmLeave(m)"
            >
              Leave
            </button>
            <span
              v-else
              class="text-xs text-slate-500 dark:text-slate-400"
            >No actions</span>
          </div>
        </div>
      </li>
    </ul>

    <div
      v-if="error"
      class="text-sm text-red-600"
    >
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import type { Membership, MembershipStatus as MS, ProfileLite } from '../../types'
import { MembershipStatus } from '../../types'
import { meLite } from '../../api/follow'
import { listMyMemberships, acceptMembership, rejectMembership } from '../../api/members'
import { useToast } from '@/modules/toast/useToast'
import AvatarUser from '@/components/shared/AvatarUser.vue'

type OwnerLite = {
  id: number
  email?: string | null
  profile?: (ProfileLite & { avatar_color?: string | null; slug?: string | null; avatar_url?: string | null; logo?: string | null; logo_url?: string | null }) | null
} & Partial<ProfileLite>
type MembershipWithOwner = Membership & { owner?: OwnerLite | null }

const loading = ref(false)
const error = ref<string | null>(null)
const items = ref<MembershipWithOwner[]>([])
const status = ref<MS | null>(null)
const meId = ref<number | null>(null)
const busyId = ref<number | null>(null)
const toast = useToast()

function formatDate(iso?: string | null): string {
  if (!iso) return ''
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? String(iso) : d.toLocaleDateString()
}
function displayOwner(m: MembershipWithOwner | any): string {
  const nameNested = m?.owner?.profile?.name as string | undefined
  const emailNested = m?.owner?.email as string | undefined
  if (nameNested?.trim()) return nameNested
  if (emailNested?.trim()) return emailNested
  const nameFlat = m?.owner?.name as string | undefined
  if (nameFlat?.trim()) return nameFlat
  return `Owner #${m.owner_id}`
}
function ownerAvatar(m: MembershipWithOwner) {
  const flat = m.owner as any
  if (flat?.avatar) return flat.avatar
  if (flat?.avatar_url) return flat.avatar_url
  if (flat?.logo) return flat.logo
  if (flat?.logo_url) return flat.logo_url
  const p = (m.owner?.profile ?? {}) as any
  return p.avatar ?? p.avatar_url ?? p.logo ?? p.logo_url ?? null
}
function ownerAvatarColor(m: MembershipWithOwner) {
  const flat = m.owner as any
  if (flat?.avatar_color) return flat.avatar_color
  const p = (m.owner?.profile ?? {}) as any
  return p.avatar_color ?? null
}
function ownerSlug(m: MembershipWithOwner) {
  const flat = m.owner as any
  if (flat?.slug) return flat.slug
  const p = (m.owner?.profile ?? {}) as any
  return p.slug ?? p.username ?? '—'
}
function statusPillClass(s: MS) {
  switch (s) {
    case MembershipStatus.ACCEPTED: return 'border-emerald-300 text-emerald-700 dark:text-emerald-400'
    case MembershipStatus.PENDING:  return 'border-amber-300 text-amber-700 dark:text-amber-400'
    case MembershipStatus.REJECTED: return 'border-rose-300 text-rose-700 dark:text-rose-400'
    case MembershipStatus.BLOCKED:  return 'border-slate-300 text-slate-600 dark:text-slate-400'
    default:                        return 'border-slate-300 text-slate-600'
  }
}
async function refresh(): Promise<void> {
  if (!meId.value) return
  loading.value = true
  error.value = null
  try {
    const res = await listMyMemberships(meId.value, { status: status.value ?? undefined })
    items.value = res as MembershipWithOwner[]
  } catch (e) {
    error.value = (e as Error)?.message ?? 'Failed to load memberships'
  } finally {
    loading.value = false
  }
}
function confirmAccept(m: MembershipWithOwner) {
  toast.confirm(`Accept membership with ${displayOwner(m)}?`, {
    confirmLabel: 'Accept',
    cancelLabel: 'Cancel',
    onConfirm: () => doAccept(m),
  })
}
async function doAccept(m: MembershipWithOwner) {
  if (busyId.value) return
  busyId.value = m.id
  try {
    await acceptMembership(m.id)
    toast.success(`Accepted ${displayOwner(m)}`)
    await refresh()
  } catch {
    toast.error('Failed to accept')
  } finally {
    busyId.value = null
  }
}
function confirmDecline(m: MembershipWithOwner) {
  toast.confirm(`Decline invitation from ${displayOwner(m)}?`, {
    confirmLabel: 'Decline',
    cancelLabel: 'Cancel',
    destructive: true,
    onConfirm: () => doDecline(m),
  })
}
async function doDecline(m: MembershipWithOwner) {
  if (busyId.value) return
  busyId.value = m.id
  try {
    await rejectMembership(m.id, 'declined_by_member')
    toast.success(`Declined ${displayOwner(m)}`)
    await refresh()
  } catch {
    toast.error('Failed to decline')
  } finally {
    busyId.value = null
  }
}
function confirmLeave(m: MembershipWithOwner) {
  toast.confirm(`Leave membership with ${displayOwner(m)}?`, {
    confirmLabel: 'Leave',
    cancelLabel: 'Cancel',
    destructive: true,
    onConfirm: () => doLeave(m),
  })
}
async function doLeave(m: MembershipWithOwner) {
  if (busyId.value) return
  busyId.value = m.id
  try {
    await rejectMembership(m.id, 'left_by_member')
    toast.success(`You left ${displayOwner(m)}`)
    await refresh()
  } catch {
    toast.error('Failed to leave')
  } finally {
    busyId.value = null
  }
}

onMounted(async () => {
  try {
    const me = await meLite()
    meId.value = Number(me?.data?.id) || null
  } catch (e) {
    error.value = (e as Error)?.message ?? 'Failed to resolve current user'
  } finally {
    await refresh()
  }
})
watch(status, refresh)
</script>
