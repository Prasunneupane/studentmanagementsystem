<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useStudentData, type Student } from '@/composables/fetchData';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    type ColumnFiltersState,
    type PaginationState,
    type SortingState,
} from '@tanstack/vue-table';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import {
    ArrowUpDown,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronRight as ChevronRightIcon,
    ChevronsLeft,
    ChevronsRight,
    FileSpreadsheet,
    FileText,
    Loader2,
    Printer,
} from 'lucide-vue-next';
import { computed, h, ref } from 'vue';
import 'vue-sonner/style.css';
import * as XLSX from 'xlsx';

// Breadcrumbs
const breadcrumbs = [{ title: 'View Students', href: '/students' }];

// Form (only dates)
const form = useForm({
    fromDate: new Date().toISOString().split('T')[0],
    toDate: new Date().toISOString().split('T')[0],
});

// Use composable
const { students, loading, errorMessage, fetchStudentListByDateRange } = useStudentData(form);

// Expandable Row States
const expandedRows = ref<Set<number>>(new Set());
const loadingRows = ref<Set<number>>(new Set());
const nestedData = ref<Record<number, any[]>>({});

// Computed values for DatePicker
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

// Fetch nested data from API
const fetchNestedData = async (studentId: number) => {
    loadingRows.value.add(studentId);

    try {
        // Replace with your actual API endpoint
        const response = await fetch(`/api/students/${studentId}/courses`);

        if (!response.ok) throw new Error('Failed to fetch');

        const data = await response.json();
        nestedData.value[studentId] = data;
    } catch (error) {
        console.error('Error fetching nested data:', error);

        // Mock data for demonstration (remove this in production)
        nestedData.value[studentId] = [
            { id: 1, course: 'Mathematics', grade: 'A', attendance: 95, status: 'Active' },
            { id: 2, course: 'Science', grade: 'B+', attendance: 88, status: 'Active' },
            { id: 3, course: 'English', grade: 'A-', attendance: 92, status: 'Active' },
            { id: 4, course: 'History', grade: 'B', attendance: 85, status: 'Pending' },
        ];
    } finally {
        loadingRows.value.delete(studentId);
    }
};

// Toggle row expansion
const toggleRow = async (studentId: number) => {
    if (expandedRows.value.has(studentId)) {
        expandedRows.value.delete(studentId);
    } else {
        expandedRows.value.add(studentId);

        if (!nestedData.value[studentId]) {
            await fetchNestedData(studentId);
        }
    }
};

