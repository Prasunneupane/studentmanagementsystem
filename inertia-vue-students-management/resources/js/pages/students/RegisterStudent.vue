<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { format, subYears, differenceInYears } from "date-fns";
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import DatePicker from "@/components/ui/datepicker/DatePicker.vue";
import type { BreadcrumbItem } from '@/types';
import { 
  getAllStates, 
  getDistrictsByStateId, 
  getMunicipalitiesByDistrictId, 
  getClassesList,
  getSectionList
} from '@/constant/apiservice/callService';

// Form with validation state
const today = new Date(); // September 3, 2025
const form = useForm({
  fName: '',
  mName: '',
  lName: '',
  email: '',
  phone: '',
  age: '',
  dateOfBirth: '',
  classId: null as { value: string; label: string } | null,
  sectionId: null as { value: string; label: string } | null,
  motherName: '',
  fatherName: '',
  guardianName: '',
  contactNumber: '',
  photo: null as File | null,
  joinedDate: format(today, 'yyyy-MM-dd'),
  address: '',
  stateId: null as { value: string; label: string } | null,
  districtId: null as { value: string; label: string } | null,
  municipalityId: null as { value: string; label: string } | null
});

// Validation state
const validationErrors = ref<Record<string, string>>({});
const showValidation = ref(false);
const isSubmitting = ref(false);

// Data arrays
const states = ref<{ value: string; label: string }[]>([]);
const districts = ref<{ value: string; label: string }[]>([]);
const municipalities = ref<{ value: string; label: string }[]>([]);
const classes = ref<{ value: string; label: string }[]>([]);
const section = ref<{ value: string; label: string }[]>([]);

// Loading states
const isStateLoading = ref(false);
const isDistrictLoading = ref(false);
const isMunicipalityLoading = ref(false);
const isClassesLoading = ref(false);
const isSectionLoading = ref(false);

// Date picker states
const dateOfBirthValue = ref<Date | null>(null);
const joinedDateValue = ref<Date | null>(today);

// Flags to prevent recursive updates
const isUpdatingAge = ref(false);
const isUpdatingDateOfBirth = ref(false);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Add Student', href: '/student/create' }
];

// Validation rule type
type ValidationRule = {
  required?: boolean;
  pattern?: RegExp;
  min?: number;
  max?: number;
  message: string;
};

// Validation rules
const validationRules: Record<string, ValidationRule> = {
  fName: { required: true, message: 'First name is required' },
  lName: { required: true, message: 'Last name is required' },
  email: { 
    required: false, 
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: 'Please enter a valid email address'
  },
  phone: { 
    required: true, 
    pattern: /^\d{10}$/,
    message: 'Please enter a valid 10-digit phone number'
  },
  age: { 
    required: true, 
    min: 1, 
    max: 100,
    message: 'Age must be between 1 and 100'
  },
  dateOfBirth: { required: true, message: 'Date of birth is required' },
  classId: { required: true, message: 'Please select a class' },
  fatherName: { required: true, message: 'Father name is required' },
  guardianName: { required: true, message: 'Guardian name is required' },
  joinedDate: { required: true, message: 'Joined date is required' },
  stateId: { required: true, message: 'Please select a state' }
};

// Validation function
const validateField = (fieldName: keyof typeof validationRules): boolean => {
  const rule = validationRules[fieldName];
  const value = form[fieldName as keyof typeof form];

  if (!rule) return true;

  // Required validation
  if (rule.required) {
    if (!value || (typeof value === 'string' && value.trim() === '') || 
        (typeof value === 'object' && value === null)) {
      if (showValidation.value) {
        validationErrors.value[fieldName] = rule.message;
      }
      return false;
    } else {
      delete validationErrors.value[fieldName];
    }
  }

  // Pattern validation
  if (rule.pattern && value && typeof value === 'string') {
    if (!rule.pattern.test(value)) {
      if (showValidation.value) {
        validationErrors.value[fieldName] = rule.message;
      }
      return false;
    } else {
      delete validationErrors.value[fieldName];
    }
  }

  // Min/Max validation for numbers
  if ((rule.min !== undefined || rule.max !== undefined) && value && typeof value === 'string') {
    const numValue = Number(value);
    if (isNaN(numValue)) {
      if (showValidation.value) {
        validationErrors.value[fieldName] = rule.message;
      }
      return false;
    }
    if (rule.min !== undefined && numValue < rule.min) {
      if (showValidation.value) {
        validationErrors.value[fieldName] = rule.message;
      }
      return false;
    }
    if (rule.max !== undefined && numValue > rule.max) {
      if (showValidation.value) {
        validationErrors.value[fieldName] = rule.message;
      }
      return false;
    } else {
      delete validationErrors.value[fieldName];
    }
  }

  return true;
};

