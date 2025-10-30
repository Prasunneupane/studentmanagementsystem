<script setup lang="ts">
import { h,ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css'
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Loader2, Edit, Trash2, Eye, MoreHorizontal } from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { useLocationData, type Student } from '@/composables/fetchData';
import DataTable from '../students/Datatable.vue';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ColumnDef } from '@tanstack/vue-table';

import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
// import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
// Breadcrumbs
const breadcrumbs = [{ title: 'View Students', href: '/students' }];

// Form (only dates)
const form = useForm({
  fromDate: new Date().toISOString().split('T')[0],
  toDate: new Date().toISOString().split('T')[0],
});

const selectedStudent = ref<Student | null>(null);
const isDialogOpen = ref(false);
const studentToDelete = ref<Student | null>(null);
const isDeleteDialogOpen = ref(false);
// Use composable
const {
  students,
  loading,
  errorMessage,
  fetchStudentListByDateRange,
  removeStudent,
  deletingId,
} = useLocationData(form);

// Computed values for DatePicker (needs Date object or undefined)
const fromDateValue = computed(() => {
  if (!form.fromDate) return undefined;
  const [year, month, day] = form.fromDate.split('-').map(Number);
  return new Date(year, month - 1, day);
});

const toDateValue = computed(() => {
  if (!form.toDate) return new Date();
  const [year, month, day] = form.toDate.split('-').map(Number);
  return new Date(year, month - 1, day);
});

// Handlers for date updates
const handleFromDateUpdate = (date: Date | undefined) => {
  if (!date) {
    form.fromDate = '';
    return;
  }
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  form.fromDate = `${year}-${month}-${day}`;
};

const handleToDateUpdate = (date: Date | undefined) => {
  if (!date) {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    form.toDate = `${year}-${month}-${day}`;
    return;
  }
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  form.toDate = `${year}-${month}-${day}`;
};

const confirmDelete = async () => {
  if (!studentToDelete.value) return;
  const success = await removeStudent(studentToDelete.value.id);
  if (success) {
    isDeleteDialogOpen.value = false;
    studentToDelete.value = null;
  }
};

// Action Handlers
const handleView = (student: Student) => {
  console.log('View student:', student);
  selectedStudent.value = student;
  isDialogOpen.value = true;
  // alert(`Viewing details for: ${student.first_name} ${student.last_name}`);
  // Implement your view logic here
  // Example: router.push(`/students/${student.id}`)
};

const handleEdit = (student: Student) => {
  console.log('Edit student:', student);
  alert(`Editing: ${student.first_name} ${student.last_name}`);
  // Implement your edit logic here
  // Example: router.push(`/students/${student.id}/edit`)
};

const handleDelete = (student: Student) => {
  console.log('Delete student:', student);
  // if (confirm(`Are you sure you want to delete ${student.first_name} ${student.last_name}?`)) {
  //   // Call your delete API here
  //   alert(`Deleted: ${student.first_name} ${student.last_name}`);
  //   // Example: deleteStudent(student.id);
  // }
  studentToDelete.value = student;
  isDeleteDialogOpen.value = true;
};

const handleSendEmail = (student: Student) => {
  console.log('Send email to:', student.email);
  alert(`Sending email to: ${student.email}`);
};

const handleDownloadReport = (student: Student) => {
  console.log('Download report for:', student.id);
  alert(`Downloading report for: ${student.first_name} ${student.last_name}`);
};

const handleChangeStatus = (student: Student) => {
  console.log('Change status for:', student.id);
  alert(`Changing status for: ${student.first_name} ${student.last_name}`);
};

