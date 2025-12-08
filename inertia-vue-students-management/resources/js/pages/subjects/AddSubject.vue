<script setup lang="ts">
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Loader2, ArrowLeft, BookOpen } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css'
import { useToast } from '@/composables/useToast'

const { toast } = useToast()

const breadcrumbs = [
  { title: 'Subjects', href: '/subjects' },
  { title: 'Add Subject', href: '/subjects/create' }
]
const defaultSubjectType = { value: 'core', label: 'Core' }
// Form
const form = useForm({
  name: '',
  code: '',
  type: defaultSubjectType, // default selected
  description: '',
  is_active: '1', // default active
})

const errors = ref<Record<string, string>>({})
let sections = [
    { value: 'core', label: 'Core' },
    { value: 'elective', label: 'Elective' },
    { value: 'optional', label: 'Optional' },
];
const submit = () => {
  errors.value = {}

  if (!form.name.trim()) errors.value.name = 'Subject name is required'
  if (!form.code.trim()) errors.value.code = 'Subject code is required'
  if (!form.type) errors.value.type = 'Please select a subject type'

  if (Object.keys(errors.value).length > 0) {
    toast.error("Please fill in all required fields.", {
            duration: 3000,
            action: {
              label: 'Close',
              onClick: (e) => {
                // Close action
              }
            }
          });
    // toast({
    //   title: 'Validation Error',
    //   description: 'Please fill in all required fields.',
    //   variant: 'destructive',
    // })
    return
  }

  form.post(route('subjects.store'), {
    onSuccess: () => {
         toast.success("Subject added successfully.", {
            duration: 3000,
            action: {
              label: 'Close',
              onClick: (e) => {
                // Close action
              }
            }
          });
    //   toast({
    //     title: 'Success!',
    //     description: 'Subject added successfully.',
    //   })
      form.reset()
      form.type = defaultSubjectType // reset to default
      form.is_active = '1'
    },
    onError: (err) => {
      errors.value = err
       toast.error("Failed to add subject.", {
            duration: 3000,
            action: {
              label: 'Close',
              onClick: (e) => {
                // Close action
              }
            }
          });
    //   toast({
    //     title: 'Error',
    //     description: 'Failed to add subject.',
    //     variant: 'destructive',
    //   })
    },
  })
}
</script>

<template>
  <Head title="Add Subject" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <Toaster position="top-right"/>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <!-- Header -->
      <Card>
        <CardHeader>
          <CardTitle>Subject Information</CardTitle>
          <CardDescription>
            Fill in the details below to add a new subject.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-8">
            <!-- Row 1: Name + Code -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="name">
                  Subject Name <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="name"
                  v-model="form.name"
                  placeholder="e.g. Mathematics"
                  :class="{ 'border-red-500': errors.name || form.errors.name }"
                />
                <p v-if="errors.name || form.errors.name" class="text-sm text-red-600">
                  {{ errors.name || form.errors.name }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="code">
                  Subject Code <span class="text-red-500">*</span>
                </Label>
                <Input
                  id="code"
                  v-model="form.code"
                  placeholder="e.g. MATH101"
                  :class="{ 'border-red-500': errors.code || form.errors.code }"
                />
                <p v-if="errors.code || form.errors.code" class="text-sm text-red-600">
                  {{ errors.code || form.errors.code }}
                </p>
              </div>
            </div>

            <!-- Row 2: Type + Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label for="type">
                  Subject Type <span class="text-red-500">*</span>
                </Label>
               <SelectSearch
                    id="sectionId"
                    v-model="form.type"
                    
                    :options="sections"
                    placeholder="Select subject type"
                    />
                <p v-if="errors.type || form.errors.type" class="text-sm text-red-600">
                  {{ errors.type || form.errors.type }}
                </p>
              </div>

              <div class="space-y-3">
                <Label>Status <span class="text-red-500">*</span></Label>
                <RadioGroup v-model="form.is_active" class="flex flex-row gap-8">
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem value="1" id="active" />
                    <Label for="active" class="cursor-pointer font-normal">Active</Label>
                  </div>
                  <div class="flex items-center space-x-2">
                    <RadioGroupItem value="0" id="inactive" />
                    <Label for="inactive" class="cursor-pointer font-normal">Inactive</Label>
                  </div>
                </RadioGroup>
              </div>
            </div>

            <!-- Description -->
            <div class="space-y-2">
              <Label for="description">Description (Optional)</Label>
              <Textarea
                id="description"
                v-model="form.description"
                placeholder="Brief description about the subject..."
                rows="4"
              />
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button type="button" variant="outline" as-child>
                <!-- <Link :href="route('subject.index')">Cancel</Link> -->
              </Button>
              <Button type="submit" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ form.processing ? 'Saving...' : 'Add Subject' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Optional: Improve select trigger appearance when invalid */
</style>