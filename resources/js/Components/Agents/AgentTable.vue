<script setup>
import { CheckCircle, Clock, RefreshCw, XCircle, MoreVertical } from '@lucide/vue'
import CopyButton from '@/Components/Common/CopyButton.vue'

// sama persis dengan App\Enums\AccountStatus (dipakai form add/edit agent)
const statusConfig = {
    logged_in:  { label: 'Logged in',  color: 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/40 dark:text-green-300 dark:border-green-900', icon: CheckCircle },
    registered: { label: 'Registered', color: 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-950/40 dark:text-yellow-300 dark:border-yellow-900', icon: Clock },
    recovered:  { label: 'Recovered',  color: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900', icon: RefreshCw },
    trouble:    { label: 'Trouble',    color: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-300 dark:border-red-900', icon: XCircle },
}

const props = defineProps({
    agents:    { type: Array, required: true },
    platforms: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
})

const emit = defineEmits(['open'])

// Helper: cari data platform spesifik milik agent
const getAgentPlatform = (agent, slug) => {
    return agent.platforms?.find((p) => p.slug === slug) || null
}

// Helper: status akun platform (default registered, sama dengan form add/edit)
const getStatus = (agent, slug) => {
    const account = getAgentPlatform(agent, slug)
    if (!account) return null
    return statusConfig[account.status] ? account.status : 'registered'
}

// Helper: hitung persentase logged_in 
const getStatusPercentage = (agent) => {
    // Gunakan TOTAL semua platform dari props, bukan agent.platforms
    const allPlatforms = props.platforms
    if (!allPlatforms || allPlatforms.length === 0) return 0

    // Hitung berapa banyak platform yg statusnya 'logged_in' untuk agent ini
    const loggedInCount = allPlatforms.filter((p) => getStatus(agent, p.slug) === 'logged_in').length

    return Math.round((loggedInCount / allPlatforms.length) * 100)
}

// Helper: warna persentase
const getPercentageColor = (percentage) => {
    if (percentage === 100) return 'text-green-600 dark:text-green-400'
    if (percentage >= 50) return 'text-yellow-600 dark:text-yellow-400'
    if (percentage > 0) return 'text-orange-600 dark:text-orange-400'
    return 'text-red-600 dark:text-red-400'
}
</script>

<template>
    <div class="flex flex-col gap-3 md:hidden">
        <article
            v-for="agent in agents"
            :key="agent.id"
            :data-testid="`agent-card-${agent.id}`"
            class="rounded-lg border border-border bg-card p-4"
        >
            <div class="flex items-start gap-3">
                <img
                    v-if="agent.avatar_url"
                    :src="agent.avatar_url"
                    :alt="`Avatar ${agent.name}`"
                    class="size-10 shrink-0 rounded-full object-cover"
                />
                <div v-else class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted font-bold text-muted-foreground">
                    {{ agent.name.charAt(0).toUpperCase() }}
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-foreground">{{ agent.name }}</p>
                    <p class="truncate text-xs text-muted-foreground">Device : {{ agent.device_user || '-' }}</p>
                    <p class="text-xs text-muted-foreground/70">Created at: {{ agent.created_at ? new Date(agent.created_at + 'T00:00:00').toLocaleDateString('en-GB') : '-' }}</p>
                </div>

                <div class="flex shrink-0 items-center gap-1.5">
                    <span class="size-1.5 rounded-full" :class="getPercentageColor(getStatusPercentage(agent)).replaceAll('text-', 'bg-')" />
                    <span class="font-mono text-sm font-medium" :class="getPercentageColor(getStatusPercentage(agent))">
                        {{ getStatusPercentage(agent) }}%
                    </span>
                    <button
                        v-if="canManage"
                        type="button"
                        :aria-label="`Edit profile ${agent.name}`"
                        :data-testid="`agent-open-mobile-${agent.id}`"
                        class="rounded p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                        @click="emit('open', agent.id)"
                    >
                        <MoreVertical class="size-4" />
                    </button>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-border pt-3">
                <div
                    v-for="p in platforms"
                    :key="p.id"
                    class="group inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs"
                    :class="getStatus(agent, p.slug)
                        ? statusConfig[getStatus(agent, p.slug)].color
                        : 'bg-muted text-muted-foreground border-border'"
                    :title="getStatus(agent, p.slug)
                        ? statusConfig[getStatus(agent, p.slug)].label
                        : 'Not registered'"
                    :aria-label="`${p.name}: ${getStatus(agent, p.slug) ? statusConfig[getStatus(agent, p.slug)].label : 'Not registered'}`"
                    :data-testid="`agent-platform-mobile-${agent.id}-${p.slug}`"
                >
                    <span class="font-medium">{{ p.name }}</span>

                    <component
                        v-if="getStatus(agent, p.slug)"
                        :is="statusConfig[getStatus(agent, p.slug)].icon"
                        class="size-3.5 shrink-0"
                    />

                    <span
                        v-if="getAgentPlatform(agent, p.slug)?.identifier"
                        class="text-muted-foreground/50 px-0.5"
                    >|</span>

                    <CopyButton
                        v-if="getAgentPlatform(agent, p.slug)?.identifier"
                        :value="getAgentPlatform(agent, p.slug).identifier"
                        :label="`${p.name} ${agent.name}`"
                        :test-id="`mobile-${p.slug}-${agent.id}`"
                        class="shrink-0 size-2"
                    />
                </div>
            </div>
        </article>

        <p v-if="!agents.length" class="py-8 text-center text-muted-foreground">
            No agents yet
        </p>
    </div>

    <div class="hidden overflow-x-auto rounded-lg border border-border bg-card md:block">
        <table class="w-full whitespace-nowrap text-left">
            <thead class="border-b border-border bg-muted/50 text-xs uppercase text-muted-foreground">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Complete</th>
                    <th class="px-4 py-3 font-medium">Platforms</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr 
                    v-for="agent in agents" 
                    :key="agent.id" 
                    :data-testid="`agent-row-${agent.id}`" 
                    class="hover:bg-muted/50"
                >
                    <!-- Name Column -->
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="agent.avatar_url"
                                :src="agent.avatar_url"
                                :alt="`Avatar ${agent.name}`"
                                class="size-10 shrink-0 rounded-full object-cover"
                            />
                            <div v-else class="size-10 shrink-0 rounded-full bg-muted flex items-center justify-center text-muted-foreground font-bold">
                                {{ agent.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="font-medium text-foreground">{{ agent.name }}</div>
                                <div class="text-xs text-muted-foreground">Device : {{ agent.device_user || '-' }}</div>
                                <div class="text-xs text-muted-foreground/70">Created at: {{ agent.created_at ? new Date(agent.created_at + 'T00:00:00').toLocaleDateString('en-GB') : '-' }}</div>
                            </div>
                        </div>
                    </td>

                    <!-- Complete Rate Column -->
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full" :class="getPercentageColor(getStatusPercentage(agent)).replaceAll('text-', 'bg-')" />
                            <span class="font-mono text-sm font-medium" :class="getPercentageColor(getStatusPercentage(agent))">
                                {{ getStatusPercentage(agent) }}%
                            </span>
                        </div>
                    </td>

                    <!-- Platforms Column -->
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <div
                                v-for="p in platforms"
                                :key="p.id"
                                class="group inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-xs"
                                :class="getStatus(agent, p.slug) 
                                    ? statusConfig[getStatus(agent, p.slug)].color 
                                    : 'bg-muted text-muted-foreground border-border'"
                                :title="getStatus(agent, p.slug) 
                                    ? statusConfig[getStatus(agent, p.slug)].label 
                                    : 'Not registered'"
                                :aria-label="`${p.name}: ${getStatus(agent, p.slug) ? statusConfig[getStatus(agent, p.slug)].label : 'Not registered'}`"
                                :data-testid="`agent-platform-${agent.id}-${p.slug}`"
                            >
                                <span class="font-medium">{{ p.name }}</span>
                                
                                <component 
                                    v-if="getStatus(agent, p.slug)"
                                    :is="statusConfig[getStatus(agent, p.slug)].icon" 
                                    class="size-3.5 shrink-0" 
                                />
                                
                                <span 
                                    v-if="getAgentPlatform(agent, p.slug)?.identifier" 
                                    class="text-muted-foreground/50 px-0.5"
                                >|</span>
                                
                                <CopyButton
                                    v-if="getAgentPlatform(agent, p.slug)?.identifier"
                                    :value="getAgentPlatform(agent, p.slug).identifier"
                                    :label="`${p.name} ${agent.name}`"
                                    :test-id="`${p.slug}-${agent.id}`"
                                    class="shrink-0 size-2"
                                />
                            </div>
                        </div>
                    </td>

                    <!-- Actions Column -->
                    <td class="px-4 py-3 text-right">
                        <button
                            v-if="canManage"
                            type="button"
                            :aria-label="`Edit profile ${agent.name}`"
                            :data-testid="`agent-open-${agent.id}`"
                            class="rounded p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="emit('open', agent.id)"
                        >
                            <MoreVertical class="size-4" />
                        </button>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="!agents.length">
                    <td colspan="4" class="py-12 text-center text-muted-foreground">
                        No agents yet
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>