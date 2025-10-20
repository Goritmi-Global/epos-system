<script setup>
import { ref, computed, onMounted, nextTick, watch } from "vue";
import { toast } from "vue3-toastify";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import axios from "axios";
import { Pencil, Plus } from "lucide-vue-next";
import ImportFile from "@/Components/importFile.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";

const customAllergy = ref("");
const isEditing = ref(false);
const editingRow = ref(null);

const q = ref("");

const resetForm = () => {
    customAllergy.value = "";
    formErrors.value = {};
};

const filteredAllergys = computed(() => {
    const searchTerm = q.value.trim().toLowerCase();
    return searchTerm
        ? allergies.value.filter((allergy) =>
            allergy.name.toLowerCase().includes(searchTerm)
        )
        : allergies.value;
});

const openAdd = () => {
    isEditing.value = false;
    customAllergy.value = "";
};

const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    customAllergy.value = row.name;
};

const deleteAllergy = async (row) => {
    try {
        await axios.delete(`/allergies/${row.id}`);
        allergies.value = allergies.value.filter((t) => t.id !== row.id);
        toast.success("Allergy deleted successfully");
    } catch (e) {
        toast.error("Delete failed");
    }
};

const isSubmitting = ref(false);
const onSubmit = async () => {
    if (isSubmitting.value) return;
    
    if (!customAllergy.value.trim()) {
        toast.error("Please enter an allergy name");
        formErrors.value = {
            customAllergy: ["Please enter an allergy name"],
        };
        return;
    }

    if (isEditing.value) {
        // Update existing allergy
        try {
            isSubmitting.value = true;
            const { data } = await axios.put(
                `/allergies/${editingRow.value.id}`,
                {
                    name: customAllergy.value.trim(),
                }
            );

            const idx = allergies.value.findIndex(
                (t) => t.id === editingRow.value.id
            );
            if (idx !== -1) allergies.value[idx] = data;

            toast.success("Allergy updated successfully");
            await fetchAllergies();
            resetForm();
            closeModal("modalAllergyForm");
        } catch (e) {
            if (e.response?.data?.errors) {
                formErrors.value = {};
                Object.entries(e.response.data.errors).forEach(
                    ([field, msgs]) => {
                        msgs.forEach((m) => toast.error(m));
                        formErrors.value = { customAllergy: msgs };
                    }
                );
            } else {
                toast.error("Update failed");
            }
        } finally {
            isSubmitting.value = false;
        }
    } else {
        // Create new allergy
        const allergyName = customAllergy.value.trim();
        
        // Check if already exists
        const exists = allergies.value.some(
            (a) => a.name.toLowerCase() === allergyName.toLowerCase()
        );
        
        if (exists) {
            toast.error(`Allergy "${allergyName}" already exists`);
            formErrors.value = { customAllergy: ["This allergy already exists"] };
            return;
        }

        try {
            isSubmitting.value = true;
            const response = await axios.post("/allergies", {
                allergies: [{ name: allergyName }],
            });

            const createdTags = response.data?.allergies ?? response.data;

            if (Array.isArray(createdTags) && createdTags.length) {
                allergies.value = [...allergies.value, ...createdTags];
            }

            toast.success("Allergy added successfully");
            resetForm();
            closeModal("modalAllergyForm");
            await fetchAllergies();
        } catch (e) {
            if (e.response?.data?.errors) {
                Object.values(e.response.data.errors).forEach((msgs) =>
                    msgs.forEach((m) => toast.error(m))
                );
            } else {
                console.error(e);
                toast.error("Create failed");
            }
        } finally {
            isSubmitting.value = false;
        }
    }
};

const closeModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;
    const modal =
        window.bootstrap?.Modal.getInstance(el) ||
        new window.bootstrap.Modal(el);
    modal.hide();
};

const allergies = ref([]);
const page = ref(1);
const perPage = ref(15);
const loading = ref(false);
const formErrors = ref({});

