<script setup>
import { ref, computed, onMounted, nextTick, watch } from "vue";
import { toast } from "vue3-toastify";
import MultiSelect from "primevue/multiselect";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import axios from "axios";
import { Pencil, Plus } from "lucide-vue-next";
import ImportFile from "@/Components/importFile.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";


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
const commonTags = ref([]); // array of values
const filterText = ref(""); // Fixed: Added missing filterText ref

const isEditing = ref(false);
const editingRow = ref(null);
const customTag = ref("");

const q = ref("");

const resetForm = () => {
    customTag.value = "";
    commonTags.value = [];
    formErrors.value = {};
};

// Fixed: Create filtered computed property that works with tags array
const filteredTags = computed(() => {
    const searchTerm = q.value.trim().toLowerCase();
    return searchTerm
        ? tags.value.filter((tag) =>
            tag.name.toLowerCase().includes(searchTerm)
        )
        : tags.value;
});

const selectAll = () => (commonTags.value = availableOptions.value.map(o => o.value));


const addCustom = () => {
    const name = (filterText.value || "").trim();
    if (!name) return;
    if (
        !options.value.some((o) => o.label.toLowerCase() === name.toLowerCase())
    ) {
        options.value.push({ label: name, value: name });
    }
    if (!commonTags.value.includes(name))
        commonTags.value = [...commonTags.value, name];
    filterText.value = "";
};

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

// const removeRow = (row) => (rows.value = rows.value.filter((r) => r !== row));

const deleteTag = async (row) => {
    try {
        await axios.delete(`/tags/${row.id}`);
        tags.value = tags.value.filter((t) => t.id !== row.id);
        toast.success("Tag deleted successfully");
    } catch (e) {
        toast.error("Delete failed ❌");
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
    if (isEditing.value) {
        if (!customTag.value.trim()) {
            toast.error("Please fill out the field can't save an empty field.");
            formErrors.value = {
                customTag: [
                    "Please fill out the field can't save an empty field",
                ],
            };
            return;
        }
        try {
            isSubmitting.value = true;
            const { data } = await axios.put(`/tags/${editingRow.value.id}`, {
                name: customTag.value.trim(),
            });

            const idx = tags.value.findIndex(
                (t) => t.id === editingRow.value.id
            );
            if (idx !== -1) tags.value[idx] = data;

            toast.success("Tag updated Successfully");
            await fetchTags();
            // Hide the modal after successful update
            resetForm();
            closeModal("modalTagForm");
        } catch (e) {
            if (e.response?.data?.errors) {
                // Reset errors object
                formErrors.value = {};
                // Loop through backend errors
                Object.entries(e.response.data.errors).forEach(
                    ([field, msgs]) => {
                        // Show toast(s)
                        msgs.forEach((m) => toast.error(m));

                        // Attach to formErrors so it shows below inputs
                        formErrors.value = { customTag: msgs };
                    }
                );
            } else {
                toast.error("Update failed");
            }
        }
        finally {
            isSubmitting.value = false;
        }
    } else {
        if (commonTags.value.length === 0) {
            formErrors.value = { tags: ["Please select at least one Tag"] };
            toast.error("Please select at least one Tag", {
                autoClose: 3000,
            });
            return;
        }
        // create
        const newTags = commonTags.value
            .filter((v) => !tags.value.some((t) => t.name === v))
            .map((v) => ({ name: v }));

        // Filter new tags and detect duplicates
        const existingTags = commonTags.value.filter((v) =>
            tags.value.some((t) => t.name === v)
        );

        if (newTags.length === 0) {
            // Show which tags already exist
            const msg = `Tag${existingTags.length > 1 ? "s" : ""
                } already exist: ${existingTags.join(", ")}`;

            toast.error(msg);
            formErrors.value = { tags: [msg] };

            // closeModal("modalTagForm");
            return;
        }

        try {
            isSubmitting.value = true;
            const response = await axios.post("/tags", { tags: newTags });

            // If backend returns array directly
            const createdTags = response.data?.tags ?? response.data;

            if (Array.isArray(createdTags) && createdTags.length) {
                tags.value = [...tags.value, ...createdTags];
            }

            toast.success("Tags added successfully");

            resetForm();
            closeModal("modalTagForm");

            // Hide the modal after successful creation

            await fetchTags();
        } catch (e) {
            // Only show create failed if there is a real error
            if (e.response?.data?.errors) {
                Object.values(e.response.data.errors).forEach((msgs) =>
                    msgs.forEach((m) => toast.error(m))
                );
            } else {
                console.error(e); // log actual error for debugging
                toast.error("Create failed");
            }
        }
        finally {
            isSubmitting.value = false;
        }
    }
};

// Function to properly hide modal and clean up backdrop
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
const page = ref(1);
const perPage = ref(15);
const loading = ref(false);
const formErrors = ref({});

const fetchTags = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/tags", {
            params: { q: q.value, page: page.value, per_page: perPage.value },
        });

        tags.value = data?.data ?? data?.tags?.data ?? data ?? [];

        // wait for DOM update before replacing icons
        await nextTick();
        window.feather?.replace();
    } catch (err) {
        console.error("Failed to fetch tags", err);
    } finally {
        loading.value = false;
    }
};

