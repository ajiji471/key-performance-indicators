<script setup>
import { reactiveOmit } from '@vueuse/core'
import { DrawerContent, DrawerPortal } from 'vaul-vue'
import { useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/lib/utils'
import DrawerOverlay from './DrawerOverlay.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    forceMount: { type: Boolean, required: false },
    disableOutsidePointerEvents: { type: Boolean, required: false },
    asChild: { type: Boolean, required: false },
    as: { type: null, required: false },
    class: {
        type: [Boolean, null, String, Object, Array],
        required: false,
        skipCheck: true,
    },
})
const emits = defineEmits([
    'escapeKeyDown',
    'pointerDownOutside',
    'focusOutside',
    'interactOutside',
    'openAutoFocus',
    'closeAutoFocus',
])

const delegatedProps = reactiveOmit(props, 'class')
const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
    <DrawerPortal>
        <DrawerOverlay />
        <DrawerContent
            data-slot="drawer-content"
            v-bind="{ ...$attrs, ...forwarded }"
            :class="cn('drawer-panel', props.class)"
        >
            <slot />
        </DrawerContent>
    </DrawerPortal>
</template>