// composables/useLocationData.ts
import { ref } from 'vue';
import { getStudentsListByDateRange } from '@/constant/apiservice/callService';
import { toast } from 'vue-sonner';

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

  const fetchStudentListByDateRange = async () => {
    if (!form.fromDate || !form.toDate) {
      toast.error('Please select both From Date and To Date');
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
      }
    } catch (err: any) {
      console.error('Fetch error:', err);

      if (err.message === 'auth' || err?.response?.status === 401) {
        errorMessage.value = 'Authentication failed. Please log in again.';
        toast.error('Session expired. Please log in again.');
      } else {
        errorMessage.value = 'Failed to load students. Please try again.';
        toast.error(errorMessage.value);
      }
    } finally {
      loading.value = false;
    }
  };

  return {
    students,
    loading,
    errorMessage,
    fetchStudentListByDateRange,
  };
}