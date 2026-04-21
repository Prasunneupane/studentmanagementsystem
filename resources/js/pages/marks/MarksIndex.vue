<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import CustomSelect from '../CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import {
  ClipboardList, BookOpen, ChevronRight, Calendar, Users,
  FileText, CheckCircle2, AlertCircle, BarChart3
} from 'lucide-vue-next'

const { toast } = useToast()

interface Option { value: string; label: string }
interface Section { id: string; name: string }
interface ClassWithSections { id: string; name: string; sections: Section[] }

interface ExamOption {
  id: string
  name: string
  exam_type: string
  academic_year: string
  academic_year_id: string
  start_date: string
  end_date: string
}

interface SubjectSchedule {
  id: string
  subject_id: string
  subject_name: string
  subject_code: string
  exam_date: string
  max_theory_marks: number
  max_practical_marks: number
  max_total_marks: number
  pass_marks: number
}

interface Props {
  academicYears: Option[]
  currentAcademicYear: Option | null
  classes: ClassWithSections[]
  exams: ExamOption[]
  subjects: SubjectSchedule[]
  filters: {
    academic_year_id?: string
    exam_id?: string
    class_id?: string
    section_id?: string
  }
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Marks Management', href: '/marks' },
]

// ─── Selections ──────────────────────────────────────────────────
const selectedAcademicYear = ref(props.filters.academic_year_id || props.currentAcademicYear?.value || '')
const selectedExam = ref(props.filters.exam_id || '')
const selectedClass = ref(props.filters.class_id || '')
const selectedSection = ref(props.filters.section_id || '')

const examOptions = computed((): Option[] =>
  props.exams.map(e => ({ value: String(e.id), label: `${e.name} (${e.exam_type})` }))
)

const classOptions = computed((): Option[] =>
  props.classes.map(c => ({ value: String(c.id), label: c.name }))
)

const sectionOptions = computed((): Option[] => {
  const cls = props.classes.find(c => String(c.id) === selectedClass.value)
  return cls?.sections.map(s => ({ value: String(s.id), label: `Section ${s.name}` })) || []
})

// ─── Reload when filters change ──────────────────────────────────
const reloadSubjects = () => {
  if (selectedExam.value && selectedClass.value && selectedSection.value) {
    router.get('/marks', {
      academic_year_id: selectedAcademicYear.value,
      exam_id: selectedExam.value,
      class_id: selectedClass.value,
      section_id: selectedSection.value,
    }, { preserveState: true, preserveScroll: true })
  }
}

// Clear dependent fields when parent changes
watch(selectedAcademicYear, () => {
  selectedExam.value = ''
  selectedClass.value = ''
  selectedSection.value = ''
  router.get('/marks', {
    academic_year_id: selectedAcademicYear.value,
  }, { preserveState: true, preserveScroll: true })
})

watch(selectedExam, () => {
  selectedClass.value = ''
  selectedSection.value = ''
})

watch(selectedClass, () => {
  selectedSection.value = ''
})

watch(selectedSection, () => {
  if (selectedSection.value) reloadSubjects()
})

// ─── Navigate to marks entry ─────────────────────────────────────
const goToEnterMarks = (subjectId: string) => {
  router.get(`/marks/${selectedExam.value}/enter`, {
    class_id: selectedClass.value,
    section_id: selectedSection.value,
    subject_id: subjectId,
  })
}

const goToResults = () => {
  router.get(`/marks/${selectedExam.value}/results`, {
    class_id: selectedClass.value,
    section_id: selectedSection.value,
  })
}

const selectedExamData = computed(() => props.exams.find(e => String(e.id) === selectedExam.value))
</script>

