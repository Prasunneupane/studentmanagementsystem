// composables/usePermissions.ts
import { computed, } from 'vue';
import { usePage, } from '@inertiajs/vue3';



export function usePermissions() {

  const page = usePage();

  const permissions = computed(() => (page.props.auth as any)?.permissions || {});

  const teachersPermissions = computed(() => {
    return permissions.value.teachers || {};
  });
  const usersPermission = computed(() => {
    return permissions.value.users || {};
  });

  const rolePermission= computed(() => {
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