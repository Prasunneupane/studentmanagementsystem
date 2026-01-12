<script setup lang="ts">
import { reactive, ref, watch, computed } from "vue";
import { format, subYears, differenceInYears } from "date-fns";
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useStudentForm } from '@/composables/useStudentForm';
import { useFormValidation } from '@/composables/useFormValidation';
import { useLocationData } from '@/composables/useLocationData';
import { useAcademicData } from '@/composables/useAcademicData';

import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css'

// Import components
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import DatePicker from "@/components/ui/datepicker/DatePicker.vue";
import StudentFormSections from "@/components/forms/StudentFormSections.vue";

// Breadcrumbs
const breadcrumbs = [{ title: 'Add Student', href: '/student/create' }];

// Reference to the form component
const studentFormRef = ref<InstanceType<typeof StudentFormSections> | null>(null);

const props = defineProps({
  stateList: {
    type: Object,
    // default: null
  },
  classList: {
    type: Object,
    // default: null
  }
});
console.log(props.stateList,"states in register student");

// Initialize form with defaults
const today = new Date();
const form = useForm({
  // Personal Info
  fName: '', 
  mName: '', 
  lName: '', 
  email: '', 
  phone: '', 
  age: '',
  dateOfBirth: '', 
  motherName: '', 
  fatherName: '', 
  guardianName: '',
  contactNumber: '', 
  photo: null as File | null,

  // Academic Info
  classId: null as { value: string; label: string } | null,
  sectionId: null as { value: string; label: string } | null,
  joinedDate: format(today, 'yyyy-MM-dd'),

  // Address Info
  address: '',
  stateId: null as { value: string; label: string } | null,
  districtId: null as { value: string; label: string } | null,
  municipalityId: null as { value: string; label: string } | null,

  // Guardian Info (Array)
  guardians: [] as any[]
});

// Use composables
const { 
  validationErrors, 
  showValidation, 
  validateField, 
  validateGuardianField, 
  validateAllFields 
} = useFormValidation(form);

const { states, districts, municipalities, ...locationHandlers } = useLocationData(form);
const { classes, sections, ...academicHandlers } = useAcademicData();
const { dateOfBirthValue, joinedDateValue, isSubmitting, handleSubmit } = useStudentForm(form, validateAllFields, validationErrors, showValidation,studentFormRef);

// Auto-sync age and date of birth
const isUpdating = ref({ age: false, dateOfBirth: false });

watch(dateOfBirthValue, (newDate) => {
  if (isUpdating.value.age) return;
  isUpdating.value.dateOfBirth = true;

  if (newDate && !isNaN(newDate.getTime())) {
    form.dateOfBirth = format(newDate, 'yyyy-MM-dd');
    form.age = differenceInYears(today, newDate).toString();
  } else {
    form.dateOfBirth = '';
    form.age = '';
  }

  if (showValidation.value) {
    validateField('dateOfBirth');
    validateField('age');
  }
  isUpdating.value.dateOfBirth = false;
});

watch(() => form.age, (newAge) => {
  if (isUpdating.value.dateOfBirth) return;
  isUpdating.value.age = true;

  const ageNum = Number(newAge);
  if (!isNaN(ageNum) && ageNum >= 1 && ageNum <= 100) {
    const calculatedDate = subYears(today, ageNum);
    dateOfBirthValue.value = calculatedDate;
    form.dateOfBirth = format(calculatedDate, 'yyyy-MM-dd');
  } else {
    dateOfBirthValue.value = null;
    form.dateOfBirth = '';
  }

  if (showValidation.value) {
    validateField('age');
    validateField('dateOfBirth');
  }
  isUpdating.value.age = false;
});

watch(joinedDateValue, (newDate) => {
  form.joinedDate = newDate ? format(newDate, 'yyyy-MM-dd') : '';
  if (showValidation.value) validateField('joinedDate');
});

// Field handlers
const handleFieldBlur = (fieldName: string) => {
  if (showValidation.value) validateField(fieldName);
};

const handlePhoneInput = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.value.length > 10) {
    input.value = input.value.slice(0, 10);
    form.phone = input.value;
  }
  if (showValidation.value) validateField('phone');
};

const handlePhotoChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  form.photo = input.files?.[0] || null;
};

// Guardian validation handler
const handleGuardianValidation = (index: number, field: string) => {
  if (showValidation.value) {
    validateGuardianField(index, field);
  }
};
</script>

<template>
  <Head title="Student Register" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster />
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">Student Registration Form</CardTitle>
        </CardHeader>
        <CardContent class="w-full">
          <StudentFormSections 
           ref="studentFormRef"
            :form="form" 
            :validation-errors="validationErrors" 
            :show-validation="showValidation"
            :states="states" 
            :districts="districts" 
            :municipalities="municipalities" 
            :classes="classes"
            :sections="sections" 
            :date-of-birth-value="dateOfBirthValue" 
            :joined-date-value="joinedDateValue"
            :is-submitting="isSubmitting" 
            @field-blur="handleFieldBlur" 
            @phone-input="handlePhoneInput"
            @photo-change="handlePhotoChange" 
            @submit="handleSubmit"
            @update:date-of-birth="(val: Date | null) => dateOfBirthValue = val"
            @update:joined-date="(val: Date | null) => joinedDateValue = val"
            @validate-guardian="handleGuardianValidation"
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>