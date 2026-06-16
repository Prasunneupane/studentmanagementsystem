<script setup lang="ts">
/**
 * PrintMarks.vue — Reusable print button for marks tables
 *
 * Opens a styled print window with the full marks table.
 * No external dependencies — pure HTML/CSS print.
 *
 * Usage:
 *   <PrintMarks
 *     :students="students"
 *     :marks="marks"
 *     :exam="exam"
 *     :schedule="schedule"
 *     :subject-name="subjectName"
 *     :max-theory="maxTheory"
 *     :max-practical="maxPractical"
 *     :max-total="maxTotal"
 *     :pass-marks="passMarks"
 *     company-name="Greenwood Academy"
 *   />
 */

import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { Printer } from 'lucide-vue-next';

// ─── Types ────────────────────────────────────────────────────────
export interface PrintStudentRow {
    student_id: number;
    roll_no: string;
    name: string;
}

export interface PrintMarkEntry {
    student_id: number;
    theory_marks: string;
    practical_marks: string;
    total_marks: number;
    is_absent: boolean;
    remarks: string;
}

export interface PrintExam {
    name: string;
    exam_type?: string;
}

export interface PrintSchedule {
    exam_date?: string;
}

// ─── Props ────────────────────────────────────────────────────────
const props = withDefaults(
    defineProps<{
        students: PrintStudentRow[];
        marks: PrintMarkEntry[];
        exam: PrintExam;
        schedule?: PrintSchedule | null;
        subjectName?: string;
        maxTheory?: number;
        maxPractical?: number;
        maxTotal?: number;
        passMarks?: number;
        companyName?: string;
        /**
         * Extra rows appended at the bottom of the print table.
         * Each item: { label: string, value: string | number }
         * Rendered as a summary block below the table.
         */
        summaryRows?: { label: string; value: string | number }[];
        label?: string;
        variant?: 'default' | 'outline' | 'ghost' | 'secondary' | 'destructive' | 'link';
        size?: 'default' | 'sm' | 'lg' | 'icon';
    }>(),
    {
        subjectName: 'N/A',
        maxTheory: 80,
        maxPractical: 20,
        maxTotal: 100,
        passMarks: 40,
        companyName: 'Greenwood Academy',
        summaryRows: () => [],
        label: 'Print',
        variant: 'outline',
        size: 'sm',
    },
);

const { toast } = useToast();

// ─── Helpers ──────────────────────────────────────────────────────
const statusLabel = (m: PrintMarkEntry): string => {
    if (m.is_absent) return 'Absent';
    if (m.total_marks >= props.passMarks) return 'Pass';
    if (m.total_marks > 0) return 'Fail';
    return '—';
};

const statusColor = (m: PrintMarkEntry): string => {
    if (m.is_absent) return '#92400e';
    if (m.total_marks >= props.passMarks) return '#166534';
    if (m.total_marks > 0) return '#991b1b';
    return '#6b7280';
};

