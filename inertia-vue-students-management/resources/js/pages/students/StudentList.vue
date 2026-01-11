<script setup lang="ts">
import { h, ref, computed, watch, nextTick } from 'vue'
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
import { useToast } from '../../composables/useToast';
import CustomSelect from '../CustomSelect.vue'
const { toast } = useToast();
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
import {usePermission } from '@/composables/usePermissions'

const { can } = usePermission();
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
const loadingEdit = ref(false)
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
  updateStudent,
} = useStudentData(form)

const {
  states,
  districts,
  municipalities,
  fetchDistricts,
  fetchMunicipalities,
} = useLocationData(editForm.value)

const { classes, sections } = useAcademicData()

// DatePicker helpers
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

// Flag to prevent watch loops during initialization
const isInitializing = ref(false)

// Location & academic watchers - FIXED to prevent infinite loops and handle object values
watch(() => editForm.value.stateId, async (newVal, oldVal) => {
  // Skip if initializing or value hasn't really changed
  if (isInitializing.value) return
  
  // Compare the actual values (objects have .value property)
  const newId = newVal?.value || null
  const oldId = oldVal?.value || null
  
  if (newId === oldId) return
  
  if (newId) {
    editForm.value.districtId = null
    editForm.value.municipalityId = null
    try { 
      await fetchDistricts(String(newId)) 
    } catch { 
      toast.error('Failed to load districts') 
    }
  } else {
    editForm.value.districtId = null
    editForm.value.municipalityId = null
  }
})

watch(() => editForm.value.districtId, async (newVal, oldVal) => {
  // Skip if initializing or value hasn't really changed
  if (isInitializing.value) return
  
  // Compare the actual values (objects have .value property)
  const newId = newVal?.value || null
  const oldId = oldVal?.value || null
  
  if (newId === oldId) return
  
  if (newId) {
    editForm.value.municipalityId = null
    try { 
      await fetchMunicipalities(String(newId)) 
    } catch { 
      toast.error('Failed to load municipalities') 
    }
  } else {
    editForm.value.municipalityId = null
  }
})

// Guardian loading
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
  } catch { 
    toast.error('Failed to load guardians') 
  } finally { 
    loadingGuardians.value = false 
  }
}

// Guardian CRUD
const openGuardianModal = (guardian?: any) => {
  editingGuardian.value = guardian
    ? { ...guardian, is_primary_contact: Boolean(Number(guardian.is_primary_contact)) }
    : { name: '', relation: '', phone: '', email: '', occupation: '', address: '', is_primary_contact: false }
  guardianErrors.value = {}
  isGuardianModalOpen.value = true
}
const closeGuardianModal = () => { 
  isGuardianModalOpen.value = false
  editingGuardian.value = null 
}
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
      if (result) toast.success('Guardian updated successfully')
    } else {
      result = await createGuardian(selectedStudent.value.id, editingGuardian.value)
      if (result) toast.success('Guardian added successfully')
    }
    if (result) {
      await loadGuardians(selectedStudent.value.id)
      setTimeout(closeGuardianModal, 100)
    } else toast.error('Failed to save guardian')
  } catch {
    toast.error('Failed to save guardian')
  } finally { 
    guardianFormProcessing.value = false 
  }
}
const handleGuardianDelete = (g: any) => { 
  editingGuardian.value = g
  isDeleteGuardianOpen.value = true 
}
const removeGuardian = async () => {
  if (!editingGuardian.value?.id) return
  try {
    const ok = await deleteGuardian(editingGuardian.value.id)
    if (ok) {
      guardians.value = guardians.value.filter(g => g.id !== editingGuardian.value.id)
      toast.success('Guardian deleted')
      isDeleteGuardianOpen.value = false
      editingGuardian.value = null
    } else toast.error('Failed to delete guardian')
  } catch { 
    toast.error('Failed to delete guardian') 
  }
}

// Student actions
const handleView = (student: Student) => {
  selectedStudent.value = student
  isDialogOpen.value = true
}

