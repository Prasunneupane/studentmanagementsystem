<script setup lang="ts">
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
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
import { Loader2, Eye } from 'lucide-vue-next'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'

const { toast } = useToast()

/* -------------------- Props -------------------- */
const props = defineProps<{
  role: { id: number; name: string }[]
  allPermissions: { id: number; name: string; module?: string }[]
  roleId?: number
  roleName?: string
  assignedPermissions?: number[]
}>()
console.log(props.role,"props.role");

/* -------------------- Role Options -------------------- */
const roleOptions = computed(() =>
  props.role.map(role => ({
    value: role.id,
    label: role.name,
  }))
)

/* -------------------- Form -------------------- */
const form = useForm({
  role_id: props.roleId
    ? { value: props.roleId, label: props.roleName }
    : null,
  permissions: props.assignedPermissions || [],
})

/* -------------------- Submit -------------------- */
const handleSubmit = () => {
  form
    .transform(data => ({
      ...data,
      role_id: data.role_id?.value, // convert to number for backend
    }))
    .post(route('roles.permissions.assign'), {
      onSuccess: () => toast.success('Permissions assigned successfully'),
      onError: () => toast.error('Something went wrong'),
    })
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
        <!-- HEADER (unchanged) -->
        <CardHeader class="flex justify-between items-center">
          <div>
            <!-- <CardTitle>Assign Permissions</CardTitle> -->
            <CardDescription>
              Select a role and assign permissions
            </CardDescription>
          </div>
          <Button as-child>
            <Link :href="route('roles.index')">
              <Eye class="w-4 h-4 mr-2" /> View Roles
            </Link>
          </Button>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-8">

            <!-- CENTERED CONTENT ONLY -->
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
                  />
                  <p v-if="form.errors.role_id" class="text-sm text-red-600">
                    {{ form.errors.role_id }}
                  </p>
                </div>

                <!-- Permissions -->
                <div class="space-y-3">
                  <Label>Permissions</Label>

                  <div class="grid grid-cols-1 gap-4">
                    <div
                      v-for="permission in props.allPermissions"
                      :key="permission.id"
                      class="flex items-center gap-3"
                    >
                      <Checkbox
                        :id="`perm-${permission.id}`"
                        :checked="form.permissions.includes(permission.id)"
                        @update:checked="checked => {
                          if (checked) {
                            form.permissions.push(permission.id)
                          } else {
                            form.permissions = form.permissions.filter(
                              id => id !== permission.id
                            )
                          }
                        }"
                      />

                      <Label
                        :for="`perm-${permission.id}`"
                        class="cursor-pointer font-normal"
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

                  <p
                    v-if="form.errors.permissions"
                    class="text-sm text-red-600"
                  >
                    {{ form.errors.permissions }}
                  </p>
                </div>

              </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end border-t pt-6">
              <Button type="submit" :disabled="form.processing">
                <Loader2
                  v-if="form.processing"
                  class="mr-2 h-4 w-4 animate-spin"
                />
                Assign Permissions
              </Button>
            </div>

          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
