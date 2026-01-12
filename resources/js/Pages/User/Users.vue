
<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, watch, computed } from "vue";
import { useForm, router, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import pkg from "lodash";
const { _, debounce, pickBy } = pkg;

import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode } from '@primevue/core/api';
import Avatar from 'primevue/avatar';
// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const props = defineProps({
    title: String,
    filters: Object,
    users: Object,
    roles: Object,
});

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const isModalOpen = ref(false);
const selectedUsers = ref([]);

const filters = ref({
    global: { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
});

// --- GESTION DU RECHARGEMENT CÔTÉ SERVEUR ---
let timeoutId = null;


const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    role: null,
});

const allColumns = computed(() => [
    { field: 'name', header: t('users.fields.name'), default: true },
    { field: 'role', header: t('users.fields.role'), default: true },
    { field: 'created_at', header: t('users.fields.created_at'), default: false },
    { field: 'phone', header: t('users.fields.phone'), default: false },
]);

const selectedColumns = ref(allColumns.value.filter(col => col.default));

const stats = computed(() => {
    const data = props.users.data || [];
    const roleStats = (props.roles || []).reduce((acc, role) => {
        acc[role.name] = data.filter(u => u.roles.some(r => r.name === role.name)).length;
        return acc;
    }, {});

    return {
        total: props.users.total,
        ...roleStats
    };
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    isModalOpen.value = true;
};

const openEdit = (user) => {
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone;
    form.role = user.roles.length > 0 ? user.roles[0].name : null;
    isModalOpen.value = true;
};

const submit = () => {
    const url = form.id ? route('user.update', form.id) : route('user.store');

    form.post(url, {
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Opération réussie', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Veuillez corriger les erreurs.', life: 3000 });
        }
    });
};

