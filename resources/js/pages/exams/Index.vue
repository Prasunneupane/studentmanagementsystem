<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Exam Management</h1>
        <p class="text-gray-600 mt-2">Manage and view all exams</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <StatCard title="Total Exams" :value="stats.total_exams" icon="Calendar" color="blue" />
        <StatCard title="Draft" :value="stats.draft_exams" icon="FileText" color="gray" />
        <StatCard title="Upcoming" :value="stats.upcoming_exams" icon="Clock" color="blue" />
        <StatCard title="Ongoing" :value="stats.ongoing_exams" icon="Play" color="green" />
        <StatCard title="Completed" :value="stats.completed_exams" icon="CheckCircle" color="gray" />
        <StatCard title="Published" :value="stats.published_exams" icon="Globe" color="purple" />
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-wrap gap-4">
          <CustomSelect
                    v-model="filtersData.status" 
                    @update:model-value="applyFilters"
                    :options="status"
                    placeholder="Select Academic Year"
          />
          <CustomSelect
                    v-model="filtersData.exam_type" 
                    @update:model-value="applyFilters"
                    :options="[
                      { value: 'all', label: 'All Types' },
                      { value: 'unit_test', label: 'Unit Test' },
                      { value: 'midterm', label: 'Midterm' },
                      { value: 'final', label: 'Final' },
                      { value: 'semester', label: 'Semester' },
                      { value: 'annual', label: 'Annual' }
                    ]"
                    placeholder="Select Exam Type"
          />

          

          <CustomSelect
                    v-model="filtersData.academic_year_id" 
                    @update:model-value="applyFilters"
                    :options="[
                      { value: 'all', label: 'All Years' }
                    ]"
                    placeholder="Select Academic Year"
          />
              

          <Button @click="resetFilters" variant="outline">
            <RefreshCw class="w-4 h-4 mr-2" />
            Reset
          </Button>
        </div>
      </div>

      <!-- Exam Cards Grid -->
      <div v-if="exams.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <ExamCard
          v-for="exam in exams.data"
          :key="exam.id"
          :exam="exam"
          @view="viewExam"
        />
      </div>
      
      <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center">
        <Calendar class="w-12 h-12 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 mb-2">No exams found</h3>
        <p class="text-gray-500">Try adjusting your filters or create a new exam</p>
      </div>

      <!-- Pagination -->
      <div v-if="exams.last_page > 1" class="mt-8 flex justify-center">
        <Pagination
          :current-page="exams.current_page"
          :last-page="exams.last_page"
          @page-change="changePage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { Calendar, RefreshCw } from 'lucide-vue-next'
import StatCard from '../exams/StatCard.vue'
import ExamCard from '../exams/ExamCard.vue'
import CustomSelect from '../CustomSelect.vue'
import { Button } from '@/components/ui/button'
import { Pagination } from '@/components/ui/pagination'

const props = defineProps({
  exams: Object,
  stats: Object,
  filters: Object,
  academicYears: Array
})
const status = [
  { value: 'all', label: 'All Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'upcoming', label: 'Upcoming' },
  { value: 'ongoing', label: 'Ongoing' },
  { value: 'completed', label: 'Completed' }
]
const filtersData = reactive({
  status: props.filters.status || 'all',
  exam_type: props.filters.exam_type || 'all',
  academic_year_id: props.filters.academic_year_id || 'all'
})

const applyFilters = () => {
  const params = {}
  if (filtersData.status !== 'all') params.status = filtersData.status
  if (filtersData.exam_type !== 'all') params.exam_type = filtersData.exam_type
  if (filtersData.academic_year_id !== 'all') params.academic_year_id = filtersData.academic_year_id
  
  router.get('/exams', params, {
    preserveState: true,
    preserveScroll: true
  })
}

const resetFilters = () => {
  filtersData.status = 'all'
  filtersData.exam_type = 'all'
  filtersData.academic_year_id = 'all'
  applyFilters()
}

const changePage = (page) => {
  const params = {}
  if (filtersData.status !== 'all') params.status = filtersData.status
  if (filtersData.exam_type !== 'all') params.exam_type = filtersData.exam_type
  if (filtersData.academic_year_id !== 'all') params.academic_year_id = filtersData.academic_year_id
  params.page = page
  
  router.get('/exams', params, {
    preserveState: true,
    preserveScroll: true
  })
}

const viewExam = (exam) => {
  router.visit(`/exams/${exam.id}`)
}
</script>