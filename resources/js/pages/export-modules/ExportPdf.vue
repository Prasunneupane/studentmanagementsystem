<script setup lang="ts">
import { ref } from 'vue'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

import { Button } from '@/components/ui/button'
import { FileDown, Loader2 } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

interface Header {
  title: string
  key: string
  width?: number
  align?: 'left' | 'center' | 'right'
}

const props = withDefaults(
  defineProps<{
    headers: Header[]
    data: Record<string, any>[]

    companyName?: string
    examName?: string
    subject?: string
    className?: string
    section?: string
    examDate?: string

    filename?: string

    label?: string
    variant?: 'default' | 'outline' | 'ghost' | 'secondary' | 'destructive' | 'link'
    size?: 'default' | 'sm' | 'lg' | 'icon'
  }>(),
  {
    companyName: '',
    examName: '',
    subject: '',
    className: '',
    section: '',
    examDate: '',

    filename: 'report',

    label: 'Export PDF',
    variant: 'outline',
    size: 'sm',
  },
)

const { toast } = useToast()

const loading = ref(false)

const exportPDF = async () => {
  loading.value = true

  try {
    const doc = new jsPDF()

    const pageWidth = doc.internal.pageSize.getWidth()

    let y = 15

    // Company Name
    if (props.companyName) {
      doc.setFontSize(18)
      doc.setFont('helvetica', 'bold')

      doc.text(props.companyName, pageWidth / 2, y, {
        align: 'center',
      })

      y += 10
    }

    // Report Title
    if (props.examName) {
      doc.setFontSize(14)
      doc.setFont('helvetica', 'normal')

      doc.text(props.examName, pageWidth / 2, y, {
        align: 'center',
      })

      y += 10
    }

    // Metadata
    doc.setFontSize(10)

    const metadata = [
      {
        label: 'Subject',
        value: props.subject,
      },
      {
        label: 'Class',
        value: props.className,
      },
      {
        label: 'Section',
        value: props.section,
      },
      {
        label: 'Date',
        value: props.examDate,
      },
    ].filter(item => item.value)

    metadata.forEach(item => {
      doc.text(`${item.label}: ${item.value}`, 14, y)
      y += 6
    })

    if (metadata.length) {
      y += 2

      doc.line(14, y, pageWidth - 14, y)

      y += 5
    }

    // Table Headers
    const head = [
      props.headers.map(header => header.title),
    ]

    // Table Body
    const body = props.data.map(row =>
      props.headers.map(header => {
        const value = row[header.key]

        return value ?? ''
      }),
    )

    // Dynamic Column Styles
    const columnStyles: Record<number, any> = {}

    props.headers.forEach((header, index) => {
      columnStyles[index] = {
        cellWidth: header.width ?? 'auto',
        halign: header.align ?? 'left',
      }
    })

    autoTable(doc, {
      startY: y,

      head,

      body,

      styles: {
        fontSize: 9,
        cellPadding: 2,
        valign: 'middle',
      },

      headStyles: {
        fillColor: [30, 87, 153],
        textColor: 255,
        fontStyle: 'bold',
      },

      alternateRowStyles: {
        fillColor: [245, 249, 253],
      },

      columnStyles,
    })

    // Footer
    const pageCount = doc.getNumberOfPages()

    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i)

      doc.setFontSize(8)

      doc.text(
        `Page ${i} of ${pageCount}`,
        pageWidth / 2,
        doc.internal.pageSize.getHeight() - 10,
        {
          align: 'center',
        },
      )
    }

    doc.save(
      `${props.filename.replace(/\s+/g, '_').toLowerCase()}.pdf`,
    )

    toast.success('PDF downloaded successfully')
  }
  catch (error) {
    console.error(error)

    toast.error('Failed to export PDF')
  }
  finally {
    loading.value = false
  }
}

defineExpose({
  exportPDF,
})
</script>

<template>
  <Button
    :variant="variant"
    :size="size"
    :disabled="loading"
    @click="exportPDF"
  >
    <Loader2
      v-if="loading"
      class="mr-2 h-4 w-4 animate-spin"
    />

    <FileDown
      v-else
      class="mr-2 h-4 w-4"
    />

    {{ label }}
  </Button>
</template>