const fetchAllergies = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/allergies", {
            params: { q: q.value, page: page.value, per_page: perPage.value },
        });

        allergies.value = data?.data ?? data?.allergies?.data ?? data ?? [];
        await nextTick();
        window.feather?.replace();
    } catch (err) {
        console.error("Failed to fetch allergies", err);
    } finally {
        loading.value = false;
    }
};

const onDownload = (type) => {
    if (!allergies.value || allergies.value.length === 0) {
        toast.error("No Allergies data to download");
        return;
    }

    const dataToExport = q.value.trim() ? filteredAllergys.value : allergies.value;

    if (dataToExport.length === 0) {
        toast.error("No Allergies found to download");
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
        const headers = ["Name"];
        const rows = data.map((s) => [`"${s.name || ""}"`]);
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
            `allergies_${new Date().toISOString().split("T")[0]}.csv`
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
        const doc = new jsPDF("p", "mm", "a4"); // portrait mode, mm units, A4 size

        // 🧾 Header section
        doc.setFont("helvetica", "bold");
        doc.setFontSize(18);
        doc.text("Allergies Report", 70, 20);

        doc.setFont("helvetica", "normal");
        doc.setFontSize(10);
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 30);
        doc.text(`Total Allergies: ${data.length}`, 14, 36);

        // 🧱 Table header (same as CSV)
        const headers = ["Name"];
        const rows = data.map((s) => [s.name || ""]);

        // 📊 Styled table
        autoTable(doc, {
            head: [headers],
            body: rows,
            startY: 45,
            styles: {
                fontSize: 10,
                cellPadding: 3,
                halign: "left",
                valign: "middle",
                lineColor: [220, 220, 220],
                lineWidth: 0.1,
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            margin: { left: 14, right: 14 },
            didDrawPage: (tableData) => {
                const pageCount = doc.internal.getNumberOfPages();
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.text(
                    `Page ${tableData.pageNumber} of ${pageCount}`,
                    14,
                    pageHeight - 10
                );
            },
        });

        // 💾 Save file
        const fileName = `allergies_${new Date().toISOString().split("T")[0]}.pdf`;
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
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        // 🧱 Prepare worksheet data (same as CSV)
        const worksheetData = data.map((allergy) => ({
            Name: allergy.name || "",
        }));

        // 📘 Create workbook & worksheet
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        // ✨ Set column widths
        worksheet["!cols"] = [{ wch: 25 }]; // Name column width

        // Add worksheet
        XLSX.utils.book_append_sheet(workbook, worksheet, "Allergies");

        // 📄 Metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Allergies Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // 💾 Save the file
        const fileName = `allergies_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully", { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};


onMounted(async () => {
    await fetchAllergies();
    window.feather?.replace();
});

watch(customAllergy, (newVal) => {
    if (newVal && formErrors.value.customAllergy) {
        delete formErrors.value.customAllergy;
    }
});

const handleImport = (data) => {
    console.log("Imported Data:", data);

    if (!Array.isArray(data) || data.length === 0) {
        toast.error("File is empty.");
        return;
    }

    const headers = data[0] || [];
    const rows = data.slice(1);

    const nonEmptyRows = rows.filter((row) =>
        Array.isArray(row) && row.some((cell) => String(cell ?? "").trim() !== "")
    );

    if (nonEmptyRows.length === 0) {
        toast.error("No data rows found the file is empty or contains only headers.");
        return;
    }

    const allergiesToImport = nonEmptyRows
        .map((row) => ({ name: String(row[0] ?? "").trim() }))
        .filter((r) => r.name !== "");

    if (allergiesToImport.length === 0) {
        toast.error("No valid allergy names found in the file.");
        return;
    }

    const namesLower = allergiesToImport.map((a) => a.name.toLowerCase());
    const dupes = namesLower.filter((n, i) => namesLower.indexOf(n) !== i);
    if (dupes.length) {
        const uniqueDupes = [...new Set(dupes)];
        toast.error(`Duplicate entries found in file: ${uniqueDupes.join(", ")}`);
        return;
    }

    const existingNames = allergies.value.map((a) => a.name.toLowerCase());
    const duplicatesInDB = allergiesToImport.filter((a) =>
        existingNames.includes(a.name.toLowerCase())
    );
    
    if (duplicatesInDB.length > 0) {
        const dupeNames = duplicatesInDB.map((a) => a.name).join(", ");
        toast.error(`These allergies already exist in the database: ${dupeNames}`);
        return;
    }

    axios
        .post("/api/allergies/import", { allergies: allergiesToImport })
        .then(() => {
            toast.success("Allergies imported successfully");
            fetchAllergies();
        })
        .catch((err) => {
            if (err?.response?.status === 422 && err.response.data?.errors) {
                formErrors.value = err.response.data.errors;
                const msg =
                    err.response.data.message ||
                    "There may be duplication or validation errors in the data.";
                toast.error(msg, { autoClose: 3000 });
            } else {
                toast.error("Import failed. Please try again.", { autoClose: 3000 });
                console.error(err);
            }
        });
};
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Allergies</h4>
                <div class="d-flex gap-2">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input v-model="q" class="form-control search-input" placeholder="Search" />
                    </div>

                    <button data-bs-toggle="modal" data-bs-target="#modalAllergyForm" @click="
                        () => {
                            openAdd();
                            resetForm();
                            formErrors = {};
                        }
                    " class="d-flex align-items-center gap-1 btn-sm px-4 py-2 rounded-pill btn btn-primary text-white">
                        <Plus class="w-4 h-4" /> Add Allergy
                    </button>

                    <ImportFile label="Import" :sampleHeaders="['Name']" :sampleData="[
                        ['Milk'],
                        ['Peanuts'],
                        ['Gluten']
                    ]" @on-import="handleImport" />

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm rounded-pill py-2 px-4 dropdown-toggle"
                            data-bs-toggle="dropdown">
                            Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">Export as PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">Export as Excel</a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('csv')">Export as CSV</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Allergy Name</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in filteredAllergys" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-3">
                                    <button data-bs-toggle="modal" data-bs-target="#modalAllergyForm" @click="
                                        () => {
                                            openEdit(r);
                                            formErrors = {};
                                        }
                                    " title="Edit" class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                        <Pencil class="w-4 h-4" />
                                    </button>

                                    <ConfirmModal :title="'Confirm Delete'"
                                        :message="`Are you sure you want to delete ${r.name}?`" :showDeleteButton="true"
                                        @confirm="
                                            () => {
                                                deleteAllergy(r);
                                            }
                                        " @cancel="() => { }" />
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredAllergys.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                {{
                                    q.trim()
                                        ? "No allergies found matching your search."
                                        : "No allergies found."
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="modalAllergyForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isEditing ? "Edit Allergy" : "Add Allergy(s)" }}
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
                    <div>
                        <label class="form-label">Allergy Name</label>
                        <input 
                            v-model="customAllergy" 
                            class="form-control" 
                            placeholder="e.g., Vegan"
                            :class="{ 'is-invalid': formErrors.customAllergy }"
                            @keyup.enter="onSubmit"
                        />
                        <span class="text-danger" v-if="formErrors.customAllergy">
                            {{ formErrors.customAllergy[0] }}
                        </span>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary rounded-pill py-2 btn-sm w-100 mt-4" :disabled="isSubmitting"
                            @click="onSubmit">
                            <template v-if="isSubmitting">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Saving...
                            </template>
                            <template v-else>
                                {{ isEditing ? "Save" : "Save" }}
                            </template>
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

.dropdown-menu {
    position: absolute !important;
    z-index: 1050 !important;
}

.table-container {
    overflow: visible !important;
}

.text-danger {
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

.dark .text-danger {
    color: #dc3545 !important;
}
</style>