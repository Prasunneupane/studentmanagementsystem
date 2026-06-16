<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import DatePicker from '@/components/ui/datepicker/DatePicker.vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import {
    AlertTriangle,
    BookOpen,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Edit,
    Eye,
    FileSpreadsheet,
    FileText,
    Loader2,
    Plus,
    Printer,
    Save,
    Search,
    ToggleLeft,
    ToggleRight,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import 'vue-sonner/style.css';
import * as XLSX from 'xlsx';
import CustomSelect from '../CustomSelect.vue';

const { toast } = useToast();

// ── Types ────────────────────────────────────────────────────────
interface Exam {
    id: number;
    name: string;
    exam_type: string;
    status: string;
    is_active: boolean;
    is_published: boolean;
    start_date: string;
    end_date: string;
    weightage: number | null;
    exam_schedules_count: number;
    exam_classes_count: number;
    academic_year: { id: number; name: string } | null;
    term: { id: number; name: string } | null;
}

interface ScheduleRow {
    id: number;
    subject_name: string;
    exam_date: string;
    start_time: string;
    end_time: string;
    room_no: string;
    max_theory_marks: number | null;
    max_practical_marks: number | null;
    max_total_marks: number;
    pass_marks: number;
}

interface ClassGroup {
    class_id: number;
    class_name: string;
    section_id: number;
    section_name: string;
    schedules: ScheduleRow[];
}

