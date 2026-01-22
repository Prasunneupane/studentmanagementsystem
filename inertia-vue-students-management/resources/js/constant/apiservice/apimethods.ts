import ApiService from '@/constant/apiservice/apiservice';
import { 
  VITE_API_STATES,
  VITE_API_DISTRICTS,
  VITE_API_MUNICIPALITIES,
  VITE_API_CLASSES, 
  VITE_API_SECTIONS,
  VITE_API_STUDENTS_LIST_BY_DATE_RANGE,
  VITE_API_DELETE_STUDENT,
  VITE_GET_GUARDIANS_LIST_BY_STUDENTID,
  VITE_UPDATE_GUARDIANS_BY_GUARDIANID,
  VITE_DELETE_GUARDIANS_BY_GUARDIANID,
  VITE_API_UPDATE_STUDENT,
  VITE_DISTRICT_LIST,
  VITE_MUNICIPALITY_LIST,
  VITE_SECTIONS_LIST_BY_CLASSID,

 } from '@/constant/services';

// Update LocationItem to reflect actual response
export interface LocationItem {
  value: string; // Changed from string to number
  label: string; // Changed from dynamic key to explicit "name"
}

interface ApiMethodConfig {
  endpoint: string;
  method: 'GET' | 'POST' | 'PUT' | 'DELETE';
  params?: any;
  data?: any;
}

export const apiMethods = {
  getAllStates: (): ApiMethodConfig => ({
    endpoint:VITE_API_STATES,
    method: 'GET'
  }),

  getDistrictsByStateId: (stateId: string): ApiMethodConfig => ({
    endpoint: VITE_DISTRICT_LIST,
    method: 'GET',
    params: { state_id: stateId }
  }),

  getMunicipalitiesByDistrictId: (districtId: string): ApiMethodConfig => ({
    endpoint: VITE_MUNICIPALITY_LIST,
    method: 'GET',
    params: { district_id: districtId }
  }),

   getClassesList: (): ApiMethodConfig => ({
    endpoint: VITE_API_CLASSES,
    method: 'GET',
    // params: {  }
  }),

    getSectionList: (classId:number): ApiMethodConfig => ({
    endpoint: VITE_SECTIONS_LIST_BY_CLASSID,
    method: 'GET',
    params: { class_id: classId }
  }),

  createStudent: (formData: FormData): ApiMethodConfig => ({
    endpoint: import.meta.env.VITE_API_STUDENTS,
    method: 'POST',
    data: formData
  }),

  getStudentsListByDateRange: (fromDate:string ,toDate:string): ApiMethodConfig => ({
    endpoint: VITE_API_STUDENTS_LIST_BY_DATE_RANGE,
    method: 'GET',
    params: { fromDate:fromDate,toDate:toDate }
  }),
  deleteStudent: (studentId: number): ApiMethodConfig => ({
  endpoint: `${VITE_API_DELETE_STUDENT}/${studentId}`, // ← /api/deleteStudent/5
  method: 'DELETE',
  }),

  getGuardiansByStudent: (studentId: number): ApiMethodConfig => ({
    endpoint: `${VITE_GET_GUARDIANS_LIST_BY_STUDENTID}/${studentId}`,
    method: 'GET',
  }),

  createGuardian: (studentId: number): ApiMethodConfig => ({
    endpoint: `/api/students/${studentId}/guardians`,
    method: 'POST',
  }),

  updateGuardian: (guardianId: number,formData: FormData): ApiMethodConfig => ({
    endpoint: `${VITE_UPDATE_GUARDIANS_BY_GUARDIANID}/${guardianId}`,
    method: 'PUT',
    data: formData
  }),


  deleteGuardian: (guardianId: number): ApiMethodConfig => ({
    endpoint: `${VITE_DELETE_GUARDIANS_BY_GUARDIANID}/${guardianId}`,
    method: 'DELETE',
  }),
  
 updateStudent: (studentId: number, formData: FormData): ApiMethodConfig => {
    formData.append('_method', 'PUT'); // append first

    return {
        endpoint: `${VITE_API_UPDATE_STUDENT}/${studentId}`,
        method: 'POST',
        data: formData
    };
},

};

export const executeApiMethod = async <T>(
  config: ApiMethodConfig
): Promise<T> => {
  const response = await ApiService.request<T>(
    config.method,
    config.endpoint,
    config.data,
    config.params,
    true
  );
  return response.data;
};

