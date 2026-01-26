<script setup lang="ts">
import { h, ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import CustomSelect from '@/components/CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import { Edit, Trash2, Plus, Filter } from 'lucide-vue-next'
import DataTable from '@/components/DataTable.vue'
import type { ColumnDef } from '@tanstack/vue-table'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import axios from 'axios'

const { toast } = useToast()

interface Assignment {
  id: number
  class_id: number
  class_name: string
  section_id: number
  section_name: string
  subject_id: number
  subject_name: string
  teacher_id: number | null
  teacher_name: string
  academic_year_id: number
  academic_year_name: string
  is_optional: boolean
  periods_per_week: number
  max_marks: number
  pass_marks: number
}

interface Option {
  value: string
  label: string
}

interface Props {
  assignments: Assignment[]
  classes: Option[]
  academicYears: Option[]
  currentAcademicYearId: number
  filters: {
    class_id?: number
    section_id?: number
  }
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Class Subjects', href: '/class-subjects' }
]

const selectedAcademicYear = ref<Option | null>(
  props.academicYears.find(y => y.value === String(props.currentAcademicYearId)) || null
)
const selectedClass = ref<Option | null>(null)
const selectedSection = ref<Option | null>(null)
const sections = ref<Option[]>([])

const assignmentToDelete = ref<Assignment | null>(null)
const isDeleteDialogOpen = ref(false)

// Filter assignments
const filteredAssignments = computed(() => {
  let result = props.assignments

  if (selectedClass.value) {
    result = result.filter(a => String(a.class_id) === selectedClass.value!.value)
  }

  if (selectedSection.value) {
    result = result.filter(a => String(a.section_id) === selectedSection.value!.value)
  }

  return result
})

// Load sections when class changes
watch(selectedClass, async (newClass) => {
  selectedSection.value = null
  sections.value = []
  
  if (!newClass?.value) return
  
  try {
    const response = await axios.get('/class-subjects/sections-by-class', {
      params: { class_id: newClass.value }
    })
    sections.value = response.data
  } catch (error) {
    console.error('Failed to fetch sections:', error)
  }
})

// Reload when academic year changes
watch(selectedAcademicYear, (newYear) => {
  if (newYear) {
    router.get('/class-subjects', {
      academic_year_id: newYear.value,
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  }
})

const handleCreate = () => {
  router.visit('/class-subjects/create')
}

const handleEdit = (assignment: Assignment) => {
  router.visit(`/class-subjects/${assignment.id}/edit`)
}

const handleDelete = (assignment: Assignment) => {
  assignmentToDelete.value = assignment
  isDeleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!assignmentToDelete.value) return

  try {
    await axios.delete(`/class-subjects/${assignmentToDelete.value.id}`)
    toast.success('Assignment deleted successfully')
    
    // Reload page
    router.reload({ only: ['assignments'] })
    
    isDeleteDialogOpen.value = false
    assignmentToDelete.value = null
  } catch (error) {
    toast.error('Failed to delete assignment')
  }
}

const clearFilters = () => {
  selectedClass.value = null
  selectedSection.value = null
}

// Table columns
const columns: ColumnDef<Assignment>[] = [
  {
    accessorKey: 'class_name',
    header: 'Class',
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('class_name'))
  },
  {
    accessorKey: 'section_name',
    header: 'Section',
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('section_name'))
  },
  {
    accessorKey: 'subject_name',
    header: 'Subject',
    cell: ({ row }) => {
      const isOptional = row.original.is_optional
      return h('div', { class: 'flex items-center gap-2' }, [
        h('span', row.getValue('subject_name')),
        isOptional && h(Badge, { variant: 'outline', class: 'text-xs' }, () => 'Optional')
      ])
    }
  },
  {
    accessorKey: 'teacher_name',
    header: 'Teacher',
    cell: ({ row }) => {
      const teacherName = row.getValue('teacher_name') as string
      const isUnassigned = teacherName === 'Unassigned'
      return h('div', { 
        class: isUnassigned ? 'text-muted-foreground italic' : '' 
      }, teacherName)
    }
  },
  {
    accessorKey: 'periods_per_week',
    header: 'Periods/Week',
    cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('periods_per_week'))
  },
  {
    accessorKey: 'max_marks',
    header: 'Max Marks',
    cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('max_marks'))
  },
  {
    accessorKey: 'pass_marks',
    header: 'Pass Marks',
    cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('pass_marks'))
  },
  {
    id: 'actions',
    header: 'Actions',
    enableSorting: false,
    cell: ({ row }) => {
      const assignment = row.original
      return h('div', { class: 'flex items-center gap-2' }, [
        h(Button, {
          variant: 'ghost',
          size: 'sm',
          class: 'h-8 w-8 p-0 cursor-pointer',
          title: 'Edit',
          onClick: () => handleEdit(assignment)
        }, () => h(Edit, { class: 'h-4 w-4' })),
        h(Button, {
          variant: 'ghost',
          size: 'sm',
          class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer',
          title: 'Delete',
          onClick: () => handleDelete(assignment)
        }, () => h(Trash2, { class: 'h-4 w-4' })),
      ])
    },
  },
]
</script>

<template>
  <Head title="Class Subject Assignments" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />
    
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Filters -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-xl font-bold">Subject Assignments</CardTitle>
              <p class="text-sm text-muted-foreground mt-1">
                Manage subject-teacher assignments for classes
              </p>
            </div>
            <Button @click="handleCreate">
              <Plus class="mr-2 h-4 w-4" />
              Assign Subject
            </Button>
          </div>
        </CardHeader>
        
        <CardContent>
          <div class="flex flex-col sm:flex-row items-end gap-4">
            <div class="space-y-2 flex-1 sm:max-w-[250px]">
              <Label>Academic Year</Label>
              <CustomSelect
                v-model="selectedAcademicYear"
                :options="academicYears"
                placeholder="Select Year"
              />
            </div>
            
            <div class="space-y-2 flex-1 sm:max-w-[250px]">
              <Label>Class (Filter)</Label>
              <CustomSelect
                v-model="selectedClass"
                :options="classes"
                placeholder="All Classes"
              />
            </div>
            
            <div class="space-y-2 flex-1 sm:max-w-[250px]">
              <Label>Section (Filter)</Label>
              <CustomSelect
                v-model="selectedSection"
                :options="sections"
                :disabled="!selectedClass"
                placeholder="All Sections"
              />
            </div>
            
            <Button
              variant="outline"
              @click="clearFilters"
              :disabled="!selectedClass && !selectedSection"
            >
              <Filter class="mr-2 h-4 w-4" />
              Clear Filters
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Table -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardContent class="pt-6">
          <DataTable
            :columns="columns"
            :data="filteredAssignments"
            :loading="false"
            title="Subject Assignments"
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>

  <!-- Delete Confirmation -->
  <AlertDialog v-model:open="isDeleteDialogOpen">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Delete Assignment?</AlertDialogTitle>
        <AlertDialogDescription>
          This action cannot be undone. This will permanently remove the subject assignment.
          <div v-if="assignmentToDelete" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md text-sm">
            <strong>{{ assignmentToDelete.subject_name }}</strong><br />
            Class: {{ assignmentToDelete.class_name }} - {{ assignmentToDelete.section_name }}<br />
            Teacher: {{ assignmentToDelete.teacher_name }}
          </div>
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
        <AlertDialogAction @click="confirmDelete" class="bg-red-600 hover:bg-red-700">
          <Trash2 class="mr-2 h-4 w-4" />
          Delete
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<style scoped>
button {
  cursor: pointer !important;
}
</style>