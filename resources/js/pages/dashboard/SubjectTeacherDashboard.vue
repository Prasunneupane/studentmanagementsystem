<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    Percent,
    CheckCircle2,
    ShieldAlert,
    AlertTriangle,
    Trophy,
    HeartHandshake,
    Rocket,
    BookOpen,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Mathematics', href: '/dashboard/subject' },
];

/**
 * ---------------------------------------------------------------------
 * Hardcoded for now. Replace with `defineProps<SubjectTeacherDashboardProps>()`
 * once wired to the backend. Shapes mirror a SubjectTeacherController
 * response: subject summary, per-class breakdown, topic/chapter analysis,
 * and risk-scored student lists. Role/permission gating is handled by
 * the caller/router, not this component.
 * ---------------------------------------------------------------------
 */
const subjectInfo = {
    subject: 'Mathematics',
    classes: ['Grade 8A', 'Grade 8B', 'Grade 9A'],
    teacher: 'Prasun Neupane',
};

interface Kpi {
    label: string;
    value: string;
    icon: typeof Users;
    tone: 'default' | 'danger';
}

const kpis: Kpi[] = [
    { label: 'My Students', value: '96', icon: Users, tone: 'default' },
    { label: 'Subject Average', value: '74.8%', icon: Percent, tone: 'default' },
    { label: 'Pass Rate', value: '87%', icon: CheckCircle2, tone: 'default' },
    { label: 'At Risk', value: '8 Students', icon: ShieldAlert, tone: 'danger' },
];

const actionRequired = [
    '8 students are below 50%',
    '5 students have declining performance',
    '3 students have attendance below 75%',
];

interface ClassRow {
    className: string;
    students: number;
    average: number;
    passRate: number;
}

const classPerformance: ClassRow[] = [
    { className: 'Grade 8A', students: 32, average: 78, passRate: 91 },
    { className: 'Grade 8B', students: 30, average: 72, passRate: 83 },
    { className: 'Grade 9A', students: 34, average: 68, passRate: 76 },
];

const maxClassAvg = Math.max(...classPerformance.map((c) => c.average));

const performanceTrend = [
    { label: 'Exam 1', value: 71 },
    { label: 'Exam 2', value: 74 },
    { label: 'Exam 3', value: 78 },
    { label: 'Current', value: 81 },
];
const maxTrend = Math.max(...performanceTrend.map((d) => d.value));

const topPerformers = [
    { name: 'Gita Sharma', value: '96%' },
    { name: 'Rahul Thapa', value: '94%' },
    { name: 'Sita Karki', value: '92%' },
];

const needsSupport = [
    { name: 'Hari Sharma', value: '42%' },
    { name: 'Ram Thapa', value: '46%' },
    { name: 'Sita Karki', value: '48%' },
];

interface TopicRow {
    topic: string;
    score: number;
    status: 'good' | 'attention' | 'critical';
}

const topicPerformance: TopicRow[] = [
    { topic: 'Algebra', score: 82, status: 'good' },
    { topic: 'Geometry', score: 74, status: 'good' },
    { topic: 'Fractions', score: 61, status: 'attention' },
    { topic: 'Statistics', score: 58, status: 'critical' },
    { topic: 'Mensuration', score: 79, status: 'good' },
];

const mostImproved = [
    { name: 'Gita Sharma', delta: '+18%' },
    { name: 'Ram Thapa', delta: '+14%' },
    { name: 'Anil Thapa', delta: '+12%' },
];
</script>

<template>
    <Head title="Subject Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">{{ subjectInfo.subject }}</h1>
                <div class="flex flex-wrap items-center gap-1.5">
                    <Badge v-for="c in subjectInfo.classes" :key="c" variant="secondary" class="text-xs font-normal">{{ c }}</Badge>
                </div>
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

            <!-- Performance by class + trend -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-medium">Performance by Class</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <div v-for="c in classPerformance" :key="c.className" class="flex items-center gap-3">
                            <span class="w-20 shrink-0 text-sm">{{ c.className }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-sky-500" :style="{ width: `${(c.average / maxClassAvg) * 100}%` }" />
                            </div>
                            <span class="w-10 shrink-0 text-right text-sm font-medium">{{ c.average }}%</span>
                            <span class="w-20 shrink-0 text-right text-xs text-muted-foreground">{{ c.students }} students</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-medium">Performance Trend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex h-32 items-end gap-3">
                            <div v-for="d in performanceTrend" :key="d.label" class="flex flex-1 flex-col items-center gap-2">
                                <div class="flex h-24 w-full items-end overflow-hidden rounded-md bg-muted">
                                    <div
                                        class="w-full rounded-md bg-primary transition-all"
                                        :style="{ height: `${(d.value / maxTrend) * 100}%` }"
                                    />
                                </div>
                                <span class="text-xs text-muted-foreground">{{ d.label }}</span>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-emerald-600 dark:text-emerald-400">↑ +10% since Exam 1</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Top performers + Needs support -->
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
                        <CardTitle class="text-base font-medium">Needs Support</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col divide-y divide-sidebar-border/70">
                        <div v-for="s in needsSupport" :key="s.name" class="flex items-center justify-between py-2 first:pt-0 last:pb-0">
                            <span class="text-sm">{{ s.name }}</span>
                            <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ s.value }}</span>
                        </div>
                        <p class="pt-2 text-xs text-muted-foreground">Common weak areas: Fractions, Statistics</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Topic performance + Most improved -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center gap-2 pb-2">
                        <BookOpen class="size-4 text-muted-foreground" />
                        <CardTitle class="text-base font-medium">Topic Performance</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <div v-for="t in topicPerformance" :key="t.topic" class="flex items-center gap-3">
                            <span class="w-24 shrink-0 text-sm">{{ t.topic }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full"
                                    :class="{
                                        'bg-emerald-500': t.status === 'good',
                                        'bg-amber-500': t.status === 'attention',
                                        'bg-red-500': t.status === 'critical',
                                    }"
                                    :style="{ width: `${t.score}%` }"
                                />
                            </div>
                            <span class="w-10 shrink-0 text-right text-sm font-medium">{{ t.score }}%</span>
                        </div>
                    </CardContent>
                </Card>

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
            </div>
        </div>
    </AppLayout>
</template>
