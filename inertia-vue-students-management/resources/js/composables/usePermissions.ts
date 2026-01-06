import { reactive, watchEffect } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermission() {
  const page = usePage()

  // 🔥 SINGLE reactive permission tree
  const permissions = reactive({
    canViewDashboard: false,

    students: {},
    subjects: {},
    teachers: {},
    users: {},
    roles: {},
    permissions: {},
    settings: {},

    canManageMasterSettings: false,

    auth: {
      isSuperAdmin: false,
      user: null,
      roles: [],
      permissions: []
    }
  })

  // 🔄 Sync from Inertia (handles timing automatically)
  watchEffect(() => {
    const serverPermissions = (page.props.auth as any)?.permissions
    if (!serverPermissions) return

    Object.assign(permissions, serverPermissions)
  })

  /* ===========================
   | Helper Methods (Industry)
   =========================== */

  const can = (path: string) => {
    return path
      .split('.')
      .reduce((acc: any, key) => acc?.[key], permissions) === true
  }

  const canAny = (paths: string[]) =>
    paths.some(p => can(p))

  const canAll = (paths: string[]) =>
    paths.every(p => can(p))

  return {
    permissions, // 👈 NO .value EVER
    can,
    canAny,
    canAll
  }
}
