<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import SidebarNav from '@/Components/Layout/SidebarNav.vue'
import { Menu } from '@lucide/vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const initial = computed(() => user.value?.name?.charAt(0).toUpperCase() ?? '')

const sidebarOpen = ref(false)
const toggleRef = ref(null)
const previousOverflow = ref('')

const onKeydown = (event) => {
    if (event.key === 'Escape') sidebarOpen.value = false
}

const closeSidebar = () => {
    sidebarOpen.value = false
}

watch(sidebarOpen, async (open) => {
    if (open) {
        previousOverflow.value = document.body.style.overflow
        document.body.style.overflow = 'hidden'
        document.addEventListener('keydown', onKeydown)
        await nextTick()
        document.querySelector('[data-testid="sidebar-close"]')?.focus()
        return
    }

    document.body.style.overflow = previousOverflow.value
    document.removeEventListener('keydown', onKeydown)
    toggleRef.value?.focus()
})

watch(() => page.url, closeSidebar)

onBeforeUnmount(() => {
    document.body.style.overflow = previousOverflow.value
    document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar desktop -->
        <aside class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-neutral-200 lg:bg-white">
            <SidebarNav />
        </aside>

        <!-- Drawer mobile -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 lg:hidden"
            role="dialog"
            aria-modal="true"
            aria-label="Main navigation"
        >
            <div class="absolute inset-0 bg-neutral-900/50" @click="closeSidebar"></div>
            <div id="app-sidebar-mobile" class="relative flex h-full w-72 max-w-[80%] flex-col bg-white shadow-xl">
                <SidebarNav closable @close="closeSidebar" />
            </div>
        </div>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-30 border-b border-neutral-200 bg-white">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button
                        ref="toggleRef"
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 lg:hidden"
                        aria-label="Open navigation"
                        aria-controls="app-sidebar-mobile"
                        :aria-expanded="sidebarOpen"
                        data-testid="sidebar-toggle"
                        @click="sidebarOpen = true"
                    >
                        <Menu class="size-5" aria-hidden="true" />
                    </button>

                    <div class="min-w-0 flex-1">
                        <slot name="header" />
                    </div>

                    <div v-if="user">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                                >
                                    <span class="flex size-8 items-center justify-center rounded-full bg-neutral-900 text-xs font-semibold text-white">
                                        {{ initial }}
                                    </span>
                                    <span class="hidden max-w-40 truncate sm:inline">{{ user.name }}</span>
                                    <svg
                                        class="size-4 text-neutral-400"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Profile
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <Link
                        v-else
                        :href="route('login')"
                        class="rounded-md px-3 py-2 text-sm font-medium text-neutral-600 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900"
                    >
                        Log in
                    </Link>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