// Define columns for DataTable with Actions
const columns: ColumnDef<Student>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')),
  },
  {
    accessorKey: 'first_name',
    header: 'First Name',
    cell: ({ row }) => h('div', {}, row.getValue('first_name')),
  },
  {
    accessorKey: 'last_name',
    header: 'Last Name',
    cell: ({ row }) => h('div', {}, row.getValue('last_name')),
  },
  {
    accessorKey: 'email',
    header: 'Email',
    cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('email')),
  },
  {
    accessorKey: 'phone',
    header: 'Phone',
    cell: ({ row }) => h('div', {}, row.getValue('phone')),
  },
  {
    accessorKey: 'age',
    header: 'Age',
    cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('age')),
  },
  {
    accessorKey: 'class_name',
    header: 'Class',
    cell: ({ row }) => h('div', {}, row.getValue('class_name')),
  },
  {
    accessorKey: 'joined_date',
    header: 'Joined Date',
    cell: ({ row }) => h('div', {}, row.getValue('joined_date')),
  },
  // ACTION COLUMN
  {
    id: 'actions',
    header: 'Actions',
    enableSorting: false,
    cell: ({ row }) => {
      const student = row.original;
      
      return h('div', { class: 'flex items-center gap-2' }, [
        // View Button
        h(Button, {
          variant: 'ghost',
          size: 'sm',
          class: 'h-8 w-8 p-0',
          title: 'View Details',
          onClick: () => handleView(student),
        }, () => h(Eye, { class: 'h-4 w-4' })),
        
        // Edit Button
        h(Button, {
          variant: 'ghost',
          size: 'sm',
          class: 'h-8 w-8 p-0',
          title: 'Edit Student',
          onClick: () => handleEdit(student),
        }, () => h(Edit, { class: 'h-4 w-4' })),
        
        // Delete Button
        h(Button, {
          variant: 'ghost',
          size: 'sm',
          class: 'h-8 w-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50',
          title: 'Delete Student',
          onClick: () => handleDelete(student),
        }, () => h(Trash2, { class: 'h-4 w-4' })),
        
        // More Options Dropdown
        // h(DropdownMenu, {}, {
        //   default: () => [
        //     h(DropdownMenuTrigger, { asChild: true }, () =>
        //       h(Button, {
        //         variant: 'ghost',
        //         size: 'sm',
        //         class: 'h-8 w-8 p-0',
        //         title: 'More Options',
        //       }, () => h(MoreHorizontal, { class: 'h-4 w-4' }))
        //     ),
        //     h(DropdownMenuContent, { align: 'end' }, () => [
        //       h(DropdownMenuItem, {
        //         onClick: () => handleSendEmail(student),
        //       }, () => 'Send Email'),
        //       h(DropdownMenuItem, {
        //         onClick: () => handleDownloadReport(student),
        //       }, () => 'Download Report'),
        //       h(DropdownMenuItem, {
        //         onClick: () => handleChangeStatus(student),
        //       }, () => 'Change Status'),
        //     ]),
        //   ],
        // }),
      ]);
    },
  },
];
</script>

