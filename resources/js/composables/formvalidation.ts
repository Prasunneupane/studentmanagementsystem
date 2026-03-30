// composables/useFormValidation.ts
import { ref, reactive } from 'vue';

export interface ValidationRule {
  required?: boolean;
  pattern?: RegExp;
  min?: number;
  max?: number;
  message: string;
}

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

export function useFormValidation(form: any) {
  const validationErrors = ref<Record<string, string>>({});
  const showValidation = ref(false);

  const validateField = (fieldName: string): boolean => {
    const rule = validationRules[fieldName];
    const value = form[fieldName];

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
      if (isNaN(numValue) || 
          (rule.min !== undefined && numValue < rule.min) ||
          (rule.max !== undefined && numValue > rule.max)) {
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

  const validateAllFields = (): boolean => {
    let isValid = true;
    Object.keys(validationRules).forEach((field) => {
      if (!validateField(field)) {
        isValid = false;
      }
    });
    return isValid;
  };

  return {
    validationErrors,
    showValidation,
    validateField,
    validateAllFields
    // validateAllFields
    // showValidation
  };
}