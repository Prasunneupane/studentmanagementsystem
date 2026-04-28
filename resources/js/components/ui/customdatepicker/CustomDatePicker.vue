<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted, Teleport } from 'vue'
import { ChevronLeft, ChevronRight, Calendar, X } from 'lucide-vue-next'

/**
 * ─────────────────────────────────────────────────────────────────
 * TYPES & INTERFACES
 * ─────────────────────────────────────────────────────────────────
 */

interface CalendarDay {
  day: number | null
  isCurrentMonth: boolean
  isToday: boolean
  isSelected: boolean
  date: string | null
}

interface DatePickerProps {
  // Data binding
  modelValue?: string
  defaultDate?: string
  value?: string

  // UI Configuration
  placeholder?: string
  disabled?: boolean

  // Selector toggles (conditional month/year dropdowns)
  year?: boolean
  month?: boolean

  // Initial calendar position (only used if modelValue/defaultDate not provided)
  initialYear?: number
  initialMonth?: number

  // Date format (support multiple formats for manual input parsing)
  dateFormat?: 'YYYY-MM-DD' | 'DD/MM/YYYY' | 'MM/DD/YYYY'
}

/**
 * ─────────────────────────────────────────────────────────────────
 * COMPONENT SETUP
 * ─────────────────────────────────────────────────────────────────
 */

const props = withDefaults(defineProps<DatePickerProps>(), {
  placeholder: 'Pick a date',
  disabled: false,
  year: false,
  month: false,
  dateFormat: 'YYYY-MM-DD',
  initialYear: undefined,
  initialMonth: undefined,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

/**
 * ─────────────────────────────────────────────────────────────────
 * STATE MANAGEMENT
 * ─────────────────────────────────────────────────────────────────
 */

const isOpen = ref(false)
const inputValue = ref('')
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(new Date().getMonth())
const calendarContainer = ref<HTMLDivElement | null>(null)
const inputContainer = ref<HTMLDivElement | null>(null)

/**
 * Dropdown positioning (fixed to viewport, rendered via Teleport)
 */
const dropdownStyle = ref<Record<string, string>>({})

const positionDropdown = () => {
  if (!inputContainer.value) return
  const rect = inputContainer.value.getBoundingClientRect()
  const dropdownHeight = 380 // approximate calendar height
  const spaceBelow = window.innerHeight - rect.bottom
  const spaceAbove = rect.top
  const showAbove = spaceBelow < dropdownHeight && spaceAbove > spaceBelow

  dropdownStyle.value = {
    position: 'fixed',
    left: `${rect.left}px`,
    width: '320px',
    zIndex: '9999',
    ...(showAbove
      ? { bottom: `${window.innerHeight - rect.top + 4}px` }
      : { top: `${rect.bottom + 4}px` }),
  }
}

/**
 * Month names for display and lookup
 */
const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
]

/**
 * ─────────────────────────────────────────────────────────────────
 * DATE PARSING & FORMATTING UTILITIES
 * ─────────────────────────────────────────────────────────────────
 */

/**
 * Parse various date formats and return YYYY-MM-DD string
 */
const parseDate = (dateString: string): string | null => {
  if (!dateString) return null

  // Already in YYYY-MM-DD format
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
    const date = new Date(dateString)
    return !isNaN(date.getTime()) ? dateString : null
  }

  // DD/MM/YYYY format
  if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(dateString)) {
    const [day, month, year] = dateString.split('/').map(Number)
    const date = new Date(year, month - 1, day)
    if (!isNaN(date.getTime())) {
      return formatDate(date.getFullYear(), date.getMonth(), date.getDate())
    }
  }

  // MM/DD/YYYY format
  if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(dateString)) {
    const [month, day, year] = dateString.split('/').map(Number)
    const date = new Date(year, month - 1, day)
    if (!isNaN(date.getTime())) {
      return formatDate(date.getFullYear(), date.getMonth(), date.getDate())
    }
  }

  // Try native Date parsing as fallback
  const date = new Date(dateString)
  if (!isNaN(date.getTime())) {
    return formatDate(date.getFullYear(), date.getMonth(), date.getDate())
  }

  return null
}

/**
 * Format date components to YYYY-MM-DD string
 */
