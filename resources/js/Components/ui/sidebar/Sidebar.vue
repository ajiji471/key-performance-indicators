<script setup>
import { computed, inject } from 'vue'
import { Sheet, SheetContent } from '@/Components/ui/sheet'
import { SIDEBAR_KEY } from './context'

const { isMobile, openMobile, setOpenMobile } = inject(SIDEBAR_KEY)

const mobileOpen = computed({
    get: () => openMobile.value,
    set: (open) => setOpenMobile(open),
})
</script>

<template>
    <aside
        v-if="!isMobile"
        data-slot="sidebar"
        class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:flex"
    >
        <slot />
    </aside>

    <Sheet v-else v-model:open="mobileOpen">
        <SheetContent id="app-sidebar-mobile" data-slot="sidebar-mobile" class="w-72">
            <slot />
        </SheetContent>
    </Sheet>
</template>
