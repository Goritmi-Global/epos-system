<script setup>
import { ref, computed } from "vue";
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";

// table data
const rows = ref([
    { id: 1, name: "kilogram (kg)" },
    { id: 2, name: "piece (pc)" },
]);

// static/common list
const options = ref([
    { name: "kilogram (kg)" },
    { name: "litre (L)" },
    { name: "millilitre (ml)" },
    { name: "pound (lb)" },
    { name: "ounce (oz)" },
    { name: "pint" },
    { name: "gallon" },
    { name: "piece (pc)" },
    { name: "dozen (doz)" },
    { name: "crate" },
    { name: "box" },
    { name: "bottle" },
    { name: "pack" },
    { name: "serving" },
    { name: "bunch" },
]);

// select state
const selected = ref([]); // array of strings (option.name)
const filterText = ref(""); // typed text in the filter box

// form mode
const isEditing = ref(false);
const editingRow = ref(null);
const editName = ref("");

// search
const q = ref("");
const filteredRows = computed(() => {
    const t = q.value.trim().toLowerCase();
    return t
        ? rows.value.filter((r) => r.name.toLowerCase().includes(t))
        : rows.value;
});

// actions
const selectAll = () => (selected.value = options.value.map((o) => o.name));
const removeAll = () => (selected.value = []);

const addCustom = () => {
    const label = (filterText.value || "").trim();
    if (!label) return;
    if (
        !options.value.some((o) => o.name.toLowerCase() === label.toLowerCase())
    ) {
        options.value.push({ name: label });
    }
    if (!selected.value.includes(label)) {
        selected.value = [...selected.value, label];
    }
    filterText.value = "";
};

const openAdd = () => {
    isEditing.value = false;
    selected.value = [];
    filterText.value = "";
};

const openEdit = (row) => {
    isEditing.value = true;
    editingRow.value = row;
    editName.value = row.name;
};

const removeRow = (row) => {
    rows.value = rows.value.filter((r) => r !== row);
};

// fake async "query"
const runQuery = (payload) => {
    console.log("[Units] will run query with payload:", payload);
    return new Promise((resolve) =>
        setTimeout(() => resolve({ ok: true }), 600)
    );
};

const onSubmit = () => {
    if (isEditing.value) {
        // update one
        if (!editName.value?.trim()) return;
        editingRow.value.name = editName.value.trim();

        const payload = { action: "update", row: { ...editingRow.value } };
        console.log("[Units] Submitted:", payload);
        runQuery(payload)
            .then((res) => console.log("[Units] query OK:", res))
            .catch((err) => console.error("[Units] query ERROR:", err));
        return;
    }
    const onView = (row) => {};
    const onEdit = (row) => {};
    const onRemove = (row) => {};

    // add many
    const existing = new Set(rows.value.map((r) => r.name));
    const added = [];
    selected.value.forEach((n) => {
        if (!existing.has(n)) {
            const row = { id: Date.now() + Math.random(), name: n };
            rows.value.push(row);
            added.push(row);
        }
    });

    const payload = { action: "create", added };
    console.log("[Units] Submitted:", payload);
    runQuery(payload)
        .then((res) => console.log("[Units] query OK:", res))
        .catch((err) => console.error("[Units] query ERROR:", err));

    selected.value = [];
};
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div
                class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
            >
                <h4 class="mb-0">Units</h4>
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
                        class="btn btn-primary rounded-pill px-4"
                        data-bs-toggle="modal"
                        data-bs-target="#modalUnitForm"
                        @click="openAdd"
                    >
                        Add Unit
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
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Unit</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in filteredRows" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button
                                        class="btn btn-link text-secondary p-0 fs-5"
                                        data-bs-toggle="dropdown"
                                        title="Actions"
                                    >
                                        ⋮
                                    </button>
                                    <ul
                                        class="dropdown-menu dropdown-menu-end shadow rounded-4 overflow-hidden"
                                    >
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onView(s)"
                                                ><i
                                                    data-feather="eye"
                                                    class="me-2"
                                                ></i
                                                >View</a
                                            >
                                        </li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2"
                                                href="javascript:;"
                                                @click="onEdit(s)"
                                                ><i
                                                    data-feather="edit-2"
                                                    class="me-2"
                                                ></i
                                                >Edit</a
                                            >
                                        </li>
                                        <li><hr class="dropdown-divider" /></li>
                                        <li>
                                            <a
                                                class="dropdown-item py-2 text-danger"
                                                href="javascript:;"
                                                @click="onRemove(s)"
                                                ><i
                                                    data-feather="trash-2"
                                                    class="me-2"
                                                ></i
                                                >Delete</a
                                            >
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredRows.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                No units found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="modalUnitForm" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ isEditing ? "Edit Unit" : "Add Unit(s)" }}
                    </h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <!-- Edit one -->
                    <div v-if="isEditing">
                        <label class="form-label">Unit Name</label>
                        <input
                            v-model="editName"
                            class="form-control"
                            placeholder="e.g., kilogram (kg)"
                        />
                    </div>

                    <!-- Add many -->
                    <div v-else>
                        <MultiSelect
                            v-model="selected"
                            :options="options"
                            optionLabel="name"
                            optionValue="name"
                            filter
                            display="chip"
                            placeholder="Choose units or type to add"
                            class="w-100"
                            appendTo="body"
                            :pt="{ panel: { class: 'pv-overlay-fg' } }"
                            @filter="(e) => (filterText = e.value || '')"
                        >
                            <template #option="{ option }">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-basket3 me-2"></i>
                                    <span>{{ option.name }}</span>
                                </div>
                            </template>

                            <template #header>
                                <div class="font-medium px-3 py-2">
                                    Common Units
                                </div>
                            </template>

                            <template #footer>
                                <div class="p-3 d-flex justify-content-between">
                                    <Button
                                        label="Add New"
                                        severity="secondary"
                                        variant="text"
                                        size="small"
                                        icon="pi pi-plus"
                                        @click="addCustom"
                                    />
                                    <div class="d-flex gap-2">
                                        <Button
                                            label="Select All"
                                            severity="secondary"
                                            variant="text"
                                            size="small"
                                            icon="pi pi-check"
                                            @click="selectAll"
                                        />
                                        <Button
                                            label="Remove All"
                                            severity="danger"
                                            variant="text"
                                            size="small"
                                            icon="pi pi-times"
                                            @click="removeAll"
                                        />
                                    </div>
                                </div>
                            </template>
                        </MultiSelect>
                    </div>

                    <button
                        class="btn btn-primary rounded-pill w-100 mt-4"
                        @click="onSubmit"
                        data-bs-dismiss="modal"
                    >
                        {{ isEditing ? "Save Changes" : "Add Unit(s)" }}
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

/* keep PrimeVue overlays above Bootstrap modal/backdrop */
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
