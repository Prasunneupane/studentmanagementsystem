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
import CustomSelect from '../CustomSelect.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Loader2, Pencil, Plus, Printer, Save, Receipt, Wallet, X } from 'lucide-vue-next';
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

interface PaymentMethodOption {
    value: string;
    label: string;
    icon?: string;
}

const props = defineProps<{ invoice: Invoice & { payments?: Payment[] }; paymentMethods?: PaymentMethodOption[] }>();

const { toast } = useToast();
const { updateInvoice, recordPayment, updatePayment } = useInvoices();

const breadcrumbs = [
    { title: 'Invoices', href: '/invoice' },
    { title: 'Edit invoice', href: `/invoice/${props.invoice.id}/edit` },
];

const paymentMethodOptions = computed<PaymentMethodOption[]>(
    () => props.paymentMethods ?? [{ value: 'cash', label: 'Cash' }, { value: 'bank_transfer', label: 'Bank transfer' }, { value: 'card', label: 'Card' }, { value: 'online_payment', label: 'Online' }, { value: 'cheque', label: 'Cheque' }],
);
const defaultMethod = computed(() => paymentMethodOptions.value[0]?.value ?? 'cash');

const form = ref({
    issueDate: props.invoice.issue_date,
    dueDate: props.invoice.due_date,
    discountType: (props.invoice.discount_type || 'fixed') as 'fixed' | 'percentage',
    discountValue: Number(props.invoice.discount_value) || 0,
    taxPercentage: Number(props.invoice.tax_percentage) || 0,
    notes: props.invoice.notes || '',
});

const errors = ref<Record<string, string>>({});
const saving = ref(false);
const hasPayments = computed(() => Number(props.invoice.paid_amount) > 0);
const balanceDue = computed(() => Math.max(Number(props.invoice.total_amount) - Number(props.invoice.paid_amount), 0));
const isFullyPaid = computed(() => Number(props.invoice.total_amount) > 0 && balanceDue.value <= 0);
const paymentSaving = ref(false);
const paymentErrors = ref<Record<string, string>>({});
const paymentForm = ref({
    id: null as number | null,
    amount: balanceDue.value > 0 ? balanceDue.value : 0,
    paidOn: new Date().toISOString().split('T')[0],
    method: defaultMethod.value,
    referenceNo: '',
    note: '',
});

