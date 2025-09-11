import { ref, onMounted, onBeforeUnmount } from 'vue'

export function useInView(rootMargin = '200px') {
  const el = ref<HTMLElement | null>(null)
  const isInView = ref(false)
  let obs: IntersectionObserver | null = null

  onMounted(() => {
    obs = new IntersectionObserver(
      (entries) => {
        const e = entries[0]
        if (e && e.isIntersecting) {
          isInView.value = true
          if (obs && el.value) obs.unobserve(el.value)
        }
      },
      { root: null, rootMargin, threshold: 0.01 }
    )
    if (el.value) obs.observe(el.value)
  })

  onBeforeUnmount(() => {
    if (obs && el.value) obs.unobserve(el.value)
    obs = null
  })

  return { el, isInView }
}
