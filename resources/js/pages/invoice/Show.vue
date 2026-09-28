<!-- resources/js/pages/Invoices/Show.vue -->
<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Toaster } from '@/components/ui/sonner';
import { usePermission } from '@/composables/usePermissions';
import { useInvoices } from '@/composables/useInvoice';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Invoice } from '@/composables/invoiceService';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Pencil, Plus, Printer, Save, Trash2, Wallet, X } from 'lucide-vue-next';
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

interface InvoiceFull extends Invoice {
    payments: Payment[];
}

const props = defineProps<{ invoice: InvoiceFull }>();

const { toast } = useToast();
const { can } = usePermission();
const { recordPayment, updatePayment, deleteInvoice } = useInvoices();

const breadcrumbs = [
    { title: 'Invoices', href: '/invoice' },
    { title: props.invoice.invoice_number, href: `/invoice/${props.invoice.id}` },
];

const statusBadge: Record<string, string> = {
    unpaid: 'bg-red-100 text-red-700',
    partial: 'bg-amber-100 text-amber-700',
    paid: 'bg-emerald-100 text-emerald-700',
    overdue: 'bg-red-100 text-red-700',
    cancelled: 'bg-slate-100 text-slate-600',
};

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'bank_transfer', label: 'Bank transfer' },
    { value: 'card', label: 'Card' },
    { value: 'online', label: 'Online' },
    { value: 'cheque', label: 'Cheque' },
];

const money = (value: number) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const formatDate = (value: string) => new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
const openPrintWindow = (invoiceId: number) => {
    window.open(`/invoice/${invoiceId}/print`, '_blank', 'noopener,noreferrer');
};

const balanceDue = computed(() => Math.max(Number(props.invoice.total_amount) - Number(props.invoice.paid_amount), 0));
const paymentSaving = ref(false);
const deleting = ref(false);
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
const formatDateInput = (date: Date | null | undefined) => (date ? date.toISOString().split('T')[0] : '');

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

const removeInvoice = async () => {
    if (!window.confirm(`Delete invoice ${props.invoice.invoice_number}? This cannot be undone.`)) return;
    deleting.value = true;
    try {
        await deleteInvoice(props.invoice.id);
        toast.success('Invoice deleted');
        router.visit('/invoice');
    } catch {
        toast.error('Failed to delete invoice');
        deleting.value = false;
    }
};
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="w-full space-y-5 bg-slate-50 p-4 sm:p-6">
            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">
                <div class="flex-1 rounded-2xl border bg-white p-3 shadow-sm">
                    <p class="text-[10px] font-medium uppercase tracking-[0.2em] text-slate-500">Student</p>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-slate-950">{{ invoice.student?.first_name }} {{ invoice.student?.last_name }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ invoice.school_class?.name || 'No class' }} <span v-if="invoice.section"> - {{ invoice.section.name }}</span></p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ invoice.invoice_number }}</span>
                    </div>
                </div>

                <div class="rounded-2xl border bg-white p-2 shadow-sm">
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="router.visit('/invoice')"><ArrowLeft class="mr-2 h-4 w-4" />Back</Button>
                        <Button variant="outline" size="sm" @click="openPrintWindow(invoice.id)"><Printer class="mr-2 h-4 w-4" />Print</Button>
                        <Button v-if="can('invoices.canEdit')" variant="outline" size="sm" @click="router.visit(`/invoice/${invoice.id}/edit`)"><Pencil class="mr-2 h-4 w-4" />Edit</Button>
                        <Button v-if="can('invoices.canDelete')" variant="outline" size="sm" class="text-red-600" :disabled="deleting" @click="removeInvoice">
                            <Loader2 v-if="deleting" class="mr-2 h-4 w-4 animate-spin" /><Trash2 v-else class="mr-2 h-4 w-4" />Delete
                        </Button>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-white text-slate-900">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <CardTitle>Invoice details</CardTitle>
                                    <p class="mt-1 text-sm text-slate-500">{{ invoice.invoice_number }}</p>
                                </div>
                                <Badge :class="statusBadge[invoice.status]" variant="secondary">{{ invoice.status }}</Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6 p-5 sm:p-7">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><p class="text-xs text-slate-500 uppercase">Issue date</p><p class="font-medium text-slate-900">{{ formatDate(invoice.issue_date) }}</p></div>
                                <div><p class="text-xs text-slate-500 uppercase">Due date</p><p class="font-medium text-slate-900">{{ formatDate(invoice.due_date) }}</p></div>
                            </div>

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
                                            <td class="px-4 py-2 text-right font-medium">{{ money(item.total ?? item.amount ?? item.quantity * item.unit_price) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="invoice.notes" class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                                <p class="mb-1 text-xs text-slate-500 uppercase">Notes</p>{{ invoice.notes }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between border-b">
                            <div><CardTitle>Payment history</CardTitle></div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!invoice.payments?.length" class="p-8 text-center text-sm text-slate-500">No payments recorded yet.</div>
                            <div v-else class="divide-y">
                                <div v-for="payment in invoice.payments" :key="payment.id" class="flex items-center justify-between gap-3 p-4">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ money(payment.amount) }}</p>
                                        <p class="text-sm text-slate-500">{{ formatDate(payment.paid_on) }} · {{ payment.payment_method.replace('_', ' ') }} <span v-if="payment.reference_no"> · Ref: {{ payment.reference_no }}</span></p>
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
                </div>

                <div class="space-y-5">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-white"><CardTitle class="flex items-center gap-2 text-slate-900"><Wallet class="h-5 w-5" />Summary</CardTitle></CardHeader>
                        <CardContent class="space-y-3 p-4">
                            <div class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-900">{{ money(invoice.subtotal) }}</span></div>
                            <div v-if="invoice.discount_amount" class="flex justify-between text-sm text-slate-600"><span>Discount</span><span class="font-medium text-red-600">- {{ money(invoice.discount_amount) }}</span></div>
                            <div v-if="invoice.tax_amount" class="flex justify-between text-sm text-slate-600"><span>Tax</span><span class="font-medium text-slate-900">{{ money(invoice.tax_amount) }}</span></div>
                            <div class="flex justify-between border-t pt-3 text-base font-semibold text-slate-950"><span>Total</span><span>{{ money(invoice.total_amount) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Paid</span><span class="font-medium text-emerald-600">{{ money(invoice.paid_amount) }}</span></div>
                            <div class="flex justify-between border-t pt-3 text-base font-semibold" :class="balanceDue > 0 ? 'text-red-600' : 'text-emerald-600'"><span>Balance</span><span>{{ money(balanceDue) }}</span></div>
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
                                <DatePicker :model-value="dateValue(paymentForm.paidOn)" @update:model-value="paymentForm.paidOn = formatDateInput($event)" />
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
                </div>
            </div>
        </div>
    </AppLayout>
</template>