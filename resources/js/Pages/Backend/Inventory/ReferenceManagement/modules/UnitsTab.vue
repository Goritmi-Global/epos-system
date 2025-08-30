<script setup>
import { ref, computed } from "vue";
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";

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

const selected = ref([]); // array of strings (option.name)
const filterText = ref(""); // what user types in the filter box

// add typed text as a new option and select it
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

const selectAll = () => {
    selected.value = options.value.map((o) => o.name);
};
const removeAll = () => {
    selected.value = [];
};

// commit to table
const onAdd = () => {
    const existing = new Set(rows.value.map((r) => r.name));
    selected.value.forEach((n) => {
        if (!existing.has(n))
            rows.value.push({ id: Date.now() + Math.random(), name: n });
    });
    selected.value = [];
};

// table search (unchanged from your version)
const q = ref("");
const filteredRows = computed(() => {
    const t = q.value.trim().toLowerCase();
    return t
        ? rows.value.filter((r) => r.name.toLowerCase().includes(t))
        : rows.value;
});
</script>

<template>
    <!-- your card + table (shortened here) -->
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
                        data-bs-target="#modalAddUnit"
                    >
                        Add Unit
                    </button>
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
                                <button class="btn btn-link text-danger p-0">
                                    Delete
                                </button>
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
    
    <!-- Modal -->
    <div class="modal fade" id="modalAddUnit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Add Unit</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
                        <!-- row template (you can style like your pills or add icons) -->
                        <template #option="{ option }">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-basket3 me-2"></i>
                                <span>{{ option.name }}</span>
                            </div>
                        </template>

                        <template #dropdownicon>
                            <i class="pi pi-box"></i>
                        </template>

                        <template #filtericon>
                            <i class="pi pi-search"></i>
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

                    <button
                        class="btn btn-primary rounded-pill w-100 mt-4"
                        @click="onAdd"
                        data-bs-dismiss="modal"
                    >
                        Add Unit(s)
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal-body">
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
            :pt="{ panel: { style: 'z-index:1085' } }"
            @filter="(e) => (filterText = e.value || '')"
        >
            <!-- row template (you can style like your pills or add icons) -->
            <template #option="{ option }">
                <div class="d-flex align-items-center">
                    <i class="bi bi-basket3 me-2"></i>
                    <span>{{ option.name }}</span>
                </div>
            </template>

            <template #dropdownicon>
                <i class="pi pi-box"></i>
            </template>

            <template #filtericon>
                <i class="pi pi-search"></i>
            </template>

            <template #header>
                <div class="font-medium px-3 py-2">Common Units</div>
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

        <button
            class="btn btn-primary rounded-pill w-100 mt-4"
            @click="onAdd"
            data-bs-dismiss="modal"
        >
            Add Unit(s)
        </button>
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

/* primevue width/tokens */
:deep(.p-multiselect) {
    width: 100%;
}
:deep(.p-multiselect-token) {
    margin: 0.15rem;
}

/* PrimeVue overlay panel above Bootstrap modal (1055) & backdrop (1050) */
.pv-overlay-fg {
    z-index: 2000 !important;
}

/* (Optional) cover other selects if you use them) */
.p-select-panel,
.p-dropdown-panel,
.p-multiselect-panel {
    z-index: 2000 !important;
}
</style>
