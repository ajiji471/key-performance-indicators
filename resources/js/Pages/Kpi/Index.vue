<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputerKpiCard from '@/Components/Kpi/InputerKpiCard.vue'

const scopeDescriptions = {
    all: 'Menampilkan hasil semua inputer.',
    self: 'Menampilkan hasil profil Anda sendiri.',
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
            <section aria-label="Keterangan halaman" class="space-y-1">
                <p class="text-sm text-foreground">{{ scopeDescriptions[kpi.scope] }}</p>
                <p class="text-xs text-muted-foreground">
                    Zona waktu: {{ kpi.timezone }} · Minggu dihitung Senin–Minggu
                </p>
            </section>

            <p
                v-if="!kpi.has_activity"
                data-testid="kpi-no-activity"
                class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground"
            >
                Belum ada kredit tercatat
            </p>

            <p
                v-if="!kpi.inputers.length"
                data-testid="kpi-empty"
                class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground"
            >
                Belum ada profil inputer
            </p>

            <section v-else aria-label="Daftar profil inputer" class="space-y-4">
                <InputerKpiCard
                    v-for="inputer in kpi.inputers"
                    :key="inputer.inputer_id"
                    :inputer="inputer"
                    :periods="kpi.periods"
                    :is-self="kpi.scope === 'self'"
                />
            </section>

            <footer class="space-y-1 border-t border-border pt-4 text-xs text-muted-foreground">
                <p>7 kredit platform = 1 agent. Email dihitung dari Google. Registered dan login dicatat terpisah.</p>
                <p>Data hanya mencakup kredit sejak pencatatan diaktifkan. Data lama tidak diisi otomatis. Akun yang langsung dicatat login hanya mendapat kredit login.</p>
            </footer>
        </div>
    </AuthenticatedLayout>
</template>
