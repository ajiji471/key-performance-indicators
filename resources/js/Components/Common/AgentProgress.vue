<script setup>
import { computed } from 'vue'
import { Pencil } from '@lucide/vue'
import { Button } from '@/Components/ui/button'

const props = defineProps({
    achieved: { type: Number, required: true },
    target:   { type: Number, default: 200 },
    canEdit:  { type: Boolean, default: false },
})

const emit = defineEmits(['edit'])

const fmt = new Intl.NumberFormat('en-US')
const percent = computed(() => Math.min(Math.round((props.achieved / props.target) * 100), 100))
</script>

<template>
    <div class="rounded-lg border border-border bg-card p-4" data-testid="agent-progress">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-medium text-foreground">Agent progress</p>
            <div class="flex items-center gap-2">
                <p class="text-sm text-muted-foreground">
                    {{ fmt.format(achieved) }} of {{ fmt.format(target) }} agents
                    <span class="ml-1 font-medium text-foreground">{{ percent }}%</span>
                </p>
                <Button
                    v-if="canEdit"
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Edit agent target"
                    data-testid="agent-target-edit"
                    @click="emit('edit')"
                >
                    <Pencil />
                </Button>
            </div>
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