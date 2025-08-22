import { reactive, computed } from 'vue'
import type { Rule } from './validators'

export function useForm<
  T extends Record<string, any>,
  Key extends Extract<keyof T, string>
>(
  initial: T,
  rules: Partial<Record<Key, Rule[]>> = {}
) {
  const KEYS = Object.keys(initial) as Key[]

  const values = reactive({ ...initial }) as T

  const touched = reactive(
    Object.fromEntries(KEYS.map(k => [k, false])) as Record<Key, boolean>
  )

  const errors = reactive({} as Record<Key, string[]>)

  function validateField<K extends Key>(key: K) {
    const rs = rules[key] || []
    const msgs = rs.map(r => r((values as any)[key], values)).filter(Boolean) as string[]
    if (msgs.length) (errors as any)[key] = msgs
    else delete (errors as any)[key]
  }

  function validateAll() {
    KEYS.forEach(k => validateField(k))
    return Object.keys(errors).length === 0
  }

  const valid = computed(() => Object.keys(errors).length === 0)

  function setTouchedAll() {
    KEYS.forEach(k => { (touched as any)[k] = true })
  }

  function setServerErrors(apiErrors: Partial<Record<Key, string[]>>) {
    (Object.keys(apiErrors || {}) as Key[]).forEach(k => {
      const v = (apiErrors as any)[k] as string[] | undefined
      if (v) (errors as any)[k] = v
    })
  }

  return { values, touched, errors, valid, validateField, validateAll, setTouchedAll, setServerErrors }
}
