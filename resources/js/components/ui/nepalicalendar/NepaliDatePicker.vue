<!--
  NepaliDatePicker.vue
  -----------------------------------------------------------------------
  Standalone Hamro Patro-style BS calendar. Drop it anywhere; it needs no
  parent state beyond the v-model.

  v-model            -> BS ISO string "2082-01-15" | null
  @select            -> DateSelection (bs + ad, both formats, on every pick)
  @invalid           -> string error message (bad day, out-of-range year...)

  Usage:
    <NepaliDatePicker v-model="bsDate" @select="onSelect" />

    function onSelect(sel: DateSelection) {
      console.log(sel.ad)       // "2025-04-28"  -> save this to the DB / hidden input
      console.log(sel.bs)       // "2082-01-15"  -> what the customer sees
    }
-->
<script setup lang="ts">
import { ref, reactive, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import type { Lang, BsDate, DateSelection } from '@/composables/bikramSambat';
import {
  BS_START, BS_END, daysInMonth, daysInYear, isValidBs, weekdayOf, bsToAd, todayBs,
  addDaysBs, isoBs, toNepaliDigits, toEnglishDigits, MONTH_NAMES, WEEKDAY_SHORT,
  AD_MONTHS_SHORT, localizeNumber, parseBsText, toSelection,
} from '@/composables/bikramSambat';

const props = withDefaults(defineProps<{
  modelValue?: string | null;
  lang?: Lang;
  placeholder?: string;
  disabled?: boolean;
}>(), {
  modelValue: null,
  lang: 'ne',
  disabled: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  'update:lang': [value: Lang];
  select: [value: DateSelection];
  clear: [];
  invalid: [message: string];
}>();

const lang = computed<Lang>({
  get: () => props.lang,
  set: v => emit('update:lang', v),
});

const STR = {
  ne: { today: 'आज', clear: 'हटाउनुहोस्', prev: 'अघिल्लो महिना', next: 'अर्को महिना', placeholder: '२०८२-०१-१५' },
  en: { today: 'Today', clear: 'Clear', prev: 'Previous month', next: 'Next month', placeholder: '2082-01-15' },
} as const;

const fieldEl = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);
const popEl = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const popUp = ref(false); // true = pop flips above the field
const text = ref('');
const errorMsg = ref('');

const sel = ref<BsDate | null>(null);
const view = reactive<{ y: number; m: number }>({ ...todayBs() });
const focusDay = ref<number | null>(null);
const hoverDay = ref<number | null>(null);

function parseModel(v: string | null | undefined): BsDate | null {
  if (!v) return null;
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v);
  if (!m) return null;
  const y = +m[1], mo = +m[2], d = +m[3];
  return isValidBs(y, mo, d) ? { y, m: mo, d } : null;
}

function syncFromModel() {
  const b = parseModel(props.modelValue);
  sel.value = b;
  if (b) { view.y = b.y; view.m = b.m; }
  writeText();
}
watch(() => props.modelValue, syncFromModel, { immediate: true });
watch(lang, () => writeText());

function writeText() {
  if (!sel.value) { text.value = ''; return; }
  const t = isoBs(sel.value);
  text.value = lang.value === 'ne' ? toNepaliDigits(t) : t;
}

function commit(b: BsDate) {
  sel.value = b;
  view.y = b.y; view.m = b.m;
  focusDay.value = b.d;
  writeText();
  errorMsg.value = '';
  emit('update:modelValue', isoBs(b));
  emit('select', toSelection(b));
}

function clear() {
  sel.value = null;
  text.value = '';
  errorMsg.value = '';
  emit('update:modelValue', null);
  emit('clear');
}

