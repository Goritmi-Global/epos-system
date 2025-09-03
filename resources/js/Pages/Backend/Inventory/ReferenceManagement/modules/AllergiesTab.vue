<script setup>
import { ref, computed, onMounted } from "vue";
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";
import { toast } from "vue3-toastify";
import axios from 'axios'
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from 'xlsx';

const viewingRow = ref(null);
const allergies = ref([]);
const loading = ref(false);

const fetchAllergies = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get("/allergies");
        allergies.value = data.data ?? data;
    } catch (err) {
        console.error("Error fetching allergies:", err);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchAllergies);

const options = ref([
    { label: "Crustaceans", value: "Crustaceans" },
    { label: "Eggs", value: "Eggs" },
    { label: "Fish", value: "Fish" },
    { label: "Lupin", value: "Lupin" },
    { label: "Milk", value: "Milk" },
    { label: "Molluscs", value: "Molluscs" },
    { label: "Mustard", value: "Mustard" },
    { label: "Peanuts", value: "Peanuts" },
    { label: "Sesame seeds", value: "Sesame seeds" },
    { label: "Soybeans", value: "Soybeans" },
    {
        label: "Sulphur dioxide / sulphites",
        value: "Sulphur dioxide / sulphites",
    },
    { label: "Tree nuts", value: "Tree nuts" },
]);

const selected = ref([]);
const filterText = ref("");
const isEditing = ref(false);
const editingRow = ref(null);
const editName = ref("");

// Client-side filtering
const filtered = computed(() => {
    const searchTerm = filterText.value.trim().toLowerCase();
    if (!searchTerm) {
        return allergies.value;
    }
    return allergies.value.filter((allergy) => 
        allergy.name.toLowerCase().includes(searchTerm)
    );
});

const selectAll = () => (selected.value = options.value.map((o) => o.value));
const removeAll = () => (selected.value = []);

// Track the current filter value from MultiSelect
const currentFilterValue = ref("");

const addCustom = () => {
    const name = currentFilterValue.value?.trim();
    if (!name) {
        toast.warning("⚠️ Please type in the search box first, then click 'Add Custom'", { autoClose: 3000 });
        return;
    }
    
    // Check if already exists in options
    if (!options.value.some((o) => o.label.toLowerCase() === name.toLowerCase())) {
        options.value.push({ label: name, value: name });
    }
    
    // Check if already selected
    if (!selected.value.includes(name)) {
        selected.value = [...selected.value, name];
    }
    
    currentFilterValue.value = "";
};

const availableOptions = computed(() => {
    return options.value.filter(option => 
        !allergies.value.some(allergie => allergie.name.toLowerCase() === option.value.toLowerCase())
    );
});

const openAdd = () => {
    isEditing.value = false;
    selected.value = [];
    filterText.value = "";
    currentFilterValue.value = "";
};

const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    editName.value = row.name;
};


const handleApiError = (error, defaultMessage = "Operation failed") => {
    if (error.response?.status === 422) {
        // Validation errors
        const errors = error.response.data.errors;
        if (errors) {
            
            Object.keys(errors).forEach(key => {
                errors[key].forEach(message => {
                    if (message.includes('already exists')) {
                        toast.error(`❌ ${message}`, { autoClose: 4000 });
                    } else {
                        toast.error(`❌ ${message}`, { autoClose: 3000 });
                    }
                });
            });
            return;
        }
    }
    
    const message = error.response?.data?.message || defaultMessage;
    toast.error(`❌ ${message}`, { autoClose: 3000 });
};

const runQuery = async (payload) => {
    try {
        console.log(payload);
        if (payload.action === "create") {
            // Pre-check for existing allergies on frontend (optional optimization)
            const existingNames = new Set(allergies.value.map(a => a.name.toLowerCase()));
            const duplicates = payload.added.filter(row => existingNames.has(row.name.toLowerCase()));
            
            if (duplicates.length > 0) {
                duplicates.forEach(dup => {
                    toast.error(`❌ Allergy "${dup.name}" already exists`, { autoClose: 4000 });
                });
                return; // Don't proceed with API call
            }

            await axios.post("/allergies", { names: payload.added.map(row => row.name) });
            await fetchAllergies();
            toast.success("✅ Allergy(s) created successfully", { autoClose: 2000 });

        } else if (payload.action === "update") {
            // Check if name already exists (excluding current record)
            const existingAllergy = allergies.value.find(a => 
                a.name.toLowerCase() === payload.row.name.toLowerCase() && a.id !== payload.row.id
            );
            
            if (existingAllergy) {
                toast.error(`❌ Allergy "${payload.row.name}" already exists`, { autoClose: 4000 });
                return;
            }

            await axios.put(`/allergies/${payload.row.id}`, {
                name: payload.row.name,
            });
            await fetchAllergies();
            toast.success("✅ Allergy updated successfully", { autoClose: 2000 });

        } else if (payload.action === "delete") {
            await axios.delete(`/allergies/${payload.row.id}`);
            await fetchAllergies();
            toast.success("✅ Allergy deleted successfully", { autoClose: 2000 });
        }
    } catch (err) {
        console.error("[Allergies] query ERROR:", err.response?.data || err);
        handleApiError(err, "Operation failed");
        throw err;
    }
};

