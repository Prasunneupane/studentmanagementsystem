<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { Clock } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

interface Props {
  modelValue?: string | null   // "HH:mm" 24h format
  placeholder?: string
  error?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  placeholder: 'Pick time',
  error: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const open = ref(false)

// Parse modelValue into hour12, minute, period
const parseTime = (val: string | null) => {
  if (!val) return { hour24: 0, minute: 0 }
  const [h, m] = val.split(':').map(Number)
  return { hour24: h || 0, minute: m || 0 }
}

const to12 = (h24: number) => {
  const period = h24 >= 12 ? 'PM' : 'AM'
  let h12 = h24 % 12
  if (h12 === 0) h12 = 12
  return { h12, period }
}

const to24 = (h12: number, period: string) => {
  if (period === 'AM') return h12 === 12 ? 0 : h12
  return h12 === 12 ? 12 : h12 + 12
}

const selectedHour = ref(12)
const selectedMinute = ref(0)
const selectedPeriod = ref<'AM' | 'PM'>('AM')

// Sync from modelValue
watch(() => props.modelValue, (val) => {
  const { hour24, minute } = parseTime(val)
  const { h12, period } = to12(hour24)
  selectedHour.value = h12
  selectedMinute.value = minute
  selectedPeriod.value = period as 'AM' | 'PM'
}, { immediate: true })

const hours = Array.from({ length: 12 }, (_, i) => i + 1)
const minutes = Array.from({ length: 12 }, (_, i) => i * 5) // 0,5,10,...,55

const emitTime = () => {
  const h24 = to24(selectedHour.value, selectedPeriod.value)
  const hStr = h24.toString().padStart(2, '0')
  const mStr = selectedMinute.value.toString().padStart(2, '0')
  emit('update:modelValue', `${hStr}:${mStr}`)
}

const selectHour = (h: number) => {
  selectedHour.value = h
  emitTime()
}
const selectMinute = (m: number) => {
  selectedMinute.value = m
  emitTime()
}
const selectPeriod = (p: 'AM' | 'PM') => {
  selectedPeriod.value = p
  emitTime()
}

const displayTime = computed(() => {
  if (!props.modelValue) return null
  const hStr = selectedHour.value.toString()
  const mStr = selectedMinute.value.toString().padStart(2, '0')
  return `${hStr}:${mStr} ${selectedPeriod.value}`
})

// Scroll to selected items when popover opens
const hourCol = ref<HTMLElement>()
const minuteCol = ref<HTMLElement>()

watch(open, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    // Scroll hour column
    const hEl = hourCol.value?.querySelector('[data-selected="true"]') as HTMLElement
    if (hEl) hEl.scrollIntoView({ block: 'center', behavior: 'instant' })
    // Scroll minute column
    const mEl = minuteCol.value?.querySelector('[data-selected="true"]') as HTMLElement
    if (mEl) mEl.scrollIntoView({ block: 'center', behavior: 'instant' })
  }
})
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full h-8 justify-start text-left font-normal px-2 text-xs',
          !modelValue && 'text-muted-foreground',
          error && 'border-red-500 focus-visible:ring-red-500'
        )"
      >
        <Clock class="mr-1.5 h-3.5 w-3.5 shrink-0 text-muted-foreground" />
        <span class="flex-1 truncate">
          {{ displayTime ?? placeholder }}
        </span>
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0" align="start">
      <div class="flex divide-x border-b-0">
        <!-- Hours -->
        <div ref="hourCol" class="flex flex-col h-52 overflow-y-auto py-1 px-1 scrollbar-thin">
          <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider px-2 pb-1">Hr</span>
          <button
            v-for="h in hours"
            :key="h"
            :data-selected="h === selectedHour"
            class="px-3 py-1.5 text-sm rounded-md transition-colors text-center min-w-[40px]"
            :class="h === selectedHour
              ? 'bg-primary text-primary-foreground font-semibold'
              : 'hover:bg-muted text-foreground'"
            @click="selectHour(h)"
          >
            {{ h }}
          </button>
        </div>

        <!-- Minutes -->
        <div ref="minuteCol" class="flex flex-col h-52 overflow-y-auto py-1 px-1 scrollbar-thin">
          <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider px-2 pb-1">Min</span>
          <button
            v-for="m in minutes"
            :key="m"
            :data-selected="m === selectedMinute"
            class="px-3 py-1.5 text-sm rounded-md transition-colors text-center min-w-[40px]"
            :class="m === selectedMinute
              ? 'bg-primary text-primary-foreground font-semibold'
              : 'hover:bg-muted text-foreground'"
            @click="selectMinute(m)"
          >
            {{ m.toString().padStart(2, '0') }}
          </button>
        </div>

        <!-- AM/PM -->
        <div class="flex flex-col py-1 px-1 justify-start">
          <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider px-2 pb-1">&nbsp;</span>
          <button
            v-for="p in (['AM', 'PM'] as const)"
            :key="p"
            class="px-3 py-2 text-sm rounded-md transition-colors font-medium min-w-[48px]"
            :class="p === selectedPeriod
              ? 'bg-primary text-primary-foreground font-semibold'
              : 'hover:bg-muted text-foreground'"
            @click="selectPeriod(p)"
          >
            {{ p }}
          </button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>
