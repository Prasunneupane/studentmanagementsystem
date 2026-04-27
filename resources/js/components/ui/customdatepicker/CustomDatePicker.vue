<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { ChevronLeft, ChevronRight, Calendar, X } from 'lucide-vue-next'

interface Props {
  modelValue?: string
  placeholder?: string
  disabled?: boolean
  initialYear?: number
  initialMonth?: number
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Pick a date',
  disabled: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

// ─── State ───────────────────────────────────────────────────────
const isOpen = ref(false)
const inputValue = ref(props.modelValue || '')
const selectedYear = ref(props.initialYear || new Date().getFullYear())
const selectedMonth = ref(props.initialMonth || new Date().getMonth())
const calendarContainer = ref<HTMLDivElement | null>(null)
const inputContainer = ref<HTMLDivElement | null>(null)

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      inputValue.value = newVal
      const date = new Date(newVal)
      if (!isNaN(date.getTime())) {
        selectedYear.value = date.getFullYear()
        selectedMonth.value = date.getMonth()
      }
    }
  }
)

// ─── Date Helpers ────────────────────────────────────────────────
const getDaysInMonth = (year: number, month: number): number => {
  return new Date(year, month + 1, 0).getDate()
}

const getFirstDayOfMonth = (year: number, month: number): number => {
  return new Date(year, month, 1).getDay()
}

