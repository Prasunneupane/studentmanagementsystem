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
import type { PaymentGatewayOption } from '@/composables/usePaymentAttempt';
import PaymentAttemptModal from '@/components/invoice/PaymentAttemptModal.vue';
import CustomSelect from '../CustomSelect.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Banknote, CheckCircle2, CreditCard, Landmark, Loader2, Pencil, Printer, QrCode, Save, ScrollText, Trash2, Wallet, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import 'vue-sonner/style.css';

interface Payment {
    id: number;
    amount: number;
    paid_on: string;
    payment_method: string;
    payment_gateway?: string | null;
    reference_no?: string;
    note?: string;
}

interface PaymentMethodOption {
    value: string;
    label: string;
    icon?: string;
}

interface InvoiceFull extends Invoice {
    payments: Payment[];
}

const props = defineProps<{
    invoice: InvoiceFull;
    paymentGateways?: PaymentGatewayOption[];
    paymentMethods?: PaymentMethodOption[];
}>();

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

const methodIcon: Record<string, typeof Banknote> = {
    cash: Banknote,
    bank_transfer: Landmark,
    card: CreditCard,
    online_payment: QrCode,
    cheque: ScrollText,
};

const paymentMethodOptions = computed<PaymentMethodOption[]>(
    () => props.paymentMethods ?? [{ value: 'cash', label: 'Cash' }, { value: 'bank_transfer', label: 'Bank transfer' }, { value: 'card', label: 'Card' }, { value: 'online_payment', label: 'Online' }, { value: 'cheque', label: 'Cheque' }],
);
const defaultMethod = computed(() => paymentMethodOptions.value[0]?.value ?? 'cash');

const money = (value: number) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const formatDate = (value: string) => new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
const methodLabel = (payment: Payment) => {
    const base = paymentMethodOptions.value.find((m) => m.value === payment.payment_method)?.label ?? payment.payment_method.replace('_', ' ');
    return payment.payment_gateway ? `${base} · ${payment.payment_gateway}` : base;
};
const openPrintWindow = (invoiceId: number) => {
    window.open(`/invoice/${invoiceId}/print`, '_blank', 'noopener,noreferrer');
};

const balanceDue = computed(() => Math.max(Number(props.invoice.total_amount) - Number(props.invoice.paid_amount), 0));
const isFullyPaid = computed(() => Number(props.invoice.total_amount) > 0 && balanceDue.value <= 0);

const paymentSaving = ref(false);
const deleting = ref(false);
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

const gatewayModalOpen = ref(false);
const selectedGateway = ref<PaymentGatewayOption | null>(null);

const openGatewayModal = (gateway: PaymentGatewayOption) => {
    if (!paymentForm.value.amount || paymentForm.value.amount <= 0 || paymentForm.value.amount > paymentLimit.value) {
        paymentErrors.value.amount = `Enter an amount up to ${money(paymentLimit.value)} before choosing a gateway.`;
        return;
    }
    // Choosing a gateway IS choosing to pay online — reflect that in the method field too.
    paymentForm.value.method = 'online_payment';
    selectedGateway.value = gateway;
    gatewayModalOpen.value = true;
};

const onGatewaySettled = () => {
    gatewayModalOpen.value = false;
    resetPaymentForm();
    router.reload({ only: ['invoice'] });
};

