<template>
  <div ref="wrapperRef" class="relative w-full">
    <!-- SELECT BOX -->
    <div
      ref="selectBox"
      class="flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1 cursor-pointer"
      :class="{ 'opacity-50 cursor-not-allowed': disabled }"
      @click="!disabled && toggleDropdown()"
      @keydown.enter.prevent="!disabled && toggleDropdown()"
      @keydown.space.prevent="!disabled && toggleDropdown()"
      @keydown.down.prevent="!disabled && (isOpen ? focusNext() : toggleDropdown())"
      @keydown.up.prevent="!disabled && (isOpen ? focusPrevious() : toggleDropdown())"
      @keydown.escape="closeDropdown"
      tabindex="0"
    >
      <!-- Display selected options -->
      <div class="flex-1 flex items-center gap-1 overflow-hidden">
        <!-- Multi-select tags -->
        <template v-if="multiple && selectedOptions.length">
          <div class="flex flex-wrap gap-1">
            <span
              v-for="option in selectedOptions"
              :key="option.value"
              class="inline-flex items-center gap-1 rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground"
            >
              {{ option.label }}
              <button
                @click.stop="removeOption(option)"
                class="ml-0.5 rounded-sm hover:bg-secondary-foreground/20 focus:outline-none"
                :disabled="disabled"
              >
                <svg width="12" height="12" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11.7816 4.03157C12.0062 3.80702 12.0062 3.44295 11.7816 3.2184C11.5571 2.99385 11.193 2.99385 10.9685 3.2184L7.50005 6.68682L4.03164 3.2184C3.80708 2.99385 3.44301 2.99385 3.21846 3.2184C2.99391 3.44295 2.99391 3.80702 3.21846 4.03157L6.68688 7.49999L3.21846 10.9684C2.99391 11.193 2.99391 11.557 3.21846 11.7816C3.44301 12.0061 3.80708 12.0061 4.03164 11.7816L7.50005 8.31316L10.9685 11.7816C11.193 12.0061 11.5571 12.0061 11.7816 11.7816C12.0062 11.557 12.0062 11.193 11.7816 10.9684L8.31322 7.49999L11.7816 4.03157Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
              </button>
            </span>
          </div>
        </template>

        <!-- Single select display -->
        <span
          v-else-if="!multiple && selectedOption"
          class="truncate text-sm"
        >
          {{ selectedOption.label }}
        </span>

        <!-- Placeholder -->
        <span
          v-else
          class="text-muted-foreground truncate text-sm"
        >
          {{ placeholder }}
        </span>
      </div>

      <!-- Dropdown arrow -->
      <svg
        width="15"
        height="15"
        viewBox="0 0 15 15"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        class="h-4 w-4 opacity-50 shrink-0 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
      >
        <path d="M4.18179 6.18181C4.35753 6.00608 4.64245 6.00608 4.81819 6.18181L7.49999 8.86362L10.1818 6.18181C10.3575 6.00608 10.6424 6.00608 10.8182 6.18181C10.9939 6.35755 10.9939 6.64247 10.8182 6.81821L7.81819 9.81821C7.73379 9.9026 7.61934 9.95001 7.49999 9.95001C7.38064 9.95001 7.26618 9.9026 7.18179 9.81821L4.18179 6.81821C4.00605 6.64247 4.00605 6.35755 4.18179 6.18181Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path>
      </svg>
    </div>

    <!-- DROPDOWN -->
    <transition name="fade">
      <div
        v-if="isOpen"
        ref="dropdownRef"
        class="absolute w-full bg-popover text-popover-foreground border rounded-md shadow-md z-50 overflow-hidden"
        :style="{ 
          top: dropdownPosition === 'down' ? 'calc(100% + 4px)' : 'auto', 
          bottom: dropdownPosition === 'up' ? 'calc(100% + 4px)' : 'auto',
        }"
      >
        <!-- SEARCH -->
        <div class="p-2 border-b">
          <div class="flex items-center border rounded-md px-3 bg-background">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-50">
              <path d="M10 6.5C10 8.433 8.433 10 6.5 10C4.567 10 3 8.433 3 6.5C3 4.567 4.567 3 6.5 3C8.433 3 10 4.567 10 6.5ZM9.30884 10.0159C8.53901 10.6318 7.56251 11 6.5 11C4.01472 11 2 8.98528 2 6.5C2 4.01472 4.01472 2 6.5 2C8.98528 2 11 4.01472 11 6.5C11 7.56251 10.6318 8.53901 10.0159 9.30884L12.8536 12.1464C13.0488 12.3417 13.0488 12.6583 12.8536 12.8536C12.6583 13.0488 12.3417 13.0488 12.1464 12.8536L9.30884 10.0159Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path>
            </svg>
            <input
              ref="searchInput"
              v-model="search"
              class="flex h-9 w-full bg-transparent py-2 px-2 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
              :placeholder="searchPlaceholder"
              @click.stop
              @keydown.down.prevent="focusNext"
              @keydown.up.prevent="focusPrevious"
              @keydown.enter.prevent="selectFocusedOption"
              @keydown.escape="closeDropdown"
              @keydown.tab="handleTab"
            />
          </div>
        </div>

        <!-- SELECT ALL (for multi-select) -->
        <div
          v-if="multiple && filteredOptions.length > 1"
          class="px-2 py-1.5 cursor-pointer hover:bg-accent hover:text-accent-foreground text-sm font-medium flex items-center gap-2"
          @click.stop="toggleSelectAll"
        >
          <div class="flex h-4 w-4 items-center justify-center rounded-sm border border-primary">
            <svg
              v-if="isAllSelected"
              width="15"
              height="15"
              viewBox="0 0 15 15"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              class="h-3 w-3"
            >
              <path d="M11.4669 3.72684C11.7558 3.91574 11.8369 4.30308 11.648 4.59198L7.39799 11.092C7.29783 11.2452 7.13556 11.3467 6.95402 11.3699C6.77247 11.3931 6.58989 11.3355 6.45446 11.2124L3.70446 8.71241C3.44905 8.48022 3.43023 8.08494 3.66242 7.82953C3.89461 7.57412 4.28989 7.55529 4.5453 7.78749L6.75292 9.79441L10.6018 3.90792C10.7907 3.61902 11.178 3.53795 11.4669 3.72684Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path>
            </svg>
            <svg
              v-else-if="isSomeSelected"
              width="15"
              height="15"
              viewBox="0 0 15 15"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              class="h-3 w-3"
            >
              <path d="M2.25 7.5C2.25 7.22386 2.47386 7 2.75 7H12.25C12.5261 7 12.75 7.22386 12.75 7.5C12.75 7.77614 12.5261 8 12.25 8H2.75C2.47386 8 2.25 7.77614 2.25 7.5Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path>
            </svg>
          </div>
          <span>{{ isAllSelected ? 'Deselect All' : 'Select All' }}</span>
        </div>

        <!-- OPTIONS LIST -->
        <div ref="optionsContainer" class="max-h-[300px] overflow-y-auto p-1">
          <div v-if="filteredOptions.length">
            <div
              v-for="(option, index) in filteredOptions"
              :key="option.value"
              :ref="el => setOptionRef(el, index)"
              class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
              :class="{
                'bg-accent text-accent-foreground': focusedIndex === index,
              }"
              @click.stop="selectOption(option)"
              @mouseenter="focusedIndex = index"
            >
              <!-- Checkbox indicator for multi-select -->
              <span v-if="multiple" class="mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary">
                <svg
                  v-if="isSelected(option)"
                  width="15"
                  height="15"
                  viewBox="0 0 15 15"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3 w-3"
                >
                  <path d="M11.4669 3.72684C11.7558 3.91574 11.8369 4.30308 11.648 4.59198L7.39799 11.092C7.29783 11.2452 7.13556 11.3467 6.95402 11.3699C6.77247 11.3931 6.58989 11.3355 6.45446 11.2124L3.70446 8.71241C3.44905 8.48022 3.43023 8.08494 3.66242 7.82953C3.89461 7.57412 4.28989 7.55529 4.5453 7.78749L6.75292 9.79441L10.6018 3.90792C10.7907 3.61902 11.178 3.53795 11.4669 3.72684Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
              </span>

              <!-- Option label -->
              <span class="flex-1">{{ option.label }}</span>

              <!-- Check icon for single select -->
              <svg
                v-if="!multiple && isSelected(option)"
                width="15"
                height="15"
                viewBox="0 0 15 15"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="ml-auto h-4 w-4"
              >
                <path d="M11.4669 3.72684C11.7558 3.91574 11.8369 4.30308 11.648 4.59198L7.39799 11.092C7.29783 11.2452 7.13556 11.3467 6.95402 11.3699C6.77247 11.3931 6.58989 11.3355 6.45446 11.2124L3.70446 8.71241C3.44905 8.48022 3.43023 8.08494 3.66242 7.82953C3.89461 7.57412 4.28989 7.55529 4.5453 7.78749L6.75292 9.79441L10.6018 3.90792C10.7907 3.61902 11.178 3.53795 11.4669 3.72684Z" fill="currentColor" fill-rule="evenodd" clip-rule="evenodd"></path>
              </svg>
            </div>
          </div>

          <div v-else class="py-6 text-center text-sm text-muted-foreground">
            No results found.
          </div>
        </div>

        <!-- FOOTER (for multi-select) -->
        <div
          v-if="multiple && selectedOptions.length > 0"
          class="border-t px-2 py-1.5 text-xs text-muted-foreground flex justify-between items-center"
        >
          <span>{{ selectedOptions.length }} selected</span>
          <button
            @click.stop="clearAll"
            class="text-primary hover:underline font-medium"
          >
            Clear
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";

