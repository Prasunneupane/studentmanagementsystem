<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import CustomSelect from '@/components/CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import { Loader2, Save, X } from 'lucide-vue-next'
import axios from 'axios'

const { toast } = useToast()

interface Option {
  value: string
  label: string
}

interface Props {
  classes: Option[]
  subjects: Option[]
  teachers: Option[]
  academicYears: Option[]
  currentAcademicYear: Option | null
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Class Subjects', href: '/class-subjects' },
  { title: 'Assign Subject', href: '/class-subjects/create' }
]

const sections = ref<Option[]>([])
const loadingSections = ref(false)

const form = useForm({
  class_id: null as Option | null,
  section_id: null as Option | null,
  subject_id: null as Option | null,
  teacher_id: null as Option | null,
  academic_year_id: props.currentAcademicYear,
  is_optional: false,
  periods_per_week: 5,
  max_marks: 100,
  pass_marks: 40,
})

// Fetch sections when class changes
watch(() => form.class_id, async (newClass) => {
  form.section_id = null
  sections.value = []
  
  if (!newClass?.value) return
  
  loadingSections.value = true
  try {
    const response = await axios.get('/class-subjects/sections-by-class', {
      params: { class_id: newClass.value }
    })
    sections.value = response.data
  } catch (error) {
    console.error('Failed to fetch sections:', error)
    toast.error('Failed to load sections')
  } finally {
    loadingSections.value = false
  }
})

// Validation
const errors = computed(() => {
  const errs: Record<string, string> = {}
  if (!form.class_id) errs.class_id = 'Class is required'
  if (!form.section_id) errs.section_id = 'Section is required'
  if (!form.subject_id) errs.subject_id = 'Subject is required'
  if (!form.academic_year_id) errs.academic_year_id = 'Academic year is required'
  if (form.periods_per_week < 0) errs.periods_per_week = 'Must be 0 or greater'
  if (form.max_marks <= 0) errs.max_marks = 'Must be greater than 0'
  if (form.pass_marks < 0) errs.pass_marks = 'Must be 0 or greater'
  if (form.pass_marks > form.max_marks) errs.pass_marks = 'Cannot exceed max marks'
  return errs
})

const canSubmit = computed(() => {
  return Object.keys(errors.value).length === 0 && !form.processing
})

