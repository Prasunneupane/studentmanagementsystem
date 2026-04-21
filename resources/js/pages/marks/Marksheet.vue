<script setup lang="ts">
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  ChevronLeft, Printer, BookOpen, Award, TrendingUp,
  CheckCircle2, XCircle, AlertTriangle, User
} from 'lucide-vue-next'

interface SubjectMark {
  subject_name: string
  subject_code: string
  theory_marks: number | null
  practical_marks: number | null
  total_marks: number | null
  max_theory_marks: number
  max_practical_marks: number
  max_total_marks: number
  pass_marks: number
  is_absent: boolean
  grade: string
  status: 'pass' | 'fail' | 'absent'
  remarks: string | null
}

interface Props {
  marksheet: {
    exam: {
      id: string
      name: string
      exam_type: string
      academic_year: string
      term: string | null
    }
    student: {
      id: number
      name: string
      roll_no: string
      class_name: string
      section_name: string
      photo_url: string
    } | null
    subjects: SubjectMark[]
    result: {
      total_marks_obtained: number
      total_max_marks: number
      percentage: number
      grade: string
      gpa: number
      rank: number | null
      status: string
      is_finalized: boolean
    } | null
  }
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Marks Management', href: '/marks' },
  { title: props.marksheet.exam.name, href: '#' },
  { title: 'Marksheet', href: '#' },
]

const student = computed(() => props.marksheet.student)
const exam = computed(() => props.marksheet.exam)
const subjects = computed(() => props.marksheet.subjects)
const result = computed(() => props.marksheet.result)

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

const handlePrint = () => {
  window.print()
}
</script>

