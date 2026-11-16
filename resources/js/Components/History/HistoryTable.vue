<script setup>
defineProps({
    logs: { type: Array, required: true },
})

const verbs = { created: 'added', updated: 'updated', deleted: 'deleted' }

const whenFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
    timeZone: 'UTC',
})

const formatWhen = (value) => whenFormatter.format(new Date(value))

const activityText = (log) => {
    const verb = verbs[log.action] ?? log.action
    const who = log.inputer_name

    if (log.subject_type === 'agent') {
        return `${who} ${verb} Agent ${log.agent_name}`
    }

    const platform = log.platform_name ?? log.platform_slug ?? 'Platform'
    const identifier = log.identifier ? ` (${log.identifier})` : ''

    return `${who} ${verb} ${platform} account${identifier} for Agent ${log.agent_name}`
}
</script>

<template>
    <div class="flex flex-col gap-3 md:hidden">
        <article
            v-for="log in logs"
            :key="log.id"
            :data-testid="`history-card-${log.id}`"
            class="rounded-lg border border-border bg-card p-4"
        >
            <p class="font-mono text-xs text-muted-foreground">{{ formatWhen(log.occurred_at) }}</p>
            <p class="mt-1 text-sm text-foreground">{{ activityText(log) }}</p>
        </article>

        <p
            v-if="!logs.length"
            data-testid="history-empty-mobile"
            class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground"
        >
            No activity recorded yet
        </p>
    </div>

    <div
        class="hidden overflow-x-auto rounded-lg border border-border bg-card md:block"
        data-testid="history-table"
    >
        <table class="w-full text-left">
            <caption class="sr-only">Inputer activity history</caption>
            <thead class="border-b border-border bg-muted/50 text-xs uppercase text-muted-foreground">
                <tr>
                    <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">When</th>
                    <th scope="col" class="px-4 py-3 font-medium">Activity</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr
                    v-for="log in logs"
                    :key="log.id"
                    :data-testid="`history-row-${log.id}`"
                    class="hover:bg-muted/50"
                >
                    <td class="whitespace-nowrap px-4 py-3 align-top font-mono text-xs text-muted-foreground">
                        {{ formatWhen(log.occurred_at) }}
                    </td>
                    <td class="px-4 py-3 align-top text-sm text-foreground">
                        {{ activityText(log) }}
                    </td>
                </tr>

                <tr v-if="!logs.length">
                    <td
                        colspan="2"
                        data-testid="history-empty"
                        class="py-12 text-center text-muted-foreground"
                    >
                        No activity recorded yet
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>