<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid,FilePlusIcon, Settings, UserPlus, Eye, Users, Book,BookUser, UserRoundCheck, GraduationCap, UserRoundPlus   } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
// <FilePlusCorner />
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        
    },
    {
        title: 'Student Management',
        href: '/',
        icon: Users,
        items: [
            {
                title: 'Add Student',
                href: '/students/create',
                icon: UserPlus,
            },
            {
                title: 'View Students',
                href: '/students',
                icon: Users,
            },
        ],
    },
    {
        title: 'Subject Management',
        href: '/',
        icon: Book,
        items: [
            {
                title: 'Add Subject',
                href: '/subjects/create',
                icon: FilePlusIcon,
            },
            {
                title: 'View Subjects',
                href: '/subjects',
                icon: BookOpen,
            },
        ],
    },
    {
        title: 'Teacher Management',
        href: '/',
        icon: BookUser,
        items: [
            {
                title: 'Add Teacher',
                href: '/teachers/create',
                icon: UserRoundPlus,
            },
            {
                title: 'View Teachers',
                href: '/teachers',
                icon: Eye,
            },
        ],
    },
    {
        title: 'Master Settings',
        icon: Settings,
        items: [
                {
                title: 'Roles',
                icon: UserRoundCheck,
                items: [
                    {
                    title: 'Add Role',
                    href: '/roles/create',
                    icon: FilePlusIcon,
                    },
                    {
                    title: 'Assign Role',
                    href: '/settings/roles/assign',
                    icon: Users,
                    },
                ],
                },
                {
                title: 'Permissions',
                icon: BookOpen,
                items: [
                    {
                    title: 'Add Permission',
                    href: '/permissions',
                    icon: FilePlusIcon,
                    },
                    {
                    title: 'Assign Permission',
                    href: '/settings/permissions/assign',
                    icon: GraduationCap,
                    },
                ],
                },
            ],
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: Settings,
        
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
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
            <NavMain :items="mainNavItems"  :isRoot="true"/>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
