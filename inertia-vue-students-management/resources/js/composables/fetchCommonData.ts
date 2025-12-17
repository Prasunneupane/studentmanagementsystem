// composables/useLocationData.ts
import { ref } from 'vue';
import { 
  getStudentsListByDateRange,
  deleteStudent,
  getGuardiansByStudent,
  updateGuardian,
  updateStudentByStudentId 

} from '@/constant/apiservice/callService';
import { useToast } from './useToast';
const { toast } = useToast();
export interface Subject {
  id: number;
  name: string;
  code: string;
  type?: string;
  description?: string;
  is_active?: boolean;
  created_at?: string;
  updated_at?: string;
  // add more fields if needed
}

export interface Teacher{
  id: number;
  name:string;
  email:string;
  phone:string;
  subject_specialization:string;
  status:string;
  joining_date:string;
  leaving_date:string;
  photo:string;
  date_of_birth:string;
  qualification:string;
  is_active:boolean;
  created_by:string;
}

export interface Role{
  id: number;
  name:string;
  description:string;
  is_active:boolean;
  // created_by:string;
}

export interface Permission{
  id: number;
  name:string;
  slug:string;
  module:string;
  description:string;
  is_active:boolean;
  // created_by:string;
}

export function useFetchingData(form?: any) {
  // const students = ref<Student[]>([]);
  const loading = ref(false);
  const errorMessage = ref('');
  const deletingId = ref<number | null>(null);
  const guardians = ref<any[]>([]);
  const subject = ref<Subject[]>([]);
  const Teacher = ref<Teacher[]>([]);
  return {
    loading,
    errorMessage,
    deletingId,
    guardians,
    subject,
    Teacher,
  };
}