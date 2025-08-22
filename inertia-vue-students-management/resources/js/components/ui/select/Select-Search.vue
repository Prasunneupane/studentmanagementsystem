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
  required?: boolean
  errorMessage?: string
  showError?: boolean
}>()

const emit = defineEmits<{
  (e: "update:modelValue", value: Option | null): void
  (e: "blur"): void
}>()

const selected = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val ?? null),
})

// Validation logic
const hasError = computed(() => {
  return props.required && props.showError && !selected.value
})

const errorText = computed(() => {
  if (hasError.value) {
    return props.errorMessage || 'This field is required'
  }
  return ''
})
</script>

<template>
  <div class="w-full">
    <Combobox class="w-full" v-model="selected" by="value">
      <ComboboxAnchor as-child>
        <ComboboxTrigger as-child>
          <Button 
            variant="outline" 
            :class="[
              'justify-between w-full',
              hasError ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''
            ]"
            @blur="emit('blur')"
          >
            <span :class="{ 'text-muted-foreground': !selected }">
              {{ selected?.label ?? props.placeholder ?? 'Select an option' }}
            </span>
            <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </ComboboxTrigger>
      </ComboboxAnchor>

      <!-- Remove the class and style, let CSS handle it -->
      <ComboboxList>
        <div class="relative w-full items-center">
          <div class="flex items-center px-3 border rounded-md bg-background">
            <ComboboxInput
              class="w-full p-2 focus-visible:ring-0 focus-visible:outline-none border-0 bg-transparent"
              placeholder="Search..."
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
    
    <!-- Error message -->
    <div v-if="hasError" class="mt-1 text-sm text-red-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
      </svg>
      {{ errorText }}
    </div>
  </div>
</template>

<style>
/* Global styles to override Reka UI dropdown width - use :global or remove scoped */
[data-slot="combobox-list"] {
  width: var(--reka-popper-anchor-width) !important;
  min-width: var(--reka-popper-anchor-width) !important;
  max-width: var(--reka-popper-anchor-width) !important;
}

/* More specific targeting */
[data-reka-popper-content-wrapper] [data-slot="combobox-list"] {
  width: var(--reka-popper-anchor-width) !important;
  min-width: var(--reka-popper-anchor-width) !important;
}

/* Override any existing width classes */
[data-slot="combobox-list"].z-50 {
  width: var(--reka-popper-anchor-width) !important;
}

/* Target the exact class structure from your HTML */
.z-50.rounded-md.border.bg-popover[data-slot="combobox-list"] {
  width: var(--reka-popper-anchor-width) !important;
  min-width: var(--reka-popper-anchor-width) !important;
}
</style>