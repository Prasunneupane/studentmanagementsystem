<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import CustomSelect from '../CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import {
  ChevronLeft, BarChart3, Calculator, Lock, Eye, Award,
  CheckCircle2, XCircle, Loader2, AlertCircle, Trophy
} from 'lucide-vue-next'

const { toast } = useToast()

interface Option { value: string; label: string }
interface Section { id: string; name: string }
interface ClassWithSections { id: string; name: string; sections: Section[] }

interface ExamClassEntry {
  class_id: string
  class_name: string
  section_id: string
  section_name: string
}

interface ResultRow {
  id: number
  student_id: number
  class_id: number
  section_id: number | null
  total_marks_obtained: number
  total_max_marks: number
  percentage: number
  grade: string
  gpa: number
  rank: number | null
  status: 'pass' | 'fail' | 'withheld'
  is_finalized: boolean
  student: {
    id: number
    first_name: string
    middle_name?: string
    last_name: string
  }
}

interface Props {
  exam: {
    id: string
    name: string
    exam_type: string
    start_date: string
    end_date: string
    academic_year_id: string
  }
  examClasses: ExamClassEntry[]
  classes: ClassWithSections[]
  results: ResultRow[]
  filters: {
    class_id?: string
    section_id?: string
  }
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Marks Management', href: '/marks' },
  { title: props.exam.name, href: '#' },
  { title: 'Results', href: '#' },
]

const selectedClass = ref(props.filters.class_id || '')
const selectedSection = ref(props.filters.section_id || '')

// Build unique class options from examClasses
const classOptions = computed((): Option[] => {
  const seen = new Set<string>()
  return props.examClasses
    .filter(ec => {
      if (seen.has(ec.class_id)) return false
      seen.add(ec.class_id)
      return true
    })
    .map(ec => ({ value: ec.class_id, label: ec.class_name }))
})

const sectionOptions = computed((): Option[] => {
  return props.examClasses
    .filter(ec => ec.class_id === selectedClass.value)
    .map(ec => ({ value: ec.section_id, label: `Section ${ec.section_name}` }))
})

// Reload when selection changes
const loadResults = () => {
  if (selectedClass.value && selectedSection.value) {
    router.get(`/marks/${props.exam.id}/results`, {
      class_id: selectedClass.value,
      section_id: selectedSection.value,
    }, { preserveState: true, preserveScroll: true })
  }
}

const getStudentName = (student: ResultRow['student']) => {
  if (!student) return 'N/A'
  return student.middle_name
    ? `${student.first_name} ${student.middle_name} ${student.last_name}`
    : `${student.first_name} ${student.last_name}`
}

// ─── Actions ─────────────────────────────────────────────────────
const calculating = ref(false)
const finalizing = ref(false)

const handleCalculate = () => {
  calculating.value = true
  useForm({
    class_id: selectedClass.value,
    section_id: selectedSection.value,
  }).post(`/marks/${props.exam.id}/calculate`, {
    onSuccess: () => {
      toast.success('Results calculated successfully!')
      loadResults()
    },
    onError: (errors) => {
      toast.error(Object.values(errors)[0] as string)
    },
    onFinish: () => { calculating.value = false },
  })
}

const handleFinalize = () => {
  if (!confirm('Are you sure you want to finalize these results? This action will lock the results from further editing.')) {
    return
  }

  finalizing.value = true
  useForm({
    class_id: selectedClass.value,
    section_id: selectedSection.value,
  }).post(`/marks/${props.exam.id}/finalize`, {
    onSuccess: () => {
      toast.success('Results finalized!')
      loadResults()
    },
    onError: (errors) => {
      toast.error(Object.values(errors)[0] as string)
    },
    onFinish: () => { finalizing.value = false },
  })
}

const goToMarksheet = (studentId: number) => {
  router.get(`/marks/${props.exam.id}/marksheet/${studentId}`)
}

