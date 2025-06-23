<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import AppLogo from '@/layouts/partials/AppLogo.vue';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Box, Folder, LayoutGrid, Palette, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.is_admin);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Maps',
            href: '/manage-maps',
            icon: Folder,
        },
        {
            title: 'Tilesets',
            href: '/manage-tilesets',
            icon: Folder,
        },
        {
            title: 'Field Types',
            href: '/manage-field-types',
            icon: Palette,
        },
        {
            title: 'Object Types',
            href: '/manage-object-types',
            icon: Box,
        },
    ];

    // Add Users management for admins
    if (isAdmin.value) {
        items.push({
            title: 'Users',
            href: '/manage-users',
            icon: Users,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/macavity/mapeditor',
        icon: Folder,
    },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
