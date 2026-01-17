<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

// --- COMPOSANTS DE BASE ---
import AppLayout from "@/sakai/layout/AppLayout.vue";

// --- PRIME VUE ULTIMATE V11 ---
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import Button from 'primevue/button';
import { useI18n } from 'vue-i18n';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import InputNumber from 'primevue/inputnumber';
import ConfirmDialog from 'primevue/confirmdialog';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Tag from 'primevue/tag';
// Core V11 API
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

const { t } = useI18n();
const props = defineProps({
    regions: Array,
    filters: Object,
    technicians: Array,
    teams: Array,
    stats: Object,
});

const toast = useToast();
const confirm = useConfirm();
const dt = ref();
const op = ref(); // Ref for OverlayPanel

// --- SYSTÈME DE FILTRES AVANCÉS (V11 Custom) ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'team_leader.name': { value: null, matchMode: FilterMatchMode.IN },
    'region.designation': { value: null, matchMode: FilterMatchMode.EQUALS },
    'team_leader.name': { value: null, matchMode: FilterMatchMode.CONTAINS },
    'region.designation': { value: null, matchMode: FilterMatchMode.EQUALS },
});

const allColumns = ref([
    { field: 'name', header: t('teams.fields.name') },
    { field: 'team_leader', header: t('teams.fields.leader') },
    { field: 'region.designation', header: t('equipments.table.region') },
    { field: 'members', header: t('teams.fields.members') },
    { field: 'nombre_tacherons', header: 'Tâcherons' },
]);

const visibleColumns = ref(allColumns.value.map(col => col.field));

const toggleColumnSelection = (event) => {
    op.value.toggle(event);
};

const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'team_leader.name': { value: null, matchMode: FilterMatchMode.IN },
        'region.designation': { value: null, matchMode: FilterMatchMode.EQUALS },
    };
    // Reset any other filters if needed
    // regionFilter.value = null; // If you want to reset the region dropdown too
};

const selectedTeams = ref([]);
const isModalOpen = ref(false);

const form = useForm({
    id: null,
    name: '',
    team_leader_id: null,
    nombre_tacherons: 0,
    members: [],
});

// --- ACTIONS ---
const applyFilters = debounce(() => {
    // This function is not directly used with the new DataTable filter system.
    // The DataTable handles filtering internally.
    // If you need to persist filters to the URL, you'd use `router.get` on filter change events.
}, 0); // Debounce not strictly needed if DataTable handles it

const resetFilters = () => {
    globalFilters.value.search.value = null;
    regionFilter.value = null;
    dt.value.reset();
    applyFilters();
};

const exportCSV = () => dt.value.exportCSV();

const openCreate = () => {
    form.reset();
     form.id = null;
    form.name ="";
    form.team_leader_id = null;
    form.nombre_tacherons = 0;
    form.members =  [];
    isModalOpen.value = true;
};

const editTeam = (team) => {

    form.id = team.id;
    form.name = team.name;
    form.team_leader_id = team.team_leader_id;
    form.nombre_tacherons = team.nombre_tacherons || 0;
    form.members = team.members ? team.members.map(m => m.id) : [];
    isModalOpen.value = true;
};

const updateTeamName = () => {
    if (form.team_leader_id && !form.id) { // Se déclenche uniquement à la création
        const leader = props.technicians.find(t => t.id === form.team_leader_id);
        if (leader) {
            const date = new Date();
            const year = date.getFullYear();
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const day = date.getDate().toString().padStart(2, '0');
            form.name = `${leader.name.split(' ')[0]}-${year}${month}${day}`;
        }
    }
};


const saveTeam = () => {
    const url = form.id ? route('teams.update', form.id) : route('teams.store');
    form.submit(form.id ? 'put' : 'post', url, {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Équipe mise à jour', life: 3000 });
        }
    });
};

const deleteTeam = (team) => {
    confirm.require({
        message: t('teams.messages.confirmDelete', { name: team.name }),
        header: t('common.deleteConfirmation'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('teams.destroy', team.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Supprimé', detail: t('teams.messages.deleteSuccess'), life: 3000 })
            });
        }
    });
};

</script>

