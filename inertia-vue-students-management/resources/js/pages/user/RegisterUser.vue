<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import SelectSearch from "@/components/ui/select/Select-Search.vue"
import InputError from '@/components/InputError.vue';
import {
  Card, CardContent, CardDescription, CardHeader, CardTitle
} from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Loader2, Eye } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'

const { toast } = useToast()

// Props
const props = defineProps({
  user: {
    type: Object,
    default: null
  },
  roles: {
    type: Array<{ value: string; label: string }>,
    default: () => []
  },
  // userRoles: {
  //   type: String,
  //   default: ''
  // }
})

const isEdit = computed(() => !!props.user)

// Status options
const statusOptions = ref(props.roles)
const defaultStatus = { value: '', label: '' }
// Initialize form
const form = useForm({
  name: props.user?.name || '',
  email: props.user?.email || '',
  password: props.user ? '' : '',
  password_confirmation: '',
  roles: props.user?.roleId || '',
  is_active: props.user ? Number(props.user.is_active) : 1,
})
console.log(form, "formdata");

const errors = ref<Record<string, string>>({})
// Submit
const handleSubmit = () => {
  errors.value = {}
  if (!form.roles) {
    form.setError('roles', 'The role field is required.')
    return
  }
  const payload = {
    onSuccess: () => {
      toast.success(isEdit.value ? "User updated successfully." : "User added successfully.")

      if (!isEdit.value) {
        form.reset()
        form.name = '',
          form.email = '',
          form.password = '',
          form.password_confirmation = ''
      }
    },

    onError: () => {
      const errorMessages = Object.values(form.errors)
      console.log(errorMessages, "errormessage");

      const msg = errorMessages.length > 0 ? errorMessages[0] : "Something went wrong."
      toast.error(msg)
    }
  }

  if (isEdit.value) {
    form.put(route('users.update', props.user.id), payload)
  } else {
    form.post(route('users.store'), payload)
  }
}
watch(
  () => ({ ...form.data() }),
  (newVal, oldVal) => {
    Object.keys(newVal).forEach((key) => {
      if (newVal[key as keyof typeof newVal] !== oldVal?.[key as keyof typeof oldVal]) {
        form.clearErrors(key as keyof typeof newVal)
      }
    })
  },
  { deep: true }
)
</script>

<template>

  <Head :title="isEdit ? 'Edit User' : 'Add User'" />
  <!-- Hero No 1 -->
  <AppLayout :breadcrumbs="[
    { title: 'Users', href: '/users' },
    { title: isEdit ? 'Edit User' : 'Add User', href: '' }
  ]">
    <Toaster position="top-right" />

    <div class="container mx-auto p-6 max-w-7xl">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <div>
            <CardTitle>{{ isEdit ? 'Edit User' : 'Add User' }}</CardTitle>
            <CardDescription>
              {{ isEdit ? 'Update User details.' : 'Add a new User to the system.' }}
            </CardDescription>
          </div>
          <Button as-child>
            <Link :href="route('users.index')">
              <Eye class="w-4 h-4 mr-2" /> View User
            </Link>
          </Button>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="flex flex-col gap-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name"
                  placeholder="Full name" />
                <InputError :message="form.errors.name" />
              </div>

              <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email"
                  placeholder="email@example.com" />
                <InputError :message="form.errors.email" />
              </div>
              <!-- Password (Create only) -->
              <div v-if="!isEdit" class="grid gap-2">
                <Label for="password">Password</Label>
                <Input id="password" type="password" required autocomplete="new-password" v-model="form.password"
                  placeholder="Password" />
                <InputError :message="form.errors.password" />
              </div>

              <div v-if="!isEdit" class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <Input id="password_confirmation" type="password" required autocomplete="new-password"
                  v-model="form.password_confirmation" placeholder="Confirm password" />
                <InputError :message="form.errors.password_confirmation" />
              </div>


              <div class="space-y-2">
                <Label>Role <span class="text-red-500">*</span></Label>
                <SelectSearch :model-value="statusOptions.find(r => r.value === form.roles) || null"
                  :options="statusOptions" placeholder="Select Role"
                  @update:modelValue="option => form.roles = option?.value ?? ''" />
                <p v-if="form.errors.roles" class="text-sm text-red-600">
                  {{ form.errors.roles }}
                </p>
              </div>

              <!-- Is Active (Edit only) -->
              <div v-if="isEdit" class="space-y-2">
                <Label>Is Active</Label>

                <RadioGroup v-model="form.is_active" class="flex gap-6">
                  <div class="flex items-center gap-2">
                    <RadioGroupItem :value="1" id="active-yes" />
                    <Label for="active-yes">True</Label>
                  </div>

                  <div class="flex items-center gap-2">
                    <RadioGroupItem :value="0" id="active-no" />
                    <Label for="active-no">False</Label>
                  </div>
                </RadioGroup>

                <InputError :message="form.errors.is_active" />
              </div>
            </div>
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button type="submit" class="cursor-pointer" tabindex="5" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ isEdit ? 'Update User' : 'Add User' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>