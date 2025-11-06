<script setup>
import { ref, computed, onMounted, onUpdated } from "vue";
import axios from "axios";
import { toast } from "vue3-toastify";
import { nextTick } from "vue";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import { Pencil, Plus } from "lucide-vue-next";
import ImportFile from "@/Components/importFile.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { parsePhoneNumber, isValidPhoneNumber } from "libphonenumber-js";
import { Head, usePage } from "@inertiajs/vue3";
const pageProps = usePage();

const onboarding = computed(() => pageProps.props.onboarding.language_and_location?.country_id ?? "PK");
console.log("🌍 Onboarding Country:", onboarding.value);

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
const searchKey = ref(Date.now());
const inputId = `search-${Math.random().toString(36).substr(2, 9)}`;
const isReady = ref(false);

onMounted(async () => {
    q.value = "";
    searchKey.value = Date.now();
    await nextTick();

   
    setTimeout(() => {
        isReady.value = true;

        // Force clear any autofill that happened
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
            q.value = '';
        }
    }, 100);

    await fetchSuppliers();
});

const handleFocus = (e) => {
    // Clear on focus
    e.target.value = '';
    q.value = '';
};


const filtered = computed(() => {
    const query = q.value.trim().toLowerCase();
    if (!query) return suppliers.value;

    // Extract only digits from the query for phone/contact comparison
    const queryDigits = query.replace(/\D/g, "");

    return suppliers.value.filter((s) => {
        // Text fields (name, email, address, etc.)
        const textMatch = [s.name, s.email, s.address, s.preferred_items].some((v) =>
            (v || "").toLowerCase().includes(query)
        );

        // Numeric match for phone/contact
        const phoneDigits = (s.phone || "").replace(/\D/g, "");
        const contactDigits = (s.contact || "").replace(/\D/g, "");
        const numberMatch =
            (queryDigits && (phoneDigits.includes(queryDigits) || contactDigits.includes(queryDigits)));

        return textMatch || numberMatch;
    });
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
        const doc = new jsPDF("p", "mm", "a4"); // Portrait mode, A4 size
        doc.setFont("helvetica", "bold");
        doc.setFontSize(18);
        doc.text("Suppliers Report", 70, 20);

        doc.setFont("helvetica", "normal");
        doc.setFontSize(10);
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 30);
        doc.text(`Total Suppliers: ${data.length}`, 14, 36);
        const headers = [
            "Name",
            "Email",
            "Phone",
            "Address",
            "Preferred Items",
        ];
        const rows = data.map((s) => [
            s.name || "",
            s.email || "",
            s.contact || "",
            s.address || "",
            s.preferred_items || "",
        ]);
        autoTable(doc, {
            head: [headers],
            body: rows,
            startY: 45,
            styles: {
                fontSize: 9,
                cellPadding: 3,
                halign: "left",
                valign: "middle",
                lineColor: [200, 200, 200],
                lineWidth: 0.1,
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { left: 14, right: 14 },
            didDrawPage: (data) => {
                // Footer
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${data.pageNumber} of ${pageCount}`,
                    14,
                    pageHeight - 10
                );
            },
        });
        const fileName = `suppliers_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success("PDF downloaded successfully.", { autoClose: 2500 });
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

        const worksheetData = data.map((s) => ({
            Name: s.name || "",
            Email: s.email || "",
            Phone: s.contact || "",
            Address: s.address || "",
            "Preferred Items": s.preferred_items || "",
        }));

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [
            { wch: 20 }, // Name
            { wch: 25 }, // Email
            { wch: 18 }, // Phone
            { wch: 30 }, // Address
            { wch: 25 }, // Preferred Items
        ];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Suppliers");


        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Supplier Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");


        const fileName = `suppliers_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully.", { autoClose: 2500 });
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
const isPhoneValid = ref(false);


// const checkPhone = ({ valid, number, country }) => {
//     if (!valid) {
//         phoneError.value =
//             "Invalid number for " + (country?.name || "selected country");
//     } else {
//         phoneError.value = ""; // clear error if valid
//     }
// };

const checkPhone = ({ number, country }) => {
    // Don't validate until user starts typing something
    if (!number || number.trim() === "") {
        phoneError.value = "";
        isPhoneValid.value = false;
        return;
    }

    phoneError.value = "";
    isPhoneValid.value = false;

    try {
        const isValid = isValidPhoneNumber(number, country?.iso2 || "PK");

        if (!isValid) {
            phoneError.value = `Invalid phone number for ${country?.name || "selected country"}`;
            return;
        }

        const parsed = parsePhoneNumber(number, country?.iso2 || "PK");

        console.log("📞 Parsed Phone:", {
            formatted: parsed.formatInternational(),
            national: parsed.nationalNumber,
            country: parsed.country,
            type: parsed.getType(),
        });

        isPhoneValid.value = true;
    } catch (err) {
        console.error("Phone validation error:", err);
        phoneError.value = "Invalid phone number format";
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
    selectedSupplier.value = null;
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
            toast.success("Supplier deleted successfully.");
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

const handleImport = (data) => {
    console.log("Imported Data:", data);

    if (!data || data.length <= 1) {
        toast.error("The file is empty", {
            autoClose: 3000,
        });
        return;
    }

    // data is 2D array: [ [col1, col2, ...], [val1, val2, ...] ]
    const headers = data[0];
    const rows = data.slice(1);

    const suppliersToImport = rows.map((row) => {
        return {
            name: row[0] || "",
            email: row[1] || "",
            contact: row[2] || "",
            address: row[3] || "",
            preferred_items: row[4] || "",
        };
    });

    axios
        .post("/api/suppliers/import", { suppliers: suppliersToImport })
        .then(() => {
            toast.success("Suppliers imported successfully");
            fetchSuppliers();
        })
        .catch((err) => {
            if (err?.response?.status === 422 && err.response.data?.errors) {
                formErrors.value = err.response.data.errors;
                toast.error("There may be some duplication in data", {
                    autoClose: 3000,
                });
            }
        });
};


</script>

<template>
      <Head title="Supplier" />
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Suppliers</h4>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>

                        <!-- Hidden decoy input to catch autofill -->
                        <input type="email" name="email" autocomplete="email"
                            style="position: absolute; left: -9999px; width: 1px; height: 1px;" tabindex="-1"
                            aria-hidden="true" />

                        <input v-if="isReady" :id="inputId" v-model="q" :key="searchKey"
                            class="form-control search-input" placeholder="Search" type="search"
                            autocomplete="new-password" :name="inputId" role="presentation" @focus="handleFocus" />
                        <input v-else class="form-control search-input" placeholder="Search" disabled type="text" />
                    </div>

                    <button data-bs-toggle="modal" data-bs-target="#modalAddSupplier" @click="
                        () => {
                            resetForm();
                            formErrors = {};
                            processStatus = 'Add';
                        }
                    "
                        class="d-flex align-items-center gap-1 px-4 btn-sm py-2 rounded-pill btn-sm btn btn-primary text-white">
                        <Plus class="w-4 h-4" /> Add Supplier
                    </button>
                    <ImportFile label="Import"
                        :sampleHeaders="['Name', 'Email', 'Phone', 'Address', 'Preferred Items']" :sampleData="[
                            ['Ali Khan', 'ali@example.com', '03001234567', 'Lahore', 'Steel'],
                            ['Ahmed Raza', 'ahmed@example.com', '03111234567', 'Karachi', 'Cement']
                        ]" @on-import="handleImport" />

                    <!-- Download all -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm rounded-pill py-2 px-4 dropdown-toggle"
                            data-bs-toggle="dropdown">
                            Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">
                                    Export as PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">
                                    Export as Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('csv')">
                                    Export as CSV
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
                        <!-- ⏳ Loading -->
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="d-flex justify-content-center align-items-center gap-2">
                                    <div class="spinner-border" role="status"
                                        style="width: 1.5rem; height: 1.5rem; border-color: #1c0d82; border-right-color: transparent;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span>Fetching suppliers...</span>
                                </div>
                            </td>
                        </tr>


                        <!-- ❌ No suppliers -->
                        <tr v-else-if="filtered.length === 0">
                            <td colspan="7" class="text-center text-muted py-4">
                                No suppliers found.
                            </td>
                        </tr>

                        <!-- ✅ Supplier rows -->
                        <tr v-else v-for="(s, i) in filtered" :key="s.id">
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
                                        @confirm="() => deleteSupplier(s.id)" @cancel="() => { }" />
                                </div>
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
                            processStatus === "Add"
                                ? "Add Supplier"
                                : "Edit Supplier"
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

                            <vue-tel-input v-model="form.phone" :default-country="onboarding" :key="onboarding"
                                mode="international" class="phone" @validate="checkPhone" :auto-format="true"
                                :enable-formatting="true" :disabled-fetching-country="true" :dropdown-options="{
                                    showFlags: false,
                                    showDialCodeInSelection: true,
                                    disabled: true
                                }" :input-options="{ showDialCode: true }" :class="{
                                    'is-invalid': formErrors.contact || phoneError,
                                    'is-valid': isPhoneValid && form.phone
                                }" />


                            <!-- Show validation messages -->
                            <small v-if="formErrors.contact" class="text-danger">
                                {{ formErrors.contact[0] }}
                            </small>
                            <small v-else-if="phoneError" class="text-danger">
                                {{ phoneError }}
                            </small>
                            <small v-else-if="isPhoneValid && form.phone" class="text-success">
                                ✓ Valid phone number
                            </small>
                        </div>

                        <!-- Preferred Items -->
                        <div class="col-lg-6">
                            <label class="form-label">Preferred Items</label>
                            <input class="form-control" v-model="form.preferred_items" />
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

                        <div class="col-md-2">
                            <button v-if="processStatus === 'Edit'"
                                class="btn btn-primary btn-sm py-2 rounded-pill w-100 mt-4" :disabled="loading"
                                @click="updateSupplier()">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                Save
                            </button>
                            <button v-else class="btn btn-primary rounded-pill btn-sm py-2 w-100 mt-4"
                                :disabled="loading" @click="submit()">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                Save
                            </button>
                        </div>
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

:global(.dark .vti__dropdown.disabled){
    background-color: #212121 !important;
}
.search-wrap {
    position: relative;
    width: clamp(220px, 28vw, 360px);
}

.side-link{
  border-radius: 55%;
  background-color: #fff !important;
}

.dark .side-link{
  border-radius: 55%;
  background-color: #181818 !important;
}

.form-control[readonly] {
    background-color: #fff !important;
    opacity: 1 !important;
}

.dark .vti__dropdown.disabled {
    background-color: #212121 !important;
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



@media (min-width: 768px) and (max-width: 992px) {
    .search-wrap {
        width: 22% !important;
        margin-bottom: 0;
        min-width: 150px;
        flex-shrink: 1;
    }

    .search-input {
        width: 100% !important;
        font-size: 14px !important;
        padding: 8px 12px !important;
    }


    .d-flex.flex-wrap.gap-2.align-items-center {
        flex-direction: row !important;
        align-items: center !important;
        gap: 8px !important;
        flex-wrap: nowrap !important;
        margin-bottom: 15px;
    }

    .btn {
        width: auto !important;
        flex-shrink: 0;
        white-space: nowrap;
        font-size: 14px !important;
        padding: 8px 14px !important;
    }


    .dropdown,
    .dropdown-toggle {
        width: auto !important;
        flex-shrink: 0;
        white-space: nowrap;
        font-size: 14px !important;
        padding: 8px 14px !important;
    }


    .suppliers-container,
    .card-body {
        overflow-x: auto !important;
        padding: 20px 15px !important;
    }

    .table-responsive {
        overflow-x: auto !important;
    }

    .table td,
    .table th {
        white-space: normal !important;
        word-break: break-word;
        font-size: 14px !important;
        padding: 10px 8px !important;
    }
}


@media (min-width: 450px) and (max-width: 767px) {

    /* Make search bar smaller to fit everything */
    .search-wrap {
        width: 18% !important;
        margin-bottom: 0;
        min-width: 130px;
        flex-shrink: 1;
    }

    /* Reduce search input font size */
    .search-input {
        width: 100% !important;
        font-size: 13px !important;
        padding: 6px 10px !important;
    }

    /* Keep items in a row with proper spacing */
    .d-flex.flex-wrap.gap-2.align-items-center {
        flex-direction: row !important;
        align-items: center !important;
        gap: 5px !important;
        flex-wrap: nowrap !important;
        margin-bottom: 15px;
    }

    /* Reduce button sizes */
    .btn {
        width: auto !important;
        flex-shrink: 0;
        white-space: nowrap;
        font-size: 13px !important;
        padding: 7px 12px !important;
    }

    /* Reduce dropdown size */
    .dropdown,
    .dropdown-toggle {
        width: auto !important;
        flex-shrink: 0;
        white-space: nowrap;
        font-size: 13px !important;
        padding: 7px 12px !important;
    }

    /* Ensure container doesn't overflow */
    .suppliers-container,
    .card-body {
        overflow-x: auto !important;
        padding: 15px 10px !important;
    }

    /* Table responsiveness - allow wrapping long text */
    .table td,
    .table th {
        white-space: normal !important;
        word-break: break-word;
        font-size: 13px !important;
        padding: 8px 6px !important;
    }
}


@media (max-width: 449px) {

    .search-wrap {
        width: 100% !important;
        margin-bottom: 10px;
    }

    .d-flex.flex-wrap.gap-2.align-items-center {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px;
    }

    .btn,
    .dropdown {
        width: 100% !important;
    }

    .search-input {
        width: 100% !important;
        font-size: 14px !important;
    }

    .table {
        font-size: 12px !important;
    }

    .table td,
    .table th {
        padding: 6px 4px !important;
        white-space: normal !important;
        word-break: break-word;
    }
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

/* Hide dropdown arrow and increase country code font size */
:deep(.vue-tel-input .vti__dropdown) {
    pointer-events: none;
}

:deep(.vue-tel-input .vti__dropdown-arrow) {
    display: none !important;
}

:deep(.vue-tel-input .vti__selection) {
    font-size: 16px !important;
    font-weight: 600 !important;
    padding-right: 8px !important;
}

:deep(.vue-tel-input .vti__dropdown:hover) {
    background-color: transparent !important;
    cursor: default !important;
}
</style>
