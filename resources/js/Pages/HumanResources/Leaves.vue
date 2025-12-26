<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// API v4 / V11
import { FilterMatchMode } from '@primevue/core/api';

// Composants PrimeVue
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import DatePicker from 'primevue/datepicker';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import FileUpload from 'primevue/fileupload';
import Textarea from 'primevue/textarea';

const props = defineProps({
    leaves: Object,
    filters: Object,
    users: Array,
});

const toast = useToast();
const confirm = useConfirm();
const leaveDialog = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');
const selectedLeaves = ref(null);
const dt = ref();

const { auth } = usePage().props;

const form = useForm({
    id: null,
    user_id: null,
    type: 'annuel',
    start_date: null,
    end_date: null,
    reason: null,
    status: 'pending',
    document_file: null,
    notes: null,
});

// --- CONSTANTES ---
const leaveTypes = ref([
    { label: 'Congé Annuel', value: 'annuel', icon: 'pi-sun', color: '#3B82F6' },
    { label: 'Congé Maladie', value: 'maladie', icon: 'pi-heart', color: '#EF4444' },
    { label: 'RTT', value: 'rtt', icon: 'pi-clock', color: '#8B5CF6' },
    { label: 'Exceptionnel', value: 'exceptionnel', icon: 'pi-star', color: '#F59E0B' }
]);

