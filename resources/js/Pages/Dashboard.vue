<script setup>
import { computed, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ChartBar from '@/Components/Common/ChartBar.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import AgentFilters from '@/Components/Agents/AgentFilters.vue'
import AgentTable from '@/Components/Agents/AgentTable.vue'
import AgentFormCard from '@/Components/Agents/AgentFormCard.vue'


// objek data di atas
const chartCards = [
    { key: 'today', title: 'Today' },
    { key: 'month', title: 'Month' },
    { key: 'all',   title: 'All' },
]

const manageRoles = ['admin', 'user']

const props = defineProps({
    stats:     { type: Object, required: true },
    agents:    { type: Object, required: true },
    platforms: { type: Array, required: true },
    filters:   { type: Object, default: () => ({}) },
})

const page = usePage()
const canManage = computed(() => manageRoles.includes(page.props.auth.user.role))
const addOpen = ref(false)
const profileId = ref(null)
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-neutral-900">Dashboard</h1>
        </template>

        <div class="mx-auto flex max-w-7xl flex-col gap-6">
            <!-- Section Chart (Stacked Bar) -->
            <section aria-label="Statistik agent per platform" class="grid gap-4 md:grid-cols-3">
                <div v-for="c in chartCards" :key="c.key" class="rounded-lg border bg-white p-4">
                    <ChartBar
                        :name="c.key"
                        :title="c.title"
                        :platforms="props.stats[c.key]"
                    />
                </div>
            </section>

            <!-- Section Tabel -->
            <section aria-label="Daftar agent" class="flex flex-col gap-3 rounded-lg border bg-white p-4">
                <AgentFilters :filters="filters" :can-manage="canManage" @add="addOpen = true" />
                <AgentTable :agents="agents.data" :platforms="platforms" :can-manage="canManage" @open="profileId = $event" />
                <Pagination :meta="agents" />
            </section>
        </div>

        <AgentFormCard v-model:open="addOpen" :platforms="platforms" />

    </AuthenticatedLayout>
</template>