<script setup>
import { computed } from 'vue'
import { Card, CardContent, CardHeader } from '@/Components/ui/card'

const props = defineProps({
    inputer: { type: Object, required: true },
    periods: { type: Object, required: true },
    isSelf: { type: Boolean, default: false },
})

const initials = computed(() => props.inputer.name.trim().split(/\s+/u)
    .filter(Boolean).slice(0, 2).map(part => Array.from(part)[0]).join('').toLocaleUpperCase('id-ID'))
const emailFormat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 })
const agentFormat = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>

<template>
    <Card data-testid="inputer-kpi-card" :data-inputer-id="inputer.inputer_id">
        <CardHeader class="flex-row items-center gap-3">
            <span
                aria-hidden="true"
                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold text-foreground"
            >
                {{ initials }}
            </span>
            <div class="min-w-0">
                <h2 class="break-words text-base font-semibold text-foreground">{{ inputer.name }}</h2>
                <p v-if="isSelf || inputer.is_deleted" class="flex flex-wrap gap-x-2 text-xs text-muted-foreground">
                    <span v-if="isSelf" data-testid="kpi-self-label">Profil Anda</span>
                    <span v-if="inputer.is_deleted" data-testid="kpi-deleted-label">Histori terhapus · ID {{ inputer.inputer_id }}</span>
                </p>
            </div>
        </CardHeader>

        <CardContent>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <section
                    v-for="(period, key) in periods"
                    :key="key"
                    :data-testid="'kpi-period-' + key"
                    class="rounded-lg border border-border bg-muted/30 p-4"
                >
                    <h3 class="mb-2 text-sm font-semibold text-foreground">{{ period.label }}</h3>
                    <table class="w-full text-sm text-foreground">
                        <caption class="sr-only">{{ inputer.name }} — {{ period.label }}</caption>
                        <thead>
                            <tr class="border-b border-border text-left text-xs text-muted-foreground">
                                <th scope="col" class="py-2 pr-3 font-medium">Metrik</th>
                                <th scope="col" class="py-2 pr-3 font-medium">Registered</th>
                                <th scope="col" class="py-2 font-medium">Login</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-border">
                                <th scope="row" class="py-2 pr-3 text-left font-medium">Email</th>
                                <td class="py-2 pr-3 tabular-nums">{{ emailFormat.format(inputer.periods[key].email_registered) }}</td>
                                <td class="py-2 tabular-nums">{{ emailFormat.format(inputer.periods[key].email_login) }}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="py-2 pr-3 text-left font-medium">Agent</th>
                                <td class="py-2 pr-3 tabular-nums">
                                    {{ agentFormat.format(inputer.periods[key].agent_registered) }}
                                    <span class="block text-xs font-normal text-muted-foreground">{{ emailFormat.format(inputer.periods[key].registered_credits) }} kredit platform</span>
                                </td>
                                <td class="py-2 tabular-nums">
                                    {{ agentFormat.format(inputer.periods[key].agent_login) }}
                                    <span class="block text-xs font-normal text-muted-foreground">{{ emailFormat.format(inputer.periods[key].login_credits) }} kredit platform</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </CardContent>
    </Card>
</template>
