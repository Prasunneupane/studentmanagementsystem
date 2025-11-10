<script setup lang="ts">
import { h, ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Loader2, Edit, Trash2, Eye, Plus, Pencil } from 'lucide-vue-next'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { useLocationData, type Student } from '@/composables/fetchData'
import DataTable from '../students/Datatable.vue'
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'
import type { ColumnDef } from '@tanstack/vue-table'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'

// Breadcrumbs
const breadcrumbs = [{ title: 'View Students', href: '/students' }]

// Date form
const form = useForm({
  fromDate: new Date().toISOString().split('T')[0],
  toDate: new Date().toISOString().split('T')[0],
})

const selectedStudent = ref<Student | null>(null)
const isDialogOpen = ref(false)
const studentToDelete = ref<Student | null>(null)
const isDeleteDialogOpen = ref(false)

// Guardian state
const guardians = ref<any[]>([])
const loadingGuardians = ref(false)
const isGuardianModalOpen = ref(false)
const editingGuardian = ref<any | null>(null)
const guardianFormProcessing = ref(false)
const guardianErrors = ref<Record<string, string>>({})

// Tabs
const activeTab = ref<'student' | 'guardians'>('student')

// Composable
const {
  students,
  loading,
  fetchStudentListByDateRange,
  removeStudent,
  getGuardiansByStudentId,
  createGuardian,
  updateGuardian,
  deleteGuardian,
} = useLocationData(form)

// DatePicker
const fromDateValue = computed(() => {
  if (!form.fromDate) return undefined
  const [y, m, d] = form.fromDate.split('-').map(Number)
  return new Date(y, m - 1, d)
})

const toDateValue = computed(() => {
  if (!form.toDate) return new Date()
  const [y, m, d] = form.toDate.split('-').map(Number)
  return new Date(y, m - 1, d)
})

const handleFromDateUpdate = (date: Date | undefined) => {
  form.fromDate = date ? date.toISOString().split('T')[0] : ''
}

const handleToDateUpdate = (date: Date | undefined) => {
  form.toDate = date ? date.toISOString().split('T')[0] : new Date().toISOString().split('T')[0]
}

// Load guardians
watch(isDialogOpen, async (open) => {
  if (open && selectedStudent.value) {
    activeTab.value = 'student'
    await loadGuardians(selectedStudent.value.id)
  } else {
    guardians.value = []
  }
})

const loadGuardians = async (studentId: number) => {
  loadingGuardians.value = true
  try {
    const response = await getGuardiansByStudentId(studentId)
    guardians.value = response.guardians || []
  } catch {
    toast.error('Failed to load guardians')
  } finally {
    loadingGuardians.value = false
  }
}

// Guardian Modal
const openGuardianModal = (guardian?: any) => {
  editingGuardian.value = guardian
    ? { ...guardian }
    : {
        name: '',
        relation: '',
        phone: '',
        email: '',
        occupation: '',
        address: '',
        is_primary_contact: false,
      }
  guardianErrors.value = {}
  isGuardianModalOpen.value = true
}

const closeGuardianModal = () => {
  isGuardianModalOpen.value = false
  editingGuardian.value = null
}

const validateGuardian = () => {
  guardianErrors.value = {}
  if (!editingGuardian.value?.name?.trim()) {
    guardianErrors.value.name = 'Name is required'
  }
  return Object.keys(guardianErrors.value).length === 0
}

const submitGuardian = async () => {
  if (!validateGuardian() || !selectedStudent.value) return

  guardianFormProcessing.value = true
  try {
    if (editingGuardian.value.id) {
      await updateGuardian(editingGuardian.value.id, editingGuardian.value)
      toast.success('Guardian updated successfully')
    } else {
      await createGuardian(selectedStudent.value.id, editingGuardian.value)
      toast.success('Guardian added successfully')
    }
    await loadGuardians(selectedStudent.value.id)
    closeGuardianModal()
  } catch {
    toast.error('Failed to save guardian')
  } finally {
    guardianFormProcessing.value = false
  }
}

const removeGuardian = async (id: number) => {
  if (!confirm('Delete this guardian?')) return
  try {
    await deleteGuardian(id)
    guardians.value = guardians.value.filter(g => g.id !== id)
    toast.success('Guardian deleted')
  } catch {
    toast.error('Failed to delete')
  }
}

// Student actions
const handleView = (student: Student) => {
  selectedStudent.value = student
  isDialogOpen.value = true
}

const handleEdit = () => {
  alert('Edit student functionality')
}

const handleDelete = (student: Student) => {
  studentToDelete.value = student
  isDeleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!studentToDelete.value) return
  const success = await removeStudent(studentToDelete.value.id)
  if (success) {
    isDeleteDialogOpen.value = false
    studentToDelete.value = null
  }
}

