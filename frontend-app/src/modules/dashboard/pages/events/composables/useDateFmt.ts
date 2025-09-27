export function useDateFmt(locale?: string) {
  const df = new Intl.DateTimeFormat(locale, { dateStyle: "medium", timeStyle: "short" });
  const dd = new Intl.DateTimeFormat(locale, { dateStyle: "medium" });
  const dt = new Intl.DateTimeFormat(locale, { timeStyle: "short" });
  function fmt(iso?: string | null) {
    if (!iso) return "";
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? "" : df.format(d);
  }
  function fmtDate(iso?: string | null) {
    if (!iso) return "";
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? "" : dd.format(d);
  }
  function fmtTime(iso?: string | null) {
    if (!iso) return "";
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? "" : dt.format(d);
  }
  return { fmt, fmtDate, fmtTime };
}
