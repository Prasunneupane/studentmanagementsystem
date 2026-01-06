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

import { Loader2, Eye, Plus } from 'lucide-vue-next'
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
const selectedPermissions = ref<number[]>([...props.rolePermissions]) // Use separate ref for permissions
const processing = ref(false)

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

    // Fetch role permissions via Inertia visit
    router.get(
      route('roles.permissions.get', { role: newRole.value }),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        only: ['rolePermissions'],
        onSuccess: (page) => {
          // Update permissions based on fetched data
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

  // Use router.post directly
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

    <div class="container mx-auto p-6 max-w-7xl">
      <Card>
        <!-- HEADER -->
        <CardHeader class="flex justify-between items-center">
          <div>
            <CardTitle>Assign Permissions to Role</CardTitle>
            <CardDescription>
              Select a role and assign permissions
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
            <!-- CENTERED CONTENT -->
            <div class="flex justify-center">
              <div class="w-full max-w-3xl space-y-8">
                <!-- Role Select -->
                <div class="space-y-2">
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

                <!-- Permissions -->
                <div class="space-y-3">
                  <Label>
                    Permissions 
                    <span class="text-xs text-muted-foreground ml-2">
                      ({{ selectedPermissions.length }} selected)
                    </span>
                  </Label>

                  <!-- Loading State -->
                  <div
                    v-if="loadingPermissions"
                    class="flex items-center justify-center py-8"
                  >
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                    <span class="ml-2 text-muted-foreground">
                      Loading permissions...
                    </span>
                  </div>

                  <!-- Permissions List -->
                  <div v-else class="grid grid-cols-1 gap-4">
                    <div
                      v-for="permission in props.allPermissions"
                      :key="permission.id"
                      class="flex items-center gap-3 p-2 rounded-md hover:bg-muted/50 transition-colors"
                    >
                      <input
                          type="checkbox"
                          :id="`perm-${permission.id}`"
                          :value="permission.id"
                          v-model="selectedPermissions"
                          :disabled="processing"
                          class="
                            h-4 w-4 rounded border border-zinc-300
                            accent-black
                             focus:ring-black/40
                            cursor-pointer
                            disabled:cursor-not-allowed disabled:opacity-50
                          "
                        />


                      <Label
                        :for="`perm-${permission.id}`"
                        class="cursor-pointer font-normal flex-1"
                      >
                        {{ permission.name }}
                        <span
                          v-if="permission.module"
                          class="text-xs text-muted-foreground ml-1"
                        >
                          ({{ permission.module }})
                        </span>
                      </Label>
                    </div>
                  </div>

                  <!-- <p
                    v-if="form.errors"
                    class="text-sm text-red-600"
                  >
                    {{ form.errors }}
                  </p> -->
                </div>
              </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3 border-t pt-6" >
              <Button
                type="button"
                variant="outline"
                as-child
                :disabled="processing"
                v-if="can('roles.canView')"
              >
                <Link :href="route('roles.index')"> Cancel </Link>
              </Button>
              <Button  class="cursor-pointer" type="submit" :disabled="processing || loadingPermissions"
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