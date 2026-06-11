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
interface Header {
    title: string
    key: string
    width?: number
}

interface Metadata {
    label: string
    value: string | number
}

const props = withDefaults(
    defineProps<{
        headers: Header[]
        data: Record<string, any>[]

        companyName?: string
        title?: string
        filename?: string

        metadata?: Metadata[]

        footerRows?: (string | number)[][]

        label?: string
        variant?: 'default' | 'outline' | 'ghost' | 'secondary' | 'destructive' | 'link'
        size?: 'default' | 'sm' | 'lg' | 'icon'
    }>(),
    {
        companyName: '',
        title: '',
        filename: 'export',
        metadata: () => [],
        footerRows: () => [],
        label: 'Excel',
        variant: 'outline',
        size: 'sm',
    }
)

const { toast } = useToast()
const loading = ref(false)

// ─── Helpers ──────────────────────────────────────────────────────


// ─── Export ───────────────────────────────────────────────────────
const exportExcel = () => {
    loading.value = true

    try {
        const rows: any[][] = []

        /*
        Company Name
        */
        if (props.companyName) {
            rows.push([props.companyName])
        }

        /*
        Report Title
        */
        if (props.title) {
            rows.push([props.title])
        }

        /*
        Metadata
        */
        props.metadata.forEach(item => {
            rows.push([
                item.label,
                item.value,
            ])
        })

        if (
            props.companyName ||
            props.title ||
            props.metadata.length
        ) {
            rows.push([])
        }

        /*
        Headers
        */
        rows.push(
            props.headers.map(h => h.title)
        )

        /*
        Data
        */
        props.data.forEach(item => {
            rows.push(
                props.headers.map(header => {
                    return item[header.key] ?? ''
                })
            )
        })

        /*
        Footer
        */
        if (props.footerRows.length) {
            rows.push([])

            props.footerRows.forEach(row => {
                rows.push(row)
            })
        }

        const ws = XLSX.utils.aoa_to_sheet(rows)

        /*
        Column Width
        */
        ws['!cols'] = props.headers.map(header => ({
            wch: header.width ?? 20,
        }))

        /*
        Merge Company Name
        */
        const merges: XLSX.Range[] = []

        const totalColumns = props.headers.length - 1

        let currentRow = 0

        if (props.companyName) {
            merges.push({
                s: { r: currentRow, c: 0 },
                e: { r: currentRow, c: totalColumns },
            })

            currentRow++
        }

        if (props.title) {
            merges.push({
                s: { r: currentRow, c: 0 },
                e: { r: currentRow, c: totalColumns },
            })

            currentRow++
        }

        if (merges.length) {
            ws['!merges'] = merges
        }

        /*
        Styling
        */
        const range = XLSX.utils.decode_range(ws['!ref']!)

        for (let row = range.s.r; row <= range.e.r; row++) {
            for (let col = range.s.c; col <= range.e.c; col++) {

                const address = XLSX.utils.encode_cell({
                    r: row,
                    c: col,
                })

                const cell = ws[address]

                if (!cell) continue

                cell.s = {
                    border: {
                        top: { style: 'thin' },
                        bottom: { style: 'thin' },
                        left: { style: 'thin' },
                        right: { style: 'thin' },
                    },
                    alignment: {
                        vertical: 'center',
                        horizontal: 'center',
                    },
                }
            }
        }

        /*
        Company Name Bold
        */
        let rowIndex = 0

        if (props.companyName) {
            const cell = ws[`A${rowIndex + 1}`]

            if (cell) {
                cell.s = {
                    font: {
                        bold: true,
                        sz: 18,
                    },
                    alignment: {
                        horizontal: 'center',
                    },
                }
            }

            rowIndex++
        }

        /*
        Title Bold
        */
        if (props.title) {
            const cell = ws[`A${rowIndex + 1}`]

            if (cell) {
                cell.s = {
                    font: {
                        bold: true,
                        sz: 14,
                    },
                    alignment: {
                        horizontal: 'center',
                    },
                }
            }

            rowIndex++
        }

        /*
        Header Row
        */
        const headerRow =
            rowIndex +
            props.metadata.length +
            2

        props.headers.forEach((_, index) => {

            const cellAddress =
                XLSX.utils.encode_cell({
                    r: headerRow - 1,
                    c: index,
                })

            const cell = ws[cellAddress]

            if (cell) {
                cell.s = {
                    font: {
                        bold: true,
                        color: {
                            rgb: 'FFFFFF',
                        },
                    },
                    fill: {
                        fgColor: {
                            rgb: '1E5799',
                        },
                    },
                    alignment: {
                        horizontal: 'center',
                    },
                    border: {
                        top: { style: 'thin' },
                        bottom: { style: 'thin' },
                        left: { style: 'thin' },
                        right: { style: 'thin' },
                    },
                }
            }
        })

        const wb = XLSX.utils.book_new()

        XLSX.utils.book_append_sheet(
            wb,
            ws,
            props.title || 'Sheet1'
        )

        XLSX.writeFile(
            wb,
            `${props.filename}.xlsx`
        )

        toast.success(
            'Excel downloaded successfully'
        )

    } catch (error) {

        console.error(error)

        toast.error(
            'Failed to export Excel'
        )

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
