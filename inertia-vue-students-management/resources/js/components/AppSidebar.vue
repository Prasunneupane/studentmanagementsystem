<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
  BookOpen, Folder, LayoutGrid, FilePlusIcon, Settings, 
  UserPlus, Eye, Users, Book, BookUser, UserRoundCheck, 
  UserRoundPlus 
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();

// Get permissions from shared data
const permissions = computed(() => page.props.auth?.permissions || {});
console.log(permissions.value,"permissions");

// Filter navigation items based on permissions
const mainNavItems = computed((): NavItem[] => {
  const items: NavItem[] = [];

  // Dashboard - always visible
  items.push({
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  });

  // Student Management
  if (permissions.value.students.canManage) {
    const studentItems: NavItem[] = [];
    
    if (permissions.value.students.canCreate) {
      studentItems.push({
        title: 'Add Student',
        href: '/students/create',
        icon: UserPlus,
      });
    }
    
    if (permissions.value.students.canView) {
      studentItems.push({
        title: 'View Students',
        href: '/students',
        icon: Users,
      });
    }

    if (studentItems.length > 0) {
      items.push({
        title: 'Student Management',
        href: '/',
        icon: Users,
        items: studentItems,
      });
    }
  }

  // Subject Management
  if (permissions.value.subjects.canManage) {
    const subjectItems: NavItem[] = [];
    
    if (permissions.value.subjects.canCreate) {
      subjectItems.push({
        title: 'Add Subject',
        href: '/subjects/create',
        icon: FilePlusIcon,
      });
    }
    
    if (permissions.value.subjects.canView) {
      subjectItems.push({
        title: 'View Subjects',
        href: '/subjects',
        icon: BookOpen,
      });
    }

    if (subjectItems.length > 0) {
      items.push({
        title: 'Subject Management',
        href: '/',
        icon: Book,
        items: subjectItems,
      });
    }
  }

  // Teacher Management
  if (permissions.value.teachers.canManage) {
    const teacherItems: NavItem[] = [];
    
    if (permissions.value.teachers.canCreate) {
      teacherItems.push({
        title: 'Add Teacher',
        href: '/teachers/create',
        icon: UserRoundPlus,
      });
    }
    
    if (permissions.value.teachers.canView) {
      teacherItems.push({
        title: 'View Teachers',
        href: '/teachers',
        icon: Eye,
      });
    }

    if (teacherItems.length > 0) {
      items.push({
        title: 'Teacher Management',
        href: '/',
        icon: BookUser,
        items: teacherItems,
      });
    }
  }

  // Master Settings
  if (permissions.value.canManageMasterSettings) {
    const masterSettingsItems: NavItem[] = [];

    // Roles sub-section
    if (permissions.value.roles.canManage) {
      const roleItems: NavItem[] = [];
      
      if (permissions.value.roles.canCreate) {
        roleItems.push({
          title: 'Add Role',
          href: '/roles/create',
          icon: FilePlusIcon,
        });
      }
      
      if (permissions.value.roles.canView) {
        roleItems.push({
          title: 'View Role',
          href: '/roles',
          icon: Eye,
        });
      }

      if (roleItems.length > 0) {
        masterSettingsItems.push({
          title: 'Roles',
          icon: UserRoundCheck,
          items: roleItems,
        });
      }
    }

    // Permissions sub-section
    if (permissions.value.permissions.canManage) {
      const permissionItems: NavItem[] = [];
      
      if (permissions.value.permissions.canCreate) {
        permissionItems.push({
          title: 'Add Permission',
          href: '/permissions/create',
          icon: FilePlusIcon,
        });
      }
      
      if (permissions.value.permissions.canView) {
        permissionItems.push({
          title: 'View Permission',
          href: '/permissions',
          icon: Eye,
        });
      }

      if (permissionItems.length > 0) {
        masterSettingsItems.push({
          title: 'Permissions',
          icon: BookOpen,
          items: permissionItems,
        });
      }
    }

    // Users sub-section
    if (permissions.value.users.canManage) {
      const userItems: NavItem[] = [];
      
      if (permissions.value.users.canCreate) {
        userItems.push({
          title: 'Add User',
          href: '/users/create',
          icon: FilePlusIcon,
        });
      }
      
      if (permissions.value.users.canView) {
        userItems.push({
          title: 'View User',
          href: '/users',
          icon: Eye,
        });
      }

      if (userItems.length > 0) {
        masterSettingsItems.push({
          title: 'Users',
          icon: Users,
          items: userItems,
        });
      }
    }

    if (masterSettingsItems.length > 0) {
      items.push({
        title: 'Master Settings',
        icon: Settings,
        items: masterSettingsItems,
      });
    }
  }

  // Settings
  if (permissions.value.settings.canView) {
    items.push({
      title: 'Settings',
      href: '/settings',
      icon: Settings,
    });
  }

  return items;
});

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
      <NavMain :items="mainNavItems" :isRoot="true" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>