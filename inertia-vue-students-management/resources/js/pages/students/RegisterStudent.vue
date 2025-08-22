<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import type { BreadcrumbItem } from '@/types';
import { 
  getAllStates, 
  getDistrictsByStateId, 
  getMunicipalitiesByDistrictId, 
  createStudent,
  getClassesList,
  getSectionList
 } from '@/constant/apiservice/callService';

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
  joinedDate: new Date().toISOString().split('T')[0],
  address: '',
  stateId: null as { value: string; label: string } | null,
  districtId: null as { value: string; label: string } | null,
  municipalityId: null as { value: string; label: string } | null
});


const states = ref<{ value: string; label: string }[]>([]);
const districts = ref<{ value: string; label: string }[]>([]);
const municipalities = ref<{ value: string; label: string }[]>([]);
const classes = ref<{ value: string; label: string }[]>([]);
const section = ref<{ value: string; label: string }[]>([]);

const isStateLoading = ref(false)
const isDistrictLoading = ref(false)
const isMunicipalityLoading = ref(false)
const isClassesLoading = ref(false)
const isSectionLoading = ref(false)
const showValidation = ref(false)
const selectedState = ref(null)
// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Add Student', href: '/student/create' }
];

// Fetch states on component mount
const fetchStates = async () => {
  isStateLoading.value = true
  try {
    const response = await getAllStates()
    states.value = response.map((state) => ({
      value: state.id.toString(),
      label: state.name
    }))
  } catch (error) {
    console.error('Failed to fetch states:', error)
  } finally {
    isStateLoading.value = false
  }
}

const fetchClasses = async () => {
  isClassesLoading.value = true
  try {
    const response = await getClassesList()
    classes.value = response.classesList.map((cls) => ({
      value: cls.id.toString(),
      label: cls.name
    }))
  } catch (error) {
    console.error('Failed to fetch classes:', error)
    return []
  } finally {
    isClassesLoading.value = false
  }
}

const fetchSection = async () => {
  isSectionLoading.value = true
  try {
    const response = await getSectionList()
    section.value = response.sectionList.map((sec) => ({
      value: sec.id.toString(),
      label: sec.name
    }))
  } catch (error) {
    console.error('Failed to fetch sections:', error)
    return []
  } finally {
    isSectionLoading.value = false
  }
}


watch(() => form.stateId, async (newState) => {
  districts.value = []
  municipalities.value = []
  form.districtId = null
  form.municipalityId = null

  if (newState && newState.value) {
    isDistrictLoading.value = true
    try {
      const response = await getDistrictsByStateId(newState.value)
      districts.value = response.map((district) => ({
        value: district.id.toString(),
        label: district.name || district.name
      }))
    } catch (error) {
      console.error('Failed to fetch districts:', error)
    } finally {
      isDistrictLoading.value = false
    }
  }
})

// Fetch municipalities when districtId changes
watch(() => form.districtId, async (newDistrict) => {
  municipalities.value = []
  form.municipalityId = null

  if (newDistrict && newDistrict.value) {
    isMunicipalityLoading.value = true
    try {
      const response = await getMunicipalitiesByDistrictId(newDistrict.value)
      municipalities.value = response.municipalities.map((municipality) => ({
        value: municipality.id.toString(),
        label: municipality.name
      }))
    } catch (error) {
      console.error('Failed to fetch municipalities:', error)
    } finally {
      isMunicipalityLoading.value = false
    }
  }
})

const validateField = () => {
  showValidation.value = true
}

// Initialize states on mount
fetchStates();
fetchClasses();
fetchSection();
// Placeholder options for classId and sectionId
// const classes = ref([
//   { value: '1', label: 'Class 1' },
//   { value: '2', label: 'Class 2' },
//   { value: '3', label: 'Class 3' }
// ]);

// const sections = ref([
//   { value: 'A', label: 'Section A' },
//   { value: 'B', label: 'Section B' },
//   { value: 'C', label: 'Section C' }
// ]);

// Handle file input for photo
const handlePhotoChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    form.photo = input.files[0];
  }
};