const onSubmit = async () => {
    try {
        if (isEditing.value) {
            if (!editName.value?.trim()) {
                toast.error("❌ Allergy name is required", { autoClose: 3000 });
                return;
            }

            const updatedRow = { ...editingRow.value, name: editName.value.trim() };
            await runQuery({ action: "update", row: updatedRow });

            // Reset editing state
            isEditing.value = false;
            editingRow.value = null;
            editName.value = "";
        } else {
            // Adding new allergies
            if (selected.value.length === 0) {
                toast.error("❌ Please select at least one allergy", { autoClose: 3000 });
                return;
            }

            const existing = new Set(allergies.value.map((a) => a.name.toLowerCase()));
            const added = [];
            const duplicates = [];

            selected.value.forEach((name) => {
                if (existing.has(name.toLowerCase())) {
                    duplicates.push(name);
                } else {
                    added.push({ name });
                }
            });

            // Show duplicates warning
            if (duplicates.length > 0) {
                duplicates.forEach(dup => {
                    toast.warning(`⚠️ "${dup}" already exists, skipping...`, { autoClose: 4000 });
                });
            }

            // Add only new allergies
            if (added.length > 0) {
                await runQuery({ action: "create", added });
            } 
            selected.value = [];
        }
    } catch (err) {
        console.error("[Allergies] Submit ERROR:", err.response?.data || err);
    }
};

const onEdit = (row) => {
    isEditing.value = true;
    editingRow.value = { ...row };
    editName.value = row.name;
    const modal = new bootstrap.Modal(document.getElementById("modalAllergyForm"));
    modal.show();
};

const onRemove = async (row) => {
    if (!confirm(`Delete allergy "${row.name}"?`)) return;
    await runQuery({ action: "delete", row });
};

const q = ref("");

const onView = (row) => {
    viewingRow.value = row;
    const modal = new bootstrap.Modal(document.getElementById("modalAllergyView"));
    modal.show();
};

const onDownload = (type) => {
    if (!allergies.value || allergies.value.length === 0) {
        toast.error("No Allergies data to download", { autoClose: 3000 });
        return;
    }

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : allergies.value;

    if (dataToExport.length === 0) {
        toast.error("No Allergies found to download", { autoClose: 3000 });
        return;
    }

    try {
        if (type === 'pdf') {
            downloadPDF(dataToExport);
        } else if (type === 'excel') {
            downloadExcel(dataToExport);
        }
        else if(type === 'csv'){
            downloadCSV(dataToExport);
        }
        else {
            toast.error("Invalid download type", { autoClose: 3000 });
        }
    } catch (error) {
        console.error('Download failed:', error);
        toast.error(`Download failed: ${error.message}`, { autoClose: 3000 });
    }
};

