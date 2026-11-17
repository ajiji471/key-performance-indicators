<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import HistoryFilters from '@/Components/History/HistoryFilters.vue'
import HistoryTable from '@/Components/History/HistoryTable.vue'
import Pagination from '@/Components/Common/Pagination.vue'

defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    timezone: { type: String, default: 'UTC' },
})
</script>

<template>
    <Head title="History" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="truncate text-base font-semibold text-foreground">History</h1>
        </template>

        <div class="space-y-6">
            <section aria-label="Page notes" class="space-y-1">
                <p class="text-sm text-foreground">
                    Activity log of inputers adding, editing, and deleting agents and platform accounts.
                </p>
                <p class="text-xs text-muted-foreground">Time zone: {{ timezone }}</p>
            </section>

            <HistoryFilters :filters="filters" />

            <HistoryTable :logs="logs.data" :timezone="timezone" />

            <Pagination :meta="logs" />
        </div>
    </AuthenticatedLayout>
</template>