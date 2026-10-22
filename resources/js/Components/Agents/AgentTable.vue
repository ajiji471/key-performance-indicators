<script setup>
import { Eye, Pencil } from '@lucide/vue'
import CopyButton from '@/Components/Common/CopyButton.vue'

// objek data di atas
defineProps({
    agents:    { type: Array, required: true }, // agents.data
    platforms: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
})
const emit = defineEmits(['open'])
</script>

<template>
    <table data-testid="agent-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Device</th>
                <th v-for="p in platforms" :key="p.id">{{ p.name }}</th>
                <th><span>Aksi</span></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="agent in agents" :key="agent.id" :data-testid="`agent-row-${agent.id}`">
                <td>{{ agent.name }}</td>
                <td>{{ agent.device_user }}</td>
                <td v-for="p in platforms" :key="p.id">
                    <template v-if="agent.accounts[p.slug]">
                        {{ agent.accounts[p.slug] }}
                        <CopyButton
                            :value="agent.accounts[p.slug]"
                            :label="`${p.name} ${agent.name}`"
                            :test-id="`${p.slug}-${agent.id}`"
                        />
                    </template>
                    <template v-else>-</template>
                </td>
                <td>
                    <button
                        type="button"
                        :aria-label="`${canManage ? 'Edit' : 'Lihat'} profile ${agent.name}`"
                        :data-testid="`agent-open-${agent.id}`"
                        @click="emit('open', agent.id)"
                    >
                        <component :is="canManage ? Pencil : Eye" />
                    </button>
                </td>
            </tr>
            <tr v-if="!agents.length">
                <td :colspan="platforms.length + 3">Belum ada agent</td>
            </tr>
        </tbody>
    </table>
</template>
