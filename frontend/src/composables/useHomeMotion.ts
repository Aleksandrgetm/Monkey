import { onMounted, onUnmounted, type Ref } from 'vue'
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

/** MatchMedia reverts all animations on preference changes and on unmount. */
export function useHomeMotion(root: Ref<HTMLElement | null>) {
  let media: gsap.MatchMedia | undefined
  onMounted(() => {
    if (!root.value) return
    media = gsap.matchMedia()
    media.add('(prefers-reduced-motion: no-preference)', () => {
      const ctx = gsap.context(() => {
        gsap.timeline({ defaults: { ease: 'power3.out' } })
          .from('.hero-line', { y: 35, opacity: 0, duration: .75, stagger: .1 })
          .from('.hero-enter', { y: 15, opacity: 0, duration: .6, stagger: .09 }, .15)
          .from('.hero-receipt', { x: -35, rotation: -15, opacity: 0, duration: .9 }, .15)
          .from('.digital-card', { y: 45, rotation: 5, opacity: 0, duration: 1 }, .35)
          .from('.saved-toast', { y: 18, opacity: 0, duration: .6 }, .85)
        gsap.utils.toArray<HTMLElement>('.reveal').forEach(el => {
          gsap.from(el, { y: 24, opacity: 0, duration: .7, ease: 'power2.out', scrollTrigger: { trigger: el, start: 'top 94%', once: true } })
        })
        gsap.from('.step-track > div', { scaleX: 0, transformOrigin: 'left', ease: 'none', scrollTrigger: { trigger: '.steps', start: 'top 85%', end: 'bottom 65%', scrub: .6 } })
        gsap.from('.notification', { y: 28, opacity: 0, duration: .8, scrollTrigger: { trigger: '.notification', start: 'top 90%', once: true } })
      }, root.value!)
      return () => ctx.revert()
    })
    media.add('(min-width: 1024px) and (prefers-reduced-motion: no-preference)', () => {
      const ctx = gsap.context(() => {
        gsap.to('.hero-receipt', { x: 45, y: 25, rotation: 0, ease: 'none', scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: 1 } })
        gsap.from('.story-file', { x: -55, rotation: -6, scrollTrigger: { trigger: '.story-art', start: 'top 85%', end: 'center 55%', scrub: .7 } })
        gsap.to('.story-receipt', { x: 25, rotation: -3, scrollTrigger: { trigger: '.story-art', start: 'top 85%', end: 'center 55%', scrub: .7 } })
      }, root.value!)
      return () => ctx.revert()
    })
  })
  onUnmounted(() => media?.revert())
}
