import ApiService from '@/constant/apiservice/apiservice';
import { VITE_API_STATES,VITE_API_DISTRICTS,VITE_API_MUNICIPALITIES } from '@/constant/services';

// Update LocationItem to reflect actual response
interface LocationItem {
  id: number; // Changed from string to number
  name: string; // Changed from dynamic key to explicit "name"
}

interface ApiMethodConfig {
  endpoint: string;
  method: 'GET' | 'POST' | 'PUT' | 'DELETE';
  params?: any;
  data?: any;
}

const apiMethods = {
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

// Update to expect an array directly
export const getAllStates = () =>
  executeApiMethod<LocationItem[]>(apiMethods.getAllStates());

export const getDistrictsByStateId = (stateId: string) =>
  executeApiMethod<LocationItem[]>(apiMethods.getDistrictsByStateId(stateId));

export const getMunicipalitiesByDistrictId = (districtId: string) =>
  executeApiMethod<{
    municipalities: LocationItem[];
  }>(apiMethods.getMunicipalitiesByDistrictId(districtId));

export const createStudent = (formData: FormData) =>
  executeApiMethod<{
    message: string;
    student: any;
  }>(apiMethods.createStudent(formData));