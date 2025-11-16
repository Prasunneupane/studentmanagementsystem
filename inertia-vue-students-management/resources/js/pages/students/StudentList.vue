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
import { useStudentData, type Student } from '@/composables/fetchData'
import { useLocationData } from '@/composables/useLocationData'
import { useAcademicData } from '@/composables/useAcademicData'
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
import SelectSearch from '@/components/ui/select/Select-Search.vue'

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
const isDeleteGuardianOpen = ref(false)

// Guardian state
const guardians = ref<any[]>([])
const loadingGuardians = ref(false)
const isGuardianModalOpen = ref(false)
const editingGuardian = ref<any | null>(null)
const guardianFormProcessing = ref(false)
const guardianErrors = ref<Record<string, string>>({})

// Edit Student Modal
const isEditModalOpen = ref(false)
const loadingEdit = ref(false)                     // <-- NEW
const editForm = ref<any>({
  id: null,
  fName: '',
  mName: '',
  lName: '',
  email: '',
  phone: '',
  age: '',
  dateOfBirth: '',
  classId: '',
  sectionId: '',
  contactNumber: '',
  joinedDate: '',
  address: '',
  stateId: '',
  districtId: '',
  municipalityId: '',
  photo: null,
})
const editFormProcessing = ref(false)
const editValidationErrors = ref<Record<string, string>>({})

// Tabs
const activeTab = ref<'student' | 'guardians'>('student')

// Composables
const {
  students,
  loading,
  fetchStudentListByDateRange,
  removeStudent,
  getGuardiansByStudentId,
  createGuardian,
  updateGuardianByGuardianId,
  deleteGuardian,
} = useStudentData(form)

const {
  states,
  districts,
  municipalities,
  fetchDistricts,
  fetchMunicipalities,
} = useLocationData(editForm.value)

const { classes, sections } = useAcademicData()

// -------------------------------------------------------------------
// DatePicker helpers (unchanged)
// -------------------------------------------------------------------
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
  if (!date) { form.fromDate = ''; return }
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  form.fromDate = `${year}-${month}-${day}`
}
const handleToDateUpdate = (date: Date | undefined) => {
  if (!date) { form.toDate = new Date().toISOString().split('T')[0]; return }
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  form.toDate = `${year}-${month}-${day}`
}

// Edit form date values
const editDateOfBirthValue = computed(() => {
  if (!editForm.value.dateOfBirth) return null
  const [y, m, d] = editForm.value.dateOfBirth.split('-').map(Number)
  return new Date(y, m - 1, d)
})
const editJoinedDateValue = computed(() => {
  if (!editForm.value.joinedDate) return null
  const [y, m, d] = editForm.value.joinedDate.split('-').map(Number)
  return new Date(y, m - 1, d)
})
const handleEditDateOfBirthUpdate = (date: Date | null) => {
  if (!date) { editForm.value.dateOfBirth = ''; return }
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  editForm.value.dateOfBirth = `${year}-${month}-${day}`
}
const handleEditJoinedDateUpdate = (date: Date | null) => {
  if (!date) { editForm.value.joinedDate = ''; return }
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  editForm.value.joinedDate = `${year}-${month}-${day}`
}

// -------------------------------------------------------------------
// Location & academic watchers (unchanged)
// -------------------------------------------------------------------
watch(() => editForm.value.stateId, async (newId) => {
  if (newId) await fetchDistricts(newId)
  else { editForm.value.districtId = ''; editForm.value.municipalityId = '' }
})
watch(() => editForm.value.districtId, async (newId) => {
  if (newId) await fetchMunicipalities(newId)
  else editForm.value.municipalityId = ''
})

// -------------------------------------------------------------------
// Guardian loading (unchanged)
// -------------------------------------------------------------------
watch(isDialogOpen, async (open) => {
  if (open && selectedStudent.value) {
    activeTab.value = 'student'
    await loadGuardians(selectedStudent.value.id)
  } else guardians.value = []
})
const loadGuardians = async (studentId: number) => {
  loadingGuardians.value = true
  try {
    const { guardians: g } = await getGuardiansByStudentId(studentId)
    guardians.value = g || []
  } finally { loadingGuardians.value = false }
}

