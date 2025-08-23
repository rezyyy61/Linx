import { computed, reactive } from "vue";
import { storeToRefs } from "pinia";
import {
  useProfileStore,
  type Profile,
  type ProfileTranslation,
  type Media,
  type User,
} from "@/stores/profile/profile";

type LinkVM = { id: string; type: string; title: string; url: string; value?: string | null };
type ValueVM = { id: string; type: string; value: string };
type FileVM = { id: string; name: string; size: number; type: string; ext: string; previewUrl: string };

function mediaName(m: Media): string {
  const k = m.key || "";
  const parts = k.split("/");
  return parts[parts.length - 1] || `file-${m.id}`;
}

const LINK_TYPES = ["website","email","phone","telegram","instagram","facebook","twitter","custom"] as const;
const VALUE_TYPES = new Set<string>(["email","phone"]);

export function useProfileUi() {
  const store = useProfileStore();
  const { profile, user } = storeToRefs(store);

  const state = reactive({
    currentLocale: "en",
    locales: ["en", "fa"] as string[],
    basics: {
      slug: "",
      location: "",
      foundedYear: "",
      avatarColor: "#4f46e5",
      displayName: "",
      entityType: "individual",
    },
    translations: {
      en: { tagline: "", about: "", goals: "", activities: "", structure: "" },
      fa: { tagline: "", about: "", goals: "", activities: "", structure: "" },
    } as Record<string, { tagline: string; about: string; goals: string; activities: string; structure: string }>,
    links: [] as Array<LinkVM>,
    values: [] as Array<ValueVM>,
    media: {
      logoUrl: "",
      files: [] as Array<FileVM>,
    },
    saving: false,
    savedAt: 0,
  });

  const original = reactive({
    links: [] as Array<{ id: number; type: string; title: string | null; url: string | null; value: string | null; order: number }>,
    fileIds: [] as number[],
  });

  function mapFromStore(p: Profile, u: User) {
    state.basics.slug = p.slug || "";
    state.basics.location = p.location || "";
    state.basics.foundedYear = p.founded_year ? String(p.founded_year) : "";
    state.basics.avatarColor = p.avatar_color || "#4f46e5";
    state.basics.displayName = u.name || "";
    state.basics.entityType = (p.entity_type as any) || "individual";

    const foundLocales = Array.from(new Set((p.translations || []).map(t => t.locale)));
    state.locales = Array.from(new Set([...state.locales, ...foundLocales]));
    state.currentLocale = foundLocales.includes(state.currentLocale) ? state.currentLocale : (foundLocales[0] || state.currentLocale);

    const init = { tagline: "", about: "", goals: "", activities: "", structure: "" };
    const tMap: Record<string, typeof init> = {};
    for (const loc of state.locales) tMap[loc] = { ...init };
    for (const t of p.translations || []) {
      tMap[t.locale] = {
        tagline: t.tagline || "",
        about: t.about || "",
        goals: t.goals || "",
        activities: t.activities || "",
        structure: t.structure || "",
      };
    }
    state.translations = tMap;

    state.links = (p.links || [])
      .map(l => ({
        id: String(l.id),
        type: l.type,
        title: l.title || "",
        url: l.url || "",
        value: (l as any).value ?? null,
      }))
      .sort((a, b) => {
        const la = (p.links || []).find(x => String(x.id) === a.id)?.order ?? 0;
        const lb = (p.links || []).find(x => String(x.id) === b.id)?.order ?? 0;
        return la - lb;
      });

    original.links = (p.links || []).map(l => ({
      id: l.id,
      type: l.type,
      title: l.title ?? null,
      url: l.url ?? null,
      value: (l as any).value ?? null,
      order: l.order,
    }));

    state.media.logoUrl = Array.isArray(p.logo) && p.logo.length > 0
      ? ((p.logo[0] as any).url ?? (p.logo[0] as any).public_url ?? "")
      : "";
    state.media.files = (p.documents || []).map((m: any) => ({
      id: String(m.id),
      name: mediaName(m as Media),
      size: Number(m.size ?? m.size_bytes ?? 0),
      type: String(m.mime_type ?? m.type ?? ""),
      ext: String(m.ext ?? (m.key ? (m.key.split(".").pop() || "").toLowerCase() : "")),
      previewUrl: String(m.url ?? m.public_url ?? ""),
    }));
    original.fileIds = state.media.files.map(f => Number(f.id))
  }

  function ensureLoadedMaps() {
    if (profile.value && user.value) mapFromStore(profile.value, user.value);
  }

  async function load() {
    await store.fetchMe();
    ensureLoadedMaps();
  }

  type SimpleLinksModel = Record<(typeof LINK_TYPES)[number], string>;

  const linksSimple = computed<SimpleLinksModel>({
    get() {
      const out = {
        website: "", email: "", phone: "", telegram: "",
        instagram: "", facebook: "", twitter: "", custom: "",
      } as SimpleLinksModel;

      for (const t of LINK_TYPES) {
        const found = state.links.find(l => l.type === t);
        if (!found) continue;
        if (VALUE_TYPES.has(t)) {
          out[t] = (found.value ?? "") as string;
        } else {
          out[t] = found.url || "";
        }
      }
      return out;
    },

    set(v) {
      const next = { ...v };
      for (const t of LINK_TYPES) {
        const val = (next as any)[t]?.toString().trim() ?? "";
        const idx = state.links.findIndex(l => l.type === t);
        if (val === "") {
          if (idx !== -1) state.links.splice(idx, 1);
          continue;
        }
        if (idx === -1) {
          const link: LinkVM = { id: `new:${t}`, type: t, title: "", url: "" };
          if (VALUE_TYPES.has(t)) {
            (link as any).value = val;
            link.url = "";
          } else {
            link.url = val;
          }
          state.links.push(link);
        } else {
          const link = state.links[idx];
          if (VALUE_TYPES.has(t)) {
            (link as any).value = val;
            link.url = "";
          } else {
            link.url = val;
            (link as any).value = null;
          }
        }
      }
      state.links = state.links
        .slice()
        .sort((a, b) => LINK_TYPES.indexOf(a.type as any) - LINK_TYPES.indexOf(b.type as any));
    },
  });

  function diffLinks() {
    const origById = new Map<number, { type: string; title: string | null; url: string | null; value: string | null; order: number }>();
    original.links.forEach(l => origById.set(l.id, { type: l.type, title: l.title, url: l.url, value: l.value, order: l.order }));

    const seenExisting = new Set<number>();
    const toDelete: number[] = [];
    const toUpdate: Array<{ id: number; payload: { type: string; title: string | null; url: string | null; value?: string | null; order: number } }> = [];
    const toAdd: Array<{ type: string; title: string | null; url: string | null; value?: string | null; order: number }> = [];

    state.links.forEach((l, idx) => {
      const isNumeric = /^\d+$/.test(l.id);
      const payload: { type: string; title: string | null; url: string | null; value?: string | null; order: number } = {
        type: l.type,
        title: l.title || null,
        url: VALUE_TYPES.has(l.type) ? null : (l.url || null),
        order: idx,
      };
      if (VALUE_TYPES.has(l.type)) {
        const v = (l as any).value ?? l.url ?? null;
        payload.value = v ? String(v) : null;
      }

      if (isNumeric) {
        const id = parseInt(l.id, 10);
        seenExisting.add(id);
        const o = origById.get(id);
        if (!o) {
          toAdd.push(payload);
        } else {
          const changed =
            o.type !== payload.type ||
            o.title !== payload.title ||
            o.url !== payload.url ||
            o.order !== payload.order ||
            (VALUE_TYPES.has(l.type) && (o.value ?? null) !== (payload.value ?? null));
          if (changed) toUpdate.push({ id, payload });
        }
      } else {
        toAdd.push(payload);
      }
    });

    for (const [id] of origById) {
      if (!seenExisting.has(id)) toDelete.push(id);
    }

    return { toAdd, toUpdate, toDelete };
  }

  function diffFiles() {
    const currIds = new Set(state.media.files.map(f => Number(f.id)));
    const origIds = new Set(original.fileIds);
    const toRemove = original.fileIds.filter(id => !currIds.has(id));
    const toAdd = state.media.files.filter(f => !origIds.has(Number(f.id)));
    return { toAdd, toRemove };
  }

  const progress = computed(() => {
    let total = 0;
    let done = 0;
    const basicsKeys = ["slug", "location", "avatarColor"] as const;
    total += basicsKeys.length;
    basicsKeys.forEach(k => {
      if ((state.basics as any)[k]) done++;
    });
    const t = state.translations[state.currentLocale];
    const tKeys = ["tagline", "about"] as const;
    total += tKeys.length;
    tKeys.forEach(k => {
      if ((t as any)[k]) done++;
    });
    return Math.round((done / Math.max(total, 1)) * 100);
  });

  async function save() {
    state.saving = true;
    try {
      const translationSrc = state.translations[state.currentLocale] || { tagline: "", about: "", goals: "", activities: "", structure: "" };
      const translation: Partial<ProfileTranslation> = { locale: state.currentLocale };
      if (translationSrc.tagline) translation.tagline = translationSrc.tagline;
      if (translationSrc.about) translation.about = translationSrc.about;
      if (translationSrc.goals) translation.goals = translationSrc.goals;
      if (translationSrc.activities) translation.activities = translationSrc.activities;
      if (translationSrc.structure) translation.structure = translationSrc.structure;

      const fy = state.basics.foundedYear as unknown;
      const fyStr = fy == null ? "" : (typeof fy === "number" ? String(fy) : String(fy));
      const founded = fyStr.trim() === "" ? undefined : Number(fyStr);

      await store.updateProfile({
        slug: state.basics.slug,
        location: state.basics.location || undefined,
        founded_year: Number.isFinite(founded) ? founded : undefined,
        avatar_color: state.basics.avatarColor || undefined,
        translation,
      } as any);

      const L = diffLinks();
      for (const id of L.toDelete) await store.deleteLink(id);
      for (const u of L.toUpdate) await store.updateLink(u.id, u.payload as any);
      for (const a of L.toAdd) await store.addLink(a as any);

      const F = diffFiles();
      for (const id of F.toRemove) await store.removeDocument(id);
      for (let i = 0; i < F.toAdd.length; i++) {
        await store.addDocument(Number(F.toAdd[i].id), i);
      }

      await store.fetchMe();
      ensureLoadedMaps();
      state.savedAt = Date.now();
    } finally {
      state.saving = false;
    }
  }

  return { state, progress, load, save, linksSimple };
}
