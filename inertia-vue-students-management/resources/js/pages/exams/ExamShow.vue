<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import {
  ArrowLeft, Edit, Calendar, Users, BookOpen, Clock,
  ChevronDown, Award, Hash, UserCheck, LayoutGrid,
  CheckCircle2, XCircle, AlertCircle, CalendarDays
} from 'lucide-vue-next'

// ─── Types ────────────────────────────────────────────────────────────────────
interface Schedule {
  id: number
  subject_id: number
  subject_name: string
  subject_code: string
  teacher_name: string | null
  exam_date: string | null
  exam_date_formatted: string | null
  start_time: string | null
  end_time: string | null
  full_marks: number | null
  pass_marks: number | null
}

interface Section {
  exam_class_id: number
  section_id: number | null
  section_name: string | null
  schedules: Schedule[]
}

interface ClassGroup {
  class_id: number
  class_name: string
  sections: Section[]
}

interface ExamInfo {
  id: number
  name: string
  exam_type: string
  start_date: string | null
  end_date: string | null
  weightage: number | null
  is_published: boolean
  academic_year: string | null
  term: string | null
}

const props = defineProps<{ exam: ExamInfo; examClasses: ClassGroup[] }>()

// ─── Accordion ────────────────────────────────────────────────────────────────
const expanded = ref<Set<number>>(new Set(props.examClasses.map(c => c.class_id)))

const isOpen    = (id: number) => expanded.value.has(id)
const toggle    = (id: number) => {
  isOpen(id) ? expanded.value.delete(id) : expanded.value.add(id)
  expanded.value = new Set(expanded.value)
}
const expandAll   = () => { expanded.value = new Set(props.examClasses.map(c => c.class_id)) }
const collapseAll = () => { expanded.value = new Set() }

// ─── Computed stats ───────────────────────────────────────────────────────────
const totalSections = computed(() =>
  props.examClasses.reduce((n, c) => n + c.sections.length, 0))

const totalScheduled = computed(() =>
  props.examClasses.reduce((n, c) =>
    n + c.sections.reduce((m, s) => m + s.schedules.length, 0), 0))

// ─── Helpers ──────────────────────────────────────────────────────────────────
const EXAM_TYPE_LABELS: Record<string, string> = {
  unit_test: 'Unit Test', midterm: 'Mid Term',
  final: 'Final', semester: 'Semester', annual: 'Annual',
}

// Soft colour pairs: [bg, text, border]  — used on pills & section dots
const CLASS_COLORS = [
  ['bg-violet-100', 'text-violet-700', 'border-violet-200'],
  ['bg-sky-100',    'text-sky-700',    'border-sky-200'],
  ['bg-emerald-100','text-emerald-700','border-emerald-200'],
  ['bg-rose-100',   'text-rose-700',   'border-rose-200'],
  ['bg-amber-100',  'text-amber-700',  'border-amber-200'],
  ['bg-indigo-100', 'text-indigo-700', 'border-indigo-200'],
]
const colorFor = (idx: number) => CLASS_COLORS[idx % CLASS_COLORS.length]

const breadcrumbs = [
  { title: 'Exams', href: '/exams' },
  { title: props.exam.name, href: `/exams/${props.exam.id}` },
]
</script>

