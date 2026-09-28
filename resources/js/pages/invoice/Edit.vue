<!-- resources/js/pages/Invoices/Edit.vue -->
<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Toaster } from '@/components/ui/sonner';
import { useInvoices } from '@/composables/useInvoice';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Invoice } from '@/composables/invoiceService';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Pencil, Plus, Printer, Save, Trash2, Receipt, Wallet, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import 'vue-sonner/style.css';

interface Payment {
    id: number;
    amount: number;
    paid_on: string;
    payment_method: string;
    reference_no?: string;
    note?: string;
}

const props = defineProps<{ invoice: Invoice & { payments?: Payment[] } }>();

const { toast } = useToast();
const { updateInvoice, recordPayment, updatePayment } = useInvoices();

const breadcrumbs = [
    { title: 'Invoices', href: '/invoice' },
    { title: 'Edit invoice', href: `/invoice/${props.invoice.id}/edit` },
];

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'card', label: 'Card' },
    { value: 'online', label: 'Online' },
    { value: 'cheque', label: 'Cheque' },
];

const feeTypeSuggestions = ['Tuition Fee', 'Admission Fee', 'Exam Fee', 'Transport Fee', 'Library Fee', 'Lab Fee', 'Sports Fee', 'Miscellaneous'];

interface LineItem {
    id?: number;
    fee_type: string;
    description: string;
    quantity: number;
    unit_price: number;
    discount_type: 'fixed' | 'percentage';
    discount_value: number;
}

const form = ref({
    issueDate: props.invoice.issue_date,
    dueDate: props.invoice.due_date,
    discountType: (props.invoice.discount_type || 'fixed') as 'fixed' | 'percentage',
    discountValue: Number(props.invoice.discount_value) || 0,
    taxPercentage: Number(props.invoice.tax_percentage) || 0,
    notes: props.invoice.notes || '',
    items: props.invoice.items.map((item) => ({
        id: item.id,
        fee_type: item.fee_type,
        description: item.description || '',
        quantity: item.quantity,
        unit_price: Number(item.unit_price),
        discount_type: item.discount_type || 'fixed',
        discount_value: Number(item.discount_value) || 0,
    })) as LineItem[],
});

const errors = ref<Record<string, string>>({});
const saving = ref(false);
const hasPayments = computed(() => Number(props.invoice.paid_amount) > 0);
const balanceDue = computed(() => Math.max(Number(props.invoice.total_amount) - Number(props.invoice.paid_amount), 0));
const paymentSaving = ref(false);
const paymentErrors = ref<Record<string, string>>({});
const paymentForm = ref({
    id: null as number | null,
    amount: balanceDue.value > 0 ? balanceDue.value : 0,
    paidOn: new Date().toISOString().split('T')[0],
    method: paymentMethods[0],
    referenceNo: '',
    note: '',
});

const resetPaymentForm = () => {
    paymentForm.value = {
        id: null,
        amount: balanceDue.value > 0 ? balanceDue.value : 0,
        paidOn: new Date().toISOString().split('T')[0],
        method: paymentMethods[0],
        referenceNo: '',
        note: '',
    };
    paymentErrors.value = {};
};

const paymentLimit = computed(() => {
    const invoiceTotal = Number(props.invoice.total_amount) || 0;
    const paidTotal = Number(props.invoice.paid_amount) || 0;
    const editingPayment = props.invoice.payments?.find((payment) => payment.id === paymentForm.value.id);

    if (!editingPayment) {
        return Math.max(0, invoiceTotal - paidTotal);
    }

    return Math.max(0, invoiceTotal - (paidTotal - Number(editingPayment.amount)));
});

const beginPaymentEdit = (payment: Payment) => {
    paymentErrors.value = {};
    paymentForm.value = {
        id: payment.id,
        amount: Number(payment.amount),
        paidOn: payment.paid_on || new Date().toISOString().split('T')[0],
        method: paymentMethods.find((method) => method.value === payment.payment_method) || paymentMethods[0],
        referenceNo: payment.reference_no || '',
        note: payment.note || '',
    };
};

const dateValue = (value: string) => {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};
const formatDate = (date: Date | null | undefined) => (date ? date.toISOString().split('T')[0] : '');
const formatDateDisplay = (value: string) => new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
const money = (value: number) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const openPrintWindow = (invoiceId: number) => {
    window.open(`/invoice/${invoiceId}/print`, '_blank', 'noopener,noreferrer');
};

const addItem = () => form.value.items.push({ fee_type: '', description: '', quantity: 1, unit_price: 0, discount_type: 'fixed', discount_value: 0 });
const removeItem = (index: number) => {
    if (form.value.items.length > 1) form.value.items.splice(index, 1);
};

