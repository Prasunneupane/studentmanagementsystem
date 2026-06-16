// composables/usePermissions.ts
import { type AppPageProps, type Permissions } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage<AppPageProps>();

    const permissions = computed<Permissions>(() => page.props.auth?.permissions || ({} as Permissions));

    const teachersPermissions = computed(() => {
        return permissions.value.teachers || {};
    });
    const usersPermission = computed(() => {
        return permissions.value.users || {};
    });

    const rolePermission = computed(() => {
        return permissions.value.roles || {};
    });

    const subjectPermission = computed(() => {
        return permissions.value.subjects || {};
    });

    return {
        permissions,
        teachersPermissions,
        usersPermission,
        rolePermission,
        subjectPermission,
    };
}
