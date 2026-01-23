// composables/useAcademicData.ts
import { ref, watch } from 'vue';
import { getClassesList, getSectionList } from '@/constant/apiservice/callService';

export function useAcademicData1() {
  const classes = ref<{ value: string; label: string }[]>([]);
  const sections = ref<{ value: string; label: string }[]>([]);
  
  const isClassesLoading = ref(false);
  const isSectionLoading = ref(false);

  // watch(() => form.classId, async (newClass, olClass) => {
  //     // Only fetch if state actually changed
      
      
  //     if (newClass?.value !== olClass?.value) {
  //       sections.value = [];
  //       form.sectionId = null;
  //       console.log(olClass,"ol");
  //       console.log(newClass,"new");
  //       // if (olClass?.value) {
  //         await fetchSectionByClassId(newClass.value);
  //       // }
  //     }
  //   });

  // const fetchClasses = async () => {
  //   isClassesLoading.value = true;
  //   try {
  //     const response = await getClassesList();
  //     classes.value = response.classesList.map((cls: any) => ({
  //       value: cls.id.toString(),
  //       label: cls.name
  //     }));
  //   } catch (error) {
  //     console.error('Failed to fetch classes:', error);
  //   } finally {
  //     isClassesLoading.value = false;
  //   }
  // };

  // const fetchSections = async () => {
  //   isSectionLoading.value = true;
  //   try {
  //     const response = await getSectionList(form.classId);
  //     sections.value = response.sectionList.map((sec: any) => ({
  //       value: sec.id.toString(),
  //       label: sec.name
  //     }));
  //   } catch (error) {
  //     console.error('Failed to fetch sections:', error);
  //   } finally {
  //     isSectionLoading.value = false;
  //   }
  // };

  const fetchSectionByClassId = async (classId: number) => {
    isSectionLoading.value = true;  
    try {
      const response = await getSectionList(classId);
      sections.value = response
    } catch (error) {
      console.error('Failed to fetch sections by class ID:', error);
      sections.value = [];
    } finally {
      isSectionLoading.value = false;
    } 
  };

  // Initialize both on composable creation
  // const initialize = async () => {
  //   await Promise.all([fetchClasses(), fetchSections()]);
  // };

  // // Auto-initialize
  // initialize();

  return {
    classes,
    sections,
    isClassesLoading,
    isSectionLoading,
    // fetchClasses,
    // fetchSections
  };
}