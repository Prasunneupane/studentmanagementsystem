<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import {
  Calendar, BookOpen, Users, ChevronDown, ChevronRight,
  Plus, ArrowLeft, CheckCircle2, FileText,
  Printer, FileSpreadsheet, FileDown
} from 'lucide-vue-next'

// ── SheetJS loaded from CDN in the export function ───────────────
declare const XLSX: any
declare const jspdf: any

interface ScheduleRow {
  id: number
  subject_name: string
  exam_date: string
  start_time: string
  end_time: string
  room_no: string
  max_theory_marks: number | null
  max_practical_marks: number | null
  max_total_marks: number
  pass_marks: number
}

interface ClassGroup {
  class_id: number
  class_name: string
  section_id: number
  section_name: string
  schedules: ScheduleRow[]
}

interface Props {
  exam: any
  groupedSchedule: ClassGroup[]
  classes: any[]
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Exams', href: '/exams' },
  { title: props.exam.name, href: `/exams/${props.exam.id}` },
  { title: 'Schedule', href: '#' },
]

// ── Collapse ─────────────────────────────────────────────────────
const collapsed = ref<Record<string, boolean>>({})
const toggleGroup = (key: string) => { collapsed.value[key] = !collapsed.value[key] }
const isOpen = (key: string) => !collapsed.value[key]

// ── Filter ────────────────────────────────────────────────────────
const activeClassFilter = ref<string>('all')
const filteredGroups = computed(() => {
  if (activeClassFilter.value === 'all') return props.groupedSchedule
  return props.groupedSchedule.filter(g => String(g.class_id) === activeClassFilter.value)
})

const uniqueClasses = computed(() => {
  const seen = new Set<number>()
  return props.groupedSchedule.filter(g => {
    if (seen.has(g.class_id)) return false
    seen.add(g.class_id); return true
  })
})

// ── Stats ─────────────────────────────────────────────────────────
const totalSchedules = computed(() =>
  props.groupedSchedule.reduce((acc, g) => acc + g.schedules.length, 0)
)
const totalSections = computed(() => props.groupedSchedule.length)
const totalClasses  = computed(() => uniqueClasses.value.length)

// ── Status ────────────────────────────────────────────────────────
const statusConfig: Record<string, { label: string; class: string }> = {
  ongoing:   { label: 'Ongoing',   class: 'bg-green-100  text-green-700  border-green-200'  },
  upcoming:  { label: 'Upcoming',  class: 'bg-blue-100   text-blue-700   border-blue-200'   },
  completed: { label: 'Completed', class: 'bg-purple-100 text-purple-700 border-purple-200' },
  draft:     { label: 'Draft',     class: 'bg-gray-100   text-gray-600   border-gray-200'   },
}
const statusCfg = computed(() => statusConfig[props.exam.status] ?? statusConfig['draft'])

// ── Formatters ────────────────────────────────────────────────────
const formatDate = (d: string) =>
  d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'