// Validate all fields
const validateAllFields = (): boolean => {
  let isValid = true;
  Object.keys(validationRules).forEach((field) => {
    if (!validateField(field as keyof typeof validationRules)) {
      isValid = false;
    }
  });
  return isValid;
};

// Date formatting
const formatDate = (date: Date | null): string => {
  if (!date || isNaN(date.getTime())) {
    return '';
  }
  try {
    return format(date, 'yyyy-MM-dd');
  } catch (e) {
    console.error("Invalid date in formatDate:", e);
    return '';
  }
};

// Sync form with date picker values
watch(dateOfBirthValue, (newDate) => {
  if (isUpdatingAge.value) return; // Prevent recursive updates
  isUpdatingDateOfBirth.value = true;
  form.dateOfBirth = formatDate(newDate);
  if (newDate && !isNaN(newDate.getTime())) {
    form.age = differenceInYears(today, newDate).toString();
  } else {
    form.age = '';
  }
  if (showValidation.value) {
    validateField('dateOfBirth');
    validateField('age');
  }
  isUpdatingDateOfBirth.value = false;
});

// Sync age with dateOfBirth
watch(() => form.age, (newAge) => {
  if (isUpdatingDateOfBirth.value) return; // Prevent recursive updates
  isUpdatingAge.value = true;
  const ageNum = Number(newAge);
  if (!isNaN(ageNum) && ageNum >= 1 && ageNum <= 100) {
    const calculatedDate = subYears(today, ageNum);
    dateOfBirthValue.value = calculatedDate;
    form.dateOfBirth = formatDate(calculatedDate);
  } else {
    dateOfBirthValue.value = null;
    form.dateOfBirth = '';
  }
  if (showValidation.value) {
    validateField('age');
    validateField('dateOfBirth');
  }
  isUpdatingAge.value = false;
});

watch(joinedDateValue, (newDate) => {
  form.joinedDate = formatDate(newDate);
  if (showValidation.value) validateField('joinedDate');
});

// API functions
const fetchStates = async () => {
  isStateLoading.value = true;
  try {
    const response = await getAllStates();
    states.value = response.map((state) => ({
      value: state.id.toString(),
      label: state.name
    }));
    const defaultState = states.value.find(state => state.value === '5');
    if (defaultState) {
      form.stateId = defaultState;
    } else {
      console.warn("Default stateId '5' not found in states");
    }
  } catch (error) {
    console.error('Failed to fetch states:', error);
  } finally {
    isStateLoading.value = false;
  }
};

const fetchClasses = async () => {
  isClassesLoading.value = true;
  try {
    const response = await getClassesList();
    classes.value = response.classesList.map((cls) => ({
      value: cls.id.toString(),
      label: cls.name
    }));
  } catch (error) {
    console.error('Failed to fetch classes:', error);
  } finally {
    isClassesLoading.value = false;
  }
};

const fetchSection = async () => {
  isSectionLoading.value = true;
  try {
    const response = await getSectionList();
    section.value = response.sectionList.map((sec) => ({
      value: sec.id.toString(),
      label: sec.name
    }));
  } catch (error) {
    console.error('Failed to fetch sections:', error);
  } finally {
    isSectionLoading.value = false;
  }
};

const fetchDistricts = async (stateId: string) => {
  isDistrictLoading.value = true;
  try {
    const response = await getDistrictsByStateId(stateId);
    districts.value = response.map((district) => ({
      value: district.id.toString(),
      label: district.name
    }));
    const defaultDistrict = districts.value.find(district => district.value === '46');
    if (defaultDistrict) {
      form.districtId = defaultDistrict;
    } else {
      console.warn("Default districtId '23' not found in districts");
    }
  } catch (error) {
    console.error('Failed to fetch districts:', error);
  } finally {
    isDistrictLoading.value = false;
  }
};

