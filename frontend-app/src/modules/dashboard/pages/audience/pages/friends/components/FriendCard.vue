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
          :alt="profile.name || profile.slug || 'Logo'"
          class="max-h-full max-w-full object-contain"
          @error="onHeroError"
        >
        <div
          v-else
          class="flex items-center justify-center"
        >
          <AvatarUser
            :src="profile.avatar || null"
            :name="profile.name || ''"
            :color="profile.avatar_color || null"
            size="2xl"
            rounded="full"
            ring
            ring-color="indigo"
            zoomable
          />
        </div>
      </div>
      <div class="absolute inset-0 ring-1 ring-white/10 pointer-events-none" />
      <div
        v-if="pinned"
        class="absolute right-3 top-3 inline-flex items-center justify-center h-6 w-6 rounded-full bg-amber-400 text-amber-900 ring-2 ring-amber-200/70 shadow-lg"
      >
        <Icon
          icon="mdi:star"
          width="18"
          height="18"
        />
      </div>
    </div>

    <div class="px-5 pt-5 pb-5 mb-4">
      <div class="flex items-start gap-3">
        <input
          type="checkbox"
          :checked="checked"
          class="mt-1 h-4 w-4 rounded border-slate-300 dark:border-zinc-600"
          @change="$emit('toggle-check')"
        >
        <div class="min-w-0 flex-1">
          <div class="text-base font-semibold leading-tight truncate flex items-center gap-1.5">
            <span>{{ profile.name || '—' }}</span>
            <Icon
              v-if="pinned"
              icon="mdi:star"
              width="14"
              height="14"
              class="text-amber-500"
            />
          </div>
          <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
            @{{ profile.slug || '—' }}
          </div>
        </div>

        <div
          ref="menuRoot"
          class="relative"
        >
          <button
            ref="menuBtn"
            class="h-9 w-9 inline-flex items-center justify-center rounded-full border border-slate-200 dark:border-zinc-700 bg-white/80 dark:bg-zinc-900/70 backdrop-blur hover:bg-white/95 dark:hover:bg-zinc-900 disabled:opacity-60"
            aria-label="Actions"
            :disabled="inviteLoading"
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
    </div>

    <teleport to="body">
      <div
        v-if="menuOpen"
        ref="menuEl"
        class="fixed min-w-[200px] rounded-2xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 shadow-2xl p-1 z-[9999]"
        :style="menuStyle"
      >
        <button
          class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-zinc-800 disabled:opacity-60"
          :disabled="inviteDisabled"
          :aria-disabled="inviteDisabled"
          :title="inviteTooltip"
          @click="onInvite"
        >
          <Icon
            :icon="inviteIcon"
            width="16"
            height="16"
          />
          <span>{{ inviteLabel }}</span>
        </button>

        <div class="my-1 h-px bg-slate-200 dark:bg-zinc-800" />

        <button
          class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-zinc-800"
          @click="closeMenu(); $emit('toggle-pin')"
        >
          <Icon
            :icon="pinned ? 'mdi:pin' : 'mdi:pin-outline'"
            width="16"
            height="16"
          />
          <span>{{ pinned ? 'Unpin' : 'Pin' }}</span>
        </button>

        <button
          class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
          @click="closeMenu(); $emit('remove')"
        >
          <Icon
            icon="mdi:trash-can-outline"
            width="16"
            height="16"
          />
          <span>Remove</span>
        </button>

        <button
          class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-zinc-800"
          @click="closeMenu(); $emit('open-mutuals')"
        >
          <Icon
            icon="mdi:account-multiple-outline"
            width="16"
            height="16"
          />
          <span>View mutuals</span>
        </button>
      </div>
    </teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { Icon } from '@iconify/vue'
import AvatarUser from '@/components/shared/AvatarUser.vue'
import type { ProfileLite, MembershipStatus as MS } from '../../../types'
import { meLite } from '../../../api/follow'
import { createMembership } from '../../../api/members'
import { useToast } from '@/modules/toast/useToast'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  profile: ProfileLite;
  pinned: boolean;
  checked: boolean;
  /** وضعیت فعلی عضویت این پروفایل نزد من (اختیاری: 'accepted' | 'pending' | ...) */
  membershipStatus?: MS | null;
}>()

defineEmits<{ (e: 'toggle-check'): void; (e: 'toggle-pin'): void; (e: 'remove'): void; (e: 'open-mutuals'): void }>()

const { t } = useI18n()
const toast = useToast()

/* -------- Hero image / avatar -------- */
const heroBroken = ref(false)
const heroSrc = computed(() => {
  const logo = (props.profile as any)?.logo || (props.profile as any)?.logo_url || null
  return heroBroken.value ? '' : (logo || '')
})
const hasHero = computed(() => !!heroSrc.value)
function onHeroError() { heroBroken.value = true }