const formatTime = (t: string) => {
  if (!t) return '—'
  const [h, m] = t.split(':')
  const hour = parseInt(h)
  return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`
}

const examTypeLabel = (type: string) =>
  type?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) ?? ''

// ── Subject colors ────────────────────────────────────────────────
const subjectColors = [
  'bg-blue-100 text-blue-700', 'bg-teal-100 text-teal-700',
  'bg-purple-100 text-purple-700', 'bg-amber-100 text-amber-700',
  'bg-rose-100 text-rose-700', 'bg-cyan-100 text-cyan-700',
]
const subjectColorMap: Record<string, string> = {}
let colorIndex = 0
const getSubjectColor = (name: string) => {
  if (!subjectColorMap[name])
    subjectColorMap[name] = subjectColors[colorIndex++ % subjectColors.length]
  return subjectColorMap[name]
}

// ═══════════════════════════════════════════════════════════════
// EXPORT: PRINT
// ═══════════════════════════════════════════════════════════════
const handlePrint = () => {
  const examName   = props.exam.name
  const examType   = examTypeLabel(props.exam.exam_type)
  const year       = props.exam.academicYear?.name ?? ''
  const term       = props.exam.term?.name ?? ''
  const startDate  = formatDate(props.exam.start_date)
  const endDate    = formatDate(props.exam.end_date)
  const weightage  = props.exam.weightage ? `${props.exam.weightage}%` : '—'
  const status     = statusConfig[props.exam.status]?.label ?? props.exam.status
  const published  = props.exam.is_published ? 'Yes' : 'No'
  const printDate  = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })

  let tableRows = ''
  let serial = 1

  props.groupedSchedule.forEach(grp => {
    // Section header spanning all columns
    tableRows += `
      <tr class="section-header">
        <td colspan="9">
          ${grp.class_name} &nbsp;—&nbsp; Section ${grp.section_name}
        </td>
      </tr>`

    grp.schedules.forEach(row => {
      tableRows += `
        <tr>
          <td class="center">${serial++}</td>
          <td><strong>${row.subject_name}</strong></td>
          <td class="center">${formatDate(row.exam_date)}</td>
          <td class="center">${formatTime(row.start_time)} – ${formatTime(row.end_time)}</td>
          <td class="center">${row.room_no || '—'}</td>
          <td class="center">${row.max_theory_marks ?? '—'}</td>
          <td class="center">${row.max_practical_marks || '—'}</td>
          <td class="center">${row.max_total_marks}</td>
          <td class="center">${row.pass_marks}</td>
        </tr>`
    })
  })

  const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>${examName} – Exam Schedule</title>
  <style>
    @page { size: A4 landscape; margin: 15mm 12mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 10pt; color: #1a1a1a; }

    .header { text-align: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 2px solid #1e3a5f; }
    .school-name { font-size: 16pt; font-weight: 900; color: #1e3a5f; letter-spacing: 0.5px; }
    .report-title { font-size: 13pt; font-weight: 700; margin: 4px 0 2px; color: #1e3a5f; }
    .report-sub { font-size: 9pt; color: #555; }

    .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 12px; }
    .info-box { background: #f4f7fb; border: 1px solid #d0daea; border-radius: 4px; padding: 6px 8px; }
    .info-label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.4px; color: #666; font-weight: 700; }
    .info-value { font-size: 9.5pt; font-weight: 700; color: #1e3a5f; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th {
      background: #1e3a5f;
      color: #fff;
      font-size: 8.5pt;
      font-weight: 700;
      padding: 7px 8px;
      text-align: left;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    th.center, td.center { text-align: center; }
    td { font-size: 9pt; padding: 6px 8px; border-bottom: 1px solid #e5eaf1; vertical-align: middle; }
    tr:nth-child(even) td { background: #f8fafd; }
    tr:hover td { background: #eef3fb; }

    .section-header td {
      background: #e8eef8;
      color: #1e3a5f;
      font-size: 9.5pt;
      font-weight: 800;
      padding: 7px 10px;
      border-top: 2px solid #1e3a5f;
      border-bottom: 1px solid #b0c0d8;
      letter-spacing: 0.3px;
    }

    .footer { margin-top: 14px; display: flex; justify-content: space-between; font-size: 8pt; color: #888; border-top: 1px solid #ddd; padding-top: 6px; }
    .totals-row td { font-weight: 700; background: #e8eef8; border-top: 2px solid #1e3a5f; }
  </style>
</head>
<body>
  <div class="header">
    <div class="school-name">School Management System</div>
    <div class="report-title">${examName} — Exam Schedule</div>
    <div class="report-sub">${examType}${year ? ' &nbsp;|&nbsp; ' + year : ''}${term ? ' &nbsp;|&nbsp; ' + term : ''} &nbsp;|&nbsp; Status: ${status}</div>
  </div>

  <div class="info-grid">
    <div class="info-box"><div class="info-label">Start Date</div><div class="info-value">${startDate}</div></div>
    <div class="info-box"><div class="info-label">End Date</div><div class="info-value">${endDate}</div></div>
    <div class="info-box"><div class="info-label">Weightage</div><div class="info-value">${weightage}</div></div>
    <div class="info-box"><div class="info-label">Published</div><div class="info-value">${published}</div></div>
    <div class="info-box"><div class="info-label">Total Classes</div><div class="info-value">${totalClasses.value}</div></div>
    <div class="info-box"><div class="info-label">Total Sections</div><div class="info-value">${totalSections.value}</div></div>
    <div class="info-box"><div class="info-label">Total Schedules</div><div class="info-value">${totalSchedules.value}</div></div>
    <div class="info-box"><div class="info-label">Print Date</div><div class="info-value">${printDate}</div></div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:36px">#</th>
        <th>Subject</th>
        <th class="center" style="width:100px">Date</th>
        <th class="center" style="width:130px">Time</th>
        <th class="center" style="width:72px">Room</th>
        <th class="center" style="width:64px">Theory</th>
        <th class="center" style="width:64px">Practical</th>
        <th class="center" style="width:56px">Total</th>
        <th class="center" style="width:52px">Pass</th>
      </tr>
    </thead>
    <tbody>
      ${tableRows}
    </tbody>
  </table>

  <div class="footer">
    <span>Generated: ${printDate}</span>
    <span>${examName} — Exam Schedule</span>
    <span>Total: ${totalSchedules.value} schedule(s) across ${totalSections.value} section(s)</span>
  </div>
</body>
</html>`

  const win = window.open('', '_blank', 'width=1200,height=800')!
  win.document.write(html)
  win.document.close()
  win.focus()
  setTimeout(() => { win.print(); win.close() }, 500)
}

