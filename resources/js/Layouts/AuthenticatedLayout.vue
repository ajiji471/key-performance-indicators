<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import AppSidebar from '@/Components/Layout/AppSidebar.vue'
import { Button } from '@/Components/ui/button'
import { Sidebar, SidebarInset, SidebarProvider, SidebarTrigger } from '@/Components/ui/sidebar'

const page = usePage()
const user = computed(() => page.props.auth.user)
const initial = computed(() => user.value?.name?.charAt(0).toUpperCase() ?? '')
</script>

<template>
    <SidebarProvider>
        <Sidebar>
            <AppSidebar />
        </Sidebar>

        <SidebarInset>
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-border bg-background/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <SidebarTrigger class="lg:hidden" />

                <div class="min-w-0 flex-1">
                    <slot name="header" />
                </div>

                <Dropdown v-if="user" align="right" width="48">
                    <template #trigger>
                        <Button variant="ghost" class="h-auto gap-2 px-2 py-1.5">
                            <span class="flex size-8 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                {{ initial }}
                            </span>
                            <span class="hidden max-w-40 truncate sm:inline">{{ user.name }}</span>
                            <svg
                                class="size-4 text-muted-foreground"
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
                        </Button>
                    </template>

                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                    </template>
                </Dropdown>

                <Button v-else variant="outline" as-child>
                    <Link :href="route('login')">Log in</Link>
                </Button>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </SidebarInset>
    </SidebarProvider>
</template>
