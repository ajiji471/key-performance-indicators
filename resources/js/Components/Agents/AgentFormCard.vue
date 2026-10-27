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

const form = useForm({
    name:          '',
    email:         '',
    password:      '',
    device_user:   '',
    device_number: '',
})

const close = () => emit('update:open', false)

const submit = () => {
    form.post(route('agents.store'), {
        preserveScroll: true,
        onSuccess: () => {
            close()
            form.reset()
        },
    })
}

// buka ulang: error dari percobaan sebelumnya sudah tidak relevan
watch(() => props.open, (isOpen) => {
    if (isOpen) form.clearErrors()
})
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Tambah agent</DialogTitle>
                <DialogDescription>
                    Nama, email, dan password wajib diisi.
                </DialogDescription>
            </DialogHeader>

            <form data-testid="agent-form" class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="agent-name">Nama</Label>
                    <Input
                        id="agent-name"
                        v-model="form.name"
                        type="text"
                        placeholder="Nama agent"
                        autocomplete="off"
                        data-testid="agent-form-name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="agent-email">Email</Label>
                    <Input
                        id="agent-email"
                        v-model="form.email"
                        type="email"
                        placeholder="agent@example.com"
                        autocomplete="off"
                        data-testid="agent-form-email"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="agent-password">Password</Label>
                    <Input
                        id="agent-password"
                        v-model="form.password"
                        type="password"
                        placeholder="Minimal 6 karakter"
                        autocomplete="new-password"
                        data-testid="agent-form-password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="agent-device-user">Device user</Label>
                        <Input
                            id="agent-device-user"
                            v-model="form.device_user"
                            type="text"
                            placeholder="Opsional"
                            autocomplete="off"
                            data-testid="agent-form-device-user"
                        />
                        <InputError :message="form.errors.device_user" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="agent-device-number">Device number</Label>
                        <Input
                            id="agent-device-number"
                            v-model="form.device_number"
                            type="text"
                            placeholder="Opsional"
                            autocomplete="off"
                            data-testid="agent-form-device-number"
                        />
                        <InputError :message="form.errors.device_number" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" data-testid="agent-form-cancel" @click="close">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing" data-testid="agent-form-submit">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>