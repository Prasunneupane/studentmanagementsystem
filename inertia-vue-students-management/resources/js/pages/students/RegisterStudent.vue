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

const name = ref("")
const country = ref("")
const selectedFramework = ref("Select Class")
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
  console.log("Form submitted:", { name: name.value, country: country.value })
}
</script>

<template>
<Head title="Student Register" />
<AppLayout :breadcrumbs="breadcrumbs">
  <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
    <Card class="w-full max-w-md shadow-lg rounded-2xl">
      <CardHeader>
        <CardTitle class="text-xl font-bold">User Form</CardTitle>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Name Input -->
          <div class="space-y-2">
            <Label for="name">First Name</Label>
            <Input id="name" class="w-full" v-model="name" placeholder="Enter your name" />
          </div>

          <div class="space-y-2">
            <Label for="name">Middle Name</Label>
            <Input id="name" class="w-full" v-model="name" placeholder="Enter your name" />
          </div>

          <div class="space-y-2">
            <Label for="name">Last Name</Label>
            <Input id="name" class="w-full" v-model="name" placeholder="Enter your name" />
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
      <div class="space-y-2 w-full">
        <Label for="class" >Class</Label>
        <SelectSearch id="class"
            v-model="selectedFramework"
            :options="frameworks"
            placeholder="Select Class"
            class="w-full"
        />
      </div>
          <!-- Submit Button -->
          <Button type="submit" class="w-full">Submit</Button>
        </form>
      </CardContent>
    </Card>
  </div>
  </AppLayout>
</template>