// -------------------------------------------------------------------
// Guardian CRUD (unchanged)
// -------------------------------------------------------------------
const openGuardianModal = (guardian?: any) => {
  editingGuardian.value = guardian
    ? { ...guardian, is_primary_contact: Boolean(Number(guardian.is_primary_contact)) }
    : { name: '', relation: '', phone: '', email: '', occupation: '', address: '', is_primary_contact: false }
  guardianErrors.value = {}
  isGuardianModalOpen.value = true
}
const closeGuardianModal = () => { isGuardianModalOpen.value = false; editingGuardian.value = null }
const validateGuardian = () => {
  guardianErrors.value = {}
  if (!editingGuardian.value?.name?.trim()) guardianErrors.value.name = 'Name is required'
  return Object.keys(guardianErrors.value).length === 0
}
const submitGuardian = async () => {
  if (!validateGuardian() || !selectedStudent.value) return
  guardianFormProcessing.value = true
  try {
    let result
    if (editingGuardian.value.id) {
      result = await updateGuardianByGuardianId(editingGuardian.value.id, editingGuardian.value)
    } else {
      result = await createGuardian(selectedStudent.value.id, editingGuardian.value)
    }
    if (result) {
      await loadGuardians(selectedStudent.value.id)
      setTimeout(closeGuardianModal, 100)
    }
  } finally { guardianFormProcessing.value = false }
}
const handleGuardianDelete = (g: any) => { editingGuardian.value = g; isDeleteGuardianOpen.value = true }
const removeGuardian = async () => {
  if (!editingGuardian.value?.id) return
  const ok = await deleteGuardian(editingGuardian.value.id)
  if (ok) {
    guardians.value = guardians.value.filter(g => g.id !== editingGuardian.value.id)
    isDeleteGuardianOpen.value = false
    editingGuardian.value = null
  }
}

// -------------------------------------------------------------------
// Student actions
// -------------------------------------------------------------------
const handleView = (student: Student) => {
  selectedStudent.value = student
  isDialogOpen.value = true
}

/* ----------  EDIT MODAL – LAZY LOAD ---------- */
const startEdit = async (student: Student) => {
  selectedStudent.value = student               // keep reference for view modal
  isEditModalOpen.value = true                  // show modal **immediately**
  loadingEdit.value = true
  editForm.value = {                            // reset form
    id: null, fName: '', mName: '', lName: '', email: '', phone: '',
    age: '', dateOfBirth: '', classId: '', sectionId: '', contactNumber: '',
    joinedDate: '', address: '', stateId: '', districtId: '', municipalityId: '',
    photo: null
  }

  try {
    // Populate form
    editForm.value = {
      id: student.id,
      fName: student.first_name || '',
      mName: student.middle_name || '',
      lName: student.last_name || '',
      email: student.email || '',
      phone: student.phone || '',
      age: student.age || '',
      dateOfBirth: student.date_of_birth || '',
      classId: student.class_id || '',
      sectionId: student.section_id || '',
      contactNumber: student.contact_number || '',
      joinedDate: student.joined_date || '',
      address: student.address || '',
      stateId: student.state_id || '',
      districtId: student.district_id || '',
      municipalityId: student.municipality_id || '',
      photo: null,
    }

    // Load dependent dropdowns
    if (student.state_id) await fetchDistricts(student.state_id)
    if (student.district_id) await fetchMunicipalities(student.district_id)
  } finally {
    loadingEdit.value = false
  }
}
const handleEdit = (student?: Student) => {
  const s = student || selectedStudent.value
  if (s) startEdit(s)
}

/* ----------  FORM VALIDATION & SUBMIT ---------- */
const validateEditForm = () => {
  editValidationErrors.value = {}
  if (!editForm.value.fName?.trim()) editValidationErrors.value.fName = 'First name is required'
  if (!editForm.value.lName?.trim()) editValidationErrors.value.lName = 'Last name is required'
  if (!editForm.value.phone?.trim()) editValidationErrors.value.phone = 'Phone is required'
  else if (editForm.value.phone.length !== 10) editValidationErrors.value.phone = 'Phone must be 10 digits'
  if (!editForm.value.age) editValidationErrors.value.age = 'Age is required'
  if (!editForm.value.dateOfBirth) editValidationErrors.value.dateOfBirth = 'Date of birth is required'
  if (!editForm.value.classId) editValidationErrors.value.classId = 'Class is required'
  if (!editForm.value.joinedDate) editValidationErrors.value.joinedDate = 'Joined date is required'
  if (!editForm.value.stateId) editValidationErrors.value.stateId = 'State is required'
  return Object.keys(editValidationErrors.value).length === 0
}

