<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'
import TimePicker from '@/components/ui/timepicker/TimePicker.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import {
  Loader2, Save, Copy, Calendar, BookOpen,
  CheckCircle2, ArrowDown, ArrowLeft, Pencil
} from 'lucide-vue-next'

const { toast } = useToast()

// ─── Types ────────────────────────────────────────────────────────
interface Subject { id: string; name: string; code: string; section_id: string | null }
interface Section { id: string; name: string }
interface ClassWithSections { id: string; name: string; sections: Section[] }

interface ExamClassEntry {
  class_id: string
  section_id: string | null
}

interface ScheduleRow {
  subject_id: string
  exam_date: string
  start_time: string
  end_time: string
  room_no: string
  max_theory_marks: string
  max_practical_marks: string
  max_total_marks: string
  pass_marks: string
}

interface Props {
  exam: {
    id: string; name: string; exam_type: string
    start_date: string; end_date: string; academic_year_id: string
  }
  examClasses: ExamClassEntry[]
  classes: ClassWithSections[]
  subjectsByClass: Record<string, Subject[]>
  /**
   * Map from backend: key = "{class_id}_{section_id}_{subject_id}"
   * value = existing schedule row values
   */
  existingSchedule: Record<string, {
    id?: number
    subject_id: string
    exam_date: string
    start_time: string
    end_time: string
    room_no: string
    max_theory_marks: string
    max_practical_marks: string
    max_total_marks: string
    pass_marks: string
  }>
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Exams',    href: '/exams' },
  { title: props.exam.name, href: `/exams/${props.exam.id}` },
  { title: 'Schedule', href: `/exams/${props.exam.id}/schedule` },
  { title: 'Edit',     href: '#' },
]

