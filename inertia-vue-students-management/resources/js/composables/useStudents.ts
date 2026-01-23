// composables/useStudents.ts
import { ref } from 'vue';
import { studentService, type Student, type Guardian } from '@/services/studentService';
import { usePage } from '@inertiajs/vue3';

export function useStudents() {
  const page = usePage();
  const students = ref<Student[]>((page.props.initialStudents as Student[]) || []);
  const loading = ref(false);

  const loadByDateRange = async (fromDate: string, toDate: string) => {
    loading.value = true;
    try {
      students.value = await studentService.loadByDateRange(fromDate, toDate);
      return students.value;
    } catch (error) {
      console.error('Failed to load students:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  const updateStudent = async (studentId: number, formData: FormData) => {
    try {
      const result = await studentService.update(studentId, formData);
      
      // Update the student in the list
      if (result.success && result.student) {
        const index = students.value.findIndex(s => s.id === studentId);
        if (index !== -1) {
          students.value[index] = result.student;
        }
      }
      
      return result;
    } catch (error) {
      console.error('Failed to update student:', error);
      throw error;
    }
  };

  const deleteStudent = async (studentId: number) => {
    try {
      const success = await studentService.delete(studentId);
      
      if (success) {
        // Remove from local list
        students.value = students.value.filter(s => s.id !== studentId);
      }
      
      return success;
    } catch (error) {
      console.error('Failed to delete student:', error);
      throw error;
    }
  };

  const getGuardians = async (studentId: number): Promise<Guardian[]> => {
    try {
      return await studentService.getGuardians(studentId);
    } catch (error) {
      console.error('Failed to load guardians:', error);
      throw error;
    }
  };

  const createGuardian = async (studentId: number, data: Partial<Guardian>): Promise<Guardian> => {
    try {
      return await studentService.createGuardian(studentId, data);
    } catch (error) {
      console.error('Failed to create guardian:', error);
      throw error;
    }
  };

  const updateGuardian = async (guardianId: number, data: Partial<Guardian>): Promise<Guardian> => {
    try {
      return await studentService.updateGuardian(guardianId, data);
    } catch (error) {
      console.error('Failed to update guardian:', error);
      throw error;
    }
  };

  const deleteGuardian = async (guardianId: number): Promise<boolean> => {
    try {
      return await studentService.deleteGuardian(guardianId);
    } catch (error) {
      console.error('Failed to delete guardian:', error);
      throw error;
    }
  };

  return {
    students,
    loading,
    loadByDateRange,
    updateStudent,
    deleteStudent,
    getGuardians,
    createGuardian,
    updateGuardian,
    deleteGuardian,
  };
}