// ═══════════════════════════════════════════════════════════════
// EXPORT: EXCEL (.xlsx) using SheetJS CDN
// ═══════════════════════════════════════════════════════════════
const exportingExcel = ref(false)

const loadSheetJS = (): Promise<void> =>
  new Promise((resolve, reject) => {
    if (typeof XLSX !== 'undefined') { resolve(); return }
    const s = document.createElement('script')
    s.src = 'https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js'
    s.onload = () => resolve()
    s.onerror = () => reject(new Error('Failed to load SheetJS'))
    document.head.appendChild(s)
  })

const handleExcel = async () => {
  exportingExcel.value = true
  try {
    await loadSheetJS()

    const wb = XLSX.utils.book_new()

    // ── Sheet 1: Summary ──────────────────────────────────────
    const summaryData = [
      ['EXAM SCHEDULE REPORT', '', '', ''],
      ['', '', '', ''],
      ['Exam Name',    props.exam.name,                    '', ''],
      ['Exam Type',    examTypeLabel(props.exam.exam_type), '', ''],
      ['Academic Year', props.exam.academicYear?.name ?? '—', '', ''],
      ['Term',         props.exam.term?.name ?? '—',        '', ''],
      ['Start Date',   formatDate(props.exam.start_date),   '', ''],
      ['End Date',     formatDate(props.exam.end_date),     '', ''],
      ['Weightage',    props.exam.weightage ? `${props.exam.weightage}%` : '—', '', ''],
      ['Status',       statusConfig[props.exam.status]?.label ?? props.exam.status, '', ''],
      ['Published',    props.exam.is_published ? 'Yes' : 'No', '', ''],
      ['', '', '', ''],
      ['Total Classes',   totalClasses.value,   '', ''],
      ['Total Sections',  totalSections.value,  '', ''],
      ['Total Schedules', totalSchedules.value, '', ''],
      ['', '', '', ''],
      ['Generated On', new Date().toLocaleString(), '', ''],
    ]
    const wsSummary = XLSX.utils.aoa_to_sheet(summaryData)
    wsSummary['!cols'] = [{ wch: 20 }, { wch: 40 }, { wch: 16 }, { wch: 16 }]

    // Bold + large title
    wsSummary['A1'] = { v: 'EXAM SCHEDULE REPORT', t: 's', s: { font: { bold: true, sz: 16, color: { rgb: '1e3a5f' } } } }

    // Bold all label cells
    const labelRows = [2,3,4,5,6,7,8,9,10,12,13,14,16]
    labelRows.forEach(r => {
      const addr = `A${r}`
      if (wsSummary[addr]) wsSummary[addr].s = { font: { bold: true } }
    })

    XLSX.utils.book_append_sheet(wb, wsSummary, 'Summary')

    // ── Sheet 2: Full Schedule ────────────────────────────────
    const headers = [
      '#', 'Class', 'Section', 'Subject', 'Exam Date', 'Start Time',
      'End Time', 'Room No', 'Theory Marks', 'Practical Marks', 'Total Marks', 'Pass Marks'
    ]

    const rows: any[][] = []
    let serial = 1

    props.groupedSchedule.forEach(grp => {
      grp.schedules.forEach(row => {
        rows.push([
          serial++,
          grp.class_name,
          `Section ${grp.section_name}`,
          row.subject_name,
          formatDate(row.exam_date),
          formatTime(row.start_time),
          formatTime(row.end_time),
          row.room_no || '—',
          row.max_theory_marks ?? '—',
          row.max_practical_marks || '—',
          row.max_total_marks,
          row.pass_marks,
        ])
      })
    })

    const scheduleData = [headers, ...rows]
    const wsSchedule   = XLSX.utils.aoa_to_sheet(scheduleData)

    // ── Bold + style header row ───────────────────────────────
    const headerCols = ['A','B','C','D','E','F','G','H','I','J','K','L']
    headerCols.forEach(col => {
      const addr = `${col}1`
      if (!wsSchedule[addr]) return
      wsSchedule[addr].s = {
        font:      { bold: true, sz: 11, color: { rgb: 'FFFFFF' } },
        fill:      { fgColor: { rgb: '1E3A5F' } },
        alignment: { horizontal: 'center', vertical: 'center', wrapText: true },
        border: {
          bottom: { style: 'medium', color: { rgb: '0e2444' } },
        }
      }
    })

    // ── Alternate row shading + center-align number cols ──────
    rows.forEach((_, idx) => {
      const excelRow = idx + 2  // row 1 = header
      const isBanded = idx % 2 === 1
      headerCols.forEach(col => {
        const addr = `${col}${excelRow}`
        if (!wsSchedule[addr]) return
        const isNum = ['I','J','K','L','A'].includes(col)
        wsSchedule[addr].s = {
          fill:      isBanded ? { fgColor: { rgb: 'F0F4FA' } } : { fgColor: { rgb: 'FFFFFF' } },
          alignment: { horizontal: isNum ? 'center' : 'left', vertical: 'center' },
          border: {
            bottom: { style: 'thin', color: { rgb: 'D5DFF0' } },
          }
        }
      })
    })

    // Column widths
    wsSchedule['!cols'] = [
      { wch: 5 },  // #
      { wch: 14 }, // class
      { wch: 12 }, // section
      { wch: 22 }, // subject
      { wch: 13 }, // date
      { wch: 11 }, // start
      { wch: 11 }, // end
      { wch: 10 }, // room
      { wch: 13 }, // theory
      { wch: 14 }, // practical
      { wch: 12 }, // total
      { wch: 11 }, // pass
    ]

    // Freeze header row
    wsSchedule['!freeze'] = { xSplit: 0, ySplit: 1, topLeftCell: 'A2', activePane: 'bottomLeft', state: 'frozen' }

    XLSX.utils.book_append_sheet(wb, wsSchedule, 'Schedule')

    // ── Sheet 3: Per-Class Breakdown ─────────────────────────
    props.groupedSchedule.forEach(grp => {
      const sheetName = `${grp.class_name}-${grp.section_name}`.substring(0, 31)
      const classHeaders = ['#', 'Subject', 'Exam Date', 'Start Time', 'End Time', 'Room No', 'Theory', 'Practical', 'Total', 'Pass']
      const classRows = grp.schedules.map((row, i) => [
        i + 1,
        row.subject_name,
        formatDate(row.exam_date),
        formatTime(row.start_time),
        formatTime(row.end_time),
        row.room_no || '—',
        row.max_theory_marks ?? '—',
        row.max_practical_marks || '—',
        row.max_total_marks,
        row.pass_marks,
      ])

      const classData  = [classHeaders, ...classRows]
      const wsClass    = XLSX.utils.aoa_to_sheet(classData)

      // Bold header
      const classCols = ['A','B','C','D','E','F','G','H','I','J']
      classCols.forEach(col => {
        const addr = `${col}1`
        if (!wsClass[addr]) return
        wsClass[addr].s = {
          font:      { bold: true, sz: 11, color: { rgb: 'FFFFFF' } },
          fill:      { fgColor: { rgb: '1E3A5F' } },
          alignment: { horizontal: 'center', vertical: 'center' },
        }
      })

      wsClass['!cols'] = [
        { wch: 4 }, { wch: 22 }, { wch: 13 }, { wch: 11 },
        { wch: 11 }, { wch: 10 }, { wch: 8 }, { wch: 10 }, { wch: 8 }, { wch: 7 },
      ]

      XLSX.utils.book_append_sheet(wb, wsClass, sheetName)
    })

    // ── Write file ────────────────────────────────────────────
    const fileName = `${props.exam.name.replace(/\s+/g, '_')}_Schedule.xlsx`
    XLSX.writeFile(wb, fileName, { bookType: 'xlsx', cellStyles: true })
  } catch (err) {
    console.error('Excel export failed:', err)
    alert('Excel export failed. Please try again.')
  } finally {
    exportingExcel.value = false
  }
}