// Define columns with expander
const columns: ColumnDef<Student>[] = [
    {
        id: 'expander',
        header: '',
        enableSorting: false,
        cell: ({ row }) => {
            const studentId = row.original.id;
            const isExpanded = expandedRows.value.has(studentId);

            return h(
                Button,
                {
                    variant: 'ghost',
                    size: 'sm',
                    class: 'h-8 w-8 p-0',
                    onClick: () => toggleRow(studentId),
                },
                () =>
                    h(isExpanded ? ChevronDown : ChevronRight, {
                        class: 'h-4 w-4 text-gray-600',
                    }),
            );
        },
    },
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

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const globalFilter = ref('');
const pagination = ref<PaginationState>({
    pageIndex: 0,
    pageSize: 10,
});

// Table instance
const table = useVueTable({
    get data() {
        return students.value;
    },
    get columns() {
        return columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get globalFilter() {
            return globalFilter.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    onColumnFiltersChange: (updater) => {
        columnFilters.value = typeof updater === 'function' ? updater(columnFilters.value) : updater;
    },
    onGlobalFilterChange: (updater) => {
        globalFilter.value = typeof updater === 'function' ? updater(globalFilter.value) : updater;
    },
    onPaginationChange: (updater) => {
        pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;
    },
    manualPagination: false,
});

// Export Functions
const handlePrint = () => {
    const rows = table.getFilteredRowModel().rows;
    const headers = table
        .getAllColumns()
        .filter((col) => col.getIsVisible() && col.id !== 'expander')
        .map((col) => col.columnDef.header as string);

    const data = rows.map((row) => {
        return table
            .getAllColumns()
            .filter((col) => col.getIsVisible() && col.id !== 'expander')
            .map((col) => {
                const cell = row.getAllCells().find((c) => c.column.id === col.id);
                return cell ? String(cell.getValue() ?? '') : '';
            });
    });

    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Student List Report</title>
      <style>
        @media print {
          @page { size: A4; margin: 15mm; }
          body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        body { font-family: Arial, sans-serif; padding: 20px; margin: 0; }
        h1 { text-align: center; margin-bottom: 20px; font-size: 24px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px 8px; text-align: left; font-size: 12px; }
        th { background-color: #f3f4f6; font-weight: bold; color: #374151; }
        tr:nth-child(even) { background-color: #f9fafb; }
      </style>
    </head>
    <body>
      <h1>Student List Report</h1>
      <p style="text-align: center; color: #666; margin-bottom: 20px;">
        Generated on ${new Date().toLocaleString()}
      </p>
      <table>
        <thead><tr>${headers.map((h) => `<th>${h}</th>`).join('')}</tr></thead>
        <tbody>${data.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('')}</tbody>
      </table>
    </body>
    </html>
  `;

    printWindow.document.write(htmlContent);
    printWindow.document.close();
    printWindow.onload = () => {
        printWindow.focus();
        printWindow.print();
    };
};

const handleExcelExport = () => {
    const rows = table.getFilteredRowModel().rows;
    const headers = table
        .getAllColumns()
        .filter((col) => col.getIsVisible() && col.id !== 'expander')
        .map((col) => col.columnDef.header as string);

    const data = rows.map((row) => {
        return table
            .getAllColumns()
            .filter((col) => col.getIsVisible() && col.id !== 'expander')
            .map((col) => {
                const cell = row.getAllCells().find((c) => c.column.id === col.id);
                return cell ? String(cell.getValue() ?? '') : '';
            });
    });

    const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
    ws['!cols'] = headers.map(() => ({ wch: 15 }));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Students');
    XLSX.writeFile(wb, `students_${new Date().toISOString().split('T')[0]}.xlsx`);
};

const handlePdfExport = () => {
    const rows = table.getFilteredRowModel().rows;
    const headers = table
        .getAllColumns()
        .filter((col) => col.getIsVisible() && col.id !== 'expander')
        .map((col) => col.columnDef.header as string);

    const data = rows.map((row) => {
        return table
            .getAllColumns()
            .filter((col) => col.getIsVisible() && col.id !== 'expander')
            .map((col) => {
                const cell = row.getAllCells().find((c) => c.column.id === col.id);
                return cell ? String(cell.getValue() ?? '') : '';
            });
    });

    const doc = new jsPDF('l', 'mm', 'a4');
    doc.setFontSize(16);
    doc.text('Student List Report', 14, 15);
    doc.setFontSize(10);
    doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);

    autoTable(doc, {
        head: [headers],
        body: data,
        startY: 28,
        styles: { fontSize: 9, cellPadding: 3 },
        headStyles: { fillColor: [59, 130, 246], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [249, 250, 251] },
    });

    doc.save(`students_${new Date().toISOString().split('T')[0]}.pdf`);
};
</script>

<template>
    <Head title="View Students" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Date Picker Card -->
            <Card class="w-full rounded-2xl shadow-lg">
                <CardHeader>
                    <CardTitle class="text-xl font-bold">Student List</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="mb-6 flex flex-col items-end gap-4 sm:flex-row">
                        <div class="flex-1 space-y-2 sm:max-w-[250px]">
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

                        <div class="flex-1 space-y-2 sm:max-w-[250px]">
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
                            :class="{ 'cursor-not-allowed opacity-50': loading }"
                            @click="fetchStudentListByDateRange"
                        >
                            <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                            {{ loading ? 'Loading...' : 'Load' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- DataTable Card -->
            <Card class="w-full rounded-2xl shadow-lg">
                <CardContent class="pt-6">
                    <div class="space-y-4">
                        <!-- Controls -->
                        <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-700">Show</span>
                                <select
                                    :value="table.getState().pagination.pageSize"
                                    @change="table.setPageSize(Number(($event.target as HTMLSelectElement).value))"
                                    class="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm"
                                >
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="text-sm text-gray-700">entries</span>
                            </div>

                            <div class="flex w-full flex-col items-start gap-3 sm:flex-row sm:items-center lg:w-auto">
                                <div class="flex items-center gap-2">
                                    <Button variant="outline" size="sm" @click="handlePrint" :disabled="loading || !students.length">
                                        <Printer class="mr-2 h-4 w-4" /> Print
                                    </Button>
                                    <Button variant="outline" size="sm" @click="handleExcelExport" :disabled="loading || !students.length">
                                        <FileSpreadsheet class="mr-2 h-4 w-4" /> Excel
                                    </Button>
                                    <Button variant="outline" size="sm" @click="handlePdfExport" :disabled="loading || !students.length">
                                        <FileText class="mr-2 h-4 w-4" /> PDF
                                    </Button>
                                </div>
                                <Input
                                    placeholder="Search all columns..."
                                    :model-value="globalFilter"
                                    class="w-full sm:max-w-sm"
                                    @update:model-value="globalFilter = $event"
                                />
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                            <div
                                                v-if="!header.isPlaceholder"
                                                :class="[header.column.getCanSort() ? 'flex cursor-pointer items-center gap-2 select-none' : '']"
                                                @click="header.column.getToggleSortingHandler()?.($event)"
                                            >
                                                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                                                <ArrowUpDown
                                                    v-if="header.column.getCanSort()"
                                                    class="h-4 w-4 transition-colors"
                                                    :class="{
                                                        'text-blue-600': header.column.getIsSorted(),
                                                        'opacity-50': !header.column.getIsSorted(),
                                                    }"
                                                />
                                            </div>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <template v-if="loading">
                                        <TableRow>
                                            <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center">
                                                <div class="flex items-center justify-center gap-2 text-gray-600">
                                                    <Loader2 class="h-5 w-5 animate-spin" />
                                                    <span>Loading...</span>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </template>

                                    <template v-else-if="!table.getRowModel().rows?.length">
                                        <TableRow>
                                            <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center text-gray-600">
                                                No data found. Please select a date range and load data.
                                            </TableCell>
                                        </TableRow>
                                    </template>

                                    <template v-else>
                                        <template v-for="row in table.getRowModel().rows" :key="row.id">
                                            <!-- Main Row -->
                                            <TableRow>
                                                <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                                </TableCell>
                                            </TableRow>

                                            <!-- Expanded Row -->
                                            <TableRow v-if="expandedRows.has(row.original.id)" class="bg-gray-50">
                                                <TableCell :colspan="table.getAllColumns().length" class="p-0">
                                                    <div class="p-4">
                                                        <div v-if="loadingRows.has(row.original.id)" class="flex items-center justify-center py-8">
                                                            <Loader2 class="mr-2 h-6 w-6 animate-spin text-gray-600" />
                                                            <span class="text-gray-600">Loading course details...</span>
                                                        </div>

                                                        <div v-else-if="nestedData[row.original.id]" class="space-y-2">
                                                            <h4 class="mb-3 text-sm font-semibold text-gray-700">
                                                                Course Details for {{ row.original.first_name }} {{ row.original.last_name }}
                                                            </h4>

                                                            <div class="rounded border bg-white">
                                                                <Table>
                                                                    <TableHeader>
                                                                        <TableRow>
                                                                            <TableHead>Course</TableHead>
                                                                            <TableHead>Grade</TableHead>
                                                                            <TableHead>Attendance</TableHead>
                                                                            <TableHead>Status</TableHead>
                                                                        </TableRow>
                                                                    </TableHeader>
                                                                    <TableBody>
                                                                        <TableRow v-for="item in nestedData[row.original.id]" :key="item.id">
                                                                            <TableCell>{{ item.course }}</TableCell>
                                                                            <TableCell>{{ item.grade }}</TableCell>
                                                                            <TableCell>{{ item.attendance }}%</TableCell>
                                                                            <TableCell>
                                                                                <span
                                                                                    class="rounded-full px-2 py-1 text-xs"
                                                                                    :class="{
                                                                                        'bg-green-100 text-green-700': item.status === 'Active',
                                                                                        'bg-yellow-100 text-yellow-700': item.status === 'Pending',
                                                                                        'bg-red-100 text-red-700': item.status === 'Inactive',
                                                                                    }"
                                                                                >
                                                                                    {{ item.status }}
                                                                                </span>
                                                                            </TableCell>
                                                                        </TableRow>
                                                                    </TableBody>
                                                                </Table>
                                                            </div>
                                                        </div>

                                                        <div v-else class="py-4 text-center text-gray-500">No course details available</div>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        </template>
                                    </template>
                                </TableBody>
                            </Table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="table.getPageCount() > 1" class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div class="text-sm text-gray-700">
                                Showing {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }} to
                                {{
                                    Math.min(
                                        (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
                                        table.getFilteredRowModel().rows.length,
                                    )
                                }}
                                of {{ table.getFilteredRowModel().rows.length }} results
                            </div>

                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" @click="table.setPageIndex(0)" :disabled="!table.getCanPreviousPage()">
                                    <ChevronsLeft class="h-4 w-4" />
                                </Button>
                                <Button variant="outline" size="sm" @click="table.previousPage()" :disabled="!table.getCanPreviousPage()">
                                    <ChevronLeft class="h-4 w-4" />
                                </Button>
                                <span class="text-sm">Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }}</span>
                                <Button variant="outline" size="sm" @click="table.nextPage()" :disabled="!table.getCanNextPage()">
                                    <ChevronRightIcon class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="table.setPageIndex(table.getPageCount() - 1)"
                                    :disabled="!table.getCanNextPage()"
                                >
                                    <ChevronsRight class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
button {
    cursor: pointer !important;
}

button:hover {
    cursor: pointer !important;
}
</style>
