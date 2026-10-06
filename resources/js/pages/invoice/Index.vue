<!-- resources/js/pages/Invoices/Index.vue -->
<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import DateRangeFilter from '@/components/ui/date-range/DateRangeFilter.vue';
import { Toaster } from '@/components/ui/sonner';
import { usePermission } from '@/composables/usePermissions';
import { useInvoices } from '@/composables/useInvoice';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { BS_END, BS_START, MONTH_NAMES, bsToAd, daysInMonth, isoAd, todayBs } from '@/composables/bikramSambat';
import DataTable from '../students/Datatable.vue';
import CustomSelect from '../CustomSelect.vue';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Head, Link, router } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Eye, Loader2, Pencil, Plus, Printer, Trash2, X } from 'lucide-vue-next';
import { computed, h, onBeforeUnmount, ref, watch } from 'vue';
import 'vue-sonner/style.css';

interface InvoiceRow {
    id: number;
    invoice_number: string;
    issue_date: string;
    due_date: string;
    status: 'unpaid' | 'partial' | 'paid' | 'overdue' | 'cancelled';
    total_amount: number;
    paid_amount: number;
    student: { id: number; first_name: string; last_name: string; photo_url?: string };
    school_class?: { id: number; name: string };
    section?: { id: number; name: string };
}

