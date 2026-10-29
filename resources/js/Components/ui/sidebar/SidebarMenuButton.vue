<script setup>
import { cn } from '@/lib/utils'
import { sidebarMenuButtonVariants } from './variants'

const props = defineProps({
    as: { type: [String, Object], default: 'button' },
    class: {
        type: [Boolean, null, String, Object, Array],
        required: false,
        skipCheck: true,
    },
    isActive: { type: Boolean, default: false },
    variant: { type: String, default: 'default' },
    size: { type: String, default: 'default' },
})
</script>

<template>
    <component
        :is="props.as"
        data-slot="sidebar-menu-button"
        :data-active="props.isActive"
        :class="cn(sidebarMenuButtonVariants({ variant: props.variant, size: props.size }), props.class)"
    >
        <slot />
    </component>
</template>
