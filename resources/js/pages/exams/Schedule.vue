<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Calendar, Clock, BookOpen, Users, ChevronDown, ChevronRight,
  Plus, ArrowLeft, CheckCircle2, CircleDot, AlertCircle, FileText
} from 'lucide-vue-next'

interface ScheduleRow {
  id: number
  subject_name: string
  exam_date: string
  start_time: string
  end_time: string
  room_no: string
  max_theory_marks: number
  max_practical_marks: number
  max_total_marks: number
  pass_marks: number
}

interface ClassGroup {
  class_id: number
  class_name: string
  section_id: number
  section_name: string
  schedules: ScheduleRow[]
}

interface Props {
  exam: any
  groupedSchedule: ClassGroup[]
  classes: any[]
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Exams', href: '/exams' },
  { title: props.exam.name, href: `/exams/${props.exam.id}` },
  { title: 'Schedule', href: '#' },
]

// ── Collapse state ──────────────────────────────────────────────
const collapsed = ref<Record<string, boolean>>({})
const toggleGroup = (key: string) => {
  collapsed.value[key] = !collapsed.value[key]
}
const isOpen = (key: string) => !collapsed.value[key]

// ── Filter ──────────────────────────────────────────────────────
const activeClassFilter = ref<string>('all')
const filteredGroups = computed(() => {
  if (activeClassFilter.value === 'all') return props.groupedSchedule
  return props.groupedSchedule.filter(
    g => String(g.class_id) === activeClassFilter.value
  )
})

// ── Unique class list for filter pills ──────────────────────────
const uniqueClasses = computed(() => {
  const seen = new Set<number>()
  return props.groupedSchedule.filter(g => {
    if (seen.has(g.class_id)) return false
    seen.add(g.class_id); return true
  })
})

// ── Stats ───────────────────────────────────────────────────────
const totalSchedules = computed(() =>
  props.groupedSchedule.reduce((acc, g) => acc + g.schedules.length, 0)
)
const totalSections = computed(() => props.groupedSchedule.length)
const totalClasses  = computed(() => uniqueClasses.value.length)

// ── Status helpers ───────────────────────────────────────────────
const statusConfig: Record<string, { label: string; class: string }> = {
  ongoing:   { label: 'Ongoing',   class: 'bg-green-100  text-green-700  border-green-200'  },
  upcoming:  { label: 'Upcoming',  class: 'bg-blue-100   text-blue-700   border-blue-200'   },
  completed: { label: 'Completed', class: 'bg-purple-100 text-purple-700 border-purple-200' },
  draft:     { label: 'Draft',     class: 'bg-gray-100   text-gray-600   border-gray-200'   },
}
const statusCfg = computed(
  () => statusConfig[props.exam.status] ?? statusConfig['draft']
)

// ── Date/time formatters ─────────────────────────────────────────
const formatDate = (d: string) =>
  d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'

const formatTime = (t: string) => {
  if (!t) return '—'
  const [h, m] = t.split(':')
  const hour = parseInt(h)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  return `${hour % 12 || 12}:${m} ${ampm}`
}

// ── Subject color cycling ────────────────────────────────────────
const subjectColors = [
  'bg-blue-100   text-blue-700',
  'bg-teal-100   text-teal-700',
  'bg-purple-100 text-purple-700',
  'bg-amber-100  text-amber-700',
  'bg-rose-100   text-rose-700',
  'bg-cyan-100   text-cyan-700',
]
const subjectColorMap: Record<string, string> = {}
let colorIndex = 0
const getSubjectColor = (name: string) => {
  if (!subjectColorMap[name]) {
    subjectColorMap[name] = subjectColors[colorIndex++ % subjectColors.length]
  }
  return subjectColorMap[name]
}
</script>