// ═══════════════════════════════════════════════════════════════
// EXPORT: PDF using jsPDF + autoTable
// ═══════════════════════════════════════════════════════════════
const exportingPdf = ref(false)

const loadJsPDF = (): Promise<void> =>
  new Promise((resolve, reject) => {
    if (typeof (window as any).jspdf !== 'undefined') { resolve(); return }
    // Load jsPDF
    const s1 = document.createElement('script')
    s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'
    s1.onload = () => {
      // Then load autoTable plugin
      const s2 = document.createElement('script')
      s2.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js'
      s2.onload = () => resolve()
      s2.onerror = () => reject(new Error('Failed to load autoTable'))
      document.head.appendChild(s2)
    }
    s1.onerror = () => reject(new Error('Failed to load jsPDF'))
    document.head.appendChild(s1)
  })

const handlePdf = async () => {
  exportingPdf.value = true
  try {
    await loadJsPDF()

    const { jsPDF } = (window as any).jspdf
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })

    const navy   = [30,  58,  95]   // #1e3a5f
    const silver = [240, 244, 250]  // header alt bg
    const white  = [255, 255, 255]
    const pageW  = doc.internal.pageSize.getWidth()
    const printDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })

    // ── Header ────────────────────────────────────────────────
    doc.setFillColor(...navy as [number,number,number])
    doc.rect(0, 0, pageW, 22, 'F')

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(14)
    doc.setTextColor(255, 255, 255)
    doc.text(props.exam.name, pageW / 2, 9, { align: 'center' })

    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    const sub = [
      examTypeLabel(props.exam.exam_type),
      props.exam.academicYear?.name,
      props.exam.term?.name,
    ].filter(Boolean).join('  |  ')
    doc.text(sub, pageW / 2, 16, { align: 'center' })

    // ── Info boxes ────────────────────────────────────────────
    const infoItems = [
      { label: 'Start Date',  value: formatDate(props.exam.start_date) },
      { label: 'End Date',    value: formatDate(props.exam.end_date) },
      { label: 'Weightage',   value: props.exam.weightage ? `${props.exam.weightage}%` : '—' },
      { label: 'Status',      value: statusConfig[props.exam.status]?.label ?? props.exam.status },
      { label: 'Published',   value: props.exam.is_published ? 'Yes' : 'No' },
      { label: 'Classes',     value: String(totalClasses.value) },
      { label: 'Sections',    value: String(totalSections.value) },
      { label: 'Schedules',   value: String(totalSchedules.value) },
    ]

    const boxW   = (pageW - 20) / 4
    const boxH   = 11
    const startY = 26

    infoItems.forEach((item, i) => {
      const col = i % 4
      const row = Math.floor(i / 4)
      const x   = 10 + col * boxW
      const y   = startY + row * (boxH + 2)

      doc.setFillColor(...silver as [number,number,number])
      doc.setDrawColor(200, 210, 225)
      doc.roundedRect(x, y, boxW - 2, boxH, 1.5, 1.5, 'FD')

      doc.setFont('helvetica', 'bold')
      doc.setFontSize(6.5)
      doc.setTextColor(120, 130, 150)
      doc.text(item.label.toUpperCase(), x + 3, y + 4)

      doc.setFont('helvetica', 'bold')
      doc.setFontSize(9)
      doc.setTextColor(...navy as [number,number,number])
      doc.text(item.value, x + 3, y + 9)
    })

    let tableStartY = startY + 2 * (boxH + 2) + 4

    // ── Schedule Table ────────────────────────────────────────
    const tableHeaders = [
      ['#', 'Class', 'Section', 'Subject', 'Date', 'Time', 'Room', 'Theory', 'Practical', 'Total', 'Pass']
    ]

    const tableBody: any[] = []
    let serial = 1

    props.groupedSchedule.forEach(grp => {
      // Section divider row
      tableBody.push([
        {
          content: `${grp.class_name}  —  Section ${grp.section_name}`,
          colSpan: 11,
          styles: {
            fillColor: [218, 228, 242],
            textColor: navy,
            fontStyle: 'bold',
            fontSize: 9,
            cellPadding: { top: 3, bottom: 3, left: 4, right: 4 },
          }
        }
      ])

      grp.schedules.forEach(row => {
        tableBody.push([
          serial++,
          grp.class_name,
          `Sec ${grp.section_name}`,
          row.subject_name,
          formatDate(row.exam_date),
          `${formatTime(row.start_time)} – ${formatTime(row.end_time)}`,
          row.room_no || '—',
          row.max_theory_marks ?? '—',
          row.max_practical_marks || '—',
          row.max_total_marks,
          row.pass_marks,
        ])
      })
    })

    ;(doc as any).autoTable({
      head:      tableHeaders,
      body:      tableBody,
      startY:    tableStartY,
      margin:    { left: 10, right: 10 },
      tableWidth: 'auto',
      styles: {
        fontSize:   8.5,
        cellPadding: { top: 3, bottom: 3, left: 3, right: 3 },
        lineColor:  [210, 220, 235],
        lineWidth:  0.2,
        textColor:  [30, 30, 30],
        font:       'helvetica',
        valign:     'middle',
      },
      headStyles: {
        fillColor:  navy,
        textColor:  white,
        fontStyle:  'bold',
        fontSize:   8.5,
        halign:     'center',
        cellPadding: { top: 4, bottom: 4, left: 3, right: 3 },
      },
      alternateRowStyles: { fillColor: [248, 251, 255] },
      columnStyles: {
        0:  { halign: 'center', cellWidth: 8  },
        1:  { cellWidth: 22 },
        2:  { halign: 'center', cellWidth: 18 },
        3:  { cellWidth: 32 },
        4:  { halign: 'center', cellWidth: 22 },
        5:  { halign: 'center', cellWidth: 30 },
        6:  { halign: 'center', cellWidth: 16 },
        7:  { halign: 'center', cellWidth: 16 },
        8:  { halign: 'center', cellWidth: 18 },
        9:  { halign: 'center', cellWidth: 16 },
        10: { halign: 'center', cellWidth: 14 },
      },
      // Page header/footer on every page
      didDrawPage: (data: any) => {
        // Running header on page 2+
        if (data.pageNumber > 1) {
          doc.setFillColor(...navy as [number,number,number])
          doc.rect(0, 0, pageW, 10, 'F')
          doc.setFont('helvetica', 'bold')
          doc.setFontSize(8)
          doc.setTextColor(255, 255, 255)
          doc.text(props.exam.name + ' — Exam Schedule', pageW / 2, 6.5, { align: 'center' })
        }
        // Footer on every page
        const pageH = doc.internal.pageSize.getHeight()
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7)
        doc.setTextColor(160, 160, 160)
        doc.text(`Generated: ${printDate}`, 10, pageH - 5)
        doc.text(
          `Page ${data.pageNumber} of ${doc.internal.getNumberOfPages()}`,
          pageW - 10, pageH - 5, { align: 'right' }
        )
        doc.text(
          `Total: ${totalSchedules.value} schedule(s) — ${totalSections.value} section(s)`,
          pageW / 2, pageH - 5, { align: 'center' }
        )
      },
    })

    const fileName = `${props.exam.name.replace(/\s+/g, '_')}_Schedule.pdf`
    doc.save(fileName)
  } catch (err) {
    console.error('PDF export failed:', err)
    alert('PDF export failed. Please try again.')
  } finally {
    exportingPdf.value = false
  }
}
</script>

