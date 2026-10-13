<script setup lang="ts" generic="TData, TValue">
import {
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    type ColumnDef,
    type ColumnFiltersState,
    type PaginationState,
    type SortingState,
} from '@tanstack/vue-table';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/composables/useToast';
import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { FlexRender } from '@tanstack/vue-table';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { ArrowUpDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, FileSpreadsheet, FileText, Loader2, Printer } from 'lucide-vue-next';
import ExcelJS from 'exceljs';

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    loading?: boolean;
    title?: string;
    /**
     * Set when `data` is already one page from a server-side paginator (as
     * opposed to the full dataset). Hides the "Show N entries" picker and the
     * built-in pager, which would otherwise re-paginate an already-paginated
     * slice — the caller is expected to render its own pager driven by the
     * server's pagination meta instead.
     */
    serverPaginated?: boolean;
    /**
     * Column positions (0-based, indexing `columns` exactly as given — not
     * affected by sorting/visibility) to include for Print export. Omit to
     * export every visible column not marked `meta.excludeFromExport` (the
     * default). Explicit indices always win, even over `excludeFromExport`.
     */
    printColumns?: number[];
    /** Same as `printColumns`, independently, for the Excel export. */
    excelColumns?: number[];
    /** Same as `printColumns`, independently, for the PDF export. */
    pdfColumns?: number[];
}

const props = defineProps<DataTableProps<any, any>>();
const page = usePage<AppPageProps>();
const companyInfo = computed(() => page.props.companyInfo);
const { toast } = useToast();

// A column's searchable text follows the same rule as its export text (see
// resolveExportColumns() below): prefer meta.exportValue when the column
// isn't a plain primitive accessorKey, otherwise fall back to the raw cell
// value. Without this, TanStack's default global filter stringifies the raw
// getValue() — for an object-valued column (e.g. accessorKey: 'student')
// that produces the literal text "[object Object]", which can never match
// anything typed into the search box, no matter the query.
const columnSearchText = (row: { original: any; getValue: (id: string) => unknown }, columnId: string): string => {
    const column = table.getColumn(columnId);
    const meta = column?.columnDef.meta as { exportValue?: (original: any) => string } | undefined;
    const raw = meta?.exportValue ? meta.exportValue(row.original) : row.getValue(columnId);
    return raw !== null && typeof raw === 'object' ? '' : String(raw ?? '');
};

const globalSearchFilter = (row: { original: any; getValue: (id: string) => unknown }, columnId: string, filterValue: string): boolean => {
    const needle = String(filterValue ?? '').trim().toLowerCase();
    if (!needle) return true;
    return columnSearchText(row, columnId).toLowerCase().includes(needle);
};

// === Reactive State ===
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const globalFilter = ref('');
const pagination = ref<PaginationState>({
    pageIndex: 0,
    pageSize: props.serverPaginated ? Math.max(props.data.length, 1) : 10,
});

// === TanStack Table Instance ===
const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },

    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    globalFilterFn: globalSearchFilter,
    // TanStack's own default (`typeof value === 'string' || 'number'`) silently
    // drops any column whose raw value isn't already a primitive from global
    // filtering *before* globalFilterFn ever runs — which is exactly the object
    // columns (e.g. accessorKey: 'student') this fix needs searchable. Our
    // filterFn already handles every value type safely, so every column with
    // an accessorFn is eligible; letting it through doesn't need a column-level
    // `enableGlobalFilter: false` override to opt back out.
    getColumnCanGlobalFilter: () => true,

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

// console.log('table', table);
// console.log(props.serverPaginated, "server paginated");
// console.log(props.data.length, "data length");
// console.log(table.getPageCount(), "page count");
// === Reset page when data changes ===
watch(
    () => props.data,
    () => {
        if (props.serverPaginated) {
            table.setPageSize(Math.max(props.data.length, 1));
        }
        table.setPageIndex(0);
    },
    { deep: true },
);

// === Export Functions ===
// A column's raw getValue() is only safe to stringify when it's already a
// primitive (a plain accessorKey on a string/number). Columns built from a
// nested object (e.g. accessorKey: 'student') or with no accessorKey at all
// (an actions/computed column) need either an explicit `meta.exportValue`
// resolver or to be left out of the export entirely — otherwise this prints
// "[object Object]" or a blank cell. Both are opt-in via `columnDef.meta`,
// so every existing caller of this component keeps its current behavior.
const resolveExportColumns = (indices?: number[]) => {
    // Indices are positions in `columns` exactly as given — the original
    // definition order, unaffected by sorting/hiding — so "0, 1, 2" always
    // means the same columns regardless of table state.
    const allInOrder = table.getAllColumns();

    if (!indices?.length) {
        // Not passed — every visible column not explicitly excluded (unchanged default).
        return allInOrder.filter((col) => col.getIsVisible() && !(col.columnDef.meta as { excludeFromExport?: boolean } | undefined)?.excludeFromExport);
    }

    // An explicit index always wins, even over excludeFromExport/visibility —
    // picking a column by position is a deliberate, specific choice.
    return indices.map((i) => allInOrder[i]).filter((col): col is NonNullable<typeof col> => !!col);
};

