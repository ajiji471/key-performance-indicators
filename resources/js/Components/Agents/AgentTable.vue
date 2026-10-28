<script setup>
import { CheckCircle, Clock, XCircle, Eye, Pencil, MoreVertical } from '@lucide/vue'
import CopyButton from '@/Components/Common/CopyButton.vue'

// Objek data di atas
const statusConfig = {
    logged_in:  { label: 'Logged in',  color: 'bg-green-50 text-green-700 border-green-200', icon: CheckCircle },
    registered: { label: 'Registered', color: 'bg-yellow-50 text-yellow-700 border-yellow-200', icon: Clock },
    trouble:    { label: 'Trouble',    color: 'bg-red-50 text-red-700 border-red-200', icon: XCircle },
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

// Helper: hitung persentase logged_in 
const getStatusPercentage = (agent) => {
    // Gunakan TOTAL semua platform dari props, bukan agent.platforms
    const allPlatforms = props.platforms
    if (!allPlatforms || allPlatforms.length === 0) return 0

    // Hitung berapa banyak platform yg statusnya 'logged_in' untuk agent ini
    const loggedInCount = allPlatforms.filter((p) => {
        const agentPlatform = getAgentPlatform(agent, p.slug)
        return agentPlatform?.status === 'logged_in'
    }).length

    return Math.round((loggedInCount / allPlatforms.length) * 100)
}

// Helper: warna persentase
const getPercentageColor = (percentage) => {
    if (percentage === 100) return 'text-green-600'
    if (percentage >= 50) return 'text-yellow-600'
    if (percentage > 0) return 'text-orange-600'
    return 'text-red-600'
}
</script>

<template>
    <div class="overflow-x-auto rounded-lg border bg-white">
        <table class="w-full whitespace-nowrap text-left">
            <thead class="border-b bg-neutral-50 text-xs uppercase text-neutral-500">
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
                    class="hover:bg-neutral-50"
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
                            <div v-else class="size-10 shrink-0 rounded-full bg-neutral-200 flex items-center justify-center text-neutral-600 font-bold">
                                {{ agent.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="font-medium text-neutral-900">{{ agent.name }}</div>
                                <div class="text-xs text-neutral-500">Device : {{ agent.device_user || '-' }}</div>
                                <div class="text-xs text-neutral-400">created at: {{ agent.created_at ? new Date(agent.created_at).toLocaleDateString('id-ID') : '-' }}</div>
                            </div>
                        </div>
                    </td>

                    <!-- Complete Rate Column -->
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full" :class="getPercentageColor(getStatusPercentage(agent)).replace('text-', 'bg-')" />
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
                                :class="getAgentPlatform(agent, p.slug)?.status 
                                    ? statusConfig[getAgentPlatform(agent, p.slug).status]?.color 
                                    : 'bg-neutral-50 text-neutral-500 border-neutral-200'"
                            >
                                <span class="font-medium">{{ p.name }}</span>
                                
                                <component 
                                    v-if="getAgentPlatform(agent, p.slug)?.status"
                                    :is="statusConfig[getAgentPlatform(agent, p.slug).status]?.icon" 
                                    class="size-3.5 shrink-0" 
                                />
                                
                                <span 
                                    v-if="getAgentPlatform(agent, p.slug)?.identifier" 
                                    class="text-neutral-300 px-0.5"
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
                            class="rounded p-1.5 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900"
                            @click="emit('open', agent.id)"
                        >
                            <MoreVertical class="size-4" />
                        </button>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="!agents.length">
                    <td colspan="4" class="py-12 text-center text-neutral-400">
                        Belum ada agent
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>