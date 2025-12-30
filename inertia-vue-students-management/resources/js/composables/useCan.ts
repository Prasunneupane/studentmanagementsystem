import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useCan() {
  const page = usePage<any>()

  const props = computed(() => page.props)
    console.log(props,"props");
    
  /* -----------------------------
   | Auth
   ----------------------------- */
  const auth = computed(() => props.value.auth ?? {})

  const isSuperAdmin = computed(
    () => auth.value.isSuperAdmin === true
  )

  /* -----------------------------
   | Module permissions (DIRECT)
   ----------------------------- */
  const students    = computed(() => props.value.students ?? {})
  const subjects    = computed(() => props.value.subjects ?? {})
  const teachers    = computed(() => props.value.teachers ?? {})
  const users       = computed(() => props.value.users ?? {})
  const roles       = computed(() => props.value.roles ?? {})
  const permissions = computed(() => props.value.permissions ?? {})
  const settings    = computed(() => props.value.settings ?? {})

  /* -----------------------------
   | Slug-based permission check
   ----------------------------- */
  const can = (slug: string): boolean => {
    if (isSuperAdmin.value) return true
    return auth.value.permissions?.includes?.(slug) ?? false
  }

  return {
    // Slug-based
    can,

    // Module-based (USED BY UI)
    students,
    subjects,
    teachers,
    users,
    roles,
    permissions,
    settings,

    // Dashboard & system
    canViewDashboard: computed(() => props.value.canViewDashboard),
    canManageMasterSettings: computed(() => props.value.canManageMasterSettings),

    // Auth
    isSuperAdmin,
    currentUser: computed(() => auth.value.user),
  }
}