/* ---------------- typing ---------------- */
function sanitize(raw: string, caret: number) {
  let out = '', c = caret;
  for (let i = 0; i < raw.length; i++) {
    let ch = raw[i];
    if (/[0-9]/.test(ch)) ch = lang.value === 'ne' ? toNepaliDigits(ch) : ch;
    else if (/[०-९]/.test(ch)) ch = lang.value === 'ne' ? ch : toEnglishDigits(ch);
    else if (!/[-/.\s]/.test(ch)) { if (i < caret) c--; continue; }
    out += ch;
  }
  return { text: out, caret: c };
}

function errorText(code: 'format' | 'year' | 'month' | 'day', y?: number, m?: number, max?: number): string {
  const ne = lang.value === 'ne';
  switch (code) {
    case 'year': return ne ? `वर्ष ${toNepaliDigits(BS_START)} देखि ${toNepaliDigits(BS_END)} सम्म मात्र मिल्छ।` : `Year must be between ${BS_START} and ${BS_END}.`;
    case 'month': return ne ? 'महिना १ देखि १२ सम्म हुनुपर्छ।' : 'Month must be between 1 and 12.';
    case 'day': return ne ? `${MONTH_NAMES.ne[(m ?? 1) - 1]} ${toNepaliDigits(y ?? 0)} मा ${toNepaliDigits(max ?? 0)} दिन मात्र छन्।` : `${MONTH_NAMES.en[(m ?? 1) - 1]} ${y} has only ${max} days.`;
    default: return ne ? 'मिति यसरी लेख्नुहोस्: २०८२-०१-१५' : 'Type the date like 2082-01-15.';
  }
}

function onInput(e: Event) {
  const el = e.target as HTMLInputElement;
  const { text: clean, caret } = sanitize(el.value, el.selectionStart ?? el.value.length);
  if (clean !== el.value) { el.value = clean; nextTick(() => el.setSelectionRange(caret, caret)); }
  text.value = clean;

  const r = parseBsText(clean);
  errorMsg.value = '';
  if (r.status === 'ok') {
    sel.value = { y: r.y, m: r.m, d: r.d };
    view.y = r.y; view.m = r.m;
    focusDay.value = r.d;
    emit('update:modelValue', isoBs(sel.value));
    emit('select', toSelection(sel.value));
  } else {
    sel.value = null;
    if (r.status === 'partial' && r.y) { view.y = r.y; if (r.m) view.m = r.m; }
    if (r.status === 'invalid') {
      errorMsg.value = errorText(r.code, r.y, r.m, r.max);
      if (r.y && r.m) { view.y = r.y; view.m = r.m; }
      emit('invalid', errorMsg.value);
    }
  }
  if (!isOpen.value) open();
}

function onBlur() {
  const r = parseBsText(text.value);
  if (r.status === 'ok') { sel.value = { y: r.y, m: r.m, d: r.d }; writeText(); }
  else if (r.status === 'partial' && text.value) {
    errorMsg.value = errorText('format');
  }
}

/* ---------------- calendar grid ---------------- */
const today = todayBs();

const monthDays = computed(() => {
  const { y, m } = view;
  const first = weekdayOf(y, m, 1);
  const n = daysInMonth(y, m);
  const cells: Array<{ d: number; wd: number; adLabel: string; isFirst: boolean } | null> = [];
  for (let i = 0; i < first; i++) cells.push(null);
  for (let d = 1; d <= n; d++) {
    const wd = (first + d - 1) % 7;
    const ad = bsToAd(y, m, d);
    cells.push({ d, wd, adLabel: ad.d === 1 ? `${AD_MONTHS_SHORT[ad.m - 1]} 1` : String(ad.d), isFirst: ad.d === 1 });
  }
  return cells;
});

const monthSpan = computed(() => {
  const { y, m } = view;
  const n = daysInMonth(y, m);
  const a1 = bsToAd(y, m, 1), a2 = bsToAd(y, m, n);
  if (a1.m === a2.m) return `${AD_MONTHS_SHORT[a1.m - 1]} ${a1.y}`;
  if (a1.y === a2.y) return `${AD_MONTHS_SHORT[a1.m - 1]} \u2013 ${AD_MONTHS_SHORT[a2.m - 1]} ${a1.y}`;
  return `${AD_MONTHS_SHORT[a1.m - 1]} ${a1.y} \u2013 ${AD_MONTHS_SHORT[a2.m - 1]} ${a2.y}`;
});

