<script setup>
import { DialogRoot } from 'reka-ui'

defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['update:open'])
</script>

<template>
    <DialogRoot :open="open" @update:open="emit('update:open', $event)">
        <slot />
    </DialogRoot>
</template>
