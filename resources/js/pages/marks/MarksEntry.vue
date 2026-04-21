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
  AlertCircle, UserX, Users
} from 'lucide-vue-next'

const { toast } = useToast()

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

// Watch for changes in theory/practical marks
const onMarkChange = (index: number) => {
  updateMarks(index)
}

// ─── Bulk actions ────────────────────────────────────────────────
const markAllPresent = () => {
  marks.value.forEach((m, i) => {
    m.is_absent = false
  })
}

const fillTheoryMarks = (value: string) => {
  marks.value.forEach((m, i) => {
    if (!m.is_absent) {
      m.theory_marks = value
      updateMarks(i)
    }
  })
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
</script>

<template>
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
            <p class="text-sm text-muted-foreground">{{ exam.name }}</p>
          </div>
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

            <!-- Quick actions -->
            <div class="flex gap-2">
              <Button type="button" variant="outline" size="sm" @click="markAllPresent">
                Mark All Present
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
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-28">Theory<br><span class="text-xs font-normal">(Max: {{ maxTheory }})</span></th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-28">Practical<br><span class="text-xs font-normal">(Max: {{ maxPractical }})</span></th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-24">Total</th>
                  <th class="text-center px-4 py-3 font-semibold text-muted-foreground w-16">Absent</th>
                  <th class="text-left px-4 py-3 font-semibold text-muted-foreground w-40">Remarks</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(student, i) in students" :key="student.student_id"
                  class="border-b last:border-0 transition-colors"
                  :class="[
                    i % 2 === 0 ? 'bg-background' : 'bg-muted/20',
                    marks[i].is_absent ? 'opacity-60' : '',
                    marks[i].total_marks > 0 && marks[i].total_marks < passMarks ? 'bg-red-50/50 dark:bg-red-950/20' : ''
                  ]">
                  <td class="px-4 py-2.5 text-muted-foreground">{{ i + 1 }}</td>
                  <td class="px-4 py-2.5 font-medium">{{ student.roll_no }}</td>
                  <td class="px-4 py-2.5">
                    <div class="flex items-center gap-2">
                      <span class="font-medium">{{ student.name }}</span>
                    </div>
                  </td>
                  <td class="px-3 py-2.5">
                    <div>
                      <Input
                        type="number"
                        :model-value="marks[i].theory_marks"
                        @update:model-value="(v) => { marks[i].theory_marks = String(v); onMarkChange(i) }"
                        class="h-8 text-center text-xs"
                        :class="{ 'border-red-500': marks[i].error_theory }"
                        :disabled="marks[i].is_absent"
                        :min="0"
                        :max="maxTheory"
                        placeholder="—"
                        :tabindex="i * 3 + 1"
                      />
                      <p v-if="marks[i].error_theory" class="text-[10px] text-red-500 mt-0.5 text-center">{{ marks[i].error_theory }}</p>
                    </div>
                  </td>
                  <td class="px-3 py-2.5">
                    <div>
                      <Input
                        type="number"
                        :model-value="marks[i].practical_marks"
                        @update:model-value="(v) => { marks[i].practical_marks = String(v); onMarkChange(i) }"
                        class="h-8 text-center text-xs"
                        :class="{ 'border-red-500': marks[i].error_practical }"
                        :disabled="marks[i].is_absent"
                        :min="0"
                        :max="maxPractical"
                        placeholder="—"
                        :tabindex="i * 3 + 2"
                      />
                      <p v-if="marks[i].error_practical" class="text-[10px] text-red-500 mt-0.5 text-center">{{ marks[i].error_practical }}</p>
                    </div>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <span class="font-semibold text-sm"
                      :class="{
                        'text-green-600': marks[i].total_marks >= passMarks,
                        'text-red-600': marks[i].total_marks > 0 && marks[i].total_marks < passMarks,
                        'text-muted-foreground': marks[i].total_marks === 0
                      }">
                      {{ marks[i].is_absent ? 'AB' : (marks[i].total_marks || '—') }}
                    </span>
                  </td>
                  <td class="px-3 py-2.5 text-center">
                    <Checkbox
                      :checked="marks[i].is_absent"
                      @update:checked="(v) => toggleAbsent(i, v as boolean)"
                    />
                  </td>
                  <td class="px-3 py-2.5">
                    <Input
                      v-model="marks[i].remarks"
                      class="h-8 text-xs"
                      placeholder="Optional"
                      :disabled="false"
                      :tabindex="i * 3 + 3"
                    />
                  </td>
                </tr>

                <!-- Empty state -->
                <tr v-if="students.length === 0">
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
            {{ submitting ? 'Saving...' : 'Save Marks' }}
          </Button>
        </div>
      </Card>

    </div>
  </AppLayout>
</template>
