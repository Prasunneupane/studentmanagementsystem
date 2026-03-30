<template>
  <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
    <component :is="iconComponent" class="w-5 h-5 text-gray-500" />
    <div>
      <p class="text-xs text-gray-500">{{ label }}</p>
      <p class="text-sm font-medium text-gray-900">{{ value }}</p>
    </div>
  </div>
</template>

<script setup>
import { Calendar, Weight, GraduationCap, Tag } from 'lucide-vue-next'

const props = defineProps({
  icon: {
    type: String,
    required: true
  },
  value: {
    type: String,
    required: true
  },
  label: {
    type: String,
    required: true
  }
})

const iconComponent = computed(() => {
  const icons = {
    Calendar,
    Weight,
    GraduationCap,
    Tag
  }
  return icons[props.icon] || Calendar
})
</script>

<script>
import { computed } from 'vue'

export default {
  setup(props) {
    const iconComponent = computed(() => {
      const icons = {
        Calendar,
        Weight,
        GraduationCap,
        Tag
      }
      return icons[props.icon] || Calendar
    })
    
    return { iconComponent }
  }
}
</script>