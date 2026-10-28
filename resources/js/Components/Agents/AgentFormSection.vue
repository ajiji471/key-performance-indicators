<script setup>
defineProps({
    title:       { type: String, required: true },
    description: { type: String, default: '' },
})
</script>

<template>
    <section class="grid gap-3 border-t pt-4 first:border-t-0 first:pt-0">
        <div>
            <h3 class="text-sm font-semibold text-neutral-900">{{ title }}</h3>
            <p v-if="description" class="text-xs text-neutral-500">{{ description }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <slot />
        </div>
    </section>
</template>