<script setup lang="ts" generic="TData, TValue">
import { ref, watch } from 'vue';
import {
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  useVueTable,
  type ColumnDef,
  type SortingState,
  type ColumnFiltersState,
  type PaginationState,
} from '@tanstack/vue-table';

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Loader2, ArrowUpDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Printer, FileSpreadsheet, FileText } from 'lucide-vue-next';
import { FlexRender } from '@tanstack/vue-table';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  loading?: boolean;
  title?: string;
}

const props = defineProps<DataTableProps<any, any>>();

// === Reactive State ===
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const globalFilter = ref('');
const pagination = ref<PaginationState>({
  pageIndex: 0,
  pageSize: 10,
});

// === TanStack Table Instance ===
const table = useVueTable({
  get data() { return props.data; },
  get columns() { return props.columns; },

  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),

  // === State Sync (CRITICAL) ===
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

  // === Updaters (CRITICAL) ===
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

  // Optional: Reset page when data changes
  manualPagination: false,
});

// === Reset page when data changes ===
watch(() => props.data, () => {
  table.setPageIndex(0);
}, { deep: true });

// === Export Functions ===
const getExportData = () => {
  const rows = table.getFilteredRowModel().rows;
  const headers = table.getAllColumns()
    .filter(col => col.getIsVisible())
    .map(col => col.columnDef.header as string);
  
  const data = rows.map(row => {
    return table.getAllColumns()
      .filter(col => col.getIsVisible())
      .map(col => {
        const cell = row.getAllCells().find(c => c.column.id === col.id);
        return cell ? String(cell.getValue() ?? '') : '';
      });
  });

  return { headers, data };
};

// Print Function
const handlePrint = () => {
  const { headers, data } = getExportData();
  const title = props.title || 'Data Table';
  
  // Create a new window for printing
  const printWindow = window.open('', '_blank');
  if (!printWindow) return;

  const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>${title}</title>
      <style>
        @media print {
          @page {
            size: A4;
           
          }
          body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
        }
        body {
          font-family: Arial, sans-serif;
          padding: 20px;
          margin: 0;
        }
        h1 {
          text-align: center;
          margin-bottom: 20px;
          font-size: 24px;
          color: #333;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
        }
        th, td {
          border: 1px solid #ddd;
          padding: 12px 8px;
          text-align: left;
          font-size: 12px;
        }
        th {
          background-color: #f3f4f6;
          font-weight: bold;
          color: #374151;
        }
        tr:nth-child(even) {
          background-color: #f9fafb;
        }
        tr:hover {
          background-color: #f3f4f6;
        }
        .no-print {
          display: none;
        }
        .print-info {
          text-align: center;
          margin-top: 20px;
          font-size: 11px;
          color: #666;
        }
      </style>
    </head>
    <body>
      <h1>${title}</h1>
      <p style="text-align: center; color: #666; margin-bottom: 20px;">
        Generated on ${new Date().toLocaleString()}
      </p>
      <table>
        <thead>
          <tr>
            ${headers.map(h => `<th>${h}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${data.map(row => `
            <tr>
              ${row.map(cell => `<td>${cell}</td>`).join('')}
            </tr>
          `).join('')}
        </tbody>
      </table>
      <div class="print-info">
        Total Records: ${data.length}
      </div>
    </body>
    </html>
  `;

  printWindow.document.write(htmlContent);
  printWindow.document.close();
  
  // Wait for content to load then print
  printWindow.onload = () => {
    printWindow.focus();
    printWindow.print();
  };
   printWindow.onafterprint = function() {
    printWindow.close();
  };
};

// Excel Export Function
const handleExcelExport = () => {
  const { headers, data } = getExportData();
  
  // Create worksheet
  const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
  
  // Set column widths
  const colWidths = headers.map(() => ({ wch: 15 }));
  ws['!cols'] = colWidths;
  
  // Create workbook
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Data');
  
  // Generate filename
  const filename = `${props.title || 'data'}_${new Date().toISOString().split('T')[0]}.xlsx`;
  
  // Save file
  XLSX.writeFile(wb, filename);
};

// PDF Export Function
const handlePdfExport = () => {
  const { headers, data } = getExportData();
  const title = props.title || 'Data Table';
  
  // Create PDF
  const doc = new jsPDF('l', 'mm', 'a4'); // landscape, millimeters, A4
  
  // Add title
  doc.setFontSize(16);
  doc.text(title, 14, 15);
  
  // Add date
  doc.setFontSize(10);
  doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);
  
  // Add table
  autoTable(doc, {
    head: [headers],
    body: data,
    startY: 28,
    styles: {
      fontSize: 9,
      cellPadding: 3,
    },
    headStyles: {
      fillColor: [59, 130, 246], // Blue color
      textColor: 255,
      fontStyle: 'bold',
    },
    alternateRowStyles: {
      fillColor: [249, 250, 251], // Light gray
    },
    margin: { top: 28, right: 14, bottom: 14, left: 14 },
  });
  
  // Add footer
  const pageCount = doc.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
    doc.setPage(i);
    doc.setFontSize(8);
    doc.text(
      `Page ${i} of ${pageCount}`,
      doc.internal.pageSize.getWidth() / 2,
      doc.internal.pageSize.getHeight() - 10,
      { align: 'center' }
    );
  }
  
  // Save PDF
  const filename = `${props.title || 'data'}_${new Date().toISOString().split('T')[0]}.pdf`;
  doc.save(filename);
};
</script>

