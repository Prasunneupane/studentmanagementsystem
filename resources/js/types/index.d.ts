import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    permissions: Permissions;
}

export interface CrudPermissions {
    canManage: boolean;
    canView: boolean;
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
}

export interface Permissions {
    canViewDashboard: boolean;
    students: CrudPermissions;
    guardians: CrudPermissions;
    subjects: CrudPermissions;
    teachers: CrudPermissions;
    terms: CrudPermissions;
    users: CrudPermissions;
    classSubjects: CrudPermissions;
    classTeachers: CrudPermissions;
    exams: CrudPermissions;
    roles: CrudPermissions & { canAssignPermissions: boolean };
    permissions: CrudPermissions;
    settings: {
        canView: boolean;
        canEdit: boolean;
    };
    canManageMasterSettings: boolean;
    auth: {
        isSuperAdmin: boolean;
        user: Pick<User, 'id' | 'name' | 'email'>;
        roles: string[];
        permissions: string[];
    };
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: any;
    items?: NavItem[];
    disabled?: boolean;
    external?: boolean;
    isOpen?: boolean;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth | null;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
