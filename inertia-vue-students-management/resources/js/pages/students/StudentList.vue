<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner';
// import Table from '@/components/ui/table/Table.vue';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import 'vue-sonner/style.css';
// import { getStudentsListByDateRange } from '@/constant/apiservice/callService';

// Breadcrumbs
const breadcrumbs = [{ title: 'View Students', href: '/students' }];

// Access server-side props passed via Inertia
const { props } = usePage();
const students = ref(props.students || []);

// Define table columns
const columns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'first_name', label: 'First Name', sortable: true },
  { key: 'last_name', label: 'Last Name', sortable: true },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'age', label: 'Age', sortable: true },
  { key: 'class_name', label: 'Class' },
  { key: 'joined_date', label: 'Joined Date', sortable: true },
];
const invoices = []; //await getStudentsListByDateRange('2023-01-01','2024-10-10')
</script>

<template>
  <Head title="View Students" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />
    <Table>
    <TableCaption>A list of your recent invoices.</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead class="w-[100px]">
          Invoice
        </TableHead>
        <TableHead>Status</TableHead>
        <TableHead>Method</TableHead>
        <TableHead class="text-right">
          Amount
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="invoice in invoices" :key="invoice.invoice">
        <TableCell class="font-medium">
          {{ invoice.invoice }}
        </TableCell>
        <TableCell>{{ invoice.paymentStatus }}</TableCell>
        <TableCell>{{ invoice.paymentMethod }}</TableCell>
        <TableCell class="text-right">
          {{ invoice.totalAmount }}
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
  </AppLayout>
</template>