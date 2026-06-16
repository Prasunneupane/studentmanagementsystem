<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle, BarChart3, BookOpen, Calendar, ChevronRight, ClipboardList, FileText } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import 'vue-sonner/style.css';
import CustomSelect from '../CustomSelect.vue';

const { toast } = useToast();

interface Option {
    value: string;
    label: string;
}
interface Section {
    id: string;
    name: string;
}
interface ClassWithSections {
    id: string;
    name: string;
    sections: Section[];
}

interface ExamOption {
    id: string;
    name: string;
    exam_type: string;
    academic_year: string;
    academic_year_id: string;
    start_date: string;
    end_date: string;
}

interface SubjectSchedule {
    id: string;
    subject_id: string;
    subject_name: string;
    subject_code: string;
    exam_date: string;
    max_theory_marks: number;
    max_practical_marks: number;
    max_total_marks: number;
    pass_marks: number;
}

interface Props {
    academicYears: Option[];
    currentAcademicYear: Option | null;
    classes: ClassWithSections[];
    exams: ExamOption[];
    subjects: SubjectSchedule[];
    filters: {
        academic_year_id?: string;
        exam_id?: string;
        class_id?: string;
        section_id?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [{ title: 'Marks Management', href: '/marks' }];

// ─── Selections ──────────────────────────────────────────────────
const selectedAcademicYear = ref(props.filters.academic_year_id || props.currentAcademicYear?.value || '');
const selectedExam = ref(props.filters.exam_id || '');
const selectedClass = ref(props.filters.class_id || '');
const selectedSection = ref(props.filters.section_id || '');

const examOptions = computed((): Option[] => props.exams.map((e) => ({ value: String(e.id), label: `${e.name} (${e.exam_type})` })));

const classOptions = computed((): Option[] => props.classes.map((c) => ({ value: String(c.id), label: c.name })));

const sectionOptions = computed((): Option[] => {
    const cls = props.classes.find((c) => String(c.id) === selectedClass.value);
    return cls?.sections.map((s) => ({ value: String(s.id), label: `Section ${s.name}` })) || [];
});

// ─── Reload when filters change ──────────────────────────────────
const reloadSubjects = () => {
    if (selectedExam.value && selectedClass.value && selectedSection.value) {
        router.get(
            '/marks',
            {
                academic_year_id: selectedAcademicYear.value,
                exam_id: selectedExam.value,
                class_id: selectedClass.value,
                section_id: selectedSection.value,
            },
            { preserveState: true, preserveScroll: true },
        );
    }
};

// Clear dependent fields when parent changes
watch(selectedAcademicYear, () => {
    selectedExam.value = '';
    selectedClass.value = '';
    selectedSection.value = '';
    router.get(
        '/marks',
        {
            academic_year_id: selectedAcademicYear.value,
        },
        { preserveState: true, preserveScroll: true },
    );
});

watch(selectedExam, () => {
    selectedClass.value = '';
    selectedSection.value = '';
});

watch(selectedClass, () => {
    selectedSection.value = '';
});

watch(selectedSection, () => {
    if (selectedSection.value) reloadSubjects();
});

// ─── Navigate to marks entry ─────────────────────────────────────
const goToEnterMarks = (subjectId: string) => {
    router.get(`/marks/${selectedExam.value}/enter`, {
        class_id: selectedClass.value,
        section_id: selectedSection.value,
        subject_id: subjectId,
    });
};

const goToResults = () => {
    router.get(`/marks/${selectedExam.value}/results`, {
        class_id: selectedClass.value,
        section_id: selectedSection.value,
    });
};

const selectedExamData = computed(() => props.exams.find((e) => String(e.id) === selectedExam.value));
</script>

<template>
    <Head title="Marks Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-primary/10 p-2.5">
                        <ClipboardList class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Marks Management</h1>
                        <p class="text-sm text-muted-foreground">Enter marks, view marksheets, and manage results</p>
                    </div>
                </div>
                <Button v-if="selectedExam && selectedClass && selectedSection" @click="goToResults" variant="outline">
                    <BarChart3 class="mr-2 h-4 w-4" />
                    View Results
                </Button>
            </div>

            <!-- Filter Card -->
            <Card class="rounded-2xl shadow-lg">
                <CardHeader class="border-b">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-primary/10 p-2">
                            <BookOpen class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-lg font-bold">Select Exam & Class</CardTitle>
                            <p class="mt-0.5 text-sm text-muted-foreground">Choose the exam, class, and section to enter marks</p>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="pt-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <Label>Academic Year</Label>
                            <CustomSelect v-model="selectedAcademicYear" :options="academicYears" placeholder="Select Year" />
                        </div>

                        <div class="space-y-2">
                            <Label>Exam</Label>
                            <CustomSelect v-model="selectedExam" :options="examOptions" placeholder="Select Exam" />
                        </div>

                        <div class="space-y-2">
                            <Label>Class</Label>
                            <CustomSelect v-model="selectedClass" :options="classOptions" placeholder="Select Class" />
                        </div>

                        <div class="space-y-2">
                            <Label>Section</Label>
                            <CustomSelect
                                v-model="selectedSection"
                                :options="sectionOptions"
                                placeholder="Select Section"
                                :disabled="!selectedClass"
                            />
                        </div>
                    </div>

                    <!-- Exam info badge -->
                    <div v-if="selectedExamData" class="mt-4 flex items-center gap-4 rounded-lg border bg-muted/50 p-3">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm text-muted-foreground"> {{ selectedExamData.start_date }} → {{ selectedExamData.end_date }} </span>
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary capitalize">
                            {{ selectedExamData.exam_type?.replace('_', ' ') }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Subjects List -->
            <Card v-if="subjects.length > 0" class="rounded-2xl shadow-lg">
                <CardHeader class="border-b">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-primary/10 p-2">
                                <FileText class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <CardTitle class="text-lg font-bold">Subjects</CardTitle>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {{ subjects.length }} subject{{ subjects.length !== 1 ? 's' : '' }} scheduled
                                </p>
                            </div>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-0 pt-0">
                    <div class="divide-y">
                        <div
                            v-for="subject in subjects"
                            :key="subject.id"
                            class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-muted/30"
                        >
                            <div class="flex items-center gap-4">
                                <div class="rounded-lg bg-muted p-2">
                                    <BookOpen class="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div>
                                    <p class="font-semibold">{{ subject.subject_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ subject.subject_code }} · Exam Date: {{ subject.exam_date }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="text-right text-xs text-muted-foreground">
                                    <p>Theory: {{ subject.max_theory_marks }} | Practical: {{ subject.max_practical_marks }}</p>
                                    <p>Total: {{ subject.max_total_marks }} | Pass: {{ subject.pass_marks }}</p>
                                </div>
                                <Button size="sm" @click="goToEnterMarks(String(subject.subject_id))">
                                    Enter Marks
                                    <ChevronRight class="ml-1 h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty state -->
            <Card v-else-if="selectedExam && selectedClass && selectedSection" class="rounded-2xl shadow-lg">
                <CardContent class="py-12">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <AlertCircle class="h-10 w-10 text-muted-foreground" />
                        <p class="text-lg font-medium text-muted-foreground">No subjects scheduled</p>
                        <p class="text-sm text-muted-foreground">
                            No exam schedule found for this class-section combination. Please set up the schedule first.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Intro state -->
            <Card v-else class="rounded-2xl shadow-lg">
                <CardContent class="py-12">
                    <div class="flex flex-col items-center gap-3 text-center">
                        <div class="rounded-full bg-primary/10 p-3">
                            <ClipboardList class="h-8 w-8 text-primary" />
                        </div>
                        <p class="text-lg font-medium">Select an exam, class, and section above</p>
                        <p class="max-w-md text-sm text-muted-foreground">
                            Choose the academic year, exam, class, and section to view scheduled subjects and enter student marks.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
