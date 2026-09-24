<!-- resources/js/pages/Invoices/Create.vue -->
<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
// import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { Toaster } from '@/components/ui/sonner';
import { useInvoices } from '@/composables/useInvoice.js';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import DatePicker from '@/components/ui/customdatepicker/CustomDatePicker.vue';
import {
    ArrowLeft,
    CreditCard,
    Loader2,
    MessageSquarePlus,
    Pencil,
    Plus,
    QrCode,
    Save,
    StickyNote,
    Trash2,
    Wallet,
    X,
} from 'lucide-vue-next';
import NepaliDatePicker from '@/components/ui/nepalicalendar/NepaliDatePicker.vue';
import type { DateSelection } from '@/composables/bikramSambat';
import { computed, nextTick, ref } from 'vue';
import CustomSelect from '../CustomSelect.vue';
import 'vue-sonner/style.css';

function onSelect(sel: DateSelection) {
  console.log(sel.bs);   // "2082-01-15"  — Nepali calendar, what the customer sees
  console.log(sel.ad);   // "2025-04-28"  — English calendar, what you save to the DB
  console.log(sel.bsFormatted.ne); // "सोमबार, बैशाख १५, २०८२"
  console.log(sel.adFormatted.en); // "Monday, 28 April 2025"
}

interface Option {
    value: string;
    label: string;
    class_id?: number;
    section_id?: number;
}

const props = withDefaults(
    defineProps<{
        students: Option[];
        paymentMethods: Option[]; // from App\Enums\PaymentMethod
        paymentGateways?: Option[]; // esewa / khalti / other, for online_payment
        bankQrUrl?: string;
        esewaQrUrl?: string;
        khaltiQrUrl?: string;
        requireNotesOnDiscount?: boolean;
    }>(),
    {
        paymentGateways: () => [
            { value: 'esewa', label: 'eSewa' },
            { value: 'khalti', label: 'Khalti' },
            { value: 'other', label: 'Other' },
        ],
        requireNotesOnDiscount: true,
    },
);

const { toast } = useToast();
const { createInvoice } = useInvoices();

const breadcrumbs = [
    { title: 'Invoices', href: '/invoice' },
    { title: 'Create Invoice', href: '/invoice/create' },
];

/* ------------------------------------------------------------------ */
/* Fee items                                                            */
/* ------------------------------------------------------------------ */

interface LineItem {
    fee_type: string;
    description: string;
    quantity: number;
    unit_price: number;
    discount_type: 'fixed' | 'percentage';
    discount_value: number;
    showDescription: boolean;
}

const feeTypeSuggestions = ['Tuition Fee', 'Admission Fee', 'Exam Fee', 'Transport Fee', 'Library Fee', 'Lab Fee', 'Sports Fee', 'Miscellaneous'];

const newItem = (): LineItem => ({
    fee_type: '',
    description: '',
    quantity: 1,
    unit_price: 0,
    discount_type: 'fixed',
    discount_value: 0,
    showDescription: false,
});

/* ------------------------------------------------------------------ */
/* Payments (multiple methods per invoice)                              */
/* ------------------------------------------------------------------ */

interface PaymentEntry {
    id: number;
    amount: number;
    method: Option | null;
    gateway: Option | null; // only for online_payment
    gatewayCode: string; // maps to payment_code
    bankName: string;
    referenceNo: string;
    chequeNumber: string;
    chequeDate: string;
}

let paymentUid = 0;
const newPaymentEntry = (): PaymentEntry => ({
    id: ++paymentUid,
    amount: 0,
    method: null,
    gateway: null,
    gatewayCode: '',
    bankName: '',
    referenceNo: '',
    chequeNumber: '',
    chequeDate: '',
});

const form = ref({
    studentId: null as string | null,
    issueDate: new Date().toISOString().split('T')[0],
    dueDate: '',
    discountType: 'fixed' as 'fixed' | 'percentage',
    discountValue: 0,
    taxPercentage: 0,
    notes: '',
    payments: [] as PaymentEntry[],
    items: [newItem()] as LineItem[], // first row already loaded and ready to fill
});

