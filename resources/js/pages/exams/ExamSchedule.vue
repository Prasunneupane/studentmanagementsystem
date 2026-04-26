<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'
import TimePicker from '@/components/ui/timepicker/TimePicker.vue'
import CustomSelect from '../CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import 'vue-sonner/style.css'
import { Loader2, Save, Copy, Calendar, ChevronRight, ChevronDown, BookOpen, CheckCircle2, ArrowDown, Clock } from 'lucide-vue-next'

const { toast } = useToast()

// ─── Types ────────────────────────────────────────────────────────
interface Subject { id: string; name: string; code: string; section_id: string | null }
interface Section { id: string; name: string }
interface ClassWithSections { id: string; name: string; sections: Section[] }

interface ExamClassEntry {
  class_id: string
  section_id: string | null   // null = all sections
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
    id: string
    name: string
    exam_type: string
    start_date: string
    end_date: string
    academic_year_id: string
  }
  examClasses: ExamClassEntry[]
  classes: ClassWithSections[]
  subjectsByClass: Record<string, Subject[]>
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Exams', href: '/exams' },
  { title: props.exam.name, href: `/exams/${props.exam.id}` },
  { title: 'Set Schedule', href: `/exams/${props.exam.id}/schedule` },
]

// ─── Date helper ─────────────────────────────────────────────────
const formatDate = (val: any): string => {
  if (!val) return ''
  const d = val instanceof Date ? val : new Date(val)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// ─── Build flat list of class-section pairs ──────────────────────
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
    
    const cls = props.classes.find(c => Number(c.id) === Number(entry.class_id))
    
    
    if (!cls) return

    if (entry.section_id === null) {
      const subjects = props.subjectsByClass[entry.class_id] || []
      cls.sections.forEach(sec => {
        const sectionSubjects = subjects.filter(s =>
          s.section_id === null || String(s.section_id) === String(sec.id)
        )
       
        
        result.push({
          key: `${cls.id}_${sec.id}`,
          classId: cls.id,
          className: cls.name,
          sectionId: sec.id,
          sectionName: sec.name,
          subjects: sectionSubjects,
        })
      })
    } else {
      const subjects = props.subjectsByClass[entry.class_id] || []
      const sec = cls.sections.find(s => s.id === entry.section_id)
      const sectionSubjects = subjects.filter(s =>
        s.section_id === null || String(s.section_id) === String(entry.section_id)
      )
      
      result.push({
        key: `${cls.id}_${entry.section_id}`,
        classId: cls.id,
        className: cls.name,
        sectionId: entry.section_id,
        sectionName: sec?.name || '',
        subjects: sectionSubjects,
      })
    }
  })
  //console.log(result,"result");
  
  return result
})

const activeTab = ref(tabs.value[0]?.key || '')

// ─── Schedule State ───────────────────────────────────────────────
const schedules = ref<Record<string, Record<string, ScheduleRow>>>({})

// ─── KEY FIX: Ensure a tab + all its subjects are always initialized ──
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

const ensureTabInitialized = (tabKey: string) => {
  const tab = tabs.value.find(t => t.key === tabKey)
  if (!tab) return

  if (!schedules.value[tabKey]) {
    schedules.value[tabKey] = {}
  }

  tab.subjects.forEach(subj => {
    if (!schedules.value[tabKey][subj.id]) {
      schedules.value[tabKey][subj.id] = makeEmptyRow(subj.id)
    }
  })
}

// Initialize all tabs upfront
tabs.value.forEach(tab => ensureTabInitialized(tab.key))

// Re-ensure whenever activeTab changes (guards against any edge case)
watch(activeTab, (newKey) => {
  ensureTabInitialized(newKey)
}, { immediate: true })

// Also guard when tabs computed value changes (e.g. prop updates)
watch(tabs, (newTabs) => {
  newTabs.forEach(tab => ensureTabInitialized(tab.key))
}, { deep: true })

const currentTabData = computed(() => tabs.value.find(t => t.key === activeTab.value))
const currentRows = computed(() => schedules.value[activeTab.value] || {})

// ─── Safe accessor: always returns a valid ScheduleRow ────────────
// Use this in template bindings to prevent "Cannot read properties of undefined"
const getRow = (tabKey: string, subjId: string): ScheduleRow => {
  ensureTabInitialized(tabKey)
  return schedules.value[tabKey][subjId]
}

// ─── Auto-calculate total marks when theory/practical change ─────
const updateTotalMarks = (tabKey: string, subjId: string) => {
  const row = schedules.value[tabKey]?.[subjId]
  if (!row) return
  const theory = parseFloat(row.max_theory_marks) || 0
  const practical = parseFloat(row.max_practical_marks) || 0
  row.max_total_marks = String(theory + practical)
}

