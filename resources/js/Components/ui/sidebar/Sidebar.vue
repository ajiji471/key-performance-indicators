<script setup>
import { computed, inject } from 'vue'
import { Drawer, DrawerContent, DrawerDescription, DrawerTitle } from '@/Components/ui/drawer'
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

    <Drawer v-else v-model:open="mobileOpen" direction="left">
        <DrawerContent id="app-sidebar-mobile" data-slot="sidebar-mobile">
            <DrawerTitle class="sr-only">Navigation menu</DrawerTitle>
            <DrawerDescription class="sr-only">Main application navigation</DrawerDescription>
            <slot />
        </DrawerContent>
    </Drawer>
</template>
