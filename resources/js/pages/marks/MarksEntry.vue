<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Label } from '@/components/ui/label'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import {
  Loader2, Save, ChevronLeft, BookOpen, Calendar,
  AlertCircle, UserX, Users, Search, FileDown,
  FileSpreadsheet, ChevronRight, ChevronsLeft, ChevronsRight,
  CheckSquare
} from 'lucide-vue-next';
// import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

// ─── jsPDF + AutoTable (loaded via CDN in template) ──────────────
// declare const jspdf: any
declare const XLSX: any

const { toast } = useToast()

const COMPANY_NAME = 'Greenwood Academy'
const PAGE_SIZE_OPTIONS = [10, 25, 50, 100]

interface Schedule {
  id: number
  max_theory_marks: number
  max_practical_marks: number
  max_total_marks: number
  pass_marks: number
  exam_date: string
}

interface StudentRow {
  student_id: number
  roll_no: string
  name: string
  photo_url: string
  theory_marks: string
  practical_marks: string
  total_marks: string
  is_absent: boolean
  remarks: string
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
  classId: number
  sectionId: number
  subjectId: number
  subjectName?: string
  students: StudentRow[]
  schedule: Schedule | null
  classes: any[]
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Marks Management', href: '/marks' },
  { title: props.exam.name, href: `/marks` },
  { title: 'Enter Marks', href: '#' },
]

// ─── Local state for marks ───────────────────────────────────────
interface MarkEntry {
  student_id: number
  theory_marks: string
  practical_marks: string
  total_marks: number
  is_absent: boolean
  remarks: string
  error_theory: string
  error_practical: string
}

const marks = ref<MarkEntry[]>(
  props.students.map(s => ({
    student_id: s.student_id,
    theory_marks: String(s.theory_marks ?? ''),
    practical_marks: String(s.practical_marks ?? ''),
    total_marks: parseFloat(String(s.total_marks)) || 0,
    is_absent: s.is_absent,
    remarks: s.remarks || '',
    error_theory: '',
    error_practical: '',
  }))
)

const maxTheory = computed(() => props.schedule?.max_theory_marks ?? 80)
const maxPractical = computed(() => props.schedule?.max_practical_marks ?? 20)
const maxTotal = computed(() => props.schedule?.max_total_marks ?? 100)
const passMarks = computed(() => props.schedule?.pass_marks ?? 40)

// ─── Search ───────────────────────────────────────────────────────
const searchQuery = ref('')

// ─── Pagination ───────────────────────────────────────────────────
const currentPage = ref(1)
const pageSize = ref(10)

// ─── Filtered + paginated indices (we operate on original array indices) ──
const filteredIndices = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return props.students.map((_, i) => i)
  return props.students.reduce<number[]>((acc, s, i) => {
    if (
      s.name.toLowerCase().includes(q) ||
      s.roll_no.toLowerCase().includes(q)
    ) acc.push(i)
    return acc
  }, [])
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredIndices.value.length / pageSize.value)))

// Reset to page 1 on search or page size change
watch([searchQuery, pageSize], () => { currentPage.value = 1 })

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

// ─── Auto-calculate total + validation ───────────────────────────
const updateMarks = (index: number) => {
  const m = marks.value[index]
  m.error_theory = ''
  m.error_practical = ''

  if (m.is_absent) {
    m.theory_marks = ''
    m.practical_marks = ''
    m.total_marks = 0
    return
  }

  const theory = m.theory_marks !== '' ? parseFloat(m.theory_marks) : 0
  const practical = m.practical_marks !== '' ? parseFloat(m.practical_marks) : 0

  if (m.theory_marks !== '' && theory > maxTheory.value) {
    m.error_theory = `Max ${maxTheory.value}`
  }
  if (m.practical_marks !== '' && practical > maxPractical.value) {
    m.error_practical = `Max ${maxPractical.value}`
  }

  m.total_marks = theory + practical
}

const toggleAbsent = (index: number, checked: boolean) => {
  marks.value[index].is_absent = checked
  updateMarks(index)
}

const onMarkChange = (index: number) => {
  updateMarks(index)
}

// ─── Bulk actions ────────────────────────────────────────────────
const markAllPresent = () => {
  marks.value.forEach((m, i) => {
    m.is_absent = false
    updateMarks(i)
  })
  toast.success('All students marked as present')
}

const generateRandomMarks = () => {
  marks.value.forEach((m, i) => {
    if (!m.is_absent) {
      m.theory_marks = String(Math.floor(Math.random() * (maxTheory.value + 1)))
      m.practical_marks = String(Math.floor(Math.random() * (maxPractical.value + 1)))
      m.remarks = generateRemarks(parseFloat(m.theory_marks), parseFloat(m.practical_marks))
      updateMarks(i)
    }
  })
  toast.success('Random marks generated for all present students')
}

