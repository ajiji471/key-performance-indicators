<script setup>
import { DrawerRoot } from 'vaul-vue'
import { useForwardPropsEmits } from 'reka-ui'

const props = defineProps({
    open: { type: Boolean, required: false },
    direction: { type: String, required: false, default: 'bottom' },
    shouldScaleBackground: { type: Boolean, required: false, default: false },
})
const emits = defineEmits(['update:open'])

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
    <DrawerRoot v-slot="slotProps" data-slot="drawer" v-bind="forwarded">
        <slot v-bind="slotProps" />
    </DrawerRoot>
</template>