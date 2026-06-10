<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import CustomSelect from '../CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import { Input } from '@/components/ui/input'
import 'vue-sonner/style.css'
import {
  ChevronLeft, BarChart3, Calculator, Lock, Eye, Award,
  CheckCircle2, XCircle, Loader2, AlertCircle, Trophy,
  ChevronRight, ChevronsLeft, ChevronsRight,
  CheckSquare, Square, FileDown, FileSpreadsheet,
} from 'lucide-vue-next'

import ExportPdf from '../export-modules/ExportPdf.vue'

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
// const props = defineProps<Props>()
const searchQuery = ref('')
// ─── Pagination ───────────────────────────────────────────────────
const currentPage = ref(1)
const pageSize = ref(10)
const examDate = computed(() => {
  const dateStr = props.exam.end_date || props.exam.start_date
  return dateStr ? new Date(dateStr).toLocaleDateString() : ''
})
const examName = computed(() => props.exam.name)
const companyName = 'Greenwood Academy'

// ─── Filtered + paginated indices (we operate on original array indices) ──
const filteredIndices = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return props.results.map((_, i) => i)
  return props.results.reduce<number[]>((acc, r, i) => {
    if (
      r.student.first_name.toLowerCase().includes(q) ||
      r.student.last_name.toLowerCase().includes(q) ||
      r.student.middle_name?.toLowerCase().includes(q) ||
      r.grade.toLowerCase().includes(q) ||
      r.gpa.toString().includes(q) ||
      r.status.toLowerCase().includes(q)
      // r.student.grade.toLowerCase().includes(q) ||
      // r.student.gpa.toString().includes(q) ||
      // r.student.percentage.toString().includes(q)

    ) acc.push(i)
    return acc
  }, [])
})

const getStudentName = (student: ResultRow['student']) => {
  if (!student) return 'N/A'
  return student.middle_name
    ? `${student.first_name} ${student.middle_name} ${student.last_name}`
    : `${student.first_name} ${student.last_name}`
}

const tableDataForPDF = computed(() => {
  return props.results.map((s, i) => ({
    sn: i + 1,
    roll_no: s.student_id,
    name: getStudentName(s.student),
    marks: `${s.total_marks_obtained} / ${parseInt(s.total_max_marks.toString())}`,
    // practical: 0,
    grade: s.grade,
    gpa: s.gpa,
    status:
      s.status === 'pass'
        ? 'Pass'
        : s.status === 'fail'
          ? 'Fail'
          : 'Withheld',
  }))
})
console.log('Table Data:', tableDataForPDF)
const totalPages = computed(() => Math.max(1, Math.ceil(filteredIndices.value.length / pageSize.value)))

// Reset to page 1 on search or page size change
watch([searchQuery, pageSize], () => {
  console.log('Search or page size changed, resetting to page 1')
  currentPage.value = 1
})

const pagedIndices = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredIndices.value.slice(start, start + pageSize.value)
})

const pageFrom = computed(() => filteredIndices.value.length === 0 ? 0 : (currentPage.value - 1) * pageSize.value + 1)
const pageTo = computed(() => Math.min(currentPage.value * pageSize.value, filteredIndices.value.length))

// Page window for pagination buttons
const pageWindow = computed(() => {
  const total = totalPages.value
  const cur = currentPage.value
  const delta = 2
  const range: number[] = []
  for (let i = Math.max(1, cur - delta); i <= Math.min(total, cur + delta); i++) {
    range.push(i)
  }
  return range
})

const COMPANY_NAME = 'Greenwood Academy'
const PAGE_SIZE_OPTIONS = [10, 25, 50, 100]

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Marks Management', href: '/marks' },
  { title: props.exam.name, href: '#' },
  { title: 'Results', href: '#' },
]

const selectedClass = ref(props.filters.class_id || '')
const selectedSection = ref(props.filters.section_id || '')

const classOptions = computed((): Option[] => {
  return props.classes.map(ec => ({ value: ec.id, label: ec.name }))
})

const sectionOptions = computed((): Option[] => {
  const selected = props.classes.find(c => c.id === selectedClass.value)
  if (!selected || !selected.sections) return []
  return selected.sections.map(section => ({ value: section.id, label: section.name }))
})