const errors = ref<Record<string, string>>({});
const saving = ref(false);
const selectedStudent = computed(() => props.students.find((s) => s.value === form.value.studentId));
const bulkDiscountActive = computed(() => (form.value.discountValue || 0) > 0);

const dateValue = (value: string) => {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};
const formatDate = (date: Date | null | undefined) => (date ? date.toISOString().split('T')[0] : '');

/* ------------------------------------------------------------------ */
/* Fee row management                                                   */
/* ------------------------------------------------------------------ */

const addItem = () => {
    form.value.items.push(newItem());
    nextTick(() => {
        const el = document.querySelector<HTMLInputElement>(`[data-fee-input="${form.value.items.length - 1}"]`);
        el?.focus();
        el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
};

const removeItem = (index: number) => {
    if (form.value.items.length > 1) form.value.items.splice(index, 1);
    else form.value.items[0] = newItem();
};

const onRowKeydown = (event: KeyboardEvent, index: number) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    const isLast = index === form.value.items.length - 1;
    const item = form.value.items[index];
    if (isLast) {
        if (item.fee_type.trim() && item.unit_price > 0) addItem();
    } else {
        document.querySelector<HTMLInputElement>(`[data-fee-input="${index + 1}"]`)?.focus();
    }
};

const toggleDescription = (item: LineItem) => {
    item.showDescription = !item.showDescription;
};

/* ------------------------------------------------------------------ */
/* Payment row management                                               */
/* ------------------------------------------------------------------ */

const chequeDialogOpen = ref(false);
const chequeDialogEntryId = ref<number | null>(null);
const chequeDraft = ref({ number: '', date: '' });

const addPayment = () => form.value.payments.push(newPaymentEntry());
const removePayment = (id: number) => {
    form.value.payments = form.value.payments.filter((p) => p.id !== id);
};

const openChequeDialog = (entry: PaymentEntry) => {
    chequeDialogEntryId.value = entry.id;
    chequeDraft.value = { number: entry.chequeNumber, date: entry.chequeDate };
    chequeDialogOpen.value = true;
};

const saveChequeDialog = () => {
    const entry = form.value.payments.find((p) => p.id === chequeDialogEntryId.value);
    if (!entry) return;
    if (!chequeDraft.value.number.trim() || !chequeDraft.value.date) {
        toast.error('Enter both cheque number and cheque date');
        return;
    }
    entry.chequeNumber = chequeDraft.value.number.trim();
    entry.chequeDate = chequeDraft.value.date;
    chequeDialogOpen.value = false;
};

const cancelChequeDialog = () => {
    const entry = form.value.payments.find((p) => p.id === chequeDialogEntryId.value);
    // No prior cheque data saved for this row yet -> reset method so nothing false is stored
    if (entry && !entry.chequeNumber) entry.method = null;
    chequeDialogOpen.value = false;
};

// Only one method's extra data is ever kept per row — switching methods wipes the rest.
const setEntryMethod = (entry: PaymentEntry, value: Option | null) => {
    entry.method = value;
    const v = value?.value;

    if (v !== 'bank_transfer') entry.bankName = '';
    if (v !== 'online_payment') {
        entry.gateway = null;
        entry.gatewayCode = '';
    }
    if (v !== 'cheque') {
        entry.chequeNumber = '';
        entry.chequeDate = '';
    }
    if (!['bank_transfer', 'credit_card', 'debit_card', 'paypal'].includes(v ?? '')) {
        entry.referenceNo = '';
    }

    if (v === 'cheque') openChequeDialog(entry);
};

const gatewayQr = (entry: PaymentEntry) => {
    if (entry.method?.value !== 'online_payment') return null;
    if (entry.gateway?.value === 'esewa') return props.esewaQrUrl;
    if (entry.gateway?.value === 'khalti') return props.khaltiQrUrl;
    return null;
};

/* ------------------------------------------------------------------ */
/* Totals                                                               */
/* ------------------------------------------------------------------ */

const itemGross = (item: LineItem) => (item.quantity || 0) * (item.unit_price || 0);

const itemDiscount = (item: LineItem) => {
    if (bulkDiscountActive.value) return 0;
    const gross = itemGross(item);
    const value = Number(item.discount_value) || 0;
    return Math.min(gross, item.discount_type === 'percentage' ? (gross * value) / 100 : value);
};

const subtotal = computed(() => form.value.items.reduce((sum, item) => sum + itemGross(item), 0));
const itemDiscountTotal = computed(() => form.value.items.reduce((sum, item) => sum + itemDiscount(item), 0));
const itemNetSubtotal = computed(() => Math.max(subtotal.value - itemDiscountTotal.value, 0));

const discountAmount = computed(() => {
    if (form.value.discountType === 'percentage') {
        return Math.min(itemNetSubtotal.value, itemNetSubtotal.value * ((form.value.discountValue || 0) / 100));
    }
    return Math.min(itemNetSubtotal.value, form.value.discountValue || 0);
});

const taxableAmount = computed(() => Math.max(itemNetSubtotal.value - discountAmount.value, 0));
const taxAmount = computed(() => taxableAmount.value * ((form.value.taxPercentage || 0) / 100));
const totalAmount = computed(() => taxableAmount.value + taxAmount.value);

const totalEntered = computed(() => form.value.payments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0));
const appliedPaid = computed(() => Math.min(totalEntered.value, totalAmount.value));
const changeReturned = computed(() => Math.max(totalEntered.value - totalAmount.value, 0));
const balanceDue = computed(() => Math.max(totalAmount.value - appliedPaid.value, 0));

