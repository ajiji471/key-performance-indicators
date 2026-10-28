<script setup>
import { Input } from '@/Components/ui/input'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    form:      { type: Object, required: true },
    platforms: { type: Array, required: true },
})
</script>

<template>
    <div class="grid gap-3">
        <div class="hidden gap-3 text-xs font-medium uppercase text-neutral-500 sm:grid sm:grid-cols-[7rem_1fr_1fr]">
            <span>Platform</span>
            <span>Identifier</span>
            <span>Password</span>
        </div>

        <div
            v-for="(platform, index) in platforms"
            :key="platform.id"
            class="grid gap-3 border-b pb-3 last:border-b-0 last:pb-0 sm:grid-cols-[7rem_1fr_1fr] sm:items-start sm:border-b-0 sm:pb-0"
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
                    placeholder="Opsional"
                    autocomplete="new-password"
                    :aria-label="`Password ${platform.name}`"
                    :data-testid="`agent-form-account-${platform.slug}-password`"
                />
                <InputError :message="form.errors[`accounts.${index}.password`]" />
            </div>
        </div>
    </div>
</template>