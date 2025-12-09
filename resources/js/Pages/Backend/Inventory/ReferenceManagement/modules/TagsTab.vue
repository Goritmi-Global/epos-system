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
import { Head } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";


const options = ref([
    { label: "Vegan", value: "Vegan" },
    { label: "Vegetarian", value: "Vegetarian" },
    { label: "Halal", value: "Halal" },
    { label: "Kosher", value: "Kosher" },
    { label: "Organic", value: "Organic" },
    { label: "Locally Sourced", value: "Locally Sourced" },
    { label: "Fairtrade", value: "Fairtrade" },
    { label: "Spicy", value: "Spicy" },
    { label: "Free-From Nuts", value: "Free-From Nuts" },
    { label: "Contains Soy", value: "Contains Soy" },
    { label: "Free-From Egg", value: "Free-From Egg" },
    { label: "Sugar-Free", value: "Sugar-Free" },
    { label: "Ethically Sourced", value: "Ethically Sourced" },
    { label: "Red Tractor Certified", value: "Red Tractor Certified" },
    { label: "Scottish Produce", value: "Scottish Produce" },
    { label: "Welsh Lamb", value: "Welsh Lamb" },
]);



const selected = ref([]);
const commonTags = ref([]);
const filterText = ref("");

const isEditing = ref(false);
const editingRow = ref(null);
const customTag = ref("");

const q = ref("");

const resetForm = () => {
    customTag.value = "";
    commonTags.value = [];
    formErrors.value = {};
};
const filteredTags = computed(() => {
    const searchTerm = q.value.trim().toLowerCase();
    if (!searchTerm) return tags.value;

    return tags.value.filter((tag) =>
        tag.name.toLowerCase().includes(searchTerm)
    );
});

const openAdd = () => {
    isEditing.value = false;
    commonTags.value = [];
    filterText.value = "";
};

const availableOptions = computed(() => {
    return options.value.filter(
        (option) =>
            !tags.value.some(
                (tag) => tag.name.toLowerCase() === option.value.toLowerCase()
            )
    );
});
const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    customTag.value = row.name;
};
const deleteTag = async (row) => {
    try {
        await axios.delete(`/tags/${row.id}`);
        tags.value = tags.value.filter((t) => t.id !== row.id);
        toast.success("Tag deleted successfully");
        await fetchTags();
    } catch (e) {
        toast.error("Delete failed");
        console.error(e);
    }
};

const runQuery = async (payload) => {
    if (payload.action === "create") {
        return axios.post("/tags", { tags: payload.added });
    }
    if (payload.action === "update") {
        return axios.put(`/tags/${payload.row.id}`, { name: payload.row.name });
    }
    if (payload.action === "delete") {
        return axios.delete(`/tags/${payload.row.id}`);
    }
};
const isSubmitting = ref(false);
const onSubmit = async () => {
    const tagName = customTag.value.trim();

    if (!tagName) {
        toast.error("Please fill out the field, can't save an empty field.");
        formErrors.value = { customTag: ["Please fill out the field, can't save an empty field"] };
        return;
    }

    try {
        isSubmitting.value = true;

        if (isEditing.value) {
            const { data } = await axios.put(`/tags/${editingRow.value.id}`, { name: tagName });
            const idx = tags.value.findIndex(t => t.id === editingRow.value.id);
            if (idx !== -1) tags.value[idx] = data;

            toast.success("Tag updated successfully");
        } else {
            const exists = tags.value.some(t => t.name.toLowerCase() === tagName.toLowerCase());
            if (exists) {
                const msg = `Tag "${tagName}" already exists.`;
                toast.error(msg);
                formErrors.value = { customTag: [msg] };
                return;
            }
            const { data } = await axios.post("/tags", { tags: [{ name: tagName }] });
            const createdTag = Array.isArray(data) ? data[0] : data;
            tags.value.push(createdTag);

            toast.success("Tag added successfully");
        }
        resetForm();
        closeModal("modalTagForm");
        await fetchTags();

    } catch (e) {
        if (e.response?.data?.errors) {
            formErrors.value = {};
            Object.entries(e.response.data.errors).forEach(([field, msgs]) => {
                msgs.forEach(m => toast.error(m));
                formErrors.value = { customTag: msgs };
            });
        } else {
            toast.error("Operation failed ❌");
            console.error(e);
        }
    } finally {
        isSubmitting.value = false;
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

// show Index page
const tags = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
    links: []
});
const loading = ref(false);
const formErrors = ref({});

