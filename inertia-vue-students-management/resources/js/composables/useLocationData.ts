// composables/useLocationData.ts
import { ref, watch } from 'vue';
import { getAllStates, getDistrictsByStateId, getMunicipalitiesByDistrictId,getStudentsListByDateRange } from '@/constant/apiservice/callService';

export function useLocationData(form: any) {
  const states = ref<{ value: string; label: string }[]>([]);
  const districts = ref<{ value: string; label: string }[]>([]);
  const municipalities = ref<{ value: string; label: string }[]>([]);
  
  const isStateLoading = ref(false);
  const isDistrictLoading = ref(false);
  const isMunicipalityLoading = ref(false);

  const fetchStates = async () => {
    isStateLoading.value = true;
    try { 
      const response = await getAllStates();
      states.value = response.map((state) => ({
        value: state.id.toString(),
        label: state.name
      }));
      
      // Set default state if available
      const defaultState = states.value.find(state => state.value === '5');
      if (defaultState) {
        form.stateId = defaultState;
      }
    } catch (error) {
      console.error('Failed to fetch states:', error);
    } finally {
      isStateLoading.value = false;
    }
  };

  const fetchStudentListByDateRange = async () => {
    
    try { 
      const response = await getStudentsListByDateRange('2023-01-01','2024-01-01');
      return response;
      
      // Set default state if available
      
    } catch (error) {
      console.error('Failed to fetch states:', error);
    } finally {
     
    }
  };

  const fetchDistricts = async (stateId: string) => {
    if (!stateId) return;
    
    isDistrictLoading.value = true;
    try {
      const response = await getDistrictsByStateId(stateId);
      districts.value = response.map((district) => ({
        value: district.id.toString(),
        label: district.name
      }));
      
      // Set default district if available
      const defaultDistrict = districts.value.find(district => district.value === '46');
      if (defaultDistrict) {
        form.districtId = defaultDistrict;
      }
    } catch (error) {
      console.error('Failed to fetch districts:', error);
    } finally {
      isDistrictLoading.value = false;
    }
  };

  const fetchMunicipalities = async (districtId: string) => {
    if (!districtId) return;
    
    isMunicipalityLoading.value = true;
    try {
      const response = await getMunicipalitiesByDistrictId(districtId);
      municipalities.value = response.municipalities.map((municipality) => ({
        value: municipality.id.toString(),
        label: municipality.name
      }));
      
      // Set default municipality if available
      const defaultMunicipality = municipalities.value.find(municipality => municipality.value === '463');
      if (defaultMunicipality) {
        form.municipalityId = defaultMunicipality;
      }
    } catch (error) {
      console.error('Failed to fetch municipalities:', error);
    } finally {
      isMunicipalityLoading.value = false;
    }
  };

  // Watch for state changes
  watch(() => form.stateId, async (newState) => {
    districts.value = [];
    municipalities.value = [];
    form.districtId = null;
    form.municipalityId = null;
    
    if (newState?.value) {
      await fetchDistricts(newState.value);
    }
  });

  // Watch for district changes
  watch(() => form.districtId, async (newDistrict) => {
    municipalities.value = [];
    form.municipalityId = null;
    
    if (newDistrict?.value) {
      await fetchMunicipalities(newDistrict.value);
    }
  });

  // Initialize
  const initialize = async () => {
    await fetchStates();
    if (form.stateId?.value) {
      await fetchDistricts(form.stateId.value);
      if (form.districtId?.value) {
        await fetchMunicipalities(form.districtId.value);
      }
    }
  };



  // Auto-initialize
  initialize();

  return {
    states,
    districts,
    municipalities,
    isStateLoading,
    isDistrictLoading,
    isMunicipalityLoading,
    fetchStates,
    fetchDistricts,
    fetchMunicipalities
  };
}