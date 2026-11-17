<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object, default: null },
})

const emit = defineEmits(['update:open'])

const form = useForm({ password: '' })

const close = () => emit('update:open', false)

const submit = () => {
    form.patch(route('accounts.update', props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            close()
            form.reset()
        },
    })
}

watch(() => props.open, (isOpen) => {
    if (!isOpen) return

    form.clearErrors()
    form.reset()
})
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md" data-testid="reset-password-dialog">
            <DialogHeader>
                <DialogTitle>Reset password</DialogTitle>
                <DialogDescription>
                    Set a new password for {{ user?.name }}. Share it with them securely.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" data-testid="reset-password-form" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="reset-password">New password</Label>
                    <Input
                        id="reset-password"
                        v-model="form.password"
                        type="password"
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                        data-testid="reset-password-input"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" data-testid="reset-password-cancel" @click="close">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing" data-testid="reset-password-submit">
                        {{ form.processing ? 'Saving...' : 'Save password' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>