const itemGross = (item: LineItem) => (item.quantity || 0) * (item.unit_price || 0);
const itemDiscount = (item: LineItem) => {
    if ((form.value.discountValue || 0) > 0) return 0;
    const gross = itemGross(item);
    const value = Number(item.discount_value) || 0;
    return Math.min(gross, item.discount_type === 'percentage' ? gross * value / 100 : value);
};
const subtotal = computed(() => form.value.items.reduce((sum, item) => sum + itemGross(item), 0));
const itemDiscountTotal = computed(() => form.value.items.reduce((sum, item) => sum + itemDiscount(item), 0));
const itemNetSubtotal = computed(() => Math.max(subtotal.value - itemDiscountTotal.value, 0));
const discountAmount = computed(() => {
    if (form.value.discountType === 'percentage') return Math.min(itemNetSubtotal.value, itemNetSubtotal.value * ((form.value.discountValue || 0) / 100));
    return Math.min(itemNetSubtotal.value, form.value.discountValue || 0);
});
const taxableAmount = computed(() => Math.max(itemNetSubtotal.value - discountAmount.value, 0));
const taxAmount = computed(() => taxableAmount.value * ((form.value.taxPercentage || 0) / 100));
const totalAmount = computed(() => taxableAmount.value + taxAmount.value);
const clearItemDiscounts = () => {
    if ((form.value.discountValue || 0) <= 0) return;
    form.value.items.forEach((item) => { item.discount_value = 0; });
};

const savePayment = async () => {
    paymentErrors.value = {};

    if (!paymentForm.value.amount || paymentForm.value.amount <= 0) {
        paymentErrors.value.amount = 'Enter a valid amount';
        return;
    }

    const maxAllowed = paymentLimit.value;
    if (paymentForm.value.amount > maxAllowed) {
        paymentErrors.value.amount = `Amount cannot exceed ${money(maxAllowed)} for this invoice.`;
        return;
    }

    const payload = {
        amount: paymentForm.value.amount,
        paid_on: paymentForm.value.paidOn,
        payment_method: paymentForm.value.method.value,
        reference_no: paymentForm.value.referenceNo || null,
        note: paymentForm.value.note || null,
    };

    paymentSaving.value = true;
    try {
        if (paymentForm.value.id) {
            await updatePayment(props.invoice.id, paymentForm.value.id, payload);
            toast.success('Payment updated successfully');
        } else {
            await recordPayment(props.invoice.id, payload);
            toast.success('Payment recorded successfully');
        }

        resetPaymentForm();
        router.reload({ only: ['invoice'] });
    } catch {
        toast.error(paymentForm.value.id ? 'Failed to update payment' : 'Failed to record payment');
    } finally {
        paymentSaving.value = false;
    }
};

const validate = () => {
    errors.value = {};
    if (!form.value.dueDate) errors.value.dueDate = 'Due date is required';
    form.value.items.forEach((item, index) => {
        if (!item.fee_type.trim()) errors.value[`item_${index}`] = 'Fee type is required';
        if (item.unit_price <= 0) errors.value[`price_${index}`] = 'Amount must be greater than 0';
    });
    return !Object.keys(errors.value).length;
};