interface Paginated {
    data: InvoiceRow[];
    current_page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

const props = defineProps<{
    invoices: Paginated;
    classes: Array<{ id: number; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    /**
     * Optional. Invoice count per status, calculated with the search / class / date
     * filters applied but WITHOUT the status filter. Powers the numbers on the status chips.
     */
    statusCounts?: Record<string, number>;
}>();

const { toast } = useToast();
const { can } = usePermission();
const { deleteInvoice } = useInvoices();
const deletingId = ref<number | null>(null);
const loading = ref(false);

/* ------------------------------------------------------------------ */
/* Filter state                                                        */
/* ------------------------------------------------------------------ */
const DEFAULTS = {
    search: '',
    status: '',
    class_id: '',
    date_field: 'issue_date', // which invoice date the range applies to
    from_date: '', // AD ISO (YYYY-MM-DD) — the picker's Nepali mode still hands us AD
    to_date: '',
    per_page: '15',
};

const queryParams = new URLSearchParams(window.location.search);
const filterState = ref({
    search: queryParams.get('search') ?? DEFAULTS.search,
    status: queryParams.get('status') ?? DEFAULTS.status,
    class_id: queryParams.get('class_id') ?? DEFAULTS.class_id,
    date_field: queryParams.get('date_field') ?? DEFAULTS.date_field,
    from_date: queryParams.get('from_date') ?? DEFAULTS.from_date,
    to_date: queryParams.get('to_date') ?? DEFAULTS.to_date,
    per_page: queryParams.get('per_page') ?? DEFAULTS.per_page,
});

const dateRangeFilter = computed({
    get: () => ({ from: filterState.value.from_date, to: filterState.value.to_date }),
    set: (value: { from: string; to: string }) => {
        filterState.value.from_date = value?.from ?? '';
        filterState.value.to_date = value?.to ?? '';
    },
});

const hasDateRange = computed(() => !!filterState.value.from_date && !!filterState.value.to_date);

const activeFilterCount = computed(
    () =>
        Number(!!filterState.value.search) +
        Number(!!filterState.value.status) +
        Number(!!filterState.value.class_id) +
        Number(hasDateRange.value),
);

/* ------------------------------------------------------------------ */
/* Filter tabs — one section visible at a time, so the panel's height  */
/* stays constant instead of stacking all three sections' height.      */
/* ------------------------------------------------------------------ */
const activeFilterTab = ref<'period' | 'status' | 'search'>('period');
const tabHasActiveFilter = computed(() => ({
    period: hasDateRange.value,
    status: !!filterState.value.status,
    search: !!filterState.value.search || !!filterState.value.class_id,
}));

/* ------------------------------------------------------------------ */
/* Date presets — Nepali months and fiscal year, resolved to AD dates  */
/* ------------------------------------------------------------------ */
interface Preset {
    key: string;
    label: string;
    from: string;
    to: string;
}

const pad = (n: number) => String(n).padStart(2, '0');
const localIso = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

const bsMonthRange = (y: number, m: number) => ({
    from: isoAd(bsToAd(y, m, 1)),
    to: isoAd(bsToAd(y, m, daysInMonth(y, m))),
});

const presets = computed<Preset[]>(() => {
    const now = new Date();
    const list: Preset[] = [];

    list.push({ key: 'today', label: 'Today', from: localIso(now), to: localIso(now) });

    const weekStart = new Date(now);
    weekStart.setDate(now.getDate() - now.getDay()); // Nepali week starts on Sunday
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    list.push({ key: 'week', label: 'This week', from: localIso(weekStart), to: localIso(weekEnd) });

    const t = todayBs();
    list.push({ key: 'bs-month', label: `This month · ${MONTH_NAMES.en[t.m - 1]}`, ...bsMonthRange(t.y, t.m) });

    const prevY = t.m === 1 ? t.y - 1 : t.y;
    const prevM = t.m === 1 ? 12 : t.m - 1;
    if (prevY >= BS_START) {
        list.push({ key: 'bs-last-month', label: `Last month · ${MONTH_NAMES.en[prevM - 1]}`, ...bsMonthRange(prevY, prevM) });
    }

    // Nepali fiscal year: Shrawan 1 (month 4) → end of Ashadh (month 3) of the next BS year
    const fyStart = t.m >= 4 ? t.y : t.y - 1;
    if (fyStart >= BS_START && fyStart + 1 <= BS_END) {
        list.push({
            key: 'fy',
            label: `Fiscal year ${fyStart}/${String(fyStart + 1).slice(2)}`,
            from: isoAd(bsToAd(fyStart, 4, 1)),
            to: bsMonthRange(fyStart + 1, 3).to,
        });
    }

    return list;
});

const activePresetKey = computed(
    () => presets.value.find((p) => p.from === filterState.value.from_date && p.to === filterState.value.to_date)?.key ?? null,
);

const applyPreset = (preset: Preset) => {
    if (activePresetKey.value === preset.key) {
        dateRangeFilter.value = { from: '', to: '' }; // click again to remove
        return;
    }
    dateRangeFilter.value = { from: preset.from, to: preset.to };
};

/* ------------------------------------------------------------------ */
/* Status chips                                                        */
/* ------------------------------------------------------------------ */
const statusChips = computed(() => [{ value: '', label: 'View all' }, ...props.statusOptions]);

const chipCount = (value: string): number | null => {
    if (!props.statusCounts) return null;
    if (value === '') return Object.values(props.statusCounts).reduce((sum, n) => sum + n, 0);
    return props.statusCounts[value] ?? 0;
};

/* ------------------------------------------------------------------ */
/* Apply filters — automatically, all filters combine (AND)            */
/* ------------------------------------------------------------------ */
const buildParams = () => {
    const f = filterState.value;
    const params: Record<string, string> = {};

    if (f.search.trim()) params.search = f.search.trim();
    if (f.status) params.status = f.status;
    if (f.class_id) params.class_id = f.class_id;
    // only send the range once both ends exist, so half-picked ranges don't hit the server
    if (f.from_date && f.to_date) {
        params.from_date = f.from_date;
        params.to_date = f.to_date;
        params.date_field = f.date_field;
    }
    if (f.per_page) params.per_page = f.per_page;

    return params;
};

let lastSent = JSON.stringify(buildParams());
let timer: ReturnType<typeof setTimeout> | undefined;

const applyFilters = () => {
    const params = buildParams();
    const key = JSON.stringify(params);
    if (key === lastSent) return;
    lastSent = key;

    loading.value = true;
    router.get('/invoice', params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

watch(
    filterState,
    () => {
        clearTimeout(timer);
        timer = setTimeout(applyFilters, 350); // debounce typing; chips and dates feel instant enough
    },
    { deep: true },
);
onBeforeUnmount(() => clearTimeout(timer));

// Page navigation bypasses the debounce — it's a click, not typing — and,
// unlike applyFilters(), explicitly carries a `page` param.
const goToPage = (page: number) => {
    if (page < 1 || page > props.invoices.last_page || page === props.invoices.current_page) return;
    const params = buildParams();
    if (page > 1) params.page = String(page);

    loading.value = true;
    router.get('/invoice', params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        },
    });
};

const resetFilters = () => {
    filterState.value = { ...DEFAULTS, per_page: filterState.value.per_page };
};

/* ------------------------------------------------------------------ */
/* Table                                                               */
/* ------------------------------------------------------------------ */
const breadcrumbs = [{ title: 'Invoices', href: '/invoice' }];

const money = (value: number) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const formatDate = (value: string) => new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
const balanceDue = (invoice: InvoiceRow) => invoice.total_amount - invoice.paid_amount;
const openPrintWindow = (invoiceId: number) => {
    window.open(`/invoice/${invoiceId}/print`, '_blank', 'noopener,noreferrer');
};

const statusBadge: Record<string, string> = {
    unpaid: 'bg-red-100 text-red-700',
    partial: 'bg-amber-100 text-amber-700',
    paid: 'bg-emerald-100 text-emerald-700',
    overdue: 'bg-red-100 text-red-700',
    cancelled: 'bg-slate-100 text-slate-600',
};

const columns: ColumnDef<InvoiceRow>[] = [
    {
        accessorKey: 'invoice_number',
        header: 'Invoice',
        cell: ({ row }) => h('div', { class: 'font-medium text-slate-900' }, row.original.invoice_number),
    },
    {
        accessorKey: 'student',
        header: 'Student',
        cell: ({ row }) => h('div', { class: 'text-slate-700' }, `${row.original.student.first_name} ${row.original.student.last_name}`),
    },
    {
        accessorKey: 'school_class',
        header: 'Class',
        cell: ({ row }) => h('div', { class: 'text-slate-500' }, row.original.school_class?.name || '-'),
    },
    {
        accessorKey: 'issue_date',
        header: 'Issue date',
        cell: ({ row }) => h('div', { class: 'text-slate-500' }, formatDate(row.original.issue_date)),
    },
    {
        accessorKey: 'due_date',
        header: 'Due date',
        cell: ({ row }) => h('div', { class: 'text-slate-500' }, formatDate(row.original.due_date)),
    },
    {
        accessorKey: 'total_amount',
        header: 'Total',
        cell: ({ row }) => h('div', { class: 'text-right font-medium text-slate-900' }, money(row.original.total_amount)),
    },
    {
        id: 'balance',
        header: 'Balance',
        enableSorting: false,
        cell: ({ row }) =>
            h(
                'div',
                { class: `text-right ${balanceDue(row.original) > 0 ? 'font-medium text-red-600' : 'text-slate-400'}` },
                money(balanceDue(row.original)),
            ),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) =>
            h('div', [
                h(
                    Badge,
                    { class: statusBadge[row.original.status] || 'bg-slate-100 text-slate-600', variant: 'secondary' },
                    () => row.original.status,
                ),
            ]),
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        cell: ({ row }) =>
            h('div', { class: 'flex justify-end gap-1' }, [
                h(
                    Button,
                    { variant: 'ghost', size: 'icon', title: 'View', onClick: () => router.visit(`/invoice/${row.original.id}`) },
                    () => h(Eye, { class: 'h-4 w-4' }),
                ),
                h(
                    Button,
                    { variant: 'ghost', size: 'icon', title: 'Print', onClick: () => openPrintWindow(row.original.id) },
                    () => h(Printer, { class: 'h-4 w-4' }),
                ),
                can('invoices.canEdit') &&
                    h(
                        Button,
                        { variant: 'ghost', size: 'icon', title: 'Edit', onClick: () => router.visit(`/invoice/${row.original.id}/edit`) },
                        () => h(Pencil, { class: 'h-4 w-4' }),
                    ),
                can('invoices.canDelete') &&
                    h(
                        Button,
                        {
                            variant: 'ghost',
                            size: 'icon',
                            class: 'text-red-600',
                            title: 'Delete',
                            disabled: deletingId.value === row.original.id,
                            onClick: () => removeInvoice(row.original),
                        },
                        () => (deletingId.value === row.original.id ? h(Loader2, { class: 'h-4 w-4 animate-spin' }) : h(Trash2, { class: 'h-4 w-4' })),
                    ),
            ]),
    },
];

const removeInvoice = async (invoice: InvoiceRow) => {
    if (!window.confirm(`Delete invoice ${invoice.invoice_number}? This cannot be undone.`)) return;
    deletingId.value = invoice.id;
    try {
        await deleteInvoice(invoice.id);
        toast.success('Invoice deleted');
        router.reload({ only: ['invoices', 'statusCounts'] });
    } catch {
        toast.error('Failed to delete invoice');
    } finally {
        deletingId.value = null;
    }
};
</script>

<template>
    <Head title="Invoices" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card class="w-full rounded-2xl shadow-lg">
                <CardHeader>
                    <CardTitle class="text-xl font-bold">
                        Invoice List
                        <Button v-if="can('invoices.canCreate')" as-child class="float-right ml-auto">
                            <Link href="/invoice/create">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Invoice
                            </Link>
                        </Button>
                    </CardTitle>
                </CardHeader>

