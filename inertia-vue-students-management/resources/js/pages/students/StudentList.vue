<script setup lang="ts">
import { h, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Toaster } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-vue-next';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { useLocationData, type Student } from '@/composables/fetchData';
import DataTable from '../students/Datatable.vue';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'; // Adjust path if needed
import type { ColumnDef } from '@tanstack/vue-table';

// Breadcrumbs
const breadcrumbs = [{ title: 'View Students', href: '/students' }];

// Form (only dates)
const form = useForm({
  fromDate: new Date().toISOString().split('T')[0],
  toDate: new Date().toISOString().split('T')[0],
});

// Use composable
const {
  students,
  loading,
  errorMessage,
  fetchStudentListByDateRange,
} = useLocationData(form);

// Computed values for DatePicker (needs Date object or undefined)
const fromDateValue = computed(() => {
  if (!form.fromDate) return undefined;
  // Create date at noon UTC to avoid timezone issues
  const [year, month, day] = form.fromDate.split('-').map(Number);
  return new Date(year, month - 1, day);
});

const toDateValue = computed(() => {
  if (!form.toDate) return new Date();
  // Create date at noon UTC to avoid timezone issues
  const [year, month, day] = form.toDate.split('-').map(Number);
  return new Date(year, month - 1, day);
});

// Handlers for date updates
const handleFromDateUpdate = (date: Date | undefined) => {
  if (!date) {
    form.fromDate = '';
    return;
  }
  // Format date as YYYY-MM-DD in local timezone
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
  // Format date as YYYY-MM-DD in local timezone
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  form.toDate = `${year}-${month}-${day}`;
};

// Define columns for DataTable
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
          <!-- Error State -->
          
            <DataTable :columns="columns" :data="students" :loading="loading" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Fix DatePicker cursor to show text cursor instead of pointer */

button  {
  cursor: pointer !important;
}

button:hover {
  cursor: pointer !important;
}
</style>