<template>
  <Head :title="exam.name" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex w-full flex-1 flex-col gap-5 p-4 md:p-6 max-w-6xl mx-auto">

      <!-- ══════════════════════════════════════════════════════════ -->
      <!--  HEADER                                                    -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div class="flex items-start gap-4 flex-wrap justify-between">

        <div class="flex items-start gap-3">
          <Button variant="outline" size="icon" class="shrink-0 mt-0.5" @click="router.visit('/exams')">
            <ArrowLeft class="w-4 h-4" />
          </Button>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-2xl font-bold tracking-tight leading-tight">{{ exam.name }}</h1>

              <!-- Exam type pill -->
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-primary/10 text-primary border-primary/20">
                {{ EXAM_TYPE_LABELS[exam.exam_type] ?? exam.exam_type }}
              </span>

              <!-- Published / Draft pill -->
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                :class="exam.is_published
                  ? 'bg-green-50 text-green-700 border-green-200'
                  : 'bg-muted text-muted-foreground border-border'">
                <CheckCircle2 v-if="exam.is_published" class="w-3 h-3" />
                <XCircle v-else class="w-3 h-3" />
                {{ exam.is_published ? 'Published' : 'Draft' }}
              </span>
            </div>

            <p class="text-sm text-muted-foreground mt-1">
              {{ [exam.academic_year, exam.term].filter(Boolean).join(' · ') }}
            </p>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="flex gap-2 shrink-0">
          <Button variant="outline" size="sm" @click="router.visit(`/exams/${exam.id}/schedule`)">
            <CalendarDays class="mr-1.5 h-4 w-4" /> Schedule
          </Button>
          <Button size="sm" @click="router.visit(`/exams/${exam.id}/edit`)">
            <Edit class="mr-1.5 h-4 w-4" /> Edit
          </Button>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════ -->
      <!--  STATS ROW                                                 -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        <Card class="rounded-xl shadow-sm border">
          <CardContent class="flex items-center gap-3 p-4">
            <div class="p-2 rounded-lg bg-primary/10 shrink-0">
              <Calendar class="w-4 h-4 text-primary" />
            </div>
            <div class="min-w-0">
              <p class="text-[11px] text-muted-foreground uppercase tracking-wide font-medium">Start</p>
              <p class="font-semibold text-sm truncate">{{ exam.start_date ?? '—' }}</p>
            </div>
          </CardContent>
        </Card>

        <Card class="rounded-xl shadow-sm border">
          <CardContent class="flex items-center gap-3 p-4">
            <div class="p-2 rounded-lg bg-primary/10 shrink-0">
              <Calendar class="w-4 h-4 text-primary" />
            </div>
            <div class="min-w-0">
              <p class="text-[11px] text-muted-foreground uppercase tracking-wide font-medium">End</p>
              <p class="font-semibold text-sm truncate">{{ exam.end_date ?? '—' }}</p>
            </div>
          </CardContent>
        </Card>

        <Card class="rounded-xl shadow-sm border">
          <CardContent class="flex items-center gap-3 p-4">
            <div class="p-2 rounded-lg bg-sky-100 shrink-0">
              <Users class="w-4 h-4 text-sky-600" />
            </div>
            <div>
              <p class="text-[11px] text-muted-foreground uppercase tracking-wide font-medium">Sections</p>
              <p class="font-bold text-lg leading-none mt-0.5">{{ totalSections }}</p>
            </div>
          </CardContent>
        </Card>

        <Card class="rounded-xl shadow-sm border">
          <CardContent class="flex items-center gap-3 p-4">
            <div class="p-2 rounded-lg bg-amber-100 shrink-0">
              <BookOpen class="w-4 h-4 text-amber-600" />
            </div>
            <div>
              <p class="text-[11px] text-muted-foreground uppercase tracking-wide font-medium">Subjects Scheduled</p>
              <p class="font-bold text-lg leading-none mt-0.5">{{ totalScheduled }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- ══════════════════════════════════════════════════════════ -->
      <!--  ACCORDION SECTION HEADING + CONTROLS                     -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-widest flex items-center gap-2">
          <LayoutGrid class="w-4 h-4" /> Classes &amp; Sections
        </h2>
        <div class="flex gap-1">
          <Button variant="ghost" size="sm" class="h-7 text-xs px-2.5" @click="expandAll">Expand All</Button>
          <span class="text-border self-center select-none">·</span>
          <Button variant="ghost" size="sm" class="h-7 text-xs px-2.5" @click="collapseAll">Collapse All</Button>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════ -->
      <!--  EMPTY STATE                                               -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div v-if="examClasses.length === 0"
        class="flex flex-col items-center justify-center gap-3 py-20 border-2 border-dashed rounded-2xl text-center">
        <div class="p-4 bg-muted rounded-full">
          <Users class="w-7 h-7 text-muted-foreground" />
        </div>
        <p class="font-semibold text-muted-foreground">No classes assigned to this exam</p>
        <Button variant="outline" size="sm" @click="router.visit(`/exams/${exam.id}/edit`)">
          <Edit class="mr-2 h-4 w-4" /> Edit Exam
        </Button>
      </div>

      <!-- ══════════════════════════════════════════════════════════ -->
      <!--  CLASS ACCORDION LIST                                      -->
      <!-- ══════════════════════════════════════════════════════════ -->
      <div class="space-y-3">
        <div
          v-for="(cls, ci) in examClasses"
          :key="cls.class_id"
          class="rounded-2xl border overflow-hidden shadow-sm transition-all duration-200"
          :class="isOpen(cls.class_id) ? 'border-primary/25' : 'border-border'"
        >

          <!-- ── Class header bar ─────────────────────────────────── -->
          <button
            class="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-muted/40 transition-colors text-left"
            :class="isOpen(cls.class_id) ? 'bg-muted/30' : 'bg-card'"
            @click="toggle(cls.class_id)"
          >
            <!-- Coloured class badge -->
            <span
              class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold border shrink-0"
              :class="[colorFor(ci)[0], colorFor(ci)[1], colorFor(ci)[2]]"
            >
              {{ cls.class_name.replace(/[^0-9A-Za-z]/g, '').slice(0, 2).toUpperCase() }}
            </span>

            <span class="font-bold text-base flex-1">{{ cls.class_name }}</span>

            <span class="text-xs text-muted-foreground mr-1 hidden sm:block">
              {{ cls.sections.length }} section{{ cls.sections.length !== 1 ? 's' : '' }}
            </span>

            <ChevronDown
              class="w-4 h-4 text-muted-foreground shrink-0 transition-transform duration-200"
              :class="isOpen(cls.class_id) ? 'rotate-180' : ''"
            />
          </button>

          <!-- ── Section list (animated collapse) ────────────────── -->
          <div v-if="isOpen(cls.class_id)" class="divide-y border-t">

            <div v-for="sec in cls.sections" :key="sec.exam_class_id">

              <!-- Section label row -->
              <div class="flex items-center gap-2.5 px-5 py-2 bg-muted/20">
                <span
                  class="w-2 h-2 rounded-full shrink-0"
                  :class="colorFor(ci)[0].replace('bg-', 'bg-').replace('100', '400')"
                />
                <span class="text-sm font-semibold">
                  {{ sec.section_name ? `Section ${sec.section_name}` : 'All Sections' }}
                </span>
                <span class="ml-auto text-xs text-muted-foreground">
                  {{ sec.schedules.length }} subject{{ sec.schedules.length !== 1 ? 's' : '' }}
                </span>
              </div>

              <!-- No schedule yet -->
              <div v-if="sec.schedules.length === 0"
                class="flex items-center gap-2 px-6 py-4 text-sm text-muted-foreground bg-card italic">
                <AlertCircle class="w-4 h-4 text-amber-400 shrink-0" />
                No subjects scheduled yet.
                <button
                  class="ml-1 underline underline-offset-2 text-primary hover:text-primary/80 not-italic font-medium"
                  @click.stop="router.visit(`/exams/${exam.id}/schedule`)"
                >
                  Set schedule →
                </button>
              </div>

              <!-- Schedule table -->
              <div v-else class="overflow-x-auto bg-card">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="border-b bg-muted/30">
                      <th class="text-left px-4 py-2.5 font-semibold text-muted-foreground whitespace-nowrap">
                        <span class="flex items-center gap-1.5 text-xs uppercase tracking-wide">
                          <BookOpen class="w-3.5 h-3.5" /> Subject
                        </span>
                      </th>
                      <th class="text-left px-4 py-2.5 font-semibold text-muted-foreground whitespace-nowrap">
                        <span class="flex items-center gap-1.5 text-xs uppercase tracking-wide">
                          <Hash class="w-3.5 h-3.5" /> Code
                        </span>
                      </th>
                      <th class="text-left px-4 py-2.5 font-semibold text-muted-foreground whitespace-nowrap">
                        <span class="flex items-center gap-1.5 text-xs uppercase tracking-wide">
                          <UserCheck class="w-3.5 h-3.5" /> Teacher
                        </span>
                      </th>
                      <th class="text-left px-4 py-2.5 font-semibold text-muted-foreground whitespace-nowrap">
                        <span class="flex items-center gap-1.5 text-xs uppercase tracking-wide">
                          <Calendar class="w-3.5 h-3.5" /> Date
                        </span>
                      </th>
                      <th class="text-left px-4 py-2.5 font-semibold text-muted-foreground whitespace-nowrap">
                        <span class="flex items-center gap-1.5 text-xs uppercase tracking-wide">
                          <Clock class="w-3.5 h-3.5" /> Time
                        </span>
                      </th>
                      <th class="text-center px-4 py-2.5 font-semibold text-muted-foreground whitespace-nowrap">
                        <span class="flex items-center justify-center gap-1.5 text-xs uppercase tracking-wide">
                          <Award class="w-3.5 h-3.5" /> Full
                        </span>
                      </th>
                      <th class="text-center px-4 py-2.5 font-semibold text-muted-foreground whitespace-nowrap">
                        <span class="text-xs uppercase tracking-wide">Pass</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y">
                    <tr
                      v-for="sch in sec.schedules"
                      :key="sch.id"
                      class="hover:bg-muted/20 transition-colors"
                    >
                      <!-- Subject name -->
                      <td class="px-4 py-3 font-medium whitespace-nowrap">
                        {{ sch.subject_name }}
                      </td>

                      <!-- Subject code -->
                      <td class="px-4 py-3">
                        <span v-if="sch.subject_code"
                          class="px-2 py-0.5 bg-muted rounded text-xs font-mono text-muted-foreground tracking-wide">
                          {{ sch.subject_code }}
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                      </td>

                      <!-- Teacher -->
                      <td class="px-4 py-3 whitespace-nowrap">
                        <span v-if="sch.teacher_name"
                          class="inline-flex items-center gap-1.5 text-sm">
                          <span class="w-5 h-5 rounded-full bg-primary/10 text-primary flex items-center justify-center text-[10px] font-bold shrink-0">
                            {{ sch.teacher_name.split(' ').map((w: string) => w[0]).join('').slice(0, 2).toUpperCase() }}
                          </span>
                          {{ sch.teacher_name }}
                        </span>
                        <span v-else class="text-muted-foreground text-xs italic">Unassigned</span>
                      </td>

                      <!-- Exam date -->
                      <td class="px-4 py-3 whitespace-nowrap text-sm">
                        <span v-if="sch.exam_date_formatted">{{ sch.exam_date_formatted }}</span>
                        <span v-else class="text-muted-foreground italic text-xs">Not set</span>
                      </td>

                      <!-- Time range -->
                      <td class="px-4 py-3 whitespace-nowrap text-sm">
                        <span v-if="sch.start_time && sch.end_time">
                          {{ sch.start_time }} – {{ sch.end_time }}
                        </span>
                        <span v-else class="text-muted-foreground italic text-xs">Not set</span>
                      </td>

                      <!-- Full marks -->
                      <td class="px-4 py-3 text-center">
                        <span v-if="sch.full_marks !== null"
                          class="inline-block px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                          {{ sch.full_marks }}
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                      </td>

                      <!-- Pass marks -->
                      <td class="px-4 py-3 text-center">
                        <span v-if="sch.pass_marks !== null"
                          class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                          {{ sch.pass_marks }}
                        </span>
                        <span v-else class="text-muted-foreground">—</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div><!-- /section loop -->
          </div><!-- /expanded body -->

        </div><!-- /class card -->
      </div><!-- /accordion list -->

    </div>
  </AppLayout>
</template>