                <CardContent class="space-y-4 pt-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <Tabs v-model="activeFilterTab">
                            <TabsList class="grid w-full grid-cols-3">
                                <TabsTrigger value="period">
                                    Period
                                    <span v-if="tabHasActiveFilter.period" class="ml-1 h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                </TabsTrigger>
                                <TabsTrigger value="status">
                                    Status
                                    <span v-if="tabHasActiveFilter.status" class="ml-1 h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                </TabsTrigger>
                                <TabsTrigger value="search">
                                    Search & class
                                    <span v-if="tabHasActiveFilter.search" class="ml-1 h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                </TabsTrigger>
                            </TabsList>
                        </Tabs>

                        <!-- Only the active tab's section renders, so the panel's height stays constant -->
                        <section v-if="activeFilterTab === 'period'" class="space-y-3 pt-4">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <div class="flex items-center gap-2 text-sm text-slate-500">
                                    <span>Date by</span>
                                    <div class="inline-flex rounded-full bg-slate-100 p-0.5">
                                        <button
                                            type="button"
                                            class="rounded-full px-3 py-1 text-xs font-medium transition"
                                            :class="filterState.date_field === 'issue_date' ? 'bg-slate-900 text-white' : 'text-slate-600'"
                                            @click="filterState.date_field = 'issue_date'"
                                        >
                                            Issue date
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-full px-3 py-1 text-xs font-medium transition"
                                            :class="filterState.date_field === 'due_date' ? 'bg-slate-900 text-white' : 'text-slate-600'"
                                            @click="filterState.date_field = 'due_date'"
                                        >
                                            Due date
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="preset in presets"
                                    :key="preset.key"
                                    type="button"
                                    class="rounded-full border px-3 py-1 text-sm transition"
                                    :class="
                                        activePresetKey === preset.key
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'
                                    "
                                    @click="applyPreset(preset)"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>

