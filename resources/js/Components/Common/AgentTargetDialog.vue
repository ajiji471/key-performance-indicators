<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    open:   { type: Boolean, default: false },
    target: { type: Number, default: 200 },
})

const emit = defineEmits(['update:open'])

const form = useForm({ target: props.target })

const close = () => emit('update:open', false)

const submit = () => {
    form.patch(route('dashboard.agent-target.update'), {
        preserveScroll: true,
        onSuccess: () => close(),
    })
}

watch(() => props.open, (isOpen) => {
    if (!isOpen) return

    form.clearErrors()
    form.target = props.target
})
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md" data-testid="agent-target-dialog">
            <DialogHeader>
                <DialogTitle>Edit agent target</DialogTitle>
                <DialogDescription>
                    Set the global agent target shown on the dashboard progress bar.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" data-testid="agent-target-form" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="agent-target">Target agents</Label>
                    <Input
                        id="agent-target"
                        v-model.number="form.target"
                        type="number"
                        min="1"
                        max="1000000"
                        step="1"
                        inputmode="numeric"
                        autocomplete="off"
                        data-testid="agent-target-input"
                    />
                    <InputError :message="form.errors.target" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" data-testid="agent-target-cancel" @click="close">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing" data-testid="agent-target-submit">
                        {{ form.processing ? 'Saving...' : 'Save' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>