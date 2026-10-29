<script setup>
import { inject } from 'vue'
import { Menu } from '@lucide/vue'
import { Button } from '@/Components/ui/button'
import { SIDEBAR_KEY } from './context'

const props = defineProps({
    class: {
        type: [Boolean, null, String, Object, Array],
        required: false,
        skipCheck: true,
    },
})

const { setOpenMobile } = inject(SIDEBAR_KEY)
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        size="icon"
        :class="props.class"
        aria-label="Open navigation"
        aria-controls="app-sidebar-mobile"
        data-testid="sidebar-toggle"
        @click="setOpenMobile(true)"
    >
        <Menu aria-hidden="true" />
    </Button>
</template>
