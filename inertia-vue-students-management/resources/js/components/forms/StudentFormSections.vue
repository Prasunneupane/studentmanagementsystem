<script setup lang="ts">
import { defineComponent, h } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import DatePicker from "@/components/ui/datepicker/DatePicker.vue";

interface Props {
  form: any;
  validationErrors: Record<string, string>;
  showValidation: boolean;
  states: { value: string; label: string }[];
  districts: { value: string; label: string }[];
  municipalities: { value: string; label: string }[];
  classes: { value: string; label: string }[];
  sections: { value: string; label: string }[];
  dateOfBirthValue: Date | null;
  joinedDateValue: Date | null;
  isSubmitting: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'field-blur': [fieldName: string];
  'phone-input': [event: Event];
  'photo-change': [event: Event];
  'submit': [];
  'update:date-of-birth': [value: Date | null];
  'update:joined-date': [value: Date | null];
}>();

// Input field component with validation
const FormField = defineComponent({
  props: ['id', 'label', 'required', 'error', 'type', 'placeholder', 'maxlength'],
  setup(props, { slots }) {
    return () => h('div', { class: 'space-y-2', id: props.id }, [
      h(Label, { for: props.id }, [
        props.label,
        props.required && h('span', { class: 'text-red-500' }, '*')
      ]),
      slots.default?.(),
      props.error && h('p', { class: 'text-sm text-red-600 mt-1' }, props.error)
    ]);
  }
});
</script>

