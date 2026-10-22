<script setup>
import { computed, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ChartDonut from '@/Components/Common/ChartDonut.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import AgentFilters from '@/Components/Agents/AgentFilters.vue'
import AgentTable from '@/Components/Agents/AgentTable.vue'

// Objek data di atas (jangan dicampur style)
const chartCards = [
    { key: 'today', title: 'Hari ini' },
    { key: 'month', title: 'Bulan ini' },
    { key: 'all',   title: 'Semua' },
]

const statuses = [
    { key: 'logged_in',  label: 'Logged in',  color: '#22c55e' },
    { key: 'registered', label: 'Registered', color: '#3b82f6' },
]
const manageRoles = ['admin', 'user']
const labels = statuses.map((s) => s.label)
const colors = statuses.map((s) => s.color)

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

const chartValues = (key) => statuses.map((s) => props.stats[key][s.key])
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-base font-semibold text-neutral-900">Dashboard</h1>
        </template>

        <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4">
            <!-- Section Chart -->
            <section aria-label="Statistik agent" class="grid gap-4 md:grid-cols-3">
                <div v-for="c in chartCards" :key="c.key" class="rounded-lg border bg-white p-4">
                    <ChartDonut
                        :name="c.key"
                        :title="c.title"
                        :labels="labels"
                        :values="chartValues(c.key)"
                        :colors="colors"
                    />
                </div>
            </section>

            <!-- Section Tabel dengan wrapper atomic CSS -->
            <section aria-label="Daftar agent" class="flex flex-col gap-3 rounded-lg border bg-white p-4">
                <AgentFilters 
                    :filters="filters" 
                    :platforms="platforms" 
                    :can-manage="canManage" 
                    @add="addOpen = true" 
                />
                <AgentTable 
                    :agents="agents.data" 
                    :platforms="platforms" 
                    :can-manage="canManage" 
                    @open="profileId = $event" 
                />
                <Pagination :meta="agents" />
            </section>
        </div>
    </AuthenticatedLayout>
</template>