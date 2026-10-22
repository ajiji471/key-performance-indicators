<script setup>
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowDownAZ, ArrowUpAZ, Plus } from '@lucide/vue'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'

// objek data di atas
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

// kirim cuma nilai yg terisi, spy URL bersih (page otomatis balik ke 1)
const apply = () => {
    const query = Object.fromEntries(Object.entries(state).filter(([, v]) => v !== ''))

    router.get(route('dashboard'), query, { preserveState: true, preserveScroll: true, replace: true })
}

let timer
watch(() => state.search, () => {
    clearTimeout(timer)
    timer = setTimeout(apply, 300) // debounce
})
watch(() => [state.platform, state.sort], apply)

const toggleSort = () => (state.sort = state.sort === 'asc' ? 'desc' : 'asc')
</script>

<template>
    <form role="search" @submit.prevent="apply">
        <input
            v-model="state.search"
            type="search"
            placeholder="Cari nama / email"
            aria-label="Cari agent"
            data-testid="agent-search"
        />

        <select v-model="state.platform" aria-label="Filter platform" data-testid="agent-filter-platform">
            <option value="">Semua platform</option>
            <option v-for="p in platforms" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>

        <button type="button" aria-label="Urutkan nama" data-testid="agent-sort" @click="toggleSort">
            {{ state.sort === 'asc' ? 'A-Z' : 'Z-A' }}
        </button>

        <button v-if="canManage" type="button" aria-label="Tambah agent" data-testid="agent-add" @click="emit('add')">
            + add
        </button>
    </form>
</template>
