<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';

// Core V11 API
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Toolbar from 'primevue/toolbar';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import FileUpload from 'primevue/fileupload';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Avatar from 'primevue/avatar';
import SelectButton from 'primevue/selectbutton';
import MultiSelect from 'primevue/multiselect';
import Slider from 'primevue/slider';

const { t } = useI18n();
const props = defineProps({
    regions: Array,
    filters: Object,
});

// --- ÉTATS RÉACTIFS ---
const toast = useToast();
const confirm = useConfirm();
const dt = ref();
const labelDialog = ref(false);
const importDialog = ref(false);
const submitted = ref(false);
const editing = ref(false);
const loading = ref(false);
const selectedRegions = ref(null);

// --- FORMULAIRES ---
const form = useForm({
    id: null,
    designation: '',
    type_centrale: 'solaire',
    puissance_centrale: 0,
    description: '',
    status: 'active'
});

// --- SYSTÈME DE FILTRES AVANCÉS (V11 Custom) ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    type_centrale: { value: null, matchMode: FilterMatchMode.IN },
    puissance_centrale: { value: [0, 1000], matchMode: FilterMatchMode.BETWEEN },
    status: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        designation: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        type_centrale: { value: null, matchMode: FilterMatchMode.IN },
        puissance_centrale: { value: [0, 1000], matchMode: FilterMatchMode.BETWEEN },
        status: { value: null, matchMode: FilterMatchMode.EQUALS }
    };
};

// --- LOGIQUE DE CALCUL DES STATS ---
const stats = computed(() => {
    const data = props.regions || [];
    const totalMWValue = data.reduce((acc, curr) => acc + (parseFloat(curr.puissance_centrale) || 0), 0);
    return {
        total: data.length,
        totalMW: totalMWValue.toLocaleString('fr-FR', { minimumFractionDigits: 2 }),
        avgPower: data.length > 0 ? (totalMWValue / data.length).toFixed(1) : 0,
        highPerf: data.filter(r => r.puissance_centrale > 500).length
    };
});

// --- MÉTHODES D'ACTION ---
const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    labelDialog.value = true;
};

const editRegion = (region) => {
    form.clearErrors();
    Object.assign(form, region);
    editing.value = true;
    labelDialog.value = true;
};

const saveRegion = () => {
    submitted.value = true;
    if (!form.designation) return;

    loading.value = true;
    const url = editing.value ? route('regions.update', form.id) : route('regions.store');
    const method = editing.value ? 'put' : 'post';

    form.submit(method, url, {
        onSuccess: () => {
            labelDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Région enregistrée', life: 3000 });
            loading.value = false;
        },
        onError: () => { loading.value = false; }
    });
};