<template>
  <Head title="Marks Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">

      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-primary/10 rounded-xl">
            <ClipboardList class="w-6 h-6 text-primary" />
          </div>
          <div>
            <h1 class="text-2xl font-bold">Marks Management</h1>
            <p class="text-sm text-muted-foreground">Enter marks, view marksheets, and manage results</p>
          </div>
        </div>
        <Button v-if="selectedExam && selectedClass && selectedSection"
          @click="goToResults" variant="outline">
          <BarChart3 class="mr-2 h-4 w-4" />
          View Results
        </Button>
      </div>

      <!-- Filter Card -->
      <Card class="shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg">
              <BookOpen class="w-5 h-5 text-primary" />
            </div>
            <div>
              <CardTitle class="text-lg font-bold">Select Exam & Class</CardTitle>
              <p class="text-sm text-muted-foreground mt-0.5">Choose the exam, class, and section to enter marks</p>
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-2">
              <Label>Academic Year</Label>
              <CustomSelect v-model="selectedAcademicYear" :options="academicYears"
                placeholder="Select Year" />
            </div>

            <div class="space-y-2">
              <Label>Exam</Label>
              <CustomSelect v-model="selectedExam" :options="examOptions"
                placeholder="Select Exam" />
            </div>

            <div class="space-y-2">
              <Label>Class</Label>
              <CustomSelect v-model="selectedClass" :options="classOptions"
                placeholder="Select Class" />
            </div>

            <div class="space-y-2">
              <Label>Section</Label>
              <CustomSelect v-model="selectedSection" :options="sectionOptions"
                placeholder="Select Section" :disabled="!selectedClass" />
            </div>
          </div>

          <!-- Exam info badge -->
          <div v-if="selectedExamData" class="mt-4 flex items-center gap-4 p-3 bg-muted/50 rounded-lg border">
            <Calendar class="w-4 h-4 text-muted-foreground" />
            <span class="text-sm text-muted-foreground">
              {{ selectedExamData.start_date }} → {{ selectedExamData.end_date }}
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary font-medium capitalize">
              {{ selectedExamData.exam_type?.replace('_', ' ') }}
            </span>
          </div>
        </CardContent>
      </Card>

      <!-- Subjects List -->
      <Card v-if="subjects.length > 0" class="shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-primary/10 rounded-lg">
                <FileText class="w-5 h-5 text-primary" />
              </div>
              <div>
                <CardTitle class="text-lg font-bold">Subjects</CardTitle>
                <p class="text-sm text-muted-foreground mt-0.5">
                  {{ subjects.length }} subject{{ subjects.length !== 1 ? 's' : '' }} scheduled
                </p>
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-0 p-0">
          <div class="divide-y">
            <div v-for="subject in subjects" :key="subject.id"
              class="flex items-center justify-between px-6 py-4 hover:bg-muted/30 transition-colors">
              <div class="flex items-center gap-4">
                <div class="p-2 bg-muted rounded-lg">
                  <BookOpen class="w-4 h-4 text-muted-foreground" />
                </div>
                <div>
                  <p class="font-semibold">{{ subject.subject_name }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ subject.subject_code }} · Exam Date: {{ subject.exam_date }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-4">
                <div class="text-right text-xs text-muted-foreground">
                  <p>Theory: {{ subject.max_theory_marks }} | Practical: {{ subject.max_practical_marks }}</p>
                  <p>Total: {{ subject.max_total_marks }} | Pass: {{ subject.pass_marks }}</p>
                </div>
                <Button size="sm" @click="goToEnterMarks(String(subject.subject_id))">
                  Enter Marks
                  <ChevronRight class="ml-1 h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Empty state -->
      <Card v-else-if="selectedExam && selectedClass && selectedSection" class="shadow-lg rounded-2xl">
        <CardContent class="py-12">
          <div class="flex flex-col items-center gap-3 text-center">
            <AlertCircle class="w-10 h-10 text-muted-foreground" />
            <p class="text-lg font-medium text-muted-foreground">No subjects scheduled</p>
            <p class="text-sm text-muted-foreground">
              No exam schedule found for this class-section combination.
              Please set up the schedule first.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Intro state -->
      <Card v-else class="shadow-lg rounded-2xl">
        <CardContent class="py-12">
          <div class="flex flex-col items-center gap-3 text-center">
            <div class="p-3 bg-primary/10 rounded-full">
              <ClipboardList class="w-8 h-8 text-primary" />
            </div>
            <p class="text-lg font-medium">Select an exam, class, and section above</p>
            <p class="text-sm text-muted-foreground max-w-md">
              Choose the academic year, exam, class, and section to view scheduled subjects and enter student marks.
            </p>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
