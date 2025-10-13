<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Loader2 } from 'lucide-vue-next'
import 'vue-sonner/style.css'
import { getStudentsListByDateRange } from '@/constant/apiservice/callService'

// Breadcrumbs
const breadcrumbs = [{ title: 'View Students', href: '/students' }]

// State variables
const fromDate = ref('')
const toDate = ref('')
const invoices = ref<any[]>([])
const loading = ref(false)
const errorMessage = ref('')

// Fetch students by date range
const fetchStudentListByDateRange = async () => {
  if (!fromDate.value || !toDate.value) {
    toast.error('Please select both From Date and To Date')
    return
  }

  loading.value = true
  errorMessage.value = ''
  invoices.value = []

  try {
    const response = await getStudentsListByDateRange(fromDate.value, toDate.value)
    console.log(response,"response");
    
    // Assuming API returns JSON like: { data: [...] }
    if (response && response.data && Array.isArray(response.data) && response.data.length > 0) {
      invoices.value = response.data
    } else {
      errorMessage.value = 'No data found. Please select another date range.'
    }
  } catch (error) {
    console.error('Error fetching data:', error)
    errorMessage.value = 'Server error. Please try again later.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Head title="View Students" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />

    <!-- Date Range Form -->
    <div class="flex flex-col sm:flex-row items-end gap-4 mb-6">
      <div class="flex flex-col">
        <label for="fromDate" class="text-sm font-medium text-gray-700 mb-1">From Date</label>
        <Input id="fromDate" type="date" v-model="fromDate" />
      </div>
      <div class="flex flex-col">
        <label for="toDate" class="text-sm font-medium text-gray-700 mb-1">To Date</label>
        <Input id="toDate" type="date" v-model="toDate" />
      </div>
      <Button :disabled="loading" class="h-[38px]" @click="fetchStudentListByDateRange">
        <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
        {{ loading ? 'Loading...' : 'Load' }}
      </Button>
    </div>

    <!-- Table Section -->
    <div class="relative border rounded-2xl shadow-sm p-4 bg-white">
      <!-- Loader -->
      <div
        v-if="loading"
        class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 z-10 rounded-2xl"
      >
        <Loader2 class="h-8 w-8 animate-spin text-gray-700" />
      </div>

      <!-- Error or Empty Message -->
      <div v-if="!loading && errorMessage" class="text-center py-8 text-gray-600">
        {{ errorMessage }}
      </div>

      <!-- Table -->
      <Table v-if="!loading && invoices.length > 0">
        <TableCaption>List of students within the selected date range</TableCaption>
        <TableHeader>
          <TableRow>
            <TableHead>ID</TableHead>
            <TableHead>First Name</TableHead>
            <TableHead>Last Name</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Phone</TableHead>
            <TableHead>Age</TableHead>
            <TableHead>Class</TableHead>
            <TableHead>Joined Date</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="student in invoices" :key="student.id">
            <TableCell>{{ student.id }}</TableCell>
            <TableCell>{{ student.first_name }}</TableCell>
            <TableCell>{{ student.last_name }}</TableCell>
            <TableCell>{{ student.email }}</TableCell>
            <TableCell>{{ student.phone }}</TableCell>
            <TableCell>{{ student.age }}</TableCell>
            <TableCell>{{ student.class_name }}</TableCell>
            <TableCell>{{ student.joined_date }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </AppLayout>
</template>

<style scoped>
input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(0.5);
}
</style>
