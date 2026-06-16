<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, ChevronLeft, Printer, User, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface SubjectMark {
    subject_name: string;
    subject_code: string;
    theory_marks: number | null;
    practical_marks: number | null;
    total_marks: number | null;
    max_theory_marks: number;
    max_practical_marks: number;
    max_total_marks: number;
    pass_marks: number;
    is_absent: boolean;
    grade: string;
    status: 'pass' | 'fail' | 'absent';
    remarks: string | null;
}

interface Props {
    marksheet: {
        exam: {
            id: string;
            name: string;
            exam_type: string;
            academic_year: string;
            term: string | null;
        };
        student: {
            id: number;
            name: string;
            roll_no: string;
            class_name: string;
            section_name: string;
            photo_url: string;
        } | null;
        subjects: SubjectMark[];
        result: {
            total_marks_obtained: number;
            total_max_marks: number;
            percentage: number;
            grade: string;
            gpa: number;
            rank: number | null;
            status: string;
            is_finalized: boolean;
        } | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Marks Management', href: '/marks' },
    { title: props.marksheet.exam.name, href: '#' },
    { title: 'Marksheet', href: '#' },
];

const student = computed(() => props.marksheet.student);
const exam = computed(() => props.marksheet.exam);
const subjects = computed(() => props.marksheet.subjects);
const result = computed(() => props.marksheet.result);

const statusColor = (status: string) => {
    if (status === 'pass') return 'text-green-600 bg-green-100 dark:bg-green-900/30';
    if (status === 'fail') return 'text-red-600 bg-red-100 dark:bg-red-900/30';
    return 'text-amber-600 bg-amber-100 dark:bg-amber-900/30';
};

const gradeColor = (grade: string) => {
    if (['A+', 'A'].includes(grade)) return 'text-green-600';
    if (['B+', 'B'].includes(grade)) return 'text-blue-600';
    if (['C+', 'C'].includes(grade)) return 'text-amber-600';
    if (['D+', 'D'].includes(grade)) return 'text-orange-600';
    return 'text-red-600';
};

const handlePrint = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Marksheet - ${student?.name ?? 'Student'}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Actions Bar (hidden in print) -->
            <div class="flex flex-wrap items-center justify-between gap-3 print:hidden">
                <Button variant="link" size="sm" @click="router.back()"> <ChevronLeft class="mr-1 h-4 w-4" /> Back </Button>
                <Button variant="outline" size="sm" @click="handlePrint"> <Printer class="mr-2 h-4 w-4" /> Print Marksheet </Button>
            </div>

            <!-- Marksheet Card -->
            <Card class="rounded-xl shadow print:hidden">
                <!-- School / Exam Header -->
                <CardHeader class="border-b py-6 text-center">
                    <div class="space-y-1">
                        <CardTitle class="text-2xl font-bold tracking-tight">{{ exam.name }}</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            {{ exam.academic_year }}
                            <span v-if="exam.term"> · {{ exam.term }}</span>
                            · <span class="capitalize">{{ exam.exam_type?.replace('_', ' ') }}</span>
                        </p>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6 pt-6">
                    <!-- Student Info -->
                    <div v-if="student" class="flex items-start justify-between rounded-xl border bg-muted/50 p-4">
                        <div class="flex items-center gap-4">
                            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-primary/10">
                                <img v-if="student.photo_url" :src="student.photo_url" :alt="student.name" class="h-full w-full object-cover" />
                                <User v-else class="h-7 w-7 text-primary" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">{{ student.name }}</h3>
                                <p class="text-sm text-muted-foreground">
                                    Roll No: <span class="font-semibold text-foreground">{{ student.roll_no }}</span>
                                </p>
                                <p class="text-sm text-muted-foreground">{{ student.class_name }} — {{ student.section_name }}</p>
                            </div>
                        </div>

                        <!-- Result badge -->
                        <div v-if="result" class="space-y-1 text-right">
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-semibold uppercase"
                                :class="statusColor(result.status)"
                            >
                                <CheckCircle2 v-if="result.status === 'pass'" class="h-3.5 w-3.5" />
                                <XCircle v-else class="h-3.5 w-3.5" />
                                {{ result.status }}
                            </span>
                            <p v-if="result.rank" class="text-xs text-muted-foreground">
                                Rank: <span class="font-bold text-foreground">{{ result.rank }}</span>
                            </p>
                            <p v-if="result.is_finalized" class="text-[10px] font-medium text-green-600">✓ Finalized</p>
                        </div>
                    </div>