const deleteUser = (user) => {
    confirm.require({
        message: `Voulez-vous supprimer définitivement ${user.name} ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('user.destroy', user.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Utilisateur retiré', life: 3000 })
            });
        }
    });
};

const confirmDeleteSelected = () => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedUsers.value.length} utilisateurs sélectionnés ?`,
        header: 'Suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => { // Assurez-vous que cette route existe
            const ids = selectedUsers.value.map(u => u.id);
            router.post(route('users.bulkDestroy'), { ids }, { // Assurez-vous que cette route existe
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} utilisateurs supprimés.`, life: 3000 });
                    selectedUsers.value = [];
                }
            });
        }
    });
};

watch(
    () => filters.value.global.value, (newValue) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(
            route('user.index'),
            { search: newValue },
            { preserveState: true, replace: true, only: ['users'] }
        );
    }, 400);
});

</script>

<template>
    <AppLayout :title="props.title">
        <Head :title="props.title" />
        <Toast />
        <ConfirmDialog />

        <div class="p-4 md:p-8 bg-[#F8FAFC] min-h-screen">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="pi pi-users text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ props.title }}</h1>
                        <p class="text-slate-500 text-sm">{{ t('users.subtitle') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button :label="t('users.actions.add')" icon="pi pi-plus" severity="primary" raised @click="openCreate" class="rounded-lg font-bold" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all">
                    <div class="flex flex-column gap-2">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ t(`users.stats.${key}`, { default: key }) }}</span>
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-black text-slate-800">{{ val }}</span>
                            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center">
                                <i class="pi pi-users text-slate-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">
                <DataTable :value="users.data" v-model:selection="selectedUsers" dataKey="id" v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['name', 'email']" paginator :rows="10" :rowsPerPageOptions="[10, 25, 50]" removableSort stripedRows class="p-datatable-custom">
                    <template #header>
                        <div class="flex flex-wrap justify-between items-center gap-4 p-2 w-full">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search text-slate-400" />
                                <InputText v-model="filters['global'].value" placeholder="Rechercher un utilisateur" class="w-full md:w-80 border-none bg-slate-50 rounded-xl" />
                            </IconField>
                            <div class="flex items-center gap-2">
                                <MultiSelect v-model="selectedColumns" :options="allColumns" optionLabel="header" placeholder="Colonnes" display="chip" class="w-full md:w-64 border-none bg-slate-50 rounded-xl" />
                                <Button v-if="selectedUsers.length" :label="t('users.actions.deleteSelected')" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected" class="p-button-sm rounded-xl" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column v-for="col in selectedColumns" :key="col.field" :field="col.field" :header="col.header" sortable>
                         <template #body="{ data, field }">
                            <template v-if="field === 'name'">
                                <div class="flex items-center gap-5 group cursor-pointer" @click="openEdit(data)">
                                    <div class="relative">
                                        <Avatar :label="data.name?.charAt(0) || 'U'" shape="circle" size="xlarge" class="shadow-lg bg-slate-200 text-slate-700" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-800">{{ data.name }}</span>
                                        <span class="text-xs text-slate-500">{{ data.email }}</span>
                                    </div>
                                </div>
                            </template>
                            <template v-else-if="field === 'role'">
                                <Tag v-for="role in data.roles" :key="role.id" :value="role.name" severity="secondary" class="mr-1" />
                            </template>
                            <template v-else-if="field === 'phone'">
                                <span class="font-mono text-sm">{{ data.phone }}</span>
                            </template>
                            <template v-else>
                                {{ data[field] }}
                            </template>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" class="hover:bg-blue-50" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteUser(data)" class="hover:bg-red-50" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <Dialog v-model:visible="isModalOpen" modal position="right" :header="false" :closable="false" :style="{ width: '40rem' }" class="v11-dialog-ultimate" :draggable="false">
            <div class="px-8 py-5 bg-slate-900 text-white rounded-xl flex justify-between items-center relative z-50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary-200">
                        <i class="pi pi-user-plus text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-100 m-0">{{ form.id ? t('users.editTitle') : t('users.createTitle') }}</h4>
                    </div>
                </div>
                <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="isModalOpen = false" class="text-white hover:bg-white/10" />
            </div>
            <form @submit.prevent="submit" class="p-4 space-y-8">
                <div class="grid grid-cols-12 gap-10">
                    <div class="col-span-12 md:col-span-4">
                        <div class="sticky top-0">
                            <div class="relative group bg-white rounded-[2.5rem] p-3 border border-slate-200 shadow-2xl transition-all duration-500 hover:border-primary-300">
                               <div class="relative w-full aspect-square overflow-hidden rounded-[2.5rem] bg-slate-100 shadow-2xl border-4 border-white group">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <span class="text-[10rem] font-black text-slate-200 uppercase select-none">
                                            {{ form.name ? form.name[0] : 'U' }}
                                        </span>
                                    </div>
                               </div>
                                <div class="py-6 text-center">
                                    <h3 class="font-black text-slate-900 text-2xl tracking-tighter leading-none mb-2">
                                        {{ form.name || 'Nouveau Profil' }}
                                    </h3>
                                    <span class="text-[10px] font-black text-primary-600 uppercase tracking-[0.3em] bg-primary-50 px-4 py-1.5 rounded-full">
                                        Identité
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-8 space-y-8">
                        <div>
                            <span class="v11-header-label">Informations Générales</span>
                            <div class="grid grid-cols-1 gap-6 mt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="v11-label">Nom complet</label>
                                    <InputText id="name" v-model="form.name" class="v11-input-ultimate" placeholder="ex: Jean Dupont" required :invalid="!!form.errors.name" />
                                    <small class="p-error">{{ form.errors.name }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="email" class="v11-label">Email Professionnel</label>
                                    <InputText id="email" v-model="form.email" type="email" class="v11-input-ultimate" placeholder="tech@entreprise.com" required :invalid="!!form.errors.email" />
                                    <small class="p-error">{{ form.errors.email }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="role" class="v11-label">Rôle</label>
                                    <Dropdown v-model="form.role" :options="props.roles" optionLabel="name" optionValue="name" placeholder="Sélectionner un rôle" class="v11-dropdown-ultimate" id="role" :invalid="!!form.errors.role" />
                                    <small class="p-error">{{ form.errors.role }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="phone" class="v11-label">Téléphone</label>
                                    <InputText id="phone" v-model="form.phone" class="v11-input-ultimate" placeholder="ex: +33 6 12 34 56 78" :invalid="!!form.errors.phone" />
                                    <small class="p-error">{{ form.errors.phone }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-blue-50/30 rounded-3xl border border-blue-100">
                            <span class="v11-header-label !text-blue-700">Sécurité du compte</span>
                            <p v-if="form.id" class="text-xs text-slate-500 mt-2">Laissez les champs vides pour ne pas changer le mot de passe.</p>
                            <div class="grid grid-cols-2 gap-6 mt-4">
                                <InputText v-model="form.password" type="password" placeholder="Mot de passe" class="v11-input-ultimate !bg-white" :invalid="!!form.errors.password" />
                                <InputText v-model="form.password_confirmation" type="password" placeholder="Confirmer" class="v11-input-ultimate !bg-white" />
                            </div>
                             <small class="p-error" v-if="form.errors.password">{{ form.errors.password }}</small>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="isModalOpen = false" class="font-bold uppercase text-[10px] tracking-widest" />
                    <Button :label="form.id ? t('common.save') : t('common.create')"  icon="pi pi-check-circle" severity="primary"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase tracking-widest text-xs"
                            @click="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<style lang="scss">
.p-datatable-custom {
    .p-datatable-thead > tr > th {
        @apply bg-slate-50/50 text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-6 px-4 border-b border-slate-100;
    }
    .p-datatable-tbody > tr {
        @apply border-b border-slate-50;
        &:hover { @apply bg-slate-50/50; }
    }
}
</style>
