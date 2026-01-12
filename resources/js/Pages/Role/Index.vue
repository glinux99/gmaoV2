<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useForm, Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, watch, computed, reactive } from "vue";
import pkg from "lodash";
const { debounce, pickBy, cloneDeep } = pkg;

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
import OverlayPanel from 'primevue/overlaypanel';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import Checkbox from 'primevue/checkbox';

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
const deleteDialog = ref(false);
const permissionDialog = ref(false);
const isCreateOpen = ref(false);

const data = reactive({
    params: {
        search: props.filters.search,
        field: props.filters.field,
        order: props.filters.order,
    },
    role: null,
});

// Formulaires Inertia
const form = useForm({});
const createForm = useForm({
    name: '',
    guard_name: 'web',
    permissions: [],
});

// --- Statistiques ---
const stats = computed(() => ({
    total: props.roles.total || 0,
    fullAccess: props.roles.data.filter(r => r.permissions.length === props.permissions.length).length,
    customAccess: props.roles.data.filter(r => r.permissions.length > 0 && r.permissions.length < props.permissions.length).length,
}));

// --- Gestion des Colonnes ---
const allColumns = [
    { field: 'name', header: 'Nom du Rôle', sortable: true },
    { field: 'guard_name', header: 'Guard', sortable: true },
    { field: 'permissions', header: 'Capacités', sortable: false },
    { field: 'created_at', header: 'Création', sortable: true },
];
const selectedColumnFields = ref(allColumns.map(col => col.field));
const displayedColumns = computed(() =>
    allColumns.filter(col => selectedColumnFields.value.includes(col.field))
);

// --- Logique de recherche permissions dans le modal ---
const permissionSearch = ref('');
const filteredPermissions = computed(() => {
    if (!permissionSearch.value) return props.permissions;
    return props.permissions.filter(p =>
        p.name.toLowerCase().includes(permissionSearch.value.toLowerCase())
    );
});

// --- Actions ---
const openCreate = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateOpen.value = true;
};

const submitCreate = () => {
    createForm.post(route('role.store'), {
        onSuccess: () => {
            isCreateOpen.value = false;
            createForm.reset();
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Rôle créé avec succès', life: 3000 });
        },
    });
};

const deleteData = () => {
    form.delete(route("role.destroy", data.role?.id), {
        onSuccess: () => {
            deleteDialog.value = false;
            toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Rôle retiré', life: 3000 });
        },
    });
}

const toggleAllPermissions = () => {
    if (createForm.permissions.length === props.permissions.length) {
        createForm.permissions = [];
    } else {
        createForm.permissions = props.permissions.map(p => p.id);
    }
};

const onPageChange = (event) => {
    router.get(route('role.index'), { page: event.page + 1 }, { preserveState: true });
};

watch(() => cloneDeep(data.params), debounce(() => {
    let params = pickBy(data.params);
    router.get(route("role.index"), params, { replace: true, preserveState: true });
}, 150));

</script>