const downloadCSV = (data) => {
    try {
        // Define headers
        const headers = ["Name", "Created At", "Updated At"];

        // Build CSV rows
        const rows = data.map(s => [
            `"${s.name || ""}"`,
            `"${s.created_at || ""}"`,
            `"${s.updated_at || ""}"`,
            
        ]);

        // Combine into CSV string
        const csvContent = [
            headers.join(","), // header row
            ...rows.map(r => r.join(",")) // data rows
        ].join("\n");

        // Create blob
        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);

        // Create download link
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `allergies_${new Date().toISOString().split("T")[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        toast.success("CSV downloaded successfully ✅", { autoClose: 2500 });
    } catch (error) {
        console.error("CSV generation error:", error);
        toast.error(`CSV generation failed: ${error.message}`, { autoClose: 5000 });
    }
};


const downloadPDF = (data) => {
  try {
    const doc = new jsPDF("p", "mm", "a4"); // portrait, millimeters, A4

    // 🌟 Title
    doc.setFontSize(20);
    doc.setFont("helvetica", "bold");
    doc.text("Allergies Report", 14, 20);

    // 🗓️ Metadata
    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    const currentDate = new Date().toLocaleString();
    doc.text(`Generated on: ${currentDate}`, 14, 28);
    doc.text(`Total Tags: ${data.length}`, 14, 34);

    // 📋 Table Data
    const tableColumns = ["Name", "Created At", "Updated At"];
    const tableRows = data.map((s) => [
      s.name || "",
      s.created_at || "",
      s.updated_at || ""
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
                lineWidth: 0.1
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
    const fileName = `Allergies_${new Date().toISOString().split("T")[0]}.pdf`;
    doc.save(fileName);

    toast.success("PDF downloaded successfully ✅", { autoClose: 2500 });
  } catch (error) {
    console.error("PDF generation error:", error);
    toast.error(`PDF generation failed: ${error.message}`, { autoClose: 5000 });
  }
};


const downloadExcel = (data) => {
    try {
        // Check if XLSX is available
        if (typeof XLSX === 'undefined') {
            throw new Error('XLSX library is not loaded');
        }
        
        // Prepare worksheet data
        const worksheetData = data.map(allergie => ({
            'Name': allergie.name || '',
            
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
            { wch: 10 }  // ID
        ];
        worksheet['!cols'] = colWidths;

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Allergies');

        // Add metadata sheet
        const metaData = [
            { Info: 'Generated On', Value: new Date().toLocaleString() },
            { Info: 'Total Records', Value: data.length },
            { Info: 'Exported By', Value: 'Allergies Management System' }
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, 'Report Info');

        // Generate file name
        const fileName = `Allergies_${new Date().toISOString().split('T')[0]}.xlsx`;
        
        // Save the file
        XLSX.writeFile(workbook, fileName);
        
        toast.success("Excel file downloaded successfully ✅", { autoClose: 2500 });
        
    } catch (error) {
        console.error('Excel generation error:', error);
        toast.error(`Excel generation failed: ${error.message}`, { autoClose: 5000 });
    }
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
                        <input v-model="filterText" class="form-control search-input" placeholder="Search allergies..." />
                    </div>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#modalAllergyForm" @click="openAdd">
                        Add Allergy
                    </button>
                      <div class="dropdown">
                        <button class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                            data-bs-toggle="dropdown">
                            Download all
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2">
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('pdf')">Download as
                                    PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="javascript:;" @click="onDownload('excel')">Download
                                    as Excel</a>
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
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Allergy</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in filtered" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 fs-5" data-bs-toggle="dropdown"
                                        title="Actions">
                                        ⋮
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden">
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;" @click="onView(r)">
                                               <i data-feather="eye" class="me-2"></i>View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2" href="javascript:;" @click="onEdit(r)">
                                                <i data-feather="edit-2" class="me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider" /></li>
                                        <li>
                                            <a class="dropdown-item py-2 text-danger" href="javascript:;" @click="onRemove(r)">
                                                <i data-feather="trash-2" class="me-2"></i>Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                {{ filterText.trim() ? 'No allergies found matching your search.' : 'No allergies found.' }}
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
                    <button type="button" class="btn btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                </div>
                <div class="modal-body">
                    <div v-if="isEditing">
                        <label class="form-label">Allergy Name</label>
                        <input v-model="editName" class="form-control" placeholder="e.g., Milk" maxlength="100" />
                    </div>
                    <div v-else>
                        <MultiSelect v-model="selected" :options="availableOptions" optionLabel="label" optionValue="value"
                            filter display="chip" placeholder="Choose allergies or type to add custom" class="w-100"
                            appendTo="self" :pt="{ panel: { class: 'pv-overlay-fg' } }"
                            @filter="(e) => (currentFilterValue = e.value || '')">
                            <template #option="{ option }">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-shield-exclamation me-2"></i>
                                    <span>{{ option.label }}</span>
                                </div>
                            </template>
                            <template #header>
                                <div class="font-medium px-3 py-2">Common Allergens</div>
                            </template>
                            <template #footer>
                                <div class="p-3 d-flex justify-content-between">
                                    <Button label="Add Custom" severity="secondary" variant="text" size="small"
                                        icon="pi pi-plus" @click="addCustom" 
                                        :disabled="!currentFilterValue.trim()" />
                                    <div class="d-flex gap-2">
                                        <Button label="Select All" severity="secondary" variant="text" size="small"
                                            icon="pi pi-check" @click="selectAll" />
                                        <Button label="Clear All" severity="danger" variant="text" size="small"
                                            icon="pi pi-times" @click="removeAll" />
                                    </div>
                                </div>
                            </template>
                        </MultiSelect>
                    </div>

                    <button class="btn btn-primary rounded-pill w-100 mt-4" @click="onSubmit" data-bs-dismiss="modal">
                        {{ isEditing ? "Save Changes" : "Add Allergy(s)" }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="modalAllergyView" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Allergy Details</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="viewingRow">
                        <p><strong>ID:</strong> {{ viewingRow.id }}</p>
                        <p><strong>Name:</strong> {{ viewingRow.name }}</p>
                        <p v-if="viewingRow.description"><strong>Description:</strong> {{ viewingRow.description }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
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

.pv-overlay-fg {
    z-index: 2000 !important;
}

:deep(.p-multiselect-panel),
:deep(.p-select-panel),
:deep(.p-dropdown-panel) {
    z-index: 2000 !important;
}

:deep(.p-multiselect) {
    width: 100%;
}

:deep(.p-multiselect-token) {
    margin: 0.15rem;
}
</style>