<template>
  <div class="space-y-4">
    <!-- Search & Controls -->
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
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

      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full lg:w-auto">
        <!-- Export Buttons -->
        <div class="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            @click="handlePrint"
            :disabled="props.loading || !props.data.length"
            class="flex items-center gap-2"
          >
            <Printer class="h-4 w-4" />
            Print
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="handleExcelExport"
            :disabled="props.loading || !props.data.length"
            class="flex items-center gap-2"
          >
            <FileSpreadsheet class="h-4 w-4" />
            Excel
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="handlePdfExport"
            :disabled="props.loading || !props.data.length"
            class="flex items-center gap-2"
          >
            <FileText class="h-4 w-4" />
            PDF
          </Button>
        </div>

        <!-- Search -->
        <Input
          placeholder="Search all columns..."
          :model-value="globalFilter"
          class="w-full sm:max-w-sm"
          @update:model-value="globalFilter = $event"
        />
      </div>
    </div>

    <!-- Table -->
    <div class="rounded-md border overflow-x-auto">
      <Table>
        <!-- Header -->
        <TableHeader>
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <TableHead v-for="header in headerGroup.headers" :key="header.id">
              <div
                v-if="!header.isPlaceholder"
                :class="[
                  header.column.getCanSort() ? 'cursor-pointer select-none flex items-center gap-2' : '',
                ]"
                @click="header.column.getToggleSortingHandler()?.($event)"
              >
                <FlexRender
                  :render="header.column.columnDef.header"
                  :props="header.getContext()"
                />
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

        <!-- Body -->
        <TableBody>
          <!-- Loading -->
          <TableRow v-if="props.loading">
            <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center">
              <div class="flex justify-center items-center gap-2 text-gray-600">
                <Loader2 class="h-5 w-5 animate-spin" />
                <span>Loading...</span>
              </div>
            </TableCell>
          </TableRow>

          <!-- No Data -->
          <TableRow v-else-if="!table.getRowModel().rows?.length">
            <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center text-gray-600">
              No data found. Please try again with another date.
            </TableCell>
          </TableRow>

          <!-- Rows -->
          <TableRow
            v-else
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            :data-state="row.getIsSelected() && 'selected'"
          >
            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Pagination -->
    <div
      class="flex flex-col sm:flex-row items-center justify-between gap-4"
      v-if="table.getPageCount() > 1"
    >
      <div class="text-sm text-gray-700">
        Showing
        {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }}
        to
        {{ Math.min(
            (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
            table.getFilteredRowModel().rows.length
          ) }}
        of {{ table.getFilteredRowModel().rows.length }} results
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          @click="table.setPageIndex(0)"
          :disabled="!table.getCanPreviousPage()"
        >
          <ChevronsLeft class="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="table.previousPage()"
          :disabled="!table.getCanPreviousPage()"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>
        <span class="text-sm">
          Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }}
        </span>
        <Button
          variant="outline"
          size="sm"
          @click="table.nextPage()"
          :disabled="!table.getCanNextPage()"
        >
          <ChevronRight class="h-4 w-4" />
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
</template>