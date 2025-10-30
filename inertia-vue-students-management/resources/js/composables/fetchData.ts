// composables/useLocationData.ts
import { ref } from 'vue';
import { getStudentsListByDateRange,deleteStudent } from '@/constant/apiservice/callService';
import { useToast } from './useToast';
const { toast } = useToast();

export interface Student {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  age: number;
  joined_date: string;
  class?: { name: string };
  // add more fields if needed
}

export function useLocationData(form: any) {
  const students = ref<Student[]>([]);
  const loading = ref(false);
  const errorMessage = ref('');
  const deletingId = ref<number | null>(null);

  const fetchStudentListByDateRange = async () => {
    if (!form.fromDate || !form.toDate) {
      // toast.error('Please select both From Date and To Date');
      
      toast.error("Please select both From Date and To Date", {
            duration: 3000,
            action: {
                label: 'Close',
                onClick: (e) => {
                    
                }
            }
          });
      return;
    }

    loading.value = true;
    errorMessage.value = '';
    students.value = [];

    try {
      

      // Your API returns an array directly (from executeApiMethod<{}>)
      // But we know it's actually Student[]
      const response = await getStudentsListByDateRange(form.fromDate, form.toDate);

      const list = response.students
      console.log(list,"list");
      
      students.value = list.map((s: any) => ({
        ...s,
        class_name: s.class?.name ?? null,
      }));

      if (students.value.length === 0) {
        errorMessage.value = 'No data found. Please select another date range.';
        toast.info(errorMessage.value , {
            duration: 3000,
            // action: {
            //     label: '',
            //     onClick: (e) => {
                    
            //     }
            // }
          });
      }
    } catch (err: any) {
      console.error('Fetch error:', err);

      if (err.message === 'auth' || err?.response?.status === 401) {
        errorMessage.value = 'Authentication failed. Please log in again.';
        // toast.error('Session expired. Please log in again.');
        toast.error("Session expired. Please log in again.", {
            duration: 3000,
            action: {
                label: 'Close',
                onClick: (e) => {
                    
                }
            }
          });
      } else {
        errorMessage.value = 'Failed to load students. Please try again.';
        // toast.error(errorMessage.value);
        toast.error(errorMessage.value, {
            duration: 3000,
            action: {
                label: 'Close',
                onClick: (e) => {
                    
                }
            }
          });
      }
    } finally {
      loading.value = false;
    }
  };

  const removeStudent = async (studentId: number): Promise<boolean> => {
    deletingId.value = studentId;
    try {
      await deleteStudent(studentId);
      // Remove from UI
      students.value = students.value.filter(s => s.id !== studentId);
      toast.success('Student deleted successfully.', {
        duration: 3000,
        action: { label: 'Close', onClick: () => {} },
      });
      return true;
    } catch (err: any) {
      console.error('Delete failed:', err);
      toast.error('Failed to delete student. Please try again.', {
        duration: 3000,
        action: { label: 'Close', onClick: () => {} },
      });
      return false;
    } finally {
      deletingId.value = null;
    }
  };

  return {
    students,
    loading,
    errorMessage,
    deletingId,
    fetchStudentListByDateRange,
    removeStudent,
  };
}