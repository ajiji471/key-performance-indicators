<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import InputError from '@/Components/InputError.vue'
import AgentFormSection from '@/Components/Agents/AgentFormSection.vue'
import AgentPlatformAccounts from '@/Components/Agents/AgentPlatformAccounts.vue'

const props = defineProps({
    open:      { type: Boolean, default: false },
    platforms: { type: Array, required: true },
})

const emit = defineEmits(['update:open'])

// objek data di atas
const form = useForm({
    name:               '',
    avatar:             null,
    email:              '',
    recovery_email:     '',
    password:           '',
    birth_date:         '',
    address:            '',
    latitude:           '',
    longitude:          '',
    job:                '',
    hobby:              '',
    context:            '',
    device_user:        '',
    device_number:      '',
    device_released_at: '',
    accounts: props.platforms.map((platform) => ({
        platform_id: platform.id,
        identifier:  '',
        password:    '',
    })),
})

const avatarPreview = ref(null)

const resetPreview = () => {
    if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
    avatarPreview.value = null
}

const setAvatar = (event) => {
    const file = event.target.files?.[0] ?? null

    form.avatar = file
    resetPreview()
    if (file) avatarPreview.value = URL.createObjectURL(file)
}

const close = () => emit('update:open', false)

const submit = () => {
    form.post(route('agents.store'), {
        forceFormData: true, // wajib: ada file avatar
        preserveScroll: true,
        onSuccess: () => {
            close()
            form.reset()
            resetPreview()
        },
    })
}

// buka ulang: error dari percobaan sebelumnya sudah tidak relevan
watch(() => props.open, (isOpen) => {
    if (isOpen) form.clearErrors()
})

onBeforeUnmount(resetPreview)
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Tambah agent</DialogTitle>
                <DialogDescription>
                    Nama, email, dan password wajib diisi. Akun platform boleh diisi sebagian.
                </DialogDescription>
            </DialogHeader>

            <form data-testid="agent-form" class="grid gap-6" @submit.prevent="submit">
                <AgentFormSection title="Identitas" description="Data utama agent.">
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
                        <Label for="agent-recovery-email">Email recovery</Label>
                        <Input
                            id="agent-recovery-email"
                            v-model="form.recovery_email"
                            type="email"
                            placeholder="Opsional"
                            autocomplete="off"
                            data-testid="agent-form-recovery-email"
                        />
                        <InputError :message="form.errors.recovery_email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="agent-birth-date">Birth date</Label>
                        <Input
                            id="agent-birth-date"
                            v-model="form.birth_date"
                            type="date"
                            data-testid="agent-form-birth-date"
                        />
                        <InputError :message="form.errors.birth_date" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="agent-avatar">Avatar</Label>
                        <input
                            id="agent-avatar"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            data-testid="agent-form-avatar"
                            class="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-neutral-200"
                            @input="setAvatar"
                        />
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            alt="Preview avatar"
                            class="size-16 rounded-full object-cover"
                        />
                        <InputError :message="form.errors.avatar" />
                    </div>
                </AgentFormSection>

                <AgentFormSection title="Alamat" description="Alamat dan koordinat lokasi agent.">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="agent-address">Address</Label>
                        <textarea
                            id="agent-address"
                            v-model="form.address"
                            rows="2"
                            placeholder="Opsional"
                            data-testid="agent-form-address"
                            class="border-input focus-visible:border-ring focus-visible:ring-ring/50 min-h-16 w-full rounded-md border bg-transparent px-2.5 py-2 text-sm shadow-xs outline-none focus-visible:ring-3"
                        />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="agent-latitude">Latitude</Label>
                        <Input
                            id="agent-latitude"
                            v-model="form.latitude"
                            type="text"
                            inputmode="decimal"
                            placeholder="-6.2000000"
                            autocomplete="off"
                            data-testid="agent-form-latitude"
                        />
                        <InputError :message="form.errors.latitude" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="agent-longitude">Longitude</Label>
                        <Input
                            id="agent-longitude"
                            v-model="form.longitude"
                            type="text"
                            inputmode="decimal"
                            placeholder="106.8166667"
                            autocomplete="off"
                            data-testid="agent-form-longitude"
                        />
                        <InputError :message="form.errors.longitude" />
                    </div>
                </AgentFormSection>

                <AgentFormSection title="Pekerjaan" description="Latar belakang agent.">
                    <div class="grid gap-2">
                        <Label for="agent-job">Job</Label>
                        <Input
                            id="agent-job"
                            v-model="form.job"
                            type="text"
                            placeholder="Opsional"
                            autocomplete="off"
                            data-testid="agent-form-job"
                        />
                        <InputError :message="form.errors.job" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="agent-hobby">Hobby</Label>
                        <Input
                            id="agent-hobby"
                            v-model="form.hobby"
                            type="text"
                            placeholder="Opsional"
                            autocomplete="off"
                            data-testid="agent-form-hobby"
                        />
                        <InputError :message="form.errors.hobby" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="agent-context">Context</Label>
                        <textarea
                            id="agent-context"
                            v-model="form.context"
                            rows="3"
                            placeholder="Catatan / deskripsi agent"
                            data-testid="agent-form-context"
                            class="border-input focus-visible:border-ring focus-visible:ring-ring/50 min-h-20 w-full rounded-md border bg-transparent px-2.5 py-2 text-sm shadow-xs outline-none focus-visible:ring-3"
                        />
                        <InputError :message="form.errors.context" />
                    </div>
                </AgentFormSection>

                <AgentFormSection title="Device" description="Perangkat yang dipakai agent.">
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

                    <div class="grid gap-2">
                        <Label for="agent-device-released-at">Device released at</Label>
                        <Input
                            id="agent-device-released-at"
                            v-model="form.device_released_at"
                            type="date"
                            data-testid="agent-form-device-released-at"
                        />
                        <InputError :message="form.errors.device_released_at" />
                    </div>
                </AgentFormSection>

                <AgentFormSection title="Akun platform" description="Kosongkan identifier kalau platform belum dipakai.">
                    <div class="grid gap-2 sm:col-span-2">
                        <AgentPlatformAccounts :form="form" :platforms="platforms" />
                        <InputError :message="form.errors.accounts" />
                    </div>
                </AgentFormSection>

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