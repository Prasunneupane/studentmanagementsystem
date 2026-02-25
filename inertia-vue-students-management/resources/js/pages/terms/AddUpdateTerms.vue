<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
// import { Textarea } from '@/components/ui/textarea'
import CustomSelect from '../CustomSelect.vue'
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import {
  Card, CardContent, CardDescription, CardHeader, CardTitle
} from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Loader2, Eye } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { useToast } from '@/composables/useToast'
import DatePicker from '@/components/ui/datepicker/DatePicker.vue'
import 'vue-sonner/style.css'
import { usePermission } from '@/composables/usePermissions'
const { toast } = useToast()
const { can } = usePermission();
// -------- PROPS ----------
interface Option {
  value: string | number
  label: string
}

const props = defineProps({
  academicYears: {
    type: Array as () => Option[],
    default: () => []
  },
  terms: {
    type: Object,
    default: null
  },
  currentAcademicYear: {
    type: Object,
    default: null
  }
})



const isEdit = computed(() => !!props.terms)

const fromDateValue = computed(() => {
 
  return new Date()
})

const toDateValue = computed(() => {
  return new Date()
})
// Dropdown options
const academicYears = props.academicYears as Option[]

const defaultAcademicYearType = props.currentAcademicYear?.value || (academicYears.length > 0 ? academicYears[0].value : '')

// -------- FORM INIT ----------
const form = useForm({
  name: props.terms?.name || '',
  term_number: props.terms?.term_number  || '',
  academic_year_id: props.currentAcademicYear?.value || '' as string,
  start_date: props.terms?.start_date || fromDateValue,
  end_date: props.terms?.end_date || toDateValue,
})

const errors = ref<Record<string, string>>({})

// -------- SUBMIT ----------
const handleSubmit = () => {
  errors.value = {}

  const payload = {
    onSuccess: () => {
      toast.success(isEdit.value ? "Term updated successfully." : "Term added successfully.")

      if (!isEdit.value) {
        form.reset()
        form.academic_year_id = defaultAcademicYearType
       
      }
    },

    onError: () => {
      const errorMessages = Object.values(form.errors)
      console.log(errorMessages,"errormessage");
      
      const msg = errorMessages.length > 0 ? errorMessages[0] : "Something went wrong."
      toast.error(msg)
    }
  }

  if (isEdit.value) {
    form.put(route('terms.update', props.terms.id), payload)
  } else {
    form.post(route('terms.store'), payload)
  }
}

</script>

<template>
  <Head :title="isEdit ? 'Edit Term' : 'Add Term'" />

  <AppLayout 
    :breadcrumbs="[
      { title: 'Terms', href: '/terms' },
      { title: isEdit ? 'Edit Term' : 'Add Term', href: '' }
    ]"
  >
    <Toaster position="top-right" />

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      
      <Card>
        <CardHeader>
          <CardTitle>
            {{ isEdit ? 'Edit Term' : 'Add Term' }}

            <Button v-if="can('terms.canView')" as-child class="ml-auto float-right">
              <Link :href="route('terms.index')">
                <Eye class="w-4 h-4 mr-2" /> View Terms
              </Link>
            </Button>
          </CardTitle>

          <CardDescription>
            {{ isEdit ? 'Update the term details below.' : 'Fill in the details to add a new term.' }}
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="handleSubmit" class="space-y-8">
              
            <!-- Row 1: Name + Code -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="type">Academic Year <span class="text-red-500">*</span></Label>
                  <CustomSelect
                    v-model="form.academic_year_id"
                    :options="academicYears"
                    placeholder="Select Academic Year"
                />
                <p v-if="form.errors.academic_year_id" class="text-sm text-red-600">{{ form.errors.academic_year_id }}</p>
              </div>
              <div class="space-y-2">
                <Label for="name">Term Name <span class="text-red-500">*</span></Label>
                <Input id="name" v-model="form.name" placeholder="e.g. First Term, Second Term" />
                <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
              </div>

             
            </div>

            <!-- Row 2: Type + Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
               <div class="space-y-2">
                <Label for="code">Term Number <span class="text-red-500">*</span></Label>
                <Input id="code" v-model="form.term_number" placeholder="e.g. 1, 2, 3..." />
                <p v-if="form.errors.term_number" class="text-sm text-red-600">{{ form.errors.term_number }}</p>
              </div>
              

              <div class="space-y-3">
                <Label>Start Date <span class="text-red-500">*</span></Label>
                <DatePicker
                  :model-value="form.start_date"
                  placeholder="Start Date"
                  :error="form.errors.start_date"
                  />

              </div>
               <div class="space-y-2">
              <Label for="description">End Date <span class="text-red-500">*</span></Label>
              <DatePicker
                :model-value="form.end_date"
                placeholder="End Date"
                :error="form.errors.end_date"
              />
               <p v-if="form.errors.end_date" class="text-sm text-red-600">{{ form.errors.end_date }}</p>
            </div>

            </div>

            <!-- Description -->
           

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t" v-if="can('terms.canCreate') || can('terms.canEdit')">
              
              <Button type="submit" :disabled="form.processing" class="cursor-pointer">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ isEdit ? 'Update Term' : 'Add Term' }}
              </Button>

            </div>

          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
