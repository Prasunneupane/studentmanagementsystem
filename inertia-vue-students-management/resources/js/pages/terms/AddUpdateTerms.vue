<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import {
  Card, CardContent, CardDescription, CardHeader, CardTitle
} from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Loader2, Eye } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import { usePermission } from '@/composables/usePermissions'
const { toast } = useToast()
const { can } = usePermission();
// -------- PROPS ----------
const props = defineProps({
  subject: {
    type: Object,
    default: null
  }
})

// -------- HELPERS ----------
const formatType = (type: string) => {
  if (!type) return ''
  return type
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const isEdit = computed(() => !!props.subject)

// Dropdown options
const sections = [
  { value: 'core', label: 'Core' },
  { value: 'extra_curricular', label: 'Extra Curricular' },
  { value: 'optional', label: 'Optional' },
]

const defaultSubjectType = { value: 'core', label: 'Core' }

// -------- FORM INIT ----------
const form = useForm({
  name: props.subject?.name || '',
  code: props.subject?.code || '',
  type: props.subject
    ? { value: props.subject.type, label: formatType(props.subject.type) }
    : defaultSubjectType,
  description: props.subject?.description || '',
  is_active: props.subject?.is_active?.toString() || '1',
})

const errors = ref<Record<string, string>>({})

// -------- SUBMIT ----------
const handleSubmit = () => {
  errors.value = {}

  const payload = {
    onSuccess: () => {
      toast.success(isEdit.value ? "Subject updated successfully." : "Subject added successfully.")

      if (!isEdit.value) {
        form.reset()
        form.type = defaultSubjectType
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
    form.put(route('subjects.update', props.subject.id), payload)
  } else {
    form.post(route('subjects.store'), payload)
  }
}

</script>

<template>
  <Head :title="isEdit ? 'Edit Subject' : 'Add Subject'" />

  <AppLayout 
    :breadcrumbs="[
      { title: 'Subjects', href: '/subjects' },
      { title: isEdit ? 'Edit Subject' : 'Add Subject', href: '' }
    ]"
  >
    <Toaster position="top-right" />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      
      <Card>
        <CardHeader>
          <CardTitle>
            {{ isEdit ? 'Edit Subject' : 'Add Subject' }}

            <Button v-if="can('subjects.canView')" as-child class="ml-auto float-right">
              <Link :href="route('subjects.index')">
                <Eye class="w-4 h-4 mr-2" /> View Subjects
              </Link>
            </Button>
          </CardTitle>

          <CardDescription>
            {{ isEdit ? 'Update the subject details below.' : 'Fill in the details to add a new subject.' }}
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-8">

            <!-- Row 1: Name + Code -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="name">Subject Name <span class="text-red-500">*</span></Label>
                <Input id="name" v-model="form.name" placeholder="e.g. Mathematics" />
                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
              </div>

              <div class="space-y-2">
                <Label for="code">Subject Code <span class="text-red-500">*</span></Label>
                <Input id="code" v-model="form.code" placeholder="e.g. MATH101" />
                <p v-if="form.errors.code" class="text-sm text-red-600">{{ form.errors.code }}</p>
              </div>
            </div>

            <!-- Row 2: Type + Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <div class="space-y-2">
                <Label for="type">Subject Type <span class="text-red-500">*</span></Label>
                <SelectSearch
                  v-model="form.type"
                  :options="sections"
                  placeholder="Select subject type"
                />
                <p v-if="form.errors.type" class="text-sm text-red-600">{{ form.errors.type }}</p>
              </div>

              <div class="space-y-3">
                <Label>Status <span class="text-red-500">*</span></Label>
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

            <!-- Description -->
            <div class="space-y-2">
              <Label for="description">Description (Optional)</Label>
              <Textarea id="description" v-model="form.description" rows="4" placeholder="Brief description..." />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t" v-if="can('subjects.canCreate') || can('subjects.canEdit')">
              
              <Button type="submit" :disabled="form.processing" class="cursor-pointer">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ isEdit ? 'Update Subject' : 'Add Subject' }}
              </Button>

            </div>

          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