const deleteRegion = (region) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer ${region.designation} ?`,
        header: 'Attention',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('regions.destroy', region.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Info', detail: 'Supprimé' })
            });
        }
    });
};

// --- OPTIONS DE CONFIGURATION ---
const typeOptions = [
    { label: 'Thermique', value: 'thermique', icon: 'pi pi-bolt', color: 'red' },
    { label: 'Solaire', value: 'solaire', icon: 'pi pi-sun', color: 'orange' },
    { label: 'Hydraulique', value: 'hydraulique', icon: 'pi pi-cloud', color: 'blue' },
    { label: 'Eolienne', value: 'eolienne', icon: 'pi pi-directions', color: 'green' }
];

const getSeverity = (type) => {
    const map = { solaire: 'warning', thermique: 'danger', hydraulique: 'info', eolienne: 'success' };
    return map[type] || 'secondary';
};
</script>

<template>
    <AppLayout>
        <Head title="Parc Énergétique V11" />

        <div class="min-h-screen bg-slate-50/50 p-4 lg:p-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Gestion du Parc</h1>
                    <p class="text-slate-500 font-medium">Supervision technique des régions énergétiques</p>
                </div>
                <div class="flex gap-3">
                    <Button label="Réinitialiser Filtres" icon="pi pi-filter-slash" outlined severity="secondary" @click="initFilters" class="rounded-xl" />
                    <Button label="Nouvelle Région" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all">
                    <div class="flex flex-column gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ key.replace(/([A-Z])/g, ' $1') }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ val }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i :class="key === 'totalMW' ? 'pi pi-bolt' : 'pi pi-database'" class="text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="regions"
                    v-model:selection="selectedRegions"
                    v-model:filters="filters"
                    dataKey="id"
                    :paginator="true"
                    :rows="10"
                    filterDisplay="menu"
                    :globalFilterFields="['designation', 'type_centrale']"
                    class="p-datatable-custom"
                    removableSort
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters['global'].value" placeholder="Recherche globale..." class="w-full md:w-80 rounded-2xl border-slate-200" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column field="designation" header="Désignation" sortable filterField="designation">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <Avatar :label="data.designation[0]" shape="circle" class="bg-primary-50 text-primary-600 font-bold" />
                                <span class="font-bold text-slate-700">{{ data.designation }}</span>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" type="text" class="p-column-filter" placeholder="Chercher par nom" />
                        </template>
                    </Column>

                    <Column field="type_centrale" header="Source" sortable :showFilterMatchModes="false">
                        <template #body="{ data }">
                            <Tag :value="data.type_centrale" :severity="getSeverity(data.type_centrale)" class="rounded-lg px-3 uppercase text-[10px]" />
                        </template>
                        <template #filter="{ filterModel }">
                            <MultiSelect v-model="filterModel.value" :options="typeOptions" optionLabel="label" optionValue="value" placeholder="Tous les types" class="w-full" />
                        </template>
                    </Column>

                    <Column field="puissance_centrale" header="Capacité" sortable>
                        <template #body="{ data }">
                            <div class="flex flex-col w-40">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-black text-slate-700">{{ data.puissance_centrale }} <small>MW</small></span>
                                    <span class="text-[10px] text-slate-400 font-bold">{{ ((data.puissance_centrale/1000)*100).toFixed(0) }}%</span>
                                </div>
                                <ProgressBar :value="(data.puissance_centrale/1000)*100" :showValue="false" style="height: 7px" class="rounded-full overflow-hidden" />
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <div class="px-3 pt-2 pb-4">
                                <Slider v-model="filterModel.value" range :min="0" :max="1000" class="w-full" />
                                <div class="flex justify-between mt-3 text-xs font-bold text-slate-500">
                                    <span>{{ filterModel.value[0] }}MW</span>
                                    <span>{{ filterModel.value[1] }}MW</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="min-w-[120px]">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editRegion(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteRegion(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog
            v-model:visible="labelDialog"
            modal
            :header="editing ? 'Mise à jour Technique' : 'Nouvelle Configuration'"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :contentStyle="{ maxHeight: '80vh', overflow: 'auto' }"
            class="ultimate-modal"
        >
            <div class="p-2 space-y-8">
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-box text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-800 m-0">Paramètres de la région</h4>
                        <p class="text-xs text-slate-500 m-0">Complétez les informations de production</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2 flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">Nom de l'infrastructure</label>
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-map-marker" />
                            <InputText v-model="form.designation" class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50" placeholder="Ex: Centrale Nord-Est" />
                        </IconField>
                        <small v-if="submitted && !form.designation" class="text-red-500 font-bold italic">Le nom est obligatoire.</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">Type d'Énergie</label>
                        <Dropdown v-model="form.type_centrale" :options="typeOptions" optionLabel="label" optionValue="value" class="rounded-xl border-slate-200 py-1">
                            <template #option="slotProps">
                                <div class="flex items-center gap-2">
                                    <i :class="slotProps.option.icon"></i>
                                    <span>{{ slotProps.option.label }}</span>
                                </div>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">Puissance (MW)</label>
                        <InputNumber v-model="form.puissance_centrale" mode="decimal" showButtons :min="0" suffix=" MW" class="w-full" inputClass="py-3.5 rounded-xl border-slate-200" />
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">Notes Techniques</label>
                        <textarea v-model="form.description" rows="3" class="w-full p-4 rounded-2xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-primary-50 transition-all text-sm outline-none" placeholder="Détails supplémentaires..."></textarea>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-8 border-t border-slate-100">
                    <button @click="labelDialog = false" class="flex-1 px-6 py-4 rounded-2xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all uppercase text-xs tracking-widest">
                        Abandonner
                    </button>
                    <button
                        @click="saveRegion"
                        class="flex-1 px-8 py-4 rounded-2xl font-black text-white bg-primary-600 hover:bg-primary-700 shadow-xl shadow-primary-200 hover:-translate-y-1 active:translate-y-0 transition-all uppercase text-xs tracking-widest flex items-center justify-center gap-3"
                    >
                        <i v-if="loading" class="pi pi-spin pi-spinner"></i>
                        <span>{{ editing ? 'Mettre à jour' : 'Enregistrer la configuration' }}</span>
                    </button>
                </div>
            </div>
        </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss">
/* Personnalisation PrimeVue V11 pour matching Tailwind */
.p-datatable-custom {
    .p-datatable-thead > tr > th {
        background: #f8fafc;
        color: #475569;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 0.05em;
        padding: 1.25rem 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .p-datatable-tbody > tr {
        background: white;
        transition: all 0.2s;
        &:hover {
            background: #f1f5f9 !important;
        }
    }
}

.ultimate-modal {
    .p-dialog-header {
        background: white;
        padding: 2rem 2rem 1rem 2rem;
        border: none;
        .p-dialog-title { font-weight: 900; font-size: 1.25rem; color: #0f172a; }
    }
    .p-dialog-content {
        padding: 0 2rem 2rem 2rem;
    }
}

/* Fix for Primary Style Toggle */
.p-button.p-button-primary {
    background: #2563eb;
    border-color: #2563eb;
    &:hover { background: #1d4ed8; border-color: #1d4ed8; }
}

/* Custom MultiSelect and Sliders */
.p-multiselect, .p-dropdown {
    border-radius: 12px;
}
</style>