<template>
    <AppLayout title="Gestion des Équipes">
        <Toast />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50 p-4 md:p-8 font-sans">

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-primary-600 shadow-xl shadow-primary-200">
                        <i class="pi pi-users text-3xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tighter text-slate-900 md:text-4xl">{{ t('teams.title') }}</h1>
                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">{{ t('teams.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex w-full items-center gap-3 lg:w-auto">
                    <button @click="exportCSV" class="flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-emerald-600 shadow-sm transition-all hover:bg-emerald-50 active:scale-95 lg:flex-none" v-tooltip.bottom="t('common.export')">
                        <i class="pi pi-file-excel"></i>
                    </button>
                    <button @click="openCreate" class="flex flex-[2] items-center justify-center gap-2 rounded-2xl bg-primary-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-primary-100 transition-all hover:bg-primary-700 active:scale-95 lg:flex-none">
                        <i class="pi pi-plus-circle"></i> {{ t('teams.actions.add') }}
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="h-12 w-12 rounded-2xl bg-primary-100 text-primary-600 flex items-center justify-center"><i class="pi pi-shield text-xl"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-800">{{ stats?.total_teams }}</span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('teams.stats.total_teams') }}</p>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="h-12 w-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="pi pi-users text-xl"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-800">{{ stats?.total_members }}</span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('teams.stats.total_members') }}</p>
                    </div>
                </div>
                <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="h-12 w-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center"><i class="pi pi-user-plus text-xl"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-800">{{ stats?.avg_members_per_team }}</span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('teams.stats.avg_members') }}</p>
                    </div>
                </div>
                 <div class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                    <div class="h-12 w-12 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center"><i class="pi pi-crown text-xl"></i></div>
                    <div>
                        <span class="text-2xl font-black text-slate-800">{{ stats?.teams_with_leader }}</span>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('teams.stats.with_leader') }}</p>
                    </div>
                </div>
            </div>



            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-200 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="teams"
                    v-model:selection="selectedTeams"
                    v-model:filters="filters"
                    dataKey="id"
                    :paginator="true"
                    :rows="10"
                    filterDisplay="menu"
                    :globalFilterFields="['name', 'team_leader.name', 'region.designation']"
                    class="p-datatable-custom"
                    removableSort
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('teams.toolbar.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="dt.exportCSV()" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="toggleColumnSelection" />
                                <Button v-if="selectedTeams && selectedTeams.length > 0"
                                        :label="t('common.deleteSelected', { count: selectedTeams.length })"
                                        icon="pi pi-trash" severity="danger" raised
                                        @click="deleteSelectedTeams" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 4rem" class="pl-8" frozen></Column>

                    <Column field="name" :header="t('teams.fields.name')" sortable filterField="name" v-if="visibleColumns.includes('name')">
                         <template #filter="{ filterModel, filterCallback }">
                            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" :placeholder="t('common.searchByName')"
                                       class="p-column-filter !min-w-44" />
                        </template>
                        <template #body="{ data }">
                            <div class="group flex cursor-pointer items-center gap-4 py-2" @click="editTeam(data)">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 transition-all group-hover:bg-primary-600 group-hover:text-white text-slate-500">
                                    <i class="pi pi-shield text-xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-black tracking-tight text-slate-800">{{ data.name }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-primary-500">ID: #{{ data.id.toString().padStart(4, '0') }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="team_leader.name" :header="t('teams.fields.leader')" sortable filterField="team_leader.name" v-if="visibleColumns.includes('team_leader')">
                        <template #filter="{ filterModel, filterCallback }">
                            <Dropdown v-model="filterModel.value" :options="technicians" optionLabel="name" optionValue="name"
                                      :placeholder="t('teams.toolbar.filterByLeader')" @change="filterCallback()" showClear filter
                                      class="p-column-filter !min-w-44" />
                        </template>
                        <template #body="{ data }">
                            <div v-if="data.team_leader" class="flex w-fit items-center gap-3 rounded-full bg-slate-50 p-1 pr-4 border border-slate-100 shadow-sm">
                                <Avatar :label="data.team_leader.name[0]" shape="circle" class="!bg-slate-900 !text-white !font-black" />
                                <span class="text-sm font-bold text-slate-700">{{ data.team_leader.name }}</span>
                            </div>
                            <span v-else class="text-slate-400 text-xs italic">Non défini</span>
                        </template>
                    </Column>

                    <Column field="region.designation" :header="t('equipments.table.region')" sortable filterField="region.designation" v-if="visibleColumns.includes('region.designation')">
                        <template #filter="{ filterModel, filterCallback }">
                            <Dropdown v-model="filterModel.value" :options="regions" optionLabel="designation" optionValue="designation"
                                      :placeholder="t('teams.toolbar.filterByRegion')" @change="filterCallback()" showClear filter
                                      class="p-column-filter !min-w-44" />
                        </template>
                        <template #body="{ data }">
                            <div v-if="data.region" class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-slate-400"></i>
                                <span class="text-sm font-medium text-slate-600">{{ data.region.designation }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="nombre_tacherons" header="Tâcherons" sortable v-if="visibleColumns.includes('nombre_tacherons')">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-user-minus text-slate-400"></i>
                                <span class="text-sm font-medium text-slate-600">{{ data.nombre_tacherons || 0 }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('teams.fields.members')" v-if="visibleColumns.includes('members')">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <AvatarGroup v-if="data.members?.length">
                                    <Avatar v-for="m in data.members.slice(0, 3)" :key="m.id" :label="m.name[0]" shape="circle" class="!border-2 !border-white !bg-primary-100 !text-primary-700 !font-bold" />
                                    <Avatar v-if="data.members.length > 3" :label="`+${data.members.length - 3}`" shape="circle" class="!bg-slate-800 !text-white !text-xs" />
                                </AvatarGroup>
                                <span class="text-[10px] font-black uppercase text-slate-400">{{ data.total_members_count || 0 }} pers.</span>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editTeam(data)" class="!text-slate-400 hover:!bg-primary-50 hover:!text-primary-600 transition-all" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteTeam(data)" class="!text-slate-400 hover:!bg-red-50 hover:!text-red-500 transition-all" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
                <div class="font-semibold mb-3">{{ t('common.columnSelector.title') }}</div>
                <MultiSelect
                    v-model="visibleColumns"
                    :options="allColumns"
                    optionLabel="header"
                    optionValue="field"
                    display="chip"
                    :placeholder="t('common.columnSelector.placeholder')" class="w-full max-w-xs" />
            </OverlayPanel>
        </div>
        <Dialog v-model:visible="isModalOpen" modal :header="false"
            :closable="false"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :contentStyle="{ maxHeight: '80vh', overflow: 'auto' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }"
        >
            <div class="px-8 py-5 bg-slate-900 text-white rounded-xl flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-box text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-100 m-0">{{ form.id ? t('teams.dialog.editTitle') : t('teams.dialog.createTitle') }}</h4>
                        <p class="text-xs text-slate-500 m-0">Configuration des unités de terrain</p>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="isModalOpen = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                    <div class="md:col-span-12 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">{{ t('teams.fields.leader') }}</label>
                                <Dropdown v-model="form.team_leader_id" :options="technicians" optionLabel="name" optionValue="id" @change="updateTeamName"
                                          :placeholder="t('teams.placeholders.leader')" filter class="rounded-xl border-slate-200 py-1" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">{{ t('teams.fields.name') }}</label>
                                <InputText v-model="form.name" :placeholder="t('teams.placeholders.name')" class="w-full py-3.5 rounded-xl border-slate-200 focus:ring-4 focus:ring-primary-50" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="text-xs font-black text-slate-500 uppercase">Nombre de Tâcherons</label>
                                <InputNumber v-model="form.nombre_tacherons" inputId="integeronly" class="w-full" inputClass="py-3.5 rounded-xl border-slate-200" />
                            </div>



                            <div class="md:col-span-2 flex flex-col gap-2 mt-4">
                                <label class="text-xs font-black text-slate-500 uppercase">{{ t('teams.fields.members') }}</label>
                                <MultiSelect v-model="form.members" :options="technicians" optionLabel="name" optionValue="id"
                                             :placeholder="t('teams.placeholders.members')" filter display="chip" class="rounded-xl border-slate-200 py-1" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="isModalOpen = false" class="font-bold uppercase text-[10px] tracking-widest" />
                    <Button :label="form.id ? t('common.update') : t('common.save')"
                            icon="pi pi-check-circle" severity="indigo"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs"
                            @click="saveTeam" :loading="form.processing" />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style>
.p-button-primary {
    background: #4f46e5;
    border: none;
}
/* Style spécifique pour la table afin d'affiner le rendu Tailwind avec PrimeVue */
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

.v11-table .p-datatable-thead > tr > th {
    background: #f8fafc !important;
    color: #94a3b8 !important;
    font-size: 10px !important;
    font-weight: 900 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.15em !important;
    padding: 1.5rem 1rem !important;
    border: none !important;
}

.p-column-filter-overlay .p-column-filter-menu-button, .p-column-filter-overlay .p-column-filter-clear-button {
    color: #94a3b8;
}


.v11-table .p-datatable-tbody > tr {
    transition: all 0.2s ease;
}

.v11-table .p-datatable-tbody > tr:hover {
    background: #f1f5f9/50 !important;
}

.p-dialog-mask {
    backdrop-filter: blur(4px);
}
</style>