// ─── Stats ───────────────────────────────────────────────────────
const stats = computed(() => {
  const total = props.results.length
  const passed = props.results.filter(r => r.status === 'pass').length
  const failed = props.results.filter(r => r.status === 'fail').length
  const finalized = props.results.every(r => r.is_finalized)
  const avgPercentage = total > 0 ? (props.results.reduce((s, r) => s + Number(r.percentage), 0) / total).toFixed(1) : '0'
  const highestMarks = total > 0 ? Math.max(...props.results.map(r => Number(r.total_marks_obtained))) : 0

  return { total, passed, failed, finalized, avgPercentage, highestMarks }
})

const statusColor = (status: string) => {
  if (status === 'pass') return 'text-green-600 bg-green-100 dark:bg-green-900/30'
  if (status === 'fail') return 'text-red-600 bg-red-100 dark:bg-red-900/30'
  return 'text-amber-600 bg-amber-100 dark:bg-amber-900/30'
}

const gradeColor = (grade: string) => {
  if (['A+', 'A'].includes(grade)) return 'text-green-600'
  if (['B+', 'B'].includes(grade)) return 'text-blue-600'
  if (['C+', 'C'].includes(grade)) return 'text-amber-600'
  if (['D+', 'D'].includes(grade)) return 'text-orange-600'
  return 'text-red-600'
}
</script>

