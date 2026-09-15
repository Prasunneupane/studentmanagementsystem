<!-- resources/js/pages/Invoices/Print.vue -->
<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/vue3';
import { Printer, ArrowLeft } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';
import type { Invoice } from '@/composables/invoiceService';

const props = defineProps<{ invoice: Invoice }>();

const money = (value: number) => `Rs. ${Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const formatDate = (value: string) => new Date(value).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });

const statusStyles: Record<string, string> = {
    paid: 'background:#dcfce7;color:#15803d;',
    partial: 'background:#fef9c3;color:#a16207;',
    unpaid: 'background:#fee2e2;color:#b91c1c;',
    overdue: 'background:#fee2e2;color:#b91c1c;',
    cancelled: 'background:#f1f5f9;color:#475569;',
};

const printInvoice = () => window.print();
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <div class="no-print flex items-center justify-between border-b bg-white p-4">
        <Button variant="outline" @click="router.visit(`/invoice/${invoice.id}`)"><ArrowLeft class="mr-2 h-4 w-4" />Back</Button>
        <Button @click="printInvoice"><Printer class="mr-2 h-4 w-4" />Print invoice</Button>
    </div>

    <div class="invoice-sheet">
        <div class="invoice-header">
            <div class="school-info">
                <img src="/images/school-logo.png" alt="School logo" class="school-logo" />
                <div>
                    <h1>Greenwood International School</h1>
                    <p>Kathmandu, Bagmati Province, Nepal</p>
                    <p>+977-1-4XXXXXX &nbsp;|&nbsp; info@greenwoodschool.edu.np</p>
                </div>
            </div>
            <div class="invoice-meta">
                <h2>INVOICE</h2>
                <p><strong>{{ invoice.invoice_number }}</strong></p>
                <p :style="statusStyles[invoice.status]" class="status-pill">{{ invoice.status.toUpperCase() }}</p>
            </div>
        </div>

        <div class="invoice-parties">
            <div>
                <p class="label">Billed to</p>
                <p class="value">{{ invoice.student?.first_name }} {{ invoice.student?.last_name }}</p>
                <p class="muted" v-if="invoice.school_class">Class: {{ invoice.school_class?.name }} <span v-if="invoice.section"> - {{ invoice.section?.name }}</span></p>
            </div>
            <div class="dates">
                <p><span class="label">Issue date</span><span class="value">{{ formatDate(invoice.issue_date) }}</span></p>
                <p><span class="label">Due date</span><span class="value">{{ formatDate(invoice.due_date) }}</span></p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fee type</th>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit price</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, index) in invoice.items" :key="item.id ?? index">
                    <td>{{ index + 1 }}</td>
                    <td>{{ item.fee_type }}</td>
                    <td class="muted">{{ item.description || '-' }}</td>
                    <td class="text-right">{{ item.quantity }}</td>
                    <td class="text-right">{{ money(item.unit_price) }}</td>
                    <td class="text-right">{{ money(item.amount ?? item.quantity * item.unit_price) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="invoice-totals">
            <div class="totals-box">
                <div class="row"><span>Subtotal</span><span>{{ money(invoice.subtotal) }}</span></div>
                <div class="row" v-if="invoice.discount_amount"><span>Discount</span><span>- {{ money(invoice.discount_amount) }}</span></div>
                <div class="row" v-if="invoice.tax_amount"><span>Tax ({{ invoice.tax_percentage }}%)</span><span>{{ money(invoice.tax_amount) }}</span></div>
                <div class="row total"><span>Total</span><span>{{ money(invoice.total_amount) }}</span></div>
                <div class="row"><span>Paid</span><span>{{ money(invoice.paid_amount) }}</span></div>
                <div class="row balance"><span>Balance due</span><span>{{ money(invoice.total_amount - invoice.paid_amount) }}</span></div>
            </div>
        </div>

        <div v-if="invoice.notes" class="invoice-notes">
            <p class="label">Notes</p>
            <p>{{ invoice.notes }}</p>
        </div>

        <div class="invoice-footer">
            <div class="signature">
                <div class="line"></div>
                <p>Authorized signature</p>
            </div>
            <p class="thank-you">Thank you for your prompt payment.</p>
        </div>
    </div>
</template>

<style scoped>
.invoice-sheet {
    max-width: 800px;
    margin: 24px auto;
    background: #fff;
    padding: 40px;
    font-family: 'Inter', system-ui, sans-serif;
    color: #1e293b;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
}
.invoice-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 20px; }
.school-info { display: flex; gap: 12px; align-items: center; }
.school-logo { height: 56px; width: 56px; object-fit: contain; }
.school-info h1 { font-size: 18px; font-weight: 700; margin: 0; }
.school-info p { font-size: 12px; color: #64748b; margin: 2px 0 0; }
.invoice-meta { text-align: right; }
.invoice-meta h2 { font-size: 26px; letter-spacing: 2px; margin: 0 0 4px; color: #0f172a; }
.status-pill { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px; }
.invoice-parties { display: flex; justify-content: space-between; margin: 24px 0; }
.label { font-size: 11px; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; margin: 0 0 2px; }
.value { font-size: 14px; font-weight: 600; margin: 0; }
.muted { font-size: 12px; color: #64748b; margin: 2px 0 0; }
.dates p { display: flex; justify-content: space-between; gap: 24px; font-size: 13px; margin: 4px 0; }
.invoice-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.invoice-table thead th { background: #0f172a; color: #fff; font-size: 11px; text-transform: uppercase; padding: 10px 12px; text-align: left; }
.invoice-table tbody td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
.text-right { text-align: right; }
.invoice-totals { display: flex; justify-content: flex-end; margin-top: 16px; }
.totals-box { width: 280px; }
.totals-box .row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; color: #475569; }
.totals-box .total { border-top: 1px solid #cbd5e1; margin-top: 4px; padding-top: 8px; font-weight: 700; color: #0f172a; font-size: 15px; }
.totals-box .balance { font-weight: 700; color: #b91c1c; }
.invoice-notes { margin-top: 24px; padding-top: 16px; border-top: 1px dashed #e2e8f0; font-size: 13px; }
.invoice-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 60px; }
.signature { text-align: center; }
.signature .line { width: 160px; border-top: 1px solid #94a3b8; margin-bottom: 6px; }
.signature p { font-size: 12px; color: #64748b; margin: 0; }
.thank-you { font-size: 12px; color: #94a3b8; font-style: italic; }

@media print {
    .no-print { display: none !important; }
    .invoice-sheet { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; }
    body { background: #fff; }
}
</style>