import ApiService from '@/constant/apiservice/apiservice';
import { 
  VITE_API_STATES,
  VITE_API_DISTRICTS,
  VITE_API_MUNICIPALITIES,
  VITE_API_CLASSES, 
  VITE_API_SECTIONS
 } from '@/constant/services';

// Update LocationItem to reflect actual response
export interface LocationItem {
  id: number; // Changed from string to number
  name: string; // Changed from dynamic key to explicit "name"
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
    endpoint: VITE_API_DISTRICTS,
    method: 'GET',
    params: { state_id: stateId }
  }),

  getMunicipalitiesByDistrictId: (districtId: string): ApiMethodConfig => ({
    endpoint: VITE_API_MUNICIPALITIES,
    method: 'GET',
    params: { district_id: districtId }
  }),

   getClassesList: (): ApiMethodConfig => ({
    endpoint: VITE_API_CLASSES,
    method: 'GET',
    // params: {  }
  }),

    getSectionList: (): ApiMethodConfig => ({
    endpoint: VITE_API_SECTIONS,
    method: 'GET',
    // params: {  }
  }),

  createStudent: (formData: FormData): ApiMethodConfig => ({
    endpoint: import.meta.env.VITE_API_STUDENTS,
    method: 'POST',
    data: formData
  })
};

export const executeApiMethod = async <T>(
  config: ApiMethodConfig
): Promise<T> => {
  const response = await ApiService.request<T>(
    config.method,
    config.endpoint,
    config.data,
    config.params
  );
  return response.data;
};