<template>
  <Head :title="`Results - ${exam.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
          <Button variant="ghost" size="sm" @click="router.visit('/marks')">
            <ChevronLeft class="h-4 w-4" />
          </Button>
          <div>
            <h1 class="text-xl font-bold">{{ exam.name }} — Results</h1>
            <p class="text-sm text-muted-foreground capitalize">{{ exam.exam_type?.replace('_', ' ') }}</p>
          </div>
        </div>

        <!-- Action buttons -->
        <div v-if="results.length > 0" class="flex gap-2">
          <Button variant="outline" size="sm" @click="handleCalculate" :disabled="calculating">
            <Loader2 v-if="calculating" class="mr-2 h-3.5 w-3.5 animate-spin" />
            <Calculator v-else class="mr-2 h-3.5 w-3.5" />
            {{ calculating ? 'Calculating...' : 'Calculate Results' }}
          </Button>
          <Button size="sm" @click="handleFinalize" :disabled="finalizing || stats.finalized"
            :variant="stats.finalized ? 'secondary' : 'default'">
            <Loader2 v-if="finalizing" class="mr-2 h-3.5 w-3.5 animate-spin" />
            <Lock v-else class="mr-2 h-3.5 w-3.5" />
            {{ stats.finalized ? 'Finalized ✓' : (finalizing ? 'Finalizing...' : 'Finalize Results') }}
          </Button>
        </div>
      </div>

      <!-- Filter -->
      <Card class="shadow rounded-xl">
        <CardContent class="py-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div class="space-y-2">
              <Label>Class</Label>
              <CustomSelect v-model="selectedClass" :options="classOptions" placeholder="Select Class"
                @update:model-value="() => { selectedSection = ''; }" />
            </div>
            <div class="space-y-2">
              <Label>Section</Label>
              <CustomSelect v-model="selectedSection" :options="sectionOptions" placeholder="Select Section"
                :disabled="!selectedClass" />
            </div>
            <div>
              <Button @click="loadResults" :disabled="!selectedClass || !selectedSection" class="w-full">
                <BarChart3 class="mr-2 h-4 w-4" />
                Load Results
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Stats Cards -->
      <div v-if="results.length > 0" class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="p-4 bg-card border rounded-xl text-center">
          <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Total Students</p>
          <p class="text-2xl font-bold">{{ stats.total }}</p>
        </div>
        <div class="p-4 bg-card border rounded-xl text-center">
          <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Passed</p>
          <p class="text-2xl font-bold text-green-600">{{ stats.passed }}</p>
        </div>
        <div class="p-4 bg-card border rounded-xl text-center">
          <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Failed</p>
          <p class="text-2xl font-bold text-red-600">{{ stats.failed }}</p>
        </div>
        <div class="p-4 bg-card border rounded-xl text-center">
          <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Avg %</p>
          <p class="text-2xl font-bold">{{ stats.avgPercentage }}%</p>
        </div>
        <div class="p-4 bg-card border rounded-xl text-center">
          <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Highest</p>
          <p class="text-2xl font-bold text-primary">{{ stats.highestMarks }}</p>
        </div>
      </div>

      <!-- Results Table -->
      <Card v-if="results.length > 0" class="shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 rounded-lg">
              <Award class="w-5 h-5 text-primary" />
            </div>
            <CardTitle class="text-lg font-bold">Student Results</CardTitle>
            <span v-if="stats.finalized"
              class="ml-2 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700 rounded-full dark:bg-green-900/30">
              ✓ Finalized
            </span>
            <span v-else
              class="ml-2 px-2 py-0.5 text-xs font-semibold bg-amber-100 text-amber-700 rounded-full dark:bg-amber-900/30">
              Draft
            </span>
          </div>
        </CardHeader>

        <CardContent class="pt-0 p-0">
          <div class="overflow-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-muted/60 border-b">
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-16">Rank</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground min-w-[200px]">Student Name</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-28">Marks</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-24">Percentage</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-20">Grade</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-20">GPA</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-24">Status</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-24">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(result, i) in results" :key="result.id"
                  class="border-b last:border-0 transition-colors hover:bg-muted/30"
                  :class="[
                    i % 2 === 0 ? 'bg-background' : 'bg-muted/20',
                    result.status === 'fail' ? 'bg-red-50/30 dark:bg-red-950/10' : ''
                  ]">
                  <td class="px-3 py-2.5 text-center">
                    <div v-if="result.rank" class="flex items-center justify-center">
                      <Trophy v-if="result.rank <= 3"
                        class="w-4 h-4 mr-1"
                        :class="{
                          'text-yellow-500': result.rank === 1,
                          'text-gray-400': result.rank === 2,
                          'text-amber-700': result.rank === 3,
                        }" />
                      <span class="font-bold">{{ result.rank }}</span>
                    </div>
                    <span v-else class="text-muted-foreground">—</span>
                  </td>
                  <td class="px-4 py-2.5 font-medium">
                    {{ getStudentName(result.student) }}
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span class="font-semibold">{{ result.total_marks_obtained }}</span>
                    <span class="text-muted-foreground">/{{ result.total_max_marks }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-center font-semibold">
                    {{ result.percentage }}%
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span class="font-bold" :class="gradeColor(result.grade)">{{ result.grade }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    {{ result.gpa }}
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold capitalize"
                      :class="statusColor(result.status)">
                      <CheckCircle2 v-if="result.status === 'pass'" class="w-3 h-3" />
                      <XCircle v-else class="w-3 h-3" />
                      {{ result.status }}
                    </span>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <Button variant="ghost" size="sm" @click="goToMarksheet(result.student_id)" class="h-7 text-xs">
                      <Eye class="mr-1 h-3.5 w-3.5" /> View
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Empty state when class selected but no results -->
      <Card v-else-if="selectedClass && selectedSection" class="shadow-lg rounded-2xl">
        <CardContent class="py-12">
          <div class="flex flex-col items-center gap-3 text-center">
            <AlertCircle class="w-10 h-10 text-muted-foreground" />
            <p class="text-lg font-medium text-muted-foreground">No results found</p>
            <p class="text-sm text-muted-foreground max-w-md">
              Results haven't been calculated yet. Enter marks for all subjects first, then click "Calculate Results".
            </p>
            <Button variant="outline" @click="handleCalculate" :disabled="calculating" class="mt-2">
              <Calculator class="mr-2 h-4 w-4" />
              Calculate Results Now
            </Button>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
