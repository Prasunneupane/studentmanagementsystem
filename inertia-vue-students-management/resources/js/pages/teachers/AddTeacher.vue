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

const { toast } = useToast()

// Props
const props = defineProps({
  teacher: {
    type: Object,
    default: null
  },
  status: {
    type: Array<{ value: string; label: string }>,
    default: () => []
  }
})

const isEdit = computed(() => !!props.teacher)

// Status options
const statusOptions = ref(props.status)
const defaultStatus = computed(() => 
  statusOptions.value.find(s => s.value === 'active') || statusOptions.value[0]
)

// Date of Birth — convert string → Date object for DatePicker
const   dobDate = ref<Date | null>(null)

// Initialize form
const form = useForm({
  name: props.teacher?.name || '',
  email: props.teacher?.email || '',
  phone: props.teacher?.phone || '',
  address: props.teacher?.address || '',
  subject_specializtion: props.teacher?.subject_specialization || '',
  qualification: props.teacher?.qualification || '',
  status: props.teacher
    ? statusOptions.value.find(s => s.value === props.teacher.status) || defaultStatus.value
    : defaultStatus.value,
  joining_date: props.teacher?.joining_date || new Date().toISOString().split('T')[0],
  leaving_date: props.teacher?.leaving_date || '',
  photo: null as File | null,  // ← File, not string
  dob: props.teacher?.date_of_birth || '',
  is_active: props.teacher?.is_active?.toString() || '1',
})

// Sync form.dob ↔ dobDate (DatePicker uses Date object)
watch(() => form.dob, (val) => {
  if (val) {
    const date = new Date(val)
    if (!isNaN(date.getTime())) {
      dobDate.value = date
    }
  } else {
    dobDate.value = null
  }
}, { immediate: true })

// When DatePicker changes → update form.dob (string: YYYY-MM-DD)
const updateDob = (date: Date | null) => {
  dobDate.value = date
  form.dob = date ? date.toISOString().split('T')[0] : ''
}

// File input handler
const handlePhotoChange = (e: Event) => {
  const input = e.target as HTMLInputElement
  if (input.files?.[0]) {
    form.photo = input.files[0]
  }
}
const errors = ref<Record<string, string>>({})
// Submit
const handleSubmit = () => {
  errors.value = {}

  const payload = {
    onSuccess: () => {
      toast.success(isEdit.value ? "Subject updated successfully." : "Subject added successfully.")

      if (!isEdit.value) {
        form.reset()
        form.status = defaultStatus.value
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
    form.put(route('teachers.update', props.teacher.id), payload)
  } else {
    form.post(route('teachers.store'), payload)
  }
}
</script>

<template>
  <Head :title="isEdit ? 'Edit Teacher' : 'Add Teacher'" />
  <!-- Hero No 1 -->
  <AppLayout :breadcrumbs="[
    { title: 'Teachers', href: '/teachers' },
    { title: isEdit ? 'Edit Teacher' : 'Add Teacher', href: '' }
  ]">
    <Toaster position="top-right" />

    <div class="container mx-auto p-6 max-w-7xl">
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <div>
            <CardTitle>{{ isEdit ? 'Edit Teacher' : 'Add Teacher' }}</CardTitle>
            <CardDescription>
              {{ isEdit ? 'Update teacher details.' : 'Add a new teacher to the system.' }}
            </CardDescription>
          </div>
          <Button as-child>
            <Link :href="route('teachers.index')">
              <Eye class="w-4 h-4 mr-2" /> View Teachers
            </Link>
          </Button>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-8">

            <!-- Row 1 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="space-y-2">
                <Label>Name <span class="text-red-500">*</span></Label>
                <Input v-model="form.name" placeholder="Ram Thapa" />
                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
              </div>

              <div class="space-y-2">
                <Label>Email <span class="text-red-500">*</span></Label>
                <Input type="email" v-model="form.email" placeholder="ram@example.com" />
                <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
              </div>

              <div class="space-y-2">
                <Label>Contact No <span class="text-red-500">*</span></Label>
                <Input v-model="form.phone" placeholder="98XXXXXXXX" />
                <p v-if="form.errors.phone" class="text-sm text-red-600">{{ form.errors.phone }}</p>
              </div>
            </div>

            <!-- Row 2 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="space-y-2">
                <Label>Address <span class="text-red-500">*</span></Label>
                <Input v-model="form.address" placeholder="Kathmandu, Nepal" />
                <p v-if="form.errors.address" class="text-sm text-red-600">{{ form.errors.address }}</p>
              </div>

              <div class="space-y-2">
                <Label>Subject Specialization <span class="text-red-500">*</span></Label>
                <Input v-model="form.subject_specializtion" placeholder="Mathematics, Physics..." />
                <p v-if="form.errors.subject_specializtion" class="text-sm text-red-600">{{ form.errors.subject_specializtion }}</p>
              </div>

              <div class="space-y-2">
                <Label>Qualification <span class="text-red-500">*</span></Label>
                <Input v-model="form.qualification" placeholder="M.Sc, B.Ed..." />
                <p v-if="form.errors.qualification" class="text-sm text-red-600">{{ form.errors.qualification }}</p>
              </div>
            </div>

            <!-- Row 3: DOB + Status + Is Active -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="space-y-2">
                <Label>Date of Birth <span class="text-red-500">*</span></Label>
                <DatePicker
                  :model-value="dobDate"
                  @update:model-value="updateDob"
                  :year-range="[1980, new Date().getFullYear()]"
                  month-year-selector
                  placeholder="Select date of birth"
                />
                <p v-if="form.errors.dob" class="text-sm text-red-600">{{ form.errors.dob }}</p>
              </div>

              <div class="space-y-2">
                <Label>Status <span class="text-red-500">*</span></Label>
                <SelectSearch
                  v-model="form.status"
                  :options="statusOptions"
                  placeholder="Select status"
                />
                <p v-if="form.errors.status" class="text-sm text-red-600">{{ form.errors.status }}</p>
              </div>

              <!-- <div class="space-y-3">
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
              </div> -->
            </div>

            <!-- Photo Upload -->
            <div class="space-y-2">
              <Label>Photo</Label>
              <Input type="file" accept="image/*" @change="handlePhotoChange" />
              <p v-if="form.errors.photo" class="text-sm text-red-600">{{ form.errors.photo }}</p>
              <p v-if="props.teacher?.photo" class="text-sm text-muted-foreground">
                Current photo: <a :href="props.teacher.photo" target="_blank" class="underline">View</a>
              </p>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button type="submit" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ isEdit ? 'Update Teacher' : 'Add Teacher' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>