// Generate remarks based on marks
const generateRemarks = (theory: number, practical: number): string => {
  const total = theory + practical
  const percentage = (total / maxTotal.value) * 100

  if (percentage >= 90) return 'Excellent Performance'
  if (percentage >= 80) return 'Very Good'
  if (percentage >= 70) return 'Good'
  if (percentage >= 60) return 'Satisfactory'
  if (percentage >= 50) return 'Average'
  if (percentage >= 40) return 'Below Average'
  return 'Poor Performance'
}

// ─── Submit ──────────────────────────────────────────────────────
const submitting = ref(false)
const hasErrors = computed(() => marks.value.some(m => m.error_theory || m.error_practical))

const handleSubmit = () => {
  if (hasErrors.value) {
    toast.error('Please fix validation errors before saving')
    return
  }

  const payload = marks.value.map(m => ({
    student_id: m.student_id,
    class_id: props.classId,
    section_id: props.sectionId,
    subject_id: props.subjectId,
    theory_marks: m.is_absent ? null : (m.theory_marks || null),
    practical_marks: m.is_absent ? null : (m.practical_marks || null),
    is_absent: m.is_absent,
    remarks: m.remarks || null,
  }))

  submitting.value = true
  useForm({ marks: payload }).post(`/marks/${props.exam.id}/store`, {
    onSuccess: () => {
      toast.success('Marks saved successfully!')
    },
    onError: (errors) => {
      toast.error(Object.values(errors)[0] as string)
    },
    onFinish: () => { submitting.value = false },
  })
}

// ─── Stats ───────────────────────────────────────────────────────
const enteredCount = computed(() => marks.value.filter(m => m.is_absent || m.theory_marks !== '' || m.practical_marks !== '').length)
const absentCount = computed(() => marks.value.filter(m => m.is_absent).length)
const pendingCount = computed(() => marks.value.length - enteredCount.value)

// ─── PDF Export ──────────────────────────────────────────────────
const exportPDF = () => {
  try {
    // const { jsPDF } = jsPDF
    const doc = new jsPDF()

    // Header
    doc.setFontSize(16)
    doc.setFont('helvetica', 'bold')
    doc.text(COMPANY_NAME, 14, 15)

    doc.setFontSize(12)
    doc.setFont('helvetica', 'normal')
    doc.text(`Exam: ${props.exam.name}`, 14, 23)
    doc.text(`Subject: ${props.subjectName ?? 'N/A'}`, 14, 30)
    doc.text(`Date: ${props.schedule?.exam_date ?? 'N/A'}`, 14, 37)
    doc.text(`Max Marks: ${maxTotal.value}  |  Pass: ${passMarks.value}`, 14, 44)

    doc.line(14, 47, 196, 47)

    const tableData = props.students.map((s, i) => {
      const m = marks.value[i]
      return [
        i + 1,
        s.roll_no,
        s.name,
        m.is_absent ? 'AB' : (m.theory_marks || '—'),
        m.is_absent ? 'AB' : (m.practical_marks || '—'),
        m.is_absent ? 'AB' : (m.total_marks || '—'),
        m.is_absent ? 'Absent' : (m.total_marks >= passMarks.value ? 'Pass' : m.total_marks > 0 ? 'Fail' : '—'),
        m.remarks || '',
      ]
    });
    autoTable(doc, {
      startY: 52,
      head: [['#', 'Roll', 'Name', `Theory (${maxTheory.value})`, `Practical (${maxPractical.value})`, `Total (${maxTotal.value})`, 'Status', 'Remarks']],
      body: tableData,
      styles: { fontSize: 9, cellPadding: 3 },
      headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
      alternateRowStyles: { fillColor: [245, 249, 252] },
      columnStyles: {
        0: { cellWidth: 10 },
        1: { cellWidth: 16 },
        2: { cellWidth: 44 },
        3: { cellWidth: 24 },
        4: { cellWidth: 24 },
        5: { cellWidth: 22 },
        6: { cellWidth: 18 },
        7: { cellWidth: 30 },
      },
    })
     const pageCount = doc.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.text(
      `Page ${i} of ${pageCount}`,
      doc.internal.pageSize.getWidth() / 2,
      doc.internal.pageSize.getHeight() - 10,
      { align: 'center' }
    );
  }

    doc.save(`marks_${props.exam.name}_${props.subjectName ?? 'subject'}.pdf`)
    toast.success('PDF downloaded!')
  } catch (e) {
    console.log(e,"error")
    toast.error('PDF export failed. Please try again.')
  }
}



