<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    GraduationCap,
    CalendarCheck2,
    Wallet,
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    Bell,
    CalendarDays,
    ArrowUpRight,
    ChevronRight,
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
interface Props {
    dashboardData: {
        students: number;
        teachers: number;
        parents: number;
        staff: number;
        studentByClass: { name: string; count: number }[];
        thisMonthStudentCount: number;
    };
}
const props = defineProps<Props>();

/**
 * ---------------------------------------------------------------------
 * All data below is hardcoded for now. When wiring this up to Laravel,
 * replace this block with `defineProps<DashboardProps>()` — the shapes
 * are already structured to match what you'd pass from the controller.
 * ---------------------------------------------------------------------
 */
interface Kpi {
    label: string;
    value: string;
    delta: string;
    trend: 'up' | 'down';
    icon: typeof Users;
}

const kpis: Kpi[] = [
    { label: 'Total Students', value: props.dashboardData.students.toString(), delta: props.dashboardData.thisMonthStudentCount > 0 ? '+'+props.dashboardData.thisMonthStudentCount + ' this month': 'No new students this month', trend: props.dashboardData.thisMonthStudentCount > 0 ? 'up': 'down', icon: Users },
    { label: 'Total Teachers', value: props.dashboardData.teachers.toString(), delta: '76 present today', trend: 'up', icon: GraduationCap },
    { label: "Today's Attendance", value: '94.3%', delta: '71 absent', trend: 'down', icon: CalendarCheck2 },
    { label: 'Fee Collection', value: 'Rs. 8.4M', delta: 'Rs. 2.1M pending', trend: 'up', icon: Wallet },
];

const attendanceTrend = [
    { label: 'Mon', value: 92 },
    { label: 'Tue', value: 95 },
    { label: 'Wed', value: 89 },
    { label: 'Thu', value: 96 },
    { label: 'Fri', value: 94 },
    { label: 'Sat', value: 91 },
];

const classDistribution = [
    { label: 'C1', value: 85 },
    { label: 'C2', value: 92 },
    { label: 'C3', value: 88 },
    { label: 'C4', value: 95 },
    { label: 'C5', value: 79 },
    { label: 'C6', value: 101 },
    { label: 'C7', value: 96 },
    { label: 'C8', value: 110 },
    { label: 'C9', value: 118 },
    { label: 'C10', value: 124 },
];

const academicPerformance = [
    { label: 'Math', value: 68 },
    { label: 'Science', value: 74 },
    { label: 'English', value: 82 },
    { label: 'Nepali', value: 79 },
    { label: 'Social', value: 76 },
];

const alerts = [
    { text: '43 students have outstanding fees', level: 'warning' as const },
    { text: 'Class 8 attendance dropped below 85%', level: 'danger' as const },
    { text: '12 students have attendance below 75%', level: 'warning' as const },
    { text: '5 teachers have not submitted marks', level: 'warning' as const },
];

const activities = [
    { time: '10:32 AM', title: 'New student admitted', detail: 'Rahul Thapa — Class 8' },
    { time: '10:15 AM', title: 'Fee payment received', detail: 'Sita Sharma — Rs. 15,000' },
    { time: '09:45 AM', title: 'Examination result published', detail: 'Grade 10 — Terminal Examination' },
    { time: '09:20 AM', title: 'Teacher added', detail: 'Krishna Adhikari' },
];

const events = [
    { date: 'Aug 20', title: 'Parent-Teacher Meeting' },
    { date: 'Aug 22', title: 'Mathematics Examination' },
    { date: 'Aug 25', title: 'Sports Day' },
    { date: 'Sep 01', title: 'Monthly Test' },
];

const maxAttendance = Math.max(...attendanceTrend.map((d) => d.value));
const maxClass = Math.max(...classDistribution.map((d) => d.value));