// Edit Modal – FIXED to properly load and set values with option objects
const startEdit = async (student: Student) => {
  selectedStudent.value = student
  isEditModalOpen.value = true
  loadingEdit.value = true
  isInitializing.value = true // Prevent watchers from firing

  // Reset form first
  editForm.value = {
    id: null, 
    fName: '', 
    mName: '', 
    lName: '', 
    email: '', 
    phone: '',
    age: '', 
    dateOfBirth: '', 
    classId: null, 
    sectionId: null, 
    contactNumber: '',
    joinedDate: '', 
    address: '', 
    stateId: null, 
    districtId: null, 
    municipalityId: null,
    photo: null
  }

  try {
    // Convert IDs to strings for consistency
    const stateId = student.state_id ? String(student.state_id) : null
    const districtId = student.district_id ? String(student.district_id) : null
    const municipalityId = student.municipality_id ? String(student.municipality_id) : null
    const classId = student.class_id ? String(student.class_id) : null
    const sectionId = student.section_id ? String(student.section_id) : null

    // Load dependent dropdowns FIRST before setting form values
    if (stateId) {
      await fetchDistricts(stateId)
    }
    if (districtId) {
      await fetchMunicipalities(districtId)
    }

    // Wait for next tick to ensure dropdowns are populated
    await nextTick()

    // Find the actual option objects from the arrays
    const classOption = classId ? classes.value.find(c => String(c.value) === classId) || null : null
    const sectionOption = sectionId ? sections.value.find(s => String(s.value) === sectionId) || null : null
    const stateOption = stateId ? states.value.find(st => String(st.value) === stateId) || null : null
    const districtOption = districtId ? districts.value.find(d => String(d.value) === districtId) || null : null
    const municipalityOption = municipalityId ? municipalities.value.find(m => String(m.value) === municipalityId) || null : null

    // Now populate form with option objects
    editForm.value = {
      id: student.id,
      fName: student.first_name || '',
      mName: student.middle_name || '',
      lName: student.last_name || '',
      email: student.email || '',
      phone: student.phone || '',
      age: student.age || '',
      dateOfBirth: student.date_of_birth || '',
      classId: classOption,
      sectionId: sectionOption,
      contactNumber: student.contact_number || '',
      joinedDate: student.joined_date || '',
      address: student.address || '',
      stateId: stateOption,
      districtId: districtOption,
      municipalityId: municipalityOption,
      photo: null,
    }

    // Wait for form to update
    await nextTick()
    
  } catch (e) {
    console.error('Failed to load edit data:', e)
    toast.error('Failed to load student data')
  } finally {
    loadingEdit.value = false
    // Re-enable watchers after a delay
    setTimeout(() => {
      isInitializing.value = false
    }, 500)
  }
}

const handleEdit = (student?: Student) => {
  const s = student || selectedStudent.value
  console.log(s,"studentdetails");
  
  if (s) startEdit(s)
}

// Form Validation & Submit - Updated to handle object values
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
  if (!validateEditForm()) { 
    toast.error('Please fill all required fields')
    return 
  }
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
    
    // Extract value from option objects
    fd.append('class_id', editForm.value.classId?.value || '')
    fd.append('section_id', editForm.value.sectionId?.value || '')
    fd.append('contact_number', editForm.value.contactNumber || '')
    fd.append('joined_date', editForm.value.joinedDate)
    fd.append('address', editForm.value.address || '')
    fd.append('state_id', editForm.value.stateId?.value || '')
    fd.append('district_id', editForm.value.districtId?.value || '')
    fd.append('municipality_id', editForm.value.municipalityId?.value || '')
    
    if (editForm.value.photo) fd.append('photo', editForm.value.photo)

    const ok = await updateStudent(editForm.value.id, fd)
    console.log(ok,"updateStudent");
    
    if (ok.success) {
      // toast.success('Student updated successfully')
      isEditModalOpen.value = false
      await fetchStudentListByDateRange()
    } else toast.error('Failed to update student')
  } catch (e) {
    console.error('Update student error:', e)
    toast.error('Failed to update student')
  } finally { 
    editFormProcessing.value = false 
  }
}

// Photo Helpers
const photoPreview = computed(() => {
  console.log(editForm.value.photo,"photo of student");
  //console.log(URL.createObjectURL(editForm.value.photo),"photo url of student");
  console.log(selectedStudent.value?.photo_url,"selected student photo url");
  
  if (editForm.value.photo) return URL.createObjectURL(editForm.value.photo)
  if (selectedStudent.value?.photo_url) return selectedStudent.value.photo_url
  return null
})
const removePhoto = () => { 
  editForm.value.photo = null;
  selectedStudent.value.photo_url = '/images/default-avatar.png'
 }
const handlePhoneInput = (e: Event) => {
  const inp = e.target as HTMLInputElement
  inp.value = inp.value.replace(/\D/g, '').slice(0, 10)
  editForm.value.phone = inp.value
}
const handlePhotoChange = (e: Event) => {
  const inp = e.target as HTMLInputElement
  if (inp.files?.[0]) editForm.value.photo = inp.files[0]
}