const resetPaymentForm = () => {
    paymentForm.value = {
        id: null,
        amount: balanceDue.value > 0 ? balanceDue.value : 0,
        paidOn: new Date().toISOString().split('T')[0],
        method: defaultMethod.value,
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
        method: payment.payment_method,
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

// Items are immutable after creation — these are read-only totals computed
// from what's already on the invoice, mirroring InvoiceService's
// recalculateTotalsFromExistingItems() so the preview matches what the
// server will actually save.
const itemTotal = (item: Invoice['items'][number]) => Number(item.total ?? item.amount ?? item.quantity * item.unit_price);
const subtotal = computed(() => props.invoice.items.reduce((sum, item) => sum + item.quantity * Number(item.unit_price), 0));
const itemDiscountTotal = computed(() => props.invoice.items.reduce((sum, item) => sum + Number(item.discount_amount ?? 0), 0));
const itemNetSubtotal = computed(() => Math.max(subtotal.value - itemDiscountTotal.value, 0));
const discountAmount = computed(() => {
    if (form.value.discountType === 'percentage') return Math.min(itemNetSubtotal.value, itemNetSubtotal.value * ((form.value.discountValue || 0) / 100));
    return Math.min(itemNetSubtotal.value, form.value.discountValue || 0);
});
const taxableAmount = computed(() => Math.max(itemNetSubtotal.value - discountAmount.value, 0));
const taxAmount = computed(() => taxableAmount.value * ((form.value.taxPercentage || 0) / 100));
const totalAmount = computed(() => taxableAmount.value + taxAmount.value);

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
        payment_method: paymentForm.value.method,
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
    return !Object.keys(errors.value).length;
};

const submit = async () => {
    if (isFullyPaid.value) return;
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
            issue_date: form.value.issueDate,
            due_date: form.value.dueDate,
            discount_type: form.value.discountType,
            discount_value: form.value.discountValue,
            tax_percentage: form.value.taxPercentage,
            notes: form.value.notes,
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

            <div v-if="isFullyPaid" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                <div class="flex items-center gap-2"><CheckCircle2 class="h-4 w-4" />This invoice is fully paid. Editing is disabled — void/return this invoice and create a new one if a correction is needed.</div>
            </div>
            <div v-else-if="hasPayments" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                This invoice already has {{ money(invoice.paid_amount) }} recorded in payments. The new total cannot go below that amount.
            </div>

            <form @submit.prevent="submit" class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-white text-slate-900">
                            <CardTitle class="flex items-center gap-2 text-lg"><Receipt class="h-5 w-5" />Invoice details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-5 p-5 sm:p-7">
                            <fieldset :disabled="isFullyPaid" class="grid gap-4 md:grid-cols-2">
                                <div><Label>Issue date *</Label><DatePicker :model-value="dateValue(form.issueDate)" @update:model-value="form.issueDate = formatDate($event)" /></div>
                                <div>
                                    <Label>Due date *</Label>
                                    <DatePicker :model-value="dateValue(form.dueDate)" @update:model-value="form.dueDate = formatDate($event)" />
                                    <p v-if="errors.dueDate" class="text-sm text-red-600">{{ errors.dueDate }}</p>
                                </div>
                            </fieldset>

                            <div class="border-t pt-5">
                                <h2 class="mb-3 text-lg font-semibold text-slate-900">Fee items</h2>
                                <div class="overflow-hidden rounded-xl border">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50 text-left text-xs text-slate-500 uppercase">
                                            <tr>
                                                <th class="px-4 py-2">Fee type</th>
                                                <th class="px-4 py-2">Description</th>
                                                <th class="px-4 py-2 text-right">Qty</th>
                                                <th class="px-4 py-2 text-right">Unit price</th>
                                                <th class="px-4 py-2 text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr v-for="item in invoice.items" :key="item.id">
                                                <td class="px-4 py-2 font-medium text-slate-900">{{ item.fee_type }}</td>
                                                <td class="px-4 py-2 text-slate-500">{{ item.description || '-' }}</td>
                                                <td class="px-4 py-2 text-right">{{ item.quantity }}</td>
                                                <td class="px-4 py-2 text-right">{{ money(item.unit_price) }}</td>
                                                <td class="px-4 py-2 text-right font-medium">{{ money(itemTotal(item)) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="mt-2 text-xs text-slate-400">
                                    Fee items are locked once an invoice is created. To correct a mistake, void/return this invoice and create a new one.
                                </p>
                            </div>

                            <div class="border-t pt-5">
                                <Label>Notes</Label>
                                <Textarea v-model="form.notes" rows="3" :disabled="isFullyPaid" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-5 lg:sticky lg:top-4 lg:self-start">
                    <Card class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b"><CardTitle>Discount & tax</CardTitle></CardHeader>
                        <CardContent class="space-y-2 p-4">
                            <fieldset :disabled="isFullyPaid" class="space-y-2">
                                <div>
                                    <Label class="text-xs">Discount type</Label>
                                    <div class="mt-1 flex gap-2">
                                        <Button type="button" size="sm" :variant="form.discountType === 'fixed' ? 'default' : 'outline'" :disabled="isFullyPaid" @click="form.discountType = 'fixed'">Fixed</Button>
                                        <Button type="button" size="sm" :variant="form.discountType === 'percentage' ? 'default' : 'outline'" :disabled="isFullyPaid" @click="form.discountType = 'percentage'">Percentage</Button>
                                    </div>
                                </div>
                                <div><Label class="text-xs">Discount value</Label><Input v-model.number="form.discountValue" type="number" min="0" step="0.01" /></div>
                                <div><Label class="text-xs">Tax (%)</Label><Input v-model.number="form.taxPercentage" type="number" min="0" max="100" step="0.01" /></div>
                            </fieldset>
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

                    <Card v-if="!isFullyPaid" class="rounded-2xl border-0 shadow-sm">
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
                                <CustomSelect v-model="paymentForm.method" :options="paymentMethodOptions" placeholder="Select method" />
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
                            <Button v-if="!paymentForm.id && !isFullyPaid" type="button" variant="outline" size="sm" @click="resetPaymentForm"><Plus class="mr-2 h-4 w-4" />New</Button>
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
                        <Button type="submit" :disabled="saving || isFullyPaid" size="lg">
                            <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" /><Save v-else class="mr-2 h-4 w-4" />
                            {{ isFullyPaid ? 'Fully paid — locked' : saving ? 'Saving...' : 'Save changes' }}
                        </Button>
                        <Button type="button" variant="outline" @click="router.visit(`/invoice/${invoice.id}`)">Cancel</Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