// ─── Excel Export ─────────────────────────────────────────────────
const exportExcel = () => {
  try {
    const rows = [
      [COMPANY_NAME],
      [`Exam: ${props.exam.name}`],
      [`Subject: ${props.subjectName ?? 'N/A'}`],
      [`Date: ${props.schedule?.exam_date ?? 'N/A'}`],
      [`Max Marks: ${maxTotal.value} | Pass: ${passMarks.value}`],
      [],
      ['#', 'Roll No', 'Student Name', `Theory (Max ${maxTheory.value})`, `Practical (Max ${maxPractical.value})`, `Total (Max ${maxTotal.value})`, 'Status', 'Remarks'],
      ...props.students.map((s, i) => {
        const m = marks.value[i]
        return [
          i + 1,
          s.roll_no,
          s.name,
          m.is_absent ? 'AB' : (m.theory_marks || ''),
          m.is_absent ? 'AB' : (m.practical_marks || ''),
          m.is_absent ? 'AB' : (m.total_marks || ''),
          m.is_absent ? 'Absent' : (m.total_marks >= passMarks.value ? 'Pass' : m.total_marks > 0 ? 'Fail' : ''),
          m.remarks || '',
        ]
      }),
    ]

    const ws = XLSX.utils.aoa_to_sheet(rows)
    ws['!cols'] = [{ wch: 5 }, { wch: 10 }, { wch: 30 }, { wch: 16 }, { wch: 16 }, { wch: 14 }, { wch: 10 }, { wch: 25 }]
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Marks')
    XLSX.writeFile(wb, `marks_${props.exam.name}_${props.subjectName ?? 'subject'}.xlsx`)
    toast.success('Excel downloaded!')
  } catch (e) {
    toast.error('Excel export failed. Please try again.')
  }
}
</script>

