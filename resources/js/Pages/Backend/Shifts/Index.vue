<script setup>
import Master from "@/Layouts/Master.vue";
import { ref, computed, onMounted, onUpdated } from "vue";
import { Eye, Calendar, AlertTriangle, XCircle, Lock, Plus, Unlock, Percent } from "lucide-vue-next";
import { toast } from "vue3-toastify";
import axios from "axios";
import { useFormatters } from '@/composables/useFormatters'
import { nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import CloseShiftModal from "@/Components/CloseShiftModal.vue";

const { formatMoney, formatCurrencySymbol, formatNumber, dateFmt } = useFormatters()

const shifts = ref([]);
const showShiftDetailsModal = ref(false);
const selectedShiftDetails = ref([]);
const selectedShiftId = ref(null);
const showXReportModal = ref(false);  
const showZReportModal = ref(false);  
const xReportData = ref(null);       
const zReportData = ref(null);


const showCloseShiftModal = ref(false);
const selectedShiftForClose = ref(null);
/* ---------------- Fetch shifts ---------------- */
const fetchShifts = async () => {
    try {
        const res = await axios.get("api/shift/all");
        shifts.value = res.data.data;
    } catch (err) {
        console.error("Failed to fetch shifts:", err);
        toast.error("Failed to load shifts");
    }
};


const handleToggleShift = (shift) => {
    if (shift.status === 'open') {
        // Show close shift modal
        selectedShiftForClose.value = shift;
        showCloseShiftModal.value = true;
    } else {
        // Reopen shift directly
        reopenShift(shift);
    }
};

const reopenShift = async (shift) => {
    try {
        const response = await axios.patch(`/api/shift/${shift.id}/reopen`, {
            status: 'open'
        });

        if (response.data.success) {
            toast.success(response.data.message || 'Shift reopened successfully.');
            shift.status = 'open';
            await fetchShifts(); // Refresh the list

            if (response.data.redirect) {
                window.location.href = response.data.redirect;
            }
        } else {
            toast.error("Unexpected response from server.");
        }
    } catch (error) {
        console.error("Failed to reopen shift:", error);
        toast.error(error.response?.data?.message || "Failed to reopen shift.");
    }
};

const viewShift = async (shift) => {
    selectedShiftId.value = shift.id;
    showShiftDetailsModal.value = true;

    try {
        const res = await axios.get(`/api/shift/${shift.id}/details`);
        selectedShiftDetails.value = res.data.data || [];
    } catch (error) {
        console.error("Failed to fetch shift details:", error);
        toast.error("Failed to load shift details");
    }
};

const closeShiftDetailsModal = () => {
    showShiftDetailsModal.value = false;
    selectedShiftDetails.value = [];
};

onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();
    setTimeout(() => {
        isReady.value = true;
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);
    fetchShifts();
});

/* ---------------- KPI Cards ---------------- */
const shiftstats = computed(() => {
    const today = new Date().toISOString().split("T")[0]; // 'YYYY-MM-DD'

    return [
        {
            label: "Total Shifts",
            value: shifts.value.length,
            icon: Percent,
            iconBg: "bg-light-primary",
            iconColor: "text-primary",
        },
        {
            label: "Open Shifts",
            value: shifts.value.filter((s) => s.status === "open").length,
            icon: Unlock,
            iconBg: "bg-light-success",
            iconColor: "text-success",
        },
        {
            label: "Closed Shifts",
            value: shifts.value.filter((s) => s.status === "closed").length,
            icon: Lock,
            iconBg: "bg-light-warning",
            iconColor: "text-warning",
        },
        {
            label: "Today’s Shifts",
            value: shifts.value.filter((s) => {
                if (!s.start_time) return false;
                return new Date(s.start_time).toISOString().split("T")[0] === today;
            }).length,
            icon: Calendar,
            iconBg: "bg-light-danger",
            iconColor: "text-danger",
        },
    ];
});


/* ---------------- Search ---------------- */
const q = ref("");
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return shifts.value;
    return shifts.value.filter((s) =>
        String(s.id).includes(t) ||
        (s.started_by && s.started_by.toLowerCase().includes(t)) ||
        (s.ended_by && s.ended_by.toLowerCase().includes(t)) ||
        (s.status && s.status.toLowerCase().includes(t)) ||
        (s.start_time && s.start_time.toLowerCase().includes(t)) ||
        String(s.sales_total).includes(t)
    );

});


/* ---------------- Helpers ---------------- */
const money = (n, currency = "GBP") =>
    new Intl.NumberFormat("en-GB", { style: "currency", currency }).format(n);

const formatTime = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleTimeString("en-GB", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
};
onUpdated(() => window.feather?.replace());
const onShiftClosed = (data) => {
    showCloseShiftModal.value = false;
    selectedShiftForClose.value = null;
    fetchShifts();
};
const onCloseModalCancel = () => {
    showCloseShiftModal.value = false;
    selectedShiftForClose.value = null;
};
const calculateExpectedClosingCash = (shift) => {
    return parseFloat(shift.opening_cash || 0) + parseFloat(shift.sales_total || 0);
};

const onDownload = (type) => {

    if (type === 'x-report') {
        const openShift = shifts.value.find(s => s.status === 'open');

        if (!openShift) {
            toast.warning('No open shift found. X Report can only be generated for open shifts.');
            return;
        }
        generateXReport(openShift);
        return;
    }
    if (type === 'z-report') {
        const closedShift = shifts.value.find(s => s.status === 'closed');

        if (!closedShift) {
            toast.warning('No closed shift found. Z Report can only be generated for closed shifts.');
            return;
        }
        generateZReport(closedShift);
        return;
    }

    if (!shifts.value || shifts.value.length === 0) {
        toast.error("No Shifts data to download");
        return;
    }
    const dataToExport = q.value.trim() ? filtered.value : shifts.value;

    if (dataToExport.length === 0) {
        toast.error("No Shifts found to download");
        return;
    }

    try {
        if (type === "pdf") {
            downloadPDF(dataToExport);
        } else if (type === "excel") {
            downloadExcel(dataToExport);
        } else if (type === "csv") {
            downloadCSV(dataToExport);
        } else {
            toast.error("Invalid download type");
        }
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    }
};