<template>
    <AppLayout>
        <Head :title="props.title" />
        <Toast />

        <div class="quantum-v11-container p-4 lg:p-8 bg-[#f8fafc] min-h-screen">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[1rem] bg-primary-600 shadow-xl shadow-primary-100">
                        <i class="pi pi-users text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                            {{ props.title }}
                        </h1>
                        <p class="text-slate-500 text-xs font-medium uppercase tracking-widest">Hiérarchie & Privilèges</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button v-show="can(['create role'])" label="Nouveau Rôle" icon="pi pi-plus-circle"
                            class="shadow-lg shadow-primary-100 severity-primary" @click="openCreate" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center"><i class="pi pi-tags text-2xl text-slate-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.total }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Rôles Définis</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-primary-50 flex items-center justify-center"><i class="pi pi-verified text-2xl text-primary-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.fullAccess }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Accès Total</div>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 flex items-center justify-center"><i class="pi pi-sliders-h text-2xl text-amber-500"></i></div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ stats.customAccess }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Accès Limité</div>
                    </div>
                </div>
            </div>

            <div class="card-v11 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
                <DataTable lazy :value="roles.data" :rows="roles.per_page" :totalRecords="roles.total"
                           @page="onPageChange" paginator class="p-datatable-sm quantum-table"
                           :first="(roles.current_page - 1) * roles.per_page">

                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="data.params.search" placeholder="Rechercher un rôle..." class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 text-sm" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" />
                            </div>
                        </div>
                    </template>

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" :sortable="col.sortable">
                        <template #body="{ data: rowData, field }">
                            <span v-if="field === 'name'" class="font-bold text-slate-800">{{ rowData.name }}</span>
                            <Tag v-else-if="field === 'guard_name'" :value="rowData.guard_name" severity="secondary" class="text-[9px]" />
                            <div v-else-if="field === 'permissions'" @click="(permissionDialog = true), (data.role = rowData)" class="cursor-pointer group flex items-center gap-2">
                                <span v-if="rowData.permissions.length === props.permissions.length" class="text-xs font-bold text-green-600 underline decoration-dotted">Accès Total</span>
                                <span v-else class="text-xs font-medium text-slate-500 group-hover:text-primary-600 transition-colors">
                                    {{ rowData.permissions.length }} Capacité(s)
                                </span>
                                <i class="pi pi-external-link text-[10px] text-slate-300"></i>
                            </div>
                            <span v-else-if="field === 'created_at'" class="text-slate-500 text-xs font-mono">
                                {{ new Date(rowData.created_at).toLocaleDateString() }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Actions" headerStyle="width: 8rem">
                        <template #body="slotProps">
                            <div class="flex gap-1">
                                <Button v-show="can(['update role'])" icon="pi pi-pencil" text rounded severity="secondary" />
                                <Button v-show="can(['delete role'])" icon="pi pi-trash" text rounded "
                                        @click="deleteDialog = true; data.role = slotProps.data" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="isCreateOpen" modal :header="false" :closable="false" class="quantum-dialog w-full max-w-2xl" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">
            <div class="px-8 py-4 bg-slate-900 rounded-xl text-white flex justify-between items-center shadow-lg relative z-50">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-primary-500/20 rounded-lg border border-primary-500/30">
                        <i class="pi pi-plus-circle text-primary-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-sm font-black uppercase tracking-widest text-white leading-none">Nouveau Rôle</h2>
                        <span class="text-[9px] text-primary-300 font-bold uppercase tracking-tighter mt-1 italic italic">Security Provisioning Console</span>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="isCreateOpen = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Nom Technique</label>
                        <InputText v-model="createForm.name" class="w-full quantum-input" placeholder="ex: Manager" />
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                        <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-2">Guard</label>
                        <div class="flex gap-2">
                            <button v-for="g in ['web', 'api']" :key="g" @click="createForm.guard_name = g"
                                    :class="createForm.guard_name === g ? 'bg-slate-900 text-white' : 'bg-white text-slate-400 border-slate-200'"
                                    class="flex-1 py-2 rounded-lg text-[10px] font-black border transition-all uppercase underline-offset-4 tracking-widest">
                                {{ g }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-inner">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xs font-black uppercase text-slate-800 tracking-tight">Sélection des Privilèges</h3>
                        <Button :label="createForm.permissions.length === props.permissions.length ? 'Désélectionner tout' : 'Sélectionner tout'"
                                @click="toggleAllPermissions" text class="text-[10px] font-bold uppercase p-0" />
                    </div>
                    <IconField iconPosition="left" class="mb-4">
                        <InputIcon class="pi pi-search text-[10px]" />
                        <InputText v-model="permissionSearch" placeholder="Rechercher une règle..." class="w-full text-xs bg-slate-50 border-none rounded-lg" />
                    </IconField>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[250px] overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="permission in filteredPermissions" :key="permission.id"
                             class="flex items-center gap-3 p-3 rounded-xl border border-transparent transition-all"
                             :class="{'bg-primary-50 border-primary-100': createForm.permissions.includes(permission.id), 'hover:bg-slate-50': !createForm.permissions.includes(permission.id)}">
                            <Checkbox v-model="createForm.permissions" :inputId="'perm'+permission.id" :value="permission.id" />
                            <label :for="'perm'+permission.id" class="text-xs font-bold text-slate-600 cursor-pointer">{{ permission.name }}</label>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-2">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Capacités : <span class="text-primary-500">{{ createForm.permissions.length }}</span></span>
                    <Button label="Confirmer" icon="pi pi-shield" class="px-6 shadow-lg shadow-primary-100 severity-primary" @click="submitCreate" :loading="createForm.processing" />
                </div>
            </template>
        </Dialog>

        <OverlayPanel ref="op" class="p-4">
            <div class="font-semibold mb-3 text-[10px] uppercase tracking-widest text-slate-400">Colonnes</div>
            <MultiSelect v-model="selectedColumnFields" :options="allColumns" optionLabel="header" optionValue="field" display="chip" class="w-full max-w-xs" />
        </OverlayPanel>

        <Dialog v-model:visible="permissionDialog" modal :header="false" :closable="false" class="quantum-dialog w-full max-w-2xl">
            <div class="px-8 py-4 bg-slate-900 rounded-xl text-white flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-primary-500/20 rounded-lg border border-primary-500/30"><i class="pi pi-lock-open text-primary-400"></i></div>
                    <h2 class="text-sm font-black uppercase tracking-widest">Privilèges : {{ data.role?.name }}</h2>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="permissionDialog = false" class="text-white hover:bg-white/10" />
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-2 max-h-[50vh] overflow-y-auto custom-scrollbar">
                    <div v-for="(perm, idx) in data.role?.permissions" :key="idx" class="p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs font-bold text-slate-700">
                        {{ perm.name }}
                    </div>
                </div>
            </div>
        </Dialog>

        <Dialog v-model:visible="deleteDialog" modal header="Confirmation" :style="{ width: '25rem' }">
            <div class="flex items-center gap-4 py-4">
                <i class="pi pi-exclamation-circle text-red-500 text-3xl"></i>
                <p class="text-sm text-slate-600">Supprimer le rôle <span class="font-bold text-slate-900">{{ data.role?.name }}</span> ?</p>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button label="Annuler" text severity="secondary" @click="deleteDialog = false" />
                    <Button label="Supprimer" " @click="deleteData" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped lang="scss">
.quantum-table {
    :deep(.p-datatable-thead > tr > th) {
        @apply bg-slate-50/50 text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-5 border-b border-slate-100;
    }
}
.quantum-input {
    @apply rounded-lg border-slate-200 focus:ring-primary-500 text-sm;
}
.severity-primary {
    @apply bg-primary-600 border-none hover:bg-primary-700 text-white font-bold;
}
.custom-scrollbar {
    &::-webkit-scrollbar { width: 4px; }
    &::-webkit-scrollbar-track { @apply bg-transparent; }
    &::-webkit-scrollbar-thumb { @apply bg-slate-200 rounded-full; }
}
</style>
