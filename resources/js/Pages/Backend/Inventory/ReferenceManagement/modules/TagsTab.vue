<script setup>
import { ref, computed } from "vue";
import Select from "primevue/select";

const rows = ref([
    { id: 1, name: "Vegan" },
    { id: 2, name: "Halal" },
]);

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

const selected = ref([]); // array of values
const filterText = ref("");

const selectAll = () => {
    selected.value = options.value.map((o) => o.value);
};

const addCustom = () => {
    const name = (filterText.value || "").trim();
    if (!name) return;
    if (
        !options.value.some((o) => o.label.toLowerCase() === name.toLowerCase())
    ) {
        options.value.push({ label: name, value: name });
    }
    if (!selected.value.includes(name)) {
        selected.value = [...selected.value, name];
    }
    filterText.value = "";
};

const onAdd = () => {
    const existing = new Set(rows.value.map((r) => r.name));
    const added = [];
    selected.value.forEach((v) => {
        if (!existing.has(v)) {
            const row = { id: Date.now() + Math.random(), name: v };
            rows.value.push(row);
            added.push(row);
        }
    });
    // 👇 Required: just show data in console
    console.log("[Tags] Submitted:", {
        added,
        allRows: rows.value.map((r) => ({ id: r.id, name: r.name })),
    });
    selected.value = [];
};

const q = ref("");
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    return t
        ? rows.value.filter((r) => r.name.toLowerCase().includes(t))
        : rows.value;
});

const removeRow = (row) => {
    rows.value = rows.value.filter((r) => r !== row);
};
</script>

<template>
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body">
            <div
                class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3"
            >
                <h4 class="mb-0">Tags</h4>
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
                        data-bs-target="#modalAddTags"
                    >
                        Add Tag
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="border-top small text-muted">
                        <tr>
                            <th>S. #</th>
                            <th>Tag</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in filtered" :key="r.id">
                            <td>{{ i + 1 }}</td>
                            <td class="fw-semibold">{{ r.name }}</td>
                            <td class="text-end">
                                <button
                                    class="btn btn-link text-danger p-0"
                                    @click="removeRow(r)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="filtered.length === 0">
                            <td colspan="3" class="text-center text-muted py-4">
                                No tags found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalAddTags" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title">Add Tag</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <Select
                        v-model="selected"
                        :options="options"
                        optionLabel="label"
                        optionValue="value"
                        :multiple="true"
                        :filter="true"
                        display="chip"
                        placeholder="Choose tags or type to add"
                        class="w-100"
                        @filter="(e) => (filterText = e.value || '')"
                    >
                        <!-- Header with Select All -->
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

                        <!-- Footer to add custom tag from filter text -->
                        <template #footer>
                            <div
                                v-if="filterText?.trim()"
                                class="p-2 border-top d-flex justify-content-between align-items-center"
                            >
                                <small class="text-muted">Not found?</small>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary rounded-pill"
                                    @click="addCustom"
                                >
                                    Add “{{ filterText.trim() }}”
                                </button>
                            </div>
                        </template>
                    </Select>

                    <button
                        class="btn btn-primary rounded-pill w-100 mt-4"
                        @click="onAdd"
                        data-bs-dismiss="modal"
                    >
                        Add Tag(s)
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

:deep(.p-select) {
    width: 100%;
}
:deep(.p-select-token) {
    margin: 0.15rem;
}
</style>
