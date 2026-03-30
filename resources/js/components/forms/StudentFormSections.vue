<script setup lang="ts">
import { defineComponent, h, ref, watch } from "vue";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import SelectSearch from "@/components/ui/select/Select-Search.vue";
import DatePicker from "@/components/ui/datepicker/DatePicker.vue";
import { LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';

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
  'validate-guardian': [index: number, field: string];
  'update:guardians': [guardians: Guardian[]];
}>();

interface Guardian {
  guardianname: string;
  relation: string;
  phone: string;
  email: string;
  occupation: string;
  address: string;
  is_primary_contact: boolean;
}

// Reactive guardians array
const guardians = ref<Guardian[]>([
  {
    guardianname: '',
    relation: '',
    phone: '',
    email: '',
    occupation: '',
    address: '',
    is_primary_contact: false,
  },
]);

// Add new guardian
const addGuardian = () => {
  guardians.value.push({
    guardianname: '',
    relation: '',
    phone: '',
    email: '',
    occupation: '',
    address: '',
    is_primary_contact: false,
  });
};

// Remove guardian (keep at least one)
const removeGuardian = (index: number) => {
  if (guardians.value.length > 1) {
    guardians.value.splice(index, 1);
    // Update form.guardians
    props.form.guardians = guardians.value;
  }
};

// Watch guardians and sync with form
watch(guardians, (newGuardians) => {
  props.form.guardians = newGuardians;
  emit('update:guardians', newGuardians);
}, { deep: true });

// Method to reset guardians (called from parent)
const resetGuardians = () => {
  guardians.value = [
    {
      guardianname: '',
      relation: '',
      phone: '',
      email: '',
      occupation: '',
      address: '',
      is_primary_contact: false,
    },
  ];
  props.form.guardians = guardians.value;
};

// Expose reset method to parent
defineExpose({
  resetGuardians
});

// Handle guardian field blur
const handleGuardianBlur = (index: number, field: string) => {
  if (props.showValidation) {
    emit('validate-guardian', index, field);
  }
};

// Handle guardian phone input
const handleGuardianPhoneInput = (event: Event, index: number) => {
  const input = event.target as HTMLInputElement;
  if (input.value.length > 15) {
    input.value = input.value.slice(0, 15);
    guardians.value[index].phone = input.value;
  }
};

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

    <!-- Guardian Information -->
    <div class="space-y-6 mt-6">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">Guardian Information</h3>
        <Button
          type="button"
          variant="outline"
          size="sm"
          @click="addGuardian"
          class="flex items-center gap-2 cursor-pointer"
        >
          <Plus class="h-4 w-4" />
          Add Guardian
        </Button>
      </div>

      <div v-for="(guardian, index) in guardians" :key="index" class="border rounded-lg p-5 space-y-5 relative bg-gray-50">
        <!-- Remove Button (except first) -->
        <Button
          v-if="guardians.length > 1"
          type="button"
          variant="ghost"
          size="icon"
          class="absolute top-2 right-2 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer"
          @click="removeGuardian(index)"
          title="Remove Guardian"
        >
          <Trash2 class="h-4 w-4" />
        </Button>

        <div class="mb-2">
          <span class="text-sm font-medium text-gray-600">Guardian {{ index + 1 }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Guardian Name -->
          <div class="space-y-2">
            <Label :for="`guardian-name-${index}`">
              Name <span class="text-red-500">*</span>
            </Label>
            <Input
              :id="`guardian-name-${index}`"
              v-model="guardian.guardianname"
              placeholder="Enter guardian name"
              :class="{ 'border-red-500 focus:border-red-500': validationErrors[`guardians.${index}.guardianname`] }"
              @blur="handleGuardianBlur(index, 'guardianname')"
            />
            <p v-if="validationErrors[`guardians.${index}.guardianname`]" class="text-sm text-red-600 mt-1">
              {{ validationErrors[`guardians.${index}.guardianname`] }}
            </p>
          </div>

          <!-- Relation -->
          <div class="space-y-2">
            <Label :for="`guardian-relation-${index}`">
              Relation <span class="text-red-500">*</span>
            </Label>
            <Input
              :id="`guardian-relation-${index}`"
              v-model="guardian.relation"
              placeholder="e.g. Father, Mother"
              :class="{ 'border-red-500 focus:border-red-500': validationErrors[`guardians.${index}.relation`] }"
              @blur="handleGuardianBlur(index, 'relation')"
            />
            <p v-if="validationErrors[`guardians.${index}.relation`]" class="text-sm text-red-600 mt-1">
              {{ validationErrors[`guardians.${index}.relation`] }}
            </p>
          </div>

          <!-- Phone -->
          <div class="space-y-2">
            <Label :for="`guardian-phone-${index}`">
              Phone <span class="text-red-500">*</span>
            </Label>
            <Input
              :id="`guardian-phone-${index}`"
              v-model="guardian.phone"
              placeholder="Enter phone (10 digits)"
              maxlength="15"
              :class="{ 'border-red-500 focus:border-red-500': validationErrors[`guardians.${index}.phone`] }"
              @input="handleGuardianPhoneInput($event, index)"
              @blur="handleGuardianBlur(index, 'phone')"
            />
            <p v-if="validationErrors[`guardians.${index}.phone`]" class="text-sm text-red-600 mt-1">
              {{ validationErrors[`guardians.${index}.phone`] }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Email -->
          <div class="space-y-2">
            <Label :for="`guardian-email-${index}`">Email</Label>
            <Input
              :id="`guardian-email-${index}`"
              v-model="guardian.email"
              type="email"
              placeholder="guardian@example.com"
              :class="{ 'border-red-500 focus:border-red-500': validationErrors[`guardians.${index}.email`] }"
              @blur="handleGuardianBlur(index, 'email')"
            />
            <p v-if="validationErrors[`guardians.${index}.email`]" class="text-sm text-red-600 mt-1">
              {{ validationErrors[`guardians.${index}.email`] }}
            </p>
          </div>

          <!-- Occupation -->
          <div class="space-y-2">
            <Label :for="`guardian-occupation-${index}`">Occupation</Label>
            <Input
              :id="`guardian-occupation-${index}`"
              v-model="guardian.occupation"
              placeholder="e.g. Teacher, Engineer"
            />
          </div>

          <!-- Primary Contact -->
          <div class="space-y-2 flex items-end">
            <div class="flex items-center gap-2 pb-2">
              <input
                :id="`guardian-primary-${index}`"
                type="checkbox"
                v-model="guardian.is_primary_contact"
                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <Label :for="`guardian-primary-${index}`" class="font-normal cursor-pointer">
                Primary Contact
              </Label>
            </div>
          </div>
        </div>

        <!-- Address -->
        <div class="space-y-2">
          <Label :for="`guardian-address-${index}`">Address</Label>
          <Input
            :id="`guardian-address-${index}`"
            v-model="guardian.address"
            placeholder="Enter full address"
          />
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <Button type="submit" class="mt-4 float-right" :tabindex="4" :disabled="form.processing">
      <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
      {{ form.processing ? 'Submitting...' : 'Submit' }}
    </Button>
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