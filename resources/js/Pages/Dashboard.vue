<script setup>
import { computed, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ChartArea from '@/Components/Common/ChartArea.vue'
import AgentProgress from '@/Components/Common/AgentProgress.vue'
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
    agent_total: { type: Number, required: true },
    agents:    { type: Object, required: true },
    platforms: { type: Array, required: true },
    filters:   { type: Object, default: () => ({}) },
})

const page = usePage()
const canManage = computed(() => manageRoles.includes(page.props.auth.user?.role))
const flash = computed(() => page.props.flash ?? {})
const addOpen = ref(false)
const editingId = ref(null)
const editOpen = computed(() => editingId.value !== null)

const closeEdit = (open) => {
    if (!open) editingId.value = null
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-neutral-900">Dashboard</h1>
        </template>

        <div class="mx-auto flex max-w-7xl flex-col gap-6">
            <p
                v-if="flash.success"
                role="status"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            >
                {{ flash.success }}
            </p>
            <p
                v-if="flash.error"
                role="alert"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ flash.error }}
            </p>

            <!-- Progress target agent -->
            <AgentProgress :achieved="props.agent_total" />

            <!-- Section Chart (Stacked Bar) -->
            <section aria-label="Agent statistics per platform" class="grid gap-4 md:grid-cols-3">
                <div v-for="c in chartCards" :key="c.key" class="rounded-lg border bg-white p-4">
                    <ChartArea
                        :name="c.key"
                        :title="c.title"
                        :platforms="props.stats[c.key]"
                    />
                </div>
            </section>

            <!-- Section Tabel -->
            <section aria-label="Agent list" class="flex flex-col gap-3 rounded-lg border bg-white p-4">
                <AgentFilters :filters="filters" :can-manage="canManage" @add="addOpen = true" />
                <AgentTable :agents="agents.data" :platforms="platforms" :can-manage="canManage" @open="editingId = $event" />
                <Pagination :meta="agents" />
            </section>
        </div>

        <AgentFormCard v-model:open="addOpen" :platforms="platforms" />
        <AgentFormCard
            :open="editOpen"
            :agent-id="editingId"
            :platforms="platforms"
            @update:open="closeEdit"
        />

    </AuthenticatedLayout>
</template>