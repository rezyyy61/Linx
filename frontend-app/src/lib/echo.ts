import Pusher, { type Options, type ChannelAuthorizationCallback } from 'pusher-js'
import type { App } from 'vue'
import { api, ensureCsrfCookie } from '@/lib/http'

const key = import.meta.env.VITE_REVERB_APP_KEY as string
const host = (import.meta.env.VITE_REVERB_HOST as string) || window.location.hostname
const sch = ((import.meta.env.VITE_REVERB_SCHEME as string) || (location.protocol.startsWith('https') ? 'https' : 'http')).toLowerCase()
const port = Number(import.meta.env.VITE_REVERB_PORT || (sch === 'https' ? 443 : 80))
const tls = sch === 'https' || sch === 'wss'

let client: Pusher | null = null

function buildClient(): Pusher {
  const opts: Options = {
    cluster: 'mt1',
    wsHost: host,
    wsPort: tls ? undefined : port,
    wssPort: tls ? port : undefined,
    forceTLS: tls,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authorizer: (channel) => ({
      authorize: async (socketId: string, callback: ChannelAuthorizationCallback) => {
        try {
          await ensureCsrfCookie()
          const { data } = await api.post('/broadcasting/auth', { socket_id: socketId, channel_name: channel.name }, { baseURL: '' })
          callback(null, data)
        } catch (err) {
          callback(err as any, null as any)
        }
      },
    }),
  }
  return new Pusher(key, opts)
}

export async function initRealtime(): Promise<Pusher> {
  if (client) return client
  client = buildClient()
  return client
}

export async function subscribePrivate(name: string) {
  const p = await initRealtime()
  return p.subscribe(name.startsWith('private-') ? name : `private-${name}`)
}

export async function subscribePresence(name: string) {
  const p = await initRealtime()
  return p.subscribe(name.startsWith('presence-') ? name : `presence-${name}`)
}

export async function subscribePublic(name: string) {
  const p = await initRealtime()
  return p.subscribe(name)
}

export function unsubscribeExact(name: string) {
  if (!client) return
  client.unsubscribe(name)
}

export async function unsubscribe(name: string) {
  if (!client) return
  const n = name.startsWith('private-') || name.startsWith('presence-') ? name : `private-${name}`
  client.unsubscribe(n)
}

export async function installRealtime(app: App) {
  const p = await initRealtime()
  ;(app.config.globalProperties as any).$pusher = p
}

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties { $pusher: Pusher }
}
