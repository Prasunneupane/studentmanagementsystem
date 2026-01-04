<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import SelectSearch from "@/components/ui/select/Select-Search.vue"
import DatePicker from "@/components/ui/datepicker/DatePicker.vue"
import {
  Card, CardContent, CardDescription, CardHeader, CardTitle
} from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Loader2, Eye } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import { usePermissions } from '@/composables/usePermission'

const { toast } = useToast()

const { rolePermission } = usePermissions();
const rolePermissionList = rolePermission.value ;
// Props
const props = defineProps({
  role: {
    type: Object,
    default: null
  }
})

const isEdit = computed(() => !!props.role)

// Initialize form
const form = useForm({
  name: props.role?.name || '',
  is_active: props.role?.is_active?.toString() || '1',
  description: props.role?.description || '',
})
console.log(form,"formdata");

// When DatePicker changes → update form.dob (string: YYYY-MM-DD)

const errors = ref<Record<string, string>>({})
// Submit
const handleSubmit = () => {
  errors.value = {}

  const payload = {
    onSuccess: () => {
      toast.success(isEdit.value ? "Role updated successfully." : "Role added successfully.")

      if (!isEdit.value) {
        form.reset()
        form.name = '',
        form.description = '',
        form.is_active = '1'
      }
    },

    onError: () => {
      const errorMessages = Object.values(form.errors)
      console.log(errorMessages,"errormessage");
      
      const msg = errorMessages.length > 0 ? errorMessages[0] : "Something went wrong."
      toast.error(msg)
    }
  }

  if (isEdit.value) {
    form.put(route('roles.update', props.role.id), payload)
  } else {
    form.post(route('roles.store'), payload)
  }
}
</script>

<template>
  <Head :title="isEdit ? 'Edit Role' : 'Add Role'" />
  <!-- Hero No 1 -->
  <AppLayout :breadcrumbs="[
    { title: 'roles', href: '/roles' },
    { title: isEdit ? 'Edit Role' : 'Add Role', href: '' }
  ]">
    <Toaster position="top-right" />

    <div class="container mx-auto p-6 max-w-7xl">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <div>
            <CardTitle>{{ isEdit ? 'Edit Role' : 'Add Role' }}</CardTitle>
            <CardDescription>
              {{ isEdit ? 'Update Role details.' : 'Add a new role to the system.' }}
            </CardDescription>
          </div>
          <Button v-if="rolePermissionList.viewRole" as-child>
            <Link :href="route('roles.index')">
              <Eye class="w-4 h-4 mr-2" /> View Roles
            </Link>
          </Button>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-8">

            <!-- Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label>Role Name <span class="text-red-500">*</span></Label>
                <Input v-model="form.name" placeholder="SuperAdmin, Admin, Account..." />
                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
              </div>

              <div class="space-y-2">
                <Label>Description <span class="text-red-500">*</span></Label>
                <Textarea v-model="form.description" placeholder="Role description..." />
                <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
              </div>

            

              <div class="space-y-3">
                <Label>Is Active <span class="text-red-500">*</span></Label>
                <RadioGroup v-model="form.is_active" class="flex flex-row gap-8">
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem value="1" id="active" />
                    <Label for="active" class="cursor-pointer font-normal">Active</Label>
                  </div>
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem value="0" id="inactive" />
                    <Label for="inactive" class="cursor-pointer font-normal">Inactive</Label>
                  </div>
                </RadioGroup>
              </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-4 pt-6 border-t" v-if="rolePermissionList.canCreate || rolePermissionList.canEdit">
              <Button  type="submit" :disabled="form.processing" class="cursor-pointer">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ isEdit ? 'Update Role' : 'Add Role' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>