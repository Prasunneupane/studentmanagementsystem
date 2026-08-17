<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    CalendarCheck2,
    TrendingUp,
    ShieldAlert,
    AlertTriangle,
    Trophy,
    HeartHandshake,
    Rocket,
    CalendarDays,
    ChevronRight,
    CircleCheck,
    CircleAlert,
    CircleX,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Class 8A', href: '/dashboard/class' },
];

/**
 * ---------------------------------------------------------------------
 * Hardcoded for now. Replace with `defineProps<ClassTeacherDashboardProps>()`
 * once wired to the backend — shapes mirror what a ClassTeacherController
 * would naturally return (class summary, per-subject stats, risk-scored
 * student list, etc). Role/permission gating is handled by the caller.
 * ---------------------------------------------------------------------
 */
const classInfo = {
    grade: 'Grade 8',
    section: 'Section A',
    teacher: 'Prasun Neupane',
};

interface Kpi {
    label: string;
    value: string;
    icon: typeof Users;
    tone: 'default' | 'danger';
}

const kpis: Kpi[] = [
    { label: 'Students', value: '32', icon: Users, tone: 'default' },
    { label: 'Attendance', value: '93.4%', icon: CalendarCheck2, tone: 'default' },
    { label: 'Class Average', value: '76.8%', icon: TrendingUp, tone: 'default' },
    { label: 'At Risk', value: '5 Students', icon: ShieldAlert, tone: 'danger' },
];

const actionRequired = [
    '5 students need attention',
    '2 students have attendance below 75%',
    '3 students have declining performance',
    '4 marks are missing for the last assessment',
];

interface SubjectRow {
    subject: string;
    average: number;
    passRate: number;
    status: 'excellent' | 'good' | 'attention' | 'critical';
}

const subjectPerformance: SubjectRow[] = [
    { subject: 'Mathematics', average: 68, passRate: 81, status: 'attention' },
    { subject: 'Science', average: 82, passRate: 94, status: 'good' },
    { subject: 'English', average: 76, passRate: 91, status: 'good' },
    { subject: 'Nepali', average: 84, passRate: 97, status: 'excellent' },
    { subject: 'Social', average: 63, passRate: 72, status: 'critical' },
];

const statusMeta: Record<SubjectRow['status'], { label: string; icon: typeof CircleCheck; class: string }> = {
    excellent: { label: 'Excellent', icon: CircleCheck, class: 'text-emerald-600 dark:text-emerald-400' },
    good: { label: 'Good', icon: CircleCheck, class: 'text-emerald-600 dark:text-emerald-400' },
    attention: { label: 'Needs attention', icon: CircleAlert, class: 'text-amber-600 dark:text-amber-400' },
    critical: { label: 'Critical', icon: CircleX, class: 'text-red-600 dark:text-red-400' },
};

interface StudentRow {
    name: string;
    marks: Record<string, number>;
    avg: number;
}

const subjectCols = ['Math', 'Science', 'English', 'Nepali', 'Social'];

const studentMatrix: StudentRow[] = [
    { name: 'Gita Sharma', marks: { Math: 91, Science: 94, English: 89, Nepali: 92, Social: 90 }, avg: 91 },
    { name: 'Rahul Thapa', marks: { Math: 82, Science: 88, English: 79, Nepali: 90, Social: 85 }, avg: 85 },
    { name: 'Sita Karki', marks: { Math: 75, Science: 81, English: 84, Nepali: 88, Social: 78 }, avg: 81 },
    { name: 'Hari Sharma', marks: { Math: 42, Science: 58, English: 61, Nepali: 65, Social: 48 }, avg: 55 },
];

function markClass(mark: number) {
    if (mark < 50) return 'text-red-600 dark:text-red-400 font-medium';
    if (mark < 65) return 'text-amber-600 dark:text-amber-400 font-medium';
    return 'text-foreground';
}

const topPerformers = [
    { name: 'Gita Sharma', value: '91%' },
    { name: 'Rahul Thapa', value: '85%' },
    { name: 'Sita Karki', value: '81%' },
];

const needsAttention = [
    { name: 'Hari Sharma', value: '55%', tags: ['Academic', 'Attendance'] },
    { name: 'Ram Thapa', value: '58%', tags: ['Academic'] },
    { name: 'Sita Karki', value: '62%', tags: ['Declining'] },
];

const mostImproved = [
    { name: 'Gita Sharma', delta: '+18%' },
    { name: 'Ram Thapa', delta: '+14%' },
    { name: 'Sita Karki', delta: '+11%' },
];

const events = [
    { date: 'Aug 20', title: 'Mathematics Exam' },
    { date: 'Aug 22', title: 'Parent Meeting' },
    { date: 'Aug 25', title: 'Science Project' },
];
</script>

