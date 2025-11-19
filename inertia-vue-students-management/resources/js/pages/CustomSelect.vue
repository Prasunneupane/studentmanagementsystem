<template>
  <div ref="wrapperRef" class="relative w-full">
    <!-- SELECT BOX -->
    <div
      class="border rounded-sm p-[5.6px]  bg-white cursor-pointer flex justify-between items-center"
      @click="toggleDropdown"
    >
      <span>
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
        :style="{ top: dropdownPosition === 'down' ? '100%' : 'auto', bottom: dropdownPosition === 'up' ? '100%' : 'auto' }"
      >
        <!-- SEARCH -->
        <input
          v-model="search"
          class="w-full p-2 border-b outline-none"
          placeholder="Search..."
        />

        <!-- OPTIONS -->
        <div v-if="filteredOptions.length">
          <div
            v-for="option in filteredOptions"
            :key="option.value"
            class="p-3 cursor-pointer hover:bg-gray-100"
            @click="selectOption(option)"
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
import { ref, computed, onMounted, watch, nextTick } from "vue";

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
});
// console.log(modelValue,"modelValue");
const emit = defineEmits(["update:modelValue"]);

const isOpen = ref(false);
const search = ref("");
const dropdownPosition = ref("down");

const wrapperRef = ref(null);
const dropdownRef = ref(null);

const selectedOption = computed(() => {
  // If modelValue is an object → extract value
  const modelVal = typeof props.modelValue === "object"
    ? props.modelValue?.value
    : props.modelValue;

  return props.options.find(
    o => String(o.value) === String(modelVal)
  ) || null;
});

// --- FILTER ---
const filteredOptions = computed(() =>
  props.options.filter(
    (option) =>
      option.label?.toString().toLowerCase().includes(search.value.toLowerCase())
  )
);
watch(
  () => props.modelValue,
  (newVal) => {
    console.log("Selected modelValue:", newVal);
  },
  { immediate: true }
);

// --- TOGGLE ---
const toggleDropdown = async () => {
  isOpen.value = !isOpen.value;

  if (isOpen.value) {
    search.value = "";
    await nextTick();
    adjustDropdownPosition();
  }
};

// --- SELECT OPTION ---
const selectOption = (option) => {
  emit("update:modelValue", option.value);
  isOpen.value = false;
};

// --- CLICK OUTSIDE ---
const handleClickOutside = (e) => {
  if (!wrapperRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

// --- POSITION LOGIC ---
const adjustDropdownPosition = () => {
  const wrapper = wrapperRef.value.getBoundingClientRect();
  const dropdownHeight = dropdownRef.value.scrollHeight;

  const spaceBelow = window.innerHeight - wrapper.bottom;
  const spaceAbove = wrapper.top;

  dropdownPosition.value =
    spaceBelow < dropdownHeight && spaceAbove > spaceBelow ? "up" : "down";
};

// WATCH WINDOW RESIZE
window.addEventListener("resize", adjustDropdownPosition);

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

watch(isOpen, (open) => {
  if (open) nextTick(adjustDropdownPosition);
});
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(2px);
}
</style>
