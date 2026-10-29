<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { ChartBar, LayoutDashboard, LogOut, UserRound, X } from '@lucide/vue'

defineProps({
    closable: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const page = usePage()
const user = computed(() => page.props.auth.user)
const initial = computed(() => user.value?.name?.charAt(0).toUpperCase() ?? '')

// objek data di atas
const menuItems = computed(() => {
    const items = [
        {
            label: 'Dashboard',
            href: route('dashboard'),
            active: route().current('dashboard'),
            icon: LayoutDashboard,
            testid: 'sidebar-link-dashboard',
        },
    ]

    if (user.value) {
        items.push({
            label: 'KPI',
            href: route('kpi'),
            active: route().current('kpi'),
            icon: ChartBar,
            testid: 'sidebar-link-kpi',
        })
    }

    return items
})
</script>

<template>
    <div class="flex h-full flex-col bg-white">
        <div class="flex h-16 items-center justify-between border-b border-neutral-200 px-4">
            <Link :href="route('dashboard')" class="flex items-center gap-2 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2">
                <ApplicationLogo class="h-8 w-auto fill-current text-neutral-900" />
                <span class="text-sm font-semibold tracking-wide text-neutral-900">KPI</span>
            </Link>

            <button
                v-if="closable"
                type="button"
                aria-label="Close navigation"
                data-testid="sidebar-close"
                class="inline-flex items-center justify-center rounded-md p-2 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                @click="emit('close')"
            >
                <X class="size-5" aria-hidden="true" />
            </button>
        </div>

        <nav aria-label="Main navigation" class="flex-1 overflow-y-auto p-3">
            <p class="px-3 pb-2 text-xs font-medium uppercase tracking-wide text-neutral-400">Menu</p>
            <ul class="space-y-1">
                <li v-for="item in menuItems" :key="item.label">
                    <Link
                        :href="item.href"
                        :aria-current="item.active ? 'page' : undefined"
                        :data-testid="item.testid"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                        :class="item.active
                            ? 'bg-neutral-900 text-white'
                            : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'"
                    >
                        <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                        {{ item.label }}
                    </Link>
                </li>
            </ul>
        </nav>

        <div v-if="user" class="border-t border-neutral-200 p-3">
            <div class="mb-3 flex items-center gap-3 rounded-md bg-neutral-50 px-3 py-2">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-xs font-semibold text-white">
                    {{ initial }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-neutral-900">{{ user.name }}</p>
                    <p class="truncate text-xs text-neutral-500">{{ user.email }}</p>
                </div>
            </div>

            <Link
                :href="route('profile.edit')"
                :aria-current="route().current('profile.*') ? 'page' : undefined"
                data-testid="sidebar-link-profile"
                class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
                :class="route().current('profile.*')
                    ? 'bg-neutral-900 text-white'
                    : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900'"
            >
                <UserRound class="size-4 shrink-0" aria-hidden="true" />
                Profile
            </Link>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                data-testid="sidebar-logout"
                class="mt-1 flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-medium text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2"
            >
                <LogOut class="size-4 shrink-0" aria-hidden="true" />
                Log out
            </Link>
        </div>
    </div>
</template>