                    <!-- Marks Table -->
                    <div class="overflow-hidden rounded-xl border">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/60">
                                    <th class="w-12 px-4 py-3 text-left font-semibold text-muted-foreground">#</th>
                                    <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Subject</th>
                                    <th class="px-3 py-3 text-center font-semibold text-muted-foreground">Theory</th>
                                    <th class="px-3 py-3 text-center font-semibold text-muted-foreground">Practical</th>
                                    <th class="px-3 py-3 text-center font-semibold text-muted-foreground">Total</th>
                                    <th class="px-3 py-3 text-center font-semibold text-muted-foreground">Max</th>
                                    <th class="px-3 py-3 text-center font-semibold text-muted-foreground">Pass</th>
                                    <th class="px-3 py-3 text-center font-semibold text-muted-foreground">Grade</th>
                                    <th class="px-3 py-3 text-center font-semibold text-muted-foreground">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(subj, i) in subjects"
                                    :key="i"
                                    class="border-b transition-colors last:border-0"
                                    :class="[
                                        i % 2 === 0 ? 'bg-background' : 'bg-muted/20',
                                        subj.status === 'fail' ? 'bg-red-50/50 dark:bg-red-950/20' : '',
                                        subj.is_absent ? 'opacity-60' : '',
                                    ]"
                                >
                                    <td class="px-4 py-2.5 text-muted-foreground">{{ i + 1 }}</td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-medium">{{ subj.subject_name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ subj.subject_code }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span v-if="subj.is_absent" class="font-medium text-red-500">AB</span>
                                        <span v-else>{{ subj.theory_marks ?? '—' }}/{{ subj.max_theory_marks }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span v-if="subj.is_absent" class="font-medium text-red-500">AB</span>
                                        <span v-else>{{ subj.practical_marks ?? '—' }}/{{ subj.max_practical_marks }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-semibold">
                                        <span v-if="subj.is_absent" class="text-red-500">AB</span>
                                        <span v-else :class="subj.status === 'fail' ? 'text-red-600' : ''">
                                            {{ subj.total_marks ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center text-muted-foreground">{{ subj.max_total_marks }}</td>
                                    <td class="px-3 py-2.5 text-center text-muted-foreground">{{ subj.pass_marks }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="font-bold" :class="gradeColor(subj.grade)">{{ subj.grade }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold capitalize"
                                            :class="statusColor(subj.status)"
                                        >
                                            {{ subj.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Result Summary -->
                    <div v-if="result" class="grid grid-cols-2 gap-4 md:grid-cols-5">
                        <div class="rounded-xl border bg-muted/50 p-4 text-center">
                            <p class="mb-1 text-xs tracking-wider text-muted-foreground uppercase">Total Marks</p>
                            <p class="text-xl font-bold">{{ result.total_marks_obtained }}/{{ result.total_max_marks }}</p>
                        </div>
                        <div class="rounded-xl border bg-muted/50 p-4 text-center" v-show="result.percentage == null">
                            <p class="mb-1 text-xs tracking-wider text-muted-foreground uppercase">Percentage</p>
                            <p class="text-xl font-bold">{{ result.percentage }}%</p>
                        </div>
                        <div class="rounded-xl border bg-muted/50 p-4 text-center">
                            <p class="mb-1 text-xs tracking-wider text-muted-foreground uppercase">Grade</p>
                            <p class="text-xl font-bold" :class="gradeColor(result.grade)">{{ result.grade }}</p>
                        </div>
                        <div class="rounded-xl border bg-muted/50 p-4 text-center">
                            <p class="mb-1 text-xs tracking-wider text-muted-foreground uppercase">GPA</p>
                            <p class="text-xl font-bold">{{ result.gpa }}</p>
                        </div>
                        <div class="rounded-xl border bg-muted/50 p-4 text-center">
                            <p class="mb-1 text-xs tracking-wider text-muted-foreground uppercase">Rank</p>
                            <p class="text-xl font-bold">{{ result.rank ?? '—' }}</p>
                        </div>
                    </div>

                    <!-- Nepal Grading Scale Reference -->
                    <div class="rounded-xl border bg-muted/30 p-4">
                        <p class="mb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase">Grading Scale</p>
                        <div class="flex flex-wrap gap-3 text-xs">
                            <span class="rounded bg-green-100 px-2 py-1 text-green-700 dark:bg-green-900/30">A+ (90-100) 4.0</span>
                            <span class="rounded bg-green-100 px-2 py-1 text-green-700 dark:bg-green-900/30">A (80-89) 3.6</span>
                            <span class="rounded bg-blue-100 px-2 py-1 text-blue-700 dark:bg-blue-900/30">B+ (70-79) 3.2</span>
                            <span class="rounded bg-blue-100 px-2 py-1 text-blue-700 dark:bg-blue-900/30">B (60-69) 2.8</span>
                            <span class="rounded bg-amber-100 px-2 py-1 text-amber-700 dark:bg-amber-900/30">C+ (50-59) 2.4</span>
                            <span class="rounded bg-amber-100 px-2 py-1 text-amber-700 dark:bg-amber-900/30">C (40-49) 2.0</span>
                            <span class="rounded bg-orange-100 px-2 py-1 text-orange-700 dark:bg-orange-900/30">D+ (30-39) 1.6</span>
                            <span class="rounded bg-orange-100 px-2 py-1 text-orange-700 dark:bg-orange-900/30">D (20-29) 1.2</span>
                            <span class="rounded bg-red-100 px-2 py-1 text-red-700 dark:bg-red-900/30">NG (&lt;20) 0.0</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Print-Only Elegant Marksheet (A4 and A5 Friendly) -->
            <div id="print-marksheet" class="mx-auto hidden w-full max-w-[21cm] bg-white font-sans leading-tight text-black print:block">
                <!-- Certificate-style elegant border wrapper -->
                <div class="main-wrapper rounded-sm border-double border-black">
                    <!-- School Header (Letterhead) -->
                    <div class="mb-2.5 flex items-center justify-between border-b border-black pb-2.5">
                        <!-- Left: Logo -->
                        <div class="header-logo-container flex h-14 w-14 flex-shrink-0 items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="logo-svg h-12 w-12 text-blue-900">
                                <!-- Crest Outline -->
                                <path
                                    d="M50,10 L80,25 C80,60 50,90 50,90 C50,90 20,60 20,25 Z"
                                    fill="rgba(30,58,138,0.03)"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                />
                                <!-- Book Icon -->
                                <path d="M35,45 Q50,40 65,45 L65,65 Q50,60 35,65 Z" fill="none" stroke="currentColor" stroke-width="1.8" />
                                <line x1="50" y1="42" x2="50" y2="63" stroke="currentColor" stroke-width="1.8" />
                                <!-- Golden Star of Excellence -->
                                <polygon points="50,20 53,26 60,26 55,30 57,36 50,32 43,36 45,30 40,26 47,26" fill="#D97706" />
                                <!-- Ribbon decoration -->
                                <path d="M25,82 L50,78 L75,82" fill="none" stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </div>

                        <!-- Center: School Info -->
                        <div class="flex-1 px-2 text-center">
                            <h1 class="school-title font-serif leading-none font-extrabold tracking-wider text-blue-900 uppercase">
                                Everest English Secondary School
                            </h1>
                            <p class="school-sub mt-0.5 font-medium text-gray-800">Baneshwor, Kathmandu, Nepal</p>
                            <p class="school-contact text-gray-650">Tel: +977-1-4488990 | Email: info@everestschool.edu.np</p>
                            <p class="school-contact text-gray-850 font-bold">PAN No: 301245789</p>
                        </div>

                        <!-- Right: Estd Info / Stamp placeholder -->
                        <div class="w-14 flex-shrink-0 text-right">
                            <div
                                class="estd-badge inline-block rounded border border-gray-400 px-1 py-0.5 font-bold tracking-wider text-gray-600 uppercase"
                            >
                                Estd. 1998
                            </div>
                        </div>
                    </div>

                    <!-- Exam Details Title -->
                    <div class="mb-3 text-center">
                        <h2
                            class="exam-title inline-block border-b border-black pb-0.5 text-xs font-extrabold tracking-wider text-gray-900 uppercase"
                        >
                            {{ exam.name }}
                        </h2>
                        <p class="exam-sub mt-1 font-bold text-gray-700">
                            Academic Session: {{ exam.academic_year }}
                            <span v-if="exam.term"> &middot; {{ exam.term }}</span>
                            <span v-if="exam.exam_type">
                                &middot; <span class="capitalize">{{ exam.exam_type.replace('_', ' ') }}</span></span
                            >
                        </p>
                        <div class="mt-1">
                            <span class="section-title rounded-sm border border-black bg-gray-100 px-4 py-0.5 font-bold tracking-widest uppercase">
                                Grade Sheet
                            </span>
                        </div>
                    </div>

                    <!-- Student Information Section -->
                    <div v-if="student" class="student-info mb-3 grid grid-cols-2 gap-x-4 gap-y-1 rounded-sm border border-black bg-gray-50/50 p-2">
                        <div class="flex items-center">
                            <span class="w-24 font-bold text-gray-600">Student Name:</span>
                            <span class="font-extrabold text-black uppercase">{{ student.name }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-16 font-bold text-gray-600">Roll No:</span>
                            <span class="font-bold text-black">{{ student.roll_no }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-24 font-bold text-gray-600">Class:</span>
                            <span class="font-semibold text-black">{{ student.class_name }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-16 font-bold text-gray-600">Section:</span>
                            <span class="font-semibold text-black">{{ student.section_name }}</span>
                        </div>
                    </div>

                    <!-- Marks Table -->
                    <div class="mb-3 overflow-x-auto">
                        <table class="marks-table w-full border-collapse border border-black">
                            <thead>
                                <tr class="border-b border-black bg-gray-100">
                                    <th class="w-8 border border-black px-1.5 py-1 text-center font-bold">S.N.</th>
                                    <th class="border border-black px-1.5 py-1 text-left font-bold">Subject Code & Name</th>
                                    <th class="w-16 border border-black px-1.5 py-1 text-center font-bold">Theory (Max)</th>
                                    <th class="w-16 border border-black px-1.5 py-1 text-center font-bold">Practical (Max)</th>
                                    <th class="w-14 border border-black px-1.5 py-1 text-center font-bold">Pass Marks</th>
                                    <th class="w-18 border border-black px-1.5 py-1 text-center font-bold">Marks Obtained</th>
                                    <th class="w-12 border border-black px-1.5 py-1 text-center font-bold">Grade</th>
                                    <th class="w-14 border border-black px-1.5 py-1 text-center font-bold">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(subj, i) in subjects" :key="i" class="border-b border-black last:border-b-2">
                                    <td class="border border-black px-1.5 py-0.5 text-center font-medium">{{ i + 1 }}</td>
                                    <td class="border border-black px-1.5 py-0.5">
                                        <div class="font-bold text-gray-950">{{ subj.subject_name }}</div>
                                        <div class="font-mono text-[8px] font-semibold text-gray-500">{{ subj.subject_code }}</div>
                                    </td>
                                    <td class="border border-black px-1.5 py-0.5 text-center">
                                        <span v-if="subj.is_absent" class="text-[8px] font-bold text-red-600">AB</span>
                                        <span v-else
                                            >{{ subj.theory_marks ?? '—' }} <span class="text-gray-400">/{{ subj.max_theory_marks }}</span></span
                                        >
                                    </td>
                                    <td class="border border-black px-1.5 py-0.5 text-center">
                                        <span v-if="subj.is_absent" class="text-[8px] font-bold text-red-600">AB</span>
                                        <span v-else
                                            >{{ subj.practical_marks ?? '—' }}
                                            <span class="text-gray-400">/{{ subj.max_practical_marks }}</span></span
                                        >
                                    </td>
                                    <td class="text-gray-650 border border-black px-1.5 py-0.5 text-center font-medium">
                                        {{ subj.pass_marks }}
                                    </td>
                                    <td
                                        class="border border-black px-1.5 py-0.5 text-center font-bold"
                                        :class="subj.status === 'fail' ? 'text-red-600' : ''"
                                    >
                                        <span v-if="subj.is_absent" class="text-red-600">AB</span>
                                        <span v-else
                                            >{{ subj.total_marks ?? '—' }}
                                            <span class="text-[8px] font-normal text-gray-400">/{{ subj.max_total_marks }}</span></span
                                        >
                                    </td>
                                    <td class="border border-black px-1.5 py-0.5 text-center font-bold">
                                        <span :class="gradeColor(subj.grade)">{{ subj.grade }}</span>
                                    </td>
                                    <td
                                        class="border border-black px-1.5 py-0.5 text-center text-[8px] font-semibold capitalize"
                                        :class="subj.status === 'fail' ? 'text-red-600' : 'text-green-700'"
                                    >
                                        {{ subj.remarks ?? subj.status }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Academic Summary & Grading Scale Grid -->
                    <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <!-- Academic Summary Box -->
                        <div v-if="result" class="academic-summary flex flex-col justify-between rounded-sm border border-black bg-gray-50/50 p-2">
                            <h4 class="summary-title text-gray-750 mb-1.5 border-b border-black pb-0.5 font-bold tracking-wider uppercase">
                                Academic Summary
                            </h4>
                            <div class="summary-text grid grid-cols-2 gap-x-3 gap-y-1">
                                <div class="flex justify-between border-b border-dashed border-gray-300 pb-0.5">
                                    <span class="text-gray-600">Max Marks:</span>
                                    <span class="font-bold">{{ result.total_max_marks }}</span>
                                </div>
                                <div class="flex justify-between border-b border-dashed border-gray-300 pb-0.5">
                                    <span class="text-gray-600">Marks Obtained:</span>
                                    <span class="font-bold">{{ result.total_marks_obtained }}</span>
                                </div>
                                <div class="flex justify-between border-b border-dashed border-gray-300 pb-0.5" v-if="result.percentage != null">
                                    <span class="text-gray-600">Percentage:</span>
                                    <span class="font-bold">{{ result.percentage }}%</span>
                                </div>
                                <div class="flex justify-between border-b border-dashed border-gray-300 pb-0.5">
                                    <span class="text-gray-600">GPA:</span>
                                    <span class="font-bold text-blue-900">{{ result.gpa }}</span>
                                </div>
                                <div class="flex justify-between border-b border-dashed border-gray-300 pb-0.5">
                                    <span class="text-gray-600">Overall Grade:</span>
                                    <span class="font-bold" :class="gradeColor(result.grade)">{{ result.grade }}</span>
                                </div>
                                <div class="flex justify-between border-b border-dashed border-gray-300 pb-0.5">
                                    <span class="text-gray-600">Rank:</span>
                                    <span class="font-bold">{{ result.rank ?? '—' }}</span>
                                </div>
                                <div
                                    class="border-gray-350 col-span-2 mt-1 flex items-center justify-between rounded-sm border bg-white px-1.5 py-0.5"
                                >
                                    <span class="text-[8px] font-bold text-gray-700 uppercase">Result Status:</span>
                                    <span
                                        class="py-0.2 rounded-sm border px-1.5 text-[8px] font-bold uppercase"
                                        :class="
                                            result.status === 'pass'
                                                ? 'border-green-200 bg-green-50 text-green-700'
                                                : 'border-red-200 bg-red-50 text-red-700'
                                        "
                                    >
                                        {{ result.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Grading Scale Box -->
                        <div class="rounded-sm border border-black bg-gray-50/50 p-2">
                            <h4 class="legend-title text-gray-750 mb-1.5 border-b border-black pb-0.5 font-bold tracking-wider uppercase">
                                Nepal Grading Scale Reference
                            </h4>
                            <table class="legend-text w-full border-collapse text-center">
                                <thead>
                                    <tr class="bg-gray-150 border-b border-gray-300 font-bold text-gray-600">
                                        <th class="border-r border-gray-200 py-0.5">Interval (%)</th>
                                        <th class="border-r border-gray-200 py-0.5">Grade</th>
                                        <th class="border-r border-gray-200 py-0.5">GP</th>
                                        <th class="py-0.5">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">90 - 100</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-green-700">A+</td>
                                        <td class="py-0.2 border-gray-150 border-r">4.0</td>
                                        <td class="py-0.2 text-gray-650">Outstanding</td>
                                    </tr>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">80 - 89</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-green-700">A</td>
                                        <td class="py-0.2 border-gray-150 border-r">3.6</td>
                                        <td class="py-0.2 text-gray-650">Excellent</td>
                                    </tr>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">70 - 79</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-blue-700">B+</td>
                                        <td class="py-0.2 border-gray-150 border-r">3.2</td>
                                        <td class="py-0.2 text-gray-650">Very Good</td>
                                    </tr>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">60 - 69</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-blue-700">B</td>
                                        <td class="py-0.2 border-gray-150 border-r">2.8</td>
                                        <td class="py-0.2 text-gray-650">Good</td>
                                    </tr>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">50 - 59</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-amber-700">C+</td>
                                        <td class="py-0.2 border-gray-150 border-r">2.4</td>
                                        <td class="py-0.2 text-gray-650">Satisfactory</td>
                                    </tr>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">40 - 49</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-amber-700">C</td>
                                        <td class="py-0.2 border-gray-150 border-r">2.0</td>
                                        <td class="py-0.2 text-gray-650">Acceptable</td>
                                    </tr>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">30 - 39</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-orange-700">D+</td>
                                        <td class="py-0.2 border-gray-150 border-r">1.6</td>
                                        <td class="py-0.2 text-gray-650 font-medium">Partially Acceptable</td>
                                    </tr>
                                    <tr class="border-gray-150 border-b">
                                        <td class="py-0.2 border-gray-150 border-r">20 - 29</td>
                                        <td class="py-0.2 border-gray-150 border-r font-bold text-orange-700">D</td>
                                        <td class="py-0.2 border-gray-150 border-r">1.2</td>
                                        <td class="py-0.2 text-gray-650">Insufficient</td>
                                    </tr>
                                    <tr>
                                        <td class="py-0.2 border-r border-gray-200">&lt; 20</td>
                                        <td class="py-0.2 border-r border-gray-200 font-bold text-red-700">NG</td>
                                        <td class="py-0.2 border-r border-gray-200">0.0</td>
                                        <td class="py-0.2 text-red-650 font-bold">Not Graded</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Signatures Section -->
                    <div class="sig-container mt-6 grid grid-cols-3 gap-2 border-t border-dashed border-gray-400 pt-5 text-center font-bold">
                        <div class="flex flex-col items-center justify-end">
                            <div class="mb-1 w-24 border-b border-black"></div>
                            <span class="sig-text text-gray-700">Class Teacher</span>
                        </div>
                        <div class="flex flex-col items-center justify-end">
                            <div
                                class="sig-seal mb-1 flex h-10 w-10 items-center justify-center rounded-full border border-dashed border-gray-300 text-gray-400 select-none"
                            >
                                SEAL
                            </div>
                            <div class="mb-1 w-24 border-b border-black"></div>
                            <span class="sig-text text-gray-700">Exam Controller</span>
                        </div>
                        <div class="flex flex-col items-center justify-end">
                            <div class="mb-1 w-24 border-b border-black"></div>
                            <span class="sig-text text-gray-700">Principal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    /* Hide unnecessary screen elements */
    nav,
    .print\:hidden,
    header,
    aside,
    button,
    .breadcrumbs {
        display: none !important;
    }

    /* Reset body styles for clean printing */
    body,
    html {
        background: white !important;
        color: black !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
        font-family:
            ui-sans-serif,
            system-ui,
            -apple-system,
            BlinkMacSystemFont,
            'Segoe UI',
            Roboto,
            'Helvetica Neue',
            Arial,
            sans-serif !important;
    }

    /* Force print background colors and borders correctly */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
    }

    /* Target all elements inside the print marksheet to be black and readable */
    #print-marksheet {
        display: block !important;
        color: #000000 !important;
        background-color: #ffffff !important;
    }

    #print-marksheet * {
        color: #000000 !important;
        border-color: #000000 !important;
    }

    /* Keep color highlights readable and high contrast when printed */
    #print-marksheet .text-red-600,
    #print-marksheet .text-red-700 {
        color: #b91c1c !important;
        font-weight: bold !important;
    }

    #print-marksheet .text-green-600,
    #print-marksheet .text-green-700 {
        color: #166534 !important; /* Dark green for print visibility */
        font-weight: bold !important;
    }

    #print-marksheet .text-blue-900 {
        color: #1e3a8a !important;
    }

    #print-marksheet .bg-gray-100 {
        background-color: #f3f4f6 !important;
    }

    #print-marksheet .bg-gray-50\/50 {
        background-color: #f9fafb !important;
    }

    /* Margins for the print page */
    @page {
        size: auto;
        margin: 8mm 8mm;
    }

    /* Default A4 typography and spacings */
    #print-marksheet .school-title {
        font-size: 18px !important;
    }
    #print-marksheet .school-sub {
        font-size: 11px !important;
    }
    #print-marksheet .school-contact {
        font-size: 9.5px !important;
    }
    #print-marksheet .exam-title {
        font-size: 14px !important;
    }
    #print-marksheet .exam-sub {
        font-size: 10px !important;
    }
    #print-marksheet .section-title {
        font-size: 11px !important;
    }
    #print-marksheet .student-info {
        font-size: 11px !important;
    }
    #print-marksheet .marks-table th {
        font-size: 10px !important;
        padding: 6px 4px !important;
    }
    #print-marksheet .marks-table td {
        font-size: 10px !important;
        padding: 5px 4px !important;
    }
    #print-marksheet .summary-title {
        font-size: 10px !important;
    }
    #print-marksheet .summary-text {
        font-size: 10px !important;
    }
    #print-marksheet .legend-title {
        font-size: 9.5px !important;
    }
    #print-marksheet .legend-text {
        font-size: 8px !important;
    }
    #print-marksheet .sig-text {
        font-size: 10px !important;
    }

    #print-marksheet .main-wrapper {
        padding: 16px !important;
        border-width: 3px !important;
        min-height: 260mm; /* A4 Portrait height minus margins */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* A5 Specific Layout and Sizing (when paper width is A5 size, ~14.8cm) */
    @media (max-width: 16cm) {
        @page {
            margin: 5mm 5mm;
        }

        #print-marksheet .main-wrapper {
            padding: 8px !important;
            border-width: 2px !important;
            min-height: 185mm; /* A5 Portrait height minus margins */
        }

        #print-marksheet .school-title {
            font-size: 13px !important;
        }
        #print-marksheet .school-sub {
            font-size: 9px !important;
        }
        #print-marksheet .school-contact {
            font-size: 7.5px !important;
        }
        #print-marksheet .exam-title {
            font-size: 10px !important;
        }
        #print-marksheet .exam-sub {
            font-size: 8px !important;
        }
        #print-marksheet .section-title {
            font-size: 8.5px !important;
        }
        #print-marksheet .student-info {
            font-size: 8.5px !important;
        }
        #print-marksheet .marks-table th {
            font-size: 8px !important;
            padding: 3px 2px !important;
        }
        #print-marksheet .marks-table td {
            font-size: 7.5px !important;
            padding: 2px 2px !important;
        }
        #print-marksheet .summary-title {
            font-size: 8.5px !important;
        }
        #print-marksheet .summary-text {
            font-size: 8px !important;
        }
        #print-marksheet .legend-title {
            font-size: 8px !important;
        }
        #print-marksheet .legend-text {
            font-size: 7px !important;
        }
        #print-marksheet .sig-text {
            font-size: 8px !important;
        }

        #print-marksheet .logo-svg {
            width: 32px !important;
            height: 32px !important;
        }
        #print-marksheet .header-logo-container {
            width: 36px !important;
            height: 36px !important;
        }

        #print-marksheet .estd-badge {
            font-size: 7px !important;
            padding: 1px !important;
        }

        #print-marksheet .sig-seal {
            width: 28px !important;
            height: 28px !important;
            font-size: 5px !important;
        }

        #print-marksheet .grid {
            gap: 4px !important;
        }

        #print-marksheet .mb-3 {
            margin-bottom: 6px !important;
        }
        #print-marksheet .mb-4 {
            margin-bottom: 8px !important;
        }
        #print-marksheet .pb-2.5 {
            padding-bottom: 6px !important;
        }
        #print-marksheet .mb-2.5 {
            margin-bottom: 6px !important;
        }
        #print-marksheet .mt-6 {
            margin-top: 12px !important;
        }
    }

    /* Remove any default layout padding/margins from parent templates */
    main,
    .flex-1,
    .p-4 {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
    }

    .rounded-2xl,
    .rounded-xl {
        border-radius: 0 !important;
    }
}
</style>
