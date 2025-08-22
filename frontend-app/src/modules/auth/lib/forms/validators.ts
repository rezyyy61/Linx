export type Rule<T = any, Vals extends Record<string, any> = Record<string, any>> =
  (value: T, values: Vals) => string | null

export const required = (msg: string): Rule => (v) =>
  (v === null || v === undefined || String(v).trim() === '') ? msg : null

export const email = (msg: string): Rule<string> => (v) =>
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v).trim()) ? null : msg

export const minLen = (n: number, msg: string): Rule<string> => (v) =>
  String(v ?? '').length >= n ? null : msg

export const sameAs = (otherKey: string, msg: string): Rule<any> => (v, values) =>
  v === (values as any)[otherKey] ? null : msg

export const match = sameAs
