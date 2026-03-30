<script setup lang="ts">
import { ref, computed, watch } from "vue"
import type { DateValue } from "@internationalized/date"
import { CalendarDate, today, getLocalTimeZone } from "@internationalized/date"
import { Calendar as CalendarIcon } from "lucide-vue-next"
import { cn } from "@/lib/utils"

import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/shadcnselect"

interface Props {
  monthYearSelector?: boolean
  modelValue?: Date | string | null
  placeholder?: string
  error?: string
}

const props = withDefaults(defineProps<Props>(), {
  monthYearSelector: false,
  modelValue: null,
  placeholder: "Pick a date",
  error: "",
})

const emit = defineEmits<{
  'update:modelValue': [value: Date | null]
}>()

const value = ref<DateValue>()
const open = ref(false)
const placeholderDate = ref<DateValue>(today(getLocalTimeZone()))
const calendarKey = ref(0)

const dateToDateValue = (date: Date | string | null): DateValue | undefined => {
  if (!date) return undefined
  const d = typeof date === "string" ? new Date(date) : date
  if (isNaN(d.getTime())) return undefined
  return new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate())
}

const dateValueToDate = (dateValue: DateValue): Date => {
  return new Date(dateValue.year, dateValue.month - 1, dateValue.day)
}

watch(
  () => props.modelValue,
  (newDate) => {
    if (newDate) {
      value.value = dateToDateValue(newDate)
      placeholderDate.value = dateToDateValue(newDate) || today(getLocalTimeZone())
    } else {
      value.value = undefined
    }
  },
  { immediate: true }
)

const formattedDate = computed(() => {
  if (!value.value) return null
  const year = value.value.year.toString().padStart(4, "0")
  const month = value.value.month.toString().padStart(2, "0")
  const day = value.value.day.toString().padStart(2, "0")
  return `${year}-${month}-${day}`
})

const months = Array.from({ length: 12 }, (_, i) => ({
  label: new Date(2000, i).toLocaleString("en", { month: "long" }),
  value: (i + 1).toString(),
}))
const years = Array.from({ length: 50 }, (_, i) => (2000 + i).toString())

const onSelectDate = (d: DateValue | undefined) => {
  if (!d) return
  value.value = d
  open.value = false
  emit("update:modelValue", dateValueToDate(d))
}

const handleMonthYearChange = (part: "month" | "year", v: string | null) => {
  if (!v || !placeholderDate.value) return
  const newValue = Number(v)
  if (newValue === placeholderDate.value[part]) return
  placeholderDate.value = placeholderDate.value.set({ [part]: newValue })
  calendarKey.value++
}
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full h-10 justify-start text-left font-normal px-3',
          !value && 'text-muted-foreground',
          error && 'border-red-500 focus-visible:ring-red-500'
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4 shrink-0" />
        <span class="flex-1 truncate">
          {{ formattedDate ?? placeholder }}
        </span>
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-2" align="start">
      <!-- Month + Year selector (optional) -->
      <div v-if="monthYearSelector" class="flex gap-2 mb-2">
        <Select
          :default-value="placeholderDate?.month?.toString() ?? ''"
          @update:model-value="(v) => handleMonthYearChange('month', v?.toString() ?? null)"
        >
          <SelectTrigger class="w-[120px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="m in months" :key="m.value" :value="m.value">
              {{ m.label }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select
          :default-value="placeholderDate?.year?.toString() ?? ''"
          @update:model-value="(v) => handleMonthYearChange('year', v?.toString() ?? null)"
        >
          <SelectTrigger class="w-[100px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="y in years" :key="y" :value="y">
              {{ y }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Calendar -->
      <Calendar
        :key="calendarKey"
        v-model="value"
        :placeholder="placeholderDate as DateValue"
        initial-focus
        @update:model-value="onSelectDate"
      />
    </PopoverContent>
  </Popover>
</template>