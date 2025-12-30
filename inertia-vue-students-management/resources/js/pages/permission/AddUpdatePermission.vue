<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
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
const page = usePage();
const { toast } = useToast()
const permissions = computed(() => (page.props.auth as any)?.permissions || {});
const perm = permissions.value.permissions ;
// Props
const props = defineProps({
  permission: {
    type: Object,
    default: null
  }
})

const isEdit = computed(() => !!props.permission)

// Initialize form
const form = useForm({
  name: props.permission?.name || '',
  slug: props.permission?.slug || '',
  module: props.permission?.module || '',
  is_active: props.permission?.is_active?.toString() || '1',
  description: props.permission?.description || '',
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
        form.module = '',
        form.slug = '',
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
    form.put(route('permissions.update', props.permission.id), payload)
  } else {
    form.post(route('permissions.store'), payload)
  }
}
</script>

<template>
  <Head :title="isEdit ? 'Edit Permission' : 'Add Permission'" />
  <!-- Hero No 1 -->
  <AppLayout :breadcrumbs="[
    { title: 'permissions', href: '/permissions' },
    { title: isEdit ? 'Edit Permissions' : 'Add Permissions', href: '' }
  ]">
    <Toaster position="top-right" />

    <div class="container mx-auto p-6 max-w-7xl">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <div>
            <CardTitle>{{ isEdit ? 'Edit Permissions' : 'Add Permissions' }}</CardTitle>
            <CardDescription>
              {{ isEdit ? 'Update Permissions details.' : 'Add a new permissions to the system.' }}
            </CardDescription>
          </div>
          <Button v-if="perm.canView" as-child>
            <Link :href="route('permissions.index')">
              <Eye class="w-4 h-4 mr-2" /> View Permissions
            </Link>
          </Button>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-8">

            <!-- Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label>Permission Name <span class="text-red-500">*</span></Label>
                <Input v-model="form.name" placeholder="Create ..., View ..." />
                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
              </div>

              <div class="space-y-2">
                <Label>Description <span class="text-red-500"></span></Label>
                <Textarea v-model="form.description" placeholder=" description..." />
                <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
              </div>

              <div class="space-y-2">
                <Label>Module <span class="text-red-500"></span></Label>
                <Input v-model="form.module" placeholder="Teacher, Subject, Users..." />
                <p v-if="form.errors.module" class="text-sm text-red-600">{{ form.errors.module }}</p>
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
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button type="submit" :disabled="form.processing" class="cursor-pointer">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ isEdit ? 'Update Permissions' : 'Add Permissions' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>