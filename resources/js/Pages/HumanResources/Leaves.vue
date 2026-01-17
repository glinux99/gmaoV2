<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// API v4 / V11
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';

// Composants PrimeVue
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
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
const { t } = useI18n();
const leaveDialog = ref(false);
const editing = ref(false);
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
const leaveTypes = computed(() => [
    { label: t('leaves.types.annual'), value: 'annuel', icon: 'pi-sun', color: '#3B82F6' },
    { label: t('leaves.types.sick'), value: 'maladie', icon: 'pi-heart', color: '#EF4444' },
    { label: t('leaves.types.rtt'), value: 'rtt', icon: 'pi-clock', color: '#8B5CF6' },
    { label: t('leaves.types.exceptional'), value: 'exceptionnel', icon: 'pi-star', color: '#F59E0B' }
]);

const statusOptions = computed(() => [
    { label: t('leaves.status.approved'), value: 'approved' },
    { label: t('leaves.status.pending'), value: 'pending' },
    { label: t('leaves.status.rejected'), value: 'rejected' },
]);

// --- FILTRES ---
const filters = ref({
    global: { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.IN },
    type: { value: null, matchMode: FilterMatchMode.IN },
});

// --- LOGIQUE ---
const stats = computed(() => {
    const data = props.leaves.data || [];
    return {
        total: data.length,
        pending: data.filter(l => l.status === 'pending').length,
        approved: data.filter(l => l.status === 'approved').length,
        rejected: data.filter(l => l.status === 'rejected').length,
    };
});

const getStatusSeverity = (status) => {
    switch (status) {
        case 'approved': return 'success';
        case 'pending': return 'warning';
        case 'rejected': return 'danger';
        default: return 'secondary';
    }
};

const getLeaveTypeIcon = (type) => {
    const found = leaveTypes.value.find(t => t.value === type);
    return ['pi', found ? found.icon : 'pi-calendar'];
};

const getLeaveTypeColor = (type) => {
    const found = leaveTypes.value.find(t => t.value === type);
    return found ? found.color : '#64748B';
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
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data,
    })).submit(method, url, {
        onSuccess: () => {
            leaveDialog.value = false;
            toast.add({ severity: 'success', summary: t('common.success'), detail: t('leaves.toast.success'), life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du congé", errors);
            toast.add({ severity: 'error', summary: t('toast.error'), detail: t('toast.genericError'), life: 3000 });
        }
    });
};

const onFileUpload = (event) => {
    form.document_file = event.files[0];
};

const deleteLeave = (leave) => {
    confirm.require({
        message: t('confirm.deleteConfirmation'),
        header: t('common.attention'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('leaves.destroy', leave.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: t('common.info'), detail: t('common.delete'), life: 3000 })
            });
        }
    });
};

