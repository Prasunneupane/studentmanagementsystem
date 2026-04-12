<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'
import CustomSelect from '../CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import { Loader2, Save, X, ChevronRight, ChevronLeft, BookOpen, Calendar, Users, CheckCircle2 } from 'lucide-vue-next'

const { toast } = useToast()

interface Option { value: string; label: string }
interface Section { id: string; name: string }
interface ClassWithSections { id: string; name: string; sections: Section[] }

interface Props {
  classes: ClassWithSections[]
  academicYears: Option[]
  terms: Option[]
  currentAcademicYear: Option | null
}

const props = defineProps<Props>()
const formatDate = (val: any): string => {
  if (!val) return ''
  const d = val instanceof Date ? val : new Date(val)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const breadcrumbs = [
  { title: 'Exams', href: '/exams' },
  { title: 'Create Exam', href: '/exams/create' }
]

// ─── Step Management ─────────────────────────────────────────────
const currentStep = ref(1)
const totalSteps = 2

const steps = [
  { number: 1, title: 'Exam Details', icon: BookOpen },
  { number: 2, title: 'Classes & Sections', icon: Users },
]

// ─── Step 1: Exam Form ────────────────────────────────────────────
const form = useForm({
  name: '',
  exam_type: '',
  academic_year_id: props.currentAcademicYear?.value || '',
  term_id: '',
  start_date: '',
  end_date: '',
  weightage: '100',
  is_published: false,
})

const examTypeOptions: Option[] = [
  { value: 'unit_test', label: 'Unit Test' },
  { value: 'midterm', label: 'Mid Term' },
  { value: 'final', label: 'Final' },
  { value: 'semester', label: 'Semester' },
  { value: 'annual', label: 'Annual' },
]

const step1Errors = computed(() => {
  const errs: Record<string, string> = {}
  if (!form.name.trim()) errs.name = 'Exam name is required'
  if (!form.exam_type) errs.exam_type = 'Exam type is required'
  if (!form.academic_year_id) errs.academic_year_id = 'Academic year is required'
  if (!form.start_date) errs.start_date = 'Start date is required'
  if (!form.end_date) errs.end_date = 'End date is required'
  if (form.start_date && form.end_date && form.start_date > form.end_date)
    errs.end_date = 'End date must be after start date'
  return errs
})

// ─── Step 2: Class/Section Selection ─────────────────────────────
interface ClassSelection { all: boolean; sections: Set<string> }
const classSelections = ref<Record<string, ClassSelection>>({})

// Initialize
props.classes.forEach(cls => {
  classSelections.value[cls.id] = { all: false, sections: new Set() }
})

const isClassChecked = (classId: string) => classSelections.value[classId]?.all ?? false
const isClassIndeterminate = (classId: string) => {
  const sel = classSelections.value[classId]
  if (!sel) return false
  return !sel.all && sel.sections.size > 0
}
const isSectionChecked = (classId: string, sectionId: string) => {
  const sel = classSelections.value[classId]
  return sel?.all || sel?.sections.has(sectionId) || false
}

// FIX: Force reactivity by spreading the object after mutations
const forceUpdate = () => {
  classSelections.value = { ...classSelections.value }
}

const toggleClass = (classId: string) => {
  const sel = classSelections.value[classId]
  const cls = props.classes.find(c => c.id === classId)
  if (!sel || !cls) return

  if (sel.all || sel.sections.size > 0) {
    sel.all = false
    sel.sections.clear()
  } else {
    sel.all = true
    sel.sections.clear()
  }
  forceUpdate()
}

const toggleSection = (classId: string, sectionId: string) => {
  const sel = classSelections.value[classId]
  const cls = props.classes.find(c => c.id === classId)
  if (!sel || !cls) return

  if (sel.all) {
    sel.all = false
    cls.sections.forEach(s => { if (s.id !== sectionId) sel.sections.add(s.id) })
  } else {
    if (sel.sections.has(sectionId)) {
      sel.sections.delete(sectionId)
    } else {
      sel.sections.add(sectionId)
      if (sel.sections.size === cls.sections.length) {
        sel.all = true
        sel.sections.clear()
      }
    }
  }
  forceUpdate()
}

// FIX: selectAllClasses now also calls forceUpdate
const selectAllClasses = () => {
  const allSelected = props.classes.every(cls => classSelections.value[cls.id]?.all)
  props.classes.forEach(cls => {
    classSelections.value[cls.id] = { all: !allSelected, sections: new Set() }
  })
  forceUpdate()
}

const allClassesSelected = computed(() =>
  props.classes.length > 0 && props.classes.every(cls => classSelections.value[cls.id]?.all)
)
const someClassesSelected = computed(() =>
  props.classes.some(cls => {
    const sel = classSelections.value[cls.id]
    return sel?.all || sel?.sections.size > 0
  })
)

// FIX: Always expand "all" into individual section_ids — never send null
const buildClassPayload = () => {
  const result: Array<{ class_id: string; section_id: string }> = []
  props.classes.forEach(cls => {
    const sel = classSelections.value[cls.id]
    if (!sel) return
    if (sel.all) {
      // Expand to individual section IDs
      cls.sections.forEach(section => {
        result.push({ class_id: cls.id, section_id: section.id })
      })
    } else {
      sel.sections.forEach(sId => {
        result.push({ class_id: cls.id, section_id: sId })
      })
    }
  })
  return result
}

const step2Valid = computed(() => buildClassPayload().length > 0)

const totalSelectedCount = computed(() => {
  let count = 0
  props.classes.forEach(cls => {
    const sel = classSelections.value[cls.id]
    if (sel?.all) count += cls.sections.length
    else count += sel?.sections.size || 0
  })
  return count
})

// ─── Navigation ────────────────────────────────────────────────
const goToStep2 = () => {
  if (Object.keys(step1Errors.value).length > 0) {
    toast.error('Please fill all required fields')
    return
  }
  currentStep.value = 2
}

const goBack = () => { currentStep.value = 1 }

// ─── Submit ────────────────────────────────────────────────────
const submitting = ref(false)

const handleSubmit = () => {
  if (!step2Valid.value) {
    toast.error('Please select at least one class or section')
    return
  }

  form.transform((data) => ({
    ...data,
    is_published: !!data.is_published,
    exam_classes: buildClassPayload(),
  })).post('/exams/store', {
    onSuccess: (page: any) => {
      const examId = page.props?.exam?.id
      toast.success('Exam created! Now set the schedule.')
      if (examId) router.visit(`/exams/${examId}/schedule`)
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0]
      toast.error(firstError as string)
      currentStep.value = 1
    },
  })
}
</script>