// ─── Build HTML string ────────────────────────────────────────────
const buildPrintHTML = (): string => {
    const tableRows = props.students
        .map((s, i) => {
            const m = props.marks[i];
            const status = statusLabel(m);
            const color = statusColor(m);
            const rowBg = i % 2 === 0 ? '#ffffff' : '#f8fafc';
            const failRowBg = !m.is_absent && m.total_marks > 0 && m.total_marks < props.passMarks ? '#fff5f5' : rowBg;

            return `
      <tr style="background:${failRowBg}">
        <td style="text-align:center">${i + 1}</td>
        <td style="text-align:center">${s.roll_no}</td>
        <td>${s.name}</td>
        <td style="text-align:center">${m.is_absent ? 'AB' : m.theory_marks || '—'}</td>
        <td style="text-align:center">${m.is_absent ? 'AB' : m.practical_marks || '—'}</td>
        <td style="text-align:center;font-weight:600;color:${color}">
          ${m.is_absent ? 'AB' : m.total_marks || '—'}
        </td>
        <td style="text-align:center;color:${color};font-weight:600">${status}</td>
        <td>${m.remarks || ''}</td>
      </tr>
    `;
        })
        .join('');

    const summaryBlock =
        props.summaryRows.length > 0
            ? `<div class="summary">
        ${props.summaryRows.map((r) => `<span><strong>${r.label}:</strong> ${r.value}</span>`).join('')}
      </div>`
            : '';

    const now = new Date().toLocaleString();

    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>${props.companyName} — ${props.exam.name} Marks</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 11px;
      color: #1a1a1a;
      padding: 20px 28px;
    }

    .header {
      text-align: center;
      margin-bottom: 16px;
      border-bottom: 2px solid #1e3a5f;
      padding-bottom: 12px;
    }
    .header h1 {
      font-size: 20px;
      font-weight: 700;
      color: #1e3a5f;
      letter-spacing: 0.5px;
    }
    .header h2 {
      font-size: 14px;
      font-weight: 600;
      color: #374151;
      margin-top: 4px;
    }

    .meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4px 32px;
      margin-bottom: 14px;
      font-size: 10.5px;
    }
    .meta span { color: #4b5563; }
    .meta strong { color: #1a1a1a; }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    thead tr {
      background: #1e3a5f;
      color: #ffffff;
    }
    thead th {
      padding: 7px 8px;
      font-weight: 600;
      font-size: 10px;
      text-align: center;
      border: 1px solid #1e3a5f;
    }
    thead th.left { text-align: left; }

    tbody td {
      padding: 5px 8px;
      border: 1px solid #e2e8f0;
      font-size: 10px;
      vertical-align: middle;
    }

    .summary {
      display: flex;
      flex-wrap: wrap;
      gap: 12px 28px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 10px 14px;
      font-size: 10.5px;
      margin-bottom: 12px;
    }

    .footer {
      display: flex;
      justify-content: space-between;
      font-size: 9px;
      color: #9ca3af;
      border-top: 1px solid #e5e7eb;
      padding-top: 8px;
      margin-top: 8px;
    }

    /* ── Signature block ──────────────────────────────────────── */
    .signatures {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0 40px;
      margin-top: 32px;
    }
    .sig-line {
      border-top: 1px solid #374151;
      padding-top: 4px;
      font-size: 9.5px;
      color: #374151;
      text-align: center;
    }

    @media print {
      body { padding: 12px 18px; }
      @page { margin: 12mm 14mm; size: A4 portrait; }
    }
  </style>
</head>
<body>

  <div class="header">
    <h1>${props.companyName}</h1>
    <h2>${props.exam.name}${props.exam.exam_type ? ` &mdash; ${props.exam.exam_type}` : ''}</h2>
  </div>

  <div class="meta">
    <span><strong>Subject:</strong> ${props.subjectName}</span>
    <span><strong>Date:</strong> ${props.schedule?.exam_date ?? 'N/A'}</span>
    <span><strong>Max Marks:</strong> ${props.maxTotal} &nbsp;(Theory: ${props.maxTheory} &nbsp;+&nbsp; Practical: ${props.maxPractical})</span>
    <span><strong>Pass Marks:</strong> ${props.passMarks}</span>
    <span><strong>Total Students:</strong> ${props.students.length}</span>
    <span><strong>Printed:</strong> ${now}</span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:28px">#</th>
        <th style="width:44px">Roll</th>
        <th class="left" style="min-width:140px">Student Name</th>
        <th style="width:68px">Theory<br/><small style="font-weight:400">(Max ${props.maxTheory})</small></th>
        <th style="width:72px">Practical<br/><small style="font-weight:400">(Max ${props.maxPractical})</small></th>
        <th style="width:60px">Total<br/><small style="font-weight:400">(${props.maxTotal})</small></th>
        <th style="width:48px">Status</th>
        <th class="left">Remarks</th>
      </tr>
    </thead>
    <tbody>
      ${tableRows}
    </tbody>
  </table>

  ${summaryBlock}

  <div class="signatures">
    <div class="sig-line">Class Teacher</div>
    <div class="sig-line">Subject Teacher</div>
    <div class="sig-line">Principal</div>
  </div>

  <div class="footer">
    <span>${props.companyName} — Confidential</span>
    <span>Generated: ${now}</span>
  </div>

</body>
</html>`;
};

// ─── Trigger print ────────────────────────────────────────────────
const triggerPrint = () => {
    try {
        const html = buildPrintHTML();
        const win = window.open('', '_blank', 'width=900,height=700');
        if (!win) {
            toast.error('Popup blocked. Please allow popups for this site.');
            return;
        }
        win.document.write(html);
        win.document.close();
        win.focus();
        // Small delay ensures styles load before print dialog
        setTimeout(() => {
            win.print();
            win.close();
        }, 400);
    } catch (e) {
        console.error('[PrintMarks]', e);
        toast.error('Print failed. Please try again.');
    }
};

// Expose for programmatic use
defineExpose({ triggerPrint });
</script>

<template>
    <Button :variant="variant" :size="size" @click="triggerPrint">
        <Printer class="mr-1.5 h-4 w-4" />
        {{ label }}
    </Button>
</template>
