<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowDownAZ, ArrowUpAZ, Plus } from '@lucide/vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'

const props = defineProps({
    filters:   { type: Object, default: () => ({}) },
    platforms: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
})

const emit = defineEmits(['add'])

const state = reactive({
    search:   props.filters.search ?? '',
    platform: props.filters.platform ?? '',
    sort:     props.filters.sort ?? 'asc',
})

const apply = () => {
    const query = Object.fromEntries(
        Object.entries(state).filter(([, v]) => v !== '')
    )
    router.get(route('dashboard'), query, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true 
    })
}

let timer
watch(() => state.search, () => {
    clearTimeout(timer)
    timer = setTimeout(apply, 300)
})

watch(() => [state.platform, state.sort], apply)

const toggleSort = () => {
    state.sort = state.sort === 'asc' ? 'desc' : 'asc'
}
</script>

<template>
    <form role="search" class="flex flex-wrap items-center gap-2" @submit.prevent="apply">
        <Input
            v-model="state.search"
            type="search"
            placeholder="Cari nama / email"
            aria-label="Cari agent"
            data-testid="agent-search"
            class="w-full sm:w-64"
        />
        <select
            v-model="state.platform"
            aria-label="Filter platform"
            data-testid="agent-filter-platform"
            class="rounded-md border border-neutral-300 py-2 text-sm"
        >
            <option value="">Semua platform</option>
            <option v-for="p in platforms" :key="p.id" :value="p.id">
                {{ p.name }}
            </option>
        </select>
        <Button 
            type="button" 
            variant="outline" 
            aria-label="Urutkan nama" 
            data-testid="agent-sort" 
            @click="toggleSort"
        >
            <component :is="state.sort === 'asc' ? ArrowDownAZ : ArrowUpAZ" class="size-4" />
            {{ state.sort === 'asc' ? 'A-Z' : 'Z-A' }}
        </Button>
        <Button
            v-if="canManage"
            type="button"
            aria-label="Tambah agent"
            data-testid="agent-add"
            class="ml-auto sm:ml-0"
            @click="emit('add')"
        >
            <Plus class="size-4" />
            add
        </Button>
    </form>
</template>