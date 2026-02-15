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

// Get current URL to determine which nav items should be open
const currentUrl = computed(() => page.url);

// Helper function to check if a route is active or if any of its children are active
const isRouteActive = (item: NavItem): boolean => {
  if (item.href && currentUrl.value.startsWith(item.href)) {
    return true;
  }
  if (item.items) {
    return item.items.some(child => isRouteActive(child));
  }
  return false;
};

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
      const studentManagement: NavItem = {
        title: 'Student Management',
        href: '/',
        icon: Users,
        items: studentItems,
        isActive: false,
      };
      studentManagement.isActive = isRouteActive(studentManagement);
      items.push(studentManagement);
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
      const subjectManagement: NavItem = {
        title: 'Subject Management',
        href: '/',
        icon: Book,
        items: subjectItems,
        isActive: false,
      };
      subjectManagement.isActive = isRouteActive(subjectManagement);
      items.push(subjectManagement);
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
      const teacherManagement: NavItem = {
        title: 'Teacher Management',
        href: '/',
        icon: BookUser,
        items: teacherItems,
        isActive: false,
      };
      teacherManagement.isActive = isRouteActive(teacherManagement);
      items.push(teacherManagement);
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
        const rolesItem: NavItem = {
          title: 'Roles',
          icon: UserRoundCheck,
          items: roleItems,
          isActive: false,
        };
        rolesItem.isActive = isRouteActive(rolesItem);
        masterSettingsItems.push(rolesItem);
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
        const permissionsItem: NavItem = {
          title: 'Permissions',
          icon: BookOpen,
          items: permissionItems,
          isActive: false,
        };
        permissionsItem.isActive = isRouteActive(permissionsItem);
        masterSettingsItems.push(permissionsItem);
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
        const usersItem: NavItem = {
          title: 'Users',
          icon: Users,
          items: userItems,
          isActive: false,
        };
        usersItem.isActive = isRouteActive(usersItem);
        masterSettingsItems.push(usersItem);
      }
    }
    // ClassSubjectMap sub-section start
    if (permissions.value.classSubjects.canManage) {
      const userItems: NavItem[] = [];
      
      if (permissions.value.classSubjects.canCreate) {
        userItems.push({
          title: 'Add Class Subject Mapping',
          href: '/class-subjects/create',
          icon: FilePlusIcon,
        });
      }
      
      if (permissions.value.classSubjects.canView) {
        userItems.push({
          title: 'View Class Subject Mapping',
          href: '/class-subjects',
          icon: Eye,
        });
      }

      if (userItems.length > 0) {
        const usersItem: NavItem = {
          title: 'ClassSubjectMap',
          icon: Users,
          items: userItems,
          isActive: false,
        };
        usersItem.isActive = isRouteActive(usersItem);
        masterSettingsItems.push(usersItem);
      }
    }
    // ClassSubjectMap sub-section end

    // ClassTeacherMap sub-section start
    if (permissions.value.classTeachers.canManage) {
      const userItems: NavItem[] = [];
      
      if (permissions.value.classTeachers.canCreate) {
        userItems.push({
          title: 'Add Class Teacher Mapping',
          href: '/class-teacher/create',
          icon: FilePlusIcon,
        });
      }
      
      if (permissions.value.classTeachers.canView) {
        userItems.push({
          title: 'View Class Subject Mapping',
          href: '/class-teacher',
          icon: Eye,
        });
      }

      if (userItems.length > 0) {
        const usersItem: NavItem = {
          title: 'ClassTeacherMap',
          icon: Users,
          items: userItems,
          isActive: false,
        };
        usersItem.isActive = isRouteActive(usersItem);
        masterSettingsItems.push(usersItem);
      }
    }
    // ClassTeacherMap sub-section end

    if (masterSettingsItems.length > 0) {
      const masterSettings: NavItem = {
        title: 'Master Settings',
        icon: Settings,
        items: masterSettingsItems,
        isActive: false,
      };
      masterSettings.isActive = isRouteActive(masterSettings);
      items.push(masterSettings);
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