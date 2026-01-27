<template>
  <div ref="wrapperRef" class="relative w-full">
    <!-- SELECT BOX -->
    <div
      class="border rounded-sm p-[5.6px] bg-white cursor-pointer flex justify-between items-center"
      :class="{ 'opacity-50 cursor-not-allowed': disabled }"
      @click="!disabled && toggleDropdown()"
    >
      <span :class="{ 'text-gray-400': !selectedOption }">
        {{ selectedOption?.label || placeholder }}
      </span>
      <span>▾</span>
    </div>

    <!-- DROPDOWN -->
    <transition name="fade">
      <div
        v-if="isOpen"
        ref="dropdownRef"
        class="absolute w-full bg-white border rounded-lg shadow-md max-h-60 overflow-auto z-50"
        :style="{ 
          top: dropdownPosition === 'down' ? '100%' : 'auto', 
          bottom: dropdownPosition === 'up' ? '100%' : 'auto',
          marginTop: dropdownPosition === 'down' ? '4px' : '0',
          marginBottom: dropdownPosition === 'up' ? '4px' : '0'
        }"
      >
        <!-- SEARCH -->
        <input
          ref="searchInput"
          v-model="search"
          class="w-full p-2 border-b outline-none"
          placeholder="Search..."
          @click.stop
        />

        <!-- OPTIONS -->
        <div v-if="filteredOptions.length">
          <div
            v-for="option in filteredOptions"
            :key="option.value"
            class="p-3 cursor-pointer hover:bg-gray-100"
            :class="{ 'bg-blue-50': isSelected(option) }"
            @click.stop="selectOption(option)"
          >
            {{ option.label }}
          </div>
        </div>

        <div v-else class="p-2 text-gray-400 text-sm">No results found</div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";

const props = defineProps({
  modelValue: [String, Number, Object],
  options: {
    type: Array,
    required: true,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: "Select…",
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const search = ref("");
const dropdownPosition = ref("down");

const wrapperRef = ref(null);
const dropdownRef = ref(null);
const searchInput = ref(null);

// Find selected option based on modelValue
const selectedOption = computed(() => {
  if (!props.modelValue) return null;
  
  return props.options.find(
    o => String(o.value) === String(props.modelValue)
  ) || null;
});

// Check if option is selected
const isSelected = (option) => {
  return String(option.value) === String(props.modelValue);
};

// Filter options based on search
const filteredOptions = computed(() =>
  props.options.filter(
    (option) =>
      option.label?.toString().toLowerCase().includes(search.value.toLowerCase())
  )
);

// Toggle dropdown
const toggleDropdown = async () => {
  if (props.disabled) return;
  
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    search.value = "";
    await nextTick();
    adjustDropdownPosition();
    // Focus search input
    searchInput.value?.focus();
  }
};

// Select option
const selectOption = (option) => {
  emit("update:modelValue", option.value);
  isOpen.value = false;
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
  const dropdownHeight = dropdownRef.value.scrollHeight || 240; // fallback height

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
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>