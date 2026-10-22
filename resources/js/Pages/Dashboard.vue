<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// objek data di atas
defineProps({
    stats:     { type: Object, required: true },
    agents:    { type: Object, required: true }, // paginator Laravel
    platforms: { type: Array, required: true },
    filters:   { type: Object, default: () => ({}) },
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <pre data-testid="stats">{{ stats }}</pre>

        <table data-testid="agent-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Device</th>
                    <th v-for="p in platforms" :key="p.id">{{ p.name }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="agent in agents.data" :key="agent.id">
                    <td>{{ agent.name }}</td>
                    <td>{{ agent.device_user }}</td>
                    <td v-for="p in platforms" :key="p.id">{{ agent.accounts[p.slug] ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </AuthenticatedLayout>
</template>
