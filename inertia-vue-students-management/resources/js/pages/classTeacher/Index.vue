<script setup lang="ts">
import { h, ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import CustomSelect from '../CustomSelect.vue'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import { Edit, Trash2, Plus, Filter, UserCheck } from 'lucide-vue-next'
import DataTable from '../students/Datatable.vue'
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
  teacher_id: number
  teacher_name: string
  academic_year_id: number
  academic_year_name: string
  is_class_teacher: boolean
  is_active: boolean
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
  { title: 'Class Teachers', href: '/class-teachers' }
]

const selectedAcademicYear = ref<Option | ''>(
  props.academicYears.find(y => String(y.value) === String(props.currentAcademicYearId)) || ''
)
const selectedClass = ref<Option | ''>()
const selectedSection = ref<Option | ''>()
const sections = ref<Option[]>([])

const assignmentToDelete = ref<Assignment | null>(null)
const isDeleteDialogOpen = ref(false)

// Filter assignments
const filteredAssignments = computed(() => {
  let result = props.assignments

  if (selectedClass.value) {
    result = result.filter(a => String(a.class_id) === String(selectedClass.value))
  }

  if (selectedSection.value) {
    result = result.filter(a => String(a.section_id) === String(selectedSection.value))
  }

  return result
})

// Load sections when class changes
watch(selectedClass, async (newClass) => {
  selectedSection.value = ''
  sections.value = []

  if (!newClass) return
  
  try {
    const response = await axios.get('/class-teachers/sections-by-class', {
      params: { class_id: newClass }
    })
    sections.value = response.data
  } catch (error) {
    console.error('Failed to fetch sections:', error)
  }
})

// Reload when academic year changes
watch(selectedAcademicYear, (newYear) => {
  if (newYear) {
    router.get('/class-teachers', {
      academic_year_id: newYear,
    }, {
      preserveState: true,
      preserveScroll: true,
    })
  }
})

const handleCreate = () => {
  router.visit('/class-teachers/create')
}

const handleEdit = (assignment: Assignment) => {
  router.visit(`/class-teachers/${assignment.id}/edit`)
}

const handleDelete = (assignment: Assignment) => {
  assignmentToDelete.value = assignment
  isDeleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!assignmentToDelete.value) return

  try {
    await axios.delete(`/class-teachers/${assignmentToDelete.value.id}`)
    toast.success('Assignment deleted successfully')
    
    router.reload({ only: ['assignments'] })
    
    isDeleteDialogOpen.value = false
    assignmentToDelete.value = null
  } catch (error) {
    toast.error('Failed to delete assignment')
  }
}

const clearFilters = () => {
  selectedClass.value = ''
  selectedSection.value = ''
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
    accessorKey: 'teacher_name',
    header: 'Teacher',
    cell: ({ row }) => {
      const isClassTeacher = row.original.is_class_teacher
      return h('div', { class: 'flex items-center gap-2' }, [
        h('span', { class: 'font-medium' }, row.getValue('teacher_name')),
        isClassTeacher && h(Badge, { variant: 'default', class: 'text-xs' }, () => [
          h(UserCheck, { class: 'h-3 w-3 mr-1' }),
          'Class Teacher'
        ])
      ])
    }
  },
  {
    accessorKey: 'is_active',
    header: 'Status',
    cell: ({ row }) => {
      const isActive = row.getValue('is_active') as boolean
      return h(Badge, { 
        variant: isActive ? 'default' : 'secondary',
        class: 'text-xs'
      }, () => isActive ? 'Active' : 'Inactive')
    }
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
  <Head title="Class Teacher Assignments" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />
    
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Filters -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-xl font-bold">Teacher Assignments</CardTitle>
              <p class="text-sm text-muted-foreground mt-1">
                Manage teacher assignments for classes and sections
              </p>
            </div>
            <Button @click="handleCreate">
              <Plus class="mr-2 h-4 w-4" />
              Assign Teacher
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
            title="Teacher Assignments"
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
          This action cannot be undone. This will permanently remove the teacher assignment.
          <div v-if="assignmentToDelete" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md text-sm">
            <strong>{{ assignmentToDelete.teacher_name }}</strong><br />
            Class: {{ assignmentToDelete.class_name }} - {{ assignmentToDelete.section_name }}<br />
            {{ assignmentToDelete.is_class_teacher ? 'Class Teacher' : 'Subject Teacher' }}
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