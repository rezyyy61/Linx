// src/composables/useNotify.ts
import { readonly, reactive } from "vue";

export type ToastType = "success" | "error" | "info" | "warning";

export type Toast = {
  id: string;
  type: ToastType;
  title?: string;
  description?: string;
  duration?: number; // ms; undefined => sticky
  action?: { label: string; onClick: () => void };
  loading?: boolean; // برای نمایش آیکون چرخان در promise()
};

// What callers pass to `push` (id is optional)
export type ToastInput = Omit<Toast, "id"> & { id?: string };

// پیش‌فرض‌های UX: پیام‌ها دیرتر برن
const DEFAULTS = {
  durations: {
    success: 12000,
    info: 12000,
    warning: 12000,
    error: 12000,
  } as Record<ToastType, number>,
  max: 5,
};

const state = reactive({
  toasts: [] as Toast[],
  max: DEFAULTS.max,
});

// تایمر با نگهداری «زمان باقیمانده» برای pause/resume واقعی
type TimerData = {
  handle: ReturnType<typeof setTimeout>;
  started: number;     // Date.now()
  remaining: number;   // ms
};
const timers = new Map<string, TimerData>();

function uid() {
  return Math.random().toString(36).slice(2) + Date.now().toString(36);
}

function resolveDuration(t: ToastInput): number | undefined {
  // اگر duration تعیین نشده، از پیش‌فرض نوع استفاده کن
  const base = t.duration ?? DEFAULTS.durations[t.type];
  // اگر اکشن دارد و کاربر duration نداده، استیکی باشه
  if (t.action && t.duration === undefined) return undefined;
  return base;
}

function push(t: ToastInput) {
  const id = t.id ?? uid();
  const toast: Toast = { ...t, id, duration: resolveDuration(t) };

  // cap queue
  if (state.toasts.length >= state.max) {
    const removed = state.toasts.shift();
    if (removed) clearTimer(removed.id); // شامل محاسبه remaining نیست چون حذف می‌کنیم
  }

  state.toasts.push(toast);
  armTimer(toast);
  return id;
}

function armTimer(t: Toast) {
  if (!t.duration) return; // sticky
  // اگر قبلاً pause شده بود، remaining را از Map بخوان
  const prev = timers.get(t.id);
  const remaining = prev?.remaining ?? t.duration;
  // اول مطمئن شو تایمر قبلی پاک شده
  if (prev) clearTimeout(prev.handle);

  const handle = setTimeout(() => remove(t.id), remaining);
  timers.set(t.id, { handle, started: Date.now(), remaining });
}

function clearTimer(id: string) {
  const data = timers.get(id);
  if (!data) return;
  clearTimeout(data.handle);
  // محاسبه remaining واقعی تا لحظه‌ی hover
  const elapsed = Date.now() - data.started;
  const left = Math.max(0, data.remaining - elapsed);
  // اگر صفر شد، بلافاصله حذفش می‌کنیم
  if (left <= 0) {
    timers.delete(id);
    remove(id);
    return;
  }
  // در غیر اینصورت باقی‌مانده را نگه‌دار تا بعداً armTimer با همان ادامه دهد
  timers.set(id, { ...data, remaining: left });
}

function remove(id: string) {
  // پاک‌سازی تایمر
  const data = timers.get(id);
  if (data) {
    clearTimeout(data.handle);
    timers.delete(id);
  }
  const i = state.toasts.findIndex((x) => x.id === id);
  if (i !== -1) state.toasts.splice(i, 1);
}

// Helper برای تمدید زمان باقی‌مانده (مثلاً بعد از یک اکشن)
function extend(id: string, ms: number) {
  const d = timers.get(id);
  if (!d) return;
  timers.set(id, { ...d, remaining: d.remaining + ms, started: Date.now() });
  // بازآرمه تا تایمر با مقدار جدید ست شود
  const t = state.toasts.find((x) => x.id === id);
  if (t) armTimer(t);
}

// Helper برای آپدیت سریع محتوای یک toast (بدون جابجایی)
function update(id: string, patch: Partial<Toast>) {
  const t = state.toasts.find((x) => x.id === id);
  if (!t) return;
  Object.assign(t, patch);
}

// convenience helpers
function success(title: string, opts: Partial<ToastInput> = {}) {
  return push({ type: "success", title, ...opts });
}
function error(title: string, opts: Partial<ToastInput> = {}) {
  return push({ type: "error", title, ...opts });
}
function info(title: string, opts: Partial<ToastInput> = {}) {
  return push({ type: "info", title, ...opts });
}
function warning(title: string, opts: Partial<ToastInput> = {}) {
  return push({ type: "warning", title, ...opts });
}

// Promise helper (loading → success/error) با آیکون چرخان و استیکی بودن در حین لود
async function promise<T>(
  p: Promise<T>,
  cfg: {
    loading?: string;
    success?: string | ((v: T) => string);
    error?: string | ((e: any) => string);
  },
) {
  const id = info(cfg.loading ?? "Working...", { duration: undefined, loading: true });
  try {
    const v = await p;
    remove(id);
    success(typeof cfg.success === "function" ? cfg.success(v) : (cfg.success ?? "Done"), {
      duration: DEFAULTS.durations.success,
    });
    return v;
  } catch (e) {
    remove(id);
    error(typeof cfg.error === "function" ? cfg.error(e) : (cfg.error ?? "Something went wrong"), {
      // خطا طولانی‌تر بمونه
      duration: DEFAULTS.durations.error,
    });
    throw e;
  }
}

export function useNotify() {
  return {
    state: readonly(state),
    push,
    remove,
    update,
    extend,
    success,
    error,
    info,
    warning,
    promise,
    // expose pause/resume for hover
    _arm: armTimer,
    _clear: clearTimer,
  };
}