const fetchMunicipalities = async (districtId: string) => {
  isMunicipalityLoading.value = true;
  try {
    const response = await getMunicipalitiesByDistrictId(districtId);
    municipalities.value = response.municipalities.map((municipality) => ({
      value: municipality.id.toString(),
      label: municipality.name
    }));
    const defaultMunicipality = municipalities.value.find(municipality => municipality.value === '463');
    if (defaultMunicipality) {
      form.municipalityId = defaultMunicipality;
    } else {
      console.warn("Default municipalityId '10' not found in municipalities");
    }
  } catch (error) {
    console.error('Failed to fetch municipalities:', error);
  } finally {
    isMunicipalityLoading.value = false;
  }
};

// Watch state changes
watch(() => form.stateId, async (newState) => {
  districts.value = [];
  municipalities.value = [];
  form.districtId = null;
  form.municipalityId = null;
  
  if (newState && newState.value) {
    await fetchDistricts(newState.value);
  }
}, { immediate: false });

// Watch district changes
watch(() => form.districtId, async (newDistrict) => {
  municipalities.value = [];
  form.municipalityId = null;
  
  if (newDistrict && newDistrict.value) {
    await fetchMunicipalities(newDistrict.value);
  }
}, { immediate: false });

// Field blur handler
const handleFieldBlur = (fieldName: keyof typeof validationRules) => {
  if (showValidation.value) {
    validateField(fieldName);
  }
};

// Phone input handler
const handlePhoneInput = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.value.length > 10) {
    input.value = input.value.slice(0, 10);
    form.phone = input.value;
  }
  if (showValidation.value) validateField('phone');
};

// Photo change handler
const handlePhotoChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    form.photo = input.files[0];
  }
};

// Submit handler
const handleSubmit = async () => {
  showValidation.value = true;
  isSubmitting.value = true;
  
  try {
    if (!validateAllFields()) {
      const firstErrorField = Object.keys(validationErrors.value)[0];
      const errorElement = document.getElementById(firstErrorField);
      if (errorElement) {
        errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        errorElement.focus();
      }
      return;
    }
    
    // Transform the form data before sending with Inertia
    form.transform((data) => {
      const transformedData: Record<string, any> = {};
      
      for (const [key, value] of Object.entries(data)) {
        if (value !== null && value !== '' && value !== undefined) {
          if (['stateId', 'districtId', 'municipalityId', 'classId', 'sectionId'].includes(key)) {
            // Extract only the value, ignore the label
            transformedData[key] = (typeof value === 'object' && !(value instanceof File) && value?.value) ? value.value : value;
          } else if (key === 'photo' && value instanceof File) {
            transformedData[key] = value;
          } else if (key === 'photo' && (typeof value === 'object' && Object.keys(value).length === 0)) {
            // Skip empty photo object
            continue;
          } else {
            transformedData[key] = value;
          }
        }
      }
      
      console.log('Transformed data:', transformedData);
      return transformedData;
    }).post(route('students.store'), {
      onSuccess: () => {
        console.log('Form submitted successfully');
        dateOfBirthValue.value = null;
        joinedDateValue.value = new Date();
        showValidation.value = false;
        validationErrors.value = {};
        form.reset();
      },
      onError: (errors) => {
        console.error('Form submission errors:', errors);
        validationErrors.value = { ...validationErrors.value, ...errors };
      }
    });
  } catch (error) {
    console.error('Form submission failed:', error);
  } finally {
    isSubmitting.value = false;
  }
};

// Initialize data and set defaults
const initializeForm = async () => {
  await fetchStates();
  if (form.stateId?.value) {
    await fetchDistricts(form.stateId.value);
    if (form.districtId?.value) {
      await fetchMunicipalities(form.districtId.value);
    }
  }
  await Promise.all([fetchClasses(), fetchSection()]);
};

// Run initialization
initializeForm();
</script>