const props = defineProps({
  modelValue: [String, Number, Object, Array],
  options: {
    type: Array,
    required: true,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: "Select…",
  },
  searchPlaceholder: {
    type: String,
    default: "Search...",
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  closeOnSelect: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const search = ref("");
const dropdownPosition = ref("down");
const focusedIndex = ref(-1);

const wrapperRef = ref(null);
const dropdownRef = ref(null);
const searchInput = ref(null);
const selectBox = ref(null);
const optionsContainer = ref(null);
const optionRefs = ref([]);

// Set option ref
const setOptionRef = (el, index) => {
  if (el) {
    optionRefs.value[index] = el;
  }
};

// Get selected values as array
const selectedValues = computed(() => {
  if (props.multiple) {
    return Array.isArray(props.modelValue) ? props.modelValue : [];
  }
  return props.modelValue ? [props.modelValue] : [];
});

// Find selected option(s)
const selectedOptions = computed(() => {
  return props.options.filter(option =>
    selectedValues.value.some(val => String(val) === String(option.value))
  );
});

// Single selected option (for non-multiple mode)
const selectedOption = computed(() => {
  if (props.multiple) return null;
  return selectedOptions.value[0] || null;
});

// Check if option is selected
const isSelected = (option) => {
  return selectedValues.value.some(val => String(val) === String(option.value));
};

// Filter options based on search
const filteredOptions = computed(() =>
  props.options.filter(option =>
    option.label?.toString().toLowerCase().includes(search.value.toLowerCase())
  )
);

// Check if all options are selected
const isAllSelected = computed(() => {
  if (!props.multiple || filteredOptions.value.length === 0) return false;
  return filteredOptions.value.every(option => isSelected(option));
});

// Check if some (but not all) options are selected
const isSomeSelected = computed(() => {
  if (!props.multiple) return false;
  const selected = filteredOptions.value.filter(option => isSelected(option));
  return selected.length > 0 && selected.length < filteredOptions.value.length;
});

// Scroll to selected option
const scrollToSelectedOption = () => {
  nextTick(() => {
    if (!optionsContainer.value) return;

    // Find first selected option index in filtered options
    const selectedIndex = filteredOptions.value.findIndex(option => isSelected(option));
    
    if (selectedIndex >= 0 && optionRefs.value[selectedIndex]) {
      const optionEl = optionRefs.value[selectedIndex];
      const container = optionsContainer.value;
      
      // Calculate position to center the selected option
      const optionTop = optionEl.offsetTop;
      const optionHeight = optionEl.offsetHeight;
      const containerHeight = container.clientHeight;
      
      // Scroll to center the selected option
      container.scrollTop = optionTop - (containerHeight / 2) + (optionHeight / 2);
      
      // Set focused index to selected
      focusedIndex.value = selectedIndex;
    }
  });
};

// Toggle dropdown
const toggleDropdown = async () => {
  if (props.disabled) return;
  
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    search.value = "";
    optionRefs.value = [];
    await nextTick();
    adjustDropdownPosition();
    searchInput.value?.focus();
    scrollToSelectedOption();
  } else {
    selectBox.value?.focus();
  }
};

// Close dropdown
const closeDropdown = () => {
  isOpen.value = false;
  selectBox.value?.focus();
};

// Select option
const selectOption = (option) => {
  if (props.multiple) {
    const currentValues = [...selectedValues.value];
    const valueStr = String(option.value);
    const index = currentValues.findIndex(val => String(val) === valueStr);

    if (index > -1) {
      currentValues.splice(index, 1);
    } else {
      currentValues.push(option.value);
    }

    emit("update:modelValue", currentValues);
    
    if (!props.closeOnSelect) {
      nextTick(() => searchInput.value?.focus());
    } else {
      closeDropdown();
    }
  } else {
    emit("update:modelValue", option.value);
    closeDropdown();
  }
};

// Remove option (for multi-select tags)
const removeOption = (option) => {
  if (!props.multiple) return;
  selectOption(option);
};

// Toggle select all
const toggleSelectAll = () => {
  if (!props.multiple) return;

  if (isAllSelected.value) {
    const filteredValues = filteredOptions.value.map(o => String(o.value));
    const newValues = selectedValues.value.filter(
      val => !filteredValues.includes(String(val))
    );
    emit("update:modelValue", newValues);
  } else {
    const allValues = new Set([
      ...selectedValues.value,
      ...filteredOptions.value.map(o => o.value)
    ]);
    emit("update:modelValue", Array.from(allValues));
  }

  nextTick(() => searchInput.value?.focus());
};

// Clear all selections
const clearAll = () => {
  emit("update:modelValue", props.multiple ? [] : null);
  nextTick(() => searchInput.value?.focus());
};

// Keyboard navigation
const focusNext = () => {
  if (filteredOptions.value.length === 0) return;
  focusedIndex.value = (focusedIndex.value + 1) % filteredOptions.value.length;
  scrollToFocusedOption();
};

const focusPrevious = () => {
  if (filteredOptions.value.length === 0) return;
  focusedIndex.value = focusedIndex.value <= 0 
    ? filteredOptions.value.length - 1 
    : focusedIndex.value - 1;
  scrollToFocusedOption();
};

const selectFocusedOption = () => {
  if (focusedIndex.value >= 0 && focusedIndex.value < filteredOptions.value.length) {
    selectOption(filteredOptions.value[focusedIndex.value]);
  }
};

const handleTab = (e) => {
  closeDropdown();
};

// Scroll to focused option
const scrollToFocusedOption = () => {
  nextTick(() => {
    const focusedEl = optionRefs.value[focusedIndex.value];
    if (focusedEl && optionsContainer.value) {
      const container = optionsContainer.value;
      const optionTop = focusedEl.offsetTop;
      const optionBottom = optionTop + focusedEl.offsetHeight;
      const containerTop = container.scrollTop;
      const containerBottom = containerTop + container.clientHeight;

      if (optionTop < containerTop) {
        container.scrollTop = optionTop;
      } else if (optionBottom > containerBottom) {
        container.scrollTop = optionBottom - container.clientHeight;
      }
    }
  });
};

// Click outside handler
const handleClickOutside = (e) => {
  if (wrapperRef.value && !wrapperRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

// Adjust dropdown position
const adjustDropdownPosition = () => {
  if (!wrapperRef.value || !dropdownRef.value) return;
  
  const wrapper = wrapperRef.value.getBoundingClientRect();
  const dropdownHeight = 400; // approximate max height

  const spaceBelow = window.innerHeight - wrapper.bottom;
  const spaceAbove = wrapper.top;

  dropdownPosition.value =
    spaceBelow < dropdownHeight && spaceAbove > spaceBelow ? "up" : "down";
};

// Window resize handler
const handleResize = () => {
  if (isOpen.value) {
    adjustDropdownPosition();
  }
};

// Lifecycle hooks
onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  window.removeEventListener("resize", handleResize);
});

// Watch isOpen to adjust position
watch(isOpen, (open) => {
  if (open) {
    nextTick(adjustDropdownPosition);
  }
});

// Reset focused index when search changes
watch(search, () => {
  focusedIndex.value = -1;
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.1s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom scrollbar for options container */
.max-h-\[300px\]::-webkit-scrollbar {
  width: 8px;
}

.max-h-\[300px\]::-webkit-scrollbar-track {
  background: transparent;
}

.max-h-\[300px\]::-webkit-scrollbar-thumb {
  background: hsl(var(--border));
  border-radius: 4px;
}

.max-h-\[300px\]::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--border) / 0.8);
}
</style>