const submitEditForm = async () => {
  if (!validateEditForm()) { toast.error('Please fill all required fields'); return }
  editFormProcessing.value = true
  try {
    const fd = new FormData()
    fd.append('first_name', editForm.value.fName)
    fd.append('middle_name', editForm.value.mName || '')
    fd.append('last_name', editForm.value.lName)
    fd.append('email', editForm.value.email || '')
    fd.append('phone', editForm.value.phone)
    fd.append('age', editForm.value.age.toString())
    fd.append('date_of_birth', editForm.value.dateOfBirth)
    fd.append('class_id', editForm.value.classId)
    fd.append('section_id', editForm.value.sectionId || '')
    fd.append('contact_number', editForm.value.contactNumber || '')
    fd.append('joined_date', editForm.value.joinedDate)
    fd.append('address', editForm.value.address || '')
    fd.append('state_id', editForm.value.stateId)
    fd.append('district_id', editForm.value.districtId || '')
    fd.append('municipality_id', editForm.value.municipalityId || '')
    if (editForm.value.photo) fd.append('photo', editForm.value.photo)

    const ok = await updateStudent(editForm.value.id, fd)
    if (ok) {
      toast.success('Student updated successfully')
      isEditModalOpen.value = false
      await fetchStudentListByDateRange()
    } else toast.error('Failed to update student')
  } catch (e) {
    console.error(e)
    toast.error('Failed to update student')
  } finally { editFormProcessing.value = false }
}

/* ----------  PHOTO HELPERS ---------- */
const photoPreview = computed(() => {
  if (editForm.value.photo) return URL.createObjectURL(editForm.value.photo)
  if (selectedStudent.value?.photo_url) return selectedStudent.value.photo_url
  return null
})
const removePhoto = () => { editForm.value.photo = null }

const handlePhoneInput = (e: Event) => {
  const inp = e.target as HTMLInputElement
  inp.value = inp.value.replace(/\D/g, '').slice(0, 10)
  editForm.value.phone = inp.value
}
const handlePhotoChange = (e: Event) => {
  const inp = e.target as HTMLInputElement
  if (inp.files?.[0]) editForm.value.photo = inp.files[0]
}

/* ----------  CLOSE / DELETE ---------- */
const closeEditModal = () => {
  isEditModalOpen.value = false
  editForm.value = { id: null, fName: '', mName: '', lName: '', email: '', phone: '',
    age: '', dateOfBirth: '', classId: '', sectionId: '', contactNumber: '',
    joinedDate: '', address: '', stateId: '', districtId: '', municipalityId: '',
    photo: null }
  editValidationErrors.value = {}
}
const handleDelete = (student: Student) => {
  studentToDelete.value = student
  isDeleteDialogOpen.value = true
}
const confirmDelete = async () => {
  if (!studentToDelete.value) return
  const ok = await removeStudent(studentToDelete.value.id)
  if (ok) { isDeleteDialogOpen.value = false; studentToDelete.value = null }
}

