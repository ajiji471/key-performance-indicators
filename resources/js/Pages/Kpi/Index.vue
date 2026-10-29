<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
</script>

<template>
    <Head title="KPI" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="truncate text-base font-semibold text-neutral-900">KPI</h1>
        </template>

        <div class="rounded-lg border bg-white p-6">
            <p class="text-sm text-neutral-700">ini halaman kpi</p>
        </div>
    </AuthenticatedLayout>
</template>