const fetchTags = async (page = null) => {
    loading.value = true;
    try {
        const { data } = await axios.get("/tags", {
            params: {
                q: q.value,
                page: page || pagination.value.current_page,
                per_page: pagination.value.per_page
            },
        });
        tags.value = data.data || [];
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            per_page: data.per_page,
            total: data.total,
            from: data.from,
            to: data.to,
            links: data.links
        };

        await nextTick();
        window.feather?.replace();
    } catch (err) {
        console.error("Failed to fetch tags", err);
        toast.error("Failed to load tags");
    } finally {
        loading.value = false;
    }
};

const handlePageChange = (url) => {
    if (!url) return;
    const urlParams = new URLSearchParams(url.split('?')[1]);
    const page = urlParams.get('page');

    if (page) {
        fetchTags(parseInt(page));
    }
};

const fetchAllTagsForExport = async () => {
    try {
        loading.value = true;
        
        const res = await axios.get("/tags", {
            params: {
                q: q.value.trim(),
                per_page: 10000, // Fetch all at once
                page: 1
            }
        });
        
        console.log('📦 Export data received:', {
            total: res.data.total,
            items: res.data.data.length
        });
        
        return res.data.data || [];
    } catch (err) {
        console.error('❌ Error fetching export data:', err);
        toast.error("Failed to load data for export");
        return [];
    } finally {
        loading.value = false;
    }
};

// ✅ UPDATED: Main download function
const onDownload = async (type) => {
    try {
        loading.value = true;
        toast.info("Preparing export data...", { autoClose: 1500 });
        
        // ✅ Fetch ALL data (not just current page)
        const allData = await fetchAllTagsForExport();

        if (!allData || allData.length === 0) {
            toast.error("No tags found to download");
            loading.value = false;
            return;
        }

        console.log(`📥 Exporting ${allData.length} tags as ${type.toUpperCase()}`);

        // Export based on type
        if (type === "pdf") {
            downloadPDF(allData);
        } else if (type === "excel") {
            downloadExcel(allData);
        } else if (type === "csv") {
            downloadCSV(allData);
        } else {
            toast.error("Invalid download type");
        }
        
    } catch (error) {
        console.error("Download failed:", error);
        toast.error(`Download failed: ${error.message}`);
    } finally {
        loading.value = false;
    }
};