// Table columns
const columns: ColumnDef<Student>[] = [
  { accessorKey: 'id', header: 'ID', cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')) },
  { accessorKey: 'first_name', header: 'First Name' },
  { accessorKey: 'last_name', header: 'Last Name' },
  { accessorKey: 'email', header: 'Email', cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('email')) },
  { accessorKey: 'phone', header: 'Phone' },
  { accessorKey: 'age', header: 'Age', cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('age')) },
  { accessorKey: 'class_name', header: 'Class' },
  { accessorKey: 'joined_date', header: 'Joined Date' },
  {
    id: 'actions',
    header: 'Actions',
    enableSorting: false,
    cell: ({ row }) => {
      const student = row.original
      return h('div', { class: 'flex items-center gap-2' }, [
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0', title: 'View', onClick: () => handleView(student) }, () => h(Eye, { class: 'h-4 w-4' })),
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0', title: 'Edit', onClick: () => handleEdit() }, () => h(Edit, { class: 'h-4 w-4' })),
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50', title: 'Delete', onClick: () => handleDelete(student) }, () => h(Trash2, { class: 'h-4 w-4' })),
      ])
    },
  },
]
</script>

<template>
  <Head title="View Students" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Date Picker -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">Student List</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col sm:flex-row items-end gap-4 mb-6">
            <div class="space-y-2 flex-1 sm:max-w-[250px]">
              <Label>From Date</Label>
              <DatePicker :model-value="fromDateValue" @update:model-value="handleFromDateUpdate" month-year-selector placeholder="From" />
            </div>
            <div class="space-y-2 flex-1 sm:max-w-[250px]">
              <Label>To Date</Label>
              <DatePicker :model-value="toDateValue" @update:model-value="handleToDateUpdate" month-year-selector placeholder="To" />
            </div>
            <Button :disabled="loading" @click="fetchStudentListByDateRange">
              <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
              {{ loading ? 'Loading...' : 'Load' }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Students Table -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardContent class="pt-6">
          <DataTable :columns="columns" :data="students" :loading="loading" title="Student List Report" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>

  <!-- LARGE MODAL (Bootstrap modal-lg style) -->
  <Dialog v-model:open="isDialogOpen">
    <DialogContent class="max-w-5xl max-h-[90vh] p-0 overflow-hidden">
      <DialogHeader class="sticky top-0 z-10 bg-white border-b p-6">
        <DialogTitle class="text-2xl font-bold">Student Profile</DialogTitle>
        <DialogDescription class="text-lg">
          {{ selectedStudent?.first_name }} {{ selectedStudent?.last_name }}
        </DialogDescription>
      </DialogHeader>

      <Tabs v-model="activeTab" class="flex flex-col h-full">
        <TabsList class="grid w-full grid-cols-2 rounded-none border-b">
          <TabsTrigger value="student">Student Information</TabsTrigger>
          <TabsTrigger value="guardians">Guardians</TabsTrigger>
        </TabsList>

        <div class="flex-1 overflow-y-auto p-6 space-y-8">
          <!-- Student Tab -->
          <TabsContent value="student" class="space-y-8">
            <div class="flex items-center gap-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6">
              <Avatar class="h-24 w-24">
                <AvatarImage :src="selectedStudent?.photo_url" />
                <AvatarFallback class="text-2xl font-bold">
                  {{ selectedStudent?.first_name?.[0] }}{{ selectedStudent?.last_name?.[0] }}
                </AvatarFallback>
              </Avatar>
              <div>
                <h3 class="text-2xl font-bold">
                  {{ selectedStudent?.first_name }}
                  {{ selectedStudent?.middle_name ? ' ' + selectedStudent.middle_name : '' }}
                  {{ selectedStudent?.last_name }}
                </h3>
                <div class="flex gap-3 mt-2">
                  <Badge>{{ selectedStudent?.class_name ?? '—' }}</Badge>
                  <Badge variant="outline">{{ selectedStudent?.section_name ?? '—' }}</Badge>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <h4 class="font-semibold text-gray-700 mb-3">Personal Details</h4>
                <dl class="space-y-2 text-sm">
                  <div class="flex justify-between"><dt class="text-gray-600">ID</dt><dd>#{{ selectedStudent?.id }}</dd></div>
                  <div class="flex justify-between"><dt class="text-gray-600">Email</dt><dd class="lowercase">{{ selectedStudent?.email ?? '—' }}</dd></div>
                  <div class="flex justify-between"><dt class="text-gray-600">Phone</dt><dd>{{ selectedStudent?.phone ?? '—' }}</dd></div>
                  <div class="flex justify-between"><dt class="text-gray-600">Age</dt><dd>{{ selectedStudent?.age ?? '—' }} yrs</dd></div>
                  <div class="flex justify-between"><dt class="text-gray-600">DOB</dt><dd>{{ selectedStudent?.date_of_birth ?? '—' }}</dd></div>
                  <div class="flex justify-between"><dt class="text-gray-600">Joined</dt><dd>{{ selectedStudent?.joined_date ?? '—' }}</dd></div>
                </dl>
              </div>
              <div>
                <h4 class="font-semibold text-gray-700 mb-3">Address</h4>
                <p class="text-sm">{{ selectedStudent?.address || 'No address provided' }}</p>
                <div class="flex flex-wrap gap-2 mt-3">
                  <Badge v-if="selectedStudent?.state_name" variant="outline">{{ selectedStudent.state_name }}</Badge>
                  <Badge v-if="selectedStudent?.district_name" variant="outline">{{ selectedStudent.district_name }}</Badge>
                  <Badge v-if="selectedStudent?.municipality_name" variant="outline">{{ selectedStudent.municipality_name }}</Badge>
                </div>
              </div>
            </div>
          </TabsContent>

          <!-- Guardians Tab -->
          <TabsContent value="guardians">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-semibold">Guardians</h3>
              <Button size="sm" @click="openGuardianModal()">
                <Plus class="h-4 w-4 mr-1" /> Add Guardian
              </Button>
            </div>

            <div class="border rounded-lg overflow-hidden">
              <table class="w-full text-sm">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left">Name</th>
                    <th class="px-4 py-3 text-left">Relation</th>
                    <th class="px-4 py-3 text-left">Phone</th>
                    <th class="px-4 py-3 text-left">Primary</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingGuardians">
                    <td colspan="5" class="text-center py-10">
                      <Loader2 class="h-8 w-8 animate-spin mx-auto" />
                    </td>
                  </tr>
                  <tr v-else-if="guardians.length === 0">
                    <td colspan="5" class="text-center py-10 text-gray-500">No guardians added</td>
                  </tr>
                  <tr v-for="g in guardians" :key="g.id" class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">{{ g.name }}</td>
                    <td class="px-4 py-3">{{ g.relation || '—' }}</td>
                    <td class="px-4 py-3">{{ g.phone || '—' }}</td>
                    <td class="px-4 py-3">
                      <span v-if="g.is_primary_contact" class="text-green-600 font-medium">Yes</span>
                      <span v-else>—</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <Button variant="ghost" size="icon" @click="openGuardianModal(g)">
                        <Pencil class="h-4 w-4" />
                      </Button>
                      <Button variant="ghost" size="icon" @click="removeGuardian(g.id)" class="text-red-600">
                        <Trash2 class="h-4 w-4" />
                      </Button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </TabsContent>
        </div>
      </Tabs>

      <div class="sticky bottom-0 bg-white border-t p-4 flex justify-end gap-3">
        <Button variant="outline" @click="isDialogOpen = false">Close</Button>
        <Button @click="handleEdit">
          <Edit class="mr-2 h-4 w-4" /> Edit Student
        </Button>
      </div>
    </DialogContent>
  </Dialog>

  <!-- LARGE Guardian Form Modal (Bootstrap modal-lg) -->
  <Dialog v-model:open="isGuardianModalOpen">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle class="text-2xl">{{ editingGuardian?.id ? 'Edit' : 'Add' }} Guardian</DialogTitle>
      </DialogHeader>
      <form @submit.prevent="submitGuardian" class="space-y-6">
        <div>
          <Label>Name <span class="text-red-500">*</span></Label>
          <Input v-model="editingGuardian.name" placeholder="Full name" />
          <p v-if="guardianErrors.name" class="text-sm text-red-600 mt-1">{{ guardianErrors.name }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><Label>Relation</Label><Input v-model="editingGuardian.relation" placeholder="Father, Mother..." /></div>
          <div><Label>Phone</Label><Input v-model="editingGuardian.phone" placeholder="98xxxxxxxx" /></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><Label>Email</Label><Input v-model="editingGuardian.email" type="email" /></div>
          <div><Label>Occupation</Label><Input v-model="editingGuardian.occupation" /></div>
        </div>
        <div><Label>Address</Label><Input v-model="editingGuardian.address" placeholder="Full address" /></div>
        <div class="flex items-center gap-3">
          <input type="checkbox" v-model="editingGuardian.is_primary_contact" class="h-4 w-4" />
          <Label class="font-normal cursor-pointer">Primary Contact</Label>
        </div>
        <DialogFooter>
          <Button type="button" variant="outline" @click="closeGuardianModal">Cancel</Button>
          <Button type="submit" :disabled="guardianFormProcessing">
            <Loader2 v-if="guardianFormProcessing" class="mr-2 h-4 w-4 animate-spin" />
            Save Guardian
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>

  <!-- Delete Confirmation -->
  <AlertDialog v-model:open="isDeleteDialogOpen">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Delete Student?</AlertDialogTitle>
        <AlertDialogDescription>
          This action cannot be undone.
          <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md text-sm">
            <strong>{{ studentToDelete?.first_name }} {{ studentToDelete?.last_name }}</strong><br />
            ID: #{{ studentToDelete?.id }}
          </div>
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
        <AlertDialogAction @click="confirmDelete" class="bg-red-600 hover:bg-red-700">
          <Trash2 class="mr-2 h-4 w-4" /> Delete
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<style scoped>
button { cursor: pointer !important; }
</style>