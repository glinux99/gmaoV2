
<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router, Head, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import pkg from "lodash";
import AppLayout from "@/sakai/layout/AppLayout.vue";
const { _, debounce, pickBy } = pkg;

import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import Avatar from 'primevue/avatar';
// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import OverlayPanel from 'primevue/overlaypanel';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

const props = defineProps({
    title: String,
    filters: Object,
    users: Object,
    roles: Object,
    regions: Array,
});

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const op = ref();
const dt = ref();
const isModalOpen = ref(false);
const fileInput = ref(null);
const selectedUsers = ref([]);
const submitted = ref(false);
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const canImpersonate = computed(() => page.props.auth.user.permissions.includes('impersonate-user'));
const authUser = computed(() => page.props.auth.user);

const filters = ref({
    'global': { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
    'name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    'email': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },

    'role': { value: props.filters?.role || null, matchMode: FilterMatchMode.EQUALS },
    'region_name': { value: props.filters?.region_id || null, matchMode: FilterMatchMode.EQUALS },
});

// --- GESTION DU RECHARGEMENT CÔTÉ SERVEUR ---
const search = debounce(() => {
    const preparedFilters = {
        search: filters.value.global.value,
        region_id: filters.value.region_name.value,
        // Vous pouvez ajouter d'autres filtres ici si nécessaire pour le backend
    };
    router.get(route('user.index'), pickBy(preparedFilters), {
        preserveState: true,
        replace: true,
        only: ['users', 'filters'],
    });
}, 400);


const resetFilters = () => {
    filters.value = {
        'global': { value: null, matchMode: FilterMatchMode.CONTAINS },
        'name': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'email': { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
        'role': { value: null, matchMode: FilterMatchMode.EQUALS },
        'region_name': { value: null, matchMode: FilterMatchMode.EQUALS },
    };
    // Optionnel: déclencher une recherche pour réinitialiser les données du backend
    search();
};

const exportCSV = () => {
    dt.value.exportCSV();
};

const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    region_id: null,
    pointure: '',
    size: '',
    roles: [],
    profile_photo: null,
    profile_photo_preview: null,
});

const allColumns = computed(() => [
    { field: 'name', header: t('users.fields.name'), default: true },
    { field: 'email', header: 'Email', default: false },
    { field: 'role', header: t('users.fields.role'), default: true },
    { field: 'region_name', header: t('users.fields.region'), default: true },
    { field: 'created_at', header: t('users.fields.created_at'), default: true },
    { field: 'phone', header: t('users.fields.phone'), default: true },
]);

const selectedColumnFields = ref(allColumns.value.filter(col => col.default).map(c => c.field));
const displayedColumns = computed(() => allColumns.value.filter(col => selectedColumnFields.value.includes(col.field)));
const regionOptions = computed(() =>
    (props.regions || []).map(r => ({ label: r.designation, value: r.id }))
);

const stats = computed(() => {
    const data = props.users.data || [];
    // Enrichir les données utilisateur avec le nom de la région pour l'affichage et le filtrage
    data.forEach(user => {
        user.region_name = user.region?.designation || 'N/A';
    });

    const roleStats = (props.roles || []).reduce((acc, role) => {
        acc[role.name] = data.filter(u => u.roles.some(r => r.name === role.name)).length;
        return acc;
    }, {});

    return {
        total: props.users.total,
        ...roleStats
    };
});