const updateLeaveStatus = (leave, status) => {
    confirm.require({
        message: t('leaves.confirm.statusChange', { status: t(`leaves.status.${status}`) }),
        header: t('common.attention'),
        icon: 'pi pi-info-circle',
        acceptClass: status === 'approved' ? 'p-button-success' : 'p-button-danger',
        acceptLabel: t('common.confirm'),
        rejectLabel: t('common.cancel'),
        accept: () => {
            router.put(route('leaves.updateStatus', leave.id), { status }, {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: t('common.success'), detail: t('leaves.toast.statusUpdated'), life: 3000 }),
                onError: () => toast.add({ severity: 'error', summary: t('toast.error'), detail: t('toast.genericError'), life: 3000 }),
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="t('leaves.title')" />

        <div class="p-4 lg:p-8 bg-[#F8FAFC] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-primary-600 rounded-[2rem] flex items-center justify-center shadow-xl shadow-primary-200">
                        <i class="pi pi-calendar text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-[900] text-slate-900 tracking-tight">{{ t('leaves.title') }}</h1>
                        <p class="text-slate-500 font-medium">
                            {{ t('leaves.subtitle', { count: leaves.data?.length || 0 }) }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button icon="pi pi-upload" text severity="secondary" @click="dt.exportCSV()" />
                    <Button :label="t('leaves.newRequest')" icon="pi pi-plus"  raised @click="openNew" class="rounded-lg font-bold" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="flex flex-col gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t(`leaves.stats.${key}`) }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ val }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i class="pi pi-calendar-plus text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                <DataTable
                    ref="dt"
                    :value="leaves.data"
                    v-model:selection="selectedLeaves"
                    v-model:filters="filters"
                    dataKey="id"
                    :paginator="true"
                    :rows="10"
                    filterDisplay="menu"
                    class="p-datatable-custom"
                    removableSort
                >
                    <template #header>
                        <div class="flex flex-wrap justify-between items-center gap-4 p-2">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" :placeholder="t('common.search')" class="w-full md:w-80 border-none bg-slate-50 rounded-xl" />
                            </IconField>
                        </div>
                    </template>

                    <Column selectionMode="multiple" style="width: 3rem"></Column>

                    <Column field="user.name" :header="t('leaves.table.applicant')" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                                    {{ data.user?.name.charAt(0) }}
                                </div>
                                <span class="font-bold text-slate-700">{{ data.user?.name }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="type" :header="t('leaves.table.type')" filterField="type" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i :class="getLeaveTypeIcon(data.type)" :style="{ color: getLeaveTypeColor(data.type) }"></i>
                                <b class="text-xs uppercase tracking-wider text-slate-500">{{ data.type }}</b>
                            </div>
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="leaveTypes" optionLabel="label" optionValue="value" :placeholder="t('common.total', {item: ''})" class="p-column-filter" />
                        </template>
                    </Column>

                    <Column field="start_date" :header="t('leaves.table.period')" style="min-width: 12rem">
                        <template #body="{ data }">
                            <div class="flex flex-col text-sm">
                                <span class="text-slate-700 font-semibold">Du {{ new Date(data.start_date).toLocaleDateString() }}</span>
                                <span class="text-slate-400 text-xs font-medium">Au {{ new Date(data.end_date).toLocaleDateString() }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="status" :header="t('leaves.table.status')" filterField="status" :showFilterMatchModes="false">
                        <template #body="{ data }">
                            <Tag :value="t(`leaves.status.${data.status}`)" :severity="getStatusSeverity(data.status)" class="rounded-md font-bold text-[10px]" />
                        </template>
                        <template #filter="{ filterModel }">
                            <Dropdown v-model="filterModel.value" :options="statusOptions" optionLabel="label" optionValue="value" :placeholder="t('common.total', {item: ''})" class="p-column-filter" />
                        </template>
                    </Column>

                    <Column field="approval_date" :header="t('leaves.table.approvalDate')" sortable>
                        <template #body="{ data }">
                            <span v-if="data.approval_date" class="text-sm text-slate-600">
                                {{ new Date(data.approval_date).toLocaleDateString() }}
                            </span>
                            <span v-else class="text-slate-400">-</span>
                        </template>
                    </Column>

                    <Column field="approvedBy.name" :header="t('leaves.table.approvedBy')" sortable>
                        <template #body="{ data }">
                            <span v-if="data.approved_by" class="font-semibold text-slate-700">
                                {{ data.approved_by.name }}
                            </span>
                            <span v-else class="text-slate-400">-</span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-2 justify-end">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editLeave(data)" />
                                <Button v-if="data.status === 'pending'" icon="pi pi-check" text rounded severity="success" @click="updateLeaveStatus(data, 'approved')" v-tooltip.top="t('common.approve')" />
                                <Button v-if="data.status === 'pending'" icon="pi pi-times" text rounded severity="warning" @click="updateLeaveStatus(data, 'rejected')" v-tooltip.top="t('common.reject')" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteLeave(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="leaveDialog" modal :header="false" :closable="false"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }">
            <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-calendar-plus text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-100 m-0">{{ editing ? t('leaves.dialog.editTitle') : t('leaves.dialog.createTitle') }}</h4>
                        <p class="text-xs text-slate-500 m-0">{{ t('leaves.dialog.headerSubtitle') }}</p>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="leaveDialog = false" class="text-white hover:bg-white/10" />
            </div>

            <div class="p-6 bg-white max-h-[80vh] overflow-y-auto scroll-smooth space-y-6">
                <div class="flex flex-col gap-2">
                    <label class="text-xs font-black text-slate-500 uppercase">{{ t('leaves.dialog.applicant') }}</label>
                    <Dropdown v-model="form.user_id" :options="users" optionLabel="name" optionValue="id"
                        :placeholder="t('leaves.dialog.applicantPlaceholder')" class="v11-input w-full" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('leaves.dialog.startDate') }}</label>
                        <Calendar v-model="form.start_date" dateFormat="dd/mm/yy" showIcon class="v11-calendar" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-black text-slate-500 uppercase">{{ t('leaves.dialog.endDate') }}</label>
                        <Calendar v-model="form.end_date" dateFormat="dd/mm/yy" showIcon class="v11-calendar" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-black text-slate-500 uppercase">{{ t('leaves.dialog.leaveType') }}</label>
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
                    <label class="text-xs font-black text-slate-500 uppercase">{{ t('leaves.dialog.reason') }}</label>
                    <Textarea v-model="form.reason" rows="3" class="v11-input w-full" :placeholder="t('leaves.dialog.reasonPlaceholder')" />
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-xs font-black text-slate-500 uppercase">{{ t('leaves.dialog.attachment') }}</label>
                    <FileUpload mode="basic" @select="onFileUpload" :auto="true" :chooseLabel="t('leaves.dialog.attachmentChoose')" class="v11-upload" />
                </div>
            </div>

            <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="leaveDialog = false" class="font-bold uppercase text-[10px] tracking-widest" />
                <Button :label="t('leaves.dialog.sendRequest')" icon="pi pi-check-circle"
                        class="px-10 h-14 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase tracking-widest text-xs"
                        @click="saveLeave" :loading="form.processing" />
            </div>

        </Dialog>

        <Toast position="bottom-right" />
        <ConfirmDialog />
    </AppLayout>
</template>

<style lang="scss">
.p-datatable-custom {
    .p-datatable-thead > tr > th {
        @apply bg-slate-50/50 text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-6 px-4 border-b border-slate-200/60;
    }
    .p-datatable-tbody > tr {
        @apply border-b border-slate-100;
        &:hover { @apply bg-slate-50/50; }
    }
}

.v11-input, .v11-calendar .p-inputtext, .v11-input .p-inputtext {
    @apply rounded-xl border-slate-200 bg-slate-50 p-3 text-sm font-bold w-full;
    &:focus-within, &:focus {
        @apply bg-white ring-2 ring-primary-200 border-primary-300;
    }
}

.v11-upload {
    .p-fileupload-basic { @apply w-full; }
    .p-button { @apply w-full rounded-xl border-dashed border-2 bg-slate-50 border-slate-200 text-slate-500 font-bold hover:bg-slate-100; }
}
</style>
