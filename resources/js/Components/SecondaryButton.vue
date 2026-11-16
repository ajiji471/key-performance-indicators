<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-border bg-card px-4 py-2 text-xs font-semibold uppercase tracking-widest text-foreground shadow-sm transition duration-150 ease-in-out hover:bg-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-25"
    >
        <slot />
    </button>
</template>
