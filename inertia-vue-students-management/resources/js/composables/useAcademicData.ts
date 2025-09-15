// composables/useAcademicData.ts
import { ref } from 'vue';
import { getClassesList, getSectionList } from '@/constant/apiservice/callService';

export function useAcademicData() {
  const classes = ref<{ value: string; label: string }[]>([]);
  const sections = ref<{ value: string; label: string }[]>([]);
  
  const isClassesLoading = ref(false);
  const isSectionLoading = ref(false);

  const fetchClasses = async () => {
    isClassesLoading.value = true;
    try {
      const response = await getClassesList();
      classes.value = response.classesList.map((cls: any) => ({
        value: cls.id.toString(),
        label: cls.name
      }));
    } catch (error) {
      console.error('Failed to fetch classes:', error);
    } finally {
      isClassesLoading.value = false;
    }
  };

  const fetchSections = async () => {
    isSectionLoading.value = true;
    try {
      const response = await getSectionList();
      sections.value = response.sectionList.map((sec: any) => ({
        value: sec.id.toString(),
        label: sec.name
      }));
    } catch (error) {
      console.error('Failed to fetch sections:', error);
    } finally {
      isSectionLoading.value = false;
    }
  };

  // Initialize both on composable creation
  const initialize = async () => {
    await Promise.all([fetchClasses(), fetchSections()]);
  };

  // Auto-initialize
  initialize();

  return {
    classes,
    sections,
    isClassesLoading,
    isSectionLoading,
    fetchClasses,
    fetchSections
  };
}