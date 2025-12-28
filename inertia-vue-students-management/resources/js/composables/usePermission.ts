// composables/usePermissions.ts
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
  const page = usePage();
  
  const permissions = computed(() => page.props.auth?.permissions || {});
  const userPermissionSlugs = computed(() => permissions.value.permissions || []);

  const hasPermission = (permission: string): boolean => {
    return userPermissionSlugs.value.includes(permission);
  };

  const hasAnyPermission = (permissionArray: string[]): boolean => {
    return permissionArray.some(permission => 
      userPermissionSlugs.value.includes(permission)
    );
  };

  const hasAllPermissions = (permissionArray: string[]): boolean => {
    return permissionArray.every(permission => 
      userPermissionSlugs.value.includes(permission)
    );
  };

  const can = (ability: string): boolean => {
    return permissions.value[ability] === true;
  };

  return {
    permissions,
    userPermissionSlugs,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    can,
  };
}