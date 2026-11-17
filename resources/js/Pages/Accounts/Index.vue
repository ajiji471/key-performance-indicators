<script setup>
import { computed, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Button } from '@/Components/ui/button'
import UserFormDialog from '@/Components/Accounts/UserFormDialog.vue'
import ResetPasswordDialog from '@/Components/Accounts/ResetPasswordDialog.vue'

const props = defineProps({
    users: { type: Array, required: true },
})

const page = usePage()
const flash = computed(() => page.props.flash ?? {})
const currentId = computed(() => page.props.auth.user?.id)

const createOpen = ref(false)
const resetTarget = ref(null)

const roleLabels = { admin: 'Admin', user: 'User', auditor: 'Auditor' }

const selectClass = 'border-input focus-visible:border-ring focus-visible:ring-ring/50 h-9 rounded-md border bg-transparent px-2 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:ring-3 dark:bg-input/30 dark:[color-scheme:dark]'

const changeRole = (user, event) => {
    const select = event.target

    router.patch(route('accounts.update', user.id), { role: select.value }, {
        preserveScroll: true,
        onSuccess: (visitPage) => {
            const fresh = visitPage.props.users.find((u) => u.id === user.id)
            if (fresh) select.value = fresh.role
        },
    })
}

const toggleActive = (user) => {
    router.patch(route('accounts.update', user.id), { is_active: !user.is_active }, {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Accounts" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="truncate text-base font-semibold text-foreground">Accounts</h1>
        </template>

        <div class="flex flex-col gap-6">
            <p
                v-if="flash.success"
                role="status"
                data-testid="accounts-flash-success"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300"
            >
                {{ flash.success }}
            </p>
            <p
                v-if="flash.error"
                role="alert"
                data-testid="accounts-flash-error"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
            >
                {{ flash.error }}
            </p>

            <section aria-label="Page notes" class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted-foreground">
                    Login accounts for this app. Admins can create accounts, change roles, deactivate access, and reset passwords.
                </p>
                <Button data-testid="accounts-add-user" @click="createOpen = true">Add user</Button>
            </section>

            <div class="flex flex-col gap-3 md:hidden">
                <article
                    v-for="user in users"
                    :key="user.id"
                    :data-testid="`account-card-${user.id}`"
                    class="rounded-lg border border-border bg-card p-4"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-foreground">
                                {{ user.name }}
                                <span v-if="user.id === currentId" class="text-xs font-normal text-muted-foreground">(you)</span>
                            </p>
                            <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                        </div>
                        <span
                            class="shrink-0 text-xs font-medium"
                            :class="user.is_active ? 'text-emerald-400' : 'text-muted-foreground'"
                        >
                            {{ user.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <select
                            :value="user.role"
                            :aria-label="`Role for ${user.name}`"
                            :data-testid="`account-role-${user.id}`"
                            :class="selectClass"
                            @change="changeRole(user, $event)"
                        >
                            <option v-for="(label, value) in roleLabels" :key="value" :value="value" class="bg-card text-foreground">
                                {{ label }}
                            </option>
                        </select>
                        <Button
                            variant="outline"
                            size="sm"
                            :data-testid="`account-toggle-${user.id}`"
                            @click="toggleActive(user)"
                        >
                            {{ user.is_active ? 'Deactivate' : 'Activate' }}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :data-testid="`account-reset-${user.id}`"
                            @click="resetTarget = user"
                        >
                            Reset password
                        </Button>
                    </div>
                </article>
            </div>

            <div class="hidden overflow-x-auto rounded-lg border border-border bg-card md:block" data-testid="accounts-table">
                <table class="w-full text-left">
                    <caption class="sr-only">User login accounts</caption>
                    <thead class="border-b border-border bg-muted/50 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">Name</th>
                            <th scope="col" class="px-4 py-3 font-medium">Email</th>
                            <th scope="col" class="px-4 py-3 font-medium">Role</th>
                            <th scope="col" class="px-4 py-3 font-medium">Status</th>
                            <th scope="col" class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            :data-testid="`account-row-${user.id}`"
                            class="hover:bg-muted/50"
                        >
                            <td class="px-4 py-3 text-sm font-medium text-foreground">
                                {{ user.name }}
                                <span v-if="user.id === currentId" class="text-xs font-normal text-muted-foreground">(you)</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <select
                                    :value="user.role"
                                    :aria-label="`Role for ${user.name}`"
                                    :data-testid="`account-role-${user.id}`"
                                    :class="selectClass"
                                    @change="changeRole(user, $event)"
                                >
                                    <option v-for="(label, value) in roleLabels" :key="value" :value="value" class="bg-card text-foreground">
                                        {{ label }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-xs font-medium"
                                    :class="user.is_active ? 'text-emerald-400' : 'text-muted-foreground'"
                                >
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :data-testid="`account-toggle-${user.id}`"
                                        @click="toggleActive(user)"
                                    >
                                        {{ user.is_active ? 'Deactivate' : 'Activate' }}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :data-testid="`account-reset-${user.id}`"
                                        @click="resetTarget = user"
                                    >
                                        Reset password
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <UserFormDialog v-model:open="createOpen" />
        <ResetPasswordDialog
            :open="resetTarget !== null"
            :user="resetTarget"
            @update:open="(value) => { if (!value) resetTarget = null }"
        />
    </AuthenticatedLayout>
</template>