const onDownload = (type) => {
    if (!tags.value || tags.value.length === 0) {
        toast.error("No Tags data to download");
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : tags.value;

    if (dataToExport.length === 0) {
        toast.error("No Tags found to download");
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
        const headers = ["name"];

        // Build CSV rows
        const rows = data.map((s) => [
            `"${s.name || ""}"`,
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
            `tags_${new Date().toISOString().split("T")[0]}.csv`
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

        doc.setFontSize(20);
        doc.setFont("helvetica", "bold");
        doc.text("Tags Report", 14, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Tags: ${data.length}`, 14, 34);

        const tableColumns = ["Name", "Created At", "Created By"];
        const tableRows = data.map((s) => [
            s.name || "",
            s.created_at || "",
            s.updated_at || "",
        ]);

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
        const fileName = `Tags_${new Date().toISOString().split("T")[0]}.pdf`;
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
        // Check if XLSX is available
        if (typeof XLSX === "undefined") {
            throw new Error("XLSX library is not loaded");
        }

        // Prepare worksheet data
        const worksheetData = data.map((tag) => ({
            Name: tag.name || "",
        }));

        // Create workbook and worksheet
        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.json_to_sheet(worksheetData);

        // Set column widths
        const colWidths = [
            { wch: 20 }, // Name
            { wch: 25 }, // Email
            { wch: 15 }, // Phone
            { wch: 30 }, // Address
            { wch: 25 }, // Preferred Items
            { wch: 10 }, // ID
        ];
        worksheet["!cols"] = colWidths;

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, "Tags");

        // Add metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Tags Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Generate file name
        const fileName = `Tags_${new Date().toISOString().split("T")[0]}.xlsx`;

        // Save the file
        XLSX.writeFile(workbook, fileName);

        toast.success("Excel file downloaded successfully", {
            autoClose: 2500,
        });
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

// 🟢 Watch customTag input
watch(customTag, (newVal) => {
    if (newVal && formErrors.value.customTag) {
        // Clear only this field’s error
        delete formErrors.value.customTag;
    }
});

// 🟢 Watch commonTags multi-select
watch(commonTags, (newVal) => {
    if (newVal.length > 0 && formErrors.value.tags) {
        // Clear only tags error
        delete formErrors.value.tags;
    }
});


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

    const tagsToImport = rows.map((row) => {
        return {
            name: row[0] || "",
        };
    });

    axios
        .post("/api/tags/import", { tags: tagsToImport })
        .then(() => {
            toast.success("Tags imported successfully");
            fetchTags();
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
                    <!-- <ImportFile label="Import" @on-import="handleImport" /> -->
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
                        <!-- Fixed: Use filteredTags instead of tags for proper filtering -->
                        <tr v-for="(r, i) in filteredTags" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>

                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-3">
                                    <button data-bs-toggle="modal" data-bs-target="#modalTagForm" @click="
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
                                                deleteTag(r);
                                            }
                                        " @cancel="() => { }" />
                                </div>
                            </td>
                        </tr>

                        <!-- Fixed: Check filteredTags length instead of tags -->
                        <tr v-if="filteredTags.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                {{
                                    q.trim()
                                        ? "No tags found matching your search."
                                        : "No tags found."
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                        <MultiSelect v-model="commonTags" :options="availableOptions" optionLabel="label"
                            optionValue="value" :multiple="true" showClear :filter="true" display="chip"
                            placeholder="Choose common  tags or add new one" class="w-100 select" appendTo="self"
                            @filter="(e) => (filterText = e.value || '')" :class="{ 'is-invalid': formErrors.tags }"
                            :invalid="formErrors.tags?.length">
                            <template #header>
                                <div class="w-100 d-flex header justify-content-end">
                                    <button type="button" class="btn btn-sm btn-link text-primary"
                                        @click.stop="selectAll">
                                        Select All
                                    </button>
                                </div>
                            </template>

                            <template #footer>
                                <div v-if="filterText?.trim()"
                                    class="p-2 border-top d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Not found in the list? Add it as a
                                        custom tag</small>
                                    <button type="button" class="btn btn-primary rounded-pill" @click="addCustom">
                                        Add "{{ filterText.trim() }}"
                                    </button>
                                </div>
                            </template>
                        </MultiSelect>
                        <span class="text-danger" v-if="formErrors.tags">{{
                            formErrors.tags[0]
                            }}</span>
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

.side-link{
  border-radius: 55%;
  background-color: #fff !important;
}

.dark .side-link{
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

:global(.dark .p-multiselect-empty-message){
    color: #fff !important;
}


/* Search filter input */
:global(.dark .p-multiselect-filter) {
    background: #181818 !important;
    color: #fff !important;
    border: 1px solid #555 !important;
}

:global(.dark .p-multiselect-filter){
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
