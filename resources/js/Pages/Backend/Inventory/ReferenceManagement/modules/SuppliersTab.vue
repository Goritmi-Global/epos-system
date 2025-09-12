<script setup>
import { ref, computed, onMounted, onUpdated } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import { nextTick } from "vue";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { Pencil, Plus } from "lucide-vue-next";

const suppliers = ref([]);
const page = ref(1);
const perPage = ref(15);

const fetchSuppliers = () => {
    loading.value = true;

    return axios
        .get("/suppliers", {
            params: { q: q.value, page: page.value, per_page: perPage.value },
        })
        .then(({ data }) => {
            suppliers.value = data?.data ?? data?.suppliers?.data ?? data ?? [];
        })

        .catch((err) => {
            console.error(err);
        })
        .finally(() => {
            loading.value = false;
        });
};

const q = ref("");

const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return suppliers.value;
    return suppliers.value.filter((s) =>
        [s.name, s.phone, s.email, s.address, s.preferred_items].some((v) =>
            (v || "").toLowerCase().includes(t)
        )
    );
});

const onDownload = (type) => {
    if (!suppliers.value || suppliers.value.length === 0) {
        toast.error("No suppliers data to download");
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : suppliers.value;

    if (dataToExport.length === 0) {
        toast.error("No suppliers found to download");
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
        // Define headers
        const headers = [
            "Name",
            "Email",
            "Phone",
            "Address",
            "Preferred Items",
        ];

        // Build CSV rows
        const rows = data.map((s) => [
            `"${s.name || ""}"`,
            `"${s.email || ""}"`,
            `"${s.contact || ""}"`,
            `"${s.address || ""}"`,
            `"${s.preferred_items || ""}"`,
        ]);

        // Combine into CSV string
        const csvContent = [
            headers.join(","), // header row
            ...rows.map((r) => r.join(",")), // data rows
        ].join("\n");

        // Create blob
        const blob = new Blob([csvContent], {
            type: "text/csv;charset=utf-8;",
        });
        const url = URL.createObjectURL(blob);

        // Create download link
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `suppliers_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("CSV downloaded successfully .", { autoClose: 2500 });
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("p", "mm", "a4"); // portrait, millimeters, A4

        doc.setFontSize(20);
        doc.setFont("helvetica", "bold");
        doc.text("Suppliers Report", 70, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 70, 28);
        doc.text(`Total Suppliers: ${data.length}`, 70, 34);

        const tableColumns = [
            "Name",
            "Email",
            "Phone",
            "Address",
            "Preferred Items",
        ];
        const tableRows = data.map((s) => [
            s.name || "",
            s.email || "",
            s.contact || "",
            s.address || "",
            s.preferred_items || "",
        ]);

        // 📑 Styled table
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
            alternateRowStyles: { fillColor: [240, 240, 240] },
            margin: { left: 14, right: 14 },
            didDrawPage: (tableData) => {
                // Footer with page numbers
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${tableData.pageNumber} of ${pageCount}`,
                    tableData.settings.margin.left,
                    pageHeight - 10
                );
            },
        });

        // 💾 Save file
        const fileName = `suppliers_${new Date().toISOString().split("T")[0]
            }.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully .", { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }
        const worksheetData = data.map((supplier) => ({
            Name: supplier.name || "",
            Email: supplier.email || "",
            Phone: supplier.phone || supplier.contact || "",
            Address: supplier.address || "",
            "Preferred Items": supplier.preferred_items || "",
            ID: supplier.id || "",
        }));
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);
        const colWidths = [
            { wch: 20 }, // Name heading de
            { wch: 25 }, // Email de
            { wch: 15 },
            { wch: 30 },
            { wch: 25 },
            { wch: 10 },
        ];
        worksheet["!cols"] = colWidths;

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, "Suppliers");

        // Add metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Supplier Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Generate file name
        const fileName = `suppliers_${new Date().toISOString().split("T")[0]
            }.xlsx`;

        // Save the file
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully .", {
            autoClose: 2500,
        });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

const form = ref({
    name: "",
    email: "",
    phone: "",
    address: "",
    preferred_items: "", // Preferred Items
});

const phoneError = ref("");

const checkPhone = ({ valid, number, country }) => {
    if (!valid) {
        phoneError.value =
            "Invalid number for " + (country?.name || "selected country");
    } else {
        phoneError.value = ""; // clear error if valid
    }
};
const loading = ref(false);
const formErrors = ref({});

// helper to close a Bootstrap modal by id
const closeModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const modal =
        window.bootstrap?.Modal.getInstance(el) ||
        new window.bootstrap.Modal(el);
    modal.hide();
};

// reset form after submit or when needed
const resetForm = () => {
    form.value = {
        name: "",
        email: "",
        phone: "",
        address: "",
        preferred_items: "",
    };
    formErrors.value = {};
};

const submit = () => {
    loading.value = true;
    formErrors.value = {};
    const payload = {
        name: form.value.name,
        email: form.value.email,
        contact: form.value.phone || null,
        address: form.value.address || null,
        preferred_items: form.value.preferred_items || null,
    };
    axios
        .post("/suppliers", payload)
        .then((res) => {
            // console.log(res, form.value);

            fetchSuppliers();
            toast.success("Supplier added successfully.", {
                autoClose: 1500,
            });
            resetForm();
            closeModal("modalAddSupplier");
        })
        .catch((err) => {
            if (err?.response?.status === 422 && err.response.data?.errors) {
                // store errors if you still want to show them near inputs
                formErrors.value = err.response.data.errors;

                toast.error("Please fill in all required fields correctly.");
            } else {
                // toast.dismiss();
                toast.error("Something went wrong. Please try again.", {
                    autoClose: 3000,
                });
                console.error(err);
            }
        })
        .finally(() => {
            loading.value = false;
        });
};

// Run on page load
onMounted(async () => {
    await fetchSuppliers();
});

// code for other functionalities
// helpers (you already have closeModal)
const openModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const modal = new window.bootstrap.Modal(el);
    modal.show();
};

const processStatus = ref();
// Editing supplier record
const selectedSupplier = ref(null);
const onEdit = (row) => {
    processStatus.value = "Edit";
    selectedSupplier.value = row;
    // map backend -> form fields
    form.value = {
        name: row.name || "",
        email: row.email || "",
        phone: row.phone ?? row.contact ?? "",
        address: row.address || "",
        preferred_items: row.preferred_items ?? row.preferred_items ?? "",
    };
    openModal("modalAddSupplier");
};

const updateSupplier = () => {
    if (!selectedSupplier.value) return;
    loading.value = true;
    formErrors.value = {};

    const payload = {
        id: selectedSupplier.value.id,
        name: form.value.name,
        email: form.value.email,
        contact: form.value.phone || null,
        address: form.value.address || null,
        preferred_items: form.value.preferred_items || null,
    };

    axios
        .post("/suppliers/update", payload)
        .then((res) => {
            fetchSuppliers();
            toast.success("Supplier updated successfully .", {
                autoClose: 500,
            });
            resetForm();
            // close modal
            closeModal("modalAddSupplier");
            return nextTick();
        })
        .catch((err) => {
            if (err?.response?.status === 422 && err.response.data?.errors) {
                formErrors.value = err.response.data.errors;
                toast.error("Validation failed. Please check the fields.", {
                    autoClose: 3000,
                });
            } else {
                toast.error("Update failed. Please try again.", {
                    autoClose: 3000,
                });
                console.error(err);
            }
        })
        .finally(() => {
            loading.value = false;
        });
};

// ---- Deleting the supplier record----
const deleteSupplier = (id) => {
    loading.value = true;
    axios
        .delete(`/suppliers/${id}`)
        .then(() => {
            suppliers.value = suppliers.value.filter((s) => s.id !== id);
            toast.success("Supplier deleted .");
        })
        .catch((err) => {
            toast.error("Delete failed. Please try again.", {
                autoClose: 3000,
            });
            console.error(err);
        })
        .finally(() => {
            loading.value = false;
        });
};
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Suppliers</h4>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input v-model="q" class="form-control search-input" placeholder="Search" />
                    </div>

                    <button data-bs-toggle="modal" data-bs-target="#modalAddSupplier" @click="
                        () => {
                            resetForm();
                            formErrors = {};
                        }
                    " class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white">
                        <Plus class="w-4 h-4" /> Add Supplier
                    </button>

                    <!-- Download all -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                            data-bs-toggle="dropdown">
                            Download all
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">
                                    Download as PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">
                                    Download as Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('csv')">
                                    Download as CSV
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Supplier name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Items Linked</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(s, i) in filtered" :key="s.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ s.name }}</td>
                            <td>{{ s.contact }}</td>
                            <td class="text-break" style="max-width: 240px">
                                {{ s.email }}
                            </td>
                            <td class="text-truncate" style="max-width: 260px">
                                {{ s.address }}
                            </td>
                            <td>{{ s.preferred_items }}</td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-3">
                                    <button @click="
                                        () => {
                                            onEdit(s);
                                            formErrors = {};
                                        }
                                    " title="Edit" class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                        <Pencil class="w-4 h-4" />
                                    </button>

                                    <ConfirmModal :title="'Confirm Delete'"
                                        :message="`Are you sure you want to delete ${s.name}?`" :showDeleteButton="true"
                                        @confirm="
                                            () => {
                                                deleteSupplier(s.id);
                                            }
                                        " @cancel="() => { }" />
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">
                                No suppliers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="modalAddSupplier" tabindex="-1" aria-labelledby="modalAddSupplier" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        {{
                            processStatus === "Edit"
                                ? "Edit Supplier"
                                : "Add Supplier"
                        }}
                    </h5>
                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal" aria-label="Close" title="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-lg-6">
                            <label class="form-label">Name</label>
                            <input class="form-control" v-model="form.name"
                                :class="{ 'is-invalid': formErrors.name }" />
                            <small v-if="formErrors.name" class="text-danger">
                                {{ formErrors.name[0] }}
                            </small>
                        </div>

                        <!-- Email -->
                        <div class="col-lg-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" v-model="form.email"
                                :class="{ 'is-invalid': formErrors.email }" />
                            <small v-if="formErrors.email" class="text-danger">
                                {{ formErrors.email[0] }}
                            </small>
                        </div>

                        <!-- Phone (vue-tel-input) -->
                        <div class="col-lg-6">
                            <label class="form-label">Phone</label>
                            <vue-tel-input v-model="form.phone" default-country="PK" mode="international"
                                @validate="checkPhone" :auto-format="true" :enable-formatting="true"
                                :input-options="{ showDialCode: true }" :class="{ 'is-invalid': formErrors.phone }" />
                            <small v-if="formErrors.phone" class="text-danger">
                                {{ formErrors.phone[0] }}
                            </small>
                            <small v-else-if="phoneError" class="text-danger">
                                {{ phoneError }}
                            </small>
                        </div>

                        <!-- Preferred Items -->
                        <div class="col-lg-6">
                            <label class="form-label">Preferred Items</label>
                            <input class="form-control" v-model="form.preferred_items" :class="{
                                'is-invalid': formErrors.preferred_items,
                            }" />
                            <small v-if="formErrors.preferred_items" class="text-danger">
                                {{ formErrors.preferred_items[0] }}
                            </small>
                        </div>

                        <!-- Address -->
                        <div class="col-lg-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="4" v-model="form.address"
                                :class="{ 'is-invalid': formErrors.address }"></textarea>
                            <small v-if="formErrors.address" class="text-danger">
                                {{ formErrors.address[0] }}
                            </small>
                        </div>

                        <button v-if="processStatus === 'Edit'" class="btn btn-primary rounded-pill w-100 mt-4"
                            :disabled="loading" @click="updateSupplier()">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                            Update Supplier
                        </button>
                        <button v-else class="btn btn-primary rounded-pill w-100 mt-4" :disabled="loading"
                            @click="submit()">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                            Add Supplier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
:root {
    --brand: #1c0d82;
}

.btn-primary {
    background: var(--brand);
    border-color: var(--brand);
}

.search-wrap {
    position: relative;
    width: clamp(220px, 28vw, 360px);
}

.search-wrap .bi-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.search-input {
    padding-left: 38px;
    border-radius: 9999px;
}

.p-multiselect {
    background-color: white !important;
    color: black !important;
}

.dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
}

/* Ensure the table container doesn't clip the dropdown */
.table-container {
    overflow: visible !important;
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}
</style>
