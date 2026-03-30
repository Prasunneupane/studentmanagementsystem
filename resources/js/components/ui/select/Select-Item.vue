<script setup lang="ts">
import {
  SelectItem,
  SelectItemText,
  SelectItemIndicator,
} from "radix-vue"
import { Check } from "lucide-vue-next"

defineProps<{ value: string }>()
</script>

<template>
  <SelectItem
    :value="value"
    class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm text-gray-700 outline-none hover:bg-indigo-100 focus:bg-indigo-100"
  >
    <SelectItemText>
      <slot />
    </SelectItemText>
    <SelectItemIndicator class="absolute right-2">
      <Check class="h-4 w-4 text-indigo-600" />
    </SelectItemIndicator>
  </SelectItem>
</template>