const handleSubmit = () => {
  if (!canSubmit.value) {
    toast.error('Please fill all required fields correctly')
    return
  }

  form.transform((data) => ({
    class_id: data.class_id?.value,
    section_id: data.section_id?.value,
    subject_id: data.subject_id?.value,
    teacher_id: data.teacher_id?.value || null,
    academic_year_id: data.academic_year_id?.value,
    is_optional: data.is_optional,
    periods_per_week: data.periods_per_week,
    max_marks: data.max_marks,
    pass_marks: data.pass_marks,
  })).post('/class-subjects', {
    onSuccess: () => {
      toast.success('Subject assigned successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      toast.error(firstError as string)
    },
  })
}

const handleCancel = () => {
  router.visit('/class-subjects')
}
</script>

<template>
  <Head title="Assign Subject to Class" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />
    
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      <Card class="w-full max-w-4xl mx-auto shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <CardTitle class="text-2xl font-bold">Assign Subject to Class-Section</CardTitle>
          <p class="text-sm text-muted-foreground mt-1">
            Configure subject assignment, teacher allocation, and grading parameters
          </p>
        </CardHeader>
        
        <CardContent class="pt-6">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Academic Year -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="academic_year_id">
                  Academic Year <span class="text-red-500">*</span>
                </Label>
                <CustomSelect
                  id="academic_year_id"
                  v-model="form.academic_year_id"
                  :options="academicYears"
                  placeholder="Select Academic Year"
                  :class="{ 'border-red-500': errors.academic_year_id }"
                />
                <p v-if="errors.academic_year_id" class="text-sm text-red-600">
                  {{ errors.academic_year_id }}
                </p>
              </div>
            </div>

            <!-- Class and Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="class_id">
                  Class <span class="text-red-500">*</span>
                </Label>
                <CustomSelect
                  id="class_id"
                  v-model="form.class_id"
                  :options="classes"
                  placeholder="Select Class"
                  :class="{ 'border-red-500': errors.class_id }"
                />
                <p v-if="errors.class_id" class="text-sm text-red-600">
                  {{ errors.class_id }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="section_id">
                  Section <span class="text-red-500">*</span>
                </Label>
                <CustomSelect
                  id="section_id"
                  v-model="form.section_id"
                  :options="sections"
                  :disabled="!form.class_id || loadingSections"
                  :placeholder="loadingSections ? 'Loading...' : 'Select Section'"
                  :class="{ 'border-red-500': errors.section_id }"
                />
                <p v-if="errors.section_id" class="text-sm text-red-600">
                  {{ errors.section_id }}
                </p>
              </div>
            </div>

            <!-- Subject and Teacher -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="subject_id">
                  Subject <span class="text-red-500">*</span>
                </Label>
                <CustomSelect
                  id="subject_id"
                  v-model="form.subject_id"
                  :options="subjects"
                  placeholder="Select Subject"
                  :class="{ 'border-red-500': errors.subject_id }"
                />
                <p v-if="errors.subject_id" class="text-sm text-red-600">
                  {{ errors.subject_id }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="teacher_id">Teacher (Optional)</Label>
                <CustomSelect
                  id="teacher_id"
                  v-model="form.teacher_id"
                  :options="teachers"
                  placeholder="Select Teacher"
                />
                <p class="text-xs text-muted-foreground">
                  Leave empty if teacher not yet assigned
                </p>
              </div>
            </div>

            <!-- Configuration -->
            <div class="space-y-4 p-4 bg-muted/50 rounded-lg">
              <h3 class="font-semibold text-sm">Subject Configuration</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-2">
                  <Label for="periods_per_week">Periods per Week</Label>
                  <Input
                    id="periods_per_week"
                    v-model.number="form.periods_per_week"
                    type="number"
                    min="0"
                    max="50"
                    :class="{ 'border-red-500': errors.periods_per_week }"
                  />
                  <p v-if="errors.periods_per_week" class="text-sm text-red-600">
                    {{ errors.periods_per_week }}
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="max_marks">
                    Maximum Marks <span class="text-red-500">*</span>
                  </Label>
                  <Input
                    id="max_marks"
                    v-model.number="form.max_marks"
                    type="number"
                    min="0"
                    step="0.01"
                    :class="{ 'border-red-500': errors.max_marks }"
                  />
                  <p v-if="errors.max_marks" class="text-sm text-red-600">
                    {{ errors.max_marks }}
                  </p>
                </div>

                <div class="space-y-2">
                  <Label for="pass_marks">
                    Pass Marks <span class="text-red-500">*</span>
                  </Label>
                  <Input
                    id="pass_marks"
                    v-model.number="form.pass_marks"
                    type="number"
                    min="0"
                    step="0.01"
                    :class="{ 'border-red-500': errors.pass_marks }"
                  />
                  <p v-if="errors.pass_marks" class="text-sm text-red-600">
                    {{ errors.pass_marks }}
                  </p>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <Checkbox
                  id="is_optional"
                  v-model:checked="form.is_optional"
                />
                <Label for="is_optional" class="font-normal cursor-pointer">
                  This is an optional/elective subject
                </Label>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t">
              <Button
                type="button"
                variant="outline"
                @click="handleCancel"
                :disabled="form.processing"
              >
                <X class="mr-2 h-4 w-4" />
                Cancel
              </Button>
              <Button
                type="submit"
                :disabled="!canSubmit"
              >
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                <Save v-else class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Saving...' : 'Assign Subject' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
.border-red-500 {
  border-color: rgb(239 68 68) !important;
}
</style>