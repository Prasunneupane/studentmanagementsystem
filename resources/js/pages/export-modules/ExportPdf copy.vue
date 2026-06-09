<script setup lang="ts">
/**
 * ExportPdf.vue — Reusable PDF export button for marks tables
 *
 * Usage:
 *   <ExportPdf
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
 *
 * Deps (install once):
 *   npm install jspdf jspdf-autotable
 */

import { ref } from 'vue'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { Button } from '@/components/ui/button'
import { FileDown, Loader2 } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

// ─── Types ────────────────────────────────────────────────────────
export interface PdfStudentRow {
  student_id: number
  roll_no: string
  name: string
}

export interface PdfMarkEntry {
  student_id: number
  theory_marks: string
  practical_marks: string
  total_marks: number
  is_absent: boolean
  remarks: string
}

export interface PdfExam {
  name: string
  exam_type?: string
}

export interface PdfSchedule {
  exam_date?: string
}

// ─── Props ────────────────────────────────────────────────────────
const props = withDefaults(defineProps<{
  students: PdfStudentRow[]
  marks: PdfMarkEntry[]
  exam: PdfExam
  schedule?: PdfSchedule | null
  subjectName?: string
  maxTheory?: number
  maxPractical?: number
  maxTotal?: number
  passMarks?: number
  companyName?: string
  /** Optional: extra rows appended at the bottom of the table (e.g. totals/summary row) */
  extraRows?: (string | number)[][]
  /** Optional: label for the button */
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
  extraRows: () => [],
  label: 'PDF',
  variant: 'outline',
  size: 'sm',
})

const { toast } = useToast()
const loading = ref(false)

// ─── Helpers ──────────────────────────────────────────────────────
const statusLabel = (m: PdfMarkEntry): string => {
  if (m.is_absent) return 'Absent'
  if (m.total_marks >= props.passMarks) return 'Pass'
  if (m.total_marks > 0) return 'Fail'
  return '—'
}

// ─── Export ───────────────────────────────────────────────────────
const exportPDF = async () => {
  loading.value = true
  try {
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
    const pageW = doc.internal.pageSize.getWidth()

    // ── Company / header block ────────────────────────────────────
    doc.setFontSize(18)
    doc.setFont('helvetica', 'bold')
    doc.text(props.companyName, pageW / 2, 16, { align: 'center' })

    doc.setFontSize(13)
    doc.setFont('helvetica', 'normal')
    doc.text(`Exam: ${props.exam.name}`, pageW / 2, 24, { align: 'center' })

    // ── Meta grid (2 columns) ─────────────────────────────────────
    doc.setFontSize(9)
    doc.setTextColor(80, 80, 80)
    const col1 = 14
    const col2 = pageW / 2 + 6
    let y = 33

    doc.text(`Subject: ${props.subjectName}`, col1, y)
    doc.text(`Date: ${props.schedule?.exam_date ?? 'N/A'}`, col2, y)
    y += 6
    doc.text(`Max Marks: ${props.maxTotal}  (Theory: ${props.maxTheory} + Practical: ${props.maxPractical})`, col1, y)
    doc.text(`Pass Marks: ${props.passMarks}`, col2, y)
    y += 4

    // ── Divider ───────────────────────────────────────────────────
    doc.setDrawColor(180, 180, 180)
    doc.line(14, y, pageW - 14, y)
    y += 4

    // ── Table data ────────────────────────────────────────────────
    const bodyRows: (string | number)[][] = props.students.map((s, i) => {
      const m = props.marks[i]
      return [
        i + 1,
        s.roll_no,
        s.name,
        m.is_absent ? 'AB' : (m.theory_marks || '—'),
        m.is_absent ? 'AB' : (m.practical_marks || '—'),
        m.is_absent ? 'AB' : (m.total_marks || '—'),
        statusLabel(m),
        m.remarks || '',
      ]
    })

    // Append caller-supplied extra rows (summary, totals, etc.)
    const allRows = [...bodyRows, ...props.extraRows]

    autoTable(doc, {
      startY: y,
      head: [[
        '#',
        'Roll',
        'Name',
        `Theory\n(${props.maxTheory})`,
        `Practical\n(${props.maxPractical})`,
        `Total\n(${props.maxTotal})`,
        'Status',
        'Remarks',
      ]],
      body: allRows,
      styles: {
        fontSize: 8,
        cellPadding: 2.5,
        valign: 'middle',
      },
      headStyles: {
        fillColor: [30, 87, 153],
        textColor: 255,
        fontStyle: 'bold',
        halign: 'center',
      },
      alternateRowStyles: { fillColor: [245, 249, 253] },
      columnStyles: {
        0: { cellWidth: 8,  halign: 'center' },
        1: { cellWidth: 14, halign: 'center' },
        2: { cellWidth: 46 },
        3: { cellWidth: 20, halign: 'center' },
        4: { cellWidth: 20, halign: 'center' },
        5: { cellWidth: 18, halign: 'center' },
        6: { cellWidth: 16, halign: 'center' },
        7: { cellWidth: 'auto' },
      },
      // Color-code Pass / Fail / Absent in status column
      didParseCell(data) {
        if (data.column.index === 6 && data.section === 'body') {
          const val = String(data.cell.raw)
          if (val === 'Pass')   { data.cell.styles.textColor = [22, 101, 52] }
          if (val === 'Fail')   { data.cell.styles.textColor = [153, 27, 27] }
          if (val === 'Absent') { data.cell.styles.textColor = [120, 53, 15] }
        }
      },
    })

    // ── Page numbers ──────────────────────────────────────────────
    const pageCount = doc.getNumberOfPages()
    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i)
      doc.setFontSize(8)
      doc.setTextColor(150, 150, 150)
      doc.text(
        `Page ${i} of ${pageCount}`,
        pageW / 2,
        doc.internal.pageSize.getHeight() - 8,
        { align: 'center' },
      )
      doc.text(
        props.companyName,
        14,
        doc.internal.pageSize.getHeight() - 8,
      )
    }

    // ── Save ──────────────────────────────────────────────────────
    const filename = `marks_${props.exam.name}_${props.subjectName}.pdf`
      .replace(/\s+/g, '_')
      .toLowerCase()
    doc.save(filename)
    toast.success('PDF downloaded!')
  } catch (e) {
    console.error('[ExportPdf]', e)
    toast.error('PDF export failed. Please try again.')
  } finally {
    loading.value = false
  }
}

// Expose so parent can trigger programmatically: ref.exportPDF()
defineExpose({ exportPDF })
</script>

<template>
  <Button :variant="variant" :size="size" :disabled="loading" @click="exportPDF">
    <Loader2 v-if="loading" class="h-4 w-4 mr-1.5 animate-spin" />
    <FileDown v-else class="h-4 w-4 mr-1.5" />
    {{ label }}
  </Button>
</template>
