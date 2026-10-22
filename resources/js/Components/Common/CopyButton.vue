<script setup>
import { ref } from 'vue'
import { Check, Copy } from '@lucide/vue'

// objek data di atas
const props = defineProps({
    value:  { type: String, required: true },
    label:  { type: String, required: true },
    testId: { type: String, default: null }, // default = label
})
const copied = ref(false)

const copy = async () => {
    await navigator.clipboard.writeText(props.value) // butuh HTTPS / localhost cuy
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
}
</script>

<template>
    <button
        type="button"
        :data-testid="`copy-${testId ?? label}`"
        :aria-label="`Salin ${label}`"
        class="inline-flex size-6 shrink-0 items-center justify-center rounded text-neutral-500 hover:text-neutral-900"
        @click="copy"
    >
        <component :is="copied ? Check : Copy" class="size-4" />
    </button>
</template>
