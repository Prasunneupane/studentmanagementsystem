<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import SelectSearch from '@/components/ui/select/Select-Search.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'

import { Loader2, Eye, Plus, CheckSquare, Square } from 'lucide-vue-next'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import { usePermission } from '@/composables/usePermissions'


const { toast } = useToast()
interface Option {
  value: string
  label: string
}
const { can } = usePermission();
/* -------------------- Props -------------------- */
const props = defineProps<{
  role: { id: number; name: string }
  allPermissions: { id: number; name: string; module?: string }[]
  rolePermissions: number[]
  roles: { id: number; name: string }[]
}>()

/* -------------------- State -------------------- */
const loadingPermissions = ref(false)
const selectedPermissions = ref<number[]>([...props.rolePermissions])
const processing = ref(false)

/* -------------------- Group Permissions by Module -------------------- */
const groupedPermissions = computed(() => {
  const groups: Record<string, { id: number; name: string; module?: string }[]> = {}
  
  props.allPermissions.forEach(permission => {
    const moduleName = permission.module || 'General'
    if (!groups[moduleName]) {
      groups[moduleName] = []
    }
    groups[moduleName].push(permission)
  })
  
  // Sort modules alphabetically
  return Object.keys(groups)
    .sort()
    .reduce((acc, key) => {
      acc[key] = groups[key]
      return acc
    }, {} as Record<string, { id: number; name: string; module?: string }[]>)
})

/* -------------------- Module Selection States -------------------- */
const isModuleFullySelected = (moduleName: string) => {
  const modulePermissions = groupedPermissions.value[moduleName]
  return modulePermissions.every(p => selectedPermissions.value.includes(p.id))
}

const isModulePartiallySelected = (moduleName: string) => {
  const modulePermissions = groupedPermissions.value[moduleName]
  const selectedCount = modulePermissions.filter(p => 
    selectedPermissions.value.includes(p.id)
  ).length
  return selectedCount > 0 && selectedCount < modulePermissions.length
}

const toggleModule = (moduleName: string) => {
  const modulePermissions = groupedPermissions.value[moduleName]
  const isFullySelected = isModuleFullySelected(moduleName)
  
  if (isFullySelected) {
    // Deselect all permissions in this module
    selectedPermissions.value = selectedPermissions.value.filter(
      id => !modulePermissions.some(p => p.id === id)
    )
  } else {
    // Select all permissions in this module
    const modulePermissionIds = modulePermissions.map(p => p.id)
    selectedPermissions.value = [
      ...selectedPermissions.value.filter(
        id => !modulePermissionIds.includes(id)
      ),
      ...modulePermissionIds
    ]
  }
}

/* -------------------- Role Options -------------------- */
const roleOptions = computed<Option[]>(() =>
  props.roles.map(role => ({
    value: String(role.id),
    label: role.name,
  }))
)

/* -------------------- Form -------------------- */
const form = useForm({
  role_id: props.role
    ? { value: String(props.role.id), label: props.role.name }
    : null,
})

/* -------------------- Watch Role Change -------------------- */
watch(
  () => form.role_id,
  async (newRole, oldRole) => {
    if (!newRole || newRole.value === oldRole?.value) return

    loadingPermissions.value = true

    router.get(
      route('roles.permissions.get', { role: newRole.value }),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        only: ['rolePermissions'],
        onSuccess: (page) => {
          selectedPermissions.value = [...((page.props.rolePermissions as number[]) || [])]
          loadingPermissions.value = false
          console.log('✅ Loaded permissions for role:', newRole.value, selectedPermissions.value)
        },
        onError: () => {
          toast.error('Failed to load role permissions')
          loadingPermissions.value = false
        },
      }
    )
  }
)

/* -------------------- Watch Selected Permissions -------------------- */
watch(
  selectedPermissions,
  (newVal) => {
    console.log('🔍 Permissions array changed:', newVal, 'Length:', newVal.length)
  },
  { deep: true }
)

const togglePermission = (permissionId: number, checked: boolean) => {
  if (checked) {
    if (!selectedPermissions.value.includes(permissionId)) {
      selectedPermissions.value.push(permissionId)
    }
  } else {
    selectedPermissions.value = selectedPermissions.value.filter(
      id => id !== permissionId
    )
  }
}

/* -------------------- Submit -------------------- */
const handleSubmit = () => {
  if (!form.role_id) {
    toast.error('Please select a role')
    return
  }
  processing.value = true

  router.post(
    route('roles.permissions.assign'),
    {
      role_id: parseInt(form.role_id.value),
      permissions: selectedPermissions.value
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Permissions assigned successfully')
        processing.value = false
      },
      onError: (errors) => {
        toast.error('Failed to assign permissions')
        processing.value = false
      },
    }
  )
}
</script>

