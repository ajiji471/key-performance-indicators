<script setup>
import { DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogTitle } from 'reka-ui'
import { cn } from '@/lib/utils'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    class: {
        type: [Boolean, null, String, Object, Array],
        required: false,
        skipCheck: true,
    },
})
</script>

<template>
    <DialogPortal>
        <DialogOverlay class="sheet-overlay fixed inset-0 z-50 bg-black/50" />
        <DialogContent
            v-bind="$attrs"
            :class="
                cn(
                    'sheet-panel fixed inset-y-0 left-0 z-50 flex h-full w-72 max-w-[80%] flex-col bg-sidebar text-sidebar-foreground p-0 shadow-lg outline-none',
                    props.class,
                )
            "
        >
            <DialogTitle class="sr-only">Menu navigasi</DialogTitle>
            <DialogDescription class="sr-only">Navigasi utama aplikasi</DialogDescription>
            <slot />
        </DialogContent>
    </DialogPortal>
</template>
