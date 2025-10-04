import type { Directive } from "vue"

const intersect: Directive<HTMLElement, (e:IntersectionObserverEntry)=>void> = {
  mounted(el, binding) {
    const cb = typeof binding.value === "function" ? binding.value : () => {}
    const obs = new IntersectionObserver((entries) => {
        for (const entry of entries) cb(entry)
      }, { root: null, rootMargin: "0px", threshold: 0.1 })
    ;(el as any).__io__ = obs
    obs.observe(el)
  },
  unmounted(el) {
    const obs: IntersectionObserver | undefined = (el as any).__io__
    if (obs) {
      obs.unobserve(el)
      obs.disconnect()
      delete (el as any).__io__
    }
  }
}

export default intersect
