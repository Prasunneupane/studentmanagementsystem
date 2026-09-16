<!-- resources/js/pages/Invoices/Show.vue -->
<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
import { ArrowLeft, Loader2, Pencil, Plus, Printer, Trash2, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CustomSelect from '../CustomSelect.vue';
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
const { recordPayment, deleteInvoice } = useInvoices();

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

const balanceDue = computed(() => Number(props.invoice.total_amount) - Number(props.invoice.paid_amount));

const paymentDialogOpen = ref(false);
const paymentSaving = ref(false);
const deleting = ref(false);
const paymentErrors = ref<Record<string, string>>({});
const paymentForm = ref({
    amount: balanceDue.value > 0 ? balanceDue.value : 0,
    paidOn: new Date().toISOString().split('T')[0],
    method: paymentMethods[0],
    referenceNo: '',
    note: '',
});

const dateValue = (value: string) => {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};
const formatDateInput = (date: Date | null | undefined) => (date ? date.toISOString().split('T')[0] : '');

const openPaymentDialog = () => {
    paymentErrors.value = {};
    paymentForm.value = {
        amount: balanceDue.value > 0 ? balanceDue.value : 0,
        paidOn: new Date().toISOString().split('T')[0],
        method: paymentMethods[0],
        referenceNo: '',
        note: '',
    };
    paymentDialogOpen.value = true;
};

const savePayment = async () => {
    paymentErrors.value = {};
    if (!paymentForm.value.amount || paymentForm.value.amount <= 0) {
        paymentErrors.value.amount = 'Enter a valid amount';
        return;
    }
    if (paymentForm.value.amount > balanceDue.value) {
        paymentErrors.value.amount = `Amount cannot exceed balance due (${money(balanceDue.value)})`;
        return;
    }
    paymentSaving.value = true;
    try {
        await recordPayment(props.invoice.id, {
            amount: paymentForm.value.amount,
            paid_on: paymentForm.value.paidOn,
            payment_method: paymentForm.value.method.value,
            reference_no: paymentForm.value.referenceNo,
            note: paymentForm.value.note,
        });
        toast.success('Payment recorded successfully');
        paymentDialogOpen.value = false;
        router.reload({ only: ['invoice'] });
    } catch {
        toast.error('Failed to record payment');
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
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium text-blue-600">Fee management</p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950">{{ invoice.invoice_number }}</h1>
                    <Badge :class="statusBadge[invoice.status]" variant="secondary" class="mt-2">{{ invoice.status }}</Badge>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" @click="router.visit('/invoice')"><ArrowLeft class="mr-2 h-4 w-4" />Back</Button>
                    <Button variant="outline" @click="router.visit(`/invoice/${invoice.id}/print`)"><Printer class="mr-2 h-4 w-4" />Print</Button>
                    <Button v-if="can('invoices.canEdit')" variant="outline" @click="router.visit(`/invoice/${invoice.id}/edit`)"><Pencil class="mr-2 h-4 w-4" />Edit</Button>
                    <Button v-if="can('invoices.canDelete')" variant="outline" class="text-red-600" :disabled="deleting" @click="removeInvoice">
                        <Loader2 v-if="deleting" class="mr-2 h-4 w-4 animate-spin" /><Trash2 v-else class="mr-2 h-4 w-4" />Delete
                    </Button>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-gradient-to-r from-slate-950 to-blue-950 text-white">
                            <div class="flex items-center gap-4">
                                <Avatar class="h-14 w-14 border-2 border-white/70">
                                    <AvatarImage :src="invoice.student?.photo_url ?? ''" />
                                    <AvatarFallback class="bg-blue-600">{{ invoice.student?.first_name?.[0] }}{{ invoice.student?.last_name?.[0] }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <CardTitle>{{ invoice.student?.first_name }} {{ invoice.student?.last_name }}</CardTitle>
                                    <p class="mt-1 text-sm text-blue-100">{{ invoice.school_class?.name || 'No class' }} <span v-if="invoice.section"> - {{ invoice.section.name }}</span></p>
                                </div>
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
                            <div><CardTitle>Payment history</CardTitle><p class="mt-1 text-sm text-slate-500">All payments recorded against this invoice.</p></div>
                            <Button v-if="can('invoices.canRecordPayment') && balanceDue > 0" size="sm" @click="openPaymentDialog"><Plus class="mr-2 h-4 w-4" />Record payment</Button>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!invoice.payments?.length" class="p-8 text-center text-sm text-slate-500">No payments recorded yet.</div>
                            <div v-else class="divide-y">
                                <div v-for="payment in invoice.payments" :key="payment.id" class="flex items-center justify-between p-4">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ money(payment.amount) }}</p>
                                        <p class="text-sm text-slate-500">{{ formatDate(payment.paid_on) }} · {{ payment.payment_method.replace('_', ' ') }} <span v-if="payment.reference_no"> · Ref: {{ payment.reference_no }}</span></p>
                                    </div>
                                    <Badge variant="secondary" class="bg-emerald-100 text-emerald-700">Paid</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-5">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-slate-950 text-white"><CardTitle class="flex items-center gap-2"><Wallet class="h-5 w-5" />Payment summary</CardTitle></CardHeader>
                        <CardContent class="space-y-3 p-5">
                            <div class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-900">{{ money(invoice.subtotal) }}</span></div>
                            <div v-if="invoice.discount_amount" class="flex justify-between text-sm text-slate-600"><span>Discount</span><span class="font-medium text-red-600">- {{ money(invoice.discount_amount) }}</span></div>
                            <div v-if="invoice.tax_amount" class="flex justify-between text-sm text-slate-600"><span>Tax</span><span class="font-medium text-slate-900">{{ money(invoice.tax_amount) }}</span></div>
                            <div class="flex justify-between border-t pt-3 text-base font-semibold text-slate-950"><span>Total</span><span>{{ money(invoice.total_amount) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Paid</span><span class="font-medium text-emerald-600">{{ money(invoice.paid_amount) }}</span></div>
                            <div class="flex justify-between border-t pt-3 text-base font-semibold" :class="balanceDue > 0 ? 'text-red-600' : 'text-emerald-600'"><span>Balance due</span><span>{{ money(balanceDue) }}</span></div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>

    <Dialog v-model:open="paymentDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader><DialogTitle>Record payment</DialogTitle></DialogHeader>
            <form class="space-y-4" @submit.prevent="savePayment">
                <div>
                    <Label>Amount *</Label>
                    <Input v-model.number="paymentForm.amount" type="number" min="0.01" :max="balanceDue" step="0.01" :class="{ 'border-red-500': paymentErrors.amount }" />
                    <p v-if="paymentErrors.amount" class="text-sm text-red-600">{{ paymentErrors.amount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Balance due: {{ money(balanceDue) }}</p>
                </div>
                <div><Label>Paid on</Label><DatePicker :model-value="dateValue(paymentForm.paidOn)" @update:model-value="paymentForm.paidOn = formatDateInput($event)" /></div>
                <div><Label>Payment method</Label><CustomSelect v-model="paymentForm.method" :options="paymentMethods" /></div>
                <div><Label>Reference number</Label><Input v-model="paymentForm.referenceNo" placeholder="Transaction / cheque no." /></div>
                <div><Label>Note</Label><Textarea v-model="paymentForm.note" rows="2" /></div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="paymentDialogOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="paymentSaving"><Loader2 v-if="paymentSaving" class="mr-2 h-4 w-4 animate-spin" />Save payment</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>