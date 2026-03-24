<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <!-- Header with Back Button -->
      <div class="mb-6 flex items-center justify-between">
        <Button variant="ghost" @click="goBack" class="gap-2">
          <ArrowLeft class="w-4 h-4" />
          Back to Exams
        </Button>
        
        <div class="flex gap-3">
          <Button variant="outline" @click="printSchedule">
            <Printer class="w-4 h-4 mr-2" />
            Print Schedule
          </Button>
        </div>
      </div>

      <!-- Exam Info Card -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ exam.name }}</h1>
            <div class="flex gap-2 mt-2">
              <Badge :variant="getTypeVariant(exam.exam_type)">
                {{ exam.exam_type_label }}
              </Badge>
              <Badge :variant="getStatusVariant(exam.status)">
                {{ exam.status_label }}
              </Badge>
              <Badge v-if="exam.is_published" variant="success">
                Published
              </Badge>
              <Badge v-else variant="secondary">
                Not Published
              </Badge>
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
          <InfoItem icon="Calendar" :value="exam.start_date + ' - ' + exam.end_date" label="Duration" />
          <InfoItem icon="Weight" :value="exam.weightage + '%'" label="Weightage" />
          <InfoItem icon="GraduationCap" :value="exam.academic_year" label="Academic Year" />
          <InfoItem icon="Tag" :value="exam.term || 'N/A'" label="Term" />
        </div>
      </div>

      <!-- Tabs for different views -->
      <Tabs default-value="schedule" class="w-full">
        <TabsList class="grid w-full grid-cols-2 lg:w-[400px]">
          <TabsTrigger value="schedule">Schedule View</TabsTrigger>
          <TabsTrigger value="calendar">Calendar View</TabsTrigger>
        </TabsList>
        
        <TabsContent value="schedule" class="mt-6">
          <!-- Schedule by Class/Section -->
          <div v-for="classGroup in examClasses" :key="classGroup.class_id" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ classGroup.class_name }}</h2>
            
            <div v-for="section in classGroup.sections" :key="section.section_id" class="mb-6">
              <h3 class="text-lg font-medium text-gray-700 mb-3">
                Section {{ section.section_name }}
              </h3>
              
              <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date</TableHead>
                      <TableHead>Subject</TableHead>
                      <TableHead>Time</TableHead>
                      <TableHead>Room</TableHead>
                      <TableHead>Full Marks</TableHead>
                      <TableHead>Pass Marks</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="schedule in section.schedules" :key="schedule.id">
                      <TableCell class="font-medium">{{ schedule.exam_date_formatted }}</TableCell>
                      <TableCell>
                        <div>
                          <p class="font-medium">{{ schedule.subject_name }}</p>
                          <p class="text-xs text-gray-500">{{ schedule.subject_code }}</p>
                        </div>
                      </TableCell>
                      <TableCell>{{ schedule.start_time }} - {{ schedule.end_time }}</TableCell>
                      <TableCell>{{ schedule.room_no || '—' }}</TableCell>
                      <TableCell>{{ schedule.full_marks }}</TableCell>
                      <TableCell>{{ schedule.pass_marks }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </div>
          </div>
        </TabsContent>
        
        <TabsContent value="calendar" class="mt-6">
          <!-- Calendar View -->
          <div class="bg-white rounded-lg shadow-sm p-4">
            <FullCalendar :options="calendarOptions" />
          </div>
        </TabsContent>
      </Tabs>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import { ArrowLeft, Printer, Calendar, Weight, GraduationCap, Tag } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import InfoItem from '@/components  /InfoItem.vue'

const props = defineProps({
  exam: Object,
  examClasses: Array
})

const goBack = () => {
  router.visit('/exams')
}

const printSchedule = () => {
  window.open(`/exams/${props.exam.id}/print`, '_blank')
}

const getTypeVariant = (type) => {
  const variants = {
    unit_test: 'secondary',
    midterm: 'default',
    final: 'destructive',
    semester: 'outline',
    annual: 'default'
  }
  return variants[type] || 'secondary'
}

const getStatusVariant = (status) => {
  const variants = {
    draft: 'secondary',
    upcoming: 'outline',
    ongoing: 'default',
    completed: 'secondary'
  }
  return variants[status] || 'secondary'
}

// Prepare calendar events
const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin],
  initialView: 'timeGridWeek',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  events: props.examClasses.flatMap(classGroup => 
    classGroup.sections.flatMap(section =>
      section.schedules.map(schedule => ({
        title: `${classGroup.class_name} ${section.section_name ? `- ${section.section_name}` : ''} - ${schedule.subject_name}`,
        start: `${schedule.exam_date}T${schedule.start_time}`,
        end: `${schedule.exam_date}T${schedule.end_time}`,
        extendedProps: {
          room: schedule.room_no,
          subject: schedule.subject_name,
          class: classGroup.class_name,
          section: section.section_name
        }
      }))
    )
  ),
  height: 'auto',
  slotMinTime: '08:00:00',
  slotMaxTime: '17:00:00',
  eventClick: function(info) {
    alert(`Subject: ${info.event.extendedProps.subject}\nClass: ${info.event.extendedProps.class}\nSection: ${info.event.extendedProps.section}\nRoom: ${info.event.extendedProps.room}`)
  }
}))
</script>