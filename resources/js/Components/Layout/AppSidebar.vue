<script setup>
import { computed, inject } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Button } from '@/Components/ui/button'
import {
    SIDEBAR_KEY,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    sidebarMenuButtonVariants,
} from '@/Components/ui/sidebar'
import { cn } from '@/lib/utils'
import { ChartBar, LayoutDashboard, LogOut, UserRound, X } from '@lucide/vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const initial = computed(() => user.value?.name?.charAt(0).toUpperCase() ?? '')
const { isMobile, setOpenMobile } = inject(SIDEBAR_KEY)

const closeMobile = () => setOpenMobile(false)

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
    <SidebarHeader>
        <div class="flex w-full items-center justify-between gap-2 px-1">
            <Link
                :href="route('dashboard')"
                class="flex items-center gap-2 rounded-md px-1 py-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                @click="closeMobile"
            >
                <ApplicationLogo class="h-8 w-auto fill-current text-sidebar-primary" />
                <span class="text-sm font-semibold tracking-wide text-sidebar-foreground">KPI</span>
            </Link>

            <Button
                v-if="isMobile"
                type="button"
                variant="ghost"
                size="icon-sm"
                aria-label="Close navigation"
                data-testid="sidebar-close"
                @click="closeMobile"
            >
                <X aria-hidden="true" />
            </Button>
        </div>
    </SidebarHeader>

    <SidebarContent>
        <SidebarGroup>
            <SidebarGroupLabel>Menu</SidebarGroupLabel>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in menuItems" :key="item.label">
                    <SidebarMenuButton
                        :as="Link"
                        :href="item.href"
                        :is-active="!!item.active"
                        :data-testid="item.testid"
                        :aria-current="item.active ? 'page' : undefined"
                        @click="closeMobile"
                    >
                        <component :is="item.icon" aria-hidden="true" />
                        <span>{{ item.label }}</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </SidebarContent>

    <SidebarFooter>
        <template v-if="user">
            <div class="flex items-center gap-2 rounded-md bg-sidebar-accent/60 p-2">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sidebar-primary text-xs font-semibold text-sidebar-primary-foreground">
                    {{ initial }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-sidebar-foreground">{{ user.name }}</p>
                    <p class="truncate text-xs text-sidebar-foreground/60">{{ user.email }}</p>
                </div>
            </div>

            <SidebarMenuButton
                :as="Link"
                :href="route('profile.edit')"
                :is-active="!!route().current('profile.*')"
                data-testid="sidebar-link-profile"
                :aria-current="route().current('profile.*') ? 'page' : undefined"
                @click="closeMobile"
            >
                <UserRound aria-hidden="true" />
                <span>Profile</span>
            </SidebarMenuButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                data-testid="sidebar-logout"
                :class="cn(sidebarMenuButtonVariants(), 'text-sidebar-foreground/70')"
                @click="closeMobile"
            >
                <LogOut aria-hidden="true" />
                <span>Log out</span>
            </Link>
        </template>

        <Button v-else variant="outline" as-child class="w-full justify-start">
            <Link :href="route('login')" @click="closeMobile">Log in</Link>
        </Button>
    </SidebarFooter>
</template>