interface Props {
    exams: {
        data: Exam[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        from: number;
        to: number;
    };
    academicYears: { value: string; label: string }[];
    terms: { value: string; label: string }[];
    filters: {
        academic_year_id?: string;
        exam_type?: string;
        status?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Exams', href: '/exams/exam-schedules' },
    { title: 'Schedules', href: '/exams/exam-schedules' },
];

// ── Stats ────────────────────────────────────────────────────────
const stats = computed(() => {
    const all = props.exams.data;
    console.log(all);

    return {
        total: props.exams.total,
        ongoing: all.filter((e) => e.status === 'ongoing').length,
        upcoming: all.filter((e) => e.status === 'upcoming').length,
        completed: all.filter((e) => e.status === 'completed').length,
    };
});

// ── Filters ──────────────────────────────────────────────────────
const search = ref(props.filters.search || '');
const yearId = ref(props.filters.academic_year_id || '');
const examType = ref(props.filters.exam_type || '');
const status = ref(props.filters.status || '');

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch([yearId, examType, status], () => applyFilters());

const applyFilters = () => {
    router.get(
        '/exams/exam-schedules',
        {
            search: search.value || undefined,
            academic_year_id: yearId.value || undefined,
            exam_type: examType.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    yearId.value = '';
    examType.value = '';
    status.value = '';
    applyFilters();
};
const isEditModalOpen = ref(false);
const editForm = useForm({
    id: null as number | null,
    name: '',
    exam_type: '',
    academic_year_id: '',
    term_id: '',
    start_date: '',
    end_date: '',
    weightage: '100',
    is_published: false,
});

const formatDateForInput = (val: any): string => {
    if (!val) return '';
    const d = val instanceof Date ? val : new Date(val);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const editExam = (exam: Exam) => {
    // console.log(exam,"exam");

    editForm.id = exam.id;
    editForm.name = exam.name;
    editForm.exam_type = exam.exam_type;
    editForm.academic_year_id = String(exam.academic_year?.id || '');
    editForm.term_id = String(exam.term?.id || '');
    editForm.start_date = exam.start_date;
    editForm.end_date = exam.end_date;
    editForm.weightage = String(exam.weightage ?? '100');
    editForm.is_published = Boolean(exam.is_published);
    isEditModalOpen.value = true;
};

const submitUpdate = () => {
    if (!editForm.id) return;
    // Force boolean conversion
    editForm.is_published = editForm.is_published === true;
    editForm.put(`/exams/update/${editForm.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            toast.success('Exam details updated successfully');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.error(firstError as string);
        },
    });
};

const hasFilters = computed(() => !!(search.value || yearId.value || examType.value || status.value));

// ── Helpers ──────────────────────────────────────────────────────
const examTypeOptions = [
    { value: 'unit_test', label: 'Unit Test' },
    { value: 'midterm', label: 'Mid Term' },
    { value: 'final', label: 'Final' },
    { value: 'semester', label: 'Semester' },
    { value: 'annual', label: 'Annual' },
];

const statusOptions = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'ongoing', label: 'Ongoing' },
    { value: 'completed', label: 'Completed' },
    { value: 'draft', label: 'Draft' },
];

const statusCfg: Record<string, { label: string; cls: string }> = {
    ongoing: { label: 'Ongoing', cls: 'bg-green-100  text-green-800  border-green-200' },
    upcoming: { label: 'Upcoming', cls: 'bg-blue-100   text-blue-800   border-blue-200' },
    completed: { label: 'Completed', cls: 'bg-purple-100 text-purple-800 border-purple-200' },
    draft: { label: 'Draft', cls: 'bg-gray-100   text-gray-700   border-gray-200' },
};
const getCfg = (s: string) => statusCfg[s] ?? statusCfg['draft'];

const typeLabel: Record<string, string> = {
    unit_test: 'Unit Test',
    midterm: 'Mid Term',
    final: 'Final',
    semester: 'Semester',
    annual: 'Annual',
};

const fmt = (d: string) => (d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—');

const fmtTime = (t: string) => {
    if (!t) return '—';
    const [h, m] = t.split(':');
    const hr = parseInt(h);
    return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
};

// ── Toggle Active ─────────────────────────────────────────────────
const togglingId = ref<number | null>(null);
const toggleActive = (exam: Exam) => {
    togglingId.value = exam.id;
    router.patch(
        `/exams/exam-schedules/${exam.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Exam marked ${exam.is_active ? 'inactive' : 'active'}`);
                togglingId.value = null;
            },
            onError: () => {
                toast.error('Failed to update status');
                togglingId.value = null;
            },
        },
    );
};

// ── Delete ────────────────────────────────────────────────────────
const deleteId = ref<number | null>(null);
const deleting = ref(false);
const confirmDelete = (id: number) => {
    deleteId.value = id;
};
const cancelDelete = () => {
    deleteId.value = null;
};
const doDelete = () => {
    if (!deleteId.value) return;
    deleting.value = true;
    router.delete(`/exams/exam-schedules/${deleteId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Schedule deleted successfully');
            deleteId.value = null;
            deleting.value = false;
        },
        onError: () => {
            toast.error('Failed to delete schedule');
            deleting.value = false;
        },
    });
};

// ── Slide-over panel ─────────────────────────────────────────────
const panelOpen = ref(false);
const panelExam = ref<Exam | null>(null);
const panelLoading = ref(false);
const panelGroups = ref<ClassGroup[]>([]);
const collapsed = ref<Record<string, boolean>>({});

const openPanel = async (exam: Exam) => {
    // panelExam.value  = exam
    // panelGroups.value = []
    // panelOpen.value  = true
    // panelLoading.value = true
    // collapsed.value  = {}

    // try {
    //   const res = await fetch(`/exams/${exam.id}/schedule`, {
    //     headers: { 'X-Inertia': 'true', 'X-Inertia-Version': '', Accept: 'application/json' },
    //   })
    //   const json = await res.json()
    //   panelGroups.value = json.props?.groupedSchedule ?? []
    // } catch {
    //   toast.error('Failed to load schedule details')
    // } finally {
    //   panelLoading.value = false
    // }
    route.visit('exams/exam-schedules/' + exam.id, {
        method: 'get',
    });
};

const closePanel = () => {
    panelOpen.value = false;
    panelExam.value = null;
    panelGroups.value = [];
};

const toggleGroup = (key: string) => {
    collapsed.value[key] = !collapsed.value[key];
};

// ── Subject colors ───────────────────────────────────────────────
const colorPool = [
    'bg-blue-100 text-blue-800',
    'bg-teal-100 text-teal-800',
    'bg-purple-100 text-purple-800',
    'bg-amber-100 text-amber-800',
    'bg-rose-100 text-rose-800',
    'bg-cyan-100 text-cyan-800',
];
const colorMap: Record<string, string> = {};
let ci = 0;
const subjColor = (name: string) => {
    if (!colorMap[name]) colorMap[name] = colorPool[ci++ % colorPool.length];
    return colorMap[name];
};

const headers = ['S.No', 'Exam', 'Type', 'Period', 'Schedules', 'Status', 'Active'];
const data = props.exams.data.map((e, i) => [
    (props.exams.from + i).toString(),
    e.name,
    typeLabel[e.exam_type] ?? e.exam_type,
    `${fmt(e.start_date)} → ${fmt(e.end_date)}`,
    `${e.exam_schedules_count} schedule${e.exam_schedules_count !== 1 ? 's' : ''}`,
    getCfg(e.status).label,
    e.is_active ? 'True' : 'False',
]);
const printSchedule = () => {
    const title = 'Exam Schedules List';
    const printWindow = window.open('', '_blank');
    if (!printWindow) return;

    const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>${title}</title>
      <style>
        @media print {
          @page {
            size: A4;
           
          }
          body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
        }
        body {
          font-family: Arial, sans-serif;
          padding: 20px;
          margin: 0;
        }
        h1 {
          text-align: center;
          margin-bottom: 20px;
          font-size: 24px;
          color: #333;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
        }
        th, td {
          border: 1px solid #ddd;
          padding: 12px 8px;
          text-align: left;
          font-size: 12px;
        }
        th {
          background-color: #f3f4f6;
          font-weight: bold;
          color: #374151;
        }
        tr:nth-child(even) {
          background-color: #f9fafb;
        }
        tr:hover {
          background-color: #f3f4f6;
        }
        .no-print {
          display: none;
        }
        .print-info {
          text-align: center;
          margin-top: 20px;
          font-size: 11px;
          color: #666;
        }
      </style>
    </head>
    <body>
      <h1>${title}</h1>
      <p style="text-align: center; color: #666; margin-bottom: 20px;">
        Generated on ${new Date().toLocaleString()}
      </p>
      <table>
        <thead>
          <tr>
            ${headers.map((h) => `<th>${h}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${data
              .map(
                  (row) => `
            <tr>
              ${row.map((cell) => `<td>${cell}</td>`).join('')}
            </tr>
          `,
              )
              .join('')}
        </tbody>
      </table>
      <div class="print-info">
        Total Records: ${data.length}
      </div>
    </body>
    </html>
  `;

    printWindow.document.write(htmlContent);
    printWindow.document.close();

    // Wait for content to load then print
    printWindow.onload = () => {
        printWindow.focus();
        printWindow.print();
    };
    printWindow.onafterprint = function () {
        printWindow.close();
    };
};

const exportToExcel = () => {
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);

    // Set column widths
    const colWidths = headers.map(() => ({ wch: 15 }));
    ws['!cols'] = colWidths;

    // Create workbook
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Data');

    // Generate filename
    const filename = `${'data'}_${new Date().toISOString().split('T')[0]}.xlsx`;

    // Save file
    XLSX.writeFile(wb, filename);
};

const printPDF = () => {
    const title = 'Exam Schedules List';

    // Create PDF
    const doc = new jsPDF('l', 'mm', 'a4'); // landscape, millimeters, A4

    // Add title
    doc.setFontSize(16);
    doc.text(title, 14, 15);

    // Add date
    doc.setFontSize(10);
    doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);

    // Add table
    autoTable(doc, {
        head: [headers],
        body: data,
        startY: 28,
        styles: {
            fontSize: 9,
            cellPadding: 3,
        },
        headStyles: {
            fillColor: [59, 130, 246], // Blue color
            textColor: 255,
            fontStyle: 'bold',
        },
        alternateRowStyles: {
            fillColor: [249, 250, 251], // Light gray
        },
        margin: { top: 28, right: 14, bottom: 14, left: 14 },
    });

    // Add footer
    const pageCount = doc.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.getWidth() / 2, doc.internal.pageSize.getHeight() - 10, { align: 'center' });
    }

    // Save PDF
    const filename = `${'data'}_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(filename);
};
</script>

<template>
    <Head title="Exam Schedules" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toaster />

        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">
            <!-- ── Page Header ─────────────────────────────────────── -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">Exam Schedules</h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">Manage and view all exam timetables</p>
                </div>
                <Button @click="router.visit('/exams/create')" class="cursor-pointer"> <Plus class="mr-1.5 h-4 w-4" /> Add Schedule </Button>
            </div>

            <!-- ── Stats ──────────────────────────────────────────── -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl border bg-card p-4">
                    <div class="text-2xl font-bold tracking-tight">{{ exams.total }}</div>
                    <div class="mt-0.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">Total</div>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <div class="text-2xl font-bold tracking-tight text-blue-600">{{ stats.upcoming }}</div>
                    <div class="mt-0.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">Upcoming</div>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <div class="text-2xl font-bold tracking-tight text-green-700">{{ stats.ongoing }}</div>
                    <div class="mt-0.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">Ongoing</div>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <div class="text-2xl font-bold tracking-tight text-purple-700">{{ stats.completed }}</div>
                    <div class="mt-0.5 text-xs font-medium tracking-wide text-muted-foreground uppercase">Completed</div>
                </div>
            </div>

            <!-- ── Filters ─────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative min-w-[180px] flex-1">
                    <Search class="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" placeholder="Search exam name..." class="h-9 pl-8 text-sm" />
                </div>
                <select v-model="yearId" class="h-9 min-w-[150px] rounded-md border bg-background px-3 text-sm text-foreground">
                    <option value="">All Academic Years</option>
                    <option v-for="y in academicYears" :key="y.value" :value="y.value">{{ y.label }}</option>
                </select>
                <select v-model="examType" class="h-9 min-w-[130px] rounded-md border bg-background px-3 text-sm text-foreground">
                    <option value="">All Types</option>
                    <option v-for="t in examTypeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <select v-model="status" class="h-9 min-w-[120px] rounded-md border bg-background px-3 text-sm text-foreground">
                    <option value="">All Status</option>
                    <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
                <Button v-if="hasFilters" variant="ghost" size="sm" @click="clearFilters"> <X class="mr-1 h-3.5 w-3.5" /> Clear </Button>
            </div>

            <!-- ── Delete Confirm Dialog ───────────────────────────── -->
            <div v-if="deleteId" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                <div class="mx-4 w-full max-w-sm rounded-2xl border bg-card p-6 shadow-xl">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="flex-shrink-0 rounded-lg bg-red-100 p-2">
                            <AlertTriangle class="h-5 w-5 text-red-600" />
                        </div>
                        <div>
                            <h3 class="text-base font-semibold">Delete Schedule?</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                This will permanently remove all schedule rows for
                                <strong>{{ exams.data.find((e) => e.id === deleteId)?.name }}</strong
                                >. This action cannot be undone.
                            </p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" size="sm" @click="cancelDelete" :disabled="deleting">Cancel</Button>
                        <Button size="sm" class="bg-red-600 text-white hover:bg-red-700" @click="doDelete" :disabled="deleting">
                            <Trash2 class="mr-1.5 h-3.5 w-3.5" />
                            {{ deleting ? 'Deleting...' : 'Yes, Delete' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ── Table ───────────────────────────────────────────── -->
            <div class="flex items-center justify-end gap-2">
                <Button variant="outline" v-if="exams.data.length > 0" size="sm" class="float-right gap-2 text-black" @click="printPDF">
                    <FileText class="h-4 w-4" />
                    PDF
                </Button>

                <Button variant="outline" v-if="exams.data.length > 0" size="sm" class="float-right gap-2 text-black" @click="exportToExcel">
                    <FileSpreadsheet class="h-4 w-4" />
                    Excel
                </Button>
                <Button variant="outline" v-if="exams.data.length > 0" size="sm" class="float-right gap-2 text-black" @click="printSchedule">
                    <Printer class="h-4 w-4" />
                    Print
                </Button>
            </div>
            <div class="overflow-hidden rounded-2xl border bg-card">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30">
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Exam</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Period</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Schedules</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Active</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-muted-foreground uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="exams.data.length === 0">
                                <tr>
                                    <td colspan="8" class="py-16 text-center text-muted-foreground">
                                        <BookOpen class="mx-auto mb-3 h-10 w-10 opacity-30" />
                                        <p class="font-medium">No exams found</p>
                                        <p class="mt-1 text-xs">Try adjusting your filters</p>
                                    </td>
                                </tr>
                            </template>

                            <tr v-for="(exam, i) in exams.data" :key="exam.id" class="border-b transition-colors last:border-b-0 hover:bg-muted/20">
                                <!-- # -->
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ exams.from + i }}
                                </td>

                                <!-- Exam name -->
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold">{{ exam.name }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">
                                        {{ exam.academic_year?.name }}
                                        <span v-if="exam.term"> · {{ exam.term.name }}</span>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium">{{ typeLabel[exam.exam_type] ?? exam.exam_type }}</span>
                                </td>

                                <!-- Period -->
                                <td class="px-4 py-3 text-xs leading-5 text-muted-foreground">
                                    {{ fmt(exam.start_date) }}<br />{{ fmt(exam.end_date) }}
                                </td>

                                <!-- Schedule count -->
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-lg bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground"
                                    >
                                        <Calendar class="h-3 w-3" />
                                        {{ exam.exam_schedules_count }}
                                        schedule{{ exam.exam_schedules_count !== 1 ? 's' : '' }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
                                        :class="getCfg(exam.status).cls"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ getCfg(exam.status).label }}
                                    </span>
                                </td>

                                <!-- Active toggle -->
                                <td class="px-4 py-3">
                                    <button class="group flex items-center gap-1.5" :disabled="togglingId === exam.id" @click="toggleActive(exam)">
                                        <component
                                            :is="exam.is_active ? ToggleRight : ToggleLeft"
                                            class="h-7 w-7 transition-colors"
                                            :class="exam.is_active ? 'text-green-600' : 'text-muted-foreground'"
                                        />
                                        <span class="text-xs font-medium" :class="exam.is_active ? 'text-green-700' : 'text-muted-foreground'">
                                            {{ exam.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 cursor-pointer px-2.5 text-xs hover:bg-primary/50"
                                            @click="editExam(exam)"
                                        >
                                            <Edit class="mr-1 h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="text-white-600 h-7 cursor-pointer px-2.5 text-xs hover:bg-blue-50"
                                            @click="router.visit(`/exams/exam-schedules/${exam?.id}`)"
                                        >
                                            <Eye class="mr-1 h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 cursor-pointer border-red-200 px-2.5 text-xs text-red-600 hover:bg-red-50"
                                            @click="confirmDelete(exam.id)"
                                        >
                                            <Trash2 class="mr-1 h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="exams.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
                    <span>Showing {{ exams.from }}–{{ exams.to }} of {{ exams.total }}</span>
                    <div class="flex gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="exams.current_page === 1"
                            @click="router.get('/exam-schedules', { ...filters, page: exams.current_page - 1 }, { preserveState: true })"
                        >
                            Prev
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="exams.current_page === exams.last_page"
                            @click="router.get('/exam-schedules', { ...filters, page: exams.current_page + 1 }, { preserveState: true })"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════ -->
        <!-- SLIDE-OVER PANEL                                        -->
        <!-- ════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="panel">
                <div v-if="panelOpen" class="fixed inset-0 z-50 flex justify-end" @click.self="closePanel">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/30" @click="closePanel" />

                    <!-- Panel -->
                    <div class="relative z-10 flex h-full w-full max-w-2xl flex-col border-l bg-background shadow-2xl">
                        <!-- Panel Header -->
                        <div class="flex flex-shrink-0 items-start justify-between border-b p-5">
                            <div class="min-w-0 flex-1 pr-4">
                                <h2 class="truncate text-base font-bold">{{ panelExam?.name }}</h2>
                                <p class="mt-0.5 text-sm text-muted-foreground">
                                    {{ typeLabel[panelExam?.exam_type ?? ''] ?? panelExam?.exam_type }}
                                    <span v-if="panelExam?.academic_year"> · {{ panelExam.academic_year.name }}</span>
                                    <span v-if="panelExam?.term"> · {{ panelExam.term.name }}</span>
                                </p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold"
                                        :class="getCfg(panelExam?.status ?? '').cls"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ getCfg(panelExam?.status ?? '').label }}
                                    </span>
                                    <span
                                        v-if="panelExam?.is_published"
                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                    >
                                        <CheckCircle2 class="h-3 w-3" /> Published
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ fmt(panelExam?.start_date ?? '') }} → {{ fmt(panelExam?.end_date ?? '') }}
                                    </span>
                                </div>
                            </div>
                            <Button variant="ghost" size="sm" @click="closePanel">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Panel Body -->
                        <div class="flex-1 space-y-3 overflow-y-auto p-5">
                            <!-- Loading -->
                            <div v-if="panelLoading" class="flex items-center justify-center py-16">
                                <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                            </div>

                            <!-- Empty -->
                            <div v-else-if="panelGroups.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                                <Calendar class="mb-3 h-10 w-10 text-muted-foreground/30" />
                                <p class="font-medium text-muted-foreground">No schedules added yet</p>
                                <p class="mt-1 text-xs text-muted-foreground/70">Add subjects to this exam schedule</p>
                                <Button size="sm" class="mt-4" @click="router.visit(`/exam-schedules/${panelExam?.id}/create`)">
                                    <Plus class="mr-1.5 h-3.5 w-3.5" /> Add Schedule
                                </Button>
                            </div>

                            <!-- Groups -->
                            <template v-else>
                                <div v-for="grp in panelGroups" :key="`${grp.class_id}_${grp.section_id}`" class="overflow-hidden rounded-xl border">
                                    <!-- Group header -->
                                    <div
                                        class="flex cursor-pointer items-center justify-between bg-muted/30 px-4 py-2.5 transition-colors hover:bg-muted/50"
                                        @click="toggleGroup(`${grp.class_id}_${grp.section_id}`)"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-2 rounded-full bg-primary" />
                                            <span class="text-sm font-semibold">{{ grp.class_name }}</span>
                                            <span class="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">
                                                Section {{ grp.section_name }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-muted-foreground">{{ grp.schedules.length }} subjects</span>
                                            <component
                                                :is="collapsed[`${grp.class_id}_${grp.section_id}`] ? ChevronRight : ChevronDown"
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </div>
                                    </div>

                                    <!-- Schedule rows -->
                                    <div v-show="!collapsed[`${grp.class_id}_${grp.section_id}`]" class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b bg-muted/10">
                                                    <th class="px-3 py-2 text-left font-semibold tracking-wider text-muted-foreground uppercase">
                                                        Subject
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-semibold tracking-wider text-muted-foreground uppercase">
                                                        Date
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-semibold tracking-wider text-muted-foreground uppercase">
                                                        Time
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-semibold tracking-wider text-muted-foreground uppercase">
                                                        Room
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-semibold tracking-wider text-muted-foreground uppercase">
                                                        Marks
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="row in grp.schedules"
                                                    :key="row.id"
                                                    class="border-b transition-colors last:border-b-0 hover:bg-muted/10"
                                                >
                                                    <td class="px-3 py-2.5">
                                                        <span
                                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold"
                                                            :class="subjColor(row.subject_name)"
                                                        >
                                                            {{ row.subject_name }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2.5 font-medium">{{ fmt(row.exam_date) }}</td>
                                                    <td class="px-3 py-2.5">
                                                        <span class="rounded bg-muted px-1.5 py-0.5 font-mono text-xs">
                                                            {{ fmtTime(row.start_time) }} – {{ fmtTime(row.end_time) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2.5 text-muted-foreground">{{ row.room_no || '—' }}</td>
                                                    <td class="px-3 py-2.5">
                                                        <div class="flex flex-wrap gap-1">
                                                            <span
                                                                class="rounded bg-blue-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-blue-800"
                                                            >
                                                                T:{{ row.max_theory_marks ?? '—' }}
                                                            </span>
                                                            <span
                                                                v-if="row.max_practical_marks"
                                                                class="rounded bg-emerald-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-emerald-800"
                                                            >
                                                                P:{{ row.max_practical_marks }}
                                                            </span>
                                                            <span
                                                                class="rounded bg-purple-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-purple-800"
                                                            >
                                                                {{ row.max_total_marks }}
                                                            </span>
                                                            <span
                                                                class="rounded bg-amber-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-amber-800"
                                                            >
                                                                ✓{{ row.pass_marks }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Panel Footer -->
                        <div class="flex flex-shrink-0 items-center justify-between border-t p-4">
                            <span class="text-xs text-muted-foreground">
                                {{ panelGroups.reduce((a, g) => a + g.schedules.length, 0) }} schedules across {{ panelGroups.length }} sections
                            </span>
                            <Button size="sm" @click="router.visit(`/exams/exam-schedules/${panelExam?.id}`)">
                                <Eye class="mr-1.5 h-3.5 w-3.5" /> Full View
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Edit Exam Modal ─────────────────────────────────── -->
        <Dialog v-model:open="isEditModalOpen">
            <DialogContent class="overflow-hidden rounded-2xl p-0 sm:max-w-[600px]">
                <DialogHeader class="border-b bg-muted/30 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-primary/10 p-2">
                            <Edit class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <DialogTitle class="text-lg font-bold">Edit Exam Details</DialogTitle>
                            <p class="mt-0.5 text-xs text-muted-foreground">Update core information for this exam</p>
                        </div>
                    </div>
                </DialogHeader>

                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-6">
                    <!-- Exam Name -->
                    <div class="space-y-2">
                        <Label for="edit_name">Exam Name <span class="text-red-500">*</span></Label>
                        <Input id="edit_name" v-model="editForm.name" placeholder="e.g. First Terminal Exam" />
                    </div>

                    <!-- Type + Academic Year -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit_type">Exam Type <span class="text-red-500">*</span></Label>
                            <CustomSelect id="edit_type" v-model="editForm.exam_type" :options="examTypeOptions" placeholder="Select Type" />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_year">Academic Year <span class="text-red-500">*</span></Label>
                            <CustomSelect id="edit_year" v-model="editForm.academic_year_id" :options="academicYears" placeholder="Select Year" />
                        </div>
                    </div>

                    <!-- Term + Weightage -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="edit_term">Term <span class="text-[10px] text-muted-foreground">(optional)</span></Label>
                            <CustomSelect id="edit_term" v-model="editForm.term_id" :options="terms" placeholder="Select Term" />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit_weightage">Weightage (%)</Label>
                            <Input id="edit_weightage" type="number" v-model="editForm.weightage" placeholder="100" />
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Start Date <span class="text-red-500">*</span></Label>
                            <DatePicker
                                :model-value="editForm.start_date"
                                @update:model-value="(val) => (editForm.start_date = formatDateForInput(val))"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label>End Date <span class="text-red-500">*</span></Label>
                            <DatePicker
                                :model-value="editForm.end_date"
                                @update:model-value="(val) => (editForm.end_date = formatDateForInput(val))"
                            />
                        </div>
                    </div>

                    <!-- Publish Toggle -->
                    <div class="flex items-start space-x-3 rounded-xl border bg-muted/50 p-3">
                        <Checkbox id="edit_published" v-model="editForm.is_published" />
                        <div class="grid gap-1 leading-none">
                            <Label for="edit_published" class="cursor-pointer font-medium">Published</Label>
                            <p class="text-[11px] text-muted-foreground">Make this exam visible in lists and reports</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2 border-t bg-muted/10 px-6 py-4">
                    <Button variant="outline" @click="isEditModalOpen = false" :disabled="editForm.processing">Cancel</Button>
                    <Button @click="submitUpdate" :disabled="editForm.processing">
                        <Loader2 v-if="editForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.panel-enter-active,
.panel-leave-active {
    transition: opacity 0.2s ease;
}

.panel-enter-active .relative.z-10,
.panel-leave-active .relative.z-10 {
    transition: transform 0.25s ease;
}

.panel-enter-from {
    opacity: 0;
}

.panel-enter-from .relative.z-10 {
    transform: translateX(100%);
}

.panel-leave-to {
    opacity: 0;
}

.panel-leave-to .relative.z-10 {
    transform: translateX(100%);
}
</style>
