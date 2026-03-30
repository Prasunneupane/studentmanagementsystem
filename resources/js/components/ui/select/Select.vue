<script setup lang="ts">
import {
  SelectRoot,
  SelectTrigger,
  SelectValue,
  SelectIcon,
  SelectContent,
  SelectViewport,
} from "radix-vue"
import { ChevronDown } from "lucide-vue-next"

// 👇 This line declares that <Select v-model="country"> works
const modelValue = defineModel<string | undefined>()
</script>

<template>
  <SelectRoot v-model="modelValue">
    <SelectTrigger
      class="inline-flex w-full items-center justify-between rounded-md border border-gray-300 bg-white px-3 py-2 text-sm ring-offset-background placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
    >
      <SelectValue placeholder="Select option..." />
      <SelectIcon>
        <ChevronDown class="h-4 w-4 opacity-50" />
      </SelectIcon>
    </SelectTrigger>

    <!-- Dropdown -->
    <SelectContent class="z-50 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg animate-in fade-in-80">
    <SelectViewport class="p-1 space-y-1">
        <slot />
    </SelectViewport>
    </SelectContent>
  </SelectRoot>
</template>
