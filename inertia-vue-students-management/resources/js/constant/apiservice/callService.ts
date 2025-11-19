// Update to expect an array directly
import {apiMethods,executeApiMethod ,LocationItem} from '@/constant/apiservice/apimethods';

export const getAllStates = () =>
  executeApiMethod<LocationItem[]>(apiMethods.getAllStates());

export const getDistrictsByStateId = (stateId: string) =>
  executeApiMethod<LocationItem[]>(apiMethods.getDistrictsByStateId(stateId));

export const getMunicipalitiesByDistrictId = (districtId: string) =>
  executeApiMethod<{
    municipalities: LocationItem[];
  }>(apiMethods.getMunicipalitiesByDistrictId(districtId));

export const getClassesList = () =>
  executeApiMethod<{classesList:LocationItem[]}>(apiMethods.getClassesList());

export const getSectionList = () =>
    executeApiMethod<{sectionList:LocationItem[]}>(apiMethods.getSectionList());

export const createStudent = (formData: FormData) =>
  executeApiMethod<{
    message: string;
    student: any;
  }>(apiMethods.createStudent(formData));

export const getStudentsListByDateRange = (fromDate:string ,toDate:string) =>
  executeApiMethod<{}>(apiMethods.getStudentsListByDateRange(fromDate,toDate));

export const deleteStudent = (studentId: number) =>
  executeApiMethod<void>(apiMethods.deleteStudent(studentId));

export const getGuardiansByStudent = (studentId: number) =>
  executeApiMethod<{guardians: any[]}>(apiMethods.getGuardiansByStudent(studentId));

export const updateGuardian = (guardianId: number,formData: FormData) =>
  executeApiMethod<{
    message: string;
    guardian: any;
  }>(apiMethods.updateGuardian(guardianId,formData));

  export const deleteGuardianByGuardianId = (guardianId: number) =>
  executeApiMethod<void>(apiMethods.deleteGuardian(guardianId));

  export const updateStudentByStudentId = (studentId: number,formData: FormData) =>
  executeApiMethod<{
    message: string;
    student: any;
  }>(apiMethods.updateStudent(studentId,formData));