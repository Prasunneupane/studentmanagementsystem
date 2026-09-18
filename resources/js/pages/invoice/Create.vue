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
import {
    ArrowLeft,
    Loader2,
    MessageSquarePlus,
    Plus,
    Save,
    StickyNote,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
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
    discount_type: 'fixed' | 'percentage';
    discount_value: number;
    showDescription: boolean;
}

const feeTypeSuggestions = [
    'Tuition Fee',
    'Admission Fee',
    'Exam Fee',
    'Transport Fee',
    'Library Fee',
    'Lab Fee',
    'Sports Fee',
    'Miscellaneous',
];

const newItem = (): LineItem => ({
    fee_type: '',
    description: '',
    quantity: 1,
    unit_price: 0,
    discount_type: 'fixed',
    discount_value: 0,
    showDescription: false,
});

const form = ref({
    studentId: null as string | null,
    issueDate: new Date().toISOString().split('T')[0],
    dueDate: '',
    discountType: 'fixed' as 'fixed' | 'percentage',
    discountValue: 0,
    taxPercentage: 0,
    notes: '',
    items: [newItem()] as LineItem[],
});

const errors = ref<Record<string, string>>({});
const saving = ref(false);
const selectedStudent = computed(() =>
    props.students.find((student) => student.value === form.value.studentId),
);
const bulkDiscountActive = computed(() => (form.value.discountValue || 0) > 0);
const showNotes = ref(false);

const dateValue = (value: string) => {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};

const formatDate = (date: Date | null | undefined) =>
    date ? date.toISOString().split('T')[0] : '';

/* ------------------------------------------------------------------ */
/* Row management                                                      */
/* ------------------------------------------------------------------ */

const addItem = () => {
    form.value.items.push(newItem());
    nextTick(() => {
        const el = document.querySelector<HTMLInputElement>(
            `[data-fee-input="${form.value.items.length - 1}"]`,
        );
        el?.focus();
        el?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
};

const removeItem = (index: number) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    } else {
        form.value.items[0] = newItem();
    }
};

const onRowKeydown = (event: KeyboardEvent, index: number) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();

    const isLast = index === form.value.items.length - 1;
    const item = form.value.items[index];

    if (isLast) {
        if (item.fee_type.trim() && item.unit_price > 0) addItem();
    } else {
        const next = document.querySelector<HTMLInputElement>(
            `[data-fee-input="${index + 1}"]`,
        );
        next?.focus();
    }
};

const toggleDescription = (item: LineItem) => {
    item.showDescription = !item.showDescription;
};

/* ------------------------------------------------------------------ */
/* Totals                                                              */
/* ------------------------------------------------------------------ */

const itemGross = (item: LineItem) =>
    (item.quantity || 0) * (item.unit_price || 0);

const itemDiscount = (item: LineItem) => {
    if (bulkDiscountActive.value) return 0;
    const gross = itemGross(item);
    const value = Number(item.discount_value) || 0;
    return Math.min(
        gross,
        item.discount_type === 'percentage'
            ? (gross * value) / 100
            : value,
    );
};

const subtotal = computed(() =>
    form.value.items.reduce((sum, item) => sum + itemGross(item), 0),
);
const itemDiscountTotal = computed(() =>
    form.value.items.reduce((sum, item) => sum + itemDiscount(item), 0),
);
const itemNetSubtotal = computed(() =>
    Math.max(subtotal.value - itemDiscountTotal.value, 0),
);

const discountAmount = computed(() => {
    if (form.value.discountType === 'percentage') {
        return Math.min(
            itemNetSubtotal.value,
            itemNetSubtotal.value * ((form.value.discountValue || 0) / 100),
        );
    }
    return Math.min(itemNetSubtotal.value, form.value.discountValue || 0);
});

const taxableAmount = computed(() =>
    Math.max(itemNetSubtotal.value - discountAmount.value, 0),
);
const taxAmount = computed(
    () => taxableAmount.value * ((form.value.taxPercentage || 0) / 100),
);
const totalAmount = computed(() => taxableAmount.value + taxAmount.value);

