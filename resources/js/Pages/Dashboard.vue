<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ChartDonut from '@/Components/Common/ChartDonut.vue'

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
const labels = statuses.map((s) => s.label)
const colors = statuses.map((s) => s.color)

// Define props dengan benar
const props = defineProps({
    stats:     { type: Object, required: true },
    agents:    { type: Object, required: true }, // paginator Laravel
    platforms: { type: Array, required: true },
    filters:   { type: Object, default: () => ({}) },
})

// Fungsi turunan dipanggil setelah props didefinisikan
const chartValues = (key) => statuses.map((s) => props.stats[key][s.key])
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
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

        <!-- Section Tabel (Versi polos sementara, nanti diganti AgentTable di D5) -->
        <section aria-label="Daftar agent" class="mt-6">
            <table data-testid="agent-table" class="w-full border">
                <thead class="bg-neutral-100">
                    <tr>
                        <th class="p-2 text-left">Nama</th>
                        <th class="p-2 text-left">Device</th>
                        <th v-for="p in platforms" :key="p.id" class="p-2 text-left">{{ p.name }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="agent in agents.data" :key="agent.id" class="border-t">
                        <td class="p-2">{{ agent.name }}</td>
                        <td class="p-2">{{ agent.device_user }}</td>
                        <td v-for="p in platforms" :key="p.id" class="p-2">{{ agent.accounts[p.slug] ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AuthenticatedLayout>
</template>