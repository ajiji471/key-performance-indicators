<script setup>
import { DrawerRoot } from 'vaul-vue'
import { useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
    open: { type: Boolean, required: false },
    defaultOpen: { type: Boolean, required: false },
    direction: { type: String, required: false, default: 'bottom' },
    shouldScaleBackground: { type: Boolean, required: false, default: false },
    modal: { type: Boolean, required: false },
    dismissible: { type: Boolean, required: false },
    handleOnly: { type: Boolean, required: false },
    snapPoints: { type: Array, required: false },
    activeSnapPoint: { type: [String, Number], required: false },
    fadeFromIndex: { type: Number, required: false },
    scrollLockTimeout: { type: Number, required: false },
    repositionInputs: { type: Boolean, required: false },
})
const emits = defineEmits(['update:open', 'update:activeSnapPoint', 'release', 'animationEnd'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
    <DrawerRoot v-slot="slotProps" data-slot="drawer" v-bind="forwarded">
        <slot v-bind="slotProps" />
    </DrawerRoot>
</template>