const invoiceStatus = computed<'unpaid' | 'partial' | 'paid'>(() => {
    if (appliedPaid.value <= 0) return 'unpaid';
    if (appliedPaid.value >= totalAmount.value) return 'paid';
    return 'partial';
});

const statusBadge: Record<string, string> = {
    unpaid: 'bg-red-100 text-red-700',
    partial: 'bg-amber-100 text-amber-700',
    paid: 'bg-emerald-100 text-emerald-700',
};

const hasDiscount = computed(() => itemDiscountTotal.value > 0 || discountAmount.value > 0);
const notesRequired = computed(() => props.requireNotesOnDiscount && hasDiscount.value);

const money = (value: number) => `Rs. ${value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const clearItemDiscounts = () => {
    if ((form.value.discountValue || 0) <= 0) return;
    form.value.items.forEach((item) => {
        item.discount_value = 0;
    });
};

/* ------------------------------------------------------------------ */
/* Submit                                                               */
/* ------------------------------------------------------------------ */

const validate = () => {
    errors.value = {};
    if (!form.value.studentId) errors.value.student = 'Select a student';
    if (!form.value.dueDate) errors.value.dueDate = 'Due date is required';

    form.value.items.forEach((item, index) => {
        if (!item.fee_type.trim()) errors.value[`item_${index}`] = 'Fee type is required';
        if (item.unit_price <= 0) errors.value[`price_${index}`] = 'Amount must be greater than 0';
    });

    form.value.payments.forEach((entry, index) => {
        if (entry.amount <= 0) {
            errors.value[`payment_${index}`] = 'Enter an amount';
            return;
        }
        if (!entry.method) {
            errors.value[`payment_${index}`] = 'Select a payment method';
            return;
        }
        if (entry.method.value === 'online_payment' && !entry.gateway) {
            errors.value[`payment_${index}`] = 'Select a gateway (eSewa / Khalti / Other)';
        }
        if (entry.method.value === 'cheque' && (!entry.chequeNumber || !entry.chequeDate)) {
            errors.value[`payment_${index}`] = 'Enter cheque details';
        }
    });

    if (notesRequired.value && !form.value.notes.trim()) {
        errors.value.notes = 'Notes are required when a discount is applied';
    }

    return !Object.keys(errors.value).length;
};

const submit = async () => {
    if (!validate()) {
        toast.error('Please complete the required fields');
        return;
    }
    if (!window.confirm('Are you sure you want to create this invoice?')) return;

    saving.value = true;
    try {
        await createInvoice({
            student_id: form.value.studentId,
            class_id: selectedStudent.value?.class_id ?? null,
            section_id: selectedStudent.value?.section_id ?? null,
            issue_date: form.value.issueDate,
            due_date: form.value.dueDate,
            discount_type: form.value.discountType,
            discount_value: form.value.discountValue,
            tax_percentage: form.value.taxPercentage,
            notes: form.value.notes,
            items: form.value.items.map(({ showDescription, ...item }) => item),
            payments: form.value.payments
                .filter((p) => p.amount > 0)
                .map((p, index, arr) => ({
                    amount: p.amount,
                    payment_method: p.method?.value,
                    payment_gateway: p.method?.value === 'online_payment' ? p.gateway?.value : p.method?.value === 'bank_transfer' ? 'bank_transfer' : null,
                    payment_code: p.gatewayCode || null,
                    reference_no: p.referenceNo || null,
                    bank_name: p.bankName || null,
                    cheque_number: p.chequeNumber || null,
                    cheque_date: p.chequeDate || null,
                    // change is only meaningful against the last entry submitted
                    change_returned: index === arr.length - 1 ? changeReturned.value : 0,
                })),
        });
        toast.success('Invoice created successfully');
    } catch (error: unknown) {
        const responseErrors = error && typeof error === 'object' ? (error as Record<string, string>) : {};
        errors.value = responseErrors;
        toast.error(Object.values(responseErrors)[0] || 'Failed to create invoice');
    } finally {
        saving.value = false;
    }
};

const handleFormKeydown = (event: KeyboardEvent) => {
    if (event.altKey && event.key.toLowerCase() === 'a') {
        event.preventDefault();
        addItem();
        return;
    }
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        submit();
    }
};
</script>

<template>
    <Head title="Create Invoice" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="w-full bg-slate-50 p-3 sm:p-4">
            <form @submit.prevent="submit" @keydown="handleFormKeydown">
                <Card class="overflow-hidden rounded-lg border shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between border-b px-3 py-2">
                        <span class="text-xs font-medium text-slate-500">New invoice</span>
                        <Button type="button" variant="ghost" size="sm" class="h-7 text-xs cursor-pointer hover:bg-slate-100 hover:text-slate-900" @click="router.visit('/invoice')">
                            <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />Back
                        </Button>
                    </CardHeader>

                    <CardContent class="grid gap-4 p-3 lg:grid-cols-3">
                        <!-- ============ LEFT (2/3) ============ -->
                        <div class="min-w-0 space-y-3 lg:col-span-2">
                            <div class="grid gap-2.5 sm:grid-cols-3">
                                <div>
                                    <Label class="text-[11px] font-medium text-slate-500">Student *</Label>
                                    <CustomSelect v-model="form.studentId" :options="props.students" placeholder="Search student..." />
                                    <p v-if="errors.student" class="mt-0.5 text-[11px] text-red-600">{{ errors.student }}</p>
                                </div>
                                <div>
                                    <Label class="text-[11px] font-medium text-slate-500">Issue date *</Label>
                                    <DatePicker v-model="form.issueDate"  />
                                </div>
                                <div>
                                    <Label class="text-[11px] font-medium text-slate-500">Due date *</Label>
                                    <DatePicker v-model="form.dueDate" />
                                    <p v-if="errors.dueDate" class="mt-0.5 text-[11px] text-red-600">{{ errors.dueDate }}</p>
                                </div>
                            </div>

                            <div class="rounded-lg border">
                                <div class="flex items-center justify-between border-b bg-slate-50/60 px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-900">Fee items</span>
                                        <Badge variant="secondary" class="h-5 px-1.5 text-[10px] font-medium">{{ form.items.length }}</Badge>
                                    </div>
                                    <Button type="button" size="sm" variant="outline" class="h-7 px-2 text-xs cursor-pointer" @click="addItem">
                                        <Plus class="mr-1 h-3.5 w-3.5" />Add
                                    </Button>
                                </div>

                                <div class="hidden gap-2 border-b bg-slate-50/90 px-3 py-1.5 text-[10px] font-semibold tracking-wider text-slate-500 uppercase md:grid md:grid-cols-[1.75rem_minmax(0,1fr)_3rem_6rem_8.5rem_6.5rem_3.5rem]">
                                    <div>#</div>
                                    <div>Fee type</div>
                                    <div class="text-right">Qty</div>
                                    <div class="text-right">Rate</div>
                                    <div>Discount</div>
                                    <div class="text-right">Amount</div>
                                    <div></div>
                                </div>

                                <div class="invoice-items-scroll max-h-[min(46vh,26rem)] divide-y overflow-y-auto">
                                    <div v-for="(item, index) in form.items" :key="index" class="px-3 py-2 transition-colors hover:bg-slate-50/60">
                                        <div class="grid grid-cols-1 items-center gap-2 md:grid-cols-[1.75rem_minmax(0,1fr)_3rem_6rem_8.5rem_6.5rem_3.5rem] md:gap-2">
                                            <div class="hidden items-center justify-center text-xs font-medium text-slate-400 md:flex">{{ index + 1 }}</div>

                                            <div class="min-w-0">
                                                <Label class="mb-0.5 block text-[10px] text-slate-500 md:hidden">Fee type *</Label>
                                                <Input
                                                    v-model="item.fee_type"
                                                    :data-fee-input="index"
                                                    list="fee-suggestions"
                                                    placeholder="e.g. Tuition Fee"
                                                    class="h-8 text-sm"
                                                    :class="{ 'border-red-500': errors[`item_${index}`] }"
                                                    @keydown="onRowKeydown($event, index)"
                                                />
                                            </div>

                                            <div>
                                                <Label class="mb-0.5 block text-[10px] text-slate-500 md:hidden">Qty</Label>
                                                <Input v-model.number="item.quantity" class="h-8 text-right text-sm" type="number" min="1" @keydown="onRowKeydown($event, index)" />
                                            </div>

                                            <div>
                                                <Label class="mb-0.5 block text-[10px] text-slate-500 md:hidden">Rate</Label>
                                                <Input
                                                    v-model.number="item.unit_price"
                                                    class="h-8 text-right text-sm"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    :class="{ 'border-red-500': errors[`price_${index}`] }"
                                                    @keydown="onRowKeydown($event, index)"
                                                />
                                            </div>

                                            <div>
                                                <Label class="mb-0.5 block text-[10px] text-slate-500 md:hidden">Discount</Label>
                                                <div class="flex gap-1">
                                                    <select v-model="item.discount_type" class="h-8 w-12 shrink-0 rounded-md border border-input bg-background px-1 text-xs" :disabled="bulkDiscountActive">
                                                        <option value="fixed">Rs</option>
                                                        <option value="percentage">%</option>
                                                    </select>
                                                    <Input v-model.number="item.discount_value" class="h-8 text-right text-sm" type="number" min="0" step="0.01" :disabled="bulkDiscountActive" @keydown="onRowKeydown($event, index)" />
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between md:block">
                                                <Label class="text-[10px] text-slate-500 md:hidden">Amount</Label>
                                                <div class="text-right text-sm font-semibold text-slate-900 tabular-nums">{{ money(itemGross(item) - itemDiscount(item)) }}</div>
                                            </div>

                                            <div class="flex items-center justify-end gap-0.5 md:justify-center">
                                                <Button type="button" variant="ghost" size="icon" class="h-7 w-7" :title="item.showDescription ? 'Hide note' : 'Add note'" @click="toggleDescription(item)">
                                                    <MessageSquarePlus class="h-3.5 w-3.5" :class="item.showDescription ? 'text-blue-600' : 'text-slate-400'" />
                                                </Button>
                                                <Button type="button" variant="ghost" size="icon" class="h-7 w-7 text-slate-400 hover:bg-red-50 hover:text-red-600" :disabled="form.items.length === 1" title="Remove" @click="removeItem(index)">
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </div>

                                        <div v-if="item.showDescription" class="mt-1.5 flex items-center gap-1.5 md:pl-[1.75rem]">
                                            <Input v-model="item.description" placeholder="Optional note for this fee..." class="h-7 text-xs" />
                                            <Button type="button" variant="ghost" size="icon" class="h-7 w-7 shrink-0 text-slate-400" @click="toggleDescription(item)">
                                                <X class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t bg-slate-50/40 p-2">
                                    <Button type="button" variant="outline" class="h-8 w-full border-dashed border-blue-200 bg-blue-50/50 text-xs text-blue-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800" @click="addItem">
                                        <Plus class="mr-1.5 h-3.5 w-3.5" />Add fee item
                                    </Button>
                                    <p class="mt-1 text-center text-[10px] text-slate-400">
                                        Press <kbd class="rounded border border-slate-300 bg-white px-1 font-mono text-[9px] text-slate-600">Enter</kbd> in the last row to add a new line
                                    </p>
                                </div>

                                <datalist id="fee-suggestions">
                                    <option v-for="fee in feeTypeSuggestions" :key="fee" :value="fee" />
                                </datalist>
                            </div>
                        </div>

                        <!-- ============ RIGHT (1/3 sidebar) ============ -->
                        <div class="space-y-3 lg:border-l lg:pl-4">
                            <!-- Discount & tax -->
                            <div>
                                <Label class="text-[11px] font-medium text-slate-500">Discount type</Label>
                                <Tabs v-model="form.discountType" class="mt-1 w-full">
                                    <TabsList class="grid h-7 w-full grid-cols-2">
                                        <TabsTrigger value="fixed" class="text-xs">Fixed</TabsTrigger>
                                        <TabsTrigger value="percentage" class="text-xs">Percentage</TabsTrigger>
                                    </TabsList>
                                </Tabs>

                                <div class="mt-2.5 grid grid-cols-2 gap-2">
                                    <div>
                                        <Label class="text-[11px] font-medium text-slate-500">Discount</Label>
                                        <Input v-model.number="form.discountValue" type="number" min="0" step="0.01" class="h-8 text-sm" @input="clearItemDiscounts" />
                                    </div>
                                    <div>
                                        <Label class="text-[11px] font-medium text-slate-500">Tax (%)</Label>
                                        <Input v-model.number="form.taxPercentage" type="number" min="0" max="100" step="0.01" class="h-8 text-sm" />
                                    </div>
                                </div>
                                <p v-if="bulkDiscountActive" class="mt-1.5 text-[10px] leading-snug text-amber-600">
                                    Item-level discounts are disabled while a bulk discount is applied.
                                </p>
                            </div>

                            <div class="border-t"></div>

                            <!-- Summary -->
                            <div class="space-y-1.5">
                                <span class="text-sm font-semibold text-slate-900">Summary</span>
                                <div class="flex justify-between text-xs text-slate-600">
                                    <span>Subtotal</span>
                                    <span class="font-medium text-slate-900 tabular-nums">{{ money(subtotal) }}</span>
                                </div>
                                <div v-if="itemDiscountTotal > 0" class="flex justify-between text-xs text-slate-600">
                                    <span>Item discount</span>
                                    <span class="font-medium text-red-600 tabular-nums">- {{ money(itemDiscountTotal) }}</span>
                                </div>
                                <div v-if="discountAmount > 0" class="flex justify-between text-xs text-slate-600">
                                    <span>Bulk discount</span>
                                    <span class="font-medium text-red-600 tabular-nums">- {{ money(discountAmount) }}</span>
                                </div>
                                <div v-if="taxAmount > 0" class="flex justify-between text-xs text-slate-600">
                                    <span>Tax</span>
                                    <span class="font-medium text-slate-900 tabular-nums">{{ money(taxAmount) }}</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between border-t pt-2.5">
                                    <span class="text-sm font-semibold text-slate-950">Total</span>
                                    <span class="rounded-md bg-blue-600 px-2.5 py-1 text-sm font-semibold text-white tabular-nums">{{ money(totalAmount) }}</span>
                                </div>
                            </div>

                            <div class="border-t"></div>

                            <!-- Payments -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                                        <Wallet class="h-3.5 w-3.5 text-slate-500" />Payments
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <Badge :class="statusBadge[invoiceStatus]" variant="secondary" class="text-[10px] capitalize">{{ invoiceStatus }}</Badge>
                                        <Button type="button" size="sm" variant="outline" class="h-6 px-2 text-[11px]" @click="addPayment">
                                            <Plus class="mr-1 h-3 w-3" />Add
                                        </Button>
                                    </div>
                                </div>

                                <p v-if="!form.payments.length" class="rounded-md border border-dashed px-2.5 py-2 text-[11px] text-slate-400">
                                    No payment collected yet — invoice will be created as unpaid. Click "Add" to record a payment now.
                                </p>

                                <div v-for="(entry, index) in form.payments" :key="entry.id" class="space-y-1.5 rounded-md border p-2">
                                    <div class="flex items-center gap-1.5">
                                        <Input v-model.number="entry.amount" type="number" min="0" step="0.01" placeholder="Amount" class="h-9 flex-1 text-xs" />
                                        <CustomSelect
                                            :model-value="entry.method"
                                            @update:model-value="(val) => setEntryMethod(entry, val)"
                                            :options="props.paymentMethods"
                                            placeholder="Method"
                                            class="h-9 flex-1 text-xs"
                                        />
                                        <Button type="button" variant="ghost" size="icon" class="h-9 w-9 shrink-0 text-slate-400 hover:bg-red-50 hover:text-red-600" @click="removePayment(entry.id)">
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>

                                    <!-- Bank transfer -->
                                    <div v-if="entry.method?.value === 'bank transfer'" class="space-y-1.5">
                                        <div class="flex gap-1.5">
                                            <Input v-model="entry.bankName" placeholder="Bank name" class="h-9 flex-1 text-xs" />
                                            <Input v-model="entry.referenceNo" placeholder="Reference no." class="h-9 flex-1 text-xs" />
                                        </div>
                                        <div v-if="props.bankQrUrl" class="flex items-center gap-2 rounded bg-slate-50 p-1.5">
                                            <img :src="props.bankQrUrl" alt="Bank QR" class="h-14 w-14 rounded border bg-white object-contain" />
                                            <span class="flex items-center gap-1 text-[10px] text-slate-500"><QrCode class="h-3 w-3" />Scan to transfer</span>
                                        </div>
                                    </div>

                                    <!-- Online payment (eSewa / Khalti / other) -->
                                    <div v-else-if="entry.method?.value === 'online payment'" class="space-y-1.5">
                                        <div class="flex gap-1.5">
                                            <CustomSelect v-model="entry.gateway" :options="props.paymentGateways" placeholder="Gateway" class="h-9 flex-1 text-xs" />
                                            <Input v-model="entry.gatewayCode" placeholder="Transaction code" class="h-9 flex-1 text-xs" />
                                        </div>
                                        <div v-if="gatewayQr(entry)" class="flex items-center gap-2 rounded bg-slate-50 p-1.5">
                                            <img :src="gatewayQr(entry) ?? undefined" :alt="`${entry.gateway?.label} QR`" class="h-14 w-14 rounded border bg-white object-contain" />
                                            <span class="flex items-center gap-1 text-[10px] text-slate-500"><QrCode class="h-3 w-3" />Scan with {{ entry.gateway?.label }}</span>
                                        </div>
                                    </div>

                                    <!-- Card / PayPal -->
                                    <div v-else-if="['credit_card', 'debit_card', 'paypal'].includes(entry.method?.value ?? '')" class="flex items-center gap-1.5">
                                        <CreditCard class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                        <Input v-model="entry.referenceNo" placeholder="Transaction / reference ID" class="h-7 flex-1 text-xs" />
                                    </div>

                                    <!-- Cheque -->
                                    <div v-else-if="entry.method?.value === 'cheque'" class="flex items-center justify-between rounded bg-slate-50 px-2 py-1.5 text-[11px]">
                                        <span v-if="entry.chequeNumber" class="text-slate-600">Cheque #{{ entry.chequeNumber }} · {{ entry.chequeDate }}</span>
                                        <span v-else class="text-amber-600">Cheque details not entered</span>
                                        <Button type="button" variant="ghost" size="icon" class="h-6 w-6 text-slate-400" @click="openChequeDialog(entry)">
                                            <Pencil class="h-3 w-3" />
                                        </Button>
                                    </div>

                                    <p v-if="errors[`payment_${index}`]" class="text-[10.5px] text-red-600">{{ errors[`payment_${index}`] }}</p>
                                </div>

                                <div class="space-y-1 pt-0.5">
                                    <div v-if="changeReturned > 0" class="flex justify-between rounded-md bg-amber-50 px-2.5 py-1.5 text-xs">
                                        <span class="text-amber-700">Change to return</span>
                                        <span class="font-semibold tabular-nums text-amber-700">{{ money(changeReturned) }}</span>
                                    </div>
                                    <div class="flex justify-between rounded-md bg-slate-50 px-2.5 py-1.5 text-xs">
                                        <span class="text-slate-500">Balance due</span>
                                        <span class="font-semibold tabular-nums" :class="balanceDue > 0 ? 'text-red-600' : 'text-emerald-600'">{{ money(balanceDue) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes — right above the create button, required when discount applied -->
                            <div>
                                <div class="mb-1.5 flex items-center gap-1.5">
                                    <StickyNote class="h-3.5 w-3.5 text-slate-500" />
                                    <Label class="text-[11px] font-medium text-slate-500">
                                        Notes <span v-if="notesRequired" class="text-red-600">*</span>
                                    </Label>
                                </div>
                                <Textarea
                                    v-model="form.notes"
                                    placeholder="e.g. Fees once paid are non-refundable."
                                    rows="2"
                                    class="min-h-[3.5rem] resize-y text-sm"
                                    :class="{ 'border-red-500': errors.notes }"
                                />
                                <p v-if="errors.notes" class="mt-0.5 text-[11px] text-red-600">{{ errors.notes }}</p>
                            </div>

                            <Button type="submit" :disabled="saving" class="h-9 w-full text-sm cursor-pointer">
                                <Loader2 v-if="saving" class="mr-1.5 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-1.5 h-4 w-4" />
                                {{ saving ? 'Creating...' : 'Create invoice' }}
                            </Button>
                            <p class="text-center text-[10px] text-slate-400">
                                <kbd class="rounded border border-slate-300 bg-white px-1 font-mono text-[9px]">Ctrl</kbd>+<kbd class="rounded border border-slate-300 bg-white px-1 font-mono text-[9px]">Enter</kbd>&nbsp;to save
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>

        <!-- Cheque details popup -->
        <Dialog v-model:open="chequeDialogOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader><DialogTitle>Cheque details</DialogTitle></DialogHeader>
                <div class="space-y-3">
                    <div>
                        <Label class="text-xs">Cheque number *</Label>
                        <Input v-model="chequeDraft.number" placeholder="e.g. 000123" class="h-8 text-sm" />
                    </div>
                    <div>
                        <Label class="text-xs">Cheque date *</Label>
                        <DatePicker :model-value="dateValue(chequeDraft.date)" @update:model-value="chequeDraft.date = formatDate($event)" />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" size="sm" @click="cancelChequeDialog">Cancel</Button>
                    <Button type="button" size="sm" @click="saveChequeDialog">Save</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.invoice-items-scroll {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--muted-foreground) / 0.4) transparent;
}
.invoice-items-scroll::-webkit-scrollbar {
    width: 8px;
}
.invoice-items-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.invoice-items-scroll::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.4);
    border: 2px solid transparent;
    background-clip: content-box;
    border-radius: 9999px;
}
.invoice-items-scroll::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.6);
    background-clip: content-box;
}
</style>