const money = (value: number) =>
    `Rs. ${value.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;

const clearItemDiscounts = () => {
    if ((form.value.discountValue || 0) <= 0) return;
    form.value.items.forEach((item) => {
        item.discount_value = 0;
    });
};

/* ------------------------------------------------------------------ */
/* Submit                                                              */
/* ------------------------------------------------------------------ */

const validate = () => {
    errors.value = {};
    if (!form.value.studentId) errors.value.student = 'Select a student';
    if (!form.value.dueDate) errors.value.dueDate = 'Due date is required';
    form.value.items.forEach((item, index) => {
        if (!item.fee_type.trim())
            errors.value[`item_${index}`] = 'Fee type is required';
        if (item.unit_price <= 0)
            errors.value[`price_${index}`] = 'Amount must be greater than 0';
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
        });
        toast.success('Invoice created successfully');
    } catch (error: unknown) {
        const responseErrors =
            error && typeof error === 'object'
                ? (error as Record<string, string>)
                : {};
        errors.value = responseErrors;
        toast.error(
            Object.values(responseErrors)[0] || 'Failed to create invoice',
        );
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
            <!-- ==================== Page header ==================== -->
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold tracking-tight text-slate-950">
                        Create invoice
                    </h1>
                    <p class="truncate text-xs text-slate-500">
                        Pick a student, add fee items, save.
                    </p>
                </div>
                <Button
                   
                    size="sm"
                    @click="router.visit('/invoice')"
                    class="h-8 text-xs cursor-pointer hover:bg-slate-100 hover:text-slate-900"
                >
                    <ArrowLeft class="mr-1.5 h-3.5 w-3.5" />Back
                </Button>
            </div>

            <!-- ==================== Form ==================== -->
            <form
                @submit.prevent="submit"
                @keydown="handleFormKeydown"
                class="grid gap-3 lg:grid-cols-3"
            >
                <!-- ============ LEFT (2/3) ============ -->
                <div class="min-w-0 space-y-3 lg:col-span-2">
                    <!-- --- Invoice details (compact) --- -->
                    <Card class="rounded-lg border shadow-sm">
                        <CardContent class="p-3">
                            <div class="grid gap-2.5 sm:grid-cols-3">
                                <div>
                                    <Label class="text-[11px] font-medium text-slate-500">
                                        Student *
                                    </Label>
                                    <CustomSelect
                                        v-model="form.studentId"
                                        :options="props.students"
                                        placeholder="Search student..."
                                    />
                                    <p
                                        v-if="errors.student"
                                        class="mt-0.5 text-[11px] text-red-600"
                                    >
                                        {{ errors.student }}
                                    </p>
                                </div>
                                <div>
                                    <Label class="text-[11px] font-medium text-slate-500">
                                        Issue date *
                                    </Label>
                                    <DatePicker
                                        :model-value="dateValue(form.issueDate)"
                                        @update:model-value="
                                            form.issueDate = formatDate($event)
                                        "
                                    />
                                </div>
                                <div>
                                    <Label class="text-[11px] font-medium text-slate-500">
                                        Due date *
                                    </Label>
                                    <DatePicker
                                        :model-value="dateValue(form.dueDate)"
                                        @update:model-value="
                                            form.dueDate = formatDate($event)
                                        "
                                    />
                                    <p
                                        v-if="errors.dueDate"
                                        class="mt-0.5 text-[11px] text-red-600"
                                    >
                                        {{ errors.dueDate }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- --- Fee items (scrollable) --- -->
                    <Card class="rounded-lg border shadow-sm">
                        <CardHeader
                            class="flex flex-row items-center justify-between space-y-0 border-b px-3 py-2"
                        >
                            <div class="flex items-center gap-2">
                                <CardTitle class="text-sm font-semibold">
                                    Fee items
                                </CardTitle>
                                <Badge
                                    variant="secondary"
                                    class="h-5 px-1.5 text-[10px] font-medium"
                                >
                                    {{ form.items.length }}
                                </Badge>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                class="h-7 px-2 text-xs"
                                @click="addItem"
                            >
                                <Plus class="mr-1 h-3.5 w-3.5" />Add
                            </Button>
                        </CardHeader>

                        <!-- Column header (sticky on scroll) -->
                        <div
                            class="hidden gap-2 border-b bg-slate-50/90 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500 md:grid md:grid-cols-[1.75rem_minmax(0,1fr)_3rem_6rem_8.5rem_6.5rem_3.5rem]"
                        >
                            <div>#</div>
                            <div>Fee type</div>
                            <div class="text-right">Qty</div>
                            <div class="text-right">Rate</div>
                            <div>Discount</div>
                            <div class="text-right">Amount</div>
                            <div></div>
                        </div>

                        <!-- Scrollable rows -->
                        <div
                            class="invoice-items-scroll max-h-[min(46vh,26rem)] divide-y overflow-y-auto"
                        >
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="px-3 py-2 transition-colors hover:bg-slate-50/60"
                            >
                                <div
                                    class="grid grid-cols-1 items-center gap-2 md:grid-cols-[1.75rem_minmax(0,1fr)_3rem_6rem_8.5rem_6.5rem_3.5rem] md:gap-2"
                                >
                                    <!-- # -->
                                    <div
                                        class="hidden items-center justify-center text-xs font-medium text-slate-400 md:flex"
                                    >
                                        {{ index + 1 }}
                                    </div>

                                    <!-- Fee type -->
                                    <div class="min-w-0">
                                        <Label
                                            class="mb-0.5 block text-[10px] text-slate-500 md:hidden"
                                        >
                                            Fee type *
                                        </Label>
                                        <Input
                                            v-model="item.fee_type"
                                            :data-fee-input="index"
                                            list="fee-suggestions"
                                            placeholder="e.g. Tuition Fee"
                                            class="h-8 text-sm"
                                            :class="{
                                                'border-red-500':
                                                    errors[`item_${index}`],
                                            }"
                                            @keydown="
                                                onRowKeydown($event, index)
                                            "
                                        />
                                    </div>

                                    <!-- Qty -->
                                    <div>
                                        <Label
                                            class="mb-0.5 block text-[10px] text-slate-500 md:hidden"
                                        >
                                            Qty
                                        </Label>
                                        <Input
                                            v-model.number="item.quantity"
                                            class="h-8 text-right text-sm"
                                            type="number"
                                            min="1"
                                            @keydown="
                                                onRowKeydown($event, index)
                                            "
                                        />
                                    </div>

                                    <!-- Rate -->
                                    <div>
                                        <Label
                                            class="mb-0.5 block text-[10px] text-slate-500 md:hidden"
                                        >
                                            Rate
                                        </Label>
                                        <Input
                                            v-model.number="item.unit_price"
                                            class="h-8 text-right text-sm"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :class="{
                                                'border-red-500':
                                                    errors[`price_${index}`],
                                            }"
                                            @keydown="
                                                onRowKeydown($event, index)
                                            "
                                        />
                                    </div>

                                    <!-- Discount -->
                                    <div>
                                        <Label
                                            class="mb-0.5 block text-[10px] text-slate-500 md:hidden"
                                        >
                                            Discount
                                        </Label>
                                        <div class="flex gap-1">
                                            <select
                                                v-model="item.discount_type"
                                                class="h-8 w-12 shrink-0 rounded-md border border-input bg-background px-1 text-xs"
                                                :disabled="bulkDiscountActive"
                                            >
                                                <option value="fixed">
                                                    Rs
                                                </option>
                                                <option value="percentage">
                                                    %
                                                </option>
                                            </select>
                                            <Input
                                                v-model.number="
                                                    item.discount_value
                                                "
                                                class="h-8 text-right text-sm"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                :disabled="bulkDiscountActive"
                                                @keydown="
                                                    onRowKeydown($event, index)
                                                "
                                            />
                                        </div>
                                    </div>

                                    <!-- Amount -->
                                    <div
                                        class="flex items-center justify-between md:block"
                                    >
                                        <Label
                                            class="text-[10px] text-slate-500 md:hidden"
                                        >
                                            Amount
                                        </Label>
                                        <div
                                            class="text-right text-sm font-semibold text-slate-900 tabular-nums"
                                        >
                                            {{
                                                money(
                                                    itemGross(item) -
                                                        itemDiscount(item),
                                                )
                                            }}
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div
                                        class="flex items-center justify-end gap-0.5 md:justify-center"
                                    >
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7"
                                            :title="
                                                item.showDescription
                                                    ? 'Hide note'
                                                    : 'Add note'
                                            "
                                            @click="toggleDescription(item)"
                                        >
                                            <MessageSquarePlus
                                                class="h-3.5 w-3.5"
                                                :class="
                                                    item.showDescription
                                                        ? 'text-blue-600'
                                                        : 'text-slate-400'
                                                "
                                            />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="h-7 w-7 text-slate-400 hover:bg-red-50 hover:text-red-600"
                                            :disabled="
                                                form.items.length === 1
                                            "
                                            title="Remove"
                                            @click="removeItem(index)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Expandable note -->
                                <div
                                    v-if="item.showDescription"
                                    class="mt-1.5 flex items-center gap-1.5 md:pl-[1.75rem]"
                                >
                                    <Input
                                        v-model="item.description"
                                        placeholder="Optional note for this fee..."
                                        class="h-7 text-xs"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="h-7 w-7 shrink-0 text-slate-400"
                                        @click="toggleDescription(item)"
                                    >
                                        <X class="h-3 w-3" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Add row (pinned under scroll area) -->
                        <div class="border-t bg-slate-50/40 p-2">
                            <Button
                                type="button"
                                variant="outline"
                                class="h-8 w-full border-dashed border-blue-200 bg-blue-50/50 text-xs text-blue-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-800"
                                @click="addItem"
                            >
                                <Plus class="mr-1.5 h-3.5 w-3.5" />
                                Add fee item
                            </Button>
                            <p
                                class="mt-1 text-center text-[10px] text-slate-400"
                            >
                                Press
                                <kbd
                                    class="rounded border border-slate-300 bg-white px-1 font-mono text-[9px] text-slate-600"
                                >
                                    Enter
                                </kbd>
                                in the last row to add a new line
                            </p>
                        </div>

                        <datalist id="fee-suggestions">
                            <option
                                v-for="fee in feeTypeSuggestions"
                                :key="fee"
                                :value="fee"
                            />
                        </datalist>
                    </Card>

                    <!-- --- Notes (inline, always visible) --- -->
                    <Card class="rounded-lg border shadow-sm">
                        <CardContent class="p-3">
                            <div class="mb-1.5 flex items-center gap-1.5">
                                <StickyNote class="h-3.5 w-3.5 text-slate-500" />
                                <Label
                                    class="text-[11px] font-medium text-slate-500"
                                >
                                    Notes
                                </Label>
                                <span class="text-[10px] text-slate-400">
                                    (optional — printed on the invoice)
                                </span>
                            </div>
                            <Textarea
                                v-model="form.notes"
                                placeholder="e.g. Fees once paid are non-refundable. Please quote the invoice number in all payments."
                                rows="2"
                                class="min-h-[3.5rem] resize-y text-sm"
                            />
                        </CardContent>
                    </Card>
                </div>

                <!-- ============ RIGHT (1/3 sidebar) ============ -->
                <div class="space-y-3 lg:sticky lg:top-3 lg:self-start">
                    <!-- --- Summary (first, most important) --- -->
                    <Card class="overflow-hidden rounded-lg border shadow-sm">
                        <CardHeader class="border-b px-3 py-2">
                            <CardTitle class="text-sm font-semibold">
                                Summary
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-1.5 p-3">
                            <div
                                class="flex justify-between text-xs text-slate-600"
                            >
                                <span>Subtotal</span>
                                <span
                                    class="font-medium text-slate-900 tabular-nums"
                                >
                                    {{ money(subtotal) }}
                                </span>
                            </div>

                            <div
                                v-if="itemDiscountTotal > 0"
                                class="flex justify-between text-xs text-slate-600"
                            >
                                <span>Item discount</span>
                                <span
                                    class="font-medium text-red-600 tabular-nums"
                                >
                                    - {{ money(itemDiscountTotal) }}
                                </span>
                            </div>

                            <div
                                v-if="discountAmount > 0"
                                class="flex justify-between text-xs text-slate-600"
                            >
                                <span>Bulk discount</span>
                                <span
                                    class="font-medium text-red-600 tabular-nums"
                                >
                                    - {{ money(discountAmount) }}
                                </span>
                            </div>

                            <div
                                v-if="taxAmount > 0"
                                class="flex justify-between text-xs text-slate-600"
                            >
                                <span>Tax</span>
                                <span
                                    class="font-medium text-slate-900 tabular-nums"
                                >
                                    {{ money(taxAmount) }}
                                </span>
                            </div>

                            <div
                                class="mt-1 flex items-center justify-between border-t pt-2.5"
                            >
                                <span
                                    class="text-sm font-semibold text-slate-950"
                                >
                                    Total
                                </span>
                                <span
                                    class="rounded-md bg-blue-600 px-2.5 py-1 text-sm font-semibold text-white tabular-nums"
                                >
                                    {{ money(totalAmount) }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- --- Discount & tax (compact) --- -->
                    <Card class="rounded-lg border shadow-sm">
                        <CardHeader class="border-b px-3 py-0.5">
                            <CardTitle class="text-sm font-semibold">
                                Discount & tax
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2.5 p-3">
                            <div>
                                <Label
                                    class="text-[11px] font-medium text-slate-500"
                                >
                                    Discount type
                                </Label>
                                <div
                                    class="mt-1 grid grid-cols-2 gap-1.5"
                                >
                                    <Button
                                        type="button"
                                        size="sm"
                                        class="h-7 text-xs"
                                        :variant="
                                            form.discountType === 'fixed'
                                                ? 'default'
                                                : 'outline'
                                        "
                                        @click="
                                            form.discountType = 'fixed';
                                            clearItemDiscounts();
                                        "
                                    >
                                        Fixed
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        class="h-7 text-xs"
                                        :variant="
                                            form.discountType === 'percentage'
                                                ? 'default'
                                                : 'outline'
                                        "
                                        @click="
                                            form.discountType = 'percentage';
                                            clearItemDiscounts();
                                        "
                                    >
                                        Percentage
                                    </Button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <Label
                                        class="text-[11px] font-medium text-slate-500"
                                    >
                                        Discount
                                    </Label>
                                    <Input
                                        v-model.number="form.discountValue"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-8 text-sm"
                                        @input="clearItemDiscounts"
                                    />
                                </div>
                                <div>
                                    <Label
                                        class="text-[11px] font-medium text-slate-500"
                                    >
                                        Tax (%)
                                    </Label>
                                    <Input
                                        v-model.number="form.taxPercentage"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="h-8 text-sm"
                                    />
                                </div>
                            </div>

                            <p
                                v-if="bulkDiscountActive"
                                class="text-[10px] leading-snug text-amber-600"
                            >
                                Item-level discounts are disabled while a bulk
                                discount is applied.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- --- Actions --- -->
                    <div class="flex flex-col gap-1.5">
                        <Button
                            type="submit"
                            :disabled="saving"
                            class="h-9 w-full text-sm cursor-pointer hover:bg-black-200 hover:text-white"
                        >
                            <Loader2
                                v-if="saving"
                                class="mr-1.5 h-4 w-4 animate-spin"
                            />
                            <Save v-else class="mr-1.5 h-4 w-4" />
                            {{ saving ? 'Creating...' : 'Create invoice' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-8 w-full text-xs cursor-pointer hover:bg-slate-100 hover:text-slate-900"
                            @click="router.visit('/invoice')"
                        >
                            Cancel
                        </Button>
                        <p class="text-center text-[10px] text-slate-400">
                            <kbd
                                class="rounded border border-slate-300 bg-white px-1 font-mono text-[9px]"
                            >
                                Ctrl </kbd
                            >+<kbd
                                class="rounded border border-slate-300 bg-white px-1 font-mono text-[9px]"
                            >
                                Enter </kbd
                            >&nbsp;to save
                        </p>
                    </div>
                </div>
            </form>
        </div>
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