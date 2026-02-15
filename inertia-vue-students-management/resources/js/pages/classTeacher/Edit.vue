<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import CustomSelect from '../CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import { Loader2, Save, X, UserCheck, AlertCircle } from 'lucide-vue-next'
// import { Alert, AlertDescription } from '@/components/ui/alert'
import axios from 'axios'

const { toast } = useToast()

interface Option {
  value: string
  label: string
}

interface ClassTeacherData {
  id: number
  class_id: string
  section_id: string
  teacher_id: string
  academic_year_id: string
  is_class_teacher: boolean
  is_active: boolean
}

interface Props {
  classTeacher: ClassTeacherData
  classes: Option[]
  sections: Option[]
  teachers: Option[]
  academicYears: Option[]
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Class Teachers', href: '/class-teachers' },
  { title: 'Edit Assignment', href: `/class-teachers/${props.classTeacher.id}/edit` }
]

const loadingSections = ref(false)
const sectionsOptions = ref<Option[]>(props.sections)

const form = useForm({
  class_id: props.classTeacher.class_id ?? '',
  section_id: props.classTeacher.section_id ?? '',
  teacher_id: props.classTeacher.teacher_id ?? '',
  academic_year_id: props.classTeacher.academic_year_id ?? '',
  is_class_teacher: props.classTeacher.is_class_teacher,
  is_active: props.classTeacher.is_active,
})

// Fetch sections when class changes
watch(() => form.class_id, async (newClass, oldClass) => {
  if (oldClass === null) return
  
  form.section_id = ''
  sectionsOptions.value = []
  
  if (!newClass) return
  
  loadingSections.value = true
  try {
    const response = await axios.get('/class-teachers/sections-by-class', {
      params: { class_id: newClass }
    })
    sectionsOptions.value = response.data
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
  if (!form.teacher_id) errs.teacher_id = 'Teacher is required'
  if (!form.academic_year_id) errs.academic_year_id = 'Academic year is required'
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
    class_id: data.class_id,
    section_id: data.section_id,
    teacher_id: data.teacher_id,
    academic_year_id: data.academic_year_id,
    is_class_teacher: data.is_class_teacher,
    is_active: data.is_active,
    _method: 'PUT',
  })).post(`/class-teacher/${props.classTeacher.id}`, {
    onSuccess: () => {
      toast.success('Assignment updated successfully')
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      toast.error(firstError as string)
    },
  })
}

const handleCancel = () => {
  router.visit('/class-teacher')
}
</script>

<template>
  <Head title="Edit Teacher Assignment" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />
    
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <CardTitle class="text-2xl font-bold">Edit Teacher Assignment</CardTitle>
          <p class="text-sm text-muted-foreground mt-1">
            Update teacher assignment configuration
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
                  :options="sectionsOptions"
                  :disabled="!form.class_id || loadingSections"
                  :placeholder="loadingSections ? 'Loading...' : 'Select Section'"
                  :class="{ 'border-red-500': errors.section_id }"
                />
                <p v-if="errors.section_id" class="text-sm text-red-600">
                  {{ errors.section_id }}
                </p>
              </div>
            </div>

            <!-- Teacher -->
            <div class="space-y-2">
              <Label for="teacher_id">
                Teacher <span class="text-red-500">*</span>
              </Label>
              <CustomSelect
                id="teacher_id"
                v-model="form.teacher_id"
                :options="teachers"
                placeholder="Select Teacher"
                :class="{ 'border-red-500': errors.teacher_id }"
              />
              <p v-if="errors.teacher_id" class="text-sm text-red-600">
                {{ errors.teacher_id }}
              </p>
            </div>

            <!-- Configuration -->
            <div class="space-y-4 p-4 bg-muted/50 rounded-lg">
              <h3 class="font-semibold text-sm">Assignment Configuration</h3>
              
              <div class="space-y-3">
                <div class="flex items-start space-x-3">
                  <Checkbox
                    id="is_class_teacher"
                    v-model="form.is_class_teacher"
                  />
                  <div class="grid gap-1.5 leading-none">
                    <Label for="is_class_teacher" class="font-medium cursor-pointer flex items-center gap-2">
                      <UserCheck class="h-4 w-4" />
                      Class Teacher
                    </Label>
                    <p class="text-sm text-muted-foreground">
                      Mark this teacher as the primary class teacher for this section
                    </p>
                  </div>
                </div>

                <Alert v-if="form.is_class_teacher" variant="default" class="border-blue-200 bg-blue-50">
                  <AlertCircle class="h-4 w-4 text-blue-600" />
                  <AlertDescription class="text-blue-800">
                    Marking as class teacher will automatically unset any existing class teacher for this section.
                  </AlertDescription>
                </Alert>

                <div class="flex items-start space-x-3">
                  <Checkbox
                    id="is_active"
                    v-model="form.is_active"
                  />
                  <div class="grid gap-1.5 leading-none">
                    <Label for="is_active" class="font-medium cursor-pointer">
                      Active Status
                    </Label>
                    <p class="text-sm text-muted-foreground">
                      Set whether this assignment is currently active
                    </p>
                  </div>
                </div>
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
                {{ form.processing ? 'Updating...' : 'Update Assignment' }}
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