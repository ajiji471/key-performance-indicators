<script setup>
import { computed } from 'vue'

const props = defineProps({
    achieved: { type: Number, required: true },
    target:   { type: Number, default: 200 },
})

const percent = computed(() => Math.min(Math.round((props.achieved / props.target) * 100), 100))
</script>

<template>
    <div class="rounded-lg border border-border bg-card p-4" data-testid="agent-progress">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-medium text-foreground">Agent progress</p>
            <p class="text-sm text-muted-foreground">
                {{ achieved }} of {{ target }} agents
                <span class="ml-1 font-medium text-foreground">{{ percent }}%</span>
            </p>
        </div>

        <div
            role="progressbar"
            class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-muted"
            aria-label="Agent target progress"
            :aria-valuenow="achieved"
            aria-valuemin="0"
            :aria-valuemax="target"
        >
            <div
                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                :style="{ width: `${percent}%` }"
            />
        </div>
    </div>
</template>
