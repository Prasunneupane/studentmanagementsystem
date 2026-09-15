// resources/js/services/invoiceService.ts
import { router } from '@inertiajs/vue3';
import axios from 'axios';

export interface InvoiceItem {
    id?: number;
    fee_type: string;
    description?: string;
    quantity: number;
    unit_price: number;
    amount?: number;
}

export interface Invoice {
    id: number;
    invoice_number: string;
    student_id: number;
    class_id?: number | null;
    section_id?: number | null;
    issue_date: string;
    due_date: string;
    status: 'unpaid' | 'partial' | 'paid' | 'overdue' | 'cancelled';
    subtotal: number;
    discount_type?: 'fixed' | 'percentage' | null;
    discount_value: number;
    discount_amount: number;
    tax_percentage: number;
    tax_amount: number;
    total_amount: number;
    paid_amount: number;
    due_amount?: number;
    notes?: string;
    items: InvoiceItem[];
    student?: { id: number; first_name: string; last_name: string; photo_url?: string };
    school_class?: { id: number; name: string };
    section?: { id: number; name: string };
}

export const invoiceService = {
    async createInvoice(payload: Record<string, unknown>) {
        return new Promise<void>((resolve, reject) => {
            router.post('/invoice', payload as any, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    async updateInvoice(id: number, payload: Record<string, unknown>) {
        return new Promise<void>((resolve, reject) => {
            router.put(`/invoice/${id}`, payload as any, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    async recordPayment(id: number, payload: Record<string, unknown>) {
        const { data } = await axios.post(`/invoice/${id}/payments`, payload);
        return data;
    },

    async deleteInvoice(id: number) {
        return new Promise<void>((resolve, reject) => {
            router.delete(`/invoice/${id}`, {
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },
};