                            <DateRangeFilter v-model="dateRangeFilter" :from-label="'From date'" :to-label="'To date'" />
                        </section>

                        <section v-else-if="activeFilterTab === 'status'" class="space-y-2 pt-4">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="chip in statusChips"
                                    :key="chip.value || 'all'"
                                    type="button"
                                    class="flex items-center gap-2 rounded-full border px-3 py-1 text-sm transition"
                                    :class="
                                        filterState.status === chip.value
                                            ? 'border-slate-900 bg-slate-900 text-white'
                                            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'
                                    "
                                    @click="filterState.status = chip.value"
                                >
                                    {{ chip.label }}
                                    <span
                                        v-if="chipCount(chip.value) !== null"
                                        class="rounded-full px-1.5 text-xs"
                                        :class="filterState.status === chip.value ? 'bg-white/20' : 'bg-slate-100 text-slate-600'"
                                    >
                                        {{ chipCount(chip.value) }}
                                    </span>
                                </button>
                            </div>
                        </section>

                        <section v-else class="grid gap-3 pt-4 md:grid-cols-[1.5fr_1fr]">
                            <div>
                                <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Search</label>
                                <Input
                                    v-model="filterState.search"
                                    placeholder="Invoice or student name"
                                    class="h-10 rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus-visible:ring-slate-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Class</label>
                                <CustomSelect
                                    v-model="filterState.class_id"
                                    :options="[{ value: '', label: 'All classes' }, ...classes.map((c) => ({ value: String(c.id), label: c.label }))]"
                                    placeholder="All classes"
                                    class="h-10"
                                />
                            </div>
                        </section>

                        <!-- summary — always visible, regardless of active tab -->
                        <div class="flex flex-col items-start justify-between gap-2 border-t border-slate-200 pt-3 mt-3 sm:flex-row sm:items-center">
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
                                <span>
                                    <span class="font-medium text-slate-700">{{ invoices.total }}</span>
                                    {{ invoices.total === 1 ? 'invoice' : 'invoices' }}
                                    <template v-if="activeFilterCount">· {{ activeFilterCount }} {{ activeFilterCount === 1 ? 'filter' : 'filters' }} active</template>
                                    <template v-else>· showing everything</template>
                                </span>
                            </div>
                            <Button
                                v-if="activeFilterCount"
                                variant="ghost"
                                class="h-9 rounded-xl text-slate-600 hover:bg-slate-100"
                                @click="resetFilters"
                            >
                                <X class="mr-1.5 h-4 w-4" />
                                Reset filters
                            </Button>
                        </div>
                    </div>

                    <DataTable :columns="columns" :data="invoices.data" :loading="loading" server-paginated title="Invoice List" />

                    <div v-if="invoices.last_page > 1" class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 pt-4 sm:flex-row">
                        <p class="text-sm text-slate-500">Showing {{ invoices.from }}–{{ invoices.to }} of {{ invoices.total }}</p>
                        <div class="flex items-center gap-1.5">
                            <Button variant="outline" size="sm" :disabled="invoices.current_page <= 1" @click="goToPage(1)"><ChevronsLeft class="h-4 w-4" /></Button>
                            <Button variant="outline" size="sm" :disabled="invoices.current_page <= 1" @click="goToPage(invoices.current_page - 1)"><ChevronLeft class="h-4 w-4" /></Button>
                            <span class="px-2 text-sm text-slate-600">Page {{ invoices.current_page }} of {{ invoices.last_page }}</span>
                            <Button variant="outline" size="sm" :disabled="invoices.current_page >= invoices.last_page" @click="goToPage(invoices.current_page + 1)"><ChevronRight class="h-4 w-4" /></Button>
                            <Button variant="outline" size="sm" :disabled="invoices.current_page >= invoices.last_page" @click="goToPage(invoices.last_page)"><ChevronsRight class="h-4 w-4" /></Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>