<template>
  <Head title="View Students" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
      <!-- Date Picker Card -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">Student List</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col sm:flex-row items-end gap-4 mb-6">
            <div class="space-y-2 flex-1 sm:max-w-[250px]">
              <Label for="fromDate">From Date</Label>
              <DatePicker
                id="fromDate"
                :model-value="fromDateValue"
                @update:model-value="handleFromDateUpdate"
                month-year-selector
                placeholder="Select from date"
                class="datepicker-input"
              />
            </div>

            <div class="space-y-2 flex-1 sm:max-w-[250px]">
              <Label for="toDate">To Date</Label>
              <DatePicker
                id="toDate"
                :model-value="toDateValue"
                @update:model-value="handleToDateUpdate"
                month-year-selector
                placeholder="Select to date"
                class="datepicker-input"
              />
            </div>

            <Button
              :disabled="loading"
              class="h-10"
              :class="{ 'opacity-50 cursor-not-allowed': loading }"
              @click="fetchStudentListByDateRange"
            >
              <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
              {{ loading ? 'Loading...' : 'Load' }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- DataTable Card -->
      <Card class="w-full shadow-lg rounded-2xl">
        <CardContent class="pt-6">
          <DataTable 
            :columns="columns" 
            :data="students" 
            :loading="loading" 
            title="Student List Report" 
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>

  <!-- Student Details Dialog – Modern & Spacious -->
<Dialog v-model:open="isDialogOpen">
  <DialogContent
    class="max-w-4xl w-full max-h-[92vh] overflow-hidden p-0"
    :class="{ 'sm:max-w-3xl': true }"
  >
    <!-- Header (sticky) -->
    <DialogHeader class="sticky top-0 z-10 bg-white border-b p-6 pb-4">
      <DialogTitle class="text-2xl font-bold text-gray-900">
        Student Details
      </DialogTitle>
      <DialogDescription class="text-sm text-gray-600 mt-1">
        {{ selectedStudent?.first_name }} {{ selectedStudent?.last_name }}
      </DialogDescription>
    </DialogHeader>

    <!-- Scrollable Body -->
    <div class="overflow-y-auto px-6 pt-4 pb-6 space-y-8">
      <!-- ==== Profile Card ==== -->
      <div class="flex flex-col sm:flex-row items-center gap-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6">
        <Avatar class="h-28 w-28 ring-4 ring-white shadow-lg">
          <AvatarImage :src="selectedStudent?.photo_url" />
          <AvatarFallback class="text-3xl font-bold bg-gradient-to-br from-blue-600 to-indigo-700 text-white">
            {{ selectedStudent?.first_name[0] }}{{ selectedStudent?.last_name[0] }}
          </AvatarFallback>
        </Avatar>

        <div class="text-center sm:text-left flex-1">
          <h3 class="text-2xl font-semibold text-gray-900">
            {{ selectedStudent?.first_name }}
            {{ selectedStudent?.middle_name ? selectedStudent?.middle_name + ' ' : '' }}
            {{ selectedStudent?.last_name }}
          </h3>
          <div class="flex flex-wrap gap-2 mt-3 justify-center sm:justify-start">
            <Badge variant="secondary" class="px-3 py-1 text-sm">
              {{ selectedStudent?.class_name ?? '—' }}
            </Badge>
            <Badge variant="outline" class="px-3 py-1 text-sm">
              {{ selectedStudent?.section_name ?? '—' }}
            </Badge>
          </div>
        </div>
      </div>

      <!-- ==== Two-Column Info ==== -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Personal -->
        <section class="space-y-4">
          <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">
            Personal Information
          </h4>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-600">Student ID</dt>
              <dd class="font-medium text-gray-900">#{{ selectedStudent?.id }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Email</dt>
              <dd class="font-medium lowercase text-gray-900">
                {{ selectedStudent?.email ?? '—' }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Phone</dt>
              <dd class="font-medium text-gray-900">{{ selectedStudent?.phone ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Age</dt>
              <dd class="font-medium text-gray-900">{{ selectedStudent?.age ?? '—' }} years</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Date of Birth</dt>
              <dd class="font-medium text-gray-900">
                {{ selectedStudent?.date_of_birth ?? '—' }}
              </dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Joined Date</dt>
              <dd class="font-medium text-gray-900">
                {{ selectedStudent?.joined_date ??'—' }}
              </dd>
            </div>
          </dl>
        </section>

        <!-- Guardian -->
        <section class="space-y-4">
          <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">
            Guardian & Contact
          </h4>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-600">Father</dt>
              <dd class="font-medium text-gray-900">{{ selectedStudent?.father_name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Mother</dt>
              <dd class="font-medium text-gray-900">{{ selectedStudent?.mother_name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Guardian</dt>
              <dd class="font-medium text-gray-900">{{ selectedStudent?.guardian_name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-600">Contact No.</dt>
              <dd class="font-medium text-gray-900">{{ selectedStudent?.contact_number ?? '—' }}</dd>
            </div>
          </dl>
        </section>
      </div>

      <!-- ==== Address ==== -->
      <section class="space-y-4">
        <h4 class="text-sm font-medium text-gray-500 uppercase tracking-wider">
          Address
        </h4>
        <p class="text-sm text-gray-700 leading-relaxed">
          {{ selectedStudent?.address || 'No address provided' }}
        </p>
        <div class="flex flex-wrap gap-2 mt-3">
          <Badge v-if="selectedStudent?.state_name" variant="outline" class="px-3 py-1">
            {{ selectedStudent?.state_name }}
          </Badge>
          <Badge v-if="selectedStudent?.district_name" variant="outline" class="px-3 py-1">
            {{ selectedStudent?.district_name }}
          </Badge>
          <Badge v-if="selectedStudent?.municipality_name" variant="outline" class="px-3 py-1">
            {{ selectedStudent?.municipality_name }}
          </Badge>
        </div>
      </section>
    </div>

    <!-- Footer (sticky) -->
    <div class="sticky bottom-0 bg-white border-t p-4 flex justify-end gap-3">
      <Button variant="outline" @click="isDialogOpen = false">
        Close
      </Button>
      <Button @click="handleEdit(selectedStudent)">
        <Edit class="mr-2 h-4 w-4" /> Edit Student
      </Button>
    </div>
  </DialogContent>
</Dialog>

<AlertDialog v-model:open="isDeleteDialogOpen">
  <AlertDialogContent>
    <AlertDialogHeader>
      <AlertDialogTitle class="text-xl">
        Are you sure you want to delete this student?
      </AlertDialogTitle>
      <AlertDialogDescription class="text-base mt-3">
        This action <span class="font-semibold text-red-600">cannot be undone</span>. 
        You are about to permanently delete:
        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md text-sm">
          <strong>
            {{ studentToDelete?.first_name }}
            {{ studentToDelete?.middle_name ? studentToDelete.middle_name + ' ' : '' }}
            {{ studentToDelete?.last_name }}
          </strong>
          <br />
          <span class="text-gray-600">ID: #{{ studentToDelete?.id }}</span>
        </div>
      </AlertDialogDescription>
    </AlertDialogHeader>
    <AlertDialogFooter class="mt-6">
      <AlertDialogCancel @click="isDeleteDialogOpen = false">
        Cancel
      </AlertDialogCancel>
      <AlertDialogAction
        @click="confirmDelete"
        class="bg-red-600 hover:bg-red-700 text-white"
      >
        <Trash2 class="mr-2 h-4 w-4" />
        Delete Student
      </AlertDialogAction>
    </AlertDialogFooter>
  </AlertDialogContent>
</AlertDialog>
</template>

<style scoped>
button {
  cursor: pointer !important;
}

button:hover {
  cursor: pointer !important;
}
</style>