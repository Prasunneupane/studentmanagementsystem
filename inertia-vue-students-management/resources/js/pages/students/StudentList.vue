<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Loader2 } from 'lucide-vue-next';
import 'vue-sonner/style.css';
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";

// Breadcrumbs
const breadcrumbs = [{ title: 'View Students', href: '/students' }];

// State variables
const form = useForm({
  fromDate: '',
  toDate: new Date().toISOString().split('T')[0],
});
const students = ref<any[]>([]);
const loading = ref(false);
const errorMessage = ref('');

// Table columns
const columns = [
  { key: 'id', label: 'ID' },
  { key: 'first_name', label: 'First Name' },
  { key: 'last_name', label: 'Last Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'age', label: 'Age' },
  { key: 'class_name', label: 'Class' },
  { key: 'joined_date', label: 'Joined Date' },
];

// Fetch students by date range using Inertia
const fetchStudentListByDateRange = () => {
  if (!form.fromDate || !form.toDate) {
    toast.error('Please select both From Date and To Date');
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  students.value = [];

  const token = localStorage.getItem('jwt_token');
  if (!token) {
    toast.error('Session expired. Please log in again.');
    errorMessage.value = 'Authentication failed. Please log in again.';
    loading.value = false;
    return;
  }

  form.get(route('student_list_by_date_range'), {
    preserveState: true,
    preserveScroll: true,
    headers: {
      Authorization: `Bearer ${token}`,
    },
    onSuccess: (page) => {
      const response = page.props.students || [];
      students.value = response.map((student: any) => ({
        ...student,
        class_name: student.class ? student.class.name : null,
      }));
      if (students.value.length === 0) {
        errorMessage.value = 'No data found. Please select another date range.';
      }
    },
    onError: (errors) => {
      console.error('Error fetching data:', errors);
      if (errors.auth || errors.error?.includes('Unauthorized')) {
        errorMessage.value = 'Authentication failed. Please log in again.';
        toast.error('Session expired. Please log in again.');
      } else if (errors.date) {
        errorMessage.value = errors.date;
        toast.error(errors.date);
      } else {
        errorMessage.value = 'Server error. Please try again later.';
        toast.error('Failed to load students. Please try again.');
      }
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};
</script>

<template>
  <Head title="View Students" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">Student List</CardTitle>
        </CardHeader>
        <CardContent class="w-full">
          <div class="flex flex-col sm:flex-row items-end gap-4 mb-6">
            <div class="flex flex-col">
              <label for="fromDate" class="text-sm font-medium text-gray-700 mb-1">From Date</label>
              <Input id="fromDate" type="date" v-model="form.fromDate" />
            </div>
            <div class="flex flex-col">
              <label for="toDate" class="text-sm font-medium text-gray-700 mb-1">To Date</label>
              <Input id="toDate" type="date" v-model="form.toDate" />
            </div>
            <Button
              :disabled="loading || form.processing"
              class="h-[38px]"
              :class="{ 'opacity-50 cursor-not-allowed': loading || form.processing }"
              @click="fetchStudentListByDateRange"
            >
              <Loader2 v-if="loading || form.processing" class="mr-2 h-4 w-4 animate-spin" />
              {{ loading || form.processing ? 'Loading...' : 'Load' }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card class="w-full shadow-lg rounded-2xl">
        <CardContent class="w-full">
          <div class="relative border rounded-2xl shadow-sm p-4 bg-white">
            <!-- Loader -->
            <div
              v-if="loading || form.processing"
              class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 z-10 rounded-2xl"
            >
              <Loader2 class="h-8 w-8 animate-spin text-gray-700" />
            </div>

            <!-- Error or Empty Message -->
            <div v-if="!loading && !form.processing && errorMessage" class="text-center py-8 text-gray-600">
              {{ errorMessage }}
            </div>

            <!-- Table -->
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead v-for="column in columns" :key="column.key">
                    {{ column.label }}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="student in students" :key="student.id">
                  <TableCell>{{ student.id }}</TableCell>
                  <TableCell>{{ student.first_name }}</TableCell>
                  <TableCell>{{ student.last_name }}</TableCell>
                  <TableCell>{{ student.email }}</TableCell>
                  <TableCell>{{ student.phone }}</TableCell>
                  <TableCell>{{ student.age }}</TableCell>
                  <TableCell>{{ student.class_name }}</TableCell>
                  <TableCell>{{ student.joined_date }}</TableCell>
                </TableRow>
                <TableRow v-if="!loading && !form.processing && !errorMessage && students.length === 0">
                  <TableCell colspan="8" class="text-center py-8 text-gray-600">
                    No data found. Please select a date range and click Load.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(0.5);
}
</style>