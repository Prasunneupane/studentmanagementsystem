<!-- resources/js/pages/Invoices/Create.vue -->
<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Toaster } from '@/components/ui/sonner';
import { useInvoices } from '@/composables/useInvoice.js';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, MessageSquarePlus, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CustomSelect from '../CustomSelect.vue';
import 'vue-sonner/style.css';

interface Option {
    value: string;
    label: string;
    class_id?: number;
    section_id?: number;
}

const props = defineProps<{ students: Option[] }>();

const { toast } = useToast();
const { createInvoice } = useInvoices();

const breadcrumbs = [
    { title: 'Invoices', href: '/invoice' },
    { title: 'Create Invoice', href: '/invoice/create' },
];

interface LineItem {
    fee_type: string;
    description: string;
    quantity: number;
    unit_price: number;
    showDescription: boolean;
}

const feeTypeSuggestions = ['Tuition Fee', 'Admission Fee', 'Exam Fee', 'Transport Fee', 'Library Fee', 'Lab Fee', 'Sports Fee', 'Miscellaneous'];

const form = ref({
    studentOption: null as Option | null,
    issueDate: new Date().toISOString().split('T')[0],
    dueDate: '',
    discountType: 'fixed' as 'fixed' | 'percentage',
    discountValue: 0,
    taxPercentage: 0,
    notes: '',
    items: [{ fee_type: '', description: '', quantity: 1, unit_price: 0, showDescription: false }] as LineItem[],
});

const errors = ref<Record<string, string>>({});
const saving = ref(false);

const dateValue = (value: string) => {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const formatDate = (date: Date | null | undefined) => (date ? date.toISOString().split('T')[0] : '');

const addItem = () => form.value.items.push({ fee_type: '', description: '', quantity: 1, unit_price: 0, showDescription: false });
const removeItem = (index: number) => {
    if (form.value.items.length > 1) form.value.items.splice(index, 1);
};

const addItemFromUnitPrice = (event: KeyboardEvent, index: number) => {
    const item = form.value.items[index];
    if (!item.fee_type.trim() || item.unit_price <= 0) return;

    if (event.key === 'Enter') event.preventDefault();
    if (event.key === 'Enter' || (event.key === 'Tab' && !event.shiftKey)) addItem();
};

const toggleDescription = (item: LineItem) => {
    item.showDescription = !item.showDescription;
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
    if (!form.value.studentOption) errors.value.student = 'Select a student';
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
    if (!window.confirm('Are you sure you want to create this invoice?')) return;

    saving.value = true;
    try {
        await createInvoice({
            student_id: form.value.studentOption?.value,
            class_id: form.value.studentOption?.class_id,
            section_id: form.value.studentOption?.section_id,
            issue_date: form.value.issueDate,
            due_date: form.value.dueDate,
            discount_type: form.value.discountType,
            discount_value: form.value.discountValue,
            tax_percentage: form.value.taxPercentage,
            notes: form.value.notes,
            items: form.value.items.map(({ showDescription, ...item }) => item),
        });
        toast.success('Invoice created successfully');
    } catch {
        toast.error('Failed to create invoice');
    } finally {
        saving.value = false;
    }
};

const handleFormKeydown = (event: KeyboardEvent) => {
    if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key.toLowerCase() === 'a') {
        event.preventDefault();
        addItem();
    }
    if ((event.ctrlKey || event.metaKey) && !event.shiftKey && event.key === 'Enter') {
        event.preventDefault();
        submit();
    }
};
</script>

