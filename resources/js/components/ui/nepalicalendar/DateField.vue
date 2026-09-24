<!--
  DateField.vue
  -----------------------------------------------------------------------
  The "use case" component: lets the customer toggle between an English
  (AD) date input and a Nepali (BS) date picker, but always gives you a
  single AD ISO date to save — because that's what most databases want.

  v-model            -> AD ISO string "2025-04-28" | null   (bind this to your form/db field)
  v-model:mode       -> 'ne' | 'en'  (optional, if you want to control/persist the toggle)
  @select            -> full DateSelection (bs + ad + both formatted strings) every time a date is picked
  name / hiddenName  -> if set, renders a real <input type="hidden"> with that name so a
                        plain HTML form (no JS) posts the AD date directly

  Usage:
    <DateField v-model="admissionDateAd" name="admission_date" @select="onSelect" />

    function onSelect(sel: DateSelection) {
      console.log(sel.ad)   // "2025-04-28"  <- send this to the backend / save to DB
      console.log(sel.bs)   // "2082-01-15"  <- what you show the Nepali customer on a report
    }
-->
<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import NepaliDatePicker from './NepaliDatePicker.vue';
import { Lang, DateSelection } from '@/composables/bikramSambat';
import { adToBs, isoBs, isoAd } from '@/composables/bikramSambat';

const props = withDefaults(defineProps<{
  /** Always an AD ISO date ("2025-04-28"), regardless of which picker is active. */
  modelValue?: string | null;
  /** Which picker is showing. Defaults to Nepali. */
  mode?: Lang;
  /** Renders <input type="hidden" :name> with the AD value, for non-JS form posts. */
  name?: string;
  disabled?: boolean;
  min?: string; // AD ISO, for the English picker
  max?: string; // AD ISO, for the English picker
}>(), {
  modelValue: null,
  mode: 'ne',
  disabled: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  'update:mode': [value: Lang];
  select: [value: DateSelection];
  clear: [];
}>();

const mode = computed<Lang>({
  get: () => props.mode,
  set: v => emit('update:mode', v),
});

const LABEL = { ne: 'नेपाली', en: 'English' } as const;

/* Nepali picker binds to a BS iso string; native <input type=date> binds to AD. */
const bsValue = ref<string | null>(null);
const adValue = ref<string | null>(props.modelValue ?? null);

watch(() => props.modelValue, v => {
  adValue.value = v ?? null;
  const b = v ? adToBs(...(v.split('-').map(Number) as [number, number, number])) : null;
  bsValue.value = b ? isoBs(b) : null;
}, { immediate: true });

function onNepaliSelect(sel: DateSelection) {
  adValue.value = sel.ad;
  emit('update:modelValue', sel.ad);
  emit('select', sel);
}
function onNepaliClear() {
  adValue.value = null;
  emit('update:modelValue', null);
  emit('clear');
}

function onEnglishChange(e: Event) {
  const v = (e.target as HTMLInputElement).value; // "" | "YYYY-MM-DD"
  if (!v) { adValue.value = null; emit('update:modelValue', null); emit('clear'); return; }
  const [y, m, d] = v.split('-').map(Number);
  adValue.value = v;
  emit('update:modelValue', v);
  const bs = adToBs(y, m, d);
  if (bs) {
    bsValue.value = isoBs(bs);
    emit('select', {
      bs: isoBs(bs), ad: v, bsParts: bs, adParts: { y, m, d, wd: new Date(v).getDay() },
      weekday: new Date(v).getDay(),
      bsFormatted: { ne: '', en: '' }, adFormatted: { ne: '', en: '' },
    });
  }
}
</script>

<template>
  <div class="df-wrap">
    <div class="df-toggle" role="group" aria-label="Calendar type">
      <button type="button" :aria-pressed="mode === 'ne'" :disabled="disabled" @click="mode = 'ne'">{{ LABEL.ne }}</button>
      <button type="button" :aria-pressed="mode === 'en'" :disabled="disabled" @click="mode = 'en'">{{ LABEL.en }}</button>
    </div>

    <NepaliDatePicker
      v-if="mode === 'ne'"
      v-model="bsValue"
      lang="ne"
      :disabled="disabled"
      @select="onNepaliSelect"
      @clear="onNepaliClear"
    />
    <input
      v-else
      type="date"
      class="df-native"
      :disabled="disabled"
      :min="min"
      :max="max"
      :value="adValue ?? ''"
      @change="onEnglishChange"
    />

    <!-- plain-HTML-form-friendly hidden field, always AD -->
    <input v-if="name" type="hidden" :name="name" :value="adValue ?? ''" />
  </div>
</template>

<style scoped>
.df-wrap { font-family: 'Mukta', 'Noto Sans Devanagari', system-ui, sans-serif; }
.df-toggle { display: inline-flex; background: #f0f1f3; border-radius: 999px; padding: 3px; margin-bottom: 8px; }
.df-toggle button { border: 0; background: transparent; border-radius: 999px; padding: 4px 14px; font-size: 13px; font-weight: 500; cursor: pointer; color: #6a7280; }
.df-toggle button[aria-pressed="true"] { background: #1d2129; color: #fff; }
.df-toggle button:disabled { cursor: default; opacity: .6; }
.df-native { width: 100%; height: 44px; padding: 0 12px; border: 1.5px solid #e2e5ea; border-radius: 10px; background: #fff; box-sizing: border-box; font-size: 16px; }
.df-native:focus { outline: none; border-color: #b81d2b; box-shadow: 0 0 0 3px rgba(184, 29, 43, .15); }
</style>
