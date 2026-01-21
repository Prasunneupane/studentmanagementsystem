<script setup lang="ts">
import { reactive, ref, watch, computed, onMounted } from "vue";
import { format, subYears, differenceInYears } from "date-fns";
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useStudentForm } from '@/composables/useStudentForm';
import { useFormValidation } from '@/composables/useFormValidation';
import { useAcademicData } from '@/composables/useAcademicData';
import axios from 'axios';

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

interface DefaultValue {
  setting_key: number;
  setting_value: string;
}

interface StateOption {
  value: string ;
  label: string;
}

interface DefaultOptions {
  value: number;
  label: string;
}

// Breadcrumbs
const breadcrumbs = [{ title: 'Add Student', href: '/student/create' }];

// Reference to the form component
const studentFormRef = ref<InstanceType<typeof StudentFormSections> | null>(null);

const props = defineProps<{
  defaultValues: DefaultValue[];
  stateList: StateOption[];
  districtList: StateOption[];
  municipalitiesList: StateOption[];
  defaultStates: DefaultOptions | null;
  defaultDistricts: DefaultOptions | null;
  defaultMunicipalities: DefaultOptions | null;
  classList: DefaultOptions[];
}>();

console.log('Props:', props);

// Local reactive state for location data
const districts = ref<StateOption[]>(props.districtList || []);
const municipalities = ref<StateOption[]>(props.municipalitiesList || []);
const isLoadingDistricts = ref(false);
const isLoadingMunicipalities = ref(false);

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
  stateId: props.defaultStates ? { 
    value: props.defaultStates.value.toString(), 
    label: props.defaultStates.label 
  } : null,
  districtId: props.defaultDistricts ? { 
    value: props.defaultDistricts.value.toString(), 
    label: props.defaultDistricts.label 
  } : null,
  municipalityId: props.defaultMunicipalities ? { 
    value: props.defaultMunicipalities.value.toString(), 
    label: props.defaultMunicipalities.label 
  } : null,

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

const { classes, sections, ...academicHandlers } = useAcademicData();
const { dateOfBirthValue, joinedDateValue, isSubmitting, handleSubmit } = useStudentForm(
  form, 
  validateAllFields, 
  validationErrors, 
  showValidation, 
  studentFormRef
);

// APPROACH 1: Using Inertia Router Reload (Recommended for Inertia apps)
const fetchDistrictsInertia = (stateId: string | number) => {
  if (!stateId) return;
  
  isLoadingDistricts.value = true;
  
  router.reload({
    only: ['districtList'],
    data: { state_id: stateId },
    preserveState: true,
    // preserveScroll: true,
    onSuccess: (page) => {
      //districts = page.props.districtList || [];
      isLoadingDistricts.value = false;
    },
    onError: () => {
      isLoadingDistricts.value = false;
      console.error('Failed to fetch districts');
    }
  });
};

const fetchMunicipalitiesInertia = (districtId: string | number, stateId: string | number) => {
  if (!districtId) return;
  
  isLoadingMunicipalities.value = true;
  
  router.reload({
    only: ['municipalitiesList'],
    data: { 
      district_id: districtId,
      state_id: stateId 
    },
    //preserveState: true,
    //preserveScroll: true,
    onSuccess: (page) => {
      //municipalities.value = page.props.municipalitiesList || [];
      isLoadingMunicipalities.value = false;
    },
    onError: () => {
      isLoadingMunicipalities.value = false;
      console.error('Failed to fetch municipalities');
    }
  });
};

// APPROACH 2: Using Axios (Alternative if you prefer separate API endpoints)
const fetchDistrictsAxios = async (stateId: string | number) => {
  if (!stateId) return;
  
  isLoadingDistricts.value = true;
  try {
    const response = await axios.get('/student/districts', {
      params: { state_id: stateId }
    });
    districts.value = response.data.districtList || [];
  } catch (error) {
    console.error('Failed to fetch districts:', error);
  } finally {
    isLoadingDistricts.value = false;
  }
};

const fetchMunicipalitiesAxios = async (districtId: string | number) => {
  if (!districtId) return;
  
  isLoadingMunicipalities.value = true;
  try {
    const response = await axios.get('/student/municipalities', {
      params: { district_id: districtId }
    });
    municipalities.value = response.data.municipalitiesList || [];
  } catch (error) {
    console.error('Failed to fetch municipalities:', error);
  } finally {
    isLoadingMunicipalities.value = false;
  }
};

// Watch for state changes
watch(() => form.stateId, (newState, oldState) => {
  if (newState && newState.value !== oldState?.value) {
    // Reset dependent fields
    form.districtId = null;
    form.municipalityId = null;
    municipalities.value = [];
    
    // Fetch districts - Choose one approach
    // APPROACH 1: Inertia (Recommended)
    fetchDistrictsInertia(newState.value);
    
    // APPROACH 2: Axios (Alternative)
    // fetchDistrictsAxios(newState.value);
  }
});

// Watch for district changes
watch(() => form.districtId, (newDistrict, oldDistrict) => {
  if (newDistrict && newDistrict.value !== oldDistrict?.value) {
    // Reset dependent fields
    form.municipalityId = null;
    
    // Fetch municipalities - Choose one approach
    // APPROACH 1: Inertia (Recommended)
    if (form.stateId) {
      fetchMunicipalitiesInertia(newDistrict.value, form.stateId.value);
    }
    
    // APPROACH 2: Axios (Alternative)
    // fetchMunicipalitiesAxios(newDistrict.value);
  }
});

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
            :states="props.stateList" 
            :districts="districts" 
            :municipalities="municipalities" 
            :classes="props.classList"
            :sections="sections" 
            :date-of-birth-value="dateOfBirthValue" 
            :joined-date-value="joinedDateValue"
            :is-submitting="isSubmitting"
            :is-loading-districts="isLoadingDistricts"
            :is-loading-municipalities="isLoadingMunicipalities"
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