const summaryAd = computed(() => {
  const b = (hoverDay.value ? { y: view.y, m: view.m, d: hoverDay.value } : null) ?? sel.value ?? today;
  return bsToAd(b.y, b.m, b.d);
});

const atStart = computed(() => view.y === BS_START && view.m === 1);
const atEnd = computed(() => view.y === BS_END && view.m === 12);
const years = Array.from({ length: BS_END - BS_START + 1 }, (_, i) => BS_START + i);

function go(delta: number) {
  let m = view.m + delta, y = view.y;
  if (m < 1) { m = 12; y--; } else if (m > 12) { m = 1; y++; }
  if (y < BS_START || y > BS_END) return;
  view.y = y; view.m = m; focusDay.value = null;
}

function pick(d: number) {
  commit({ y: view.y, m: view.m, d });
  close();
}

/* ---------------- open / close / positioning ---------------- */
function open() {
  if (props.disabled || isOpen.value) return;
  isOpen.value = true;
  if (sel.value) { view.y = sel.value.y; view.m = sel.value.m; }
  nextTick(placePopup);
}
function close() { isOpen.value = false; hoverDay.value = null; }

function placePopup() {
  if (!popEl.value || !fieldEl.value) return;
  const pr = popEl.value.getBoundingClientRect();
  const fr = fieldEl.value.getBoundingClientRect();
  popUp.value = pr.bottom > window.innerHeight && fr.top > pr.height + 12;
}

function onDocPointerDown(e: PointerEvent) {
  if (isOpen.value && fieldEl.value && !fieldEl.value.contains(e.target as Node)) close();
}
document.addEventListener('pointerdown', onDocPointerDown);
onBeforeUnmount(() => document.removeEventListener('pointerdown', onDocPointerDown));
window.addEventListener('resize', () => isOpen.value && placePopup());

/* ---------------- keyboard ---------------- */
function onFieldKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') close();
  else if (e.key === 'Enter') { onBlur(); close(); }
  else if (e.key === 'ArrowDown') { e.preventDefault(); open(); }
}
function focusedDay(): number {
  if (focusDay.value && focusDay.value <= daysInMonth(view.y, view.m)) return focusDay.value;
  if (sel.value && sel.value.y === view.y && sel.value.m === view.m) return sel.value.d;
  if (today.y === view.y && today.m === view.m) return today.d;
  return 1;
}
function onGridKeydown(e: KeyboardEvent, d: number) {
  const cur: BsDate = { y: view.y, m: view.m, d };
  let next: BsDate | null = null;
  const shiftMonth = (dm: number): BsDate => {
    let y = cur.y, m = cur.m + dm;
    if (m < 1) { m = 12; y--; } else if (m > 12) { m = 1; y++; }
    return (y < BS_START || y > BS_END) ? cur : { y, m, d: Math.min(cur.d, daysInMonth(y, m)) };
  };
  switch (e.key) {
    case 'ArrowLeft': next = addDaysBs(cur, -1); break;
    case 'ArrowRight': next = addDaysBs(cur, 1); break;
    case 'ArrowUp': next = addDaysBs(cur, -7); break;
    case 'ArrowDown': next = addDaysBs(cur, 7); break;
    case 'PageUp': next = shiftMonth(-1); break;
    case 'PageDown': next = shiftMonth(1); break;
    case 'Home': next = { ...cur, d: 1 }; break;
    case 'End': next = { ...cur, d: daysInMonth(cur.y, cur.m) }; break;
    case 'Enter': case ' ': pick(d); return;
    case 'Escape': close(); inputEl.value?.focus(); return;
    default: return;
  }
  e.preventDefault();
  focusDay.value = next.d; view.y = next.y; view.m = next.m;
  nextTick(() => (popEl.value?.querySelector(`[data-d="${next!.d}"]`) as HTMLElement | null)?.focus());
}