const selectedSectionText = computed((): string => {
  const section = sectionOptions.value.find(s => s.value === selectedSection.value)
  return section?.label || ''
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

const headersForPDF = [
  { title: '#', key: 'sn', width: 10, align: 'center' },
  { title: 'Roll No', key: 'roll_no', width: 20 },
  { title: 'Name', key: 'name', width: 50 },
  { title: 'Marks', key: 'marks', align: 'center' },
  { title: 'GPA', key: 'gpa', align: 'center' },
  { title: 'Status', key: 'status', align: 'center' },

]

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
              <CustomSelect v-model="selectedClass" :options="classOptions" placeholder="Select Class" />
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
          <div class="ml-auto">
            <div class="flex items-center gap-2">
              <ExportPdf :company-name="companyName" :exam-name="examName" 
                :class-name="selectedClass" :section="selectedSection" :exam-date="examDate" filename="math_result" :headers="headersForPDF" :data="tableDataForPDF" />
              <Button variant="outline" size="sm" >
                <FileSpreadsheet class="h-4 w-4 mr-1.5" />
                Excel
              </Button>
              <!-- Search -->
              <div class=" relative">
                <Search class="" />
                <Input v-model="searchQuery" placeholder="Search name,grade,status..." class=" pl-8 h-8 text-xs w-52" />
              </div>
            </div>

          </div>
        </CardHeader>

        <CardContent class="pt-0 p-0">
          <div class="overflow-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-muted/60 border-b">
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-16">Rank</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground w-20">Student Name</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-28">Marks</th>
                  <!-- <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-24">Percentage</th> -->
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-20">Grade</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-20">GPA</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-24">Status</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground w-24">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(origIndex, rowPos) in pagedIndices" :key="results[origIndex].id"
                  class="border-b last:border-0 transition-colors hover:bg-muted/30" :class="[
                    rowPos % 2 === 0 ? 'bg-background' : 'bg-muted/20',
                    results[origIndex].status === 'fail' ? 'bg-red-50/30 dark:bg-red-950/10' : ''
                  ]">
                  <td class="px-3 py-2.5 text-center">
                    <div v-if="results[origIndex].rank" class="flex items-center justify-center">
                      <Trophy v-if="results[origIndex].rank <= 3" class="w-4 h-4 mr-1" :class="{
                        'text-yellow-500': results[origIndex].rank === 1,
                        'text-gray-400': results[origIndex].rank === 2,
                        'text-amber-700': results[origIndex].rank === 3,
                      }" />
                      <span class="font-bold">{{ results[origIndex].rank }}</span>
                    </div>
                    <span v-else class="text-muted-foreground">—</span>
                  </td>
                  <td class="px-4 py-1.5 font-medium">
                    {{ getStudentName(results[origIndex].student) }}
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span class="font-semibold">{{ results[origIndex].total_marks_obtained }}</span>
                    <span class="text-muted-foreground">/{{ parseInt(results[origIndex].total_max_marks) }}</span>
                  </td>
                  <!-- <td class="px-3 py-2.5 text-center font-semibold">
                    {{ results[origIndex].percentage }}%
                  </td> -->
                  <td class="px-3 py-2.5 text-center">
                    <span class="font-bold" :class="gradeColor(results[origIndex].grade)">{{ results[origIndex].grade
                      }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    {{ results[origIndex].gpa }}
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold capitalize"
                      :class="statusColor(results[origIndex].status)">
                      <CheckCircle2 v-if="results[origIndex].status === 'pass'" class="w-3 h-3" />
                      <XCircle v-else class="w-3 h-3" />
                      {{ results[origIndex].status }}
                    </span>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <Button variant="ghost" size="sm" @click="goToMarksheet(results[origIndex].student_id)"
                      class="h-7 text-xs">
                      <Eye class="mr-1 h-3.5 w-3.5" /> View
                    </Button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination Starts Here -->
          <div v-if="filteredIndices.length > 0"
            class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t bg-muted/20">
            <!-- Left: rows info + page size -->
            <div class="flex items-center gap-3 text-sm text-muted-foreground">
              <span>
                Showing {{ pageFrom }}–{{ pageTo }} of {{ filteredIndices.length }}
                <template v-if="searchQuery"> (filtered from {{ results.length }})</template>
              </span>
              <div class="flex items-center gap-1.5">
                <Label class="text-xs">Rows:</Label>
                <select v-model.number="pageSize"
                  class="h-7 text-xs rounded-md border border-input bg-background px-2 focus:outline-none focus:ring-1 focus:ring-ring">
                  <option v-for="sz in PAGE_SIZE_OPTIONS" :key="sz" :value="sz">{{ sz }}</option>
                </select>
              </div>
            </div>

            <!-- Right: page buttons -->
            <div class="flex items-center gap-1">
              <!-- First -->
              <Button variant="outline" size="icon" class="h-7 w-7" :disabled="currentPage === 1"
                @click="currentPage = 1">
                <ChevronsLeft class="h-3.5 w-3.5" />
              </Button>
              <!-- Prev -->
              <Button variant="outline" size="icon" class="h-7 w-7" :disabled="currentPage === 1"
                @click="currentPage--">
                <ChevronLeft class="h-3.5 w-3.5" />
              </Button>

              <!-- Page numbers -->
              <template v-if="pageWindow[0] > 1">
                <Button variant="ghost" size="icon" class="h-7 w-7 text-xs" @click="currentPage = 1">1</Button>
                <span v-if="pageWindow[0] > 2" class="text-muted-foreground text-xs px-1">…</span>
              </template>

              <Button v-for="p in pageWindow" :key="p" :variant="p === currentPage ? 'default' : 'ghost'" size="icon"
                class="h-7 w-7 text-xs" @click="currentPage = p">
                {{ p }}
              </Button>

              <template v-if="pageWindow[pageWindow.length - 1] < totalPages">
                <span v-if="pageWindow[pageWindow.length - 1] < totalPages - 1"
                  class="text-muted-foreground text-xs px-1">…</span>
                <Button variant="ghost" size="icon" class="h-7 w-7 text-xs" @click="currentPage = totalPages">
                  {{ totalPages }}
                </Button>
              </template>

              <!-- Next -->
              <Button variant="outline" size="icon" class="h-7 w-7" :disabled="currentPage === totalPages"
                @click="currentPage++">
                <ChevronRight class="h-3.5 w-3.5" />
              </Button>
              <!-- Last -->
              <Button variant="outline" size="icon" class="h-7 w-7" :disabled="currentPage === totalPages"
                @click="currentPage = totalPages">
                <ChevronsRight class="h-3.5 w-3.5" />
              </Button>
            </div>
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
