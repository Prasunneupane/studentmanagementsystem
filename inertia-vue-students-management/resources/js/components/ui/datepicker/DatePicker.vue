<script setup lang="ts">
import { ref, computed } from "vue"
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

// ✅ Props
defineProps<{
  monthYearSelector?: boolean
}>()

// ✅ State
const value = ref<DateValue>()
const open = ref(false)
const placeholder = ref<DateValue>(today(getLocalTimeZone()))
const calendarKey = ref(0) // Add a key for re-rendering

// ✅ Format date as YYYY-MM-DD
const formattedDate = computed(() => {
  if (!value.value) return "Pick a date"
  const d = value.value.toDate(getLocalTimeZone())
  return d.toISOString().split("T")[0]
})

// ✅ Month & Year arrays
const months = Array.from({ length: 12 }, (_, i) => ({
  label: new Date(2000, i).toLocaleString("en", { month: "long" }),
  value: (i + 1).toString(),
}))
const years = Array.from({ length: 50 }, (_, i) => (2000 + i).toString())

// ✅ Handle date selection → close popover
const onSelectDate = (d: DateValue | undefined) => {
  if (!d) return
  value.value = d
  open.value = false
}

// ✅ Handle month/year change
const handleMonthYearChange = (part: 'month' | 'year', v: string | null) => {
  if (!v || !placeholder.value) return
  const newValue = Number(v)
  if (newValue === placeholder.value[part]) return
  placeholder.value = placeholder.value.set({ [part]: newValue })
  calendarKey.value++ // Increment the key to force re-render
}
</script>

<template>
  <Popover v-model:open="open" class="w-full">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !value && 'text-muted-foreground',
        )"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ formattedDate }}
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-2">
      <!-- ✅ Month + Year selector (optional) -->
      <div v-if="monthYearSelector" class="flex gap-2 mb-2">
        <Select
          :default-value="placeholder?.month?.toString() ?? ''"
          @update:model-value="(v) => handleMonthYearChange('month', v)"
        >
          <SelectTrigger class="w-[120px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="m in months"
              :key="m.value"
              :value="m.value"
            >
              {{ m.label }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select
          :default-value="placeholder?.year?.toString() ?? ''"
          @update:model-value="(v) => handleMonthYearChange('year', v)"
        >
          <SelectTrigger class="w-[100px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="y in years"
              :key="y"
              :value="y"
            >
              {{ y }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- ✅ Calendar -->
      <Calendar
        :key="calendarKey"
        class="w-full"
        v-model="value"
        :placeholder="placeholder as DateValue"
        initial-focus
        @update:model-value="onSelectDate"
      />
    </PopoverContent>
  </Popover>
</template>