const alertStyles: Record<'warning' | 'danger', string> = {
    warning: 'border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400',
    danger: 'border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400',
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Greeting -->
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">Good morning, Admin 👋</h1>
                <p class="text-sm text-muted-foreground">Here's what's happening across your school today.</p>
            </div>

            <!-- KPI cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card
                    v-for="kpi in kpis"
                    :key="kpi.label"
                    class="relative overflow-hidden border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <CardContent class="flex items-start justify-between p-5">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-sm text-muted-foreground">{{ kpi.label }}</span>
                            <span class="text-2xl font-semibold tracking-tight">{{ kpi.value }}</span>
                            <span
                                class="flex items-center gap-1 text-xs"
                                :class="kpi.trend === 'up' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                            >
                                <component :is="kpi.trend === 'up' ? TrendingUp : TrendingDown" class="size-3.5" />
                                {{ kpi.delta }}
                            </span>
                        </div>
                        <div class="rounded-lg bg-primary/10 p-2.5 text-primary">
                            <component :is="kpi.icon" class="size-5" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts row -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Attendance trend -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-base font-medium">Attendance Trend</CardTitle>
                        <span class="text-xs text-muted-foreground">Last 6 days</span>
                    </CardHeader>
                    <CardContent>
                        <div class="flex h-40 items-end gap-3">
                            <div v-for="d in attendanceTrend" :key="d.label" class="flex flex-1 flex-col items-center gap-2">
                                <div class="flex h-32 w-full items-end overflow-hidden rounded-md bg-muted">
                                    <div
                                        class="w-full rounded-md bg-primary transition-all"
                                        :style="{ height: `${(d.value / maxAttendance) * 100}%` }"
                                    />
                                </div>
                                <span class="text-xs text-muted-foreground">{{ d.label }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Class distribution -->
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-base font-medium">Students by Class</CardTitle>
                        <span class="text-xs text-muted-foreground">1,245 total</span>
                    </CardHeader>
                    <CardContent>
                        <div class="flex h-40 items-end gap-2">
                            <div v-for="d in classDistribution" :key="d.label" class="flex flex-1 flex-col items-center gap-2">
                                <div class="flex h-32 w-full items-end overflow-hidden rounded-md bg-muted">
                                    <div
                                        class="w-full rounded-md bg-sky-500/80 transition-all dark:bg-sky-400/80"
                                        :style="{ height: `${(d.value / maxClass) * 100}%` }"
                                    />
                                </div>
                                <span class="text-[10px] text-muted-foreground">{{ d.label }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Academic performance + Alerts -->
            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="border-sidebar-border/70 dark:border-sidebar-border lg:col-span-2">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base font-medium">Subject Performance</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <div v-for="s in academicPerformance" :key="s.label" class="flex items-center gap-3">
                            <span class="w-16 shrink-0 text-sm text-muted-foreground">{{ s.label }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${s.value}%` }" />
                            </div>
                            <span class="w-10 shrink-0 text-right text-sm font-medium">{{ s.value }}%</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center gap-2 pb-2">
                        <AlertTriangle class="size-4 text-amber-500" />
                        <CardTitle class="text-base font-medium">Alerts</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-2">
                        <div
                            v-for="(alert, i) in alerts"
                            :key="i"
                            class="rounded-lg border px-3 py-2 text-xs leading-snug"
                            :class="alertStyles[alert.level]"
                        >
                            {{ alert.text }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Activity + Events -->
            <div class="grid gap-4 lg:grid-cols-2">
                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <div class="flex items-center gap-2">
                            <Bell class="size-4 text-muted-foreground" />
                            <CardTitle class="text-base font-medium">Recent Activity</CardTitle>
                        </div>
                        <a href="#" class="flex items-center text-xs text-primary hover:underline">
                            View all <ChevronRight class="size-3" />
                        </a>
                    </CardHeader>
                    <CardContent class="flex flex-col divide-y divide-sidebar-border/70">
                        <div v-for="(a, i) in activities" :key="i" class="flex items-start justify-between gap-4 py-2.5 first:pt-0 last:pb-0">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-sm font-medium">{{ a.title }}</span>
                                <span class="text-xs text-muted-foreground">{{ a.detail }}</span>
                            </div>
                            <span class="shrink-0 text-xs text-muted-foreground">{{ a.time }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <div class="flex items-center gap-2">
                            <CalendarDays class="size-4 text-muted-foreground" />
                            <CardTitle class="text-base font-medium">Upcoming Events</CardTitle>
                        </div>
                        <a href="#" class="flex items-center text-xs text-primary hover:underline">
                            Calendar <ArrowUpRight class="size-3" />
                        </a>
                    </CardHeader>
                    <CardContent class="flex flex-col divide-y divide-sidebar-border/70">
                        <div v-for="(e, i) in events" :key="i" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                            <Badge variant="secondary" class="shrink-0 font-mono text-[10px]">{{ e.date }}</Badge>
                            <span class="text-sm">{{ e.title }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>