// ─── Copy schedule to all sections of same class ─────────────────
const copyToAllSections = () => {
  const tab = currentTabData.value
  if (!tab) return

  const sourceRows = schedules.value[tab.key]
  let count = 0

  tabs.value.forEach(t => {
    if (t.classId === tab.classId && t.key !== tab.key) {
      ensureTabInitialized(t.key)
      schedules.value[t.key] = JSON.parse(JSON.stringify(sourceRows))
      // Preserve correct subject_id for each subject in the target tab
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

  if (count > 0) toast.success(`Schedule copied to ${count} other section${count > 1 ? 's' : ''}`)
  else toast.info('No other sections in this class to copy to')
}

const copyToAllClasses = () => {
  const tab = currentTabData.value
  if (!tab) return

  const sourceRows = schedules.value[tab.key]
  let count = 0

  tabs.value.forEach(t => {
    if (t.key !== tab.key) {
      ensureTabInitialized(t.key)
      t.subjects.forEach((subj, idx) => {
        const src = Object.values(sourceRows)[idx] || Object.values(sourceRows)[0]
        schedules.value[t.key][subj.id] = {
          ...schedules.value[t.key][subj.id],
          exam_date: src?.exam_date || '',
          start_time: src?.start_time || '',
          end_time: src?.end_time || '',
          room_no: src?.room_no || '',
        }
      })
      count++
    }
  })

  if (count > 0) toast.success(`Schedule applied to ${count} other tabs`)
}

// ─── Fill down ───────────────────────────────────────────────────
const fillDown = (fromIndex: number) => {
  const tab = currentTabData.value
  if (!tab) return

  const fromSubj = tab.subjects[fromIndex]
  const src = schedules.value[tab.key][fromSubj.id]

  for (let i = fromIndex + 1; i < tab.subjects.length; i++) {
    const subj = tab.subjects[i]
    const row = schedules.value[tab.key][subj.id]
    if (row) {
      row.start_time = src.start_time
      row.end_time = src.end_time
      row.room_no = src.room_no
    }
  }
  toast.success(`Time & room filled down to ${tab.subjects.length - fromIndex - 1} row(s)`)
}

// ─── Submit ───────────────────────────────────────────────────────
const submitting = ref(false)

const handleSubmit = async () => {
  const payload: Array<{
    class_id: string
    section_id: string | null
    subject_id: string
    exam_date: string
    start_time: string
    end_time: string
    room_no: string
    max_theory_marks: string
    max_practical_marks: string
    max_total_marks: string
    pass_marks: string
  }> = []

  tabs.value.forEach(tab => {
    const rows = schedules.value[tab.key] || {}
    Object.values(rows).forEach(row => {
      if (row.exam_date) {
        payload.push({
          class_id: tab.classId,
          section_id: tab.sectionId,
          ...row,
        })
      }
    })
  })

  if (payload.length === 0) {
    toast.error('Please set at least one exam date to continue')
    return
  }

  submitting.value = true
  useForm({ schedules: payload }).post(`/exams/${props.exam.id}/schedule`, {
    onSuccess: () => {
      toast.success('Schedule saved successfully!')
      router.visit(`/exams/${props.exam.id}`)
    },
    onError: (errors) => {
      toast.error(Object.values(errors)[0] as string)
    },
    onFinish: () => { submitting.value = false },
  })
}

// ─── Helpers ──────────────────────────────────────────────────────
const otherSectionsCount = (tab: ClassSectionTab) =>
  tabs.value.filter(t => t.classId === tab.classId && t.key !== tab.key).length

const tabsGroupedByClass = computed(() => {
  const groups: Record<string, ClassSectionTab[]> = {}
  tabs.value.forEach(tab => {
    if (!groups[tab.classId]) groups[tab.classId] = []
    groups[tab.classId].push(tab)
  })
  return groups
})

const tabHasData = (key: string) => {
  const rows = schedules.value[key] || {}
  return Object.values(rows).some(r => r.exam_date)
}

// ─── Progress ────────────────────────────────────────────────────
const completedTabs = computed(() => tabs.value.filter(t => tabHasData(t.key)).length)
const progressPercent = computed(() => tabs.value.length ? Math.round((completedTabs.value / tabs.value.length) * 100) : 0)
</script>

<template>
  <Head :title="`Schedule - ${exam.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">

      <!-- Header Info Bar -->
      <div class="flex flex-wrap items-center gap-4 p-4 bg-muted/50 border rounded-xl">
        <div class="flex items-center gap-2 text-sm">
          <BookOpen class="w-4 h-4 text-primary" />
          <span class="font-semibold">{{ exam.name }}</span>
        </div>
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Calendar class="w-4 h-4" />
          <span>{{ exam.start_date }} → {{ exam.end_date }}</span>
        </div>
        <div class="ml-auto flex items-center gap-3">
          <div class="flex items-center gap-2 text-sm text-muted-foreground">
            <CheckCircle2 class="w-4 h-4 text-green-500" />
            <span>{{ completedTabs }}/{{ tabs.length }} done</span>
          </div>
          <div class="w-24 h-2 bg-muted rounded-full overflow-hidden">
            <div class="h-full bg-primary rounded-full transition-all duration-300" :style="{ width: progressPercent + '%' }" />
          </div>
        </div>
      </div>

      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader class="border-b">
          <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-primary/10 rounded-lg">
                <Calendar class="w-5 h-5 text-primary" />
              </div>
              <div>
                <CardTitle class="text-xl font-bold">Set Exam Schedule</CardTitle>
                <p class="text-sm text-muted-foreground mt-0.5">
                  Set subject-wise dates and timings per class-section
                </p>
              </div>
            </div>

            <div class="flex gap-2">
              <Button type="button" variant="outline" size="sm" @click="copyToAllSections"
                v-if="currentTabData && otherSectionsCount(currentTabData) > 0">
                <Copy class="mr-1.5 h-3.5 w-3.5" />
                Copy to other sections
              </Button>
              <Button type="button" variant="outline" size="sm" @click="copyToAllClasses" v-if="tabs.length > 1">
                <Copy class="mr-1.5 h-3.5 w-3.5" />
                Apply dates & times to all
              </Button>
            </div>
          </div>
        </CardHeader>

        <CardContent class="pt-0 p-0">
          <div class="flex h-full min-h-[500px]">

            <!-- Left: Tab List -->
            <div class="w-52 border-r shrink-0 py-3">
              <div v-for="(classTabs, classId) in tabsGroupedByClass" :key="classId" class="mb-3">
                <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wider px-4 pb-1">
                  {{ classTabs[0].className }}
                </p>
                <button v-for="tab in classTabs" :key="tab.key"
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

            <!-- Right: Schedule Table -->
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
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-20 hidden">Theory</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-20 hidden">Practical</th>
                        <th class="text-left px-3 py-2.5 font-semibold text-muted-foreground w-20 hidden">Pass</th>
                        <th class="text-center px-2 py-2.5 font-semibold text-muted-foreground w-10"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <!--
                        KEY FIX: Use getRow(activeTab, subj.id) instead of
                        schedules[activeTab][subj.id] to guarantee the row
                        object always exists before Vue renders the cell.
                      -->
                      <tr v-for="(subj, i) in currentTabData.subjects" :key="subj.id"
                        class="border-b last:border-0 transition-colors"
                        :class="i % 2 === 0 ? 'bg-background' : 'bg-muted/20'">
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
                          <Input
                            v-model="getRow(activeTab, subj.id).room_no"
                            placeholder="Room"
                            class="h-8 text-xs"
                          />
                        </td>
                        <td class="px-2 py-2 hidden">
                          <Input
                            type="number"
                            v-model="getRow(activeTab, subj.id).max_theory_marks"
                            class="h-8 text-xs"
                            min="0"
                            
                            @input="updateTotalMarks(activeTab, subj.id)"
                          />
                        </td>
                        <td class="px-2 py-2 hidden">
                          <Input
                            type="number"
                            v-model="getRow(activeTab, subj.id).max_practical_marks"
                            class="h-8 text-xs"
                            min="0"
                            @input="updateTotalMarks(activeTab, subj.id)"
                          />
                        </td>
                        <td class="px-2 py-2 hidden">
                          <Input
                            type="number"
                            v-model="getRow(activeTab, subj.id).pass_marks"
                            class="h-8 text-xs"
                            min="0"
                          />
                        </td>
                        <td class="px-1 py-2 text-center">
                          <Button
                            v-if="i < currentTabData.subjects.length - 1"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-7 w-7 p-0"
                            title="Fill time & room down"
                            @click="fillDown(i)"
                          >
                            <ArrowDown class="h-3.5 w-3.5 text-muted-foreground" />
                          </Button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <p class="text-xs text-muted-foreground mt-3">
                  💡 Only rows with a date set will be saved. Leave blank to skip a subject. Total marks auto-calculates from Theory + Practical.
                </p>
              </div>

              <div v-else class="flex items-center justify-center h-full text-muted-foreground">
                Select a class-section from the left to begin
              </div>
            </div>

          </div>
        </CardContent>

        <!-- Footer -->
        <div class="flex justify-between gap-3 px-6 py-4 border-t">
          <Button type="button" variant="outline" @click="router.visit('/exams')">
            Skip for now
          </Button>
          <Button type="button" @click="handleSubmit" :disabled="submitting">
            <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
            <Save v-else class="mr-2 h-4 w-4" />
            {{ submitting ? 'Saving...' : 'Save Schedule' }}
          </Button>
        </div>

      </Card>
    </div>
  </AppLayout>
</template>