<template>
  <Head title="Assign Permissions to Role" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Roles', href: '/settings/roles' },
      { title: 'Assign Permissions', href: '' },
    ]"
  >
    <Toaster position="top-right" />

    <div class="container mx-auto p-6 max-w-[1400px]">
      <Card>
        <!-- HEADER -->
        <CardHeader class="flex justify-between items-center">
          <div>
            <CardTitle>Assign Permissions to Role</CardTitle>
            <CardDescription>
              Select a role and assign permissions organized by module
            </CardDescription>
          </div>
          <div class="flex gap-3">
            <Button v-if="can('roles.canView')" as-child>
              <Link :href="route('roles.index')">
                <Eye class="w-4 h-4 mr-2" /> View Roles
              </Link>
            </Button>

            <Button v-if="can('roles.canCreate')" as-child>
              <Link :href="route('roles.create')">
                <Plus class="w-4 h-4 mr-2" /> Create Role
              </Link>
            </Button>
          </div>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-8">
            <!-- Role Select -->
            <div class="max-w-md space-y-2">
              <Label>
                Role <span class="text-red-500">*</span>
              </Label>
              <SelectSearch
                v-model="form.role_id"
                :options="roleOptions"
                placeholder="Select role"
                :disabled="processing"
              />
              <p v-if="form.errors.role_id" class="text-sm text-red-600">
                {{ form.errors.role_id }}
              </p>
            </div>

            <!-- Permissions Section -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <Label class="text-lg font-semibold">
                  Permissions
                </Label>
                <span class="text-sm text-muted-foreground bg-muted px-3 py-1 rounded-full">
                  {{ selectedPermissions.length }} of {{ allPermissions.length }} selected
                </span>
              </div>

              <!-- Loading State -->
              <div
                v-if="loadingPermissions"
                class="flex items-center justify-center py-12"
              >
                <Loader2 class="h-8 w-8 animate-spin text-primary" />
                <span class="ml-3 text-muted-foreground">
                  Loading permissions...
                </span>
              </div>

              <!-- Permissions Grid by Module -->
              <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                  v-for="(permissions, moduleName) in groupedPermissions"
                  :key="moduleName"
                  class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                >
                  <!-- Module Header -->
                  <div 
                    class="bg-muted/50 p-3 border-b cursor-pointer hover:bg-muted transition-colors flex items-center gap-2"
                    @click="toggleModule(moduleName)"
                  >
                    <div class="flex-shrink-0">
                      <CheckSquare 
                        v-if="isModuleFullySelected(moduleName)"
                        class="w-5 h-5 text-primary"
                      />
                      <div 
                        v-else-if="isModulePartiallySelected(moduleName)"
                        class="w-5 h-5 border-2 border-primary rounded flex items-center justify-center"
                      >
                        <div class="w-2.5 h-2.5 bg-primary rounded-sm"></div>
                      </div>
                      <Square 
                        v-else
                        class="w-5 h-5 text-muted-foreground"
                      />
                    </div>
                    <div class="flex-1 min-w-0">
                      <h3 class="font-semibold text-sm truncate">{{ moduleName }}</h3>
                      <p class="text-xs text-muted-foreground">
                        {{ permissions.filter(p => selectedPermissions.includes(p.id)).length }}/{{ permissions.length }} selected
                      </p>
                    </div>
                  </div>

                  <!-- Module Permissions -->
                  <div class="p-3 space-y-2 max-h-64 overflow-y-auto">
                    <div
                      v-for="permission in permissions"
                      :key="permission.id"
                      class="flex items-start gap-2 p-1.5 rounded hover:bg-muted/30 transition-colors"
                    >
                      <input
                        type="checkbox"
                        :id="`perm-${permission.id}`"
                        :value="permission.id"
                        v-model="selectedPermissions"
                        :disabled="processing"
                        class="
                          mt-0.5 h-4 w-4 rounded border border-zinc-300
                          accent-black
                          focus:ring-black/40
                          cursor-pointer
                          disabled:cursor-not-allowed disabled:opacity-50
                        "
                      />
                      <Label
                        :for="`perm-${permission.id}`"
                        class="cursor-pointer font-normal text-sm leading-tight flex-1"
                      >
                        {{ permission.name }}
                      </Label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3 border-t pt-6">
              <Button
                type="button"
                variant="outline"
                as-child
                :disabled="processing"
                v-if="can('roles.canView')"
              >
                <Link :href="route('roles.index')"> Cancel </Link>
              </Button>
              <Button
                class="cursor-pointer"
                type="submit"
                :disabled="processing || loadingPermissions"
                v-if="can('roles.canAssignPermissions')"
              >
                <Loader2
                  v-if="processing"
                  class="mr-2 h-4 w-4 animate-spin"
                />
                {{ processing ? 'Assigning...' : 'Assign Permissions' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>