// -------------------------------------------------------------------
// Table columns (unchanged)
// -------------------------------------------------------------------
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
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0', title: 'Edit', onClick: () => handleEdit(student) }, () => h(Edit, { class: 'h-4 w-4' })),
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

  <!-- VIEW MODAL (unchanged) -->
  <Dialog v-model:open="isDialogOpen">
    <DialogContent class="max-w-[95vw] lg:max-w-[1100px] w-full p-0 overflow-hidden flex flex-col" style="height: 85vh;">
      <!-- ... view modal content unchanged ... -->
      <DialogHeader class="flex-shrink-0 bg-white border-b p-6">
        <DialogTitle class="text-2xl font-bold">Student Profile</DialogTitle>
        <DialogDescription class="text-lg">
          {{ selectedStudent?.first_name }} {{ selectedStudent?.last_name }}
        </DialogDescription>
      </DialogHeader>

      <Tabs v-model="activeTab" class="flex flex-col flex-1 overflow-hidden">
        <TabsList class="flex-shrink-0 grid w-full grid-cols-2 rounded-none border-b">
          <TabsTrigger value="student">Student Information</TabsTrigger>
          <TabsTrigger value="guardians">Guardians</TabsTrigger>
        </TabsList>

        <div class="flex-1 overflow-y-auto p-6">
          <!-- Student Tab (unchanged) -->
          <TabsContent value="student" class="space-y-6 mt-0">
            <!-- ... unchanged ... -->
          </TabsContent>

          <!-- Guardians Tab (unchanged) -->
          <TabsContent value="guardians" class="mt-0">
            <!-- ... unchanged ... -->
          </TabsContent>
        </div>
      </Tabs>

      <div class="flex-shrink-0 bg-white border-t p-4 flex justify-end gap-3">
        <Button variant="outline" @click="isDialogOpen = false">Close</Button>
        <Button @click="handleEdit()">
          <Edit class="mr-2 h-4 w-4" /> Edit Student
        </Button>
      </div>
    </DialogContent>
  </Dialog>

  <!-- ====================== EDIT STUDENT MODAL ====================== -->
  <Dialog v-model:open="isEditModalOpen">
    <DialogContent
      class="max-w-[95vw] lg:max-w-[700px] w-full p-0 overflow-hidden flex flex-col"
      style="height: 85vh;"
    >
      <!-- Header -->
      <DialogHeader class="flex-shrink-0 bg-white border-b p-6">
        <DialogTitle class="text-2xl font-bold">Edit Student</DialogTitle>
        <DialogDescription class="text-lg">
          Update student information
        </DialogDescription>
      </DialogHeader>

      <!-- Scrollable body -->
      <div class="flex-1 overflow-y-auto p-6">
        <!-- Loading spinner while data is fetched -->
        <div v-if="loadingEdit" class="flex flex-col items-center justify-center h-full">
          <Loader2 class="h-12 w-12 animate-spin text-primary" />
          <p class="mt-3 text-muted-foreground">Loading student data…</p>
        </div>

        <!-- Form (only shown after load) -->
        <form v-else @submit.prevent="submitEditForm" class="space-y-6">
          <!-- Photo -->
          <div class="flex items-center gap-6 bg-gray-50 rounded-lg p-4">
            <div class="relative">
              <Avatar class="h-24 w-24">
                <AvatarImage :src="photoPreview || undefined" />
                <AvatarFallback class="text-2xl font-bold">
                  {{ editForm.fName?.[0] || 'S' }}{{ editForm.lName?.[0] || 'T' }}
                </AvatarFallback>
              </Avatar>
              <Button
                v-if="photoPreview"
                type="button"
                variant="ghost"
                size="icon"
                class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-red-500 text-white hover:bg-red-600"
                @click="removePhoto"
              >×</Button>
            </div>
            <div class="flex-1">
              <Label for="edit-photo">Profile Photo</Label>
              <Input id="edit-photo" type="file" accept="image/*" @change="handlePhotoChange" class="mt-2" />
              <p class="text-xs text-gray-500 mt-1">Upload a new photo or keep existing one</p>
            </div>
          </div>

          <!-- Personal Information -->
          <div>
            <h3 class="text-lg font-semibold mb-3 pb-2 border-b">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="edit-fName">First Name <span class="text-red-500">*</span></Label>
                <Input id="edit-fName" v-model="editForm.fName" placeholder="First name"
                       :class="{ 'border-red-500': editValidationErrors.fName }" />
                <p v-if="editValidationErrors.fName" class="text-sm text-red-600">{{ editValidationErrors.fName }}</p>
              </div>
              <div class="space-y-2">
                <Label for="edit-mName">Middle Name</Label>
                <Input id="edit-mName" v-model="editForm.mName" placeholder="Middle name" />
              </div>
              <div class="space-y-2">
                <Label for="edit-lName">Last Name <span class="text-red-500">*</span></Label>
                <Input id="edit-lName" v-model="editForm.lName" placeholder="Last name"
                       :class="{ 'border-red-500': editValidationErrors.lName }" />
                <p v-if="editValidationErrors.lName" class="text-sm text-red-600">{{ editValidationErrors.lName }}</p>
              </div>
            </div>
          </div>

          <!-- Contact Information -->
          <div>
            <h3 class="text-lg font-semibold mb-3 pb-2 border-b">Contact Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="edit-email">Email</Label>
                <Input id="edit-email" v-model="editForm.email" type="email" placeholder="Email" />
              </div>
              <div class="space-y-2">
                <Label for="edit-phone">Phone <span class="text-red-500">*</span></Label>
                <Input id="edit-phone" v-model="editForm.phone" placeholder="10-digit number"
                       :class="{ 'border-red-500': editValidationErrors.phone }"
                       @input="handlePhoneInput" maxlength="10" />
                <p v-if="editValidationErrors.phone" class="text-sm text-red-600">{{ editValidationErrors.phone }}</p>
              </div>
              <div class="space-y-2">
                <Label for="edit-age">Age <span class="text-red-500">*</span></Label>
                <Input id="edit-age" v-model="editForm.age" type="number" min="1" max="100"
                       :class="{ 'border-red-500': editValidationErrors.age }" />
                <p v-if="editValidationErrors.age" class="text-sm text-red-600">{{ editValidationErrors.age }}</p>
              </div>
            </div>
          </div>

          <!-- Academic Information -->
          <div>
            <h3 class="text-lg font-semibold mb-3 pb-2 border-b">Academic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="edit-dateOfBirth">Date of Birth <span class="text-red-500">*</span></Label>
                <DatePicker id="edit-dateOfBirth"
                            :model-value="editDateOfBirthValue"
                            @update:model-value="handleEditDateOfBirthUpdate"
                            month-year-selector />
                <p v-if="editValidationErrors.dateOfBirth" class="text-sm text-red-600">{{ editValidationErrors.dateOfBirth }}</p>
              </div>
              <div class="space-y-2">
                <Label for="edit-classId">Class <span class="text-red-500">*</span></Label>
                <SelectSearch id="edit-classId" v-model="editForm.classId"
                              :options="classes" placeholder="Select Class" />
                <p v-if="editValidationErrors.classId" class="text-sm text-red-600">{{ editValidationErrors.classId }}</p>
              </div>
              <div class="space-y-2">
                <Label for="edit-sectionId">Section</Label>
                <SelectSearch id="edit-sectionId" v-model="editForm.sectionId"
                              :options="sections" placeholder="Select Section" />
              </div>

              <div class="space-y-2">
                <Label for="edit-joinedDate">Joined Date <span class="text-red-500">*</span></Label>
                <DatePicker id="edit-joinedDate"
                            :model-value="editJoinedDateValue"
                            @update:model-value="handleEditJoinedDateUpdate"
                            month-year-selector />
                <p v-if="editValidationErrors.joinedDate" class="text-sm text-red-600">{{ editValidationErrors.joinedDate }}</p>
              </div>
              <div class="space-y-2">
                <Label for="edit-contactNumber">Contact Number</Label>
                <Input id="edit-contactNumber" v-model="editForm.contactNumber" placeholder="Contact number" />
              </div>
            </div>
          </div>

          <!-- Address Information -->
          <div>
            <h3 class="text-lg font-semibold mb-3 pb-2 border-b">Address Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2 md:col-span-3">
                <Label for="edit-address">Address</Label>
                <Input id="edit-address" v-model="editForm.address" placeholder="Full address" />
              </div>

              <div class="space-y-2">
                <Label for="edit-stateId">State <span class="text-red-500">*</span></Label>
                <SelectSearch id="edit-stateId" v-model="editForm.stateId"
                              :options="states" placeholder="Select State" />
                <p v-if="editValidationErrors.stateId" class="text-sm text-red-600">{{ editValidationErrors.stateId }}</p>
              </div>
              <div class="space-y-2">
                <Label for="edit-districtId">District</Label>
                <SelectSearch id="edit-districtId" v-model="editForm.districtId"
                              :options="districts" :disabled="!editForm.stateId"
                              placeholder="Select District" />
              </div>
              <div class="space-y-2">
                <Label for="edit-municipalityId">Municipality</Label>
                <SelectSearch id="edit-municipalityId" v-model="editForm.municipalityId"
                              :options="municipalities" :disabled="!editForm.districtId"
                              placeholder="Select Municipality" />
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="flex-shrink-0 bg-white border-t p-4 flex justify-end gap-3">
        <Button type="button" variant="outline" @click="closeEditModal">Cancel</Button>
        <Button type="button" @click="submitEditForm" :disabled="editFormProcessing || loadingEdit">
          <Loader2 v-if="editFormProcessing" class="mr-2 h-4 w-4 animate-spin" />
          {{ editFormProcessing ? 'Updating...' : 'Update Student' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>

  <!-- GUARDIAN FORM MODAL (unchanged) -->
  <Dialog v-model:open="isGuardianModalOpen">
    <!-- ... unchanged ... -->
  </Dialog>

  <!-- DELETE STUDENT CONFIRMATION (unchanged) -->
  <AlertDialog v-model:open="isDeleteDialogOpen">
    <!-- ... unchanged ... -->
  </AlertDialog>

  <!-- DELETE GUARDIAN CONFIRMATION (unchanged) -->
  <AlertDialog v-model:open="isDeleteGuardianOpen">
    <!-- ... unchanged ... -->
  </AlertDialog>
</template>

<style scoped>
button { cursor: pointer !important; }
.border-red-500 { border-color: rgb(239 68 68) !important; }
.border-red-500:focus { border-color: rgb(239 68 68) !important; box-shadow: 0 0 0 1px rgb(239 68 68) !important; }
</style>