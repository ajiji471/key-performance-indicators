<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputerKpiCard from '@/Components/Kpi/InputerKpiCard.vue'

const scopeDescriptions = {
    all: 'Showing results for all inputers.',
    self: 'Showing results for your own profile.',
}

defineProps({
    kpi: { type: Object, required: true },
})
</script>

<template>
    <Head title="KPI Inputer" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="truncate text-base font-semibold text-foreground">KPI Inputer</h1>
        </template>

        <div class="space-y-6">
            <section aria-label="Page notes" class="space-y-1">
                <p class="text-sm text-foreground">{{ scopeDescriptions[kpi.scope] }}</p>
                <p class="text-xs text-muted-foreground">
                    Time zone: {{ kpi.timezone }} · Weeks run Monday–Sunday
                </p>
            </section>

            <p
                v-if="!kpi.has_activity"
                data-testid="kpi-no-activity"
                class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground"
            >
                No credits recorded yet
            </p>

            <p
                v-if="!kpi.inputers.length"
                data-testid="kpi-empty"
                class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground"
            >
                No inputer profiles yet
            </p>

            <section v-else aria-label="Inputer profile list" class="space-y-4">
                <InputerKpiCard
                    v-for="inputer in kpi.inputers"
                    :key="inputer.inputer_id"
                    :inputer="inputer"
                    :periods="kpi.periods"
                    :is-self="kpi.scope === 'self'"
                />
            </section>

            <footer class="space-y-1 border-t border-border pt-4 text-xs text-muted-foreground">
                <p>7 platform credits = 1 agent. Email counts come from Google. Registered and login are recorded separately.</p>
                <p>Data only covers credits since recording was enabled. Old data is not backfilled. Accounts recorded directly as login only earn login credits.</p>
            </footer>
        </div>
    </AuthenticatedLayout>
</template>
