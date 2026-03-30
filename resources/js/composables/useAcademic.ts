// composables/useAcademic.ts
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { studentService } from '@/services/studentService';

interface AcademicOption {
  value: string;
  label: string;
}

export function useAcademic(classIdRef?: any) {
  const page = usePage();
  
  const classes = ref<AcademicOption[]>((page.props.classes as AcademicOption[]) || []);
  const sections = ref<AcademicOption[]>([]);
  const isSectionLoading = ref(false);

  const fetchSections = async (classId: string) => {
    if (!classId) {
      sections.value = [];
      return;
    }
    
    isSectionLoading.value = true;
    try {
      sections.value = await studentService.getSectionsByClassId(classId);
    } catch (error) {
      console.error('Failed to fetch sections:', error);
      sections.value = [];
    } finally {
      isSectionLoading.value = false;
    }
  };

  // Auto-fetch sections when classId changes (if provided)
  if (classIdRef) {
    watch(() => classIdRef.value, async (newClassId) => {
      if (newClassId?.value) {
        await fetchSections(newClassId.value);
      } else {
        sections.value = [];
      }
    });
  }

  return {
    classes,
    sections,
    isSectionLoading,
    fetchSections,
  };
}