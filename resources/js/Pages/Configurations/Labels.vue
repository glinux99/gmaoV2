<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// --- IMPORTS COMPOSANTS ---
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import Checkbox from 'primevue/checkbox';
import ColorPicker from 'primevue/colorpicker';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Avatar from 'primevue/avatar';

const { t } = useI18n();
const props = defineProps({
    labels: Object, // Inertia pagination object
    filters: Object,
});

// --- ÉTATS & RÉFÉRENCES ---
const toast = useToast();
const confirm = useConfirm();
const dt = ref();
const labelDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');

// --- TYPES DE CARACTÉRISTIQUES ---
const characteristicTypes = ref([
    { label: computed(() => t('labels.types.text')), value: 'text' },
    { label: computed(() => t('labels.types.number')), value: 'number' },
    { label: computed(() => t('labels.types.date')), value: 'date' },
    { label: computed(() => t('labels.types.file')), value: 'file' },
    { label: computed(() => t('labels.types.boolean')), value: 'boolean' },
    { label: computed(() => t('labels.types.select')), value: 'select' },
]);

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    designation: '',
    description: '',
    color: '3B82F6', // Bleu par défaut
    characteristics: [],
});

// --- STATISTIQUES (Calculées sur les labels actuels) ---
const stats = computed(() => {
    const data = props.labels.data || [];
    return {
        total: data.length,
        withChars: data.filter(l => l.label_characteristics?.length > 0).length,
    };
});

// --- CONFIGURATION DES FILTRES ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    };
};

// --- LOGIQUE MÉTIER ---
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    labelDialog.value = true;
};

const editLabel = (label) => {
    form.id = label.id;
    form.designation = label.designation;
    form.description = label.description;
    form.color = label.color.replace('#', '');
    form.characteristics = label.label_characteristics ? JSON.parse(JSON.stringify(label.label_characteristics)) : [];
    editing.value = true;
    labelDialog.value = true;
};

const saveLabel = () => {
    submitted.value = true;
    if (!form.designation) return;

    const hasChangedChars = editing.value && form.isDirty;

    if (hasChangedChars) {
        confirm.require({
            message: t('labels.messages.confirmChange'),
            header: t('common.confirmation'),
            icon: 'pi pi-exclamation-triangle',
            acceptClass: 'p-button-warning',
            accept: () => submitForm(),
        });
    } else {
        submitForm();
    }
};

const submitForm = () => {
    const url = editing.value ? route('labels.update', form.id) : route('labels.store');
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data,
        color: '#' + data.color,
        characteristics: data.characteristics.filter(c => c.name.trim() !== '')
    })).submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: t(`labels.messages.${editing.value ? 'updateSuccess' : 'createSuccess'}`), life: 3000 });
            form.reset();
        },
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: t('common.errorOccurred'), life: 3000 })
    });
};

const deleteLabel = (label) => {
    confirm.require({
        message: t('labels.messages.confirmDelete', { name: label.designation }),
        header: t('common.deleteConfirmation'),
        icon: 'pi pi-exclamation-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('labels.destroy', label.id), {
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: t('labels.messages.deleteSuccess'), life: 3000 })
            });
        }
    });
};

// --- GESTION CARACTÉRISTIQUES ---
const addCharacteristic = () => {
    form.characteristics.push({ id: null, name: '', type: 'text', is_required: false });
};

const removeCharacteristic = (index) => {
    form.characteristics.splice(index, 1);
};

// --- RECHERCHE & EXPORT ---
let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('labels.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 300);
};

const dialogTitle = computed(() => editing.value ? t('labels.dialog.editTitle') : t('labels.dialog.createTitle'));
</script>

