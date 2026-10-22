<script setup>
import { computed } from 'vue'
import { Eye, Pencil } from '@lucide/vue'
import CopyButton from '@/Components/Common/CopyButton.vue'

const props = defineProps({
    agents:    { type: Array, required: true },
    canManage: { type: Boolean, default: false },
})

const emit = defineEmits(['open'])

// Objek data di atas (jgn dicampur style)
const statusConfig = {
    logged_in:  { label: 'Logged in',  color: 'bg-green-100 text-green-700 border-green-200' },
    registered: { label: 'Registered', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    trouble:    { label: 'Trouble',    color: 'bg-red-100 text-red-700 border-red-200' },
}

// Hitung persentase logged_in per agent
const getStatusPercentage = (agent) => {
    if (!agent.platforms || agent.platforms.length === 0) return 0
    const totalPlatforms = agent.platforms.length
    const loggedInCount = agent.platforms.filter(p => p.status === 'logged_in').length
    return Math.round((loggedInCount / totalPlatforms) * 100)
}

// Warna persentase berdasarkan nilai
const getPercentageColor = (percentage) => {
    if (percentage === 100) return 'text-green-600'
    if (percentage >= 50) return 'text-yellow-600'
    if (percentage > 0) return 'text-orange-600'
    return 'text-red-600'
}
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div
            v-for="agent in agents"
            :key="agent.id"
            :data-testid="`agent-card-${agent.id}`"
            class="flex flex-col rounded-lg border bg-white"
        >
            <!-- Header Card: Name | Edit Button -->
            <div class="flex items-center justify-between border-b bg-neutral-50 px-3 py-2">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-neutral-900" :title="agent.name">
                        {{ agent.name }}
                    </div>
                </div>
                <button
                    type="button"
                    :aria-label="`${canManage ? 'Edit' : 'Lihat'} profile ${agent.name}`"
                    :data-testid="`agent-open-${agent.id}`"
                    class="shrink-0 rounded p-1.5 text-neutral-500 hover:bg-neutral-200 hover:text-neutral-900"
                    @click="emit('open', agent.id)"
                >
                    <component :is="canManage ? Pencil : Eye" class="size-4" />
                </button>
            </div>

            <!-- Body Card -->
            <div class="flex flex-1 flex-col gap-3 p-3">
                <!-- Blok Device -->
                <div class="grid grid-cols-3 gap-2 rounded-md border bg-neutral-50 px-2 py-1.5 text-sm">
                    <div>
                        <div class="text-xs text-neutral-500">Name</div>
                        <div class="truncate font-medium text-neutral-900" :title="agent.name">{{ agent.name }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-neutral-500">Device</div>
                        <div class="truncate font-medium text-neutral-900" :title="agent.device_user">{{ agent.device_user || '-' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-neutral-500">Status</div>
                        <div 
                            class="font-semibold"
                            :class="getPercentageColor(getStatusPercentage(agent))"
                        >
                            {{ getStatusPercentage(agent) }}%
                        </div>
                    </div>
                </div>

                <!-- Blok Platform (TANPA CHART, cuma badge status tunggal) -->
                <div class="grid gap-2">
                    <div
                        v-for="p in agent.platforms"
                        :key="p.slug"
                        class="flex items-center justify-between rounded-md border px-2 py-1.5"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-semibold text-neutral-700">{{ p.name }}</div>
                            <div v-if="p.identifier" class="flex items-center gap-1">
                                <span class="truncate text-sm text-neutral-900" :title="p.identifier">
                                    {{ p.identifier }}
                                </span>
                                <CopyButton
                                    :value="p.identifier"
                                    :label="`${p.name} ${agent.name}`"
                                    :test-id="`${p.slug}-${agent.id}`"
                                />
                            </div>
                            <div v-else class="text-sm text-neutral-400">-</div>
                        </div>
                        
                        <!-- Badge Status Tunggal -->
                        <span 
                            v-if="p.status"
                            :class="statusConfig[p.status]?.color || 'bg-neutral-100 text-neutral-700 border-neutral-200'"
                            class="shrink-0 rounded-full border px-2 py-0.5 text-xs font-medium"
                        >
                            {{ statusConfig[p.status]?.label || p.status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!agents.length" class="col-span-full py-12 text-center text-neutral-400">
            Belum ada agent
        </div>
    </div>
</template>