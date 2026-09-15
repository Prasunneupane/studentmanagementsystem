<!-- resources/js/pages/Invoices/Index.vue -->
<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Toaster } from '@/components/ui/sonner';
import { usePermission } from '@/composables/usePermissions';
import { useInvoices } from '@/composables/useInvoice';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Loader2, Pencil, Plus, Printer, Receipt, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CustomSelect from '../CustomSelect.vue';
import 'vue-sonner/style.css';

interface Option {
    value: string;
    label: string;
}

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
    classes: Option[];
    filters?: { search?: string; status?: string; class_id?: string };
}>();

const { toast } = useToast();
const { can } = usePermission();
const { deleteInvoice } = useInvoices();

const breadcrumbs = [{ title: 'Invoices', href: '/invoice' }];

const search = ref(props.filters?.search || '');
const statusOption = ref<Option | null>(
    props.filters?.status ? { value: props.filters.status, label: props.filters.status } : null,
);
const classOption = ref<Option | null>(
    props.filters?.class_id ? props.classes.find((c) => c.value === props.filters?.class_id) || null : null,
);
const deletingId = ref<number | null>(null);

const statusOptions: Option[] = [
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partial', label: 'Partial' },
    { value: 'paid', label: 'Paid' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'cancelled', label: 'Cancelled' },
];

const statusBadge: Record<string, string> = {
    unpaid: 'bg-red-100 text-red-700',
    partial: 'bg-amber-100 text-amber-700',
    paid: 'bg-emerald-100 text-emerald-700',
    overdue: 'bg-red-100 text-red-700',
    cancelled: 'bg-slate-100 text-slate-600',
};

const money = (value: number) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const formatDate = (value: string) => new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });

let debounceTimer: ReturnType<typeof setTimeout>;
const applyFilters = (resetPage = true) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            '/invoice',
            {
                search: search.value || undefined,
                status: statusOption.value?.value || undefined,
                class_id: classOption.value?.value || undefined,
                page: resetPage ? 1 : props.invoices.current_page,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
};

watch(search, () => applyFilters());
watch(statusOption, () => applyFilters());
watch(classOption, () => applyFilters());

const goToPage = (page: number) => {
    router.get(
        '/invoice',
        { search: search.value || undefined, status: statusOption.value?.value || undefined, class_id: classOption.value?.value || undefined, page },
        { preserveState: true, replace: true },
    );
};

const removeInvoice = async (invoice: InvoiceRow) => {
    if (!window.confirm(`Delete invoice ${invoice.invoice_number}? This cannot be undone.`)) return;
    deletingId.value = invoice.id;
    try {
        await deleteInvoice(invoice.id);
        toast.success('Invoice deleted');
    } catch {
        toast.error('Failed to delete invoice');
    } finally {
        deletingId.value = null;
    }
};

const balanceDue = (invoice: InvoiceRow) => invoice.total_amount - invoice.paid_amount;

const pages = computed(() => {
    const total = props.invoices.last_page;
    const current = props.invoices.current_page;
    const range: number[] = [];
    for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) range.push(i);
    return range;
});
</script>

<template>
    <Head title="Invoices" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="w-full space-y-5 bg-slate-50 p-4 sm:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium text-blue-600">Fee management</p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950">Invoices</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ invoices.total }} invoices total</p>
                </div>
                <Button v-if="can('invoices.canCreate')" @click="router.visit('/invoice/create')"><Plus class="mr-2 h-4 w-4" />New invoice</Button>
            </div>

            <Card class="rounded-2xl border-0 shadow-sm">
                <CardContent class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input v-model="search" placeholder="Search by invoice number or student name..." class="pl-9" />
                    </div>
                    <div class="w-full sm:w-48"><CustomSelect v-model="statusOption" :options="statusOptions" placeholder="All statuses" clearable /></div>
                    <div class="w-full sm:w-48"><CustomSelect v-model="classOption" :options="classes" placeholder="All classes" clearable /></div>
                </CardContent>
            </Card>

            <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                <CardContent class="p-0">
                    <div v-if="!invoices.data.length" class="p-12 text-center">
                        <Receipt class="mx-auto mb-3 h-10 w-10 text-slate-300" />
                        <p class="text-sm text-slate-500">No invoices found.</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs tracking-wide text-slate-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3">Invoice</th>
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Class</th>
                                    <th class="px-4 py-3">Due date</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-right">Balance</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ invoice.invoice_number }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ invoice.student.first_name }} {{ invoice.student.last_name }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ invoice.school_class?.name || '-' }} <span v-if="invoice.section"> - {{ invoice.section.name }}</span></td>
                                    <td class="px-4 py-3 text-slate-500">{{ formatDate(invoice.due_date) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-900">{{ money(invoice.total_amount) }}</td>
                                    <td class="px-4 py-3 text-right" :class="balanceDue(invoice) > 0 ? 'font-medium text-red-600' : 'text-slate-400'">{{ money(balanceDue(invoice)) }}</td>
                                    <td class="px-4 py-3"><Badge :class="statusBadge[invoice.status]" variant="secondary">{{ invoice.status }}</Badge></td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" title="View" @click="router.visit(`/invoice/${invoice.id}`)"><Eye class="h-4 w-4" /></Button>
                                            <Button variant="ghost" size="icon" title="Print" @click="router.visit(`/invoice/${invoice.id}/print`)"><Printer class="h-4 w-4" /></Button>
                                            <Button v-if="can('invoices.canEdit')" variant="ghost" size="icon" title="Edit" @click="router.visit(`/invoice/${invoice.id}/edit`)"><Pencil class="h-4 w-4" /></Button>
                                            <Button v-if="can('invoices.canDelete')" variant="ghost" size="icon" class="text-red-600" title="Delete" :disabled="deletingId === invoice.id" @click="removeInvoice(invoice)">
                                                <Loader2 v-if="deletingId === invoice.id" class="h-4 w-4 animate-spin" />
                                                <Trash2 v-else class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="invoices.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 text-sm text-slate-500">
                        <span>Showing {{ invoices.from }}-{{ invoices.to }} of {{ invoices.total }}</span>
                        <div class="flex gap-1">
                            <Button variant="outline" size="sm" :disabled="invoices.current_page === 1" @click="goToPage(invoices.current_page - 1)">Prev</Button>
                            <Button v-for="page in pages" :key="page" size="sm" :variant="page === invoices.current_page ? 'default' : 'outline'" @click="goToPage(page)">{{ page }}</Button>
                            <Button variant="outline" size="sm" :disabled="invoices.current_page === invoices.last_page" @click="goToPage(invoices.current_page + 1)">Next</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>