// composables/useLocation.ts
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { studentService } from '@/services/studentService';

interface LocationOption {
  value: string;
  label: string;
}

export function useLocation() {
  const page = usePage();
  
  const states = ref<LocationOption[]>((page.props.states as LocationOption[]) || []);
  const districts = ref<LocationOption[]>([]);
  const municipalities = ref<LocationOption[]>([]);
  
  const isDistrictLoading = ref(false);
  const isMunicipalityLoading = ref(false);

  const fetchDistricts = async (stateId: string) => {
    if (!stateId) {
      districts.value = [];
      return;
    }
    
    isDistrictLoading.value = true;
    try {
      districts.value = await studentService.getDistrictsByStateId(stateId);
    } catch (error) {
      console.error('Failed to fetch districts:', error);
      districts.value = [];
    } finally {
      isDistrictLoading.value = false;
    }
  };

  const fetchMunicipalities = async (districtId: string) => {
    if (!districtId) {
      municipalities.value = [];
      return;
    }
    
    isMunicipalityLoading.value = true;
    try {
      const response = await studentService.getMunicipalitiesByDistrictId(districtId);
      municipalities.value = response;
    } catch (error) {
      console.error('Failed to fetch municipalities:', error);
      municipalities.value = [];
    } finally {
      isMunicipalityLoading.value = false;
    }
  };

  return {
    states,
    districts,
    municipalities,
    isDistrictLoading,
    isMunicipalityLoading,
    fetchDistricts,
    fetchMunicipalities,
  };
}