// Picks up the gateway intent Create.vue stashed right before submitting
// (sessionStorage, not a query param — see PENDING_GATEWAY_INTENT_KEY in
// Create.vue). One-time use: cleared immediately whether or not it matches,
// so navigating to a different invoice afterwards never re-triggers it.
onMounted(() => {
    const raw = sessionStorage.getItem('pendingGatewayIntent');
    if (!raw) return;
    sessionStorage.removeItem('pendingGatewayIntent');

    try {
        const intent = JSON.parse(raw) as { gateway: string; amount: number; ts: number };
        const isFresh = Date.now() - intent.ts < 2 * 60 * 1000;
        const gateway = props.paymentGateways?.find((g) => g.value === intent.gateway);
        if (!isFresh || !gateway || intent.amount <= 0 || intent.amount > paymentLimit.value) return;

        paymentForm.value.amount = intent.amount;
        paymentForm.value.method = 'online_payment';
        selectedGateway.value = gateway;
        gatewayModalOpen.value = true;
    } catch {
        // malformed/stale value — ignore
    }
});

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
            <!-- Single card: student/invoice header, actions, meta, items, notes -->
            <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                <CardHeader class="flex flex-col gap-3 border-b bg-white sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <CardTitle class="text-xl">{{ invoice.student?.first_name }} {{ invoice.student?.last_name }}</CardTitle>
                            <Badge :class="statusBadge[invoice.status]" variant="secondary" class="capitalize">{{ invoice.status }}</Badge>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ invoice.invoice_number }} · {{ invoice.school_class?.name || 'No class' }}<span v-if="invoice.section"> - {{ invoice.section.name }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" @click="router.visit('/invoice')"><ArrowLeft class="mr-2 h-4 w-4" />Back</Button>
                        <Button variant="outline" size="sm" @click="openPrintWindow(invoice.id)"><Printer class="mr-2 h-4 w-4" />Print</Button>
                        <Button v-if="can('invoices.canEdit')" variant="outline" size="sm" @click="router.visit(`/invoice/${invoice.id}/edit`)"><Pencil class="mr-2 h-4 w-4" />Edit</Button>
                        <Button v-if="can('invoices.canDelete')" variant="outline" size="sm" class="text-red-600" :disabled="deleting" @click="removeInvoice">
                            <Loader2 v-if="deleting" class="mr-2 h-4 w-4 animate-spin" /><Trash2 v-else class="mr-2 h-4 w-4" />Delete
                        </Button>
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
                    <p class="text-xs text-slate-400">
                        Fee items are locked once an invoice is created. To correct a mistake, void/return this invoice and create a new one.
                    </p>

                    <div v-if="invoice.notes" class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="mb-1 text-xs text-slate-500 uppercase">Notes</p>{{ invoice.notes }}
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <Card class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between border-b">
                            <CardTitle class="flex items-center gap-2"><Wallet class="h-5 w-5" />Payment history</CardTitle>
                            <Badge v-if="invoice.payments?.length" variant="secondary">{{ invoice.payments.length }}</Badge>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div v-if="!invoice.payments?.length" class="p-8 text-center text-sm text-slate-500">No payments recorded yet.</div>
                            <div v-else class="divide-y">
                                <div v-for="payment in invoice.payments" :key="payment.id" class="flex items-center gap-3 p-4">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                        <component :is="methodIcon[payment.payment_method] ?? Banknote" class="h-4.5 w-4.5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-slate-900">{{ money(payment.amount) }}</p>
                                        <p class="truncate text-sm text-slate-500">
                                            {{ formatDate(payment.paid_on) }} · {{ methodLabel(payment) }}<span v-if="payment.reference_no"> · Ref: {{ payment.reference_no }}</span>
                                        </p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" @click="beginPaymentEdit(payment)">
                                        <Pencil class="mr-2 h-3.5 w-3.5" />Edit
                                    </Button>
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

                    <!-- Fully paid: nothing left to collect, so the collection form is replaced entirely
                         unless staff is actively correcting an existing payment row. -->
                    <Card v-if="isFullyPaid && !paymentForm.id" class="rounded-2xl border-0 bg-emerald-50 shadow-sm">
                        <CardContent class="flex flex-col items-center gap-2 p-6 text-center">
                            <CheckCircle2 class="h-8 w-8 text-emerald-600" />
                            <p class="font-medium text-emerald-800">Invoice fully paid</p>
                            <p class="text-sm text-emerald-700">There is nothing left to collect on this invoice.</p>
                        </CardContent>
                    </Card>

                    <Card v-else class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between border-b">
                            <div>
                                <CardTitle>{{ paymentForm.id ? 'Update payment' : 'Collect payment' }}</CardTitle>
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

                            <div v-if="!paymentForm.id && paymentGateways?.length && paymentLimit > 0">
                                <Label class="mb-1.5 block">Or collect online</Label>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-for="gateway in paymentGateways"
                                        :key="gateway.value"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="openGatewayModal(gateway)"
                                    >
                                        <QrCode class="mr-1.5 h-3.5 w-3.5" :style="{ color: gateway.color }" />{{ gateway.label }}
                                    </Button>
                                </div>
                            </div>

                            <div>
                                <Label>Paid on</Label>
                                <DatePicker :model-value="dateValue(paymentForm.paidOn)" @update:model-value="paymentForm.paidOn = formatDateInput($event)" />
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
                </div>
            </div>
        </div>

        <PaymentAttemptModal
            v-model:open="gatewayModalOpen"
            :invoice-id="invoice.id"
            :gateway="selectedGateway"
            :amount="paymentForm.amount"
            @settled="onGatewaySettled"
        />
    </AppLayout>
</template>
