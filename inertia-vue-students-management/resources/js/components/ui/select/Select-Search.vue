<script setup lang="ts">
import { Check, ChevronsUpDown, Search } from "lucide-vue-next"
import { computed } from "vue"
import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxList,
  ComboboxTrigger,
} from "@/components/ui/combobox"

interface Option {
  value: string
  label: string
}

const props = defineProps<{
  options: Option[]
  modelValue?: Option | null
  placeholder?: string
}>()

const emit = defineEmits<{
  (e: "update:modelValue", value: Option | null): void
}>()

const selected = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val ?? null),
})
</script>

<template >
  <Combobox class="w-full " v-model="selected" by="value">
    <ComboboxAnchor as-child>
      <ComboboxTrigger as-child>
        <Button variant="outline" class="justify-between w-full">
          <span :class="{ 'text-muted-foreground': !selected }">
            {{ selected?.label ?? props.placeholder ?? 'Select an option' }}
          </span>
          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </ComboboxTrigger>
    </ComboboxAnchor>

    <ComboboxList class="w-[--reka-combobox-trigger-width]">
      <div class="relative w-full items-center">
        <div class="flex items-center px-3 border rounded-md bg-background">
          <!-- <Search class="h-4 w-4 text-muted-foreground" /> -->
          <ComboboxInput
            class="w-full p-2 focus-visible:ring-0 focus-visible:outline-none border-0 bg-transparent"
            />
        </div>
      </div>

      <ComboboxEmpty>No option found.</ComboboxEmpty>

      <ComboboxGroup class="w-full">
        <ComboboxItem
          v-for="option in props.options"
          :key="option.value"
          :value="option"
          class="px-4 py-2 hover:bg-accent hover:text-accent-foreground cursor-pointer"
        >
          {{ option.label }}
          <ComboboxItemIndicator>
            <Check :class="cn('ml-auto h-4 w-4')" />
          </ComboboxItemIndicator>
        </ComboboxItem>
      </ComboboxGroup>
    </ComboboxList>
  </Combobox>
</template>