<template>
  <Head title="Student Register" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">Student Registration Form</CardTitle>
        </CardHeader>
        <CardContent class="w-full">
          <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Name Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2" id="fName">
                <Label for="fName">First Name <span class="text-red-500">*</span></Label>
                <Input 
                  id="fName" 
                  v-model="form.fName" 
                  placeholder="Enter first name"
                  :class="{ 'border-red-500 focus:border-red-500': validationErrors.fName }"
                  @blur="handleFieldBlur('fName')"
                />
                <p v-if="validationErrors.fName" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.fName }}
                </p>
              </div>
              <div class="space-y-2" id="mName">
                <Label for="mName">Middle Name</Label>
                <Input 
                  id="mName" 
                  v-model="form.mName" 
                  placeholder="Enter middle name" 
                />
              </div>
              <div class="space-y-2" id="lName">
                <Label for="lName">Last Name <span class="text-red-500">*</span></Label>
                <Input 
                  id="lName" 
                  v-model="form.lName" 
                  placeholder="Enter last name"
                  :class="{ 'border-red-500 focus:border-red-500': validationErrors.lName }"
                  @blur="handleFieldBlur('lName')"
                />
                <p v-if="validationErrors.lName" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.lName }}
                </p>
              </div>
            </div>

            <!-- Contact Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2" id="email">
                <Label for="email">Email</Label>
                <Input 
                  id="email" 
                  v-model="form.email" 
                  placeholder="Enter email address" 
                  type="email"
                  :class="{ 'border-red-500 focus:border-red-500': validationErrors.email }"
                  @blur="handleFieldBlur('email')"
                />
                <p v-if="validationErrors.email" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.email }}
                </p>
              </div>
              <div class="space-y-2" id="phone">
                <Label for="phone">Mobile Number <span class="text-red-500">*</span></Label>
                <Input 
                  id="phone" 
                  v-model="form.phone" 
                  placeholder="Enter 10-digit mobile number"
                  :class="{ 'border-red-500 focus:border-red-500': validationErrors.phone }"
                  @input="handlePhoneInput"
                  @blur="handleFieldBlur('phone')"
                  maxlength="10"
                />
                <p v-if="validationErrors.phone" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.phone }}
                </p>
              </div>
              <div class="space-y-2" id="age">
                <Label for="age">Age <span class="text-red-500">*</span></Label>
                <Input 
                  id="age" 
                  v-model="form.age" 
                  placeholder="Enter age" 
                  type="number" 
                  min="1" 
                  max="100"
                  :class="{ 'border-red-500 focus:border-red-500': validationErrors.age }"
                  @blur="handleFieldBlur('age')"
                />
                <p v-if="validationErrors.age" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.age }}
                </p>
              </div>
            </div>

            <!-- Academic Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2" id="dateOfBirth">
                <Label for="dateOfBirth">Date of Birth <span class="text-red-500">*</span></Label>
                <DatePicker
                  id="dateOfBirth"
                  v-model="dateOfBirthValue"
                  month-year-selector
                />
                <p v-if="validationErrors.dateOfBirth" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.dateOfBirth }}
                </p>
              </div>
              <div class="space-y-2" id="classId">
                <Label for="classId">Class <span class="text-red-500">*</span></Label>
                <SelectSearch
                  id="classId"
                  v-model="form.classId"
                  :options="classes"
                  :loading="isClassesLoading"
                  placeholder="Select Class"
                  :required="true"
                  :show-error="showValidation && !form.classId"
                  :error-message="validationErrors.classId || 'Please select a class'"
                  @blur="handleFieldBlur('classId')"
                />
              </div>
              <div class="space-y-2" id="sectionId">
                <Label for="sectionId">Section</Label>
                <SelectSearch
                  id="sectionId"
                  v-model="form.sectionId"
                  :options="section"
                  :loading="isSectionLoading"
                  placeholder="Select Section"
                />
              </div>
            </div>

            <!-- Family Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2" id="fatherName">
                <Label for="fatherName">Father Name <span class="text-red-500">*</span></Label>
                <Input 
                  id="fatherName" 
                  v-model="form.fatherName" 
                  placeholder="Enter father name"
                  :class="{ 'border-red-500 focus:border-red-500': validationErrors.fatherName }"
                  @blur="handleFieldBlur('fatherName')"
                />
                <p v-if="validationErrors.fatherName" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.fatherName }}
                </p>
              </div>
              <div class="space-y-2" id="motherName">
                <Label for="motherName">Mother Name</Label>
                <Input 
                  id="motherName" 
                  v-model="form.motherName" 
                  placeholder="Enter mother name" 
                />
              </div>
              <div class="space-y-2" id="guardianName">
                <Label for="guardianName">Guardian Name <span class="text-red-500">*</span></Label>
                <Input 
                  id="guardianName" 
                  v-model="form.guardianName" 
                  placeholder="Enter guardian name"
                  :class="{ 'border-red-500 focus:border-red-500': validationErrors.guardianName }"
                  @blur="handleFieldBlur('guardianName')"
                />
                <p v-if="validationErrors.guardianName" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.guardianName }}
                </p>
              </div>
            </div>

            <!-- Additional Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2" id="contactNumber">
                <Label for="contactNumber">Contact Number</Label>
                <Input 
                  id="contactNumber" 
                  v-model="form.contactNumber" 
                  placeholder="Enter contact number" 
                />
              </div>
              <div class="space-y-2" id="photo">
                <Label for="photo">Photo</Label>
                <Input 
                  id="photo" 
                  type="file" 
                  accept="image/*"
                  @change="handlePhotoChange" 
                />
              </div>
              <div class="space-y-2" id="joinedDate">
                <Label for="joinedDate">Joined Date <span class="text-red-500">*</span></Label>
                <DatePicker
                  id="joinedDate"
                  v-model="joinedDateValue"
                  month-year-selector
                />
                <p v-if="validationErrors.joinedDate" class="text-sm text-red-600 mt-1">
                  {{ validationErrors.joinedDate }}
                </p>
              </div>
            </div>

            <!-- Address Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2" id="address">
                <Label for="address">Address</Label>
                <Input 
                  id="address" 
                  v-model="form.address" 
                  placeholder="Enter address" 
                />
              </div>
              <div class="space-y-2" id="stateId">
                <Label for="stateId">State <span class="text-red-500">*</span></Label>
                <SelectSearch
                  id="stateId"
                  v-model="form.stateId"
                  :options="states"
                  :loading="isStateLoading"
                  placeholder="Select State"
                  :required="true"
                  :show-error="showValidation && !form.stateId"
                  :error-message="validationErrors.stateId || 'Please select a state'"
                  @blur="handleFieldBlur('stateId')"
                />
              </div>
              <div class="space-y-2" id="districtId">
                <Label for="districtId">District</Label>
                <SelectSearch
                  id="districtId"
                  v-model="form.districtId"
                  :options="districts"
                  :loading="isDistrictLoading"
                  :disabled="!form.stateId || isDistrictLoading"
                  placeholder="Select District"
                />
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2" id="municipalityId">
                <Label for="municipalityId">Municipality</Label>
                <SelectSearch
                  id="municipalityId"
                  v-model="form.municipalityId"
                  :options="municipalities"
                  :loading="isMunicipalityLoading"
                  :disabled="!form.districtId || isDistrictLoading || isMunicipalityLoading"
                  placeholder="Select Municipality"
                />
              </div>
            </div>

            <!-- Submit Button -->
            <!-- Other form sections unchanged -->
            <div class="flex justify-end pt-6">
              <Button 
                type="submit" 
                :disabled="isSubmitting"
                class="px-8 py-2 flex items-center justify-center"
              >
                <span v-if="isSubmitting" class="flex items-center">
                  <svg class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Submitting...
                </span>
                <span v-else>Submit Registration</span>
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
button[type="submit"],
button[role="button"] {
  cursor: pointer !important;
}

button[type="submit"]:hover,
button[role="button"]:hover {
  cursor: pointer !important;
}

.space-y-2 > label {
  font-weight: 500;
}

.text-red-500 {
  font-weight: 600;
}

.border-red-500 {
  border-color: rgb(239 68 68) !important;
}

.border-red-500:focus {
  border-color: rgb(239 68 68) !important;
  box-shadow: 0 0 0 1px rgb(239 68 68) !important;
}
</style>