<script setup lang="ts">
import DatePicker from '@/components/ui/customdatepicker/CustomDatePicker.vue';
import NepaliDatePicker from '@/components/ui/nepalicalendar/NepaliDatePicker.vue';
import { CalendarRange } from 'lucide-vue-next';
import { adToBs, bsToAd, isoAd, isoBs } from '@/composables/bikramSambat';
import type { DateSelection } from '@/composables/bikramSambat';
import { computed, ref } from 'vue';

type DateRangeValue = {
    from: string;
    to: string;
};

const props = withDefaults(
    defineProps<{
        modelValue?: DateRangeValue | null;
        fromLabel?: string;
        toLabel?: string;
        disabled?: boolean;
    }>(),
    {
        modelValue: () => ({ from: '', to: '' }),
        fromLabel: 'From date',
        toLabel: 'To date',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: DateRangeValue];
}>();

const mode = ref<'en' | 'ne'>('en');

const localValue = computed({
    get: () => props.modelValue ?? { from: '', to: '' },
    set: (value: DateRangeValue) => emit('update:modelValue', value),
});

const toBsValue = (adDate: string | null | undefined): string | null => {
    if (!adDate) return null;
    const [year, month, day] = adDate.split('-').map(Number);
    if (!year || !month || !day) return null;

    const bsDate = adToBs(year, month, day);
    return bsDate ? isoBs(bsDate) : null;
};

const toAdValue = (bsDate: string | null | undefined): string => {
    if (!bsDate) return '';
    const [year, month, day] = bsDate.split('-').map(Number);
    if (!year || !month || !day) return '';

    const adDate = bsToAd(year, month, day);
    return adDate ? isoAd(adDate) : '';
};

const updateField = (field: 'from' | 'to', value: string | null) => {
    const nextValue = {
        ...localValue.value,
        [field]: value ?? '',
    };

    localValue.value = nextValue;
};

const handleNepaliSelection = (field: 'from' | 'to', selection: DateSelection) => {
    updateField(field, selection.ad ?? '');
};

const getFieldModel = (field: 'from' | 'to') => {
    const current = localValue.value[field];
    return mode.value === 'ne' ? toBsValue(current) : current;
};
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <CalendarRange class="h-4 w-4 text-slate-600" />
                Date range
            </div>

            <div class="inline-flex rounded-full border border-slate-200 bg-slate-100 p-1">
                <button
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[10px] font-semibold transition-all"
                    :class="mode === 'en' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    @click="mode = 'en'"
                >
                    EN
                </button>
                <button
                    type="button"
                    class="rounded-full px-2.5 py-1 text-[10px] font-semibold transition-all"
                    :class="mode === 'ne' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    @click="mode = 'ne'"
                >
                    NP
                </button>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <div class="space-y-1.5">
                <label class="block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ fromLabel }}</label>
                <template v-if="mode === 'en'">
                    <DatePicker
                        :model-value="localValue.from"
                        :disabled="disabled"
                        placeholder="From date"
                        @update:model-value="(value: string) => updateField('from', value)"
                    />
                </template>
                <template v-else>
                    <NepaliDatePicker
                        :model-value="getFieldModel('from')"
                        :disabled="disabled"
                        placeholder="From date"
                        @update:model-value="(value: string | null) => updateField('from', toAdValue(value))"
                        @select="(selection: DateSelection) => handleNepaliSelection('from', selection)"
                    />
                </template>
            </div>

            <div class="space-y-1.5">
                <label class="block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ toLabel }}</label>
                <template v-if="mode === 'en'">
                    <DatePicker
                        :model-value="localValue.to"
                        :disabled="disabled"
                        placeholder="To date"
                        @update:model-value="(value: string) => updateField('to', value)"
                    />
                </template>
                <template v-else>
                    <NepaliDatePicker
                        :model-value="getFieldModel('to')"
                        :disabled="disabled"
                        placeholder="To date"
                        @update:model-value="(value: string | null) => updateField('to', toAdValue(value))"
                        @select="(selection: DateSelection) => handleNepaliSelection('to', selection)"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