<template>
  <Head :title="`Marksheet - ${student?.name ?? 'Student'}`" />
  <AppLayout :breadcrumbs="breadcrumbs">

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">

      <!-- Actions Bar (hidden in print) -->
      <div class="flex items-center justify-between print:hidden">
        <Button variant="ghost" size="sm" @click="router.back()">
          <ChevronLeft class="mr-1 h-4 w-4" /> Back
        </Button>
        <Button variant="outline" size="sm" @click="handlePrint">
          <Printer class="mr-2 h-4 w-4" /> Print Marksheet
        </Button>
      </div>

      <!-- Marksheet Card -->
      <Card class="shadow-lg rounded-2xl max-w-4xl mx-auto w-full print:shadow-none print:border-2 print:border-black">

        <!-- School / Exam Header -->
        <CardHeader class="border-b text-center py-6">
          <div class="space-y-1">
            <CardTitle class="text-2xl font-bold tracking-tight">{{ exam.name }}</CardTitle>
            <p class="text-sm text-muted-foreground">
              {{ exam.academic_year }}
              <span v-if="exam.term"> · {{ exam.term }}</span>
              · <span class="capitalize">{{ exam.exam_type?.replace('_', ' ') }}</span>
            </p>
          </div>
        </CardHeader>

        <CardContent class="pt-6 space-y-6">

          <!-- Student Info -->
          <div v-if="student" class="flex items-start justify-between p-4 bg-muted/50 rounded-xl border">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden">
                <img v-if="student.photo_url" :src="student.photo_url" :alt="student.name"
                  class="w-full h-full object-cover" />
                <User v-else class="w-7 h-7 text-primary" />
              </div>
              <div>
                <h3 class="text-lg font-bold">{{ student.name }}</h3>
                <p class="text-sm text-muted-foreground">
                  Roll No: <span class="font-semibold text-foreground">{{ student.roll_no }}</span>
                </p>
                <p class="text-sm text-muted-foreground">
                  {{ student.class_name }} — Section {{ student.section_name }}
                </p>
              </div>
            </div>

            <!-- Result badge -->
            <div v-if="result" class="text-right space-y-1">
              <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold uppercase"
                :class="statusColor(result.status)">
                <CheckCircle2 v-if="result.status === 'pass'" class="w-3.5 h-3.5" />
                <XCircle v-else class="w-3.5 h-3.5" />
                {{ result.status }}
              </span>
              <p v-if="result.rank" class="text-xs text-muted-foreground">
                Rank: <span class="font-bold text-foreground">{{ result.rank }}</span>
              </p>
              <p v-if="result.is_finalized" class="text-[10px] text-green-600 font-medium">✓ Finalized</p>
            </div>
          </div>

          <!-- Marks Table -->
          <div class="rounded-xl border overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-muted/60 border-b">
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground w-12">#</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground">Subject</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground">Theory</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground">Practical</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground">Total</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground">Max</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground">Pass</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground">Grade</th>
                  <th class="text-center px-3 py-3 font-semibold text-muted-foreground">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(subj, i) in subjects" :key="i"
                  class="border-b last:border-0 transition-colors"
                  :class="[
                    i % 2 === 0 ? 'bg-background' : 'bg-muted/20',
                    subj.status === 'fail' ? 'bg-red-50/50 dark:bg-red-950/20' : '',
                    subj.is_absent ? 'opacity-60' : ''
                  ]">
                  <td class="px-4 py-2.5 text-muted-foreground">{{ i + 1 }}</td>
                  <td class="px-4 py-2.5">
                    <div class="font-medium">{{ subj.subject_name }}</div>
                    <div class="text-xs text-muted-foreground">{{ subj.subject_code }}</div>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span v-if="subj.is_absent" class="text-red-500 font-medium">AB</span>
                    <span v-else>{{ subj.theory_marks ?? '—' }}/{{ subj.max_theory_marks }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span v-if="subj.is_absent" class="text-red-500 font-medium">AB</span>
                    <span v-else>{{ subj.practical_marks ?? '—' }}/{{ subj.max_practical_marks }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-center font-semibold">
                    <span v-if="subj.is_absent" class="text-red-500">AB</span>
                    <span v-else :class="subj.status === 'fail' ? 'text-red-600' : ''">
                      {{ subj.total_marks ?? '—' }}
                    </span>
                  </td>
                  <td class="px-3 py-2.5 text-center text-muted-foreground">{{ subj.max_total_marks }}</td>
                  <td class="px-3 py-2.5 text-center text-muted-foreground">{{ subj.pass_marks }}</td>
                  <td class="px-3 py-2.5 text-center">
                    <span class="font-bold" :class="gradeColor(subj.grade)">{{ subj.grade }}</span>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold capitalize"
                      :class="statusColor(subj.status)">
                      {{ subj.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Result Summary -->
          <div v-if="result" class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="p-4 bg-muted/50 rounded-xl text-center border">
              <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Total Marks</p>
              <p class="text-xl font-bold">{{ result.total_marks_obtained }}/{{ result.total_max_marks }}</p>
            </div>
            <div class="p-4 bg-muted/50 rounded-xl text-center border">
              <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Percentage</p>
              <p class="text-xl font-bold">{{ result.percentage }}%</p>
            </div>
            <div class="p-4 bg-muted/50 rounded-xl text-center border">
              <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Grade</p>
              <p class="text-xl font-bold" :class="gradeColor(result.grade)">{{ result.grade }}</p>
            </div>
            <div class="p-4 bg-muted/50 rounded-xl text-center border">
              <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">GPA</p>
              <p class="text-xl font-bold">{{ result.gpa }}</p>
            </div>
            <div class="p-4 bg-muted/50 rounded-xl text-center border">
              <p class="text-xs text-muted-foreground uppercase tracking-wider mb-1">Rank</p>
              <p class="text-xl font-bold">{{ result.rank ?? '—' }}</p>
            </div>
          </div>

          <!-- Nepal Grading Scale Reference -->
          <div class="p-4 bg-muted/30 rounded-xl border">
            <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">Grading Scale</p>
            <div class="flex flex-wrap gap-3 text-xs">
              <span class="px-2 py-1 rounded bg-green-100 dark:bg-green-900/30 text-green-700">A+ (90-100) 4.0</span>
              <span class="px-2 py-1 rounded bg-green-100 dark:bg-green-900/30 text-green-700">A (80-89) 3.6</span>
              <span class="px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700">B+ (70-79) 3.2</span>
              <span class="px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-700">B (60-69) 2.8</span>
              <span class="px-2 py-1 rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700">C+ (50-59) 2.4</span>
              <span class="px-2 py-1 rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700">C (40-49) 2.0</span>
              <span class="px-2 py-1 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700">D+ (30-39) 1.6</span>
              <span class="px-2 py-1 rounded bg-orange-100 dark:bg-orange-900/30 text-orange-700">D (20-29) 1.2</span>
              <span class="px-2 py-1 rounded bg-red-100 dark:bg-red-900/30 text-red-700">NG (&lt;20) 0.0</span>
            </div>
          </div>

        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style>
@media print {
  nav, .print\\:hidden, header, aside {
    display: none !important;
  }
  body {
    background: white !important;
  }
  .rounded-2xl {
    border-radius: 0 !important;
  }
}
</style>