<template>
  <Head :title="`Schedule – ${exam.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4 max-w-6xl mx-auto w-full">

      <!-- ── Header Card ─────────────────────────────────────────── -->
      <Card class="rounded-2xl shadow-sm border">
        <CardContent class="p-5">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-semibold"
                  :class="statusCfg.class">
                  <span class="w-1.5 h-1.5 rounded-full bg-current inline-block"></span>
                  {{ statusCfg.label }}
                </span>
                <span v-if="exam.is_published"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-xs font-semibold bg-emerald-50 text-emerald-700 border-emerald-200">
                  <CheckCircle2 class="w-3 h-3" /> Published
                </span>
              </div>
              <h1 class="text-xl font-bold tracking-tight">{{ exam.name }}</h1>
              <p class="text-sm text-muted-foreground mt-0.5">
                {{ exam.exam_type?.replace('_', ' ')?.replace(/\b\w/g, (c: string) => c.toUpperCase()) }}
                <span v-if="exam.academicYear"> · {{ exam.academicYear.name }}</span>
                <span v-if="exam.term"> · {{ exam.term.name }}</span>
              </p>
            </div>

            <div class="flex gap-2 flex-wrap">
              <Button variant="outline" size="sm" @click="router.visit('/exams')">
                <ArrowLeft class="w-4 h-4 mr-1" /> Back
              </Button>
              <Button size="sm" @click="router.visit(`/exams/${exam.id}/schedule/create`)">
                <Plus class="w-4 h-4 mr-1" /> Add Schedule
              </Button>
            </div>
          </div>

          <!-- Meta row -->
          <div class="flex gap-6 mt-4 flex-wrap">
            <div class="flex items-center gap-1.5 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              <span class="text-muted-foreground">Start:</span>
              <span class="font-medium">{{ formatDate(exam.start_date) }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              <span class="text-muted-foreground">End:</span>
              <span class="font-medium">{{ formatDate(exam.end_date) }}</span>
            </div>
            <div v-if="exam.weightage" class="flex items-center gap-1.5 text-sm">
              <FileText class="w-4 h-4 text-muted-foreground" />
              <span class="text-muted-foreground">Weightage:</span>
              <span class="font-medium">{{ exam.weightage }}%</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- ── Stats ──────────────────────────────────────────────── -->
      <div class="grid grid-cols-3 gap-3">
        <div class="bg-card border rounded-xl p-4">
          <div class="text-2xl font-bold tracking-tight">{{ totalClasses }}</div>
          <div class="text-xs text-muted-foreground mt-0.5 font-medium uppercase tracking-wide">Classes</div>
        </div>
        <div class="bg-card border rounded-xl p-4">
          <div class="text-2xl font-bold tracking-tight">{{ totalSections }}</div>
          <div class="text-xs text-muted-foreground mt-0.5 font-medium uppercase tracking-wide">Sections</div>
        </div>
        <div class="bg-card border rounded-xl p-4">
          <div class="text-2xl font-bold tracking-tight">{{ totalSchedules }}</div>
          <div class="text-xs text-muted-foreground mt-0.5 font-medium uppercase tracking-wide">Schedules</div>
        </div>
      </div>

      <!-- ── Filter Pills ───────────────────────────────────────── -->
      <div class="flex gap-2 flex-wrap">
        <button
          class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-all"
          :class="activeClassFilter === 'all'
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-card text-muted-foreground border-border hover:border-primary/50'"
          @click="activeClassFilter = 'all'">
          All Classes
        </button>
        <button
          v-for="grp in uniqueClasses" :key="grp.class_id"
          class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-all"
          :class="activeClassFilter === String(grp.class_id)
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-card text-muted-foreground border-border hover:border-primary/50'"
          @click="activeClassFilter = String(grp.class_id)">
          {{ grp.class_name }}
        </button>
      </div>

      <!-- ── Empty State ────────────────────────────────────────── -->
      <div v-if="filteredGroups.length === 0"
        class="flex flex-col items-center justify-center py-16 text-center bg-card border rounded-2xl">
        <BookOpen class="w-10 h-10 text-muted-foreground/40 mb-3" />
        <p class="text-base font-medium text-muted-foreground">No schedules found</p>
        <p class="text-sm text-muted-foreground/70 mt-1">Add a schedule to get started</p>
        <Button class="mt-4" size="sm" @click="router.visit(`/exams/${exam.id}/schedule/create`)">
          <Plus class="w-4 h-4 mr-1.5" /> Add Schedule
        </Button>
      </div>

      <!-- ── Schedule Groups ────────────────────────────────────── -->
      <div
        v-for="grp in filteredGroups"
        :key="`${grp.class_id}_${grp.section_id}`"
        class="rounded-2xl border overflow-hidden shadow-sm bg-card">

        <!-- Group header -->
        <div
          class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-muted/30 transition-colors border-b bg-muted/20"
          @click="toggleGroup(`${grp.class_id}_${grp.section_id}`)">
          <div class="flex items-center gap-2.5">
            <div class="w-2 h-2 rounded-full bg-primary"></div>
            <span class="font-semibold text-sm">{{ grp.class_name }}</span>
            <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-primary/10 text-primary">
              Section {{ grp.section_name }}
            </span>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs text-muted-foreground">
              {{ grp.schedules.length }} subject{{ grp.schedules.length !== 1 ? 's' : '' }}
            </span>
            <component
              :is="isOpen(`${grp.class_id}_${grp.section_id}`) ? ChevronDown : ChevronRight"
              class="w-4 h-4 text-muted-foreground transition-transform" />
          </div>
        </div>

        <!-- Schedule table -->
        <div v-show="isOpen(`${grp.class_id}_${grp.section_id}`)">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b bg-muted/10">
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Subject</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Time</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Room</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Theory</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Practical</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Pass</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in grp.schedules" :key="row.id"
                  class="border-b last:border-b-0 hover:bg-muted/20 transition-colors">

                  <!-- Subject -->
                  <td class="px-4 py-3 font-medium">
                    <span
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
                      :class="getSubjectColor(row.subject_name)">
                      {{ row.subject_name }}
                    </span>
                  </td>

                  <!-- Date -->
                  <td class="px-4 py-3 text-sm font-medium">{{ formatDate(row.exam_date) }}</td>

                  <!-- Time -->
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-muted px-2 py-1 rounded-md">
                      {{ formatTime(row.start_time) }} – {{ formatTime(row.end_time) }}
                    </span>
                  </td>

                  <!-- Room -->
                  <td class="px-4 py-3 text-sm text-muted-foreground">{{ row.room_no || '—' }}</td>

                  <!-- Theory -->
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-md font-semibold">
                      {{ row.max_theory_marks ?? '—' }}
                    </span>
                  </td>

                  <!-- Practical -->
                  <td class="px-4 py-3">
                    <span
                      class="font-mono text-xs px-2 py-1 rounded-md font-semibold"
                      :class="row.max_practical_marks
                        ? 'bg-emerald-50 text-emerald-700'
                        : 'text-muted-foreground'">
                      {{ row.max_practical_marks || '—' }}
                    </span>
                  </td>

                  <!-- Total -->
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded-md font-semibold">
                      {{ row.max_total_marks }}
                    </span>
                  </td>

                  <!-- Pass -->
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-amber-50 text-amber-700 px-2 py-1 rounded-md font-semibold">
                      {{ row.pass_marks }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>