<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

// --- COMPOSANTS DE BASE ---
import AppLayout from "@/sakai/layout/AppLayout.vue";

// --- PRIME VUE ULTIMATE V11 ---
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import Tag from 'primevue/tag';

const props = defineProps({
    regions: Array,
    filters: Object,
    technicians: Array,
    teams: Array,
});

const toast = useToast();
const confirm = useConfirm();
const dt = ref();

const filters = ref({
    search: props.filters?.search || '',
    region_id: props.filters?.region_id || null,
    leader_id: props.filters?.leader_id || null,
});

const selectedTeams = ref([]);
const isModalOpen = ref(false);

const form = useForm({
    id: null,
    name: '',
    team_leader_id: null,
    creation_date: null,
    members: [],
});

// --- ACTIONS ---
const applyFilters = debounce(() => {
    router.get(route('teams.index'), { ...filters.value }, { preserveState: true, replace: true });
}, 400);

const resetFilters = () => {
    filters.value = { search: '', region_id: null, leader_id: null };
    applyFilters();
};

const exportCSV = () => dt.value.exportCSV();

const openCreate = () => {
    form.reset();
    form.creation_date = new Date();
    isModalOpen.value = true;
};

const editTeam = (team) => {
    form.id = team.id;
    form.name = team.name;
    form.team_leader_id = team.team_leader_id;
    form.creation_date = new Date(team.creation_date);
    form.members = team.members ? team.members.map(m => m.id) : [];
    isModalOpen.value = true;
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
</script>

<template>
    <AppLayout title="Gestion des Équipes">
        <Toast />
        <ConfirmDialog />

        <div class="min-h-screen bg-slate-50 p-4 md:p-10 font-sans">

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[2rem] bg-slate-900 shadow-xl shadow-slate-200">
                        <i class="pi pi-users text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tighter text-slate-900 md:text-4xl">Unités de Terrain</h1>
                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Management des ressources V11</p>
                    </div>
                </div>

                <div class="flex w-full items-center gap-3 lg:w-auto">
                    <button @click="exportCSV" class="flex flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-emerald-600 shadow-sm transition-all hover:bg-emerald-50 active:scale-95 lg:flex-none">
                        <i class="pi pi-file-excel"></i> Export Excel
                    </button>
                    <button @click="openCreate" class="flex flex-[2] items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-indigo-100 transition-all hover:bg-indigo-700 active:scale-95 lg:flex-none">
                        <i class="pi pi-plus-circle"></i> Nouvelle Équipe
                    </button>
                </div>
            </div>

            <div class="mb-6 flex flex-wrap items-center gap-4 rounded-[2.5rem] border border-white bg-white/50 p-4 shadow-sm backdrop-blur-md">
                <div class="relative flex-1 min-w-[280px]">
                    <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input v-model="filters.search" type="text" placeholder="Rechercher une unité..." @input="applyFilters"
                           class="w-full rounded-2xl border-none bg-white py-3 pl-12 text-sm font-semibold shadow-inner focus:ring-2 focus:ring-indigo-500/20" />
                </div>

                <Dropdown v-model="filters.region_id" :options="regions" optionLabel="name" optionValue="id" placeholder="Toutes les régions"
                          class="w-full !rounded-2xl !border-none !bg-white !shadow-sm md:w-56" @change="applyFilters" />

                <button @click="resetFilters" class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm hover:text-red-500 transition-colors">
                    <i class="pi pi-filter-slash"></i>
                </button>
            </div>

            <div class="overflow-hidden rounded-[3rem] border border-white bg-white shadow-2xl shadow-slate-200/60">
                <DataTable :value="teams" ref="dt" v-model:selection="selectedTeams" dataKey="id"
                           scrollable scrollHeight="600px" class="v11-table">

                    <Column selectionMode="multiple" headerStyle="width: 4rem" class="pl-8"></Column>

                    <Column header="Désignation de l'Unité" minWidth="300px">
                        <template #body="{ data }">
                            <div class="group flex cursor-pointer items-center gap-4 py-2" @click="editTeam(data)">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 transition-all group-hover:bg-indigo-600 group-hover:text-white text-slate-500">
                                    <i class="pi pi-shield text-xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-black tracking-tight text-slate-800">{{ data.name }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-indigo-500">ID: #{{ data.id.toString().padStart(4, '0') }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Commandement" minWidth="200px">
                        <template #body="{ data }">
                            <div v-if="data.team_leader" class="flex w-fit items-center gap-3 rounded-full bg-slate-50 p-1 pr-4 border border-slate-100">
                                <Avatar :label="data.team_leader.name[0]" shape="circle" class="!bg-slate-900 !text-white !font-black" />
                                <span class="text-sm font-bold text-slate-700">{{ data.team_leader.name }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Effectifs" minWidth="250px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <AvatarGroup v-if="data.members?.length">
                                    <Avatar v-for="m in data.members.slice(0, 3)" :key="m.id" :label="m.name[0]" shape="circle" class="!border-2 !border-white !bg-indigo-100 !text-indigo-700 !font-bold" />
                                    <Avatar v-if="data.members.length > 3" :label="`+${data.members.length - 3}`" shape="circle" class="!bg-slate-800 !text-white !text-xs" />
                                </AvatarGroup>
                                <span class="text-[10px] font-black uppercase text-slate-400">{{ data.members?.length || 0 }} pers.</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded @click="editTeam(data)" class="!text-slate-400 hover:!bg-indigo-50 hover:!text-indigo-600 transition-all" />
                                <Button icon="pi pi-trash" text rounded severity="danger" class="!opacity-50 hover:!opacity-100 transition-all" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="isModalOpen" modal position="right" :draggable="false"
                class="!m-0 !h-screen !max-h-none" :style="{ width: '450px' }">

            <template #header>
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white">
                        <i class="pi pi-file-edit"></i>
                    </div>
                    <span class="text-xl font-black tracking-tighter text-slate-900">Fiche de l'unité</span>
                </div>
            </template>

            <form @submit.prevent="saveTeam" class="flex h-full flex-col gap-6 py-4 font-sans">
                <div class="space-y-6 flex-grow overflow-y-auto px-1">
                    <div class="rounded-[2rem] bg-slate-50 p-6 border border-slate-100 space-y-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Nom de l'équipe</label>
                            <InputText v-model="form.name" placeholder="Ex: EQP-DELTA-01" class="w-full !rounded-2xl !border-none !shadow-sm !py-4" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Chef d'unité</label>
                            <Dropdown v-model="form.team_leader_id" :options="technicians" optionLabel="name" optionValue="id"
                                      placeholder="Désigner un leader" filter class="!rounded-2xl !border-none !shadow-sm" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Membres assignés</label>
                            <MultiSelect v-model="form.members" :options="technicians" optionLabel="name" optionValue="id"
                                         placeholder="Sélectionner les membres" filter display="chip" class="!rounded-2xl !border-none !shadow-sm" />
                        </div>
                    </div>
                </div>

                <div class="mt-auto border-t border-slate-100 pt-6 flex gap-3">
                    <button type="button" @click="isModalOpen = false" class="flex-1 rounded-2xl py-4 text-sm font-bold text-slate-400 hover:bg-slate-50 transition-colors">Annuler</button>
                    <button type="submit" :disabled="form.processing" class="flex-[2] rounded-2xl bg-slate-900 py-4 text-sm font-black text-white shadow-xl transition-all hover:bg-black active:scale-95">
                        Confirmer les modifications
                    </button>
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<style>
/* Style spécifique pour la table afin d'affiner le rendu Tailwind avec PrimeVue */
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
