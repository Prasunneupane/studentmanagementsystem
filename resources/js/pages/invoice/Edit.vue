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
import { ArrowLeft, Loader2, Plus, Save, Trash2, Receipt } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CustomSelect from '../CustomSelect.vue';
import 'vue-sonner/style.css';

const props = defineProps<{ invoice: Invoice }>();

const { toast } = useToast();
const { updateInvoice } = useInvoices();

const breadcrumbs = [
    { title: 'Invoices', href: '/invoice' },
    { title: props.invoice.invoice_number, href: `/invoice/${props.invoice.id}` },
    { title: 'Edit', href: `/invoice/${props.invoice.id}/edit` },
];

const feeTypeSuggestions = ['Tuition Fee', 'Admission Fee', 'Exam Fee', 'Transport Fee', 'Library Fee', 'Lab Fee', 'Sports Fee', 'Miscellaneous'];

interface LineItem {
    id?: number;
    fee_type: string;
    description: string;
    quantity: number;
    unit_price: number;
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
    })) as LineItem[],
});

const errors = ref<Record<string, string>>({});
const saving = ref(false);
const hasPayments = computed(() => Number(props.invoice.paid_amount) > 0);

const dateValue = (value: string) => {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};
const formatDate = (date: Date | null | undefined) => (date ? date.toISOString().split('T')[0] : '');

const addItem = () => form.value.items.push({ fee_type: '', description: '', quantity: 1, unit_price: 0 });
const removeItem = (index: number) => {
    if (form.value.items.length > 1) form.value.items.splice(index, 1);
};

const subtotal = computed(() => form.value.items.reduce((sum, item) => sum + (item.quantity || 0) * (item.unit_price || 0), 0));
const discountAmount = computed(() => {
    if (form.value.discountType === 'percentage') return subtotal.value * ((form.value.discountValue || 0) / 100);
    return form.value.discountValue || 0;
});
const taxableAmount = computed(() => Math.max(subtotal.value - discountAmount.value, 0));
const taxAmount = computed(() => taxableAmount.value * ((form.value.taxPercentage || 0) / 100));
const totalAmount = computed(() => taxableAmount.value + taxAmount.value);

const money = (value: number) => `Rs. ${value.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

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
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium text-blue-600">Fee management</p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950">Edit {{ invoice.invoice_number }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ invoice.student?.first_name }} {{ invoice.student?.last_name }}</p>
                </div>
                <Button variant="outline" @click="router.visit(`/invoice/${invoice.id}`)"><ArrowLeft class="mr-2 h-4 w-4" />Back to invoice</Button>
            </div>

            <div v-if="hasPayments" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                This invoice already has {{ money(invoice.paid_amount) }} recorded in payments. The new total cannot go below that amount.
            </div>

            <form @submit.prevent="submit" class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-5 lg:col-span-2">
                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-gradient-to-r from-slate-950 to-blue-950 text-white">
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
                                            <div class="sm:col-span-2">
                                                <Label class="text-xs">Qty</Label>
                                                <Input v-model.number="item.quantity" type="number" min="1" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <Label class="text-xs">Unit price *</Label>
                                                <Input v-model.number="item.unit_price" type="number" min="0" step="0.01" :class="{ 'border-red-500': errors[`price_${index}`] }" />
                                            </div>
                                            <div class="flex items-end justify-between gap-2 sm:col-span-1">
                                                <span class="text-sm font-medium text-slate-700">{{ money(item.quantity * item.unit_price) }}</span>
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

                <div class="space-y-5">
                    <Card class="rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b"><CardTitle>Discount & tax</CardTitle></CardHeader>
                        <CardContent class="space-y-4 p-5">
                            <div>
                                <Label class="text-xs">Discount type</Label>
                                <div class="mt-1 flex gap-2">
                                    <Button type="button" size="sm" :variant="form.discountType === 'fixed' ? 'default' : 'outline'" @click="form.discountType = 'fixed'">Fixed</Button>
                                    <Button type="button" size="sm" :variant="form.discountType === 'percentage' ? 'default' : 'outline'" @click="form.discountType = 'percentage'">Percentage</Button>
                                </div>
                            </div>
                            <div><Label class="text-xs">Discount value</Label><Input v-model.number="form.discountValue" type="number" min="0" step="0.01" /></div>
                            <div><Label class="text-xs">Tax (%)</Label><Input v-model.number="form.taxPercentage" type="number" min="0" max="100" step="0.01" /></div>
                        </CardContent>
                    </Card>

                    <Card class="overflow-hidden rounded-2xl border-0 shadow-sm">
                        <CardHeader class="border-b bg-slate-950 text-white"><CardTitle>Summary</CardTitle></CardHeader>
                        <CardContent class="space-y-3 p-5">
                            <div class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-900">{{ money(subtotal) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Discount</span><span class="font-medium text-red-600">- {{ money(discountAmount) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Tax</span><span class="font-medium text-slate-900">{{ money(taxAmount) }}</span></div>
                            <div class="flex items-center justify-between border-t pt-3">
                                <span class="text-base font-semibold text-slate-950">Total</span>
                                <Badge class="bg-blue-600 px-3 py-1 text-base">{{ money(totalAmount) }}</Badge>
                            </div>
                            <div class="flex justify-between border-t pt-3 text-sm text-slate-600"><span>Already paid</span><span class="font-medium text-emerald-600">{{ money(invoice.paid_amount) }}</span></div>
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