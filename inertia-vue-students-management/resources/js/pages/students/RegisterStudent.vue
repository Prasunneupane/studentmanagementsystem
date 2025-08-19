<script setup lang="ts">
import { ref } from "vue"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Button } from "@/components/ui/button"
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card"
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
// Import custom select
import SelectItem from "@/components/ui/select/Select-Item.vue"
import Select from "@/components/ui/select/Select.vue"
import SelectSearch from "@/components/ui/select/Select-Search.vue"
// import SelectSearch from "@/components/ui/select/Select-Search.vue"

const fName = ref("")
const mName = ref("")
const lName = ref("")
const email = ref("")
const phone = ref("")
const age = ref("")
const dateOfBirth = ref("")
const classId = ref(null)
const sectionId = ref(null)
const motherName = ref("")
const fatherName = ref("")
const guardianName = ref("")
const contactNumber = ref("")
const photo = ref("")
const joinedDate = ref(new Date().toISOString().split('T')[0]) // Default to today's date
const address =ref(null)
const stateId = ref(null)
const districtId = ref(null)
const municipalityId = ref(null)
const selectedFramework = ref(null)
// const country = ref("")
const city = ref("")
const allCities = ["Kathmandu", "Pokhara", "Butwal", "Delhi", "Tokyo", "New York"]
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Add Student',
    href: '/student/create',
  },

];

const frameworks = [
  { value: "next.js", label: "Next.js" },
  { value: "sveltekit", label: "SvelteKit" },
  { value: "nuxt", label: "Nuxt" },
  { value: "remix", label: "Remix" },
  { value: "astro", label: "Astro" },
]

const handleSubmit = () => {
  console.log("Form submitted:", {
    fName: fName.value,
    mName: mName.value,
    lName: lName.value,
    classId: classId.value,
    sectionId: sectionId.value,
    email: email.value,
    phone: phone.value,
    age: age.value,
    dateOfBirth: dateOfBirth.value,
    motherName: motherName.value,
    fatherName: fatherName.value,
    guardianName: guardianName.value,
    contactNumber: contactNumber.value,
    photo: photo.value,
    joinedDate: joinedDate.value,
    address: address.value,
    stateId: stateId.value,
    districtId: districtId.value,
    municipalityId: municipalityId.value,

  })
}
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
                <Label for="name">First Name</Label>
                <Input id="name" class="w-full" v-model="fName" placeholder="Enter your name" />
              </div>

              <div class="space-y-2">
                <Label for="name">Middle Name</Label>
                <Input id="name" class="w-full" v-model="mName" placeholder="Enter your name" />
              </div>

              <div class="space-y-2">
                <Label for="name">Last Name</Label>
                <Input id="name" class="w-full" v-model="lName" placeholder="Enter your name" />
              </div>
            </div>

            <!-- Country Select -->
            <!-- <div class="space-y-2">
            <Label for="country">Country</Label>
            <Select v-model="country">
            <SelectItem value="nepal">Nepal</SelectItem>
            <SelectItem value="india">India</SelectItem>
            <SelectItem value="usa">USA</SelectItem>
            </Select>
            </div> -->

            <!-- Searchable Select -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              
              <div class="space-y-2">
                <Label for="name">Email</Label>
                <Input id="name" class="w-full" v-model="email" placeholder="Enter your name" />
              </div>

              <div class="space-y-2">
                <Label for="name">Mobile No</Label>
                <Input id="name" class="w-full" v-model="phone" placeholder="Enter your name" />
              </div>

              <div class="space-y-2">
                <Label for="name">Age <small>*</small></Label>
                <Input id="name" class="w-full" v-model="age" placeholder="Enter your name" />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              
              <div class="space-y-2">
                <Label for="name">Date Of Birth</Label>
                <Input id="name" class="w-full" v-model="dateOfBirth" placeholder="Enter your date of birth" />
              </div>
              
              <div class="space-y-2 w-full">
                <Label for="class">Class</Label>
                <SelectSearch id="class" v-model="classId" :options="frameworks" placeholder="Select Class" />
              </div>

               <div class="space-y-2 w-full">
                <Label for="class">Section</Label>
                <SelectSearch id="section" v-model="sectionId" :options="frameworks" placeholder="Select Section" />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              
              <div class="space-y-2">
                <Label for="name">Father Name</Label>
                <Input id="name" class="w-full" v-model="fatherName" placeholder="Enter your father name" />
              </div>

              <div class="space-y-2">
                <Label for="name">Mother Name</Label>
                <Input id="name" class="w-full" v-model="motherName" placeholder="Enter your mother name" />
              </div>

              <div class="space-y-2">
                <Label for="name">Guardian Name </Label>
                <Input id="name" class="w-full" v-model="guardianName" placeholder="Enter your guardian name" />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              
              <div class="space-y-2">
                <Label for="name">Contact Number</Label>
                <Input id="name" class="w-full" v-model="contactNumber" placeholder="Enter your name" />
              </div>

              <div class="space-y-2">
                <Label for="name">Photo</Label>
                <Input id="name" class="w-full" v-model="photo" type="file" placeholder="Enter yourphoto" />
              </div>

              <div class="space-y-2">
                <Label for="name">Joined Date</Label>
                <Input id="name" class="w-full" v-model="joinedDate" placeholder="Enter your name" />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              
              <div class="space-y-2">
                <Label for="name">Address</Label>
                <SelectSearch id="section" v-model="address" :options="frameworks" placeholder="Select Class" />
              </div>

              <div class="space-y-2">
                <Label for="name">State</Label>
                <SelectSearch id="section" v-model="stateId" :options="frameworks" placeholder="Select Class" />
              </div>

              <div class="space-y-2">
                <Label for="name">District</Label>
                <SelectSearch id="section" v-model="districtId" :options="frameworks" placeholder="Select Class" />
              </div>
              <div class="space-y-2">
                <Label for="name">Municipality</Label>
                <SelectSearch id="section" v-model="municipalityId" :options="frameworks" placeholder="Select Class" />
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
