<script setup>
import { nextTick, provide, ref, watch } from 'vue'
import { useMediaQuery } from '@vueuse/core'
import { usePage } from '@inertiajs/vue3'
import { SIDEBAR_KEY } from './context'

const props = defineProps({
    defaultOpenMobile: { type: Boolean, default: false },
})

const page = usePage()
const isMobile = useMediaQuery('(max-width: 1023px)')
const openMobile = ref(props.defaultOpenMobile)

const setOpenMobile = (open) => {
    openMobile.value = open
}

watch(openMobile, async (open) => {
    if (open || !isMobile.value) return

    await nextTick()
    // tunggu animasi keluar sheet selesai sebelum mengembalikan fokus
    setTimeout(() => {
        if (openMobile.value) return
        document.querySelector('[data-testid="sidebar-toggle"]')?.focus()
    }, 250)
})

watch(() => page.url, () => setOpenMobile(false))

provide(SIDEBAR_KEY, { isMobile, openMobile, setOpenMobile })
</script>

<template>
    <div class="flex min-h-screen w-full bg-muted/40">
        <slot />
    </div>
</template>