<template>
  <!-- CDN Libraries for PDF and Excel -->
  <!-- <component :is="'script'" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" />
  <component :is="'script'" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js" />
  <component :is="'script'" src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" /> -->

  <Head :title="`Enter Marks - ${exam.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Button variant="ghost" size="sm" @click="router.visit('/marks')">
            <ChevronLeft class="h-4 w-4" />
          </Button>
          <div>
            <h1 class="text-xl font-bold">Enter Marks</h1>
            <p class="text-sm text-muted-foreground">{{ exam.name }}{{ subjectName ? ` — ${subjectName}` : '' }}</p>
          </div>
        </div>

        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" @click="exportPDF">
            <FileDown class="h-4 w-4 mr-1.5" />
            PDF
          </Button>
          <Button variant="outline" size="sm" @click="exportExcel">
            <FileSpreadsheet class="h-4 w-4 mr-1.5" />
            Excel
          </Button>
        </div>
      </div>

      <!-- Stats Bar -->
      <div class="flex flex-wrap items-center gap-4 p-4 bg-muted/50 border rounded-xl">
        <div class="flex items-center gap-2 text-sm">
          <Calendar class="w-4 h-4 text-primary" />
          <span>{{ schedule?.exam_date ?? 'N/A' }}</span>
        </div>
        <div class="h-4 w-px bg-border" />
        <div class="flex items-center gap-2 text-sm">
          <Users class="w-4 h-4 text-muted-foreground" />
          <span>{{ students.length }} students</span>
        </div>
        <div class="h-4 w-px bg-border" />
        <div class="flex items-center gap-2 text-sm text-green-600">
          <span>✓ {{ enteredCount }} entered</span>
        </div>
        <div class="flex items-center gap-2 text-sm text-amber-600">
          <span>⏳ {{ pendingCount }} pending</span>
        </div>
        <div v-if="absentCount > 0" class="flex items-center gap-2 text-sm text-red-600">
          <UserX class="w-4 h-4" />
          <span>{{ absentCount }} absent</span>
        </div>
        <div class="ml-auto flex items-center gap-2 text-sm text-muted-foreground">
          <span>Max: {{ maxTotal }} (T:{{ maxTheory }} + P:{{ maxPractical }}) | Pass: {{ passMarks }}</span>
        </div>
      </div>

      <!-- Marks Entry Table -->
      <Card class="shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-primary/10 rounded-lg">
                <BookOpen class="w-5 h-5 text-primary" />
              </div>
              <CardTitle class="text-lg font-bold">Student Marks</CardTitle>
            </div>

            <!-- Toolbar: Search + Bulk actions -->
            <div class="flex flex-wrap items-center gap-2">
              <!-- Search -->
              <div class="relative">
                <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search name or roll no..."
                  class="pl-8 h-8 text-xs w-52"
                />
              </div>

              <Button type="button" variant="outline" size="sm" @click="markAllPresent">
                <CheckSquare class="h-4 w-4 mr-1.5" />
                Mark All Present
              </Button>

              <Button type="button" variant="outline" size="sm" @click="generateRandomMarks">
                <Dices class="h-4 w-4 mr-1.5" />
                Generate Random Marks
              </Button>
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-0 p-0">
          <div class="overflow-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-muted/60 border-b">
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground w-12">#</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground w-16">Roll</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground min-w-[200px]">Student Name</th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-28">
                    Theory<br><span class="text-xs font-normal">(Max: {{ maxTheory }})</span>
                  </th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-28">
                    Practical<br><span class="text-xs font-normal">(Max: {{ maxPractical }})</span>
                  </th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-24">Total</th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-16">Absent</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground w-40">Remarks</th>
                </tr>
              </thead>
              <tbody>
                <!-- Paginated rows -->
                <tr
                  v-for="(origIndex, rowPos) in pagedIndices"
                  :key="students[origIndex].student_id"
                  class="border-b last:border-0 transition-colors"
                  :class="[
                    rowPos % 2 === 0 ? 'bg-background' : 'bg-muted/20',
                    marks[origIndex].is_absent ? 'opacity-60' : '',
                    marks[origIndex].total_marks > 0 && marks[origIndex].total_marks < passMarks
                      ? 'bg-red-50/50 dark:bg-red-950/20'
                      : ''
                  ]"
                >
                  <!-- Absolute row number (across all pages) -->
                  <td class="px-4 py-2.5 text-muted-foreground">
                    {{ (currentPage - 1) * pageSize + rowPos + 1 }}
                  </td>
                  <td class="px-4 py-2.5 font-medium">{{ students[origIndex].roll_no }}</td>
                  <td class="px-4 py-2.5">
                    <span class="font-medium">{{ students[origIndex].name }}</span>
                  </td>

                  <!-- Theory -->
                  <td class="px-3 py-2.5">
                    <div>
                      <Input
                        type="number"
                        :model-value="marks[origIndex].theory_marks"
                        @update:model-value="(v) => { marks[origIndex].theory_marks = String(v); onMarkChange(origIndex) }"
                        class="h-8 text-center text-xs"
                        :class="{ 'border-red-500': marks[origIndex].error_theory }"
                        :disabled="marks[origIndex].is_absent"
                        :min="0"
                        :max="maxTheory"
                        placeholder="—"
                        :tabindex="rowPos * 3 + 1"
                      />
                      <p v-if="marks[origIndex].error_theory" class="text-[10px] text-red-500 mt-0.5 text-center">
                        {{ marks[origIndex].error_theory }}
                      </p>
                    </div>
                  </td>

                  <!-- Practical -->
                  <td class="px-3 py-2.5">
                    <div>
                      <Input
                        type="number"
                        :model-value="marks[origIndex].practical_marks"
                        @update:model-value="(v) => { marks[origIndex].practical_marks = String(v); onMarkChange(origIndex) }"
                        class="h-8 text-center text-xs"
                        :class="{ 'border-red-500': marks[origIndex].error_practical }"
                        :disabled="marks[origIndex].is_absent"
                        :min="0"
                        :max="maxPractical"
                        placeholder="—"
                        :tabindex="rowPos * 3 + 2"
                      />
                      <p v-if="marks[origIndex].error_practical" class="text-[10px] text-red-500 mt-0.5 text-center">
                        {{ marks[origIndex].error_practical }}
                      </p>
                    </div>
                  </td>

                  <!-- Total -->
                  <td class="px-3 py-2.5 text-center">
                    <span
                      class="font-semibold text-sm"
                      :class="{
                        'text-green-600': marks[origIndex].total_marks >= passMarks,
                        'text-red-600': marks[origIndex].total_marks > 0 && marks[origIndex].total_marks < passMarks,
                        'text-muted-foreground': marks[origIndex].total_marks === 0
                      }"
                    >
                      {{ marks[origIndex].is_absent ? 'AB' : (marks[origIndex].total_marks || '—') }}
                    </span>
                  </td>

                  <!-- Absent -->
                  <td class="px-3 py-2.5 text-center">
                    <Checkbox
                      :checked="marks[origIndex].is_absent"
                      @update:checked="(v: boolean) => toggleAbsent(origIndex, v)"
                    />
                  </td>

                  <!-- Remarks -->
                  <td class="px-3 py-2.5">
                    <Input
                      v-model="marks[origIndex].remarks"
                      class="h-8 text-xs"
                      placeholder="Optional"
                      :tabindex="rowPos * 3 + 3"
                    />
                  </td>
                </tr>

                <!-- No results from search -->
                <tr v-if="filteredIndices.length === 0 && searchQuery">
                  <td colspan="8" class="px-4 py-12 text-center">
                    <div class="flex flex-col items-center gap-2">
                      <Search class="w-8 h-8 text-muted-foreground" />
                      <p class="text-muted-foreground">No students match "<strong>{{ searchQuery }}</strong>"</p>
                    </div>
                  </td>
                </tr>

                <!-- Empty state (no students at all) -->
                <tr v-else-if="students.length === 0">
                  <td colspan="8" class="px-4 py-12 text-center">
                    <div class="flex flex-col items-center gap-2">
                      <AlertCircle class="w-8 h-8 text-muted-foreground" />
                      <p class="text-muted-foreground">No students enrolled in this class-section</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ── Pagination Bar ───────────────────────────────────── -->
          <div
            v-if="filteredIndices.length > 0"
            class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t bg-muted/20"
          >
            <!-- Left: rows info + page size -->
            <div class="flex items-center gap-3 text-sm text-muted-foreground">
              <span>
                Showing {{ pageFrom }}–{{ pageTo }} of {{ filteredIndices.length }}
                <template v-if="searchQuery"> (filtered from {{ students.length }})</template>
              </span>
              <div class="flex items-center gap-1.5">
                <Label class="text-xs">Rows:</Label>
                <select
                  v-model.number="pageSize"
                  class="h-7 text-xs rounded-md border border-input bg-background px-2 focus:outline-none focus:ring-1 focus:ring-ring"
                >
                  <option v-for="sz in PAGE_SIZE_OPTIONS" :key="sz" :value="sz">{{ sz }}</option>
                </select>
              </div>
            </div>

            <!-- Right: page buttons -->
            <div class="flex items-center gap-1">
              <!-- First -->
              <Button
                variant="outline"
                size="icon"
                class="h-7 w-7"
                :disabled="currentPage === 1"
                @click="currentPage = 1"
              >
                <ChevronsLeft class="h-3.5 w-3.5" />
              </Button>
              <!-- Prev -->
              <Button
                variant="outline"
                size="icon"
                class="h-7 w-7"
                :disabled="currentPage === 1"
                @click="currentPage--"
              >
                <ChevronLeft class="h-3.5 w-3.5" />
              </Button>

              <!-- Page numbers -->
              <template v-if="pageWindow[0] > 1">
                <Button variant="ghost" size="icon" class="h-7 w-7 text-xs" @click="currentPage = 1">1</Button>
                <span v-if="pageWindow[0] > 2" class="text-muted-foreground text-xs px-1">…</span>
              </template>

              <Button
                v-for="p in pageWindow"
                :key="p"
                :variant="p === currentPage ? 'default' : 'ghost'"
                size="icon"
                class="h-7 w-7 text-xs"
                @click="currentPage = p"
              >
                {{ p }}
              </Button>

              <template v-if="pageWindow[pageWindow.length - 1] < totalPages">
                <span v-if="pageWindow[pageWindow.length - 1] < totalPages - 1" class="text-muted-foreground text-xs px-1">…</span>
                <Button variant="ghost" size="icon" class="h-7 w-7 text-xs" @click="currentPage = totalPages">
                  {{ totalPages }}
                </Button>
              </template>

              <!-- Next -->
              <Button
                variant="outline"
                size="icon"
                class="h-7 w-7"
                :disabled="currentPage === totalPages"
                @click="currentPage++"
              >
                <ChevronRight class="h-3.5 w-3.5" />
              </Button>
              <!-- Last -->
              <Button
                variant="outline"
                size="icon"
                class="h-7 w-7"
                :disabled="currentPage === totalPages"
                @click="currentPage = totalPages"
              >
                <ChevronsRight class="h-3.5 w-3.5" />
              </Button>
            </div>
          </div>
        </CardContent>

        <!-- Footer -->
        <div class="flex justify-between gap-3 px-6 py-4 border-t">
          <Button type="button" variant="outline" @click="router.visit('/marks')">
            <ChevronLeft class="mr-2 h-4 w-4" />
            Back
          </Button>
          <Button type="button" @click="handleSubmit" :disabled="submitting || hasErrors">
            <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
            <Save v-else class="mr-2 h-4 w-4" />
            {{ submitting ? 'Saving...' : 'Save Final Marks' }}
          </Button>
        </div>
      </Card>

    </div>
  </AppLayout>
</template>