<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import { useI18n } from 'vue-i18n';

// --- COMPOSANTS & LAYOUT ---
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

// PrimeVue Components
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const props = defineProps({
    technicians: Object,
    regions: Array,
    filters: Object,
});

// --- ÉTAT DU COMPOSANT ---
const selectedTechs = ref([]);
const fileInput = ref(null);
const loading = ref(false);
const isModalOpen = ref(false);
const visibleColumns = ref(['name', 'fonction', 'region', 'numero']);

// --- FILTRES (Structure robuste du premier code) ---
const filters = ref({
    global: { value: props.filters?.search || null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    fonction: { value: null, matchMode: FilterMatchMode.EQUALS },
    region: { value: null, matchMode: FilterMatchMode.EQUALS },
    email: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    numero: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }] },
});

// --- STATISTIQUES ---
const stats = computed(() => ({
    total: props.technicians?.total || 0,
    regions: props.regions?.length || 0,
}));

// --- GESTION DU RECHARGEMENT CÔTÉ SERVEUR ---
let timeoutId = null;
watch(() => filters.value.global.value, (newValue) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(
            route('technicians.index'),
            { search: newValue },
            { preserveState: true, replace: true, only: ['technicians'] }
        );
    }, 400);
});

// --- OPTIONS ---
const fonctionOptions = [
    { label: 'Superviseur', value: 'Superviseur' },
    { label: 'Technicien Chef', value: 'Technicien Chef' },
    { label: 'Technicien Journalier', value: 'Technicien Journalier' }
];

const regionOptions = computed(() =>
    (props.regions || []).map(r => ({ label: r.designation, value: r.designation }))
);

const allColumnOptions = ref([
    { field: 'fonction', header: 'Fonction' },
    { field: 'region', header: 'Région' },
    { field: 'numero', header: 'Téléphone' },
    { field: 'email', header: 'Email' }
]);

// --- FORMULAIRE ---
const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    fonction: null,
    numero: '',
    region: '',
    pointure: '',
    size: '',
    profile_photo: null,
    profile_photo_preview: null,
    status: 'active'
});

// --- LOGIQUE PHOTO ---
const triggerFileInput = () => fileInput.value.click();

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
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

// --- ACTIONS CRUD ---
const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.profile_photo_preview = null;
    isModalOpen.value = true;
};

const openEdit = (tech) => {
    form.clearErrors();
    form.id = tech.id;
    form.name = tech.name;
    form.email = tech.email;
    form.fonction = tech.fonction;
    form.numero = tech.numero;
    form.region = tech.region;
    form.pointure = tech.pointure;
    form.size = tech.size;
    form.profile_photo_preview = tech.profile_photo_url;
    isModalOpen.value = true;
};

const submit = () => {
    const url = form.id ? route('technicians.update', form.id) : route('technicians.store');
    form.post(url, {
        forceFormData: true,
        onSuccess: () => {
            isModalOpen.value = false;
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Opération réussie', life: 3000 });
        },
    });
};

