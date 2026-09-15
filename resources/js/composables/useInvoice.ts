// resources/js/composables/useInvoices.ts
import { invoiceService } from '@/composables/invoiceService';

export function useInvoices() {
    return {
        createInvoice: invoiceService.createInvoice,
        updateInvoice: invoiceService.updateInvoice,
        recordPayment: invoiceService.recordPayment,
        deleteInvoice: invoiceService.deleteInvoice,
    };
}