<template>

  <Head title="Create Exam" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">

      <!-- Step Indicator -->
      <div class="flex items-center gap-0">
        <template v-for="(step, i) in steps" :key="step.number">
          <div class="flex items-center gap-2">
            <div
              class="flex items-center justify-center w-9 h-9 rounded-full border-2 font-semibold text-sm transition-all duration-300"
              :class="{
                'bg-primary border-primary text-primary-foreground': currentStep === step.number,
                'bg-green-500 border-green-500 text-white': currentStep > step.number,
                'bg-muted border-border text-muted-foreground': currentStep < step.number,
              }">
              <CheckCircle2 v-if="currentStep > step.number" class="w-5 h-5" />
              <span v-else>{{ step.number }}</span>
            </div>
            <span class="text-sm font-medium hidden sm:block"
              :class="currentStep === step.number ? 'text-primary' : 'text-muted-foreground'">
              {{ step.title }}
            </span>
          </div>
          <div v-if="i < steps.length - 1" class="flex-1 h-0.5 mx-3 transition-all duration-300"
            :class="currentStep > step.number ? 'bg-green-500' : 'bg-border'" />
        </template>
      </div>

      <!-- ══════════════════════════════════════════ -->
      <!-- STEP 1: EXAM DETAILS                      -->
      <!-- ══════════════════════════════════════════ -->
      <Card v-if="currentStep === 1" class="w-full shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg">
              <BookOpen class="w-5 h-5 text-primary" />
            </div>
            <div>
              <CardTitle class="text-xl font-bold">Exam Details</CardTitle>
              <p class="text-sm text-muted-foreground mt-0.5">Enter basic exam information</p>
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-6">
          <div class="space-y-6">

            <!-- Exam Name + Type -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="name">Exam Name <span class="text-red-500">*</span></Label>
                <Input id="name" v-model="form.name" placeholder="e.g. First Terminal Exam"
                  :class="{ 'border-red-500': step1Errors.name && form.name !== '' }" />
                <p v-if="step1Errors.name && form.name !== ''" class="text-sm text-red-600">{{ step1Errors.name }}</p>
              </div>

              <div class="space-y-2">
                <Label for="exam_type">Exam Type <span class="text-red-500">*</span></Label>
                <CustomSelect id="exam_type" v-model="form.exam_type" :options="examTypeOptions"
                  placeholder="Select Exam Type" :class="{ 'border-red-500': step1Errors.exam_type }" />
                <p v-if="step1Errors.exam_type" class="text-sm text-red-600">{{ step1Errors.exam_type }}</p>
              </div>
            </div>

            <!-- Academic Year + Term -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="academic_year_id">Academic Year <span class="text-red-500">*</span></Label>
                <CustomSelect id="academic_year_id" v-model="form.academic_year_id" :options="academicYears"
                  placeholder="Select Academic Year" :class="{ 'border-red-500': step1Errors.academic_year_id }" />
                <p v-if="step1Errors.academic_year_id" class="text-sm text-red-600">{{ step1Errors.academic_year_id }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="term_id">Term <span class="text-muted-foreground text-xs">(optional)</span></Label>
                <CustomSelect id="term_id" v-model="form.term_id" :options="terms" placeholder="Select Term" />
              </div>
            </div>

            <!-- Dates + Weightage -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="space-y-2">
                <Label>Start Date <span class="text-red-500">*</span></Label>
                <DatePicker :model-value="form.start_date" placeholder="Start Date" :error="form.errors.start_date"
                  @update:model-value="(val) => { form.start_date = formatDate(val) }" />
                <p v-if="step1Errors.start_date" class="text-sm text-red-600">{{ step1Errors.start_date }}</p>
              </div>

              <div class="space-y-2">
                <Label for="description">End Date <span class="text-red-500">*</span></Label>
                <DatePicker :model-value="form.end_date" placeholder="End Date" :error="form.errors.end_date"
                  @update:model-value="(val) => form.end_date = formatDate(val)" />
                <p v-if="step1Errors.end_date" class="text-sm text-red-600">{{ step1Errors.end_date }}</p>
              </div>

              <div class="space-y-2">
                <Label for="weightage">Weightage (%) <span
                    class="text-muted-foreground text-xs">(optional)</span></Label>
                <Input id="weightage" type="number" v-model="form.weightage" placeholder="100" min="0" max="100" />
              </div>
            </div>

            <!-- Publish toggle -->
            <div class="p-4 bg-muted/50 rounded-lg">
              <div class="flex items-start space-x-3">
                <Checkbox id="is_published" v-model="form.is_published" />
                <div class="grid gap-1 leading-none">
                  <Label for="is_published" class="font-medium cursor-pointer">Publish Immediately</Label>
                  <p class="text-sm text-muted-foreground">Make this exam visible to teachers and students right away
                  </p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t">
              <Button type="button" class="cursor-pointer" variant="outline" @click="router.visit('/exams')">
                <X class="mr-2 h-4 w-4" /> Cancel
              </Button>
              <Button type="button" class="cursor-pointer" @click="goToStep2" :disabled="Object.keys(step1Errors).length > 0">
                Next: Select Classes
                <ChevronRight class="ml-2 h-4 w-4" />
              </Button>
            </div>

          </div>
        </CardContent>
      </Card>

      <!-- ══════════════════════════════════════════ -->
      <!-- STEP 2: CLASS & SECTION SELECTION         -->
      <!-- ══════════════════════════════════════════ -->
      <Card v-if="currentStep === 2" class="w-full shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-primary/10 rounded-lg">
                <Users class="w-5 h-5 text-primary" />
              </div>
              <div>
                <CardTitle class="text-xl font-bold">Select Classes & Sections</CardTitle>
                <p class="text-sm text-muted-foreground mt-0.5">
                  Choose which classes this exam applies to
                </p>
              </div>
            </div>
            <!-- Selection count badge -->
            <div v-if="totalSelectedCount > 0"
              class="px-3 py-1.5 bg-primary/10 text-primary rounded-full text-sm font-semibold">
              {{ totalSelectedCount }} section{{ totalSelectedCount !== 1 ? 's' : '' }} selected
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-6">
          <div class="space-y-4">

            <!-- Select All -->
            <div class="flex items-center gap-3 p-3 bg-muted/50 rounded-lg border">
              <Checkbox
                id="select_all"
                :checked="allClassesSelected"
                :indeterminate="!allClassesSelected && someClassesSelected"
                @update:checked="selectAllClasses"
              />
              <Label for="select_all" class="font-semibold cursor-pointer text-sm" @click="selectAllClasses">
                Select All Classes & Sections
              </Label>
            </div>

            <!-- Class rows -->
            <div class="space-y-3">
              <div v-for="cls in classes" :key="cls.id"
                class="border rounded-xl overflow-hidden transition-all duration-200"
                :class="(classSelections[cls.id]?.all || classSelections[cls.id]?.sections.size > 0)
                  ? 'border-primary/40 bg-primary/5'
                  : 'border-border bg-card'">

                <!-- Class header row -->
                <div class="flex items-center gap-3 p-3 cursor-pointer hover:bg-muted/30 transition-colors"
                  @click="toggleClass(cls.id)">
                  <Checkbox
                    :id="`class_${cls.id}`"
                    :checked="isClassChecked(cls.id)"
                    :indeterminate="isClassIndeterminate(cls.id)"
                    @update:checked="toggleClass(cls.id)"
                    @click.stop
                  />
                  <Label :for="`class_${cls.id}`" class="font-semibold cursor-pointer flex-1 pointer-events-none">
                    {{ cls.name }}
                  </Label>
                  <span class="text-xs text-muted-foreground">
                    {{ cls.sections.length }} section{{ cls.sections.length !== 1 ? 's' : '' }}
                  </span>
                </div>

                <!-- FIX: Section pills — use light tinted bg so checkbox stays visible -->
                <div class="flex flex-wrap gap-2 px-10 pb-3">
                  <div
                    v-for="section in cls.sections"
                    :key="section.id"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg border cursor-pointer transition-all duration-150 text-sm select-none"
                    :class="isSectionChecked(cls.id, section.id)
                      ? 'bg-primary/10 text-primary border-primary font-semibold'
                      : 'bg-background border-border hover:border-primary/50 text-foreground'"
                    @click="toggleSection(cls.id, section.id)"
                  >
                    <Checkbox
                      :id="`section_${cls.id}_${section.id}`"
                      :checked="isSectionChecked(cls.id, section.id)"
                      @update:checked="toggleSection(cls.id, section.id)"
                      @click.stop
                      class="pointer-events-none"
                    />
                    <span class="font-medium">Section {{ section.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Validation message -->
            <p v-if="!step2Valid && totalSelectedCount === 0" class="text-sm text-amber-600 flex items-center gap-1.5">
              <span class="inline-block w-1.5 h-1.5 bg-amber-600 rounded-full" />
              Select at least one class or section to continue
            </p>

            <!-- Actions -->
            <div class="flex justify-between gap-3 pt-4 border-t">
              <Button type="button" variant="outline" @click="goBack">
                <ChevronLeft class="mr-2 h-4 w-4" /> Back
              </Button>
              <div class="flex gap-3">
                <Button type="button" variant="outline" @click="router.visit('/exams')">
                  <X class="mr-2 h-4 w-4" /> Cancel
                </Button>
                <Button type="button" @click="handleSubmit" :disabled="!step2Valid || form.processing">
                  <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                  <Save v-else class="mr-2 h-4 w-4" />
                  {{ form.processing ? 'Saving...' : 'Save & Set Schedule' }}
                </Button>
              </div>
            </div>

          </div>
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