// --- SUPPRESSION ---
const deleteTech = (tech) => {
    confirm.require({
        message: `Voulez-vous supprimer définitivement ${tech.name} ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('technicians.destroy', tech.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Technicien retiré', life: 3000 })
            });
        }
    });
};

const confirmDeleteSelected = () => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedTechs.value.length} techniciens sélectionnés ?`,
        header: 'Suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            const ids = selectedTechs.value.map(t => t.id);
            router.post(route('technicians.bulkDestroy'), { ids }, {
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} techniciens supprimés.`, life: 3000 });
                    selectedTechs.value = [];
                }
            });
        }
    });
};

const exportData = (type) => {
    window.location.href = route('technicians.export', { type, search: filters.value.global.value });
};
</script>


<template>
    <AppLayout title="Ultimate Technicians Manager">
        <Head title="Techniciens Ultimate V11" />
        <Toast />
        <ConfirmDialog />

        <div class="p-4 md:p-8 bg-[#F8FAFC] min-h-screen">

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="pi pi-users text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Gestion des Techniciens</h1>
                        <p class="text-slate-500 text-sm">Liste et administration des techniciens.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-slate-100">
                    <Button icon="pi pi-file-excel" severity="secondary" text class="rounded-full" @click="exportData('excel')" v-tooltip.bottom="'Export Excel'" />
                    <Button icon="pi pi-file-pdf" severity="secondary" text class="rounded-full" @click="exportData('pdf')" v-tooltip.bottom="'Export PDF'" />
                    <div class="h-8 w-[1px] bg-slate-100 mx-2"></div>
                    <Button label="Nouveau Technicien" icon="pi pi-plus" severity="primary" raised @click="openCreate" class="rounded-lg font-bold" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <span class="text-sm text-gray-500">Total Techniciens</span>
                    <p class="text-2xl font-bold">{{ technicians.total }}</p>
                </div>
                 <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <span class="text-sm text-gray-500">Régions Couvertes</span>
                    <p class="text-2xl font-bold">{{ regions.length }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 overflow-hidden">

                <div class="p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <IconField iconPosition="left" class="w-full md:w-96">
                            <InputIcon class="pi pi-search text-gray-400" />
                            <InputText v-model="filters['global'].value" placeholder="Recherche globale..." class="w-full md:w-80 border-none bg-slate-50 rounded-xl" />
                        </IconField>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button icon="pi pi-columns" text severity="secondary" @click="(e) => opColumns.toggle(e)" class="rounded-xl border border-slate-200" />
                        <Button v-if="selectedTechs.length" label="Supprimer la sélection" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected"
                                class="p-button-sm rounded-xl" />
                    </div>
                </div>

                <DataTable :value="technicians.data" v-model:selection="selectedTechs" dataKey="id" :loading="loading"
                    v-model:filters="filters" filterDisplay="menu" :globalFilterFields="['name', 'email', 'fonction', 'region', 'numero']"
                    paginator :rows="10" :rowsPerPageOptions="[10, 25, 50]" removableSort stripedRows
                    class="p-datatable-custom">

                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                    <Column field="name" header="Technicien" sortable filter>
                        <template #body="{ data }">
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
                        <template #filter="{filterModel,filterCallback}">
                            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"/>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('fonction')" field="fonction" header="Fonction" sortable filter>
                        <template #body="{ data }">
                            <Tag :value="data.fonction" severity="secondary" />
                        </template>
                        <template #filter="{filterModel,filterCallback}">
                            <Dropdown v-model="filterModel.value" @change="filterCallback()" :options="fonctionOptions" optionLabel="label" optionValue="value" placeholder="Fonction" showClear style="min-width: 12rem" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('region')" field="region" header="Région" sortable filter>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-gray-400"></i>
                                <span class="font-semibold text-slate-600">{{ data.region }}</span>
                            </div>
                        </template>
                        <template #filter="{filterModel,filterCallback}">
                            <Dropdown v-model="filterModel.value" @change="filterCallback()" :options="regionOptions" optionLabel="label" optionValue="value" placeholder="Région" showClear style="min-width: 12rem" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('numero')" field="numero" header="Téléphone" sortable filter>
                        <template #body="{ data }">
                            <span class="font-mono text-sm">{{ data.numero }}</span>
                        </template>
                        <template #filter="{filterModel,filterCallback}">
                            <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par téléphone"/>
                        </template>
                    </Column>


                    <Column header="Expertise" class="text-center" v-if="visibleColumns.includes('pointure')">
                        <template #body>
                            <div class="flex gap-1 justify-center">
                                <i class="pi pi-star-fill text-yellow-400 text-[10px]" v-for="i in 3" :key="i"></i>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" class="hover:bg-blue-50" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteTech(data)" class="hover:bg-red-50" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>


        <Dialog v-model:visible="isModalOpen" modal position="right" :header="form.id ? 'Fiche Technicien' : 'Nouvel Enrôlement'"
            :style="{ width: '60vw' }" class="v11-dialog-ultimate" :draggable="false">

            <form @submit.prevent="submit" class="p-4 space-y-8">
                <div class="grid grid-cols-12 gap-10">

                 <div class="col-span-12 md:col-span-4">
        <div class="sticky top-0">
            <div class="relative group bg-white rounded-[2.5rem] p-3 border border-slate-200 shadow-2xl transition-all duration-500 hover:border-emerald-300">

               <div class="relative w-full aspect-square overflow-hidden rounded-[2.5rem] bg-slate-100 shadow-2xl border-4 border-white group" :class="{'border-red-500': form.errors.profile_photo}">

    <div
        class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 group-hover:scale-110"
        :style="{
            backgroundImage: form.profile_photo_preview ? `url(${form.profile_photo_preview})` : 'none'
        }"
    >
        <div v-if="!form.profile_photo_preview" class="w-full h-full flex items-center justify-center">
            <span class="text-[14rem] font-black text-slate-200 uppercase select-none">
                {{ form.name ? form.name.charAt(0) : 'U' }}
            </span>
        </div>
    </div>

    <div
        @click="triggerFileInput"
        class="absolute inset-0 flex items-center justify-center bg-black/20 opacity-0 group-hover:opacity-100 transition-all duration-300 cursor-pointer backdrop-blur-[2px] z-10"
    >
        <div class="w-24 h-24 bg-white/95 text-slate-900 rounded-full flex items-center justify-center shadow-2xl scale-75 group-hover:scale-100 transition-transform duration-500 border-8 border-emerald-500/10">
            <i class="pi pi-camera text-4xl"></i>
        </div>
    </div>

    <button
        v-if="form.profile_photo_preview"
        type="button"
        @click.stop="removePhoto"
        class="absolute top-5 right-5 w-12 h-12 bg-white/90 text-red-500 rounded-2xl flex items-center justify-center shadow-xl hover:bg-red-500 hover:text-white hover:scale-110 transition-all z-20 border border-white/50 backdrop-blur-md"
        v-tooltip.left="'Supprimer la photo'"
    >
        <i class="pi pi-trash text-xl"></i>
    </button>

</div>

                <input type="file" ref="fileInput" class="hidden" @change="handlePhotoChange" accept="image/*" />

                <div class="py-6 text-center">
                    <h3 class="font-black text-slate-900 text-2xl tracking-tighter leading-none mb-2">
                        {{ form.name || 'Nouveau Profil' }}
                    </h3>
                    <span class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.3em] bg-emerald-50 px-4 py-1.5 rounded-full">
                        Identité Visuelle
                    </span>
                </div>
            </div>

            <div class="mt-4 p-5 bg-slate-900 rounded-[2rem] shadow-xl border-t border-slate-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative flex items-center justify-center">
                            <div class="absolute w-full h-full bg-emerald-500/20 rounded-full animate-ping"></div>
                            <div class="w-3 h-3 bg-emerald-400 rounded-full relative shadow-[0_0_15px_rgba(52,211,153,1)]"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Compte Actif</span>
                    </div>
                    <i class="pi pi-verified text-emerald-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

                    <div class="col-span-12 md:col-span-8 space-y-8">
                        <div>
                            <span class="v11-header-label">Informations Générales</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="v11-label">Nom complet</label>
                                    <InputText id="name" v-model="form.name" class="v11-input-ultimate" placeholder="ex: Jean Dupont" required />
                                    <small class="p-error" v-if="form.errors.name">{{ form.errors.name }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="email" class="v11-label">Email Professionnel</label>
                                    <InputText id="email" v-model="form.email" type="email" class="v11-input-ultimate" placeholder="tech@entreprise.com" required />
                                    <small class="p-error" v-if="form.errors.email">{{ form.errors.email }}</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="v11-header-label">Affectation & Rôle</span>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div class="flex flex-col gap-2">
                                    <label for="fonction" class="v11-label">Fonction</label>
                                    <Dropdown v-model="form.fonction" :options="fonctionOptions" optionLabel="label" optionValue="value"
                                        placeholder="Sélectionner un rôle" class="v11-dropdown-ultimate" id="fonction" />
                                    <small class="p-error" v-if="form.errors.fonction">{{ form.errors.fonction }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="region" class="v11-label">Région</label>
                                    <Dropdown v-model="form.region" :options="regionOptions" optionLabel="label" optionValue="value"
                                        placeholder="Zone d'intervention" class="v11-dropdown-ultimate" id="region" />
                                    <small class="p-error" v-if="form.errors.region">{{ form.errors.region }}</small>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="v11-header-label">Données de Sécurité (EPI)</span>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                <div class="flex flex-col gap-2">
                                    <label for="numero" class="v11-label">Téléphone</label>
                                    <InputText id="numero" v-model="form.numero" class="v11-input-ultimate !bg-white" />
                                    <small class="p-error" v-if="form.errors.numero">{{ form.errors.numero }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="pointure" class="v11-label">Pointure</label>
                                    <InputText id="pointure" v-model="form.pointure" class="v11-input-ultimate !bg-white" />
                                    <small class="p-error" v-if="form.errors.pointure">{{ form.errors.pointure }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="size" class="v11-label">Taille (Vêtement)</label>
                                    <InputText id="size" v-model="form.size" class="v11-input-ultimate !bg-white" />
                                    <small class="p-error" v-if="form.errors.size">{{ form.errors.size }}</small>
                                </div>
                            </div>
                        </div>

                        <div v-if="!form.id" class="p-6 bg-blue-50/30 rounded-3xl border border-blue-100">
                            <span class="v11-header-label !text-blue-700">Sécurité du compte</span>
                            <div class="grid grid-cols-2 gap-6 mt-4">
                                <InputText v-model="form.password" type="password" placeholder="Mot de passe" class="v11-input-ultimate !bg-white" />
                                <small class="p-error" v-if="form.errors.password">{{ form.errors.password }}</small>
                                <InputText v-model="form.password_confirmation" type="password" placeholder="Confirmer" class="v11-input-ultimate !bg-white" />
                                <small class="p-error" v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-12 pt-8 border-t border-slate-100">
                    <Button label="Fermer sans enregistrer" severity="secondary" outlined @click="isModalOpen = false" class="rounded-2xl px-10" />
                    <Button :label="form.id ? 'Valider les modifications' : 'Créer le profil'" severity="primary" raised :loading="form.processing" @click="submit"
                            class="rounded-2xl px-12 shadow-xl shadow-indigo-200" />
                </div>
            </form>
        </Dialog>


        <OverlayPanel ref="opColumns">
            <div class="p-4 w-72 flex flex-col gap-4">
                <span class="font-black text-xs uppercase tracking-widest border-b pb-2">Colonnes Affichées</span>
                <MultiSelect v-model="visibleColumns" :options="allColumns" optionLabel="header" optionValue="field"
                    display="chip" class="w-full border-none bg-slate-50" />
            </div>
        </OverlayPanel>

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
/* Scrollbar invisible pour le look moderne */
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
