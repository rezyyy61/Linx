<!-- frontend-app/src/modules/dashboard/pages/audience/pages/members/components/MemberCard.vue -->
<template>
  <div
    class="relative isolate rounded-3xl border border-white/10 bg-gradient-to-b from-white/5 to-white/0 dark:from-zinc-900/60 dark:to-zinc-900/30 backdrop-blur shadow-xl hover:shadow-2xl transition"
  >
    <div
      class="relative flex items-center justify-center bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,.10),rgba(255,255,255,0))] dark:bg-[radial-gradient(ellipse_at_top,rgba(255,255,255,.06),rgba(255,255,255,0))] overflow-hidden rounded-t-3xl"
    >
      <div class="w-full h-40 sm:h-48 flex items-center justify-center p-5">
        <img
          v-if="hasHero"
          :src="heroSrc"
          :alt="displayName || 'Logo'"
          class="max-h-full max-w-full object-contain"
          @error="onHeroError"
        >
        <div
          v-else
          class="flex items-center justify-center"
        >
          <AvatarUser
            :src="profileLike.avatar"
            :name="profileLike.name || ''"
            :color="profileLike.avatar_color || null"
            size="2xl"
            rounded="full"
            ring
            ring-color="indigo"
            zoomable
          />
        </div>
      </div>
      <div class="absolute inset-0 ring-1 ring-white/10 pointer-events-none" />
    </div>

    <div class="px-5 pt-5 pb-5 mb-4">
      <div class="flex items-start gap-3">
        <div class="min-w-0 flex-1">
          <div class="text-base font-semibold leading-tight truncate">
            {{ displayName || '—' }}
          </div>
          <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
            {{ displaySubline || '—' }}
          </div>
        </div>

        <div
          ref="menuRoot"
          class="relative"
        >
          <button
            ref="menuBtn"
            class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200 dark:border-zinc-700 bg-white/80 dark:bg-zinc-900/70 backdrop-blur hover:bg-white/95 dark:hover:bg-zinc-900"
            aria-label="Actions"
            @click="toggleMenu"
          >
            <Icon
              icon="mdi:dots-horizontal"
              width="18"
              height="18"
            />
          </button>
        </div>
      </div>

      <div class="mt-3">
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="{
            'bg-amber-100 text-amber-800': membership.status === 'pending',
            'bg-emerald-100 text-emerald-800': membership.status === 'accepted',
            'bg-rose-100 text-rose-800': membership.status === 'rejected'
          }"
        >
          {{ membership.status }}
        </span>
      </div>
    </div>

    <teleport to="body">
      <div
        v-if="menuOpen"
        ref="menuEl"
        class="fixed min-w-[180px] rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-2xl p-1 z-[9999]"
        :style="menuStyle"
      >
        <button
          class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
          @click="closeMenu(); onRemove()"
        >
          <Icon
            icon="mdi:trash-can-outline"
            width="16"
            height="16"
          />
          <span>Remove</span>
        </button>
      </div>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch } from 'vue'
import { Icon } from '@iconify/vue'
import AvatarUser from '@/components/shared/AvatarUser.vue'
import type { Membership, ProfileLite } from '../../../types'
import { deleteMembership } from '../../../api/members'
import { useToast } from '@/modules/toast/useToast'
import { useI18n } from 'vue-i18n'

const props = defineProps<{ membership: Membership }>()
const emit = defineEmits<{ (e: 'updated'): void }>()

const { t } = useI18n()
const toast = useToast()

const heroBroken = ref(false)
const heroSrc = computed(() => {
  const m: any = props.membership?.meta || {}
  const logo = m.logo || m.logo_url || null
  return heroBroken.value ? '' : (logo || '')
})
const hasHero = computed(() => !!heroSrc.value)
function onHeroError() { heroBroken.value = true }

const profileLike = computed<ProfileLite>(() => {
  const member: any = (props.membership as any).member || {}
  const meta: any = (props.membership as any).meta || {}
  return {
    id: member.id ?? props.membership.member_id ?? 0,
    name: member.name ?? meta.name ?? props.membership.email ?? null,
    slug: member.slug ?? null,
    avatar: member.avatar ?? meta.avatar ?? null,
    avatar_color: member.avatar_color ?? meta.avatar_color ?? null
  }
})

const displayName = computed(() => profileLike.value.name || '')
const displaySubline = computed(() => {
  if (profileLike.value.slug) return `@${profileLike.value.slug}`
  if (props.membership.email) return props.membership.email
  if (props.membership.member_id) return `id:${props.membership.member_id}`
  return ''
})

const menuOpen = ref(false)
const menuRoot = ref<HTMLElement | null>(null)
const menuBtn = ref<HTMLElement | null>(null)
const menuEl = ref<HTMLElement | null>(null)
const menuStyle = ref<Record<string, string>>({})

function updateMenuPosition() {
  const btn = menuBtn.value
  const el = menuEl.value
  if (!btn || !el) return
  const r = btn.getBoundingClientRect()
  const vw = window.innerWidth
  const vh = window.innerHeight
  const margin = 8
  el.style.visibility = 'hidden'
  el.style.top = '0px'
  el.style.left = '0px'
  const w = el.offsetWidth || 200
  const h = el.offsetHeight || 150
  let top = r.bottom + margin
  let left = r.right - w
  if (top + h > vh - margin) top = r.top - h - margin
  if (top < margin) top = margin
  if (left + w > vw - margin) left = vw - w - margin
  if (left < margin) left = margin
  menuStyle.value = { top: `${top}px`, left: `${left}px` }
  el.style.visibility = 'visible'
}

function toggleMenu() { menuOpen.value = !menuOpen.value }
function closeMenu() { menuOpen.value = false }

watch(menuOpen, async v => {
  if (v) {
    await nextTick()
    updateMenuPosition()
    document.addEventListener('click', onGlobalClick)
  } else {
    document.removeEventListener('click', onGlobalClick)
  }
})
function onGlobalClick(e: MouseEvent) {
  if (!menuOpen.value) return
  if (menuEl.value?.contains(e.target as Node)) return
  if (menuRoot.value?.contains(e.target as Node)) return
  closeMenu()
}

async function actuallyRemove() {
  try {
    await deleteMembership(props.membership.id)
    emit('updated')
    toast.success(t('toast.member.removed'))
  } catch {
    toast.error(t('toast.member.removeFailed'))
  }
}

function onRemove() {
  closeMenu()
  toast.confirm(
    t('toast.member.confirmRemove', { name: displayName.value || t('toast.member.unknown') }),
    {
      confirmLabel: t('toast.action.remove'),
      cancelLabel: t('toast.action.cancel'),
      destructive: true,
      onConfirm: actuallyRemove
    }
  )
}
</script>


