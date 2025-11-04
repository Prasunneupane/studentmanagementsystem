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
  joinedDate: { required: true, message: 'Joined date is required' },
  stateId: { required: true, message: 'Please select a state' },
  
  // Guardian validation rules
  'guardian.guardianname': { required: true, message: 'Guardian name is required' },
  'guardian.relation': { required: true, message: 'Relation is required' },
  'guardian.phone': { 
    required: true, 
    pattern: /^\d{10,15}$/,
    message: 'Please enter a valid phone number (10-15 digits)'
  },
  'guardian.email': { 
    required: false, 
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: 'Please enter a valid email address'
  },
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

  const validateGuardianField = (index: number, field: string): boolean => {
    const guardians = form.guardians || [];
    if (!guardians[index]) return true;

    const rule = validationRules[`guardian.${field}`];
    if (!rule) return true;

    const value = guardians[index][field];
    const errorKey = `guardians.${index}.${field}`;

    // Required validation
    if (rule.required) {
      if (!value || (typeof value === 'string' && value.trim() === '')) {
        if (showValidation.value) {
          validationErrors.value[errorKey] = rule.message;
        }
        return false;
      } else {
        delete validationErrors.value[errorKey];
      }
    }

    // Pattern validation
    if (rule.pattern && value && typeof value === 'string') {
      if (!rule.pattern.test(value)) {
        if (showValidation.value) {
          validationErrors.value[errorKey] = rule.message;
        }
        return false;
      } else {
        delete validationErrors.value[errorKey];
      }
    }

    return true;
  };

  const validateAllGuardians = (): boolean => {
    const guardians = form.guardians || [];
    let isValid = true;

    guardians.forEach((guardian: any, index: number) => {
      ['guardianname', 'relation', 'phone', 'email'].forEach(field => {
        if (!validateGuardianField(index, field)) {
          isValid = false;
        }
      });
    });

    return isValid;
  };

  const validateAllFields = (): boolean => {
    let isValid = true;
    
    // Validate basic fields
    Object.keys(validationRules).forEach((field) => {
      if (!field.startsWith('guardian.')) {
        if (!validateField(field)) {
          isValid = false;
        }
      }
    });

    // Validate guardians
    if (!validateAllGuardians()) {
      isValid = false;
    }

    return isValid;
  };

  return {
    validationErrors,
    showValidation,
    validateField,
    validateGuardianField,
    validateAllGuardians,
    validateAllFields
  };
}