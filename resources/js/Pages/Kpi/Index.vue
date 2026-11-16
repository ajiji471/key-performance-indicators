<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
</script>

<template>
    <Head title="KPI" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="truncate text-base font-semibold text-foreground">KPI</h1>
        </template>

        <div class="rounded-lg border border-border bg-card p-6">
            <p class="text-sm text-muted-foreground">ini halaman kpi</p>
        </div>
    </AuthenticatedLayout>
</template>