// ─── Date helper ─────────────────────────────────────────────────
const formatDate = (val: any): string => {
  if (!val) return ''
  const d = val instanceof Date ? val : new Date(val)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// ─── Tabs ─────────────────────────────────────────────────────────
interface ClassSectionTab {
  key: string
  classId: string
  className: string
  sectionId: string | null
  sectionName: string
  subjects: Subject[]
}

const tabs = computed((): ClassSectionTab[] => {
  const result: ClassSectionTab[] = []

  props.examClasses.forEach(entry => {
    const cls = props.classes.find(c => c.id === entry.class_id)
    if (!cls) return

    if (entry.section_id === null) {
      const subjects = props.subjectsByClass[entry.class_id] || []
      cls.sections.forEach(sec => {
        result.push({
          key: `${cls.id}_${sec.id}`,
          classId: cls.id,
          className: cls.name,
          sectionId: sec.id,
          sectionName: sec.name,
          subjects: subjects.filter(s =>
            s.section_id === null || String(s.section_id) === String(sec.id)
          ),
        })
      })
    } else {
      const sec = cls.sections.find(s => s.id === entry.section_id)
      const subjects = (props.subjectsByClass[entry.class_id] || []).filter(s =>
        s.section_id === null || String(s.section_id) === String(entry.section_id)
      )
      result.push({
        key: `${cls.id}_${entry.section_id}`,
        classId: cls.id,
        className: cls.name,
        sectionId: entry.section_id,
        sectionName: sec?.name || '',
        subjects,
      })
    }
  })

  // Sort tabs: Class 1, Class 2 … (numeric order)
  result.sort((a, b) => {
    const aNum = parseInt(a.className.replace(/\D/g, '')) || 0
    const bNum = parseInt(b.className.replace(/\D/g, '')) || 0
    if (aNum !== bNum) return aNum - bNum
    return a.sectionName.localeCompare(b.sectionName)
  })

  return result
})

const activeTab = ref(tabs.value[0]?.key || '')

// ─── Build grouped sidebar ────────────────────────────────────────
const tabsGroupedByClass = computed(() => {
  const groups: Record<string, ClassSectionTab[]> = {}
  tabs.value.forEach(tab => {
    if (!groups[tab.classId]) groups[tab.classId] = []
    groups[tab.classId].push(tab)
  })
  return groups
})

// ─── Schedule state ───────────────────────────────────────────────
const schedules = ref<Record<string, Record<string, ScheduleRow>>>({})

const makeEmptyRow = (subjId: string): ScheduleRow => ({
  subject_id: subjId,
  exam_date: '',
  start_time: '',
  end_time: '',
  room_no: '',
  max_theory_marks: '80',
  max_practical_marks: '20',
  max_total_marks: '100',
  pass_marks: '40',
})

/**
 * Look up an existing value from the backend map.
 * Backend key: "{class_id}_{section_id}_{subject_id}"
 * Tab key:     "{class_id}_{section_id}"
 */
const lookupExisting = (tab: ClassSectionTab, subjId: string): ScheduleRow | null => {
  const backendKey = `${tab.classId}_${tab.sectionId}_${subjId}`
  const existing   = props.existingSchedule[backendKey]
  if (!existing) return null
  return {
    subject_id:           String(existing.subject_id),
    exam_date:            existing.exam_date ?? '',
    start_time:           existing.start_time ?? '',
    end_time:             existing.end_time ?? '',
    room_no:              existing.room_no ?? '',
    max_theory_marks:     String(existing.max_theory_marks ?? '80'),
    max_practical_marks:  String(existing.max_practical_marks ?? '20'),
    max_total_marks:      String(existing.max_total_marks ?? '100'),
    pass_marks:           String(existing.pass_marks ?? '40'),
  }
}

// ─── Safe initialiser (same pattern as create page) ───────────────
const ensureTabInitialized = (tabKey: string) => {
  const tab = tabs.value.find(t => t.key === tabKey)
  if (!tab) return

  if (!schedules.value[tabKey]) schedules.value[tabKey] = {}

  tab.subjects.forEach(subj => {
    if (!schedules.value[tabKey][subj.id]) {
      // Prefer existing value from backend, fall back to empty row
      schedules.value[tabKey][subj.id] =
        lookupExisting(tab, subj.id) ?? makeEmptyRow(subj.id)
    }
  })
}

// Initialize all tabs
tabs.value.forEach(tab => ensureTabInitialized(tab.key))

// Guard on tab switch
watch(activeTab, (key) => ensureTabInitialized(key), { immediate: true })
watch(tabs, (t) => t.forEach(tab => ensureTabInitialized(tab.key)), { deep: true })

const getRow = (tabKey: string, subjId: string): ScheduleRow => {
  ensureTabInitialized(tabKey)
  return schedules.value[tabKey][subjId]
}

const currentTabData = computed(() => tabs.value.find(t => t.key === activeTab.value))

// ─── Auto-total ───────────────────────────────────────────────────
const updateTotalMarks = (tabKey: string, subjId: string) => {
  const row = schedules.value[tabKey]?.[subjId]
  if (!row) return
  row.max_total_marks = String(
    (parseFloat(row.max_theory_marks) || 0) + (parseFloat(row.max_practical_marks) || 0)
  )
}

// ─── Copy helpers ─────────────────────────────────────────────────
const otherSectionsCount = (tab: ClassSectionTab) =>
  tabs.value.filter(t => t.classId === tab.classId && t.key !== tab.key).length

const copyToAllSections = () => {
  const tab = currentTabData.value
  if (!tab) return
  const src = schedules.value[tab.key]
  let count = 0

  tabs.value.forEach(t => {
    if (t.classId === tab.classId && t.key !== tab.key) {
      ensureTabInitialized(t.key)
      schedules.value[t.key] = JSON.parse(JSON.stringify(src))
      t.subjects.forEach(subj => {
        if (schedules.value[t.key][subj.id]) {
          schedules.value[t.key][subj.id].subject_id = subj.id
        } else {
          schedules.value[t.key][subj.id] = makeEmptyRow(subj.id)
        }
      })
      count++
    }
  })

  if (count > 0) toast.success(`Copied to ${count} other section${count > 1 ? 's' : ''}`)
  else toast.info('No other sections in this class')
}

const copyToAllClasses = () => {
  const tab = currentTabData.value
  if (!tab) return
  const srcValues = Object.values(schedules.value[tab.key])
  let count = 0

  tabs.value.forEach(t => {
    if (t.key !== tab.key) {
      ensureTabInitialized(t.key)
      t.subjects.forEach((subj, idx) => {
        const src = srcValues[idx] || srcValues[0]
        schedules.value[t.key][subj.id] = {
          ...schedules.value[t.key][subj.id],
          exam_date:  src?.exam_date || '',
          start_time: src?.start_time || '',
          end_time:   src?.end_time || '',
          room_no:    src?.room_no || '',
        }
      })
      count++
    }
  })

  if (count > 0) toast.success(`Dates & times applied to ${count} other tabs`)
}

// ─── Fill down ────────────────────────────────────────────────────
const fillDown = (fromIndex: number) => {
  const tab = currentTabData.value
  if (!tab) return
  const src = schedules.value[tab.key][tab.subjects[fromIndex].id]
  for (let i = fromIndex + 1; i < tab.subjects.length; i++) {
    const row = schedules.value[tab.key][tab.subjects[i].id]
    if (row) { row.start_time = src.start_time; row.end_time = src.end_time; row.room_no = src.room_no }
  }
  toast.success(`Filled down to ${tab.subjects.length - fromIndex - 1} row(s)`)
}

// ─── Progress ─────────────────────────────────────────────────────
const tabHasData = (key: string) =>
  Object.values(schedules.value[key] || {}).some(r => r.exam_date)

const completedTabs   = computed(() => tabs.value.filter(t => tabHasData(t.key)).length)
const progressPercent = computed(() =>
  tabs.value.length ? Math.round((completedTabs.value / tabs.value.length) * 100) : 0
)

// ─── Submit (PUT) ─────────────────────────────────────────────────
const submitting = ref(false)

const handleSubmit = () => {
  const payload: Array<{
    class_id: string; section_id: string | null; subject_id: string
    exam_date: string; start_time: string; end_time: string; room_no: string
    max_theory_marks: string; max_practical_marks: string; max_total_marks: string; pass_marks: string
  }> = []

  tabs.value.forEach(tab => {
    Object.values(schedules.value[tab.key] || {}).forEach(row => {
      if (row.exam_date) {
        payload.push({ class_id: tab.classId, section_id: tab.sectionId, ...row })
      }
    })
  })

  if (payload.length === 0) {
    toast.error('Please set at least one exam date to continue')
    return
  }

  submitting.value = true
  useForm({ schedules: payload }).put(`/exams/${props.exam.id}/schedule`, {
    onSuccess: () => {
      toast.success('Schedule updated successfully!')
      router.visit(`/exams/${props.exam.id}/schedule`)
    },
    onError: (errors) => {
      toast.error(Object.values(errors)[0] as string)
    },
    onFinish: () => { submitting.value = false },
  })
}
</script>

<template>
  <Head :title="`Edit Schedule - ${exam.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">

      <!-- Header info bar -->
      <div class="flex flex-wrap items-center gap-4 p-4 bg-muted/50 border rounded-xl">
        <div class="flex items-center gap-2 text-sm">
          <BookOpen class="w-4 h-4 text-primary" />
          <span class="font-semibold">{{ exam.name }}</span>
          <span class="text-xs text-muted-foreground border px-2 py-0.5 rounded-full ml-1">Editing</span>
        </div>
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Calendar class="w-4 h-4" />
          <span>{{ exam.start_date }} → {{ exam.end_date }}</span>
        </div>
        <!-- Progress -->
        <div class="ml-auto flex items-center gap-3">
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <CheckCircle2 class="w-4 h-4 text-green-500" />
            <span>{{ completedTabs }}/{{ tabs.length }} done</span>
          </div>
          <div class="w-24 h-2 bg-muted rounded-full overflow-hidden">
            <div class="h-full bg-primary rounded-full transition-all duration-300"
              :style="{ width: progressPercent + '%' }" />
          </div>
        </div>
      </div>

      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-amber-100 rounded-lg">
                <Pencil class="w-5 h-5 text-amber-600" />
              </div>
              <div>
                <CardTitle class="text-xl font-bold">Edit Exam Schedule</CardTitle>
                <p class="text-sm text-muted-foreground mt-0.5">
                  Modify subject-wise dates and timings — existing values are pre-filled
                </p>
              </div>
            </div>

            <div class="flex gap-2">
              <Button type="button" variant="outline" size="sm" @click="copyToAllSections"
                v-if="currentTabData && otherSectionsCount(currentTabData) > 0">
                <Copy class="mr-1.5 h-3.5 w-3.5" />
                Copy to other sections
              </Button>
              <Button type="button" variant="outline" size="sm" @click="copyToAllClasses"
                v-if="tabs.length > 1">
                <Copy class="mr-1.5 h-3.5 w-3.5" />
                Apply dates & times to all
              </Button>
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-0 p-0">
          <div class="flex h-full min-h-[500px]">

            <!-- Left: sorted class-section tabs -->
            <div class="w-52 border-r shrink-0 py-3">
              <div v-for="(classTabs, classId) in tabsGroupedByClass" :key="classId" class="mb-3">
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 pb-1">
                  {{ classTabs[0].className }}
                </p>
                <button
                  v-for="tab in classTabs" :key="tab.key"
                  class="w-full text-left px-4 py-2 text-sm flex items-center justify-between transition-colors hover:bg-muted/50"
                  :class="activeTab === tab.key
                    ? 'bg-primary/10 text-primary font-semibold border-r-2 border-primary'
                    : 'text-foreground'"
                  @click="activeTab = tab.key">
                  <span>Section {{ tab.sectionName }}</span>
                  <CheckCircle2 v-if="tabHasData(tab.key)" class="w-3.5 h-3.5 text-green-500 shrink-0" />
                </button>
              </div>
            </div>

            <!-- Right: editable table -->
            <div class="flex-1 overflow-auto p-5">
              <div v-if="currentTabData">

                <div class="flex items-center justify-between mb-4">
                  <h3 class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">
                    {{ currentTabData.className }} — Section {{ currentTabData.sectionName }}
                  </h3>
                  <span class="text-xs text-muted-foreground">
                    {{ currentTabData.subjects.length }} subject{{ currentTabData.subjects.length !== 1 ? 's' : '' }}
                  </span>
                </div>

                <div class="rounded-xl border overflow-hidden">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-muted/60 border-b">
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-36">Subject</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-40">Date</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-32">Start</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-32">End</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-24">Room</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-20">Theory</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-20">Practical</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-20">Pass</th>
                        <th class="text-center px-2 py-2.5 font-semibold text-muted-foreground w-10"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="(subj, i) in currentTabData.subjects" :key="subj.id"
                        class="border-b last:border-0 transition-colors"
                        :class="[
                          i % 2 === 0 ? 'bg-background' : 'bg-muted/20',
                          getRow(activeTab, subj.id).exam_date ? 'ring-inset ring-1 ring-primary/10' : ''
                        ]">
                        <td class="px-3 py-2">
                          <div class="font-medium">{{ subj.name }}</div>
                          <div class="text-xs text-muted-foreground">{{ subj.code }}</div>
                        </td>
                        <td class="px-2 py-2">
                          <DatePicker
                            :model-value="getRow(activeTab, subj.id).exam_date"
                            placeholder="Pick date"
                            @update:model-value="(val) => { getRow(activeTab, subj.id).exam_date = formatDate(val) }"
                          />
                        </td>
                        <td class="px-2 py-2">
                          <TimePicker
                            :model-value="getRow(activeTab, subj.id).start_time"
                            placeholder="Start"
                            @update:model-value="(val) => { getRow(activeTab, subj.id).start_time = val }"
                          />
                        </td>
                        <td class="px-2 py-2">
                          <TimePicker
                            :model-value="getRow(activeTab, subj.id).end_time"
                            placeholder="End"
                            @update:model-value="(val) => { getRow(activeTab, subj.id).end_time = val }"
                          />
                        </td>
                        <td class="px-2 py-2">
                          <Input v-model="getRow(activeTab, subj.id).room_no"
                            placeholder="Room" class="h-8 text-xs" />
                        </td>
                        <td class="px-2 py-2">
                          <Input type="number" v-model="getRow(activeTab, subj.id).max_theory_marks"
                            class="h-8 text-xs" min="0"
                            @input="updateTotalMarks(activeTab, subj.id)" />
                        </td>
                        <td class="px-2 py-2">
                          <Input type="number" v-model="getRow(activeTab, subj.id).max_practical_marks"
                            class="h-8 text-xs" min="0"
                            @input="updateTotalMarks(activeTab, subj.id)" />
                        </td>
                        <td class="px-2 py-2">
                          <Input type="number" v-model="getRow(activeTab, subj.id).pass_marks"
                            class="h-8 text-xs" min="0" />
                        </td>
                        <td class="px-1 py-2 text-center">
                          <Button
                            v-if="i < currentTabData.subjects.length - 1"
                            type="button" variant="ghost" size="sm"
                            class="h-7 w-7 p-0" title="Fill time & room down"
                            @click="fillDown(i)">
                            <ArrowDown class="h-3.5 w-3.5 text-muted-foreground" />
                          </Button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <p class="text-xs text-muted-foreground mt-3">
                  💡 Rows with existing data are pre-filled. Only rows with a date set will be saved.
                  Total marks auto-calculates from Theory + Practical.
                </p>
              </div>

              <div v-else class="flex items-center justify-center h-full text-muted-foreground">
                Select a class-section from the left to begin editing
              </div>
            </div>

          </div>
        </CardContent>

        <!-- Footer -->
        <div class="flex justify-between gap-3 px-6 py-4 border-t">
          <Button type="button" variant="outline"
            @click="router.visit(`/exams/${exam.id}/schedule`)">
            <ArrowLeft class="mr-2 h-4 w-4" />
            Cancel
          </Button>
          <Button type="button" @click="handleSubmit" :disabled="submitting">
            <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
            <Save v-else class="mr-2 h-4 w-4" />
            {{ submitting ? 'Saving...' : 'Update Schedule' }}
          </Button>
        </div>

      </Card>
    </div>
  </AppLayout>
</template>