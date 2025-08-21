<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import type { BreadcrumbItem } from '@/types';
import { getAllStates, getDistrictsByStateId, getMunicipalitiesByDistrictId, createStudent } from '@/constant/apiservice/apimethods';


const form = reactive({
  fName: '',
  mName: '',
  lName: '',
  email: '',
  phone: '',
  age: '',
  dateOfBirth: '',
  classId: null,
  sectionId: null,
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

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Add Student', href: '/student/create' }
];

// Fetch states on component mount
const fetchStates = async () => {
  try {
    const response = await getAllStates();
    console.log(response, "response");
    states.value = response.map((state) => ({
      value: state.id.toString(),
      label: state.name
    }));
  } catch (error) {
    console.error('Failed to fetch states:', error);
  }
};


watch(() => form.stateId, async (newState) => {
  districts.value = [];
  municipalities.value = [];
  form.districtId = null;
  form.municipalityId = null;

  if (newState && newState.value) {
    try {
      const response = await getDistrictsByStateId(newState.value);
      districts.value = response.map((district) => ({
        value: district.id.toString(),
        label: district.name || district.name
      }));
    } catch (error) {
      console.error('Failed to fetch districts:', error);
    }
  }
});

// Fetch municipalities when districtId changes
watch(() => form.districtId, async (newDistrict) => {
  municipalities.value = [];
  form.municipalityId = null;

  if (newDistrict && newDistrict.value) {
    try {
      const response = await getMunicipalitiesByDistrictId(newDistrict.value);
      municipalities.value = response.municipalities.map((municipality) => ({
        value: municipality.id.toString(),
        label: municipality.name
      }));
    } catch (error) {
      console.error('Failed to fetch municipalities:', error);
    }
  }
});

// Initialize states on mount
fetchStates();

// Placeholder options for classId and sectionId
const classes = ref([
  { value: '1', label: 'Class 1' },
  { value: '2', label: 'Class 2' },
  { value: '3', label: 'Class 3' }
]);

const sections = ref([
  { value: 'A', label: 'Section A' },
  { value: 'B', label: 'Section B' },
  { value: 'C', label: 'Section C' }
]);

// Handle file input for photo
const handlePhotoChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files.length > 0) {
    form.photo = input.files[0];
  }
};

// Handle form submission
const handleSubmit = async () => {
  try {
    const formData = new FormData();
    for (const [key, value] of Object.entries(form)) {
      if (value !== null && value !== '') {
        // For select fields, use value.value
        if (
          key === 'stateId' ||
          key === 'districtId' ||
          key === 'municipalityId' ||
          key === 'classId' ||
          key === 'sectionId'
        ) {
          if (typeof value === 'object' && value !== null && 'value' in value) {
            formData.append(key, value.value);
          }
        } else {
          formData.append(key, value as string | Blob);
        }
      }
    }

    const response = await createStudent(formData);
    console.log('Form submitted successfully:', response);
  } catch (error) {
    console.error('Form submission failed:', error);
  }
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
                <Input id="fName" v-model="form.fName" placeholder="Enter first name" />
              </div>
              <div class="space-y-2">
                <Label for="mName">Middle Name</Label>
                <Input id="mName" v-model="form.mName" placeholder="Enter middle name" />
              </div>
              <div class="space-y-2">
                <Label for="lName">Last Name</Label>
                <Input id="lName" v-model="form.lName" placeholder="Enter last name" />
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
                <Input id="phone" v-model="form.phone" placeholder="Enter mobile number" />
              </div>
              <div class="space-y-2">
                <Label for="age">Age <small>*</small></Label>
                <Input id="age" v-model="form.age" placeholder="Enter age" type="number" />
              </div>
            </div>

            <!-- Academic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="dateOfBirth">Date of Birth</Label>
                <Input id="dateOfBirth" v-model="form.dateOfBirth" placeholder="Enter date of birth" type="date" />
              </div>
              <div class="space-y-2">
                <Label for="classId">Class</Label>
                <SelectSearch id="classId" v-model="form.classId" :options="classes" placeholder="Select Class" />
              </div>
              <div class="space-y-2">
                <Label for="sectionId">Section</Label>
                <SelectSearch id="sectionId" v-model="form.sectionId" :options="sections" placeholder="Select Section" />
              </div>
            </div>

            <!-- Family Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="fatherName">Father Name</Label>
                <Input id="fatherName" v-model="form.fatherName" placeholder="Enter father name" />
              </div>
              <div class="space-y-2">
                <Label for="motherName">Mother Name</Label>
                <Input id="motherName" v-model="form.motherName" placeholder="Enter mother name" />
              </div>
              <div class="space-y-2">
                <Label for="guardianName">Guardian Name</Label>
                <Input id="guardianName" v-model="form.guardianName" placeholder="Enter guardian name" />
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
                <Input id="joinedDate" v-model="form.joinedDate" type="date" />
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
                <SelectSearch id="stateId" v-model="form.stateId" :options="states" placeholder="Select State" />
              </div>
              <div class="space-y-2">
                <Label for="districtId">District</Label>
                <SelectSearch id="districtId" v-model="form.districtId" :options="districts" placeholder="Select District" />
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="space-y-2">
                <Label for="municipalityId">Municipality</Label>
                <SelectSearch id="municipalityId" v-model="form.municipalityId" :options="municipalities" placeholder="Select Municipality" />
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