const formatDate = (year: number, month: number, day: number): string => {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${year}-${pad(month + 1)}-${pad(day)}`
}

/**
 * Format date for display based on user preference
 */
const formatDateForDisplay = (dateString: string): string => {
  if (!dateString) return ''
  const [year, month, day] = dateString.split('-')
  
  switch (props.dateFormat) {
    case 'DD/MM/YYYY':
      return `${day}/${month}/${year}`
    case 'MM/DD/YYYY':
      return `${month}/${day}/${year}`
    case 'YYYY-MM-DD':
    default:
      return dateString
  }
}

/**
 * ─────────────────────────────────────────────────────────────────
 * INITIALIZATION & WATCHERS
 * ─────────────────────────────────────────────────────────────────
 */

/**
 * Initialize input value and calendar from modelValue, defaultDate, or value prop
 */
const initializeDatePicker = () => {
  const sourceDate = props.modelValue || props.defaultDate || props.value

  if (sourceDate) {
    const parsed = parseDate(sourceDate)
    if (parsed) {
      inputValue.value = parsed
      const [year, month, day] = parsed.split('-').map(Number)
      selectedYear.value = year
      selectedMonth.value = month - 1
    }
  } else {
    // No default date provided - use initial year/month if provided
    if (props.initialYear !== undefined) selectedYear.value = props.initialYear
    if (props.initialMonth !== undefined) selectedMonth.value = props.initialMonth
  }
}

onMounted(() => {
  initializeDatePicker()
})

/**
 * Watch for external changes to modelValue prop
 */
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal && newVal !== inputValue.value) {
      const parsed = parseDate(newVal)
      if (parsed) {
        inputValue.value = parsed
        const [year, month, day] = parsed.split('-').map(Number)
        selectedYear.value = year
        selectedMonth.value = month - 1
      }
    }
  }
)

/**
 * ─────────────────────────────────────────────────────────────────
 * CALENDAR GRID GENERATION
 * ─────────────────────────────────────────────────────────────────
 */

/**
 * Get number of days in a specific month
 */
const getDaysInMonth = (year: number, month: number): number => {
  return new Date(year, month + 1, 0).getDate()
}

/**
 * Get first day of week for a specific month (0 = Sunday)
 */
const getFirstDayOfMonth = (year: number, month: number): number => {
  return new Date(year, month, 1).getDay()
}

/**
 * Generate the complete calendar grid for the current selected month/year
 */
const calendarGrid = computed((): CalendarDay[] => {
  const daysInMonth = getDaysInMonth(selectedYear.value, selectedMonth.value)
  const firstDay = getFirstDayOfMonth(selectedYear.value, selectedMonth.value)
  const prevMonthDays = getDaysInMonth(
    selectedMonth.value === 0 ? selectedYear.value - 1 : selectedYear.value,
    selectedMonth.value === 0 ? 11 : selectedMonth.value - 1
  )

  const days: CalendarDay[] = []
  const today = new Date()
  const currentDateStr = today.getFullYear() === selectedYear.value &&
    today.getMonth() === selectedMonth.value
    ? today.getDate()
    : null

  // Previous month's days (grayed out)
  for (let i = firstDay - 1; i >= 0; i--) {
    days.push({
      day: prevMonthDays - i,
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      date: null,
    })
  }

  // Current month's days
  for (let i = 1; i <= daysInMonth; i++) {
    const dateStr = formatDate(selectedYear.value, selectedMonth.value, i)
    const isSelected = inputValue.value === dateStr
    days.push({
      day: i,
      isCurrentMonth: true,
      isToday: i === currentDateStr,
      isSelected,
      date: dateStr,
    })
  }

  // Next month's days (grayed out)
  const remainingDays = 42 - days.length
  for (let i = 1; i <= remainingDays; i++) {
    days.push({
      day: i,
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      date: null,
    })
  }

  return days
})

/**
 * ─────────────────────────────────────────────────────────────────
 * CALENDAR NAVIGATION & SELECTION
 * ─────────────────────────────────────────────────────────────────
 */

/**
 * Navigate to previous month (Bootstrap style)
 */
const previousMonth = () => {
  if (selectedMonth.value === 0) {
    selectedMonth.value = 11
    selectedYear.value--
  } else {
    selectedMonth.value--
  }
}

/**
 * Navigate to next month (Bootstrap style)
 */
const nextMonth = () => {
  if (selectedMonth.value === 11) {
    selectedMonth.value = 0
    selectedYear.value++
  } else {
    selectedMonth.value++
  }
}

/**
 * Update year directly
 */
const updateYear = (year: number) => {
  selectedYear.value = year
}

/**
 * Update month directly
 */
const updateMonth = (month: number) => {
  selectedMonth.value = month
}

/**
 * Year range for dropdown (50 years in past, 50 years in future)
 */
const yearRange = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 101 }, (_, i) => currentYear - 50 + i)
})

/**
 * ─────────────────────────────────────────────────────────────────
 * DATE SELECTION & INPUT HANDLING
 * ─────────────────────────────────────────────────────────────────
 */

/**
 * Select a date from the calendar
 */
const selectDate = (dateStr: string) => {
  inputValue.value = dateStr
  emit('update:modelValue', dateStr)
  isOpen.value = false
}

/**
 * Handle manual date input
 */
const handleInputChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  inputValue.value = target.value

  // Try to parse the input
  const parsed = parseDate(target.value)
  if (parsed) {
    const [year, month, day] = parsed.split('-').map(Number)
    selectedYear.value = year
    selectedMonth.value = month - 1
    
    // Only emit if it looks like a complete date
    if (
      target.value.match(/^\d{4}-\d{2}-\d{2}$/) ||
      target.value.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)
    ) {
      emit('update:modelValue', parsed)
    }
  }
}

/**
 * Handle input blur - validate and format
 */
const handleInputBlur = () => {
  if (!inputValue.value) return

  const parsed = parseDate(inputValue.value)
  if (parsed) {
    inputValue.value = parsed
    emit('update:modelValue', parsed)
  } else {
    // Invalid date - revert to last valid value
    inputValue.value = props.modelValue || props.defaultDate || props.value || ''
  }
}

/**
 * Clear the selected date
 */
const clearDate = () => {
  inputValue.value = ''
  emit('update:modelValue', '')
}

/**
 * Open calendar dropdown
 */
const openCalendar = async () => {
  if (!props.disabled) {
    isOpen.value = true
    await nextTick()
    positionDropdown()
  }
}

/**
 * ─────────────────────────────────────────────────────────────────
 * CLICK-OUTSIDE DETECTION
 * ─────────────────────────────────────────────────────────────────
 */

const handleClickOutside = (e: MouseEvent) => {
  if (
    calendarContainer.value &&
    inputContainer.value &&
    !calendarContainer.value.contains(e.target as Node) &&
    !inputContainer.value.contains(e.target as Node)
  ) {
    isOpen.value = false
  }
}

const onScrollOrResize = () => {
  if (isOpen.value) positionDropdown()
}

watch(isOpen, (newVal) => {
  if (newVal) {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', onScrollOrResize, true)
    window.addEventListener('resize', onScrollOrResize)
  } else {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', onScrollOrResize, true)
    window.removeEventListener('resize', onScrollOrResize)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', onScrollOrResize, true)
  window.removeEventListener('resize', onScrollOrResize)
})

/**
 * ─────────────────────────────────────────────────────────────────
 * COMPUTED PROPERTIES FOR TEMPLATE
 * ─────────────────────────────────────────────────────────────────
 */

/**
 * Display-formatted month/year header
 */
const headerText = computed(() => `${monthNames[selectedMonth.value]} ${selectedYear.value}`)

/**
 * Check if month dropdown should be shown
 */
const showMonthSelector = computed(() => props.month === true)

/**
 * Check if year dropdown should be shown
 */
const showYearSelector = computed(() => props.year === true)

/**
 * Check if default Bootstrap header should be shown
 */
const showBootstrapHeader = computed(() => !showMonthSelector.value && !showYearSelector.value)
</script>

<template>
  <div class="relative w-full">
    <!-- Input Field Container -->
    <div ref="inputContainer" class="relative">
      <input
        :value="inputValue"
        :placeholder="placeholder"
        :disabled="disabled"
        type="text"
        @change="handleInputChange"
        @input="handleInputChange"
        @blur="handleInputBlur"
        @click="openCalendar"
        class="w-full h-9 px-3 pr-9 py-2 text-sm border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-0 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      />
      <!-- Calendar Icon (Decorative) -->
      <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-muted-foreground">
        <Calendar class="w-4 h-4" />
      </div>
      <!-- Clear Button -->
      <button
        v-if="inputValue && !disabled"
        @click="clearDate"
        class="absolute right-7 top-1/2 -translate-y-1/2 p-1 hover:bg-muted rounded transition-colors"
        type="button"
        title="Clear date"
      >
        <X class="w-3 h-3 text-muted-foreground" />
      </button>
    </div>

    <!-- Calendar Dropdown (Teleported to body to escape overflow clipping) -->
    <Teleport to="body">
    <div
      v-if="isOpen"
      ref="calendarContainer"
      :style="dropdownStyle"
      class="bg-popover border border-border rounded-lg shadow-lg p-4"
    >
      <!-- ═══════════════════════════════════════════════════════════
           HEADER SECTION: Month/Year Navigation
           Three modes: Bootstrap (default), Month selector, Year selector
           ═══════════════════════════════════════════════════════════ -->

      <!-- Mode 1: Bootstrap-Style Header (Default: both props false) -->
      <div v-if="showBootstrapHeader" class="mb-4">
        <div class="flex items-center justify-between">
          <button
            @click="previousMonth"
            class="p-1.5 hover:bg-muted rounded transition-colors"
            type="button"
            title="Previous month"
          >
            <ChevronLeft class="w-4 h-4 text-muted-foreground" />
          </button>
          <h3 class="text-sm font-semibold text-foreground min-w-fit px-2">
            {{ headerText }}
          </h3>
          <button
            @click="nextMonth"
            class="p-1.5 hover:bg-muted rounded transition-colors"
            type="button"
            title="Next month"
          >
            <ChevronRight class="w-4 h-4 text-muted-foreground" />
          </button>
        </div>
      </div>

      <!-- Mode 2: Custom Month/Year Selectors -->
      <div v-else class="space-y-3 mb-4">
        <!-- Month Dropdown (when month=true) -->
        <div v-if="showMonthSelector">
          <label class="block text-xs font-semibold text-muted-foreground mb-1.5">
            Month
          </label>
          <select
            :value="selectedMonth"
            @change="(e) => updateMonth(parseInt((e.target as HTMLSelectElement).value))"
            class="w-full px-3 py-2 text-sm border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option v-for="(month, index) in monthNames" :key="index" :value="index">
              {{ month }}
            </option>
          </select>
        </div>

        <!-- Year Dropdown (when year=true) -->
        <div v-if="showYearSelector">
          <label class="block text-xs font-semibold text-muted-foreground mb-1.5">
            Year
          </label>
          <select
            :value="selectedYear"
            @change="(e) => updateYear(parseInt((e.target as HTMLSelectElement).value))"
            class="w-full px-3 py-2 text-sm border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option v-for="year in yearRange" :key="year" :value="year">
              {{ year }}
            </option>
          </select>
        </div>

        <!-- Display current month/year when selectors are visible -->
        <div v-if="showMonthSelector || showYearSelector" class="pt-2 pb-1 border-b border-border">
          <p class="text-center text-xs font-medium text-muted-foreground">
            {{ headerText }}
          </p>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           CALENDAR GRID
           ═══════════════════════════════════════════════════════════ -->

      <!-- Day Headers (Sun-Sat) -->
      <div class="grid grid-cols-7 gap-1 mb-2">
        <div
          v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']"
          :key="day"
          class="text-center text-xs font-semibold text-muted-foreground py-1"
        >
          {{ day }}
        </div>
      </div>

      <!-- Day Cells -->
      <div class="grid grid-cols-7 gap-1">
        <button
          v-for="(dayObj, index) in calendarGrid"
          :key="index"
          @click="dayObj.date && selectDate(dayObj.date)"
          :disabled="!dayObj.isCurrentMonth"
          :class="[
            'aspect-square rounded text-xs font-medium transition-colors flex items-center justify-center',
            dayObj.isCurrentMonth
              ? dayObj.isSelected
                ? 'bg-primary text-primary-foreground font-bold cursor-pointer'
                : dayObj.isToday
                ? 'bg-accent text-accent-foreground border border-primary cursor-pointer'
                : 'hover:bg-muted text-foreground cursor-pointer'
              : 'text-muted-foreground/40 cursor-default',
          ]"
          type="button"
          :tabindex="dayObj.isCurrentMonth ? 0 : -1"
        >
          {{ dayObj.day }}
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════════════
           FOOTER
           ═══════════════════════════════════════════════════════════ -->

      <div class="mt-3 pt-3 border-t border-border text-xs text-muted-foreground">
        <p v-if="inputValue" class="text-center font-medium text-foreground">
          Selected: {{ inputValue }}
        </p>
        <p v-else class="text-center">Select a date</p>
      </div>
    </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* Optional: Add smooth transitions for dropdown opening/closing */
@media (prefers-reduced-motion: no-preference) {
  div[ref='calendarContainer'] {
    animation: slideDown 0.15s ease-out;
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>