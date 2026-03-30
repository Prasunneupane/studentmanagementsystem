// composables/useLocationData.ts
import { ref, watch } from 'vue';
import { getDistrictsByStateId, getMunicipalitiesByDistrictId } from '@/constant/apiservice/callService';

interface LocationOption {
  value: string;
  label: string;
}

interface DistrictResponse {
  districts: LocationOption[];
}

export function useLocationData(
  form: any,
  initialStates: LocationOption[] = [],
  initialDistricts: LocationOption[] = [],
  initialMunicipalities: LocationOption[] = []
) {
  // Initialize with props data
  const states = ref<LocationOption[]>(initialStates);
  const districts = ref<LocationOption[]>(initialDistricts);
  const municipalities = ref<LocationOption[]>(initialMunicipalities);
  
  const isStateLoading = ref(false);
  const isDistrictLoading = ref(false);
  const isMunicipalityLoading = ref(false);

  const fetchDistricts = async (stateId: string) => {
    if (!stateId) return;
    
    isDistrictLoading.value = true;
    try {
      const response = await getDistrictsByStateId(stateId);
      
      districts.value = response as LocationOption[];

      // Reset municipality when districts change
      municipalities.value = [];
      form.municipalityId = null;
    } catch (error) {
      console.error('Failed to fetch districts:', error);
      districts.value = [];
    } finally {
      isDistrictLoading.value = false;
    }
  };

  const fetchMunicipalities = async (districtId: string) => {
    if (!districtId) return;
    
    isMunicipalityLoading.value = true;
    try {
      const response = await getMunicipalitiesByDistrictId(districtId);
      municipalities.value = response as LocationOption[];
    } catch (error) {
      console.error('Failed to fetch municipalities:', error);
      municipalities.value = [];
    } finally {
      isMunicipalityLoading.value = false;
    }
  };

  // Watch for state changes
  watch(() => form.stateId, async (newState, oldState) => {
    // Only fetch if state actually changed
    if (newState?.value !== oldState?.value) {
      districts.value = [];
      municipalities.value = [];
      form.districtId = null;
      form.municipalityId = null;
      
      if (newState?.value) {
        await fetchDistricts(newState.value);
      }
    }
  });

  // Watch for district changes
  watch(() => form.districtId, async (newDistrict, oldDistrict) => {
    // Only fetch if district actually changed
    if (newDistrict?.value !== oldDistrict?.value) {
      municipalities.value = [];
      form.municipalityId = null;
      
      if (newDistrict?.value) {
        await fetchMunicipalities(newDistrict.value);
      }
    }
  });

  return {
    states,
    districts,
    municipalities,
    isStateLoading,
    isDistrictLoading,
    isMunicipalityLoading,
    fetchDistricts,
    fetchMunicipalities
  };
}