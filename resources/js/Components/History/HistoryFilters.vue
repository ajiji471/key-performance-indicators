<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
})

const state = reactive({
    from: props.filters.from ?? '',
    until: props.filters.until ?? '',
})

const visit = (query) => {
    router.get(route('history'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

const apply = () => {
    const query = Object.fromEntries(
        Object.entries(state).filter(([, value]) => value !== '' && value != null)
    )
    visit(query)
}

const reset = () => {
    state.from = ''
    state.until = ''
    visit({})
}
</script>

<template>
    <form class="flex flex-wrap items-end gap-3" @submit.prevent="apply">
        <div class="flex flex-col gap-1.5">
            <Label for="history-from" class="text-xs text-muted-foreground">From</Label>
            <Input
                id="history-from"
                v-model="state.from"
                type="date"
                aria-label="From date"
                data-testid="history-from"
                class="w-full sm:w-44"
            />
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="history-until" class="text-xs text-muted-foreground">Until</Label>
            <Input
                id="history-until"
                v-model="state.until"
                type="date"
                aria-label="Until date"
                data-testid="history-until"
                class="w-full sm:w-44"
            />
        </div>

        <div class="flex items-center gap-2">
            <Button type="submit" data-testid="history-apply">Apply</Button>
            <Button type="button" variant="outline" data-testid="history-reset" @click="reset">
                Reset
            </Button>
        </div>
    </form>
</template>