// Handle form submission
const handleSubmit = async () => {
  // try {
  //   const formData = new FormData();
  //   for (const [key, value] of Object.entries(form)) {
  //     if (value !== null && value !== '') {
  //       // For select fields, use value.value
  //       if (
  //         key === 'stateId' ||
  //         key === 'districtId' ||
  //         key === 'municipalityId' ||
  //         key === 'classId' ||
  //         key === 'sectionId'
  //       ) {
  //         if (typeof value === 'object' && value !== null && 'value' in value) {
  //           formData.append(key, value.value);
  //         }
  //       } else {
  //         formData.append(key, value as string | Blob);
  //       }
  //     }
  //   }

  //   useForm({
  //     formData,
  //     onFinish: () => {
  //       // Reset form after submission
  //       for (const key in form) {
  //         if (key === 'photo') {
  //           form[key] = null; // Reset photo to null
  //         } else {
  //           form[key] = '';
  //         }
  //       }
  //     }
    
  // });

  //   console.log(formData,"formdata");
    

  //   //  form.post(route('login'), {
  //   //     onFinish: () => form.reset('password'),
  //   // });
  // } catch (error) {
  //   console.error('Form submission failed:', error);
  // }
  showValidation.value = true
  
  if (!selectedState.value) {
    // Focus the field or show additional error handling
    return
  }

  console.log(form,"form");
  
  // form.post(route('student/register'), { // Replace with your registration route
  //   onSuccess: () => console.log('Form submitted successfully'),
  //   onError: (errors) => console.error('Form submission errors:', errors),
  //   onFinish: () => form.reset() // Optional: Reset form after submission
  // })

};
</script>

<template>
  <Head title="Student Register" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <Card class="w-full shadow-lg rounded-2xl">
        <CardHeader>
          <CardTitle class="text-xl font-bold">User Form</CardTitle>
        </CardHeader>
        <CardContent class="w-full">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Name Input -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="fName">First Name</Label>
                <Input id="fName" v-model="form.fName" placeholder="Enter first name" required/>
              </div>
              <div class="space-y-2">
                <Label for="mName">Middle Name</Label>
                <Input id="mName" v-model="form.mName" placeholder="Enter middle name" />
              </div>
              <div class="space-y-2">
                <Label for="lName">Last Name</Label>
                <Input id="lName" v-model="form.lName" placeholder="Enter last name" required/>
              </div>
            </div>

            <!-- Contact Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" placeholder="Enter email" type="email" />
              </div>
              <div class="space-y-2">
                <Label for="phone">Mobile No</Label>
                <Input id="phone" v-model="form.phone" placeholder="Enter mobile number" required/>
              </div>
              <div class="space-y-2">
                <Label for="age">Age <small>*</small></Label>
                <Input id="age" v-model="form.age" placeholder="Enter age" type="number" required/>
              </div>
            </div>

            <!-- Academic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="dateOfBirth">Date of Birth</Label>
                <Input id="dateOfBirth" v-model="form.dateOfBirth" placeholder="Enter date of birth" type="date" required />
              </div>
              <div class="space-y-2">
                <Label for="classId">Class</Label>
                <SelectSearch
                id="classId"
                v-model="form.classId"
                :options="classes"
                :loading="isClassesLoading"
                placeholder="Select Class"
                required
              />
              </div>
              <div class="space-y-2">
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

            <!-- Family Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="fatherName">Father Name</Label>
                <Input id="fatherName" v-model="form.fatherName" placeholder="Enter father name" required />
              </div>
              <div class="space-y-2">
                <Label for="motherName">Mother Name</Label>
                <Input id="motherName" v-model="form.motherName" placeholder="Enter mother name" />
              </div>
              <div class="space-y-2">
                <Label for="guardianName">Guardian Name</Label>
                <Input id="guardianName" v-model="form.guardianName" placeholder="Enter guardian name" required />
              </div>
            </div>

            <!-- Additional Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="contactNumber">Contact Number</Label>
                <Input id="contactNumber" v-model="form.contactNumber" placeholder="Enter contact number" />
              </div>
              <div class="space-y-2">
                <Label for="photo">Photo</Label>
                <Input id="photo" type="file" @change="handlePhotoChange" />
              </div>
              <div class="space-y-2">
                <Label for="joinedDate">Joined Date</Label>
                <Input id="joinedDate" v-model="form.joinedDate" type="date" required />
              </div>
            </div>
                
            <!-- Address Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="address">Address</Label>
                <Input id="address" v-model="form.address" placeholder="Enter address" />
              </div>
              <div class="space-y-2">
                <Label for="stateId">State</Label>
                <SelectSearch
                id="stateId"
                v-model="form.stateId"
                :options="states"
                :loading="isStateLoading"
                placeholder="Select State"
                :required="true"
                :show-error="showValidation"
                error-message="Please select a state"
                @blur="validateField"
              />
              </div>
              <div class="space-y-2">
                <Label for="districtId">District</Label>
                <SelectSearch
                id="districtId"
                v-model="form.districtId"
                :options="districts"
                :loading="isDistrictLoading"
                :disabled="isDistrictLoading"
                placeholder="Select District"
              />
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="municipalityId">Municipality</Label>
                <SelectSearch
                id="municipalityId"
                v-model="form.municipalityId"
                :options="municipalities"
                :loading="isMunicipalityLoading"
                :disabled="isDistrictLoading || isMunicipalityLoading"
                placeholder="Select Municipality"
              />
              </div>
            </div>

            <!-- Submit Button -->
            <Button type="submit" class="float-right">Submit</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>