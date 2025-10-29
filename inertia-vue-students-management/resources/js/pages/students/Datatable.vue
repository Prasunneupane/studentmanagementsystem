<script setup lang="ts" generic="TData, TValue">
import { ref } from 'vue';
import {
  FlexRender,
  getCoreRowModel,
  useVueTable,
  getPaginationRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  type ColumnDef,
  type SortingState,
  type ColumnFiltersState,
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
import { Loader2, ArrowUpDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';

interface DataTableProps {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  loading?: boolean;
}

const props = defineProps<DataTableProps>();

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const globalFilter = ref('');
const pagination = ref({
  pageIndex: 0,
  pageSize: 10,
});

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
});
</script>

<template>
  <div class="space-y-4">
    <!-- Search & Page Size -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-700">Show</span>
        <select
          :value="pagination.pageSize"
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

      <Input
        placeholder="Search all columns..."
        :model-value="globalFilter"
        class="max-w-sm"
        @input="globalFilter = ($event.target as HTMLInputElement).value"
      />
    </div>

    <!-- Table -->
    <div class="rounded-md border overflow-x-auto">
      <Table class="w-full">
        <!-- ✅ Thead Always Visible -->
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
                  class="h-4 w-4"
                  :class="{
                    'text-blue-600': header.column.getIsSorted(),
                  }"
                />
              </div>
            </TableHead>
          </TableRow>
        </TableHeader>

        <!-- ✅ Tbody Logic -->
        <TableBody>
          <!-- Loader -->
          <TableRow v-if="props.loading">
            <TableCell
              :colspan="table.getAllColumns().length"
              class="h-24 text-center"
            >
              <div class="flex justify-center items-center gap-2 text-gray-600">
                <Loader2 class="h-5 w-5 animate-spin" />
               
              </div>
            </TableCell>
          </TableRow>

          <!-- No Data -->
          <TableRow v-else-if="!table.getRowModel().rows?.length">
            <TableCell
              :colspan="table.getAllColumns().length"
              class="h-24 text-center text-gray-600"
            >
              No data found.Please try again with another date.
            </TableCell>
          </TableRow>

          <!-- Data Rows -->
          <template v-else>
            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              :data-state="row.getIsSelected() && 'selected'"
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>

    <!-- Pagination -->
    <div
      class="flex flex-col sm:flex-row items-center justify-between gap-4"
      v-if="table.getFilteredRowModel().rows.length > 0"
    >
      <div class="text-sm text-gray-700">
        Showing
        {{
          table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1
        }}
        to
        {{
          Math.min(
            (table.getState().pagination.pageIndex + 1) *
              table.getState().pagination.pageSize,
            table.getFilteredRowModel().rows.length
          )
        }}
        of {{ table.getFilteredRowModel().rows.length }} results
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          @click="() => table.setPageIndex(0)"
          :disabled="!table.getCanPreviousPage()"
        >
          <ChevronsLeft class="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="() => table.previousPage()"
          :disabled="!table.getCanPreviousPage()"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>
        <span class="text-sm">
          Page {{ table.getState().pagination.pageIndex + 1 }} of
          {{ table.getPageCount() }}
        </span>
        <Button
          variant="outline"
          size="sm"
          @click="() => table.nextPage()"
          :disabled="!table.getCanNextPage()"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="() => table.setPageIndex(table.getPageCount() - 1)"
          :disabled="!table.getCanNextPage()"
        >
          <ChevronsRight class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </div>
</template>