<template>
    <AppLayout title="Gestion des Labels">
        <Head title="Labels" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Configuration des Labels</h1>
                    <p class="text-slate-500 font-medium">Définissez les étiquettes et caractéristiques techniques</p>
                </div>
                <Button :label="t('labels.actions.add')" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6" />
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <Toast />
                <ConfirmDialog />

                <DataTable ref="dt" :value="labels.data" dataKey="id" :paginator="true" :rows="10"
                    v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['designation', 'description']"
                    class="p-datatable-custom" responsiveLayout="scroll">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" :placeholder="t('common.search')" class="w-full md:w-80 rounded-2xl border-slate-200" />
                            </IconField>
                            <div class="flex gap-2">
                                <Button :label="t('labels.toolbar.resetFilters')" icon="pi pi-filter-slash" severity="secondary" outlined @click="initFilters" class="rounded-xl" />
                                <Button :label="t('common.export')" icon="pi pi-upload" severity="secondary" text @click="dt.exportCSV()" />
                            </div>
                        </div>
                    </template>

                    <Column field="designation" :header="t('labels.fields.name')" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :label="data.designation[0]" shape="circle" :style="{ backgroundColor: data.color + '20', color: data.color }" class="font-bold" />
                                <span class="font-black text-slate-700">{{ data.designation }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" :placeholder="t('common.searchByName')" />
                        </template>
                    </Column>

                    <Column :header="t('labels.fields.style')">
                        <template #body="{ data }">
                            <Tag :value="data.color" :style="{ backgroundColor: data.color, color: '#fff' }" class="rounded-lg px-3 border-none" />
                        </template>
                    </Column>

                    <Column field="description" :header="t('labels.fields.description')" class="text-slate-500 text-sm" />

                    <Column :header="t('labels.fields.characteristics')">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-for="char in data.label_characteristics" :key="char.id" :value="char.name" severity="secondary" class="bg-slate-100 text-slate-600 border-none px-2"></Tag>
                                <span v-if="!data.label_characteristics?.length" class="text-xs italic text-slate-400">Aucune</span>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editLabel(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteLabel(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="labelDialog" modal :header="dialogTitle"
            :style="{ width: '90vw', maxWidth: '650px' }"
            :contentStyle="{ maxHeight: '85vh', overflow: 'auto' }"
            class="ultimate-modal">

            <div class="p-2 space-y-8 mt-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('labels.fields.name') }}</label>
                        <InputText v-model="form.designation" class="w-full py-3.5 rounded-xl border-slate-200 shadow-inner"
                            :class="{ 'p-invalid': submitted && !form.designation }" />
                        <small class="text-red-500 font-bold" v-if="submitted && !form.designation">{{ t('labels.validation.nameRequired') }}</small>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('labels.fields.color') }}</label>
                        <div class="flex items-center gap-3 p-2 bg-white rounded-xl border border-slate-200">
                            <ColorPicker v-model="form.color" />
                            <span class="font-mono text-xs font-bold text-slate-400">#{{ form.color }}</span>
                        </div>
                    </div>

                    <div class="md:col-span-3 space-y-2">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest">{{ t('labels.fields.description') }}</label>
                        <Textarea v-model="form.description" rows="2" class="w-full rounded-xl border-slate-200" />
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-tighter">{{ t('labels.fields.characteristics') }}</h3>
                        <Button :label="t('labels.actions.addChar')" icon="pi pi-plus" severity="primary" text size="small" @click="addCharacteristic" />
                    </div>

                    <div v-if="form.characteristics.length" class="space-y-3">
                        <div v-for="(char, index) in form.characteristics" :key="index"
                            class="flex flex-col md:flex-row items-start md:items-center gap-3 p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">

                            <InputText v-model="char.name" :placeholder="t('labels.fields.charName')" class="flex-1 rounded-lg border-slate-200 text-sm" />

                            <Dropdown v-model="char.type" :options="characteristicTypes" optionLabel="label" optionValue="value"
                                class="w-full md:w-32 rounded-lg border-slate-200 text-sm" :disabled="char.id !== null" />

                            <div class="flex items-center gap-2 px-3">
                                <Checkbox v-model="char.is_required" :binary="true" :inputId="`req-${index}`" />
                                <label :for="`req-${index}`" class="text-xs font-bold text-slate-500">{{ t('labels.fields.isRequiredShort') }}</label>
                            </div>

                            <Button icon="pi pi-trash" severity="danger" text rounded @click="removeCharacteristic(index)" />
                        </div>
                    </div>
                    <div v-else class="text-center py-8 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                        <p class="text-slate-400 text-sm italic">Aucune caractéristique définie pour ce label.</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-slate-100">
                    <Button :label="t('common.cancel')" severity="secondary" text @click="labelDialog = false" class="flex-1 py-4 rounded-2xl font-bold" />
                    <Button :label="t('common.save')" severity="primary" raised @click="saveLabel" :loading="form.processing"
                        class="flex-1 py-4 rounded-2xl font-black shadow-xl shadow-primary-200 transition-all uppercase text-xs tracking-widest" />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style lang="scss">
/* Integration des styles V11 Custom */
.p-datatable-custom {
    .p-datatable-thead > tr > th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.7rem;
        padding: 1.25rem 1rem;
        border-bottom: 2px solid #f1f5f9;
    }
    .p-datatable-tbody > tr {
        transition: all 0.2s;
        &:hover { background: #f8fafc !important; }
    }
}

.ultimate-modal {
    .p-dialog-header {
        padding: 2rem 2rem 1rem 2rem;
        .p-dialog-title { font-weight: 900; font-size: 1.25rem; }
    }
    .p-dialog-content { padding: 0 2rem 2rem 2rem; }
}

/* Style spécifique pour le bouton primaire de validation */
.p-button.p-button-primary {
    background: #2563eb !important;
    border: none;
    &:hover { background: #1d4ed8 !important; transform: translateY(-1px); }
}
</style>