const formatDateToString = (year: number, month: number, day: number): string => {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${year}-${pad(month + 1)}-${pad(day)}`
}

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
]

// ─── Calendar Grid ───────────────────────────────────────────────
interface CalendarDay {
  day: number | null
  isCurrentMonth: boolean
  isToday: boolean
  isSelected: boolean
  date: string | null
}

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

  // Previous month's days
  for (let i = firstDay - 1; i >= 0; i--) {
    days.push({
      day: prevMonthDays - i,
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      date: null
    })
  }

  // Current month's days
  for (let i = 1; i <= daysInMonth; i++) {
    const dateStr = formatDateToString(selectedYear.value, selectedMonth.value, i)
    const isSelected = inputValue.value === dateStr
    days.push({
      day: i,
      isCurrentMonth: true,
      isToday: i === currentDateStr,
      isSelected,
      date: dateStr
    })
  }

  // Next month's days
  const remainingDays = 42 - days.length
  for (let i = 1; i <= remainingDays; i++) {
    days.push({
      day: i,
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      date: null
    })
  }

  return days
})

// ─── Actions ─────────────────────────────────────────────────────
const selectDate = (dateStr: string) => {
  inputValue.value = dateStr
  emit('update:modelValue', dateStr)
  isOpen.value = false
}

const handleInputChange = (e: Event) => {
  const target = e.target as HTMLInputElement
  inputValue.value = target.value

  // Try to parse and validate the date
  const date = new Date(target.value)
  if (!isNaN(date.getTime())) {
    selectedYear.value = date.getFullYear()
    selectedMonth.value = date.getMonth()
    // Only emit if it's a valid complete date
    if (target.value.match(/^\d{4}-\d{2}-\d{2}$/)) {
      emit('update:modelValue', target.value)
    }
  }
}

const handleInputBlur = () => {
  // Validate and format on blur
  if (inputValue.value) {
    const date = new Date(inputValue.value)
    if (!isNaN(date.getTime())) {
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      inputValue.value = `${year}-${month}-${day}`
      emit('update:modelValue', inputValue.value)
    } else {
      // Invalid date - revert
      inputValue.value = props.modelValue || ''
    }
  }
}

const previousMonth = () => {
  if (selectedMonth.value === 0) {
    selectedMonth.value = 11
    selectedYear.value--
  } else {
    selectedMonth.value--
  }
}

const nextMonth = () => {
  if (selectedMonth.value === 11) {
    selectedMonth.value = 0
    selectedYear.value++
  } else {
    selectedMonth.value++
  }
}

const changeYear = (increment: number) => {
  selectedYear.value += increment
}

const changeMonth = (monthIndex: number) => {
  selectedMonth.value = monthIndex
}

const clearDate = () => {
  inputValue.value = ''
  emit('update:modelValue', '')
}

const openCalendar = async () => {
  isOpen.value = true
  await nextTick()
  // Calendar is now open
}

// Close calendar when clicking outside
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

watch(isOpen, (newVal) => {
  if (newVal) {
    document.addEventListener('click', handleClickOutside)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})
</script>

<template>
  <div class="relative w-full">
    <!-- Input Field -->
    <div ref="inputContainer" class="relative">
      <div class="relative">
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
        <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none text-muted-foreground">
          <Calendar class="w-4 h-4" />
        </div>
        <button
          v-if="inputValue"
          @click="clearDate"
          class="absolute right-7 top-1/2 -translate-y-1/2 p-1 hover:bg-muted rounded transition-colors"
          type="button"
        >
          <X class="w-3 h-3 text-muted-foreground" />
        </button>
      </div>
    </div>

    <!-- Calendar Dropdown -->
    <div
      v-if="isOpen"
      ref="calendarContainer"
      class="absolute top-full left-0 z-50 mt-1 bg-popover border border-border rounded-lg shadow-lg p-4 w-80"
    >
      <!-- Month/Year Navigation -->
      <div class="space-y-3 mb-4">
        <!-- Year Selector -->
        <div class="flex items-center justify-between gap-2">
          <button
            @click="changeYear(-1)"
            class="p-1.5 hover:bg-muted rounded transition-colors"
            type="button"
            title="Previous year"
          >
            <ChevronLeft class="w-4 h-4 text-muted-foreground" />
          </button>
          <div class="flex-1 text-center">
            <select
              :value="selectedYear"
              @change="(e) => selectedYear = parseInt((e.target as HTMLSelectElement).value)"
              class="w-full px-2 py-1 text-sm font-semibold border border-input rounded bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option v-for="year in Array.from({ length: 100 }, (_, i) => new Date().getFullYear() - 50 + i)" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>
          <button
            @click="changeYear(1)"
            class="p-1.5 hover:bg-muted rounded transition-colors"
            type="button"
            title="Next year"
          >
            <ChevronRight class="w-4 h-4 text-muted-foreground" />
          </button>
        </div>

        <!-- Month Selector Grid -->
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="(month, index) in monthNames"
            :key="index"
            @click="changeMonth(index)"
            :class="[
              'py-1.5 px-2 rounded text-xs font-medium transition-colors border',
              selectedMonth === index
                ? 'bg-primary text-primary-foreground border-primary'
                : 'bg-muted text-foreground border-transparent hover:bg-muted/80'
            ]"
            type="button"
          >
            {{ month.slice(0, 3) }}
          </button>
        </div>
      </div>

      <!-- Calendar Header -->
      <div class="flex items-center justify-between mb-3">
        <button
          @click="previousMonth"
          class="p-1.5 hover:bg-muted rounded transition-colors"
          type="button"
          title="Previous month"
        >
          <ChevronLeft class="w-4 h-4 text-muted-foreground" />
        </button>
        <h3 class="text-sm font-semibold text-foreground">
          {{ monthNames[selectedMonth] }} {{ selectedYear }}
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

      <!-- Day Headers -->
      <div class="grid grid-cols-7 gap-1 mb-2">
        <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day" class="text-center text-xs font-semibold text-muted-foreground py-1">
          {{ day }}
        </div>
      </div>

      <!-- Calendar Days -->
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
                ? 'bg-primary text-primary-foreground font-bold'
                : dayObj.isToday
                ? 'bg-accent text-accent-foreground border border-primary'
                : 'hover:bg-muted text-foreground'
              : 'text-muted-foreground/40 cursor-default',
            !dayObj.isCurrentMonth && 'cursor-not-allowed hover:bg-transparent'
          ]"
          type="button"
          :tabindex="dayObj.isCurrentMonth ? 0 : -1"
        >
          {{ dayObj.day }}
        </button>
      </div>

      <!-- Footer Info -->
      <div class="mt-3 pt-3 border-t border-border text-xs text-muted-foreground">
        <p v-if="inputValue" class="text-center font-medium text-foreground">
          Selected: {{ inputValue }}
        </p>
        <p v-else class="text-center">Select a date</p>
      </div>
    </div>
  </div>
</template>