// ✅ UPDATED CSV Download
const downloadCSV = (data) => {
    try {
        const headers = ["Name"];
        
        const rows = data.map((tag) => {
            const escapeCSV = (str) => {
                if (str === null || str === undefined) return '""';
                str = String(str).replace(/"/g, '""');
                return `"${str}"`;
            };
            
            return [escapeCSV(tag.name)];
        });

        const csvContent = [
            headers.join(","),
            ...rows.map((r) => r.join(",")),
        ].join("\n");

        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute(
            "download",
            `tags_${new Date().toISOString().split("T")[0]}.csv`
        );
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        toast.success(`CSV downloaded successfully (${data.length} tags)`, { autoClose: 2500 });
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

// ✅ UPDATED PDF Download
const downloadPDF = (data) => {
    try {
        const doc = new jsPDF("p", "mm", "a4");
        
        // Title
        doc.setFont("helvetica", "bold");
        doc.setFontSize(18);
        doc.text("Tags Report", 80, 20);

        // Metadata
        doc.setFont("helvetica", "normal");
        doc.setFontSize(10);
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 30);
        doc.text(`Total Tags: ${data.length}`, 14, 36);

        // Table Columns
        const headers = ["Name"];
        
        // Table Rows
        const rows = data.map((tag) => [tag.name || ""]);

        // Create Styled Table
        autoTable(doc, {
            head: [headers],
            body: rows,
            startY: 45,
            styles: {
                fontSize: 10,
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

        const fileName = `tags_${new Date().toISOString().split("T")[0]}.pdf`;
        doc.save(fileName);

        toast.success(`PDF downloaded successfully (${data.length} tags)`, { autoClose: 2500 });
    } catch (error) {
        console.error("PDF generation error:", error);
        toast.error(`PDF generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};

// ✅ UPDATED Excel Download
const downloadExcel = (data) => {
    try {
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        const worksheetData = data.map((tag) => ({
            Name: tag.name || "",
        }));

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        worksheet["!cols"] = [{ wch: 25 }];

        XLSX.utils.book_append_sheet(workbook, worksheet, "Tags");

        // Add metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Tags Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Save file
        const fileName = `tags_${new Date().toISOString().split("T")[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

        toast.success(`Excel file downloaded successfully (${data.length} tags)`, { autoClose: 2500 });
    } catch (error) {
        console.error("Excel generation error:", error);
        toast.error(`Excel generation failed: ${error.message}`, {
            autoClose: 5000,
        });
    }
};


onMounted(async () => {
    await fetchTags();
    window.feather?.replace();
});


watch(customTag, (newVal) => {
    if (newVal && formErrors.value.customTag) {
        delete formErrors.value.customTag;
    }
});

let searchTimeout = null;
watch(q, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchTags(1);
    }, 500);
});


watch(commonTags, (newVal) => {
    if (newVal.length > 0 && formErrors.value.tags) {
        delete formErrors.value.tags;
    }
});


const handleImport = (data) => {
    if (!data || data.length <= 1) {
        toast.error("The file is empty", { autoClose: 3000 });
        return;
    }

    const headers = data[0];
    const rows = data.slice(1);
    const tagsToImport = rows.map((row) => ({
        name: row[0]?.trim() || "",
    }));

    axios
        .post("/api/tags/import", { tags: tagsToImport })
        .then(() => {
            toast.success("Tags imported successfully");
            const importModal = document.querySelector('.modal.show');
            if (importModal) {
                const bsModal = bootstrap.Modal.getInstance(importModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }

            // ✅ Force remove any lingering backdrops
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 100);
            fetchTags();
        })
        .catch((err) => {
            if (err?.response?.status === 422 && err.response.data?.errors) {
                formErrors.value = err.response.data.errors;
                toast.error("Validation failed — check for duplicates or missing data", {
                    autoClose: 3000,
                });
            } else {
                toast.error("Import failed. Please try again.", { autoClose: 3000 });
            }

            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 300);
        });
};



</script>

<template>

    <Head title="Tag" />
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Tags</h4>
                <div class="d-flex gap-2">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input v-model="q" class="form-control search-input" placeholder="Search" />
                    </div>

                    <button data-bs-toggle="modal" data-bs-target="#modalTagForm" @click="
                        () => {
                            openAdd();
                            resetForm();
                            formErrors = {};
                        }
                    " class="d-flex align-items-center gap-1 btn-sm px-4 py-2 rounded-pill btn btn-primary text-white">
                        <Plus class="w-4 h-4" /> Add Tag
                    </button>
                    <ImportFile label="Import" :sampleHeaders="['Name']" :sampleData="[
                        ['Vegan'],
                        ['Gluten-Free'],
                        ['Organic']
                    ]" @on-import="handleImport" />

                    <!-- Download all -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm rounded-pill py-2 px-4 dropdown-toggle"
                            data-bs-toggle="dropdown">
                            Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 ">
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">Export as
                                    PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">Export
                                    as Excel</a>
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
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Tag Name</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="loading">
                            <td colspan="3" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Loading tags...</p>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <template v-else>
                            <tr v-for="(r, i) in filteredTags" :key="r.id">
                                <td>{{ pagination.from + i }}</td>
                                <td class="fw-semibold">{{ r.name }}</td>

                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-3">
                                        <button data-bs-toggle="modal" data-bs-target="#modalTagForm"
                                            @click="() => { openEdit(r); formErrors = {}; }" title="Edit"
                                            class="p-2 rounded-full text-blue-600 hover:bg-blue-100">
                                            <Pencil class="w-4 h-4" />
                                        </button>

                                        <ConfirmModal :title="'Confirm Delete'"
                                            :message="`Are you sure you want to delete ${r.name}?`"
                                            :showDeleteButton="true" @confirm="() => { deleteTag(r); }"
                                            @cancel="() => { }" />
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredTags.length === 0">
                                <td colspan="3" class="text-center text-muted py-4">
                                    {{ q.trim() ? "No tags found matching your search." : "No tags found." }}
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div v-if="!loading && pagination.last_page > 1"
                class="mt-4 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                </div>

                <Pagination :pagination="pagination.links" :isApiDriven="true" @page-changed="handlePageChange" />
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="modalTagForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isEditing ? "Edit Tag" : "Add Tag" }}
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
                    <div v-if="isEditing">
                        <label class="form-label">Tag Name</label>
                        <input v-model="customTag" class="form-control" placeholder="e.g., Vegan"
                            :class="{ 'is-invalid': formErrors.customTag }" />
                        <span class="text-danger" v-if="formErrors.customTag">{{
                            formErrors.customTag[0]
                            }}</span>
                    </div>

                    <div v-else>
                        <label class="form-label">Tag Name</label>
                        <input v-model="customTag" class="form-control" placeholder="Enter tag name"
                            :class="{ 'is-invalid': formErrors.customTag }" />
                        <span class="text-danger" v-if="formErrors.customTag">{{ formErrors.customTag[0] }}</span>
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

:global(.dark .form-control:focus) {
    border-color: #fff !important;
}


.btn-primary {
    background: var(--brand);
    border-color: var(--brand);
}

.search-wrap {
    position: relative;
    width: clamp(220px, 28vw, 360px);
}


.dark .text-danger {
    color: #dc3545 !important;
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


/* ========================  MultiSelect Styling   ============================= */
:deep(.p-multiselect-header) {
    background-color: white !important;
    color: black !important;

}

:deep(.p-multiselect-label) {
    color: #000 !important;
}

.dark .p-multiselect {
    background-color: #212121 !important;
}


.dark .p-multiselect-list {
    background-color: #181818 !important;
    color: #fff !important;
}

.dark .border-top {
    background-color: #121212 !important;
    color: #fff !important;
}

.dark .p-multiselect-empty-message {
    background-color: #121212 !important;
    color: #fff !important;
}

.dark .p-multiselect-list {
    background-color: #181818 !important;
}


:deep(.p-select .p-component .p-inputwrapper) {
    background: #fff !important;
    color: #000 !important;
    border-bottom: 1px solid #ddd;
}

/* Options list container */
:deep(.p-multiselect-list) {
    background: #fff !important;
}

/* Each option */
:deep(.p-multiselect-option) {
    background: #fff !important;
    color: #000 !important;
}

/* Hover/selected option */
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

/* Checkbox box in dropdown */
:deep(.p-multiselect-overlay .p-checkbox-box) {
    background: #fff !important;
    border: 1px solid #ccc !important;
}

/* Search filter input */
:deep(.p-multiselect-filter) {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
}

/* Optional: adjust filter container */
:deep(.p-multiselect-filter-container) {
    background: #fff !important;
}

/* Selected chip inside the multiselect */
:deep(.p-multiselect-chip) {
    background: #e9ecef !important;
    color: #000 !important;
    border-radius: 12px !important;
    border: 1px solid #ccc !important;
    padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:deep(.p-multiselect-chip .p-chip-remove-icon) {
    color: #555 !important;
}

:deep(.p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #dc3545 !important;
    /* red on hover */
}

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

:deep(.p-multiselect-overlay) {
    background-color: #fff !important;
    color: #000 !important;
}

.dark .header {
    background-color: #212121 !important;
}

.dark .p-inputtext {
    background-color: #181818 !important;
    color: #fff !important;
}

.side-link {
    border-radius: 55%;
    background-color: #fff !important;
}

.dark .side-link {
    border-radius: 55%;
    background-color: #181818 !important;
}

/* ======================== Dark Mode MultiSelect ============================= */

:global(.dark .p-multiselect-header) {
    background-color: #121212 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect) {
    background-color: #121212 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect-label) {
    color: #fff !important;
}

:global(.dark .p-select .p-component .p-inputwrapper) {
    background: #121212 !important;
    color: #fff !important;
    border-bottom: 1px solid #555 !important;
}

/* Options list container */
:global(.dark .p-multiselect-list) {
    background: #181818 !important;
}

/* Each option */
:global(.dark .p-multiselect-option) {
    background: #121212 !important;
    color: #fff !important;
}

/* Hover/selected option */
:global(.dark .p-multiselect-option.p-highlight),
:global(.dark .p-multiselect-option:hover) {
    background: #181818 !important;
    color: #fff !important;
}

:global(.dark .p-multiselect),
:global(.dark .p-multiselect-panel),
:global(.dark .p-multiselect-token) {
    background: #121212 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Checkbox box in dropdown */
:global(.dark .p-multiselect-overlay .p-checkbox-box) {
    background: #121212 !important;
    border: 1px solid #555 !important;
}

:global(.dark .p-multiselect-empty-message) {
    color: #fff !important;
}


/* Search filter input */
:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}

:global(.dark .p-multiselect-filter) {
    background-color: #212121 !important;
}

/* Optional: adjust filter container */
:global(.dark .p-multiselect-filter-container) {
    background: #121212 !important;
}

/* Selected chip inside the multiselect */
:global(.dark .p-multiselect-chip) {
    background: #111 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
    border-radius: 12px !important;
    padding: 0.25rem 0.5rem !important;
}

/* Chip remove (x) icon */
:global(.dark .p-multiselect-chip .p-chip-remove-icon) {
    color: #ccc !important;
}

:global(.dark .p-multiselect-chip .p-chip-remove-icon:hover) {
    color: #f87171 !important;
    /* lighter red */
}

/* ==================== Dark Mode Select Styling ====================== */
:global(.dark .p-select) {
    background-color: #121212 !important;
    color: #fff !important;
    border-color: #555 !important;
}

/* Options container */
:global(.dark .p-select-list-container) {
    background-color: #121212 !important;
    color: #fff !important;
}

/* Each option */
:global(.dark .p-select-option) {
    background-color: transparent !important;
    color: #fff !important;
}

/* Hovered option */
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
