<script setup>
import { Input } from '@/Components/ui/input'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    form:      { type: Object, required: true },
    platforms: { type: Array, required: true },
})

// sama persis dengan App\Enums\AccountStatus
const STATUSES = [
    { value: 'registered', label: 'Registered' },
    { value: 'logged_in',  label: 'Logged in' },
    { value: 'recovered',  label: 'Recovered' },
    { value: 'trouble',    label: 'Trouble' },
]
</script>

<template>
    <div class="grid gap-3">
        <div class="hidden gap-3 text-xs font-medium uppercase text-neutral-500 sm:grid sm:grid-cols-[6rem_1fr_1fr_8.5rem]">
            <span>Platform</span>
            <span>Identifier</span>
            <span>Password</span>
            <span>Status</span>
        </div>

        <div
            v-for="(platform, index) in platforms"
            :key="platform.id"
            class="grid gap-3 border-b pb-3 last:border-b-0 last:pb-0 sm:grid-cols-[6rem_1fr_1fr_8.5rem] sm:items-start sm:border-b-0 sm:pb-0"
        >
            <div class="pt-1 text-sm font-medium text-neutral-900">{{ platform.name }}</div>

            <div class="grid gap-1">
                <Input
                    v-model="form.accounts[index].identifier"
                    type="text"
                    :placeholder="`Email / ID ${platform.name}`"
                    autocomplete="off"
                    :aria-label="`Identifier ${platform.name}`"
                    :data-testid="`agent-form-account-${platform.slug}-identifier`"
                />
                <InputError :message="form.errors[`accounts.${index}.identifier`]" />
                <InputError :message="form.errors[`accounts.${index}.platform_id`]" />
            </div>

            <div class="grid gap-1">
                <Input
                    v-model="form.accounts[index].password"
                    type="password"
                    placeholder="Optional"
                    autocomplete="new-password"
                    :aria-label="`Password ${platform.name}`"
                    :data-testid="`agent-form-account-${platform.slug}-password`"
                />
                <InputError :message="form.errors[`accounts.${index}.password`]" />
            </div>

            <div class="grid gap-1">
                <select
                    v-model="form.accounts[index].status"
                    :disabled="!form.accounts[index].identifier"
                    :aria-label="`Status ${platform.name}`"
                    :data-testid="`agent-form-account-status-${platform.id}`"
                    class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full min-w-0 rounded-md border bg-transparent px-2 py-1 text-sm shadow-xs outline-none focus-visible:ring-3 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option v-for="s in STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <InputError :message="form.errors[`accounts.${index}.status`]" />
            </div>
        </div>
    </div>
</template>