const downloadCSV = (data) => {
    try {
        const headers = [
            "Shift ID",
            "Started By",
            "Ended By",
            "Status",
            "Start Time",
            "End Time",
            "Sales Total",
        ];

        const rows = data.map((s) => {
            const startTime = s.start_time
                ? new Date(s.start_time).toLocaleString("en-GB")
                : "N/A";
            const endTime = s.end_time
                ? new Date(s.end_time).toLocaleString("en-GB")
                : "N/A";

            // Format sales total
            const salesTotal = s.sales_total
                ? formatCurrencySymbol(s.sales_total)
                : "0.00";

            return [
                `"${s.id || ""}"`,
                `"${s.started_by || ""}"`,
                `"${s.ended_by || ""}"`,
                `"${s.status || ""}"`,
                `"${startTime}"`,
                `"${endTime}"`,
                `"${salesTotal}"`,
            ];
        });

        const csvContent = [
            headers.join(","),
            ...rows.map((r) => r.join(",")), 
        ].join("\n");

        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `shifts_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("CSV downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("p", "mm", "a4");

        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text("Shifts Report", 75, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Shifts: ${data.length}`, 14, 34);

        const tableColumns = [
            "Shift ID",
            "Started By",
            "Ended By",
            "Status",
            "Start Time",
            "End Time",
            "Sales Total",
        ];

        const tableRows = data.map((s) => {
            const startTime = s.start_time
                ? new Date(s.start_time).toLocaleString("en-GB")
                : "N/A";

            // Format end time
            const endTime = s.end_time
                ? new Date(s.end_time).toLocaleString("en-GB")
                : "N/A";

            // Format sales total
            const salesTotal = s.sales_total
                ? formatCurrencySymbol(s.sales_total)
                : "0.00";

            return [
                s.id || "",
                s.started_by || "",
                s.ended_by || "",
                s.status || "",
                startTime,
                endTime,
                salesTotal,
            ];
        });

        // 📑 Render Table
        autoTable(doc, {
            head: [tableColumns],
            body: tableRows,
            startY: 40,
            styles: {
                fontSize: 8,
                cellPadding: 2,
                halign: "left",
                lineColor: [0, 0, 0],
                lineWidth: 0.1,
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { left: 14, right: 14 },
            didDrawPage: (td) => {
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${td.pageNumber} of ${pageCount}`,
                    td.settings.margin.left,
                    pageHeight - 10
                );
            },
        });

        // 💾 Save File
        const fileName = `shifts_${new Date()
            .toISOString()
            .split("T")[0]}.pdf`;
        doc.save(fileName);
        toast.success("PDF downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadExcel = (data) => {
    try {
        // Prepare data for Excel
        const excelData = data.map((s) => {
            // Format start time
            const startTime = s.start_time
                ? new Date(s.start_time).toLocaleString("en-GB")
                : "N/A";

            // Format end time
            const endTime = s.end_time
                ? new Date(s.end_time).toLocaleString("en-GB")
                : "N/A";

            // Format sales total
            const salesTotal = s.sales_total
                ? formatCurrencySymbol(s.sales_total)
                : "0.00";

            return {
                "Shift ID": s.id || "",
                "Started By": s.started_by || "",
                "Ended By": s.ended_by || "",
                "Status": s.status || "",
                "Start Time": startTime,
                "End Time": endTime,
                "Sales Total": salesTotal,
            };
        });

        // Create worksheet
        const ws = XLSX.utils.json_to_sheet(excelData);

        // Create workbook
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Shifts");

        // Generate file name
        const fileName = `shifts_${new Date()
            .toISOString()
            .split("T")[0]}.xlsx`;

        // Save file
        XLSX.writeFile(wb, fileName);

        toast.success("Excel downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const generateXReport = async (shift) => {
    if (shift.status !== 'open') {
        toast.warning('X Report can only be generated for open shifts');
        return;
    }

    try {

        const res = await axios.get(`/api/shift/${shift.id}/x-report`);

        if (res.data.success) {
            xReportData.value = res.data.data;
            showXReportModal.value = true;
            toast.success('X Report generated successfully');
        }
    } catch (error) {
        console.error('Failed to generate X Report:', error);
        toast.error(error.response?.data?.message || 'Failed to generate X Report');
    }
};

const closeXReportModal = () => {
    showXReportModal.value = false;
    xReportData.value = null;
};

const downloadXReportPdf = async (shiftId) => {
    try {
        const res = await axios.get(`/api/shift/${shiftId}/x-report/pdf`);

        if (!res.data.success) {
            toast.error('Failed to generate X Report PDF');
            return;
        }

        const data = res.data.data;
        const doc = new jsPDF('p', 'mm', 'a4');

        // Title
        doc.setFontSize(18);
        doc.setFont('helvetica', 'bold');
        doc.text(data.title, 105, 20, { align: 'center' });

        // Generated Date
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text(`Generated: ${data.generatedDate}`, 14, 28);

        // Shift Information Section
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('Shift Information', 14, 38);

        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text(`Shift ID: ${data.shiftId}`, 14, 46);
        doc.text(`Started By: ${data.startedBy}`, 14, 52);
        doc.text(`Start Time: ${data.startTime}`, 14, 58);
        doc.text(`Opening Cash: £${data.openingCash}`, 14, 64);

        // Sales Summary Table
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('Sales Summary', 14, 75);

        const salesData = [
            ['Total Orders', data.salesSummary.total_orders.toString()],
            ['Subtotal', '£' + formatCurrencySymbol(data.salesSummary.subtotal)],
            ['Tax', '£' + formatCurrencySymbol(data.salesSummary.total_tax)],
            ['Discount', '£' + formatCurrencySymbol(data.salesSummary.total_discount)],
            ['Total Sales', '£' + formatCurrencySymbol(data.salesSummary.total_sales)],
        ];

        autoTable(doc, {
            head: [['Description', 'Amount']],
            body: salesData,
            startY: 0,
            theme: 'grid',
            styles: {
                fontSize: 9,
                cellPadding: 3,
                halign: 'left',
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: 'bold',
                halign: 'center',
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245],
            },
            columnStyles: {
                1: { halign: 'right' },
            },
            margin: { left: 14, right: 14 },
        });

        let currentY = doc.lastAutoTable.finalY + 10;

        // Cash Summary Table
        if (currentY > 250) {
            doc.addPage();
            currentY = 14;
        }

        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('Cash Summary', 14, currentY);

        const cashData = [
            ['Opening Cash', '£' + formatCurrencySymbol(data.cashSummary.opening_cash)],
            ['Cash Sales', '£' + formatCurrencySymbol(data.cashSummary.cash_sales)],
            ['Expected Cash', '£' + formatCurrencySymbol(data.cashSummary.expected_cash)],
        ];

        autoTable(doc, {
            head: [['Description', 'Amount']],
            body: cashData,
            startY: currentY + 5,
            theme: 'grid',
            styles: {
                fontSize: 9,
                cellPadding: 3,
                halign: 'left',
            },
            headStyles: {
                fillColor: [243, 156, 18],
                textColor: 255,
                fontStyle: 'bold',
                halign: 'center',
            },
            alternateRowStyles: {
                fillColor: [255, 243, 205],
            },
            columnStyles: {
                1: { halign: 'right' },
            },
            margin: { left: 14, right: 14 },
        });

        // Payment Methods Table
        if (data.paymentMethods.length > 0) {
            currentY = doc.lastAutoTable.finalY + 10;

            if (currentY > 250) {
                doc.addPage();
                currentY = 14;
            }

            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('Payment Methods', 14, currentY);

            const paymentData = data.paymentMethods.map(pm => [
                pm.method,
                pm.count.toString(),
                '£' + formatCurrencySymbol(pm.total),
            ]);

            autoTable(doc, {
                head: [['Method', 'Count', 'Total']],
                body: paymentData,
                startY: currentY + 5,
                theme: 'grid',
                styles: {
                    fontSize: 9,
                    cellPadding: 3,
                },
                headStyles: {
                    fillColor: [52, 152, 219],
                    textColor: 255,
                    fontStyle: 'bold',
                    halign: 'center',
                },
                columnStyles: {
                    1: { halign: 'center' },
                    2: { halign: 'right' },
                },
                margin: { left: 14, right: 14 },
            });
        }

        // Top Items Table
        if (data.topItems.length > 0) {
            currentY = doc.lastAutoTable.finalY + 10;

            if (currentY > 250) {
                doc.addPage();
                currentY = 14;
            }

            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('Top Selling Items', 14, currentY);

            const itemsData = data.topItems.map(item => [
                item.name,
                item.total_qty.toString(),
                '£' + formatCurrencySymbol(item.total_revenue),
            ]);

            autoTable(doc, {
                head: [['Item', 'Qty', 'Revenue']],
                body: itemsData,
                startY: currentY + 5,
                theme: 'grid',
                styles: {
                    fontSize: 9,
                    cellPadding: 3,
                },
                headStyles: {
                    fillColor: [231, 76, 60],
                    textColor: 255,
                    fontStyle: 'bold',
                    halign: 'center',
                },
                columnStyles: {
                    1: { halign: 'center' },
                    2: { halign: 'right' },
                },
                margin: { left: 14, right: 14 },
            });
        }

        // Save PDF
        doc.save(res.data.fileName);
        toast.success('X Report PDF downloaded successfully');
    } catch (error) {
        console.error('Failed to download X Report PDF:', error);
        toast.error(error.response?.data?.message || 'Failed to download X Report PDF');
    }
};
const generateZReport = async (shift) => {
    // Check if shift is closed
    if (shift.status !== 'closed') {
        toast.warning('Z Report can only be generated for closed shifts');
        return;
    }

    try {
        // Call API to get Z Report data
        const res = await axios.get(`/api/shift/${shift.id}/z-report`);

        if (res.data.success) {
            // Store report data
            zReportData.value = res.data.data;
            // Show modal
            showZReportModal.value = true;
            toast.success('Z Report generated successfully');
        }
    } catch (error) {
        console.error('Failed to generate Z Report:', error);
        toast.error(error.response?.data?.message || 'Failed to generate Z Report');
    }
};
const closeZReportModal = () => {
    showZReportModal.value = false;
    zReportData.value = null;
};

const downloadZReportPdf = async (shiftId) => {
    try {
        const res = await axios.get(`/api/shift/${shiftId}/z-report/pdf`);

        if (!res.data.success) {
            toast.error('Failed to generate Z Report PDF');
            return;
        }

        const data = res.data.data;
        const doc = new jsPDF('p', 'mm', 'a4');
        let currentY = 20;

        // ============== HEADER ==============
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('Daily Summary Report', 105, currentY, { align: 'center' });

        currentY += 6;
        doc.setFontSize(12);
        doc.text('All Brands', 105, currentY, { align: 'center' });

        currentY += 6;
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text(data.generatedDate, 105, currentY, { align: 'center' });

        // ============== FLOAT SESSION ==============
        currentY += 10;
        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.text('Float Session', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');

        const floatData = [
            ['Started by', data.startedBy],
            ['Started at', data.startTime],
            ['Closed at', data.endTime],
            ['Closed by', data.endedBy],
            ['Opening Cash', formatCurrencySymbol(data.cashReconciliation.opening_cash)],
            ['Cash Expenses', formatCurrencySymbol(data.cashReconciliation.cash_expenses || 0)],
            ['Cash Transfers ', formatCurrencySymbol(data.cashReconciliation.cash_transfers || 0)],
            ['Cash Changed', formatCurrencySymbol(data.cashReconciliation.cash_changed || 0)],
            ['Cash Sales ', formatCurrencySymbol(data.cashReconciliation.cash_sales)],
            ['Cash Refunds', formatCurrencySymbol(data.cashReconciliation.cash_refunds || 0)],
            ['Estimated Closing Balance', formatCurrencySymbol(data.cashReconciliation.expected_cash)],
        ];

        floatData.forEach(([label, value]) => {
            doc.text(label, 16, currentY);
            doc.text(value, 190, currentY, { align: 'right' });
            currentY += 5;
        });

        // ============== FLOAT SESSION JOURNAL ==============
        currentY += 5;
        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.text('Float Session Journal', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        doc.setFont('helvetica', 'normal');
        doc.text('Deposits        Till Deposits', 16, currentY);
        doc.text('0.00', 190, currentY, { align: 'right' });
        currentY += 5;
        doc.text('Withdrawals Till Withdrawals', 16, currentY);
        doc.text('0.00', 190, currentY, { align: 'right' });
        currentY += 5;
        doc.text('Total', 16, currentY);
        doc.text('0.00', 190, currentY, { align: 'right' });

        // ============== SALES SUMMARY ==============
        currentY += 10;
        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.text('Sales Summary', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        doc.setFont('helvetica', 'normal');

        const salesData = [
            ['Sales Count', data.salesSummary.total_orders.toString()],
            ['Average Ticket Size', formatCurrencySymbol(data.salesSummary.avg_order_value)],
            ['', ''],
            ['Retail Price', formatCurrencySymbol(data.salesSummary.subtotal + data.salesSummary.total_tax)],
            ['Discount *', formatCurrencySymbol(data.salesSummary.total_discount)],
            ['Refund **', formatCurrencySymbol(0)],
            ['Net Retail Price', formatCurrencySymbol(data.salesSummary.total_sales)],
            ['Net Charges ?', formatCurrencySymbol(data.salesSummary.total_charges || 0)],
            ['Sale Price', formatCurrencySymbol(data.salesSummary.subtotal)],
            ['Tax ?', formatCurrencySymbol(data.salesSummary.total_tax)],
            ['Sale Price Inclusive of Tax', formatCurrencySymbol(data.salesSummary.total_sales)],
            ['', ''],
            ['Paid Amount', formatCurrencySymbol(data.salesSummary.total_sales)],
            ['Net Paid Amount', formatCurrencySymbol(data.salesSummary.total_sales)],
            ['Balance', formatCurrencySymbol(0)],
        ];

        salesData.forEach(([label, value]) => {
            if (label === '') {
                currentY += 3;
            } else {
                doc.text(label, 16, currentY);
                doc.text(value, 190, currentY, { align: 'right' });
                currentY += 5;
            }
        });

        // Check for page break
        if (currentY > 250) {
            doc.addPage();
            currentY = 20;
        }

        // ============== PAYMENT METHOD BREAKDOWN ==============
        currentY += 5;
        doc.text('Net Cash Receipts', 16, currentY);
        doc.text(formatCurrencySymbol(data.cashReconciliation.cash_sales), 190, currentY, { align: 'right' });
        currentY += 5;

        data.paymentMethods.forEach(pm => {
            if (pm.method.toLowerCase() !== 'cash') {
                doc.text(`Net ${pm.method} Receipts`, 16, currentY);
                doc.text(formatCurrencySymbol(pm.net), 190, currentY, { align: 'right' });
                currentY += 5;
            }
        });

        doc.text('Net Online Payment Receipts', 16, currentY);
        const onlineTotal = data.paymentMethods
            .filter(pm => ['online', 'card'].includes(pm.method.toLowerCase()))
            .reduce((sum, pm) => sum + pm.net, 0);
        doc.text(formatCurrencySymbol(onlineTotal), 190, currentY, { align: 'right' });
        currentY += 5;
        doc.text('Net Other Receipts', 16, currentY);
        doc.text('£0.00', 190, currentY, { align: 'right' });
        currentY += 8;

        doc.text('Unpaid Sales Count', 16, currentY);
        doc.text(formatCurrencySymbol(0), 190, currentY, { align: 'right' });
        currentY += 5;
        doc.text('Unpaid Sales Amount', 16, currentY);
        doc.text(formatCurrencySymbol(0), 190, currentY, { align: 'right' });

        // ============== SALES VAT ==============
        if (currentY > 220) {
            doc.addPage();
            currentY = 20;
        }

        currentY += 10;
        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.text('Sales VAT', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        autoTable(doc, {
            head: [['Tax %', 'Count', 'Sale*', 'Tax*']],
            body: [
                ['0.00 %', '1', '£1.00', '£0.00'],
                ['20.00 %', data.salesSummary.total_orders - 1,
                    formatCurrencySymbol(data.salesSummary.subtotal - 1),
                    formatCurrencySymbol(data.salesSummary.total_tax)],
            ],
            foot: [['Total', data.salesSummary.total_orders,
                formatCurrencySymbol(data.salesSummary.subtotal),
                formatCurrencySymbol(data.salesSummary.total_tax)]],
            startY: currentY,
            theme: 'grid',
            styles: { fontSize: 9, cellPadding: 3 },
            headStyles: { fillColor: [0, 0, 0], textColor: 255, fontStyle: 'bold', halign: 'center' },
            footStyles: { fillColor: [240, 240, 240], fontStyle: 'bold' },
            columnStyles: {
                0: { halign: 'left' },
                1: { halign: 'center' },
                2: { halign: 'right' },
                3: { halign: 'right' },
            },
            margin: { left: 14, right: 14 },
        });

        currentY = doc.lastAutoTable.finalY + 10;

        // ============== SALE REFUNDS ==============
        if (data.salesSummary.total_refunds > 0) {
            doc.setFillColor(0, 0, 0);
            doc.rect(14, currentY, 182, 8, 'F');
            doc.setTextColor(255, 255, 255);
            doc.text('Sale Refunds', 105, currentY + 5.5, { align: 'center' });
            doc.setTextColor(0, 0, 0);
            currentY += 12;
            // Add refunds data here if available
        } else {
            doc.setFillColor(0, 0, 0);
            doc.rect(14, currentY, 182, 8, 'F');
            doc.setTextColor(255, 255, 255);
            doc.text('Sale Refunds', 105, currentY + 5.5, { align: 'center' });
            doc.setTextColor(0, 0, 0);
            currentY += 12;
            doc.setFont('helvetica', 'normal');
            doc.text('No Data Available', 105, currentY, { align: 'center' });
            currentY += 10;
        }

        // ============== SALE CANCELLATIONS ==============
        if (currentY > 230) {
            doc.addPage();
            currentY = 20;
        }

        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Sale Cancellations', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);
        currentY += 12;

        if (!data.cancelled_items || data.cancelled_items.length === 0) {
            doc.setFont('helvetica', 'normal');
            doc.text('No Data Available', 105, currentY, { align: 'center' });
            currentY += 10;
        }

        // ============== VENUE SALES ==============
        if (currentY > 230) {
            doc.addPage();
            currentY = 20;
        }

        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Venue Sales', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        if (data.venue_sales && data.venue_sales.length > 0) {
            autoTable(doc, {
                head: [['Venue', 'Count', 'Amount']],
                body: data.venue_sales.map(v => [v.venue, v.count, formatCurrencySymbol(v.amount)]),
                foot: [['Total',
                    data.venue_sales.reduce((s, v) => s + v.count, 0),
                    formatCurrencySymbol(data.venue_sales.reduce((s, v) => s + v.amount, 0))]],
                startY: currentY,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [0, 0, 0], textColor: 255, fontStyle: 'bold', halign: 'center' },
                footStyles: { fillColor: [240, 240, 240], fontStyle: 'bold' },
                columnStyles: { 1: { halign: 'center' }, 2: { halign: 'right' } },
                margin: { left: 14, right: 14 },
            });
            currentY = doc.lastAutoTable.finalY + 10;
        }

        // ============== DISPATCH SALES ==============
        if (currentY > 230) {
            doc.addPage();
            currentY = 20;
        }

        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Dispatch Sales', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        if (data.dispatch_sales && data.dispatch_sales.length > 0) {
            autoTable(doc, {
                head: [['Dispatch Type', 'Count', 'Amount']],
                body: data.dispatch_sales.map(d => [d.type, d.count, formatCurrencySymbol(d.amount)]),
                foot: [['Total',
                    data.dispatch_sales.reduce((s, d) => s + d.count, 0),
                    formatCurrencySymbol(data.dispatch_sales.reduce((s, d) => s + d.amount, 0))]],
                startY: currentY,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [0, 0, 0], textColor: 255, fontStyle: 'bold', halign: 'center' },
                footStyles: { fillColor: [240, 240, 240], fontStyle: 'bold' },
                columnStyles: { 1: { halign: 'center' }, 2: { halign: 'right' } },
                margin: { left: 14, right: 14 },
            });
            currentY = doc.lastAutoTable.finalY + 10;
        }

        // ============== PAYMENT METHOD SALES ==============
        if (currentY > 210) {
            doc.addPage();
            currentY = 20;
        }

        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Payment Method Sales', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        autoTable(doc, {
            head: [['Pay Method', 'Receipts', 'Refunds', 'Net']],
            body: data.paymentMethods.map(pm => [
                pm.method,
                formatCurrencySymbol(pm.receipts),
                formatCurrencySymbol(pm.refunds),
                formatCurrencySymbol(pm.net)
            ]),
            foot: [['Total',
                formatCurrencySymbol(data.paymentMethods.reduce((s, pm) => s + pm.receipts, 0)),
                formatCurrencySymbol(data.paymentMethods.reduce((s, pm) => s + pm.refunds, 0)),
                formatCurrencySymbol(data.paymentMethods.reduce((s, pm) => s + pm.net, 0))]],
            startY: currentY,
            theme: 'grid',
            styles: { fontSize: 9, cellPadding: 3 },
            headStyles: { fillColor: [0, 0, 0], textColor: 255, fontStyle: 'bold', halign: 'center' },
            footStyles: { fillColor: [240, 240, 240], fontStyle: 'bold' },
            columnStyles: {
                0: { halign: 'left' },
                1: { halign: 'right' },
                2: { halign: 'right' },
                3: { halign: 'right' },
            },
            margin: { left: 14, right: 14 },
        });

        currentY = doc.lastAutoTable.finalY + 10;

        // ============== MENU CATEGORIES SUMMARY ==============
        if (currentY > 210) {
            doc.addPage();
            currentY = 20;
        }

        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Menu Categories Summary', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        if (data.menu_category_summary && data.menu_category_summary.length > 0) {
            autoTable(doc, {
                head: [['Menu Category', 'Count', 'Amount*']],
                body: data.menu_category_summary.map(cat => [
                    cat.category,
                    cat.count,
                    formatCurrencySymbol(cat.amount)
                ]),
                foot: [['Total',
                    data.menu_category_summary.reduce((s, c) => s + c.count, 0),
                    formatCurrencySymbol(data.menu_category_summary.reduce((s, c) => s + c.amount, 0))]],
                startY: currentY,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [0, 0, 0], textColor: 255, fontStyle: 'bold', halign: 'center' },
                footStyles: { fillColor: [240, 240, 240], fontStyle: 'bold' },
                columnStyles: { 1: { halign: 'center' }, 2: { halign: 'right' } },
                margin: { left: 14, right: 14 },
            });
            currentY = doc.lastAutoTable.finalY + 10;
        }

        // ============== COVERS SALES SUMMARY ==============
        if (currentY > 240) {
            doc.addPage();
            currentY = 20;
        }

        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Covers Sales Summary', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        doc.setFont('helvetica', 'normal');
        doc.text('Total number of covers', 16, currentY);
        doc.text(data.covers_summary?.total_covers.toString() || '0', 190, currentY, { align: 'right' });
        currentY += 5;
        doc.text('Average revenue per cover', 16, currentY);
        doc.text(formatCurrencySymbol(data.covers_summary?.avg_revenue_per_cover || 0), 190, currentY, { align: 'right' });

        // ============== SALE DISCOUNTS ==============
        currentY += 10;
        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Sale Discounts', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        if (data.discounts_summary && data.discounts_summary.length > 0) {
            autoTable(doc, {
                head: [['Discount Type', 'Count', 'Amount']],
                body: data.discounts_summary.map(d => [d.type, d.count, formatCurrencySymbol(d.amount)]),
                foot: [['Total',
                    data.discounts_summary.reduce((s, d) => s + d.count, 0),
                    formatCurrencySymbol(data.discounts_summary.reduce((s, d) => s + d.amount, 0))]],
                startY: currentY,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [0, 0, 0], textColor: 255, fontStyle: 'bold', halign: 'center' },
                footStyles: { fillColor: [240, 240, 240], fontStyle: 'bold' },
                columnStyles: { 1: { halign: 'center' }, 2: { halign: 'right' } },
                margin: { left: 14, right: 14 },
            });
            currentY = doc.lastAutoTable.finalY + 10;
        }

        // ============== SALE CHARGES ==============
        if (currentY > 230) {
            doc.addPage();
            currentY = 20;
        }

        doc.setFillColor(0, 0, 0);
        doc.rect(14, currentY, 182, 8, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('Sale Charges', 105, currentY + 5.5, { align: 'center' });
        doc.setTextColor(0, 0, 0);

        currentY += 12;
        if (data.charges_summary && data.charges_summary.length > 0) {
            autoTable(doc, {
                head: [['Scheme', 'Count', 'Amount', 'Tax']],
                body: data.charges_summary.map(c => [
                    c.scheme,
                    c.count,
                    formatCurrencySymbol(c.amount),
                    formatCurrencySymbol(c.tax)
                ]),
                foot: [['Total',
                    data.charges_summary.reduce((s, c) => s + c.count, 0),
                    formatCurrencySymbol(data.charges_summary.reduce((s, c) => s + c.amount, 0)),
                    formatCurrencySymbol(data.charges_summary.reduce((s, c) => s + c.tax, 0))]],
                startY: currentY,
                theme: 'grid',
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [0, 0, 0], textColor: 255, fontStyle: 'bold', halign: 'center' },
                footStyles: { fillColor: [240, 240, 240], fontStyle: 'bold' },
                columnStyles: {
                    1: { halign: 'center' },
                    2: { halign: 'right' },
                    3: { halign: 'right' },
                },
                margin: { left: 14, right: 14 },
            });
            currentY = doc.lastAutoTable.finalY + 10;
        }

        // Save PDF
        doc.save(res.data.fileName);
        toast.success('Z Report PDF downloaded successfully');
    } catch (error) {
        console.error('Failed to download Z Report PDF:', error);
        toast.error(error.response?.data?.message || 'Failed to download Z Report PDF');
    }
};

const printZReport = async (shiftId) => {
    try {
        const res = await axios.post(`/api/printers/${shiftId}/z-report/print`);
        
        if (res.data.success) {
            toast.success('Z Report sent to printer successfully');
        } else {
            toast.error(res.data.message || 'Print failed');
        }
    } catch (error) {
        console.error('Print failed:', error);
        toast.error('Unable to connect to the printer. Please ensure it is properly connected.');
    }
};


</script>

<template>
    <Master>

        <Head title="Promo" />
        <div class="page-wrapper">

            <h4 class="fw-semibold mb-3">Shift Management</h4>

            <!-- KPI Cards -->
            <div class="row g-3">
                <div v-for="stat in shiftstats" :key="stat.label" class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex align-items-center">
                            <div :class="[stat.iconBg, stat.iconColor]"
                                class="rounded-circle p-3 d-flex align-items-center justify-content-center me-3"
                                style="width: 56px; height: 56px">
                                <component :is="stat.icon" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="mb-0 fw-bold">{{ stat.value }}</h3>
                                <p class="text-muted mb-0 small">{{ stat.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table card -->
            <div class="card border-0 shadow-lg rounded-4 mt-0">
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fw-semibold">shifts</h5>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="email" name="email" autocomplete="email"
                                    style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                                    aria-hidden="true" />

                                <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                                    class="form-control search-input" placeholder="Search" type="search"
                                    autocomplete="new-password" :name="inputId" role="presentation"
                                    @focus="handleFocus" />
                                <input v-else class="form-control search-input" placeholder="Search" disabled
                                    type="text" />
                            </div>

                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill py-2 px-4 dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                    Export
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                                    <!-- Existing Export Options -->
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;"
                                            @click="onDownload('pdf')">Export as PDF</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;"
                                            @click="onDownload('excel')">Export as Excel</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('csv')">
                                            Export as CSV
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;"
                                            @click="onDownload('x-report')">
                                            Generate X Report
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item py-2" href="javascript:;"
                                            @click="onDownload('z-report')">
                                            Generate Z Report
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="border-top small text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>Shift ID</th>
                                    <th>Started By</th>
                                    <th>Start Time</th>
                                    <th>Opening Cash</th>
                                    <th>Closing Cash</th>
                                    <th>Sales Total</th>
                                    <th>Closed By</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="(shift, i) in filtered" :key="shift.id">
                                    <td>{{ i + 1 }}</td>
                                    <td>#{{ shift.id }}</td>
                                    <td>{{ shift.started_by || 'N/A' }}</td>
                                    <td>{{ formatTime(shift.start_time) }}</td>
                                    <td>{{ formatCurrencySymbol(shift.opening_cash) }}</td>
                                    <td>{{ formatCurrencySymbol(shift.closing_cash) }}</td>
                                    <td>{{ formatCurrencySymbol(shift.sales_total) }}</td>
                                    <td>{{ shift.ended_by || 'N/A' }}</td>

                                    <td class="text-center">
                                        <span :class="shift.status === 'open'
                                            ? 'badge bg-success px-4 py-2 rounded-pill'
                                            : 'badge bg-danger px-4 py-2 rounded-pill'">
                                            {{ shift.status === 'open' ? 'Open' : 'Closed' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-3">
                                            <!-- View Shift -->
                                            <button @click="viewShift(shift)" title="View Shift"
                                                class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                                <Eye class="w-4 h-4" />
                                            </button>
                                            <button @click="handleToggleShift(shift)"
                                                class="relative inline-flex items-center w-10 h-5 rounded-full transition-colors duration-300 focus:outline-none"
                                                :class="shift.status === 'open'
                                                    ? 'bg-green-500 hover:bg-green-600'
                                                    : 'bg-red-400 hover:bg-red-500'" :title="shift.status === 'open' ? 'Close Shift' : 'Reopen Shift'">
                                                <span
                                                    class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-300"
                                                    :class="shift.status === 'open' ? 'translate-x-5' : 'translate-x-0'"></span>
                                            </button>
                                        </div>
                                    </td>

                                </tr>

                                <tr v-if="!shifts.length">
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No shifts found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Shift Details Modal -->
            <div v-if="showShiftDetailsModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="relative bg-white rounded-4 shadow-lg border-0 overflow-hidden">
                    <!-- Header -->
                    <div class="modal-header align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div class="d-flex flex-column">
                                <h5 class="modal-title mb-0">Shift Details</h5>
                                <small class="text-muted">ID: {{ selectedShiftId }}</small>
                            </div>
                        </div>
                        <button @click="closeShiftDetailsModal"
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-danger" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-4 bg-light">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Shift Information</h6>

                                        <div v-if="selectedShiftDetails.length" class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="fw-semibold">Shift ID</th>
                                                        <th class="fw-semibold">Role</th>
                                                        <th class="fw-semibold">Start Date</th>
                                                        <th class="fw-semibold">Sales Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(detail, index) in selectedShiftDetails" :key="index">
                                                        <td>{{ selectedShiftId }}</td>
                                                        <td>
                                                            <span class="badge bg-primary rounded-pill">
                                                                {{ detail.role || 'N/A' }}
                                                            </span>
                                                        </td>
                                                        <td>{{ new Date(detail.joined_at).toLocaleString() }}</td>
                                                        <td class="fw-semibold">
                                                            {{ formatCurrencySymbol(detail.sales_amount || 0) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div v-else class="text-center py-5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"
                                                class="text-muted mx-auto mb-3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p class="text-muted mb-0">No details available for this shift.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button class="btn btn-secondary px-4 rounded-pill" @click="closeShiftDetailsModal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
            <div v-if="showXReportModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4 ">
                <div
                    class="relative bg-white rounded-4 shadow-lg border-0  max-w-2xl overflow-hidden max-h-[85vh] flex flex-col mt-5">

                    <!-- Header -->
                    <div class="modal-header align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div class="d-flex flex-column">
                                <h5 class="modal-title mb-0">X Report</h5>
                                <small class="text-muted">Shift ID: {{ xReportData?.shift_id }}</small>
                            </div>
                        </div>
                        <button @click="closeXReportModal"
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-danger" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="modal-body p-4 bg-light overflow-y-auto" v-if="xReportData">
                        <div class="row g-4">

                            <div class="col-lg-12">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Shift Information</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2"><strong>Started By:</strong> {{ xReportData.started_by
                                                }}</p>
                                                <p class="mb-2"><strong>Start Time:</strong> {{ new
                                                    Date(xReportData.start_time).toLocaleString()
                                                }}</p>
                                                <p class="mb-0"><strong>Opening Cash:</strong> {{
                                                    formatCurrencySymbol(xReportData.opening_cash)
                                                }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2"><strong>Status:</strong> <span
                                                        class="badge bg-primary">{{
                                                            xReportData.status
                                                        }}</span></p>
                                                <p class="mb-0"><strong>Generated At:</strong> {{ new
                                                    Date(xReportData.generated_at).toLocaleString() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Summary -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Sales Summary</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <tr>
                                                    <td>Total Orders:</td>
                                                    <td class="text-end">{{
                                                        xReportData.sales_summary.total_orders
                                                    }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Subtotal:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(xReportData.sales_summary.subtotal) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Tax:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(xReportData.sales_summary.total_tax) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Discount:</td>
                                                    <td class="text-end text-danger">-{{
                                                        formatCurrencySymbol(xReportData.sales_summary.total_discount)
                                                    }}
                                                    </td>
                                                </tr>
                                                <tr class=" border-top">
                                                    <td>Total Sales:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(xReportData.sales_summary.total_sales)
                                                    }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cash Summary -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Cash Summary</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <tr>
                                                    <td>Opening Cash:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(xReportData.cash_summary.opening_cash)
                                                    }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Cash Sales:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(xReportData.cash_summary.cash_sales) }}
                                                    </td>
                                                </tr>
                                                <tr class="fw-bold border-top">
                                                    <td>Expected Cash:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(xReportData.cash_summary.expected_cash)
                                                    }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Methods -->
                            <div class="col-12" v-if="xReportData.payment_methods.length">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Payment Methods</h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="fw-semibold">Method</th>
                                                        <th class="fw-semibold">Count</th>
                                                        <th class="fw-semibold text-end pe-3">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(pm, idx) in xReportData.payment_methods" :key="idx">
                                                        <td>{{ pm.method }}</td>
                                                        <td>{{ pm.count }}</td>
                                                        <td class="text-end pe-3">{{ formatCurrencySymbol(pm.total) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales by User -->
                            <div class="col-12" v-if="xReportData.sales_by_user.length">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Sales by User</h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="fw-semibold">User</th>
                                                        <th class="fw-semibold">Role</th>
                                                        <th class="fw-semibold">Orders</th>
                                                        <th class="fw-semibold text-end pe-3">Total Sales</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(user, idx) in xReportData.sales_by_user" :key="idx">
                                                        <td>{{ user.user_name }}</td>
                                                        <td><span class="badge bg-primary rounded-pill">{{ user.role
                                                        }}</span></td>
                                                        <td>{{ user.orders_count }}</td>
                                                        <td class="text-end pe-3">{{
                                                            formatCurrencySymbol(user.total_sales) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Selling Items -->
                            <div class="col-12" v-if="xReportData.top_items.length">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Top Selling Items</h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="fw-semibold">Item</th>
                                                        <th class="fw-semibold">Quantity Sold</th>
                                                        <th class="fw-semibold text-end pe-3">Revenue</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(item, idx) in xReportData.top_items" :key="idx">
                                                        <td>{{ item.name }}</td>
                                                        <td>{{ item.total_qty }}</td>
                                                        <td class="text-end pe-3">{{
                                                            formatCurrencySymbol(item.total_revenue) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button class="btn btn-primary px-4 rounded-pill"
                            @click="downloadXReportPdf(xReportData.shift_id)">
                            <i class="bi bi-download me-2"></i>Download PDF
                        </button>
                        <button class="btn btn-secondary px-4 rounded-pill" @click="closeXReportModal">Close</button>
                    </div>
                </div>
            </div>
            <div v-if="showZReportModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div
                    class="relative bg-white rounded-4 shadow-lg border-0 max-w-2xl overflow-hidden max-h-[85vh] flex flex-col mt-5">

                    <!-- Header -->
                    <div class="modal-header align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger rounded-circle p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div class="d-flex flex-column">
                                <h5 class="modal-title mb-0">Z Report (End of Shift)</h5>
                                <small class="text-muted">Shift ID: {{ zReportData?.shift_id }}</small>
                            </div>
                        </div>
                        <button @click="closeZReportModal"
                            class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                            aria-label="Close" title="Close">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-danger" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body p-4 bg-light overflow-y-auto max-h-[70vh]" v-if="zReportData">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Shift Information</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2"><strong>Started By:</strong> {{ zReportData.started_by
                                                }}</p>
                                                <p class="mb-2"><strong>Start Time:</strong> {{ new
                                                    Date(zReportData.start_time).toLocaleString() }}</p>
                                                <p class="mb-0"><strong>Ended By:</strong> {{ zReportData.ended_by }}
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2"><strong>End Time:</strong> {{ new
                                                    Date(zReportData.end_time).toLocaleString() }}</p>
                                                <p class="mb-2"><strong>Status:</strong> <span
                                                        class="badge bg-danger">{{
                                                            zReportData.status }}</span></p>
                                                <p class="mb-0"><strong>Duration:</strong> {{ zReportData.duration }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Sales Summary</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <tr>
                                                    <td>Total Orders:</td>
                                                    <td class="text-end">{{ zReportData.sales_summary.total_orders }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Subtotal:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(zReportData.sales_summary.subtotal)
                                                    }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Tax:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(zReportData.sales_summary.total_tax) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Discount:</td>
                                                    <td class="text-end text-danger">-{{
                                                        formatCurrencySymbol(zReportData.sales_summary.total_discount)
                                                    }}</td>
                                                </tr>
                                                <tr class="border-top">
                                                    <td>Total Sales:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(zReportData.sales_summary.total_sales) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Avg Order Value:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(zReportData.sales_summary.avg_order_value)
                                                    }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cash Reconciliation -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Cash Reconciliation</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <tr>
                                                    <td>Opening Cash:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(zReportData.cash_reconciliation.opening_cash)
                                                    }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Cash Sales:</td>
                                                    <td class="text-end">{{
                                                        formatCurrencySymbol(zReportData.cash_reconciliation.cash_sales)
                                                    }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Expected Cash:</td>
                                                    <td class="text-end fw-bold">{{
                                                        formatCurrencySymbol(zReportData.cash_reconciliation.expected_cash)
                                                    }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Actual Cash:</td>
                                                    <td class="text-end fw-bold">{{
                                                        formatCurrencySymbol(zReportData.cash_reconciliation.actual_cash)
                                                    }}</td>
                                                </tr>
                                                <tr
                                                    :class="zReportData.cash_reconciliation.variance >= 0 ? 'text-success' : 'text-danger'">
                                                    <td>Variance:</td>
                                                    <td class="text-end fw-bold">
                                                        {{ zReportData.cash_reconciliation.variance >= 0 ? '+' : '' }}{{
                                                            formatCurrencySymbol(zReportData.cash_reconciliation.variance)
                                                        }}
                                                        ({{ zReportData.cash_reconciliation.variance_percentage }}%)
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Methods -->
                            <div class="col-12" v-if="zReportData.payment_methods.length">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Payment Methods</h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Method</th>
                                                        <th>Count</th>
                                                        <th class="text-end pe-3">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(pm, idx) in zReportData.payment_methods" :key="idx">
                                                        <td>{{ pm.method }}</td>
                                                        <td>{{ pm.count }}</td>
                                                        <td class="text-end pe-3">{{ formatCurrencySymbol(pm.total) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales by User -->
                            <div class="col-12" v-if="zReportData.sales_by_user.length">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Sales by User</h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>User</th>
                                                        <th>Role</th>
                                                        <th>Orders</th>
                                                        <th class="text-end pe-3">Total Sales</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(user, idx) in zReportData.sales_by_user" :key="idx">
                                                        <td>{{ user.user_name }}</td>
                                                        <td><span class="badge bg-primary rounded-pill">{{ user.role
                                                        }}</span></td>
                                                        <td>{{ user.orders_count }}</td>
                                                        <td class="text-end pe-3">{{
                                                            formatCurrencySymbol(user.total_sales) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Selling Items -->
                            <div class="col-12" v-if="zReportData.top_items.length">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Top Selling Items </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Quantity Sold</th>
                                                        <th class="text-end pe-3">Revenue</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(item, idx) in zReportData.top_items" :key="idx">
                                                        <td>{{ item.name }}</td>
                                                        <td>{{ item.total_qty }}</td>
                                                        <td class="text-end pe-3">{{
                                                            formatCurrencySymbol(item.total_revenue) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Movement -->
                            <div class="col-12" v-if="zReportData.stock_movement && zReportData.stock_movement.length">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-body">
                                        <h6 class="fw-semibold mb-3">Stock Movement</h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Opening Stock</th>
                                                        <th>Closing Stock</th>
                                                        <th>Sold</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(stock, idx) in zReportData.stock_movement" :key="idx">
                                                        <td>{{ stock.item_name }}</td>
                                                        <td>{{ stock.start_stock }}</td>
                                                        <td>{{ stock.end_stock }}</td>
                                                        <td class="fw-bold text-danger">{{ stock.sold }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button class="btn btn-danger px-4 rounded-pill"
                            @click="downloadZReportPdf(zReportData.shift_id)">
                            <i class="bi bi-download me-2"></i>Download PDF
                        </button>
                        <button class="btn btn-primary px-4 rounded-pill ms-2"
                            @click="printZReport(zReportData.shift_id)">
                            <i class="bi bi-printer me-2"></i>Print Z Report
                        </button>
                        <button class="btn btn-secondary px-4 rounded-pill" @click="closeZReportModal">Close</button>
                    </div>
                </div>
            </div>


        </div>

        <CloseShiftModal :show="showCloseShiftModal" :shiftId="selectedShiftForClose?.id"
            :expectedClosingCash="selectedShiftForClose ? calculateExpectedClosingCash(selectedShiftForClose) : 0"
            @cancel="onCloseModalCancel" @closed="onShiftClosed" />
    </Master>
</template>

<style scoped>
.search-wrap {
    position: relative;
}

.search-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-input {
    padding-left: 40px;
    border-radius: 50px;
}

.img-chip {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
}


.dark .p-select {
    background-color: #121212 !important;
    color: #fff !important;
}

.dark .p-select-label {
    color: #fff !important;
}

.dark .p-select-list {
    background-color: #121212 !important;
    color: #fff !important;
}

:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

:deep(.p-multiselect-header) {
    background-color: white !important;
    color: black !important;

}

:deep(.p-multiselect-label) {
    color: #000 !important;
}

:deep(.p-select .p-component .p-inputwrapper) {
    background: #fff !important;
    color: #000 !important;
    border-bottom: 1px solid #ddd;
}

:deep(.p-multiselect-list) {
    background: #fff !important;
}

:deep(.p-multiselect-option) {
    background: #fff !important;
    color: #000 !important;
}

:deep(.p-multiselect-option.p-highlight) {
    background: #f0f0f0 !important;
    color: #000 !important;
}

:deep(.p-multiselect),
:deep(.p-multiselect-panel),
:deep(.p-multiselect-token) {
    background: #fff !important;
    color: #000 !important;
    border-color: #a4a7aa;
}

:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
}

:deep(.p-multiselect-filter) {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
}
:deep(.p-multiselect-filter-container) {
    background: #fff !important;
}
:deep(.p-multiselect-chip) {
    background: #e9ecef !important;
    color: #000 !important;
    border-radius: 12px !important;
    border: 1px solid #ccc !important;
    padding: 0.25rem 0.5rem !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon) {
    color: #555 !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #dc3545 !important;
}
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}
:deep(.p-select) {
    background-color: white !important;
    color: black !important;
    border-color: #9b9c9c
}
:deep(.p-select-list-container) {
    background-color: white !important;
    color: black !important;
}
:deep(.p-select-option) {
    background-color: transparent !important;
    color: black !important;
}
:deep(.p-select-option:hover) {
    background-color: #f0f0f0 !important;
    color: black !important;
}
:deep(.p-select-option.p-focus) {
    background-color: #f0f0f0 !important;
    color: black !important;
}

:deep(.p-select-label) {
    color: #000 !important;
}

:deep(.p-placeholder) {
    color: #80878e !important;
}

:global(.dark .p-multiselect-header) {
    background-color: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-label) {
    color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
    background: #181818 !important;
    color: #fff !important;
    border-bottom: 1px solid #555 !important;
}
:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}
:global(.dark .p-multiselect-option) {
    background: #181818 !important;
    color: #fff !important;
}
:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
    background: #222 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
    background: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #181818 !important;
    border: 1px solid #555 !important;
}
:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}
:global(.dark .p-multiselect-filter-container) {
    background: #181818 !important;
}
:global(.dark .p-multiselect-chip) {
    background: #111 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
}

:global(.dark .p-select) {
    background-color: #181818 !important;
    color: #fff !important;
    border-color: #555 !important;
}
:global(.dark .p-select-list-container) {
    background-color: #181818 !important;
    color: #fff !important;
}
:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}
:global(.dark .p-select-option:hover),
:global(.dark .p-select-option.p-focus) {
    background-color: #222 !important;
    color: #fff !important;
}

:global(.dark .p-select-label) {
    color: #fff !important;
}

:global(.dark .p-placeholder) {
    color: #aaa !important;
}
</style>