// --- LOGIQUE ---
const getStatusSeverity = (status) => {
    switch (status) {
        case 'approved': return 'success';
        case 'pending': return 'warn';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
};

const openNew = () => {
    form.reset();
    editing.value = false;
    form.user_id = auth.user?.id;
    form.start_date = new Date();
    form.end_date = new Date();
    leaveDialog.value = true;
};

const editLeave = (leave) => {
    form.clearErrors();
    Object.assign(form, {
        ...leave,
        start_date: new Date(leave.start_date),
        end_date: new Date(leave.end_date),
    });
    editing.value = true;
    leaveDialog.value = true;
};

const saveLeave = () => {
    const url = editing.value ? route('leaves.update', form.id) : route('leaves.store');
    form.post(url, {
        forceFormData: true,
        onSuccess: () => {
            leaveDialog.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Opération réussie', life: 3000 });
        },
        ...(editing.value && { _method: 'put' })
    });
};

const onFileUpload = (event) => {
    form.document_file = event.files[0];
};

const performSearch = () => {
    router.get(route('leaves.index'), { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <AppLayout>
        <Head title="Gestion des Congés" />

        <div class="p-4 lg:p-10 bg-[#F8FAFC] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center shadow-xl shadow-primary-100 rotate-3">
                        <i class="pi pi-calendar text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">Gestion des Congés</h1>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">
                            {{ leaves.data?.length || 0 }} Demandes répertoriées
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button icon="pi pi-download" text severity="secondary" @click="dt.exportCSV()" />
                    <Button label="Nouvelle Demande" icon="pi pi-plus" severity="primary" raised @click="openNew" class="rounded-xl px-6" />
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="leaves.data"
                    v-model:selection="selectedLeaves"
                    dataKey="id"
                    :paginator="true"
                    :rows="10"
                    class="ultimate-table"
                    paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                    currentPageReportTemplate="{first} à {last} sur {totalRecords}"
                >
                    <template #header>
                        <div class="flex justify-between items-center p-4">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-primary-500" />
                                <InputText v-model="search" placeholder="Recherche rapide..." @input="performSearch"
                                    class="w-full md:w-96 rounded-2xl border-none bg-slate-100 focus:bg-white transition-all" />
                            </IconField>
                        </div>
                    </template>

                    <Column selectionMode="multiple" style="width: 3rem"></Column>

                    <Column field="user.name" header="Demandeur" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                                    {{ data.user?.name.charAt(0) }}
                                </div>
                                <span class="font-bold text-slate-700">{{ data.user?.name }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="type" header="Type">
                        <template #body="{ data }">
                            <b class="text-xs uppercase tracking-wider text-slate-500">{{ data.type }}</b>
                        </template>
                    </Column>

                    <Column field="start_date" header="Période" style="min-width: 12rem">
                        <template #body="{ data }">
                            <div class="flex flex-col text-sm">
                                <span class="text-slate-700 font-semibold">Du {{ new Date(data.start_date).toLocaleDateString() }}</span>
                                <span class="text-slate-400 text-xs font-medium">Au {{ new Date(data.end_date).toLocaleDateString() }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" header="État">
                        <template #body="{ data }">
                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="rounded-lg px-3 uppercase text-[10px] font-black" />
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-2 justify-end">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editLeave(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteLeave(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="leaveDialog" modal :header="editing ? 'Mise à jour Congé' : 'Nouvelle Demande'"
            :style="{ width: '550px' }" class="v11-dialog">
            <div class="grid grid-cols-1 gap-6 py-4">
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Collaborateur</label>
                    <Select v-model="form.user_id" :options="users" optionLabel="name" optionValue="id"
                        placeholder="Sélectionner le demandeur" class="v11-input w-full" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date de début</label>
                        <DatePicker v-model="form.start_date" dateFormat="dd/mm/yy" showIcon iconDisplay="input" class="v11-datepicker" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date de fin</label>
                        <DatePicker v-model="form.end_date" dateFormat="dd/mm/yy" showIcon iconDisplay="input" class="v11-datepicker" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type de congé</label>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                        <div v-for="type in leaveTypes" :key="type.value"
                            @click="form.type = type.value"
                            :class="['p-3 border-2 rounded-xl cursor-pointer transition-all flex flex-col items-center gap-2',
                                    form.type === type.value ? 'border-primary-500 bg-primary-50' : 'border-slate-100 bg-white hover:border-slate-200']">
                            <i :class="['pi', type.icon]" :style="{ color: type.color }"></i>
                            <span class="text-[10px] font-bold uppercase">{{ type.label }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Motif de la demande</label>
                    <Textarea v-model="form.reason" rows="3" class="v11-input w-full" placeholder="Expliquez brièvement le motif..." />
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Justificatif (PDF, Image)</label>
                    <FileUpload mode="basic" @select="onFileUpload" :auto="true" chooseLabel="Choisir un fichier" class="v11-upload" />
                </div>
            </div>

            <template #footer>
                <div class="flex gap-3 w-full pt-4">
                    <Button label="Annuler" text severity="secondary" @click="leaveDialog = false" class="flex-1 rounded-xl" />
                    <Button label="Envoyer la demande" severity="primary" raised @click="saveLeave"
                        :loading="form.processing" class="flex-1 rounded-xl font-black py-4 shadow-lg shadow-primary-200" />
                </div>
            </template>
        </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss">
/* DESIGN SYSTEM V11 - CSS CUSTOMS */

.ultimate-table {
    .p-datatable-thead > tr > th {
        @apply bg-slate-50/50 text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-6 px-4 border-b border-slate-100;
        &.p-highlight { @apply text-primary-600 bg-primary-50/30; }
    }
    .p-datatable-tbody > tr {
        @apply border-b border-slate-50 transition-all duration-300;
        &:hover { @apply bg-primary-50/5 !important; }
        td { @apply py-4 px-4; }
    }
}

.v11-input, .v11-datepicker {
    &.p-select, &.p-datepicker, &.p-textarea, &.p-inputtext {
        @apply rounded-xl border-slate-200 bg-slate-50 p-3 text-sm font-bold;
        &:focus { @apply bg-white ring-4 ring-primary-50 border-primary-500 outline-none; }
    }
}

.v11-dialog {
    .p-dialog-header { @apply p-8 border-none; .p-dialog-title { @apply font-black text-slate-900; } }
    .p-dialog-content { @apply px-8 pb-8; }
}

.p-paginator {
    @apply border-t border-slate-100 py-4 bg-white rounded-b-[2.5rem];
    .p-paginator-page { @apply rounded-lg font-bold; &.p-highlight { @apply bg-primary-50 text-primary-600; } }
}

.v11-upload {
    .p-fileupload-basic { @apply w-full; }
    .p-button { @apply w-full rounded-xl border-dashed border-2 bg-slate-50 border-slate-200 text-slate-500 font-bold hover:bg-slate-100; }
}
</style>