// Close / Delete - Updated to reset with null values
const closeEditModal = () => {
  isEditModalOpen.value = false
  isInitializing.value = false
  editForm.value = {
    id: null, 
    fName: '', 
    mName: '', 
    lName: '', 
    email: '', 
    phone: '',
    age: '', 
    dateOfBirth: '', 
    classId: null, 
    sectionId: null, 
    contactNumber: '',
    joinedDate: '', 
    address: '', 
    stateId: null, 
    districtId: null, 
    municipalityId: null,
    photo: null
  }
  editValidationErrors.value = {}
}
const handleDelete = (student: Student) => {
  studentToDelete.value = student
  isDeleteDialogOpen.value = true
}
const confirmDelete = async () => {
  if (!studentToDelete.value) return
  try {
    const ok = await removeStudent(studentToDelete.value.id)
    if (ok) {
      toast.success('Student deleted successfully')
      isDeleteDialogOpen.value = false
      studentToDelete.value = null
    } else toast.error('Failed to delete student')
  } catch { 
    toast.error('Failed to delete student') 
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
        can('students.canView') &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'View', onClick: () => handleView(student) }, () => h(Eye, { class: 'h-4 w-4' })),
        can('students.canEdit') &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 cursor-pointer', title: 'Edit', onClick: () => handleEdit(student) }, () => h(Edit, { class: 'h-4 w-4' })),
        can('students.canDelete') &&
        h(Button, { variant: 'ghost', size: 'sm', class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer', title: 'Delete', onClick: () => handleDelete(student) }, () => h(Trash2, { class: 'h-4 w-4' })),
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

  <!-- VIEW MODAL -->
  <Dialog v-model:open="isDialogOpen">
    <DialogContent class="max-w-[95vw] lg:max-w-[1100px] w-full p-0 overflow-hidden flex flex-col" style="height: 85vh;">
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
          <TabsContent value="student" class="space-y-6 mt-0">
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

          <TabsContent value="guardians" class="mt-0">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-semibold">Guardians</h3>
              <Button size="sm" @click="openGuardianModal()">
                <Plus class="h-4 w-4 mr-1" /> Add Guardian
              </Button>
            </div>

            <div class="border rounded-lg overflow-x-auto">
              <table class="w-full text-sm min-w-[800px]">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold">Name</th>
                    <th class="px-4 py-3 text-left font-semibold">Relation</th>
                    <th class="px-4 py-3 text-left font-semibold">Phone</th>
                    <th class="px-4 py-3 text-left font-semibold">Email</th>
                    <th class="px-4 py-3 text-left font-semibold">Occupation</th>
                    <th class="px-4 py-3 text-left font-semibold">Address</th>
                    <th class="px-4 py-3 text-left font-semibold">Is Primary</th>
                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="loadingGuardians">
                    <td colspan="8" class="text-center py-10">
                      <Loader2 class="h-8 w-8 animate-spin mx-auto" />
                    </td>
                  </tr>
                  <tr v-else-if="guardians.length === 0">
                    <td colspan="8" class="text-center py-10 text-gray-500">No guardians added</td>
                  </tr>
                  <tr v-for="g in guardians" :key="g.id" class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3 max-w-[200px] truncate" :title="g.name">{{ g.name || '—' }}</td>
                    <td class="px-4 py-3 max-w-[200px] truncate" :title="g.relation">{{ g.relation || '—' }}</td>
                    <td class="px-4 py-3 max-w-[200px] truncate" :title="g.phone">{{ g.phone || '—' }}</td>
                    <td class="px-4 py-3 max-w-[200px] truncate" :title="g.email">{{ g.email || '—' }}</td>
                    <td class="px-4 py-3 max-w-[200px] truncate" :title="g.occupation">{{ g.occupation || '—' }}</td>
                    <td class="px-4 py-3 max-w-[200px] truncate" :title="g.address">{{ g.address || '—' }}</td>
                    <td class="px-4 py-3">
                      <span v-if="g.is_primary_contact" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Primary
                      </span>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                      <div class="flex items-center justify-end gap-1">
                        <Button v-if="can('guardians.canEdit')" variant="ghost" size="icon" class="h-8 w-8" @click="openGuardianModal(g)">
                          <Pencil class="h-4 w-4" />
                        </Button>
                        <Button v-if="can('guardians.canDelete')" variant="ghost" size="icon" class="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50" @click="handleGuardianDelete(g)">
                          <Trash2 class="h-4 w-4" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
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

  <!-- EDIT STUDENT MODAL -->
  <Dialog :forceMount="true" v-model:open="isEditModalOpen">
    <DialogContent class="max-w-[95vw] lg:max-w-[1400px] w-full p-0 overflow-hidden flex flex-col max-h-[85vh]">
      <DialogHeader class="flex-shrink-0 bg-white border-b p-6">
        <DialogTitle class="text-2xl font-bold">Edit Student</DialogTitle>
        <DialogDescription class="text-lg">Update student information</DialogDescription>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto p-6 min-h-0 max-h-[calc(85vh-140px)]">
        <div v-if="loadingEdit" class="flex flex-col items-center justify-center h-full">
          <Loader2 class="h-12 w-12 animate-spin text-primary" />
          <p class="mt-3 text-muted-foreground">Loading student data…</p>
        </div>

        <form v-else @submit.prevent="submitEditForm" class="space-y-4">
          <div class="flex items-center gap-4 bg-gray-50 rounded-lg p-4">
            <div class="relative">
              <Avatar class="h-20 w-20">
                <AvatarImage v-if="photoPreview" :src="photoPreview" />
                <AvatarFallback class="text-xl font-bold">
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
              <Input id="edit-photo" type="file" accept="image/*" @change="handlePhotoChange" class="mt-1" />
              <p class="text-xs text-gray-500 mt-1">Upload a new photo or keep existing one</p>
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="space-y-2">
              <Label for="edit-fName">First Name <span class="text-red-500">*</span></Label>
              <Input id="edit-fName" v-model="editForm.fName" placeholder="First name" :class="{ 'border-red-500': editValidationErrors.fName }" />
              <p v-if="editValidationErrors.fName" class="text-sm text-red-600">{{ editValidationErrors.fName }}</p>
            </div>

            <div class="space-y-2">
              <Label for="edit-mName">Middle Name</Label>
              <Input id="edit-mName" v-model="editForm.mName" placeholder="Middle name" />
            </div>
            
            <div class="space-y-2">
              <Label for="edit-lName">Last Name <span class="text-red-500">*</span></Label>
              <Input id="edit-lName" v-model="editForm.lName" placeholder="Last name" :class="{ 'border-red-500': editValidationErrors.lName }" />
              <p v-if="editValidationErrors.lName" class="text-sm text-red-600">{{ editValidationErrors.lName }}</p>
            </div>

            <div class="space-y-2">
              <Label for="edit-email">Email</Label>
              <Input id="edit-email" v-model="editForm.email" type="email" placeholder="Email" />
            </div>

            <div class="space-y-2">
              <Label for="edit-phone">Phone <span class="text-red-500">*</span></Label>
              <Input id="edit-phone" v-model="editForm.phone" placeholder="10-digit number" :class="{ 'border-red-500': editValidationErrors.phone }" @input="handlePhoneInput" maxlength="10" />
              <p v-if="editValidationErrors.phone" class="text-sm text-red-600">{{ editValidationErrors.phone }}</p>
            </div>

            <div class="space-y-2">
              <Label for="edit-age">Age <span class="text-red-500">*</span></Label>
              <Input id="edit-age" v-model="editForm.age" type="number" min="1" max="100" :class="{ 'border-red-500': editValidationErrors.age }" />
              <p v-if="editValidationErrors.age" class="text-sm text-red-600">{{ editValidationErrors.age }}</p>
            </div>

            <div class="space-y-2">
              <Label for="edit-dateOfBirth">Date of Birth <span class="text-red-500">*</span></Label>
              <DatePicker id="edit-dateOfBirth" :model-value="editDateOfBirthValue" @update:model-value="handleEditDateOfBirthUpdate" month-year-selector />
              <p v-if="editValidationErrors.dateOfBirth" class="text-sm text-red-600">{{ editValidationErrors.dateOfBirth }}</p>
            </div>

            <div class="space-y-2">
              <Label for="edit-classId">Class <span class="text-red-500">*</span></Label>
               <CustomSelect
                v-model="editForm.classId"
                :options="classes"
                placeholder="Select Class"
              />
              <p v-if="editValidationErrors.classId" class="text-sm text-red-600">{{ editValidationErrors.classId }}</p>
            </div>

            <div class="space-y-2">
              <Label for="edit-sectionId">Section</Label>
              <CustomSelect 
               
                id="edit-sectionId" 
                v-model="editForm.sectionId" 
                :options="sections" 
                placeholder="Select Section" 
              />
            </div>

            <!-- <div class="space-y-2">
              <Label for="edit-joinedDate">Joined Date <span class="text-red-500">*</span></Label>
              <DatePicker id="edit-joinedDate" :model-value="editJoinedDateValue" @update:model-value="handleEditJoinedDateUpdate" month-year-selector />
              <p v-if="editValidationErrors.joinedDate" class="text-sm text-red-600">{{ editValidationErrors.joinedDate }}</p>
            </div> -->

            <div class="space-y-2">
              <Label for="edit-contactNumber">Contact Number</Label>
              <Input id="edit-contactNumber" v-model="editForm.contactNumber" placeholder="Contact number" />
            </div>

            <div class="space-y-2">
              <Label for="edit-address">Address</Label>
              <Input id="edit-address" v-model="editForm.address" placeholder="Full address" />
            </div>

            <div class="space-y-2">
              <Label for="edit-stateId">State <span class="text-red-500">*</span></Label>
              <CustomSelect 
               
                id="edit-stateId" 
                v-model="editForm.stateId" 
                :options="states" 
                placeholder="Select State" 
              />
              <p v-if="editValidationErrors.stateId" class="text-sm text-red-600">{{ editValidationErrors.stateId }}</p>
            </div>

            <div class="space-y-2">
              <Label for="edit-districtId">District</Label>
              <CustomSelect 
              
                id="edit-districtId" 
                v-model="editForm.districtId" 
                :options="districts" 
                :disabled="!editForm.stateId" 
                placeholder="Select District" 
              />
            </div>

            <div class="space-y-2">
              <Label for="edit-municipalityId">Municipality</Label>
              <CustomSelect 
                
                id="edit-municipalityId" 
                v-model="editForm.municipalityId" 
                :options="municipalities" 
                :disabled="!editForm.districtId" 
                placeholder="Select Municipality" 
              />
            </div>
          </div>
        </form>
      </div>

      <div class="flex-shrink-0 bg-white border-t p-4 flex justify-end gap-3">
        <Button type="button" variant="outline" @click="closeEditModal">Cancel</Button>
        <Button type="button" @click="submitEditForm" :disabled="editFormProcessing || loadingEdit">
          <Loader2 v-if="editFormProcessing" class="mr-2 h-4 w-4 animate-spin" />
          {{ editFormProcessing ? 'Updating...' : 'Update Student' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>

  <!-- GUARDIAN FORM MODAL -->
  <Dialog v-model:open="isGuardianModalOpen">
    <DialogContent class="max-w-4xl">
      <DialogHeader>
        <DialogTitle class="text-2xl">{{ editingGuardian?.id ? 'Edit' : 'Add' }} Guardian</DialogTitle>
      </DialogHeader>
      <form v-if="editingGuardian" @submit.prevent="submitGuardian" class="space-y-6">
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

  <!-- DELETE STUDENT CONFIRMATION -->
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

  <!-- DELETE GUARDIAN CONFIRMATION -->
  <AlertDialog v-model:open="isDeleteGuardianOpen">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Delete Guardian?</AlertDialogTitle>
        <AlertDialogDescription>
          This action cannot be undone.
          <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md text-sm">
            <strong>{{ editingGuardian?.name }}</strong><br />
            ID: #{{ editingGuardian?.id }}
          </div>
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel @click="isDeleteGuardianOpen = false">Cancel</AlertDialogCancel>
        <AlertDialogAction @click="removeGuardian" class="bg-red-600 hover:bg-red-700">
          <Trash2 class="mr-2 h-4 w-4" /> Delete
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<style scoped>
button { cursor: pointer !important; }
.border-red-500 { border-color: rgb(239 68 68) !important; }
.border-red-500:focus { border-color: rgb(239 68 68) !important; box-shadow: 0 0 0 1px rgb(239 68 68) !important; }

/* Prevent body scroll when any dropdown (Radix/HeadlessUI/shadcn) is open */
body:has([data-radix-popper-content-wrapper]),
body:has([data-headlessui-popper]),
body:has([role="listbox"][data-state="open"]) {
  overflow: hidden !important;
}

/* Ensure the dropdown appears above everything */
[data-radix-popper-content-wrapper],
[role="listbox"],
[data-headlessui-popper] {
  z-index: 9999 !important;
}
body.select-dropdown-open {
  overflow: hidden !important;
}
[data-radix-popper-content-wrapper] {
  z-index: 9999 !important;
}
</style> 