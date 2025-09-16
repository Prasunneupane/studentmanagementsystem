// composables/useStudentForm.ts
import { ref } from 'vue';

import { Button } from "@/components/ui/button"
import { useToast } from './useToast';

const { toast } = useToast();

export function useStudentForm(form: any, validateAllFields: () => boolean, validationErrors: any, showValidation: any) {
  const dateOfBirthValue = ref<Date | null>(null);
  const joinedDateValue = ref<Date | null>(new Date());
  const isSubmitting = ref(false);

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
      
      // Transform and submit form data
      form.transform((data: any) => {
        const transformedData: Record<string, any> = {};
        
        for (const [key, value] of Object.entries(data)) {
          if (value !== null && value !== '' && value !== undefined) {
            if (['stateId', 'districtId', 'municipalityId', 'classId', 'sectionId'].includes(key)) {
              transformedData[key] = (
                typeof value === 'object' &&
                !(value instanceof File) &&
                value !== null &&
                'value' in value
              )
                ? (value as { value: any }).value
                : value;
            } else if (key === 'photo' && value instanceof File) {
              transformedData[key] = value;
            } else if (key === 'photo' && (typeof value === 'object' && Object.keys(value).length === 0)) {
              continue; // Skip empty photo object
            } else {
              transformedData[key] = value;
            }
          }
        }
        
        console.log('Transformed data:', transformedData);
        return transformedData;
      }).post(route('students.store'), {
        onSuccess: () => {
        console.log(toast,"toast");
          console.log('Form submitted successfully');
          toast.success("Student registered successfully!", {
            duration: 3000,
            action: {
                label: 'Close',
                onClick: (e) => {
                    
                }
            }
          });
          // Reset form and states
          dateOfBirthValue.value = null;
          joinedDateValue.value = new Date();
          showValidation.value = false;
          validationErrors.value = {};
          form.reset();
        },
        onError: (errors: any) => {
          
          console.error('Form submission errors:', errors);
          
          
          toast.error("Failed to register student. Please check the form and try again.", {
            duration: 5000,
            position: 'top-right',
            description: Object.keys(errors).length > 0 
              ? `Found ${Object.keys(errors).length} error(s)`
              : "An unexpected error occurred",
            action: {
              label: "Close",
              onClick: (e) => {
                    
                }
            }
          });
          validationErrors.value = { ...validationErrors.value, ...errors };
        }
      });
    } catch (error) {
      console.error('Form submission failed:', error);
    } finally {
      isSubmitting.value = false;
    }
  };

  return {
    dateOfBirthValue,
    joinedDateValue,
    isSubmitting,
    handleSubmit
  };
}