<template>
    <Head title="Create Invoice" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />
        <div class="w-full space-y-4 bg-slate-50 p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-slate-950">Create invoice</h1>
                    <p class="text-sm text-slate-500">Add a student, fee items, and payment details.</p>
                </div>
                <Button variant="outline" size="sm" @click="router.visit('/invoice')"><ArrowLeft class="mr-2 h-4 w-4" />Back to invoices</Button>
            </div>

            <form @submit.prevent="submit" @keydown="handleFormKeydown" class="grid gap-5 lg:grid-cols-3">
                <div class="min-w-0 lg:col-span-2">
                    <Card class="overflow-hidden rounded-xl border shadow-sm">
                        <CardHeader class="border-b py-3"><CardTitle class="text-base">Invoice details</CardTitle></CardHeader>
                        <CardContent class="space-y-4 p-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label>Student *</Label>
                                    <CustomSelect v-model="form.studentOption" :options="props.students" placeholder="Search student..." />
                                    <p v-if="errors.student" class="text-sm text-red-600">{{ errors.student }}</p>
                                </div>
                                <div><Label>Issue date *</Label><DatePicker :model-value="dateValue(form.issueDate)" @update:model-value="form.issueDate = formatDate($event)" /></div>
                                <div>
                                    <Label>Due date *</Label>
                                    <DatePicker :model-value="dateValue(form.dueDate)" @update:model-value="form.dueDate = formatDate($event)" />
                                    <p v-if="errors.dueDate" class="text-sm text-red-600">{{ errors.dueDate }}</p>
                                </div>
                            </div>

                            <div class="border-t pt-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-slate-900">Fee items</h2>
                                    <Button type="button" size="sm" variant="outline" @click="addItem"><Plus class="mr-2 h-4 w-4" />Add item</Button>
                                </div>

                                <div class="invoice-items-scroll max-h-[min(48vh,30rem)] space-y-3 overflow-y-scroll pr-2">
                                    <div v-for="(item, index) in form.items" :key="index" class="rounded-xl border bg-white p-4">
                                        <div class="grid gap-3 sm:grid-cols-12">
                                            <div class="sm:col-span-4">
                                                <Label class="text-xs">Fee type *</Label>
                                                <Input v-model="item.fee_type" list="fee-suggestions" placeholder="Tuition Fee" :class="{ 'border-red-500': errors[`item_${index}`] }" />
                                            </div>
                                            <div class="sm:col-span-5">
                                                <Label class="text-xs">Qty</Label>
                                                <Input v-model.number="item.quantity" type="number" min="1" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <Label class="text-xs">Unit price *</Label>
                                                <Input v-model.number="item.unit_price" type="number" min="0" step="0.01" :class="{ 'border-red-500': errors[`price_${index}`] }" @keydown.enter="addItemFromUnitPrice($event, index)" @keydown.tab="addItemFromUnitPrice($event, index)" />
                                            </div>
                                            <div class="flex items-end justify-between gap-2 sm:col-span-1">
                                                <span class="text-sm font-medium text-slate-700">{{ money(item.quantity * item.unit_price) }}</span>
                                                <Button type="button" variant="ghost" size="icon" class="text-red-600" :disabled="form.items.length === 1" @click="removeItem(index)"><Trash2 class="h-4 w-4" /></Button>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <Button type="button" variant="ghost" size="sm" class="h-7 px-2 text-xs text-muted-foreground" @click="toggleDescription(item)">
                                                <MessageSquarePlus class="mr-1.5 h-3.5 w-3.5" />{{ item.showDescription ? 'Hide comment' : 'Add comment' }}
                                            </Button>
                                            <Input v-if="item.showDescription" v-model="item.description" class="mt-2" placeholder="Optional comment for this fee" />
                                        </div>
                                    </div>
                                </div>
                                <datalist id="fee-suggestions">
                                    <option v-for="fee in feeTypeSuggestions" :key="fee" :value="fee" />
                                </datalist>
                            </div>

                            <div class="border-t pt-4">
                                <Label>Notes</Label>
                                <Textarea v-model="form.notes" placeholder="Payment instructions, remarks..." rows="3" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-1.5 lg:sticky lg:top-4 lg:self-start">
                    <Card class="rounded-xl border shadow-sm">
                        <CardHeader class="border-b px-4 py-3"><CardTitle class="text-base">Discount & tax</CardTitle></CardHeader>
                        <CardContent class="space-y-2 p-4">
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

                    <Card class="overflow-hidden rounded-xl border shadow-sm">
                        <CardHeader class="border-b px-4 py-3"><CardTitle class="text-base">Summary</CardTitle></CardHeader>
                        <CardContent class="space-y-2 p-4">
                            <div class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><span class="font-medium text-slate-900">{{ money(subtotal) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Discount</span><span class="font-medium text-red-600">- {{ money(discountAmount) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-600"><span>Tax</span><span class="font-medium text-slate-900">{{ money(taxAmount) }}</span></div>
                            <div class="flex items-center justify-between border-t pt-2.5">
                                <span class="text-base font-semibold text-slate-950">Total</span>
                                <Badge class="bg-blue-600 px-3 py-1 text-base">{{ money(totalAmount) }}</Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex flex-col gap-1.5">
                        <Button type="submit" :disabled="saving" size="default"><Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" /><Save v-else class="mr-2 h-4 w-4" />{{ saving ? 'Creating...' : 'Create invoice' }}</Button>
                        <Button type="button" variant="outline" @click="router.visit('/invoice')">Cancel</Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
.invoice-items-scroll {
    scrollbar-width: thin;
    scrollbar-color: hsl(var(--muted-foreground) / 0.5) hsl(var(--muted) / 0.35);
}

.invoice-items-scroll::-webkit-scrollbar {
    width: 10px;
}

.invoice-items-scroll::-webkit-scrollbar-track {
    background: hsl(var(--muted) / 0.35);
    border-radius: 9999px;
}

.invoice-items-scroll::-webkit-scrollbar-thumb {
    background: hsl(var(--muted-foreground) / 0.5);
    border: 2px solid hsl(var(--background));
    border-radius: 9999px;
}

.invoice-items-scroll::-webkit-scrollbar-thumb:hover {
    background: hsl(var(--muted-foreground) / 0.7);
}
</style>