<template>
  <Head :title="`Schedule – ${exam.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 p-4 max-w-6xl mx-auto w-full">

      <!-- ── Header Card ────────────────────────────────────────── -->
      <Card class="rounded-2xl shadow-sm border">
        <CardContent class="p-5">
          <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border text-xs font-semibold"
                  :class="statusCfg.class">
                  <span class="w-1.5 h-1.5 rounded-full bg-current inline-block"></span>
                  {{ statusCfg.label }}
                </span>
                <span v-if="exam.is_published"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-xs font-semibold bg-emerald-50 text-emerald-700 border-emerald-200">
                  <CheckCircle2 class="w-3 h-3" /> Published
                </span>
              </div>
              <h1 class="text-xl font-bold tracking-tight">{{ exam.name }}</h1>
              <p class="text-sm text-muted-foreground mt-0.5">
                {{ examTypeLabel(exam.exam_type) }}
                <span v-if="exam.academicYear"> · {{ exam.academicYear.name }}</span>
                <span v-if="exam.term"> · {{ exam.term.name }}</span>
              </p>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-2 flex-wrap items-center">
              <Button variant="outline" size="sm" @click="router.visit('/exams')">
                <ArrowLeft class="w-4 h-4 mr-1" /> Back
              </Button>

              <!-- Print -->
              <Button variant="outline" size="sm" @click="handlePrint"
                class="border-slate-300 hover:bg-slate-50">
                <Printer class="w-4 h-4 mr-1.5 text-slate-600" />
                Print
              </Button>

              <!-- Excel -->
              <Button variant="outline" size="sm" @click="handleExcel" :disabled="exportingExcel"
                class="border-emerald-300 hover:bg-emerald-50 text-emerald-700">
                <FileSpreadsheet class="w-4 h-4 mr-1.5" />
                {{ exportingExcel ? 'Generating...' : 'Excel' }}
              </Button>

              <!-- PDF -->
              <Button variant="outline" size="sm" @click="handlePdf" :disabled="exportingPdf"
                class="border-red-300 hover:bg-red-50 text-red-700">
                <FileDown class="w-4 h-4 mr-1.5" />
                {{ exportingPdf ? 'Generating...' : 'PDF' }}
              </Button>

              <Button size="sm" @click="router.visit(`/exams/${exam.id}/schedule/create`)">
                <Plus class="w-4 h-4 mr-1" /> Add Schedule
              </Button>
            </div>
          </div>

          <!-- Meta row -->
          <div class="flex gap-6 mt-4 flex-wrap">
            <div class="flex items-center gap-1.5 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              <span class="text-muted-foreground">Start:</span>
              <span class="font-medium">{{ formatDate(exam.start_date) }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-sm">
              <Calendar class="w-4 h-4 text-muted-foreground" />
              <span class="text-muted-foreground">End:</span>
              <span class="font-medium">{{ formatDate(exam.end_date) }}</span>
            </div>
            <div v-if="exam.weightage" class="flex items-center gap-1.5 text-sm">
              <FileText class="w-4 h-4 text-muted-foreground" />
              <span class="text-muted-foreground">Weightage:</span>
              <span class="font-medium">{{ exam.weightage }}%</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- ── Stats ───────────────────────────────────────────────── -->
      <div class="grid grid-cols-3 gap-3">
        <div class="bg-card border rounded-xl p-4">
          <div class="text-2xl font-bold tracking-tight">{{ totalClasses }}</div>
          <div class="text-xs text-muted-foreground mt-0.5 font-medium uppercase tracking-wide">Classes</div>
        </div>
        <div class="bg-card border rounded-xl p-4">
          <div class="text-2xl font-bold tracking-tight">{{ totalSections }}</div>
          <div class="text-xs text-muted-foreground mt-0.5 font-medium uppercase tracking-wide">Sections</div>
        </div>
        <div class="bg-card border rounded-xl p-4">
          <div class="text-2xl font-bold tracking-tight">{{ totalSchedules }}</div>
          <div class="text-xs text-muted-foreground mt-0.5 font-medium uppercase tracking-wide">Schedules</div>
        </div>
      </div>

      <!-- ── Filter Pills ─────────────────────────────────────────── -->
      <div class="flex gap-2 flex-wrap">
        <button
          class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-all"
          :class="activeClassFilter === 'all'
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-card text-muted-foreground border-border hover:border-primary/50'"
          @click="activeClassFilter = 'all'">
          All Classes
        </button>
        <button
          v-for="grp in uniqueClasses" :key="grp.class_id"
          class="px-3.5 py-1.5 rounded-full text-xs font-semibold border transition-all"
          :class="activeClassFilter === String(grp.class_id)
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-card text-muted-foreground border-border hover:border-primary/50'"
          @click="activeClassFilter = String(grp.class_id)">
          {{ grp.class_name }}
        </button>
      </div>

      <!-- ── Empty State ──────────────────────────────────────────── -->
      <div v-if="filteredGroups.length === 0"
        class="flex flex-col items-center justify-center py-16 text-center bg-card border rounded-2xl">
        <BookOpen class="w-10 h-10 text-muted-foreground/40 mb-3" />
        <p class="text-base font-medium text-muted-foreground">No schedules found</p>
        <p class="text-sm text-muted-foreground/70 mt-1">Add a schedule to get started</p>
        <Button class="mt-4" size="sm" @click="router.visit(`/exams/${exam.id}/schedule/create`)">
          <Plus class="w-4 h-4 mr-1.5" /> Add Schedule
        </Button>
      </div>

      <!-- ── Schedule Groups ──────────────────────────────────────── -->
      <div v-for="grp in filteredGroups" :key="`${grp.class_id}_${grp.section_id}`"
        class="rounded-2xl border overflow-hidden shadow-sm bg-card">

        <!-- Group header -->
        <div
          class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-muted/30 transition-colors border-b bg-muted/20"
          @click="toggleGroup(`${grp.class_id}_${grp.section_id}`)">
          <div class="flex items-center gap-2.5">
            <div class="w-2 h-2 rounded-full bg-primary"></div>
            <span class="font-semibold text-sm">{{ grp.class_name }}</span>
            <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-primary/10 text-primary">
              Section {{ grp.section_name }}
            </span>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs text-muted-foreground">
              {{ grp.schedules.length }} subject{{ grp.schedules.length !== 1 ? 's' : '' }}
            </span>
            <component
              :is="isOpen(`${grp.class_id}_${grp.section_id}`) ? ChevronDown : ChevronRight"
              class="w-4 h-4 text-muted-foreground transition-transform" />
          </div>
        </div>

        <!-- Schedule table -->
        <div v-show="isOpen(`${grp.class_id}_${grp.section_id}`)">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b bg-muted/10">
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Subject</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Date</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Time</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Room</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Theory</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Practical</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Total</th>
                  <th class="px-4 py-2.5 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wider">Pass</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in grp.schedules" :key="row.id"
                  class="border-b last:border-b-0 hover:bg-muted/20 transition-colors">
                  <td class="px-4 py-3 font-medium">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
                      :class="getSubjectColor(row.subject_name)">
                      {{ row.subject_name }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm font-medium">{{ formatDate(row.exam_date) }}</td>
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-muted px-2 py-1 rounded-md">
                      {{ formatTime(row.start_time) }} – {{ formatTime(row.end_time) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-muted-foreground">{{ row.room_no || '—' }}</td>
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-md font-semibold">
                      {{ row.max_theory_marks ?? '—' }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs px-2 py-1 rounded-md font-semibold"
                      :class="row.max_practical_marks ? 'bg-emerald-50 text-emerald-700' : 'text-muted-foreground'">
                      {{ row.max_practical_marks || '—' }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded-md font-semibold">
                      {{ row.max_total_marks }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="font-mono text-xs bg-amber-50 text-amber-700 px-2 py-1 rounded-md font-semibold">
                      {{ row.pass_marks }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>