<template>
    <Head title="Class Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">{{ classInfo.grade }} — {{ classInfo.section }}</h1>
                <p class="text-sm text-muted-foreground">Class Teacher: {{ classInfo.teacher }}</p>
            </div>

            <!-- KPI cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card v-for="kpi in kpis" :key="kpi.label" class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardContent class="flex items-center justify-between p-5">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-sm text-muted-foreground">{{ kpi.label }}</span>
                            <span
                                class="text-2xl font-semibold tracking-tight"
                                :class="kpi.tone === 'danger' ? 'text-red-600 dark:text-red-400' : ''"
                            >
                                {{ kpi.value }}
                            </span>
                        </div>
                        <div
                            class="rounded-lg p-2.5"
                            :class="kpi.tone === 'danger' ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-primary/10 text-primary'"
                        >
                            <component :is="kpi.icon" class="size-5" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Action Required -->
            <Card class="border-amber-500/30 bg-amber-500/[0.04] dark:border-amber-500/20">
                <CardHeader class="flex flex-row items-center gap-2 pb-2">
                    <AlertTriangle class="size-4 text-amber-500" />
                    <CardTitle class="text-base font-medium">Action Required</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-2">
                    <div v-for="(item, i) in actionRequired" :key="i" class="flex items-center gap-2 text-sm">
                        <span class="size-1.5 shrink-0 rounded-full bg-amber-500" />
                        {{ item }}
                    </div>
                </CardContent>
            </Card>

            <!-- Subject performance -->
            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-medium">Subject Performance</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <div v-for="row in subjectPerformance" :key="row.subject" class="flex items-center gap-3">
                        <span class="w-24 shrink-0 text-sm">{{ row.subject }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full"
                                :class="{
                                    'bg-emerald-500': row.status === 'excellent' || row.status === 'good',
                                    'bg-amber-500': row.status === 'attention',
                                    'bg-red-500': row.status === 'critical',
                                }"
                                :style="{ width: `${row.average}%` }"
                            />
                        </div>
                        <span class="w-10 shrink-0 text-right text-sm font-medium">{{ row.average }}%</span>
                        <span class="flex w-36 shrink-0 items-center justify-end gap-1 text-xs" :class="statusMeta[row.status].class">
                            <component :is="statusMeta[row.status].icon" class="size-3.5" />
                            {{ statusMeta[row.status].label }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Student performance matrix -->
            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardHeader class="flex flex-row items-center justify-between pb-2">
                    <CardTitle class="text-base font-medium">Student Performance</CardTitle>
                    <a href="#" class="flex items-center text-xs text-primary hover:underline">
                        View full class <ChevronRight class="size-3" />
                    </a>
                </CardHeader>
                <CardContent class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-sm">
                        <thead>
                            <tr class="border-b border-sidebar-border/70 text-left text-xs text-muted-foreground">
                                <th class="py-2 font-normal">Student</th>
                                <th v-for="col in subjectCols" :key="col" class="py-2 text-right font-normal">{{ col }}</th>
                                <th class="py-2 text-right font-normal">Avg</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in studentMatrix" :key="s.name" class="border-b border-sidebar-border/40 last:border-0">
                                <td class="py-2.5">{{ s.name }}</td>
                                <td v-for="col in subjectCols" :key="col" class="py-2.5 text-right" :class="markClass(s.marks[col])">
                                    {{ s.marks[col] }}
                                </td>
                                <td class="py-2.5 text-right font-medium">{{ s.avg }}</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- Top performers + Needs attention -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center gap-2 pb-2">
                        <Trophy class="size-4 text-amber-500" />
                        <CardTitle class="text-base font-medium">Top Performers</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col divide-y divide-sidebar-border/70">
                        <div v-for="(s, i) in topPerformers" :key="s.name" class="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                            <span class="text-sm"><span class="mr-2 text-muted-foreground">{{ i + 1 }}.</span>{{ s.name }}</span>
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ s.value }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center gap-2 pb-2">
                        <HeartHandshake class="size-4 text-red-500" />
                        <CardTitle class="text-base font-medium">Needs Attention</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col divide-y divide-sidebar-border/70">
                        <div v-for="s in needsAttention" :key="s.name" class="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                            <div class="flex flex-col gap-1">
                                <span class="text-sm">{{ s.name }}</span>
                                <div class="flex gap-1">
                                    <Badge v-for="tag in s.tags" :key="tag" variant="outline" class="text-[10px]">{{ tag }}</Badge>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ s.value }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Most improved + Upcoming -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center gap-2 pb-2">
                        <Rocket class="size-4 text-sky-500" />
                        <CardTitle class="text-base font-medium">Most Improved</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col divide-y divide-sidebar-border/70">
                        <div v-for="s in mostImproved" :key="s.name" class="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                            <span class="text-sm">{{ s.name }}</span>
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ s.delta }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center gap-2 pb-2">
                        <CalendarDays class="size-4 text-muted-foreground" />
                        <CardTitle class="text-base font-medium">Upcoming</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col divide-y divide-sidebar-border/70">
                        <div v-for="(e, i) in events" :key="i" class="flex items-center gap-3 py-2 first:pt-0 last:pb-0">
                            <Badge variant="secondary" class="shrink-0 font-mono text-[10px]">{{ e.date }}</Badge>
                            <span class="text-sm">{{ e.title }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
