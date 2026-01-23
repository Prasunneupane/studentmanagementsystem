// services/studentService.ts
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const api = axios.create({
  baseURL: '/students',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add CSRF token to requests
api.interceptors.request.use((config) => {
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token;
  }
  return config;
});

export interface Student {
  id: number;
  first_name: string;
  middle_name?: string;
  last_name: string;
  email?: string;
  phone: string;
  age: number;
  date_of_birth: string;
  joined_date: string;
  address?: string;
  contact_number?: string;
  photo_url?: string;
  class_id?: number;
  class_name?: string;
  section_id?: number;
  section_name?: string;
  state_id?: number;
  state_name?: string;
  district_id?: number;
  district_name?: string;
  municipality_id?: number;
  municipality_name?: string;
}

export interface Guardian {
  id: number;
  name: string;
  relation?: string;
  phone?: string;
  email?: string;
  occupation?: string;
  address?: string;
  is_primary_contact: boolean;
}

export const studentService = {
  /**
   * Load students by date range
   */
  async loadByDateRange(fromDate: string, toDate: string): Promise<Student[]> {
    const response = await api.get('/load/by-date-range', {
      params: { from_date: fromDate, to_date: toDate }
    });
    return response.data.students;
  },

  /**
   * Update student
   */
  async update(studentId: number, formData: FormData): Promise<any> {
    // Laravel doesn't support PUT with FormData directly, so we use POST with _method
    formData.append('_method', 'PUT');
    const response = await axios.post(`/students/${studentId}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });
    return response.data;
  },

  /**
   * Delete student
   */
  async delete(studentId: number): Promise<boolean> {
    const response = await api.delete(`/${studentId}`);
    return response.data.success;
  },

  /**
   * Get guardians for a student
   */
  async getGuardians(studentId: number): Promise<Guardian[]> {
    const response = await api.get(`/${studentId}/guardians`);
    return response.data.guardians;
  },

  /**
   * Create guardian
   */
  async createGuardian(studentId: number, data: Partial<Guardian>): Promise<Guardian> {
    const response = await api.post(`/${studentId}/guardians`, data);
    return response.data.guardian;
  },

  /**
   * Update guardian
   */
  async updateGuardian(guardianId: number, data: Partial<Guardian>): Promise<Guardian> {
    const response = await api.put(`/guardians/${guardianId}`, data);
    return response.data.guardian;
  },

  /**
   * Delete guardian
   */
  async deleteGuardian(guardianId: number): Promise<boolean> {
    const response = await api.delete(`/guardians/${guardianId}`);
    return response.data.success;
  },

  /**
   * Get districts by state ID
   */
  async getDistrictsByStateId(stateId: string) {
    const response = await axios.get('/get-districts-by-state_id', {
      params: { state_id: stateId }
    });
    return response.data;
  },

  /**
   * Get municipalities by district ID
   */
  async getMunicipalitiesByDistrictId(districtId: string) {
    const response = await axios.get('/get-municipalities-by-district_id', {
      params: { district_id: districtId }
    });
    return response.data;
  },

  /**
   * Get sections by class ID
   */
  async getSectionsByClassId(classId: string) {
    const response = await axios.get('/get-sections-by-class_id', {
      params: { class_id: classId }
    });
    return response.data;
  },
};