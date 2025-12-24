<script setup lang="ts">
import { Check, ChevronsUpDown, Loader2 } from "lucide-vue-next"
import { computed, ref } from "vue"
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
const query = ref("");
const props = defineProps<{
  options: Option[]
  modelValue?: Option | null
  placeholder?: string
  required?: boolean
  errorMessage?: string
  showError?: boolean
  loading?: boolean
  disabled?: boolean
  id?: string
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

// Handle blur event
const handleBlur = () => {
  emit('blur')
}
</script>

<template>
  <div class="w-full">
    <Combobox 
      class="w-full" 
      v-model="selected" 
      by="value"
      :disabled="disabled"
    >
      <ComboboxAnchor as-child>
        <ComboboxTrigger as-child>
          <Button 
            variant="outline" 
            :disabled="disabled || loading"
            :class="[
              'justify-between w-full cursor-pointer',
              hasError ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : '',
              disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
            ]"
            @blur="handleBlur"
          >
            <span :class="{ 'text-muted-foreground': !selected }">
              {{ selected?.label ?? props.placeholder ?? 'Select an option' }}
            </span>
            <div class="ml-2 flex items-center">
              <Loader2 
                v-if="loading" 
                class="h-4 w-4 animate-spin opacity-50" 
              />
              <ChevronsUpDown 
                v-else 
                class="h-4 w-4 shrink-0 opacity-50" 
              />
            </div>
          </Button>
        </ComboboxTrigger>
      </ComboboxAnchor>

      <ComboboxList v-if="!disabled">
        <div class="relative w-full">
          <div class="flex items-center px-3 border rounded-md bg-background">
            <ComboboxInput
                v-model="query"
                class="w-full p-2 focus-visible:ring-0 focus-visible:outline-none border-0 bg-transparent"
                placeholder="Search..."
              />
          </div>
        </div>

        <ComboboxEmpty>
          <div class="py-4 text-center text-sm text-muted-foreground">
            {{ loading ? 'Loading...' : 'No options found.' }}
          </div>
        </ComboboxEmpty>

        <ComboboxGroup class="w-full" v-if="!loading">
          <ComboboxItem
            v-for="option in props.options"
            :key="option.value"
            :value="option"
            class="px-4 py-2 hover:bg-accent hover:text-accent-foreground cursor-pointer transition-colors"
          >
            {{ option.label }}
            <ComboboxItemIndicator>
              <Check :class="cn('ml-auto h-4 w-4')" />
            </ComboboxItemIndicator>
          </ComboboxItem>
        </ComboboxGroup>

        <!-- Loading state in dropdown -->
        <div v-if="loading" class="flex items-center justify-center py-4">
          <Loader2 class="h-4 w-4 animate-spin mr-2" />
          <span class="text-sm text-muted-foreground">Loading options...</span>
        </div>
      </ComboboxList>
    </Combobox>
    
    <!-- Error message -->
    <div v-if="hasError || errorText" class="mt-1 text-sm text-red-600 flex items-center gap-1">
      <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
      </svg>
      {{ errorText }}
    </div>
  </div>
</template>

<style>
/* Global styles to override Reka UI dropdown width */
[data-slot="combobox-list"] {
  width: var(--reka-popper-anchor-width) !important;
  min-width: var(--reka-popper-anchor-width) !important;
}

/* More specific targeting */
[data-reka-popper-content-wrapper] [data-slot="combobox-list"] {
  width: var(--reka-popper-anchor-width) !important;
  min-width: var(--reka-popper-anchor-width) !important;
}

/* Target the exact class structure from your HTML */
.z-50.rounded-md.border.bg-popover[data-slot="combobox-list"] {
  width: var(--reka-popper-anchor-width) !important;
  min-width: var(--reka-popper-anchor-width) !important;
}

/* Ensure proper cursor for interactive elements */
button[role="combobox"] {
  cursor: pointer !important;
}

button[role="combobox"]:not(:disabled):hover {
  cursor: pointer !important;
}

button[role="combobox"]:disabled {
  cursor: not-allowed !important;
}
</style>