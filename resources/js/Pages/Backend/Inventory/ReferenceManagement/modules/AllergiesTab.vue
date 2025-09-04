<script setup>
import { ref, computed, onMounted, nextTick, watch } from "vue";
import { toast } from "vue3-toastify";
import MultiSelect from "primevue/multiselect";
import { jsPDF } from "jspdf";
import autoTable from "jspdf-autotable";
import * as XLSX from "xlsx";
import axios from "axios";
import { Pencil, Plus } from "lucide-vue-next";

const commonExistingAllergiesList = ref([
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

const commonAllergies = ref([]); // array of values
const filterText = ref(""); // Fixed: Added missing filterText ref

const isEditing = ref(false);
const editingRow = ref(null);
const customAllergy = ref("");

const q = ref("");

const resetForm = () => {
    customAllergy.value = "";
    commonAllergies.value = [];
    formErrors.value = {};
};

// Fixed: Create filtered computed property that works with allergies array
const filteredAllergys = computed(() => {
    const searchTerm = q.value.trim().toLowerCase();
    return searchTerm
        ? allergies.value.filter((allergy) =>
              allergy.name.toLowerCase().includes(searchTerm)
          )
        : allergies.value;
});

const selectAll = () =>
    (commonAllergies.value = commonExistingAllergiesList.value.map(
        (o) => o.value
    ));

const addCustom = () => {
    const name = (filterText.value || "").trim();
    if (!name) return;
    if (
        !commonExistingAllergiesList.value.some(
            (o) => o.label.toLowerCase() === name.toLowerCase()
        )
    ) {
        commonExistingAllergiesList.value.push({ label: name, value: name });
    }
    if (!commonAllergies.value.includes(name))
        commonAllergies.value = [...commonAllergies.value, name];
    filterText.value = "";
};

const openAdd = () => {
    isEditing.value = false;
    commonAllergies.value = [];
    filterText.value = "";
};

const availableOptions = computed(() => {
    return commonExistingAllergiesList.value.filter(
        (option) =>
            !allergies.value.some(
                (allergy) =>
                    allergy.name.toLowerCase() === option.value.toLowerCase()
            )
    );
});
const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    customAllergy.value = row.name;
};

// const removeRow = (row) => (rows.value = rows.value.filter((r) => r !== row));

const deleteAllergy = async (row) => {
    try {
        await axios.delete(`/allergies/${row.id}`);
        allergies.value = allergies.value.filter((t) => t.id !== row.id);
        toast.success("Allergy deleted");
    } catch (e) {
        toast.error("Delete failed");
    }
};

const onSubmit = async () => {
    if (isEditing.value) {
        if (!customAllergy.value.trim()) {
            toast.error("Please fill out the field can't save an empty field.");
            formErrors.value = {
                customAllergy: [
                    "Please fill out the field can't save an empty field",
                ],
            };
            return;
        }
        try {
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

            toast.success("Allergy updated Successfully");

            await fetchAllergies();

            // Hide the modal after successful update
            resetForm();
            closeModal("modalAllergyForm");
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
                        formErrors.value = { customAllergy: msgs };
                    }
                );
            } else {
                toast.error("Update failed");
            }
        }
    } else {
        if (commonAllergies.value.length === 0) {
            formErrors.value = {
                allergies: ["Please select at least one Allergy Item"],
            };
            toast.error("Please select at least one Allergy");
            return;
        }
        // create
        const newAllergy = commonAllergies.value
            .filter((v) => !allergies.value.some((t) => t.name === v))
            .map((v) => ({ name: v }));

        // Filter new allergies and detect duplicates
        const existingAllergy = commonAllergies.value.filter((v) =>
            allergies.value.some((t) => t.name === v)
        );

        if (newAllergy.length === 0) {
            // Show which allergies already exist
            const msg = `Allergy${
                existingAllergy.length > 1 ? "s" : ""
            } already exist: ${existingAllergy.join(", ")}`;

            toast.error(msg);
            formErrors.value = { allergies: [msg] };

            // closeModal("modalAllergyForm");
            return;
        }

        try {
            const response = await axios.post("/allergies", {
                allergies: newAllergy,
            });

            // If backend returns array directly
            const createdTags = response.data?.allergies ?? response.data;

            if (Array.isArray(createdTags) && createdTags.length) {
                allergies.value = [...allergies.value, ...createdTags];
            }

            toast.success("Allergies added");

            resetForm();
            closeModal("modalAllergyForm");

            // Hide the modal after successful creation

            await fetchAllergies();
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

        // wait for DOM update before replacing icons
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

    // Use filtered data if there's a search query, otherwise use all suppliers
    const dataToExport = q.value.trim() ? filtered.value : allergies.value;

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
        // Define headers
        const headers = ["Name", "Created At", "Created By"];

        // Build CSV rows
        const rows = data.map((s) => [
            `"${s.name || ""}"`,
            `"${s.created_at || ""}"`,
            `"${s.updated_at || ""}"`,
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
        const doc = new jsPDF("p", "mm", "a4");

        doc.setFontSize(20);
        doc.setFont("helvetica", "bold");
        doc.text("Allergies Report", 14, 20);

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        const currentDate = new Date().toLocaleString();
        doc.text(`Generated on: ${currentDate}`, 14, 28);
        doc.text(`Total Allergies: ${data.length}`, 14, 34);

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
        const fileName = `Allergys_${
            new Date().toISOString().split("T")[0]
        }.pdf`;
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
        const worksheetData = data.map((allergy) => ({
            Name: allergy.name || "",
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
        XLSX.utils.book_append_sheet(workbook, worksheet, "Allergies");

        // Add metadata sheet
        const metaData = [
            { Info: "Generated On", Value: new Date().toLocaleString() },
            { Info: "Total Records", Value: data.length },
            { Info: "Exported By", Value: "Allergies Management System" },
        ];
        const metaSheet = XLSX.utils.json_to_sheet(metaData);
        XLSX.utils.book_append_sheet(workbook, metaSheet, "Report Info");

        // Generate file name
        const fileName = `Allergys_${
            new Date().toISOString().split("T")[0]
        }.xlsx`;

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
    await fetchAllergies();
    window.feather?.replace();
});

// 🟢 Watch customAllergy input
watch(customAllergy, (newVal) => {
    if (newVal && formErrors.value.customAllergy) {
        // Clear only this field’s error
        delete formErrors.value.customAllergy;
    }
});

// 🟢 Watch commonAllergies multi-select
watch(commonAllergies, (newVal) => {
    if (newVal.length > 0 && formErrors.value.allergies) {
        // Clear only allergies error
        delete formErrors.value.allergies;
    }
});
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div
                class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
            >
                <h4 class="mb-0">Allergies</h4>
                <div class="d-flex gap-2">
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input
                            v-model="q"
                            class="form-control search-input"
                            placeholder="Search"
                        />
                    </div>

                    <button
                        data-bs-toggle="modal"
                        data-bs-target="#modalAllergyForm"
                        @click="
                            () => {
                                openAdd();
                                resetForm();
                                formErrors = {};
                            }
                        "
                        class="d-flex align-items-center gap-1 px-4 py-2 rounded-pill btn btn-primary text-white"
                    >
                        <Plus class="w-4 h-4" /> Add Allergy
                    </button>

                    <!-- Download all -->
                    <div class="dropdown">
                        <button
                            class="btn btn-outline-secondary rounded-pill px-4 dropdown-toggle"
                            data-bs-toggle="dropdown"
                        >
                            Download all
                        </button>
                        <ul
                            class="dropdown-menu dropdown-menu-end shadow rounded-4 py-2"
                        >
                            <li>
                                <a
                                    class="dropdown-item py-2"
                                    href="javascript:;"
                                    @click="onDownload('pdf')"
                                    >Download as PDF</a
                                >
                            </li>
                            <li>
                                <a
                                    class="dropdown-item py-2"
                                    href="javascript:;"
                                    @click="onDownload('excel')"
                                    >Download as Excel</a
                                >
                            </li>

                            <li>
                                <a
                                    class="dropdown-item py-2"
                                    href="javascript:;"
                                    @click="onDownload('csv')"
                                >
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
                            <th>Allergy Name</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fixed: Use filteredAllergys instead of allergies for proper filtering -->
                        <tr v-for="(r, i) in filteredAllergys" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>

                            <td class="text-center">
                                <div
                                    class="d-inline-flex align-items-center gap-3"
                                >
                                    <button
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalAllergyForm"
                                        @click="
                                            () => {
                                                openEdit(r);
                                                formErrors = {};
                                            }
                                        "
                                        title="Edit"
                                        class="p-2 rounded-full text-blue-600 hover:bg-blue-100"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>

                                    <ConfirmModal
                                        :title="'Confirm Delete'"
                                        :message="`Are you sure you want to delete ${r.name}?`"
                                        :showDeleteButton="true"
                                        @confirm="
                                            () => {
                                                deleteAllergy(r);
                                            }
                                        "
                                        @cancel="() => {}"
                                    />
                                </div>
                            </td>
                        </tr>

                        <!-- Fixed: Check filteredAllergys length instead of allergies -->
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
    <div
        class="modal fade"
        id="modalAllergyForm"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isEditing ? "Edit Allergy" : "Add Allergy(s)" }}
                    </h5>

                    <button
                        class="absolute top-2 right-2 p-2 rounded-full hover:bg-gray-100 transition transform hover:scale-110"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                        title="Close"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-red-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="isEditing">
                        <label class="form-label">Allergy Name</label>
                        <input
                            v-model="customAllergy"
                            class="form-control"
                            placeholder="e.g., Vegan"
                            :class="{ 'is-invalid': formErrors.customAllergy }"
                        />
                        <span
                            class="text-danger"
                            v-if="formErrors.customAllergy"
                            >{{ formErrors.customAllergy[0] }}</span
                        >
                    </div>
                    <div v-else>
                        <MultiSelect
                            v-model="commonAllergies"
                            :options="availableOptions"
                            optionLabel="label"
                            optionValue="value"
                            :multiple="true"
                            showClear
                            :filter="true"
                            display="chip"
                            placeholder="Choose common  allergies or add new one"
                            class="w-100"
                            appendTo="self"
                            @filter="(e) => (filterText = e.value || '')"
                            :invalid="formErrors.allergies?.length"
                        >
                            <template #header>
                                <div class="w-100 d-flex justify-content-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-link text-primary"
                                        @click.stop="selectAll"
                                    >
                                        Select All
                                    </button>
                                </div>
                            </template>

                            <template #footer>
                                <div
                                    v-if="filterText?.trim()"
                                    class="p-2 border-top d-flex justify-content-between align-items-center"
                                >
                                    <small class="text-muted"
                                        >Not found in the list? Add it as a
                                        custom allergy</small
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary rounded-pill"
                                        @click="addCustom"
                                    >
                                        Add "{{ filterText.trim() }}"
                                    </button>
                                </div>
                            </template>
                        </MultiSelect>
                        <span class="text-danger" v-if="formErrors.allergies">{{
                            formErrors.allergies[0]
                        }}</span>
                    </div>

                    <button
                        class="btn btn-primary rounded-pill w-100 mt-4"
                        @click="onSubmit"
                    >
                        {{ isEditing ? "Save Changes" : "Add Allergy(s)" }}
                    </button>
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

.table-responsive {
    overflow: visible !important;
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
