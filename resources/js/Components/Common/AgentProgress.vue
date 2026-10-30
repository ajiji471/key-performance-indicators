<script setup>
import { computed } from 'vue'

const props = defineProps({
    achieved: { type: Number, required: true },
    target:   { type: Number, default: 200 },
})

const percent = computed(() => Math.min(Math.round((props.achieved / props.target) * 100), 100))
</script>

<template>
    <div class="rounded-lg border bg-white p-4" data-testid="agent-progress">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-medium text-neutral-900">Progress Agent</p>
            <p class="text-sm text-neutral-500">
                {{ achieved }} of {{ target }} agent
                <span class="ml-1 font-medium text-neutral-900">{{ percent }}%</span>
            </p>
        </div>

        <div
            role="progressbar"
            class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-neutral-100"
            aria-label="Progress target agent"
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