/* -------- Menu / dropdown -------- */
const menuOpen = ref(false)
const menuRoot = ref<HTMLElement | null>(null)
const menuBtn = ref<HTMLElement | null>(null)
const menuEl = ref<HTMLElement | null>(null)
const menuStyle = ref<Record<string, string>>({})
const inviteLoading = ref(false)
const invitedNow = ref(false) // قفل محلی بعد از ارسال موفق

const isAccepted = computed(() => props.membershipStatus === 'accepted')
const isPending  = computed(() => props.membershipStatus === 'pending')
const inviteDisabled = computed(() => isAccepted.value || isPending.value || inviteLoading.value || invitedNow.value)
const inviteLabel = computed(() => {
  if (isAccepted.value) return 'Already a member'
  if (isPending.value || invitedNow.value) return 'Invitation pending'
  return 'Invite to members'
})
const inviteTooltip = computed(() => inviteDisabled.value ? inviteLabel.value : 'Invite to members')
const inviteIcon = computed(() => {
  if (isAccepted.value) return 'mdi:account-check-outline'
  if (isPending.value || invitedNow.value) return 'mdi:clock-outline'
  return 'mdi:account-plus-outline'
})

function updateMenuPosition() {
  const btn = menuBtn.value, el = menuEl.value
  if (!btn || !el) return
  const r = btn.getBoundingClientRect()
  const vw = window.innerWidth, vh = window.innerHeight, margin = 8
  el.style.visibility = 'hidden'
  el.style.top = '0px'; el.style.left = '0px'
  const w = el.offsetWidth || 200, h = el.offsetHeight || 150
  let top = r.bottom + margin, left = r.right - w
  if (top + h > vh - margin) top = r.top - h - margin
  if (top < margin) top = margin
  if (left + w > vw - margin) left = vw - w - margin
  if (left < margin) left = margin
  menuStyle.value = { top: `${top}px`, left: `${left}px` }
  el.style.visibility = 'visible'
}

function toggleMenu() { menuOpen.value = !menuOpen.value }
function closeMenu() { menuOpen.value = false }

/* -------- Invite flow -------- */
async function actuallyInvite() {
  if (inviteDisabled.value) return
  inviteLoading.value = true
  try {
    const me = await meLite()
    const ownerId = me?.data?.id
    if (!ownerId) throw new Error('no-owner')
    await createMembership(ownerId, { member_id: props.profile.id })
    toast.success(t('toast.member.invite.sent'))
    invitedNow.value = true
  } catch {
    toast.error(t('toast.member.invite.failed'))
  } finally {
    inviteLoading.value = false
    closeMenu()
  }
}

function onInvite() {
  if (inviteDisabled.value) return
  menuOpen.value = false
  nextTick(() => {
    const name = props.profile.name || (props.profile.slug ? `@${props.profile.slug}` : t('toast.member.unknown'))
    toast.confirm(
      t('toast.member.invite.confirm', { name }),
      {
        confirmLabel: t('toast.action.invite'),
        cancelLabel: t('toast.action.cancel'),
        destructive: false,
        onConfirm: actuallyInvite,
      }
    )
  })
}

/* -------- Global handlers -------- */
function onGlobalClick(e: MouseEvent) {
  if (!menuOpen.value) return
  const t = e.target as Node
  if (menuEl.value && menuEl.value.contains(t)) return
  if (menuRoot.value && menuRoot.value.contains(t)) return
  closeMenu()
}
function onKey(e: KeyboardEvent) { if (e.key === 'Escape') closeMenu() }
function onScroll() { if (menuOpen.value) updateMenuPosition() }

watch(menuOpen, async v => {
  if (v) {
    await nextTick()
    updateMenuPosition()
    window.addEventListener('resize', updateMenuPosition)
    window.addEventListener('scroll', onScroll, true)
    document.addEventListener('click', onGlobalClick)
    document.addEventListener('keydown', onKey)
  } else {
    window.removeEventListener('resize', updateMenuPosition)
    window.removeEventListener('scroll', onScroll, true)
    document.removeEventListener('click', onGlobalClick)
    document.removeEventListener('keydown', onKey)
  }
})

onMounted(() => { document.addEventListener('click', onGlobalClick) })
onBeforeUnmount(() => {
  document.removeEventListener('click', onGlobalClick)
  window.removeEventListener('resize', updateMenuPosition)
  window.removeEventListener('scroll', onScroll, true)
  document.removeEventListener('keydown', onKey)
})
</script>