const formattedUsers = computed(() => {
    // Assurez-vous que stats est calculé pour que region_name soit ajouté
    stats.value;
    return props.users.data;
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.profile_photo_preview = null;
    isModalOpen.value = true;
};

const openEdit = (user) => {
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.phone = user.phone;
    form.roles = user.roles.map(r => r.name);
    form.region_id = user.region_id;
    form.pointure = user.pointure;
    form.size = user.size;
    form.profile_photo_preview = user.profile_photo_url;
    isModalOpen.value = true;
};

const triggerFileInput = () => fileInput.value.click();

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) { // 2MB Max
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Image trop lourde (Max 2MB)', life: 3000 });
            return;
        }
        form.profile_photo = file;
        const reader = new FileReader();
        reader.onload = (event) => {
            form.profile_photo_preview = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removePhoto = () => {
    form.profile_photo = null;
    form.profile_photo_preview = null;
    if (fileInput.value) fileInput.value.value = null;
};

const submit = () => {
    // 1. Préparation de l'URL et des données
    const url = form.id ? route('user.update', form.id) : route('user.store');

    // On prépare l'objet à envoyer. On inclut _method pour que Laravel
    // comprenne qu'il s'agit d'un PUT même si on passe par du POST (requis pour les fichiers).
    const dataToSend = {
        ...form.data(),
        _method: form.id ? 'put' : 'post',
    };

    // 2. Utilisation de router
    router.visit(url, {
        method: 'post', // On utilise physiquement 'post' pour supporter FormData/Fichiers
        data: dataToSend,
        forceFormData: true, // Force l'envoi en FormData (indispensable pour les images)
        onBefore: () => {
            // Optionnel : form.processing = true;
        },
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({
                severity: 'success',
                summary: t('common.success'),
                detail: form.id ? t('users.toast.updateSuccess') : t('users.toast.createSuccess'),
                life: 3000
            });
            form.reset();
        },
        onError: (errors) => {
            // Avec router, il faut manuellement assigner les erreurs au formulaire
            // si vous voulez qu'elles s'affichent sous vos inputs
            form.setError(errors);

            console.error("Erreur lors de la sauvegarde de l'utilisateur", errors);
            const errorDetail = Object.values(errors).flat().join(' ; ');
            toast.add({
                severity: 'error',
                summary: t('common.error'),
                detail: errorDetail || t('users.toast.saveError'),
                life: 5000
            });
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

const impersonateUser = (user) => {
    router.post(route('users.impersonate', user.id), {}, {
        onSuccess: () => toast.add({ severity: 'warn', summary: 'Impersonation', detail: `Vous usurpez maintenant l'identité de ${user.name}`, life: 4000 })
    });
};

const onPage = (event) => {
    const params = { ...pickBy(filters.value), page: event.page + 1, per_page: event.rows };
    router.get(route('user.index'), params, {
        preserveState: true,
        replace: true,
        only: ['users', 'filters'],
    });
};

watch(
    () => [filters.value.global.value, filters.value.region_name.value],
    () => {
        search();
    }
);

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
                    <Button :label="t('users.actions.add')" icon="pi pi-plus"  raised @click="openCreate" class="rounded-lg font-bold" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div v-for="(val, key) in stats" :key="key" class="p-6 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center gap-5">
                     <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center">
                        <i class="pi pi-users text-2xl text-slate-500"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800">{{ val }}</div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ t(`users.stats.${key}`, { default: key }) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <DataTable ref="dt" :value="formattedUsers" v-model:selection="selectedUsers" dataKey="id" paginator :rows="users.per_page" :totalRecords="users.total" lazy
                    :rowsPerPageOptions="[10, 25, 50, 100]" @page="onPage"
                    removableSort stripedRows class="p-datatable-custom">
                    <template #header>
                        <div class="flex flex-wrap justify-between items-center gap-4 p-2 w-full">
                            <div class="flex items-center gap-2">
                                <IconField iconPosition="left">
                                    <InputIcon class="pi pi-search text-slate-400"/>
                                    <InputText v-model="filters['global'].value" :placeholder="t('users.searchPlaceholder')" class="w-full md:w-80 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white"/>
                                </IconField>
                                <Dropdown
                                    v-model="filters['region_name'].value"
                                    :options="regionOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Filtrer par région"
                                    :placeholder="t('users.filterByRegionPlaceholder')"
                                    class="w-full md:w-60 rounded-2xl border-slate-200 bg-slate-50/50 focus:bg-white" />
                            </div>

                            <div class="flex items-center gap-2">
                                <Button v-if="selectedUsers.length" :label="t('users.actions.deleteSelected')" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected" class="p-button-sm rounded-xl animate-fadein"/>
                                <Button icon="pi pi-download" text rounded severity="secondary" @click="exportCSV" v-tooltip.bottom="t('common.exportCsv')" />
                                <Button icon="pi pi-filter-slash" outlined severity="secondary" @click="resetFilters" class="rounded-xl" v-tooltip.bottom="t('common.resetFilters')" />
                                <Button icon="pi pi-cog" text rounded severity="secondary" @click="op.toggle($event)" v-tooltip.bottom="t('common.columns')" />
                            </div>
                        </div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column v-for="col in displayedColumns" :key="col.field" :field="col.field" :header="col.header" sortable>
                         <template #body="{ data, field }">
                            <template v-if="field === 'name'">
                                <div class="flex items-center gap-5 group cursor-pointer" @click="openEdit(data)">
                                    <div class="relative">
                                        <Avatar :image="data.profile_photo_url || null" :label="data.profile_photo_url ? '' : data.name?.charAt(0) || 'U'" shape="circle" size="xlarge"
                                            class="shadow-lg" :class="{'bg-slate-200 text-slate-700': !data.profile_photo_url}" />
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
                            <template v-else-if="field === 'region_name'">
                                <span class="text-slate-600 text-sm">{{ data.region_name }}</span>
                            </template>
                             <template v-else-if="field === 'created_at'">
                                <span class="text-slate-600 text-sm font-mono">{{ data[field] ? new Date(data[field]).toLocaleDateString() : '-' }}</span>
                            </template>
                            <template v-else>
                                {{ data[field] }}
                            </template>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" alignFrozen="right" frozen class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" class="hover:bg-blue-50" />
                                <Button v-if="canImpersonate && data.id !== authUser.id" icon="pi pi-user-edit" text rounded severity="warn" @click="impersonateUser(data)" v-tooltip.left="t('users.actions.impersonate')" class="hover:bg-yellow-50" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteUser(data)" v-tooltip.left="t('common.delete')" class="hover:bg-red-50" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <OverlayPanel ref="op" class="p-4">
            <div class="font-semibold mb-3">{{ t('common.columnSelector.title') }}</div>
            <MultiSelect
                v-model="selectedColumnFields"
                :options="allColumns"
                optionLabel="header"
                optionValue="field"
                display="chip"
                :placeholder="t('common.columnSelector.placeholder')"
                class="w-full max-w-xs"  />
        </OverlayPanel>
        <Dialog v-model:visible="isModalOpen" modal position="right" :header="false" :closable="false" :style="{ width: '60vw' }" class="quantum-dialog" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }" :draggable="false">
            <!-- Loading Overlay -->
            <div v-if="form.processing" class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-50 rounded-3xl">
                <i class="pi pi-spin pi-spinner text-5xl text-primary-500"></i>
            </div>

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
                                    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 group-hover:scale-110"
                                        :style="{ backgroundImage: form.profile_photo_preview ? `url(${form.profile_photo_preview})` : 'none' }">
                                        <div v-if="!form.profile_photo_preview" class="w-full h-full flex items-center justify-center">
                                            <span class="text-[14rem] font-black text-slate-200 uppercase select-none">
                                                {{ form.name ? form.name[0] : 'U' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div @click="triggerFileInput" class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 group-hover:opacity-100 transition-all duration-300 cursor-pointer backdrop-blur-[2px] z-10">
                                        <div class="w-24 h-24 bg-white/95 text-slate-900 rounded-full flex items-center justify-center shadow-2xl scale-75 group-hover:scale-100 transition-transform duration-500 border-8 border-primary-500/10">
                                            <i class="pi pi-camera text-4xl"></i>
                                        </div>
                                    </div>
                                    <button v-if="form.profile_photo_preview" type="button" @click.stop="removePhoto"
                                        class="absolute top-5 right-5 w-12 h-12 bg-white/90 text-red-500 rounded-2xl flex items-center justify-center shadow-xl hover:bg-red-500 hover:text-white hover:scale-110 transition-all z-20 border border-white/50 backdrop-blur-md"
                                        v-tooltip.left="t('users.actions.removePhoto')">
                                        <i class="pi pi-trash text-xl"></i>
                                    </button>
                                </div>
                                <input type="file" ref="fileInput" class="hidden" @change="handlePhotoChange" accept="image/*" :aria-label="t('users.uploadPhotoAriaLabel')" />
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
                            <span class="v11-header-label">{{ t('users.form.generalInfo') }}</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="v11-label">{{ t('users.fields.fullName') }}</label>
                                    <InputText id="name" v-model="form.name" class="v11-input-ultimate" :placeholder="t('users.placeholders.fullName')" required :invalid="!!form.errors.name" />
                                    <small class="p-error">{{ form.errors.name }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="email" class="v11-label">{{ t('users.fields.workEmail') }}</label>
                                    <InputText id="email" v-model="form.email" type="email" class="v11-input-ultimate" :placeholder="t('users.placeholders.workEmail')" required :invalid="!!form.errors.email" />
                                    <small class="p-error">{{ form.errors.email }}</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="v11-header-label">{{ t('users.form.assignmentAndRole') }}</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="role" class="v11-label">{{ t('users.fields.role') }}</label>
                                    <MultiSelect v-model="form.roles" :options="props.roles" optionLabel="name" optionValue="name" :placeholder="t('users.placeholders.selectRole')" display="chip" class="v11-dropdown-ultimate" id="role" :invalid="!!form.errors.roles" />
                                    <small class="p-error">{{ form.errors.roles }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="phone" class="v11-label">{{ t('users.fields.phone') }}</label>
                                    <InputText id="phone" v-model="form.phone" class="v11-input-ultimate" :placeholder="t('users.placeholders.phone')" :invalid="!!form.errors.phone" />
                                    <small class="p-error">{{ form.errors.phone }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="region" class="v11-label">{{ t('users.fields.region') }}</label>
                                    <Dropdown v-model="form.region_id" :options="regionOptions" optionLabel="label" optionValue="value" :placeholder="t('users.placeholders.region')" class="v11-dropdown-ultimate" id="region" :invalid="!!form.errors.region_id" />
                                    <small class="p-error">{{ form.errors.region_id }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="v11-header-label">{{ t('users.form.safetyData') }}</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                <InputText id="pointure" v-model="form.pointure" class="v11-input-ultimate !bg-white" :placeholder="t('users.placeholders.shoeSize')" :invalid="!!form.errors.pointure" />
                                <small class="p-error">{{ form.errors.pointure }}</small>
                                <InputText id="size" v-model="form.size" class="v11-input-ultimate !bg-white" :placeholder="t('users.placeholders.clothingSize')" :invalid="!!form.errors.size" />
                                <small class="p-error">{{ form.errors.size }}</small>
                            </div>
                        </div>

                        <div class="p-6 bg-blue-50/30 rounded-3xl border border-blue-100">
                            <span class="v11-header-label !text-blue-700">{{ t('users.form.accountSecurity') }}</span>
                            <p v-if="form.id" class="text-xs text-slate-500 mt-2">{{ t('users.form.passwordHelp') }}</p>
                            <div class="grid grid-cols-2 gap-6 mt-4">
                                <IconField>
                                    <InputText v-model="form.password" :type="showPassword ? 'text' : 'password'" :placeholder="t('users.placeholders.password')" class="v11-input-ultimate !bg-white w-full" :invalid="!!form.errors.password" />
                                    <InputIcon class="cursor-pointer" :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'" @click="showPassword = !showPassword" />
                                </IconField>
                                <IconField>
                                    <InputText v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" :placeholder="t('users.placeholders.confirmPassword')" class="v11-input-ultimate !bg-white w-full" />
                                    <InputIcon class="cursor-pointer" :class="showConfirmPassword ? 'pi pi-eye-slash' : 'pi pi-eye'" @click="showConfirmPassword = !showConfirmPassword" />
                                </IconField>
                            </div>
                             <small class="p-error" v-if="form.errors.password">{{ form.errors.password }}</small>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center w-full px-2 py-4">
                    <Button :label="t('common.cancel')" icon="pi pi-times" text severity="secondary" @click="isModalOpen = false" class="font-bold uppercase text-[10px] tracking-widest" /><Button :label="form.id ? t('common.save') : t('common.create')"  icon="pi pi-check-circle"
                            class="px-10 h-14 rounded-2xl shadow-xl shadow-primary-100 font-black uppercase tracking-widest text-xs"
                            @click="submit" :loading="form.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<style lang="scss">

.p-datatable-thead > tr > th {
    background: #fdfdfd;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    @apply text-slate-400 font-black text-[10px] uppercase tracking-[0.15em] py-6 px-4;
}

.p-datatable-tbody > tr {
    transition: all 0.2s;
    &:hover {
        background: #f8faff !important;
    }
}

.animate-fadein {
    animation: fadeIn 0.3s ease-in-out;
}
</style>