<template>
  <form @submit.prevent="$emit('submit')" class="space-y-6">
    <!-- Personal Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- First Name -->
      <div class="space-y-2" id="fName">
        <Label for="fName">First Name <span class="text-red-500">*</span></Label>
        <Input 
          id="fName" 
          v-model="form.fName" 
          placeholder="Enter first name"
          :class="{ 'border-red-500 focus:border-red-500': validationErrors.fName }"
          @blur="$emit('field-blur', 'fName')"
        />
        <p v-if="validationErrors.fName" class="text-sm text-red-600 mt-1">
          {{ validationErrors.fName }}
        </p>
      </div>

      <!-- Middle Name -->
      <div class="space-y-2">
        <Label for="mName">Middle Name</Label>
        <Input id="mName" v-model="form.mName" placeholder="Enter middle name" />
      </div>

      <!-- Last Name -->
      <div class="space-y-2" id="lName">
        <Label for="lName">Last Name <span class="text-red-500">*</span></Label>
        <Input 
          id="lName" 
          v-model="form.lName" 
          placeholder="Enter last name"
          :class="{ 'border-red-500 focus:border-red-500': validationErrors.lName }"
          @blur="$emit('field-blur', 'lName')"
        />
        <p v-if="validationErrors.lName" class="text-sm text-red-600 mt-1">
          {{ validationErrors.lName }}
        </p>
      </div>
    </div>

    <!-- Contact Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Email -->
      <div class="space-y-2" id="email">
        <Label for="email">Email</Label>
        <Input 
          id="email" 
          v-model="form.email" 
          placeholder="Enter email address" 
          type="email"
          :class="{ 'border-red-500 focus:border-red-500': validationErrors.email }"
          @blur="$emit('field-blur', 'email')"
        />
        <p v-if="validationErrors.email" class="text-sm text-red-600 mt-1">
          {{ validationErrors.email }}
        </p>
      </div>

      <!-- Phone -->
      <div class="space-y-2" id="phone">
        <Label for="phone">Mobile Number <span class="text-red-500">*</span></Label>
        <Input 
          id="phone" 
          v-model="form.phone" 
          placeholder="Enter 10-digit mobile number"
          :class="{ 'border-red-500 focus:border-red-500': validationErrors.phone }"
          @input="$emit('phone-input', $event)"
          @blur="$emit('field-blur', 'phone')"
          maxlength="10"
        />
        <p v-if="validationErrors.phone" class="text-sm text-red-600 mt-1">
          {{ validationErrors.phone }}
        </p>
      </div>

      <!-- Age -->
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
          @blur="$emit('field-blur', 'age')"
        />
        <p v-if="validationErrors.age" class="text-sm text-red-600 mt-1">
          {{ validationErrors.age }}
        </p>
      </div>
    </div>

    <!-- Academic Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Date of Birth -->
      <div class="space-y-2" id="dateOfBirth">
        <Label for="dateOfBirth">Date of Birth <span class="text-red-500">*</span></Label>
        <DatePicker
          id="dateOfBirth"
          :model-value="dateOfBirthValue"
          @update:model-value="$emit('update:date-of-birth', $event)"
          month-year-selector
        />
        <p v-if="validationErrors.dateOfBirth" class="text-sm text-red-600 mt-1">
          {{ validationErrors.dateOfBirth }}
        </p>
      </div>

      <!-- Class -->
      <div class="space-y-2" id="classId">
        <Label for="classId">Class <span class="text-red-500">*</span></Label>
        <SelectSearch
          id="classId"
          v-model="form.classId"
          :options="classes"
          placeholder="Select Class"
          :required="true"
          :show-error="showValidation && !form.classId"
          :error-message="validationErrors.classId || 'Please select a class'"
          @blur="$emit('field-blur', 'classId')"
        />
      </div>

      <!-- Section -->
      <div class="space-y-2" id="sectionId">
        <Label for="sectionId">Section</Label>
        <SelectSearch
          id="sectionId"
          v-model="form.sectionId"
          :options="sections"
          placeholder="Select Section"
        />
      </div>
    </div>

    <!-- Family Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Father Name -->
      <div class="space-y-2" id="fatherName">
        <Label for="fatherName">Father Name <span class="text-red-500">*</span></Label>
        <Input 
          id="fatherName" 
          v-model="form.fatherName" 
          placeholder="Enter father name"
          :class="{ 'border-red-500 focus:border-red-500': validationErrors.fatherName }"
          @blur="$emit('field-blur', 'fatherName')"
        />
        <p v-if="validationErrors.fatherName" class="text-sm text-red-600 mt-1">
          {{ validationErrors.fatherName }}
        </p>
      </div>

      <!-- Mother Name -->
      <div class="space-y-2">
        <Label for="motherName">Mother Name</Label>
        <Input id="motherName" v-model="form.motherName" placeholder="Enter mother name" />
      </div>

      <!-- Guardian Name -->
      <div class="space-y-2" id="guardianName">
        <Label for="guardianName">Guardian Name <span class="text-red-500">*</span></Label>
        <Input 
          id="guardianName" 
          v-model="form.guardianName" 
          placeholder="Enter guardian name"
          :class="{ 'border-red-500 focus:border-red-500': validationErrors.guardianName }"
          @blur="$emit('field-blur', 'guardianName')"
        />
        <p v-if="validationErrors.guardianName" class="text-sm text-red-600 mt-1">
          {{ validationErrors.guardianName }}
        </p>
      </div>
    </div>

    <!-- Additional Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Contact Number -->
      <div class="space-y-2">
        <Label for="contactNumber">Contact Number</Label>
        <Input id="contactNumber" v-model="form.contactNumber" placeholder="Enter contact number" />
      </div>

      <!-- Photo -->
      <div class="space-y-2">
        <Label for="photo">Photo</Label>
        <Input 
          id="photo" 
          type="file" 
          accept="image/*"
          @change="$emit('photo-change', $event)"
        />
      </div>

      <!-- Joined Date -->
      <div class="space-y-2" id="joinedDate">
        <Label for="joinedDate">Joined Date <span class="text-red-500">*</span></Label>
        <DatePicker
          id="joinedDate"
          :model-value="joinedDateValue"
          @update:model-value="$emit('update:joined-date', $event)"
          month-year-selector
        />
        <p v-if="validationErrors.joinedDate" class="text-sm text-red-600 mt-1">
          {{ validationErrors.joinedDate }}
        </p>
      </div>
    </div>

    <!-- Address Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Address -->
      <div class="space-y-2">
        <Label for="address">Address</Label>
        <Input id="address" v-model="form.address" placeholder="Enter address" />
      </div>

      <!-- State -->
      <div class="space-y-2" id="stateId">
        <Label for="stateId">State <span class="text-red-500">*</span></Label>
        <SelectSearch
          id="stateId"
          v-model="form.stateId"
          :options="states"
          placeholder="Select State"
          :required="true"
          :show-error="showValidation && !form.stateId"
          :error-message="validationErrors.stateId || 'Please select a state'"
          @blur="$emit('field-blur', 'stateId')"
        />
      </div>

      <!-- District -->
      <div class="space-y-2" id="districtId">
        <Label for="districtId">District</Label>
        <SelectSearch
          id="districtId"
          v-model="form.districtId"
          :options="districts"
          :disabled="!form.stateId"
          placeholder="Select District"
        />
      </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Municipality -->
      <div class="space-y-2" id="municipalityId">
        <Label for="municipalityId">Municipality</Label>
        <SelectSearch
          id="municipalityId"
          v-model="form.municipalityId"
          :options="municipalities"
          :disabled="!form.districtId"
          placeholder="Select Municipality"
        />
      </div>
    </div>

    <!-- Submit Button -->
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