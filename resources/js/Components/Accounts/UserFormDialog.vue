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
})

const emit = defineEmits(['update:open'])

const form = useForm({ name: '', email: '', password: '', role: 'user' })

const close = () => emit('update:open', false)

const submit = () => {
    form.post(route('accounts.store'), {
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
        <DialogContent class="sm:max-w-md" data-testid="user-form-dialog">
            <DialogHeader>
                <DialogTitle>Add user</DialogTitle>
                <DialogDescription>
                    Create a login account. The account is active immediately.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" data-testid="user-form" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="user-name">Name</Label>
                    <Input id="user-name" v-model="form.name" type="text" autocomplete="off" data-testid="user-form-name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-email">Email</Label>
                    <Input id="user-email" v-model="form.email" type="email" autocomplete="off" data-testid="user-form-email" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-password">Password</Label>
                    <Input
                        id="user-password"
                        v-model="form.password"
                        type="password"
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                        data-testid="user-form-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-role">Role</Label>
                    <select
                        id="user-role"
                        v-model="form.role"
                        data-testid="user-form-role"
                        class="border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full min-w-0 rounded-md border bg-transparent px-2 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:ring-3 dark:bg-input/30 dark:[color-scheme:dark]"
                    >
                        <option value="user" class="bg-card text-foreground">User</option>
                        <option value="auditor" class="bg-card text-foreground">Auditor</option>
                        <option value="admin" class="bg-card text-foreground">Admin</option>
                    </select>
                    <InputError :message="form.errors.role" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" data-testid="user-form-cancel" @click="close">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing" data-testid="user-form-submit">
                        {{ form.processing ? 'Creating...' : 'Create user' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>