defineExpose({ open, close, clear, today: () => commit(todayBs()) });
</script>

<template>
  <div ref="fieldEl" class="ndp-field" :class="{ invalid: errorMsg, disabled }">
    <input
      ref="inputEl"
      type="text"
      autocomplete="off"
      spellcheck="false"
      :disabled="disabled"
      :placeholder="placeholder ?? STR[lang].placeholder"
      :value="text"
      aria-haspopup="dialog"
      :aria-expanded="isOpen"
      @input="onInput"
      @focus="open"
      @click="open"
      @blur="onBlur"
      @keydown="onFieldKeydown"
    />
    <button type="button" class="ndp-icon" :disabled="disabled" @click="isOpen ? close() : open()" aria-label="Open calendar">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" /><path d="M3.5 10h17M8 3v4M16 3v4" />
      </svg>
    </button>
    <p v-if="errorMsg" class="ndp-msg" role="alert">{{ errorMsg }}</p>

    <div v-if="isOpen" ref="popEl" class="ndp-pop" :class="{ up: popUp }" role="dialog" aria-label="Nepali calendar">
      <div class="ndp-head">
        <button type="button" class="ndp-nav" :disabled="atStart" :aria-label="STR[lang].prev" @click="go(-1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7" /></svg>
        </button>
        <div class="ndp-title">
          <div class="ndp-selects">
            <select v-model.number="view.m" aria-label="Month">
              <option v-for="(nm, i) in MONTH_NAMES[lang]" :key="i" :value="i + 1">{{ nm }}</option>
            </select>
            <select v-model.number="view.y" aria-label="Year">
              <option v-for="yy in years" :key="yy" :value="yy">{{ localizeNumber(yy, lang) }}</option>
            </select>
          </div>
          <div class="ndp-span">{{ monthSpan }}</div>
        </div>
        <button type="button" class="ndp-nav" :disabled="atEnd" :aria-label="STR[lang].next" @click="go(1)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7" /></svg>
        </button>
      </div>

      <div class="ndp-body">
        <div class="ndp-week">
          <span v-for="(w, i) in WEEKDAY_SHORT[lang]" :key="w" :class="{ sat: i === 6 }">{{ w }}</span>
        </div>
        <div class="ndp-grid">
          <template v-for="(cell, i) in monthDays" :key="i">
            <span v-if="!cell"></span>
            <button
              v-else
              type="button"
              class="ndp-cell"
              :class="{ sat: cell.wd === 6, today: today.y === view.y && today.m === view.m && today.d === cell.d, sel: sel && sel.y === view.y && sel.m === view.m && sel.d === cell.d }"
              :data-d="cell.d"
              :tabindex="cell.d === focusedDay() ? 0 : -1"
              @click="pick(cell.d)"
              @mouseenter="hoverDay = cell.d"
              @mouseleave="hoverDay = null"
              @keydown="onGridKeydown($event, cell.d)"
            >
              <span class="bs">{{ localizeNumber(cell.d, lang) }}</span>
              <span class="ad" :class="{ first: cell.isFirst }">{{ cell.adLabel }}</span>
            </button>
          </template>
        </div>
      </div>

      <div class="ndp-foot">
        <div class="ndp-sum"><span class="tag">AD</span>{{ summaryAd.d }} {{ AD_MONTHS_SHORT[summaryAd.m - 1] }} {{ summaryAd.y }}</div>
        <div class="ndp-bar">
          <button type="button" class="ndp-link" @click="commit(todayBs()); close()">{{ STR[lang].today }}</button>
          <!-- <button type="button" class="ndp-link" @click="clear(); close()">{{ STR[lang].clear }}</button> -->
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ndp-field { position: relative; font-family: 'Mukta', 'Noto Sans Devanagari', system-ui, sans-serif; }
.ndp-field input { width: 100%; height: 44px; padding: 0 44px 0 12px; font-size: 16px; font-weight: 500; border: 1.5px solid #e2e5ea; border-radius: 10px; background: #fff; box-sizing: border-box; }
.ndp-field input:focus { outline: none; border-color: #b81d2b; box-shadow: 0 0 0 3px rgba(184, 29, 43, .15); }
.ndp-field.invalid input { border-color: #d1283a; }
.ndp-field.disabled input { background: #f4f5f6; color: #9aa0a8; }
.ndp-icon { position: absolute; right: 5px; top: 5px; width: 34px; height: 34px; border: 0; border-radius: 8px; background: transparent; color: #b81d2b; cursor: pointer; display: grid; place-items: center; }
.ndp-icon:hover { background: #fbe7e9; }
.ndp-msg { margin: 6px 0 0; font-size: 13px; color: #b81d2b; }

.ndp-pop { position: absolute; z-index: 50; left: 0; top: calc(100% + 8px); width: 312px; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 14px 36px rgba(20,22,32,.2), 0 2px 6px rgba(20,22,32,.08); }
.ndp-pop.up { top: auto; bottom: calc(100% + 8px); }
.ndp-head { display: flex; align-items: center; gap: 4px; padding: 8px 6px 10px; background: #b81d2b; color: #fff; }
.ndp-nav { width: 34px; height: 34px; border: 0; border-radius: 50%; background: transparent; color: #fff; cursor: pointer; display: grid; place-items: center; }
.ndp-nav:hover:not(:disabled) { background: rgba(255,255,255,.18); }
.ndp-nav:disabled { opacity: .35; }
.ndp-title { flex: 1; text-align: center; min-width: 0; }
.ndp-selects { display: flex; justify-content: center; gap: 4px; }
.ndp-selects select { appearance: none; border: 0; border-radius: 6px; background: transparent; color: #fff; font-size: 18px; font-weight: 700; padding: 0 14px 0 4px; }
.ndp-selects option { color: #1d2129; }
.ndp-span { font-size: 12px; opacity: .86; }
.ndp-body { padding: 6px 8px 2px; }
.ndp-week, .ndp-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
.ndp-week span { padding: 5px 0; text-align: center; font-size: 12px; font-weight: 600; color: #6a7280; }
.ndp-week span.sat { color: #d1283a; }
.ndp-grid { row-gap: 2px; }
.ndp-cell { position: relative; height: 42px; border: 0; border-radius: 10px; background: transparent; cursor: pointer; display: grid; place-items: center; padding: 0 0 6px; }
.ndp-cell .bs { font-size: 16px; font-weight: 600; }
.ndp-cell .ad { position: absolute; right: 4px; bottom: 2px; font-size: 9px; color: #6a7280; }
.ndp-cell .ad.first { color: #b81d2b; font-weight: 700; }
.ndp-cell.sat .bs { color: #d1283a; }
.ndp-cell:hover { background: #fbe7e9; }
.ndp-cell.today { box-shadow: inset 0 0 0 2px #f0a30a; }
.ndp-cell.sel { background: #b81d2b; }
.ndp-cell.sel .bs, .ndp-cell.sel .ad { color: #fff; }
.ndp-foot { border-top: 1px solid #e2e5ea; padding: 8px 10px 10px; }
.ndp-sum { font-size: 13px; font-weight: 500; }
.ndp-sum .tag { display: inline-block; margin-right: 6px; padding: 0 6px; border-radius: 5px; background: #fbe7e9; color: #8e1420; font-size: 11px; font-weight: 700; }
.ndp-bar { display: flex; justify-content: flex-end; gap: 4px; margin-top: 6px; }
.ndp-link { border: 0; background: transparent; color: #b81d2b; font-weight: 600; font-size: 14px; padding: 3px 10px; border-radius: 8px; cursor: pointer; }
.ndp-link:hover { background: #fbe7e9; }
</style>
