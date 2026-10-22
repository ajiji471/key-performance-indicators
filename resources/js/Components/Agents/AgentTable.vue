<script setup>
import { Eye, Pencil } from '@lucide/vue'
import CopyButton from '@/Components/Common/CopyButton.vue'

defineProps({
    agents:    { type: Array, required: true },
    platforms: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
})

const emit = defineEmits(['open'])
</script>

<template>
    <div class="overflow-x-auto rounded-md border">
        <table 
            data-testid="agent-table" 
            class="w-full whitespace-nowrap text-left [&_td]:px-3 [&_td]:py-2 [&_th]:px-3 [&_th]:py-2"
        >
            <thead class="bg-neutral-100 text-xs uppercase text-neutral-500">
                <tr>
                    <th>Nama</th>
                    <th>Device</th>
                    <th v-for="p in platforms" :key="p.id">{{ p.name }}</th>
                    <th><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr 
                    v-for="agent in agents" 
                    :key="agent.id" 
                    :data-testid="`agent-row-${agent.id}`" 
                    class="hover:bg-neutral-50"
                >
                    <td class="font-medium text-neutral-900">{{ agent.name }}</td>
                    <td>{{ agent.device_user }}</td>
                    <td v-for="p in platforms" :key="p.id">
                        <div v-if="agent.accounts[p.slug]" class="flex items-center gap-1">
                            <span class="max-w-40 truncate" :title="agent.accounts[p.slug]">
                                {{ agent.accounts[p.slug] }}
                            </span>
                            <CopyButton
                                :value="agent.accounts[p.slug]"
                                :label="`${p.name} ${agent.name}`"
                                :test-id="`${p.slug}-${agent.id}`"
                            />
                        </div>
                        <span v-else class="text-neutral-400">-</span>
                    </td>
                    <td>
                        <button
                            type="button"
                            :aria-label="`${canManage ? 'Edit' : 'Lihat'} profile ${agent.name}`"
                            :data-testid="`agent-open-${agent.id}`"
                            class="rounded p-1 hover:bg-neutral-200"
                            @click="emit('open', agent.id)"
                        >
                            <component :is="canManage ? Pencil : Eye" class="size-4" />
                        </button>
                    </td>
                </tr>
                <tr v-if="!agents.length">
                    <td :colspan="platforms.length + 3" class="py-8 text-center text-neutral-400">
                        Belum ada agent
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>