const submit = async () => {
    if (!validate()) {
        toast.error('Please complete the required fields');
        return;
    }
    if (totalAmount.value < Number(props.invoice.paid_amount)) {
        toast.error('Total cannot be less than the amount already paid');
        return;
    }
    saving.value = true;
    try {
        await updateInvoice(props.invoice.id, {
            student_id: props.invoice.student_id,
            class_id: props.invoice.class_id,
            section_id: props.invoice.section_id,
            issue_date: form.value.issueDate,
            due_date: form.value.dueDate,
            discount_type: form.value.discountType,
            discount_value: form.value.discountValue,
            tax_percentage: form.value.taxPercentage,
            notes: form.value.notes,
            items: form.value.items,
        });
        toast.success('Invoice updated successfully');
    } catch {
        toast.error('Failed to update invoice');
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Head :title="`Edit ${invoice.invoice_number}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="w-full space-y-5 bg-slate-50 p-4 sm:p-6">
            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
                <div class="flex-1 rounded-2xl border bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-500">Student</p>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-slate-950">Edit invoice</h1>
                            <p class="mt-1 text-sm text-slate-600">{{ invoice.student?.first_name }} {{ invoice.student?.last_name }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ invoice.invoice_number }}</span>
                    </div>
                </div>

                <div class="rounded-2xl border bg-white p-2 shadow-sm">
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="router.visit(`/invoice/${invoice.id}`)"><ArrowLeft class="mr-2 h-4 w-4" />Back</Button>
                        <Button variant="outline" size="sm" @click="openPrintWindow(invoice.id)"><Printer class="mr-2 h-4 w-4" />Print</Button>
                    </div>
                </div>
            </div>

            <div v-if="hasPayments" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                This invoice already has {{ money(invoice.paid_amount) }} recorded in payments. The new total cannot go below that amount.
            </div>

            <form @submit.prevent="submit" class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-white text-slate-900">
                            <CardTitle class="flex items-center gap-2 text-lg"><Receipt class="h-5 w-5" />Invoice details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-5 p-5 sm:p-7">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div><Label>Issue date *</Label><DatePicker :model-value="dateValue(form.issueDate)" @update:model-value="form.issueDate = formatDate($event)" /></div>
                                <div>
                                    <Label>Due date *</Label>
                                    <DatePicker :model-value="dateValue(form.dueDate)" @update:model-value="form.dueDate = formatDate($event)" />
                                    <p v-if="errors.dueDate" class="text-sm text-red-600">{{ errors.dueDate }}</p>
                                </div>
                            </div>

                            <div class="border-t pt-5">
                                <div class="mb-3 flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-slate-900">Fee items</h2>
                                    <Button type="button" size="sm" variant="outline" @click="addItem"><Plus class="mr-2 h-4 w-4" />Add item</Button>
                                </div>

                                <div class="space-y-3">
                                    <div v-for="(item, index) in form.items" :key="item.id ?? index" class="rounded-xl border bg-white p-4">
                                        <div class="grid gap-3 sm:grid-cols-12">
                                            <div class="sm:col-span-4">
                                                <Label class="text-xs">Fee type *</Label>
                                                <Input v-model="item.fee_type" list="fee-suggestions" placeholder="Tuition Fee" :class="{ 'border-red-500': errors[`item_${index}`] }" />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <Label class="text-xs">Description</Label>
                                                <Input v-model="item.description" placeholder="Optional note" />
                                            </div>
                                            <div class="sm:col-span-1">
                                                <Label class="text-xs">Qty</Label>
                                                <Input v-model.number="item.quantity" class="w-full" type="number" min="1" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <Label class="text-xs">Unit price *</Label>
                                                <Input v-model.number="item.unit_price" type="number" min="0" step="0.01" :class="{ 'border-red-500': errors[`price_${index}`] }" />
                                            </div>
                                            <div class="sm:col-span-3">
                                                <Label class="text-xs">Item discount</Label>
                                                <div class="flex gap-1">
                                                    <select v-model="item.discount_type" class="h-10 rounded-md border border-input bg-background px-2 text-xs">
                                                        <option value="fixed">Fixed</option>
                                                        <option value="percentage">%</option>
                                                    </select>
                                                    <Input v-model.number="item.discount_value" type="number" min="0" step="0.01" :disabled="(form.discountValue || 0) > 0" />
                                                </div>
                                            </div>
                                            <div class="flex min-w-0 items-end justify-between gap-1 sm:col-span-2">
                                                <span class="text-sm font-medium text-slate-700">{{ money(itemGross(item) - itemDiscount(item)) }}</span>
                                                <Button type="button" variant="ghost" size="icon" class="text-red-600" :disabled="form.items.length === 1" @click="removeItem(index)"><Trash2 class="h-4 w-4" /></Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <datalist id="fee-suggestions">
                                    <option v-for="fee in feeTypeSuggestions" :key="fee" :value="fee" />
                                </datalist>
                            </div>

                            <div class="border-t pt-5">
                                <Label>Notes</Label>
                                <Textarea v-model="form.notes" rows="3" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-5 lg:sticky lg:top-4 lg:self-start">
                    <Card class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b"><CardTitle>Discount & tax</CardTitle></CardHeader>
                        <CardContent class="space-y-2 p-4">
                            <div>
                                <Label class="text-xs">Discount type</Label>
                                <div class="mt-1 flex gap-2">
                                    <Button type="button" size="sm" :variant="form.discountType === 'fixed' ? 'default' : 'outline'" @click="form.discountType = 'fixed'; clearItemDiscounts()">Fixed</Button>
                                    <Button type="button" size="sm" :variant="form.discountType === 'percentage' ? 'default' : 'outline'" @click="form.discountType = 'percentage'; clearItemDiscounts()">Percentage</Button>
                                </div>
                            </div>
                            <div><Label class="text-xs">Discount value</Label><Input v-model.number="form.discountValue" type="number" min="0" step="0.01" @input="clearItemDiscounts" /></div>
                            <div><Label class="text-xs">Tax (%)</Label><Input v-model.number="form.taxPercentage" type="number" min="0" max="100" step="0.01" /></div>
                        </CardContent>
                    </Card>

                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-white"><CardTitle class="flex items-center gap-2 text-slate-900"><Wallet class="h-5 w-5" />Summary</CardTitle></CardHeader>
                        <CardContent class="space-y-2 p-4">
                            <div class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-900">{{ money(subtotal) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Item discount</span><span class="font-medium text-red-600">- {{ money(itemDiscountTotal) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Bulk discount</span><span class="font-medium text-red-600">- {{ money(discountAmount) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Tax</span><span class="font-medium text-slate-900">{{ money(taxAmount) }}</span></div>
                            <div class="flex items-center justify-between border-t pt-2.5">
                                <span class="text-base font-semibold text-slate-950">Total</span>
                                <Badge class="bg-blue-100 px-3 py-1 text-base text-blue-700">{{ money(totalAmount) }}</Badge>
                            </div>
                            <div class="flex justify-between border-t pt-2.5 text-sm text-slate-600"><span>Already paid</span><span class="font-medium text-emerald-600">{{ money(invoice.paid_amount) }}</span></div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between border-b">
                            <div>
                                <CardTitle>{{ paymentForm.id ? 'Update payment' : 'Add payment' }}</CardTitle>
                            </div>
                            <Button v-if="paymentForm.id" type="button" variant="outline" size="sm" @click="resetPaymentForm">
                                <X class="mr-2 h-4 w-4" />Clear
                            </Button>
                        </CardHeader>
                        <CardContent class="space-y-4 p-4">
                            <div>
                                <Label>Amount *</Label>
                                <Input v-model.number="paymentForm.amount" type="number" min="0.01" :max="paymentLimit" step="0.01" :class="{ 'border-red-500': paymentErrors.amount }" />
                                <p v-if="paymentErrors.amount" class="mt-1 text-sm text-red-600">{{ paymentErrors.amount }}</p>
                                <p class="mt-1 text-xs text-slate-500">Allowed: {{ money(paymentLimit) }}</p>
                            </div>
                            <div>
                                <Label>Paid on</Label>
                                <DatePicker :model-value="dateValue(paymentForm.paidOn)" @update:model-value="paymentForm.paidOn = formatDate($event)" />
                            </div>
                            <div>
                                <Label>Payment method</Label>
                                <select v-model="paymentForm.method" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                    <option v-for="method in paymentMethods" :key="method.value" :value="method">{{ method.label }}</option>
                                </select>
                            </div>
                            <div>
                                <Label>Reference number</Label>
                                <Input v-model="paymentForm.referenceNo" placeholder="Transaction / cheque no." />
                            </div>
                            <div>
                                <Label>Note</Label>
                                <Textarea v-model="paymentForm.note" rows="2" />
                            </div>
                            <div class="flex gap-2 pt-1">
                                <Button type="button" class="flex-1" :disabled="paymentSaving" @click="savePayment">
                                    <Loader2 v-if="paymentSaving" class="mr-2 h-4 w-4 animate-spin" />
                                    <Save v-else class="mr-2 h-4 w-4" />
                                    {{ paymentSaving ? 'Saving...' : paymentForm.id ? 'Update payment' : 'Save payment' }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between border-b">
                            <div>
                                <CardTitle>Payment history</CardTitle>
                            </div>
                            <Button v-if="!paymentForm.id" type="button" variant="outline" size="sm" @click="resetPaymentForm"><Plus class="mr-2 h-4 w-4" />New</Button>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!invoice.payments?.length" class="p-6 text-center text-sm text-slate-500">No payments recorded yet.</div>
                            <div v-else class="divide-y">
                                <div v-for="payment in invoice.payments" :key="payment.id" class="flex items-center justify-between gap-3 p-4">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ money(payment.amount) }}</p>
                                        <p class="text-sm text-slate-500">{{ formatDateDisplay(payment.paid_on) }} · {{ payment.payment_method.replace('_', ' ') }} <span v-if="payment.reference_no"> · Ref: {{ payment.reference_no }}</span></p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button type="button" variant="outline" size="sm" @click="beginPaymentEdit(payment)">
                                            <Pencil class="mr-2 h-3.5 w-3.5" />Edit
                                        </Button>
                                        <Badge variant="secondary" class="bg-emerald-100 text-emerald-700">Paid</Badge>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex flex-col gap-2">
                        <Button type="submit" :disabled="saving" size="lg"><Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" /><Save v-else class="mr-2 h-4 w-4" />{{ saving ? 'Saving...' : 'Save changes' }}</Button>
                        <Button type="button" variant="outline" @click="router.visit(`/invoice/${invoice.id}`)">Cancel</Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>

</template>