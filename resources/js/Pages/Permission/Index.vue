<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useForm, Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, watch, computed } from "vue";
import pkg from "lodash";
const { debounce } = pkg;

// PrimeVue Components
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from "primevue/usetoast";
import Toast from 'primevue/toast';
import MultiSelect from 'primevue/multiselect';
import OverlayPanel from 'primevue/overlaypanel';
import Tag from 'primevue/tag';

const props = defineProps({
    title: String,
    filters: Object,
    permissions: Object,
    roles: Object,
    perPage: Number,
});

const { t } = useI18n();
const toast = useToast();
const op = ref();
const isModalOpen = ref(false);
const selectedItems = ref([]);

const form = useForm({
    id: null,
    name: '',
    guard_name: 'web',
});

// --- Statistiques ---
const stats = computed(() => ({
    total: props.permissions.total || 0,
    web: props.permissions.data.filter(p => p.guard_name === 'web').length,
    api: props.permissions.data.filter(p => p.guard_name === 'api').length,
}));

// --- Gestion des Colonnes ---
const allColumns = [
    { field: 'name', header: 'Nom Permission', sortable: true },
    { field: 'guard_name', header: 'Guard', sortable: true },
    { field: 'created_at', header: 'Date Création', sortable: true },
];
const selectedColumnFields = ref(allColumns.map(col => col.field));
const displayedColumns = computed(() =>
    allColumns.filter(col => selectedColumnFields.value.includes(col.field))
);

// --- Actions ---
const openCreate = () => {
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEdit = (permission) => {
    form.clearErrors();
    form.id = permission.id;
    form.name = permission.name;
    form.guard_name = permission.guard_name;
    isModalOpen.value = true;
};

const submit = () => {
    const url = form.id ? route('permission.update', form.id) : route('permission.store');
    form.submit(form.id ? 'put' : 'post', url, {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Permission enregistrée', life: 3000 });
        }
    });
};

// --- Filtres & Pagination ---
const filters = ref({ global: { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS } });

watch(() => filters.value.global.value, debounce((newValue) => {
    router.get(route("permission.index"), { search: newValue }, { preserveState: true, replace: true });
}, 300));

const onPageChange = (event) => {
    router.get(route('permission.index'), { page: event.page + 1 }, { preserveState: true });
};
</script>

<template>
    <AppLayout>
        <Head :title="props.title" />
        <Toast />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-shield text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                            {{ props.title }}
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">Contrôle d'accès & Sécurité</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button label="Nouvelle Permission" icon="pi pi-plus" class="shadow-lg shadow-primary-200" @click="openCreate" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-lock text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Permissions</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-blue-50 flex items-center justify-center"><i class="pi pi-globe text-2xl text-blue-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.web }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Guard Web</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-purple-50 flex items-center justify-center"><i class="pi pi-bolt text-2xl text-purple-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.api }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Guard API</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable lazy :value="permissions.data" :rows="permissions.per_page" :totalRecords="permissions.total"
                           v-model:selection="selectedItems" dataKey="id" v-model:filters="filters"
                           @page="onPageChange" paginator class="p-datatable-sm quantum-table">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher une règle..." class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-download" text rounded severity="secondary" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem" />

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                        <template #body="{ data, field }">
                            <span v-if="field === 'name'" class="font-bold text-slate-800 tracking-tight">{{ data.name }}</span>
                            <Tag v-else-if="field === 'guard_name'" :value="data.guard_name" severity="secondary" class="text-[9px] px-2" />
                            <span v-else-if="field === 'created_at'" class="text-slate-500 text-xs font-mono">
                                {{ new Date(data.created_at).toLocaleDateString() }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" headerStyle="width: 5rem">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-secondary" @click="openEdit(data)" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <OverlayPanel ref="op" class="p-4">
            <div class="font-semibold mb-3">Colonnes à afficher</div>
            <MultiSelect v-model="selectedColumnFields" :options="allColumns" optionLabel="header" optionValue="field" display="chip" class="w-full max-w-xs" />
        </OverlayPanel>

        <Dialog v-model:visible="isModalOpen" modal :header="false" :closable="false" class="quantum-dialog w-full max-w-lg" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">

            <div class="px-8 py-4 bg-slate-900 rounded-xl text-white flex justify-between items-center shadow-lg relative z-50">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-primary-500/20 rounded-lg border border-primary-500/30">
                        <i class="pi pi-key text-primary-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-sm font-black uppercase tracking-widest text-white leading-none">
                            {{ form.id ? 'Modifier Permission' : 'Nouvelle Permission' }}
                        </h2>
                        <span class="text-[9px] text-primary-300 font-bold uppercase tracking-tighter mt-1 italic">Security Policy Console</span>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="isModalOpen = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 space-y-6">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">Identifiant Unique</label>
                    <InputText v-model="form.name" class="w-full quantum-input" placeholder="ex: manage-users" :invalid="!!form.errors.name" />
                    <small class="p-error block mt-1" v-if="form.errors.name">{{ form.errors.name }}</small>
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">Contexte (Guard)</label>
                    <div class="flex gap-4">
                        <div class="flex-1 p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center justify-center gap-3"
                             :class="form.guard_name === 'web' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white'"
                             @click="form.guard_name = 'web'">
                            <span class="text-xs font-bold">WEB</span>
                        </div>
                        <div class="flex-1 p-3 rounded-xl border-2 transition-all cursor-pointer flex items-center justify-center gap-3"
                             :class="form.guard_name === 'api' ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-slate-200 bg-white'"
                             @click="form.guard_name = 'api'">
                            <span class="text-xs font-bold">API</span>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-2">
                    <Button label="Abandonner" icon="pi pi-times" class="p-button-text p-button-secondary" @click="isModalOpen = false" />
                    <Button label="Appliquer" icon="pi pi-check" class="px-6 shadow-lg shadow-primary-100" @click="submit" :loading="form.processing" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped lang="scss">
/* On réutilise les mêmes styles que ton composant Interventions */
.quantum-table {
    :deep(.p-datatable-thead > tr > th) {
        @apply bg-slate-50/50 text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-5 border-b border-slate-100;
    }
}
.quantum-input {
    @apply rounded-xl border-slate-200 focus:ring-primary-500;
}
</style>
