<script setup lang="ts">
/**
 * ExportExcel.vue — Reusable Excel export button for marks tables
 *
 * Usage:
 *   <ExportExcel
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
 *     :extra-header-rows="[['Academic Year', '2081-82']]"
 *     :extra-footer-rows="[['', '', 'Total Present', 30, '', '', '', '']]"
 *   />
 *
 * Deps (install once):
 *   npm install xlsx
 */

import { ref } from 'vue'
import * as XLSX from 'xlsx'
import { Button } from '@/components/ui/button'
import { FileSpreadsheet, Loader2 } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

// ─── Types ────────────────────────────────────────────────────────
export interface ExcelStudentRow {
  student_id: number
  roll_no: string
  name: string
}

export interface ExcelMarkEntry {
  student_id: number
  theory_marks: string
  practical_marks: string
  total_marks: number
  is_absent: boolean
  remarks: string
}

export interface ExcelExam {
  name: string
  exam_type?: string
}

export interface ExcelSchedule {
  exam_date?: string
}

// ─── Props ────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  students: ExcelStudentRow[]
  marks: ExcelMarkEntry[]
  exam: ExcelExam
  schedule?: ExcelSchedule | null
  subjectName?: string
  maxTheory?: number
  maxPractical?: number
  maxTotal?: number
  passMarks?: number
  companyName?: string
  /**
   * Extra rows inserted BEFORE the column header row.
   * Each inner array = one row of cells.
   * Example: [['Note', 'Preliminary Exam Results'], ['Batch', '2081-82']]
   */
  extraHeaderRows?: (string | number | null)[][]
  /**
   * Extra rows appended AFTER the last student row.
   * Useful for totals, averages, summaries.
   * Example: [['', '', 'Class Average', 72, 18, 90, '', '']]
   */
  extraFooterRows?: (string | number | null)[][]
  /** Optional: button label */
  label?: string
  variant?: 'default' | 'outline' | 'ghost' | 'secondary' | 'destructive' | 'link'
  size?: 'default' | 'sm' | 'lg' | 'icon'
}>(), {
  subjectName: 'N/A',
  maxTheory: 80,
  maxPractical: 20,
  maxTotal: 100,
  passMarks: 40,
  companyName: 'Greenwood Academy',
  extraHeaderRows: () => [],
  extraFooterRows: () => [],
  label: 'Excel',
  variant: 'outline',
  size: 'sm',
})

const { toast } = useToast()
const loading = ref(false)

// ─── Helpers ──────────────────────────────────────────────────────
const statusLabel = (m: ExcelMarkEntry): string => {
  if (m.is_absent) return 'Absent'
  if (m.total_marks >= props.passMarks) return 'Pass'
  if (m.total_marks > 0) return 'Fail'
  return ''
}

// ─── Export ───────────────────────────────────────────────────────
const exportExcel = () => {
  loading.value = true
  try {
    // ── Build rows array ──────────────────────────────────────────
    const rows: (string | number | null)[][] = []

    // Fixed header block
    rows.push([props.companyName])
    rows.push([`Exam: ${props.exam.name}`])
    rows.push([`Subject: ${props.subjectName}`])
    rows.push([`Date: ${props.schedule?.exam_date ?? 'N/A'}`])
    rows.push([`Max Marks: ${props.maxTotal}  (Theory: ${props.maxTheory} + Practical: ${props.maxPractical})`])
    rows.push([`Pass Marks: ${props.passMarks}`])
    rows.push([]) // blank spacer

    // Caller-supplied extra header rows (e.g. academic year, batch, teacher name)
    for (const r of props.extraHeaderRows) {
      rows.push(r)
    }
    if (props.extraHeaderRows.length > 0) rows.push([]) // spacer after extras

    // Column headers
    const columnHeaders = [
      '#',
      'Roll No',
      'Student Name',
      `Theory (Max ${props.maxTheory})`,
      `Practical (Max ${props.maxPractical})`,
      `Total (Max ${props.maxTotal})`,
      'Status',
      'Remarks',
    ]
    rows.push(columnHeaders)

    // Data rows
    props.students.forEach((s, i) => {
      const m = props.marks[i]
      rows.push([
        i + 1,
        s.roll_no,
        s.name,
        m.is_absent ? 'AB' : (m.theory_marks !== '' ? parseFloat(m.theory_marks) : ''),
        m.is_absent ? 'AB' : (m.practical_marks !== '' ? parseFloat(m.practical_marks) : ''),
        m.is_absent ? 'AB' : (m.total_marks || ''),
        statusLabel(m),
        m.remarks || '',
      ])
    })

    // Caller-supplied footer rows (totals, averages, etc.)
    if (props.extraFooterRows.length > 0) {
      rows.push([]) // spacer before footer
      for (const r of props.extraFooterRows) {
        rows.push(r)
      }
    }

    // ── Build worksheet ───────────────────────────────────────────
    const ws = XLSX.utils.aoa_to_sheet(rows)

    // Column widths
    ws['!cols'] = [
      { wch: 5 },   // #
      { wch: 10 },  // Roll
      { wch: 32 },  // Name
      { wch: 18 },  // Theory
      { wch: 18 },  // Practical
      { wch: 16 },  // Total
      { wch: 12 },  // Status
      { wch: 28 },  // Remarks
    ]

    // Merge company name across all 8 columns (row 0 = index 0)
    ws['!merges'] = [
      { s: { r: 0, c: 0 }, e: { r: 0, c: 7 } }, // company name
      { s: { r: 1, c: 0 }, e: { r: 1, c: 7 } }, // exam name
    ]

    // ── Build workbook & save ─────────────────────────────────────
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Marks')

    const filename = `marks_${props.exam.name}_${props.subjectName}.xlsx`
      .replace(/\s+/g, '_')
      .toLowerCase()
    XLSX.writeFile(wb, filename)
    toast.success('Excel downloaded!')
  } catch (e) {
    console.error('[ExportExcel]', e)
    toast.error('Excel export failed. Please try again.')
  } finally {
    loading.value = false
  }
}

// Expose for programmatic use
defineExpose({ exportExcel })
</script>

<template>
  <Button :variant="variant" :size="size" :disabled="loading" @click="exportExcel">
    <Loader2 v-if="loading" class="h-4 w-4 mr-1.5 animate-spin" />
    <FileSpreadsheet v-else class="h-4 w-4 mr-1.5" />
    {{ label }}
  </Button>
</template>