const getExportData = (indices?: number[]) => {
    const rows = table.getFilteredRowModel().rows;
    const columns = resolveExportColumns(indices);
    const headers = columns.map((col) => col.columnDef.header as string);

    const data = rows.map((row) => columns.map((col) => columnSearchText(row, col.id)));

    return { headers, data };
};

const escapeHtml = (value: string): string =>
    value.replace(/[&<>"']/g, (character) => {
        const entities: Record<string, string> = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        };

        return entities[character];
    });

const companyInfoRows = () => {
    const company = companyInfo.value;
    if (!company) return [];

    return [
        ['Company', company.company_name],
        ['Address', company.company_address],
        ['PAN No.', company.company_pan_no],
        ['Phone No.', [company.company_phone_no, company.company_mobile_no].filter(Boolean).join(' / ') || null],
    ].filter((row): row is [string, string] => Boolean(row[1]));
};

// Print Function
const handlePrint = () => {
    const { headers, data } = getExportData(props.printColumns);
    const title = props.title || 'Data Table';
    const company = companyInfo.value;
 
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const companyHeader = company
        ? `<tr><td class="company-cell" colspan="${headers.length}"><div class="company-info">
            ${company.logo_url ? `<img src="${escapeHtml(company.logo_url)}" alt="">` : ''}
            <div>
              <strong>${escapeHtml(company.company_name)}</strong>
              ${company.company_address ? `<div>${escapeHtml(company.company_address)}</div>` : ''}
              ${company.company_pan_no ? `<div>PAN No.: ${escapeHtml(company.company_pan_no)}</div>` : ''}
              ${company.company_phone_no || company.company_mobile_no ? `<div>Phone No.: ${escapeHtml([company.company_phone_no, company.company_mobile_no].filter(Boolean).join(' / '))}</div>` : ''}
            </div>
          </div></td></tr>`
        : '';

    const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>${escapeHtml(title)}</title>
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
          margin-top: 10px;
        }
        thead {
          display: table-header-group;
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
        .company-cell {
          border: 0;
          padding: 0 0 8px;
        }
        .company-info {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 14px;
          text-align: left;
          font-size: 12px;
          line-height: 1.5;
        }
        .company-info strong {
          font-size: 17px;
        }
        .company-info img {
          width: 58px;
          height: 58px;
          object-fit: contain;
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
      <h1>${escapeHtml(title)}</h1>
      <p style="text-align: center; color: #666; margin-bottom: 20px;">
        Generated on ${escapeHtml(new Date().toLocaleString())}
      </p>
      <table>
        <thead>
          ${companyHeader}
          <tr>
            ${headers.map((h) => `<th>${escapeHtml(h)}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${data
              .map(
                  (row) => `
            <tr>
              ${row.map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}
            </tr>
          `,
              )
              .join('')}
        </tbody>
      </table>
      <div class="print-info">
        Total Records: ${data.length}
      </div>
    </body>
    </html>
  `;

    printWindow.onload = () => {
        printWindow.focus();
        printWindow.print();
    };
    printWindow.onafterprint = () => printWindow.close();
    printWindow.document.write(htmlContent);
    printWindow.document.close();
};

// Excel Export Function
const handleExcelExport = async () => {
    const { headers, data } = getExportData(props.excelColumns);
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Data');
    const columnCount = Math.max(headers.length, 2);
    const companyRows = companyInfoRows();

    for (const [index, [label, value]] of companyRows.entries()) {
        const row = worksheet.addRow(index === 0 ? [value] : [label, value]);
        worksheet.mergeCells(row.number, index === 0 ? 1 : 2, row.number, columnCount);
        row.font = { bold: true, size: index === 0 ? 16 : 11 };
    }

    if (companyRows.length) worksheet.addRow([]);

    if (props.title) {
        const titleRow = worksheet.addRow([props.title]);
        worksheet.mergeCells(titleRow.number, 1, titleRow.number, columnCount);
        titleRow.font = { bold: true, size: 13 };
    }

    const headerRow = worksheet.addRow(headers);
    headerRow.font = { bold: true };
    headerRow.eachCell((cell) => {
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE5E7EB' } };
        cell.border = {
            top: { style: 'thin' },
            bottom: { style: 'thin' },
            left: { style: 'thin' },
            right: { style: 'thin' },
        };
    });

    for (const row of data) worksheet.addRow(row);

    worksheet.columns = Array.from({ length: columnCount }, (_, index) => ({
        width: index < headers.length ? Math.max(15, headers[index].length + 2) : 15,
    }));
    worksheet.pageSetup = {
        orientation: 'landscape',
        fitToPage: true,
        fitToWidth: 1,
        fitToHeight: 0,
        printTitlesRow: `1:${headerRow.number}`,
    };

    try {
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${props.title || 'data'}_${new Date().toISOString().split('T')[0]}.xlsx`;
        link.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export table to Excel.', error);
        toast.error('Unable to export table to Excel.');
    }
};

// PDF Export Function
const handlePdfExport = async () => {
    const { headers, data } = getExportData(props.pdfColumns);
    const title = props.title || 'Data Table';
    const company = companyInfo.value;

    // Create PDF
    const doc = new jsPDF('l', 'mm', 'a4'); // landscape, millimeters, A4

    let logoDataUrl: string | null = null;
    if (company?.logo_url) {
        try {
            const response = await fetch(company.logo_url);
            if (!response.ok) throw new Error(`Logo request failed with status ${response.status}.`);

            const logoUrl = URL.createObjectURL(await response.blob());
            try {
                const image = new Image();
                image.src = logoUrl;
                await image.decode();

                const canvas = document.createElement('canvas');
                canvas.width = image.naturalWidth;
                canvas.height = image.naturalHeight;
                const context = canvas.getContext('2d');
                if (!context) throw new Error('Unable to prepare the company logo for the PDF.');

                context.drawImage(image, 0, 0);
                logoDataUrl = canvas.toDataURL('image/png');
            } finally {
                URL.revokeObjectURL(logoUrl);
            }
        } catch (error) {
            console.error('Failed to load the company logo for PDF export.', error);
            toast.error('Unable to load the company logo. PDF export was cancelled.');
            return;
        }
    }

    const pageWidth = doc.internal.pageSize.getWidth();
    const infoLines = company
        ? [
              company.company_address,
              company.company_pan_no ? `PAN No.: ${company.company_pan_no}` : null,
              [company.company_phone_no, company.company_mobile_no].filter(Boolean).length
                  ? `Phone No.: ${[company.company_phone_no, company.company_mobile_no].filter(Boolean).join(' / ')}`
                  : null,
          ]
              .filter((line): line is string => Boolean(line))
              .flatMap((line) => doc.splitTextToSize(line, pageWidth - 28))
        : [];
    const companyNameLines = company ? doc.splitTextToSize(company.company_name, pageWidth - (logoDataUrl ? 42 : 28)) : [];
    const titleY = company ? Math.max(39, 19 + infoLines.length * 4.5 + 5, 12 + companyNameLines.length * 5 + 5) : 15;
    const tableStartY = company ? titleY + 12 : 28;

    // Add title
    doc.setFontSize(16);
    doc.text(title, 14, titleY);

    // Add date
    doc.setFontSize(10);
    doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, titleY + 6);

    // Add table
    autoTable(doc, {
        head: [headers],
        body: data,
        startY: tableStartY,
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
        margin: { top: tableStartY, right: 14, bottom: 14, left: 14 },
        willDrawPage: () => {
            if (!company) return;

            if (logoDataUrl) {
                const imageSize = doc.getImageProperties(logoDataUrl);
                const scale = Math.min(18 / imageSize.width, 18 / imageSize.height);
                doc.addImage(logoDataUrl, 'PNG', 14, 6, imageSize.width * scale, imageSize.height * scale);
            }
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.text(companyNameLines, logoDataUrl ? 38 : 14, 12);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);

            if (infoLines.length) doc.text(infoLines, 14, 19);
        },
    });

    // Add footer
    const pageCount = doc.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.getWidth() / 2, doc.internal.pageSize.getHeight() - 10, { align: 'center' });
    }

    // Save PDF
    const filename = `${props.title || 'data'}_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(filename);
};
</script>

<template>
    <div class="mt-5 space-y-5">
        <!-- Search & Controls -->
        <div class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
            <div v-if="!props.serverPaginated" class="flex items-center gap-2">
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

            <div class="flex w-full flex-col items-start gap-3 sm:flex-row sm:items-center lg:ml-auto lg:w-auto">
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
                    @update:model-value="globalFilter = String($event)"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-md border">
            <Table>
                <!-- Header -->
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

                <!-- Body -->
                <TableBody>
                    <!-- Loading -->
                    <TableRow v-if="props.loading">
                        <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center">
                            <div class="flex items-center justify-center gap-2 text-gray-600">
                                <Loader2 class="h-5 w-5 animate-spin" />
                                <span>Loading...</span>
                            </div>
                        </TableCell>
                    </TableRow>

                    <!-- No Data -->
                    <TableRow v-else-if="!table.getRowModel().rows?.length">
                        <TableCell :colspan="table.getAllColumns().length" class="h-24 text-center text-gray-600">
                            No data found. Please try again .
                        </TableCell>
                    </TableRow>

                    <!-- Rows -->
                    <TableRow v-else v-for="row in table.getRowModel().rows" :key="row.id" :data-state="row.getIsSelected() && 'selected'">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row" v-if="!props.serverPaginated && table.getPageCount() > 1">
            <div class="text-sm text-gray-700">
                Showing
                {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }}
                to
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
                <span class="text-sm"> Page {{ table.getState().pagination.pageIndex + 1 }} of {{ table.getPageCount() }} </span>
                <Button variant="outline" size="sm" @click="table.nextPage()" :disabled="!table.getCanNextPage()">
                    <ChevronRight class="h-4 w-4" />
                </Button>
                <Button variant="outline" size="sm" @click="table.setPageIndex(table.getPageCount() - 1)" :disabled="!table.getCanNextPage()">
                    <ChevronsRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
