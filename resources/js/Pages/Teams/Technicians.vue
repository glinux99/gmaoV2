<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import debounce from 'lodash/debounce';

// --- COMPOSANTS DE BASE ---
import AppLayout from '@/sakai/layout/AppLayout.vue';

// --- PRIME VUE ULTIMATE ---
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import { useToast } from "primevue/usetoast";
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";
import MultiSelect from 'primevue/multiselect';
import OverlayPanel from 'primevue/overlaypanel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import Sidebar from 'primevue/sidebar';

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const props = defineProps({
    technicians: Object,
    regions: Array,
    filters: Object,
});

// --- ÉTAT DU COMPOSANT ---
const search = ref(props.filters?.search || '');
const showFilters = ref(false);
const opColumns = ref();
const selectedTechs = ref([]);
const fileInput = ref(null);
const loading = ref(false);

// Filtres avancés
const filterForm = ref({
    region: props.filters?.region || null,
    fonction: props.filters?.fonction || null,
});

const allColumns = ref([
    { field: 'name', header: 'Technicien' },
    { field: 'fonction', header: 'Fonction' },
    { field: 'region', header: 'Région' },
    { field: 'numero', header: 'Téléphone' },
    { field: 'email', header: 'Email' },
    { field: 'pointure', header: 'Équipement' },
]);

const visibleColumns = ref(['name', 'fonction', 'region', 'numero']);

// --- FORMULAIRE ULTIMATE ---
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
const isModalOpen = ref(false);

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.profile_photo_preview = null;
    form.profile_photo = null; // Ensure the actual file is also reset
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

const confirmDeleteSelected = () => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer les ${selectedTechs.value.length} techniciens sélectionnés ?`,
        header: 'Confirmation de suppression multiple',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Supprimer',
        rejectLabel: 'Annuler',
        accept: () => {
            deleteSelected();
        }
    });
};

const deleteSelected = () => {
    const ids = selectedTechs.value.map(t => t.id);
    router.post(route('technicians.bulkDestroy'), { ids }, {
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: `${ids.length} techniciens supprimés.`, life: 3000 });
            selectedTechs.value = [];
        },
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'La suppression multiple a échoué.', life: 3000 })
    });
};

const deleteTech = (tech) => {
    confirm.require({
        message: `Voulez-vous supprimer définitivement ${tech.name} ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('technicians.destroy', tech.id), {
                onSuccess: () => toast.add({ severity: 'info', summary: 'Supprimé', detail: 'Technicien retiré', life: 3000 })
            });
        }
    });
};

// --- FILTRAGE & RECHERCHE ---
const updateFilters = debounce(() => {
    loading.value = true;
    router.get(route('technicians.index'), {
        search: search.value,
        region: filterForm.value.region,
        fonction: filterForm.value.fonction,
    }, {
        preserveState: true,
        replace: true,
        onFinish: () => loading.value = false
    });
}, 500);

const resetFilters = () => {
    filterForm.value = { region: null, fonction: null };
    search.value = '';
    updateFilters();
};

const exportData = (type) => {
    window.location.href = route('technicians.export', { type, ...filterForm.value, search: search.value });
};

// Options
const fonctionOptions = [
    { label: 'Superviseur', value: 'Superviseur' },
    { label: 'Technicien Chef', value: 'Technicien Chef' },
    { label: 'Technicien Journalier', value: 'Technicien Journalier' }
];
const regionOptions = computed(() => (props.regions || []).map(r => ({ label: r.designation, value: r.designation })));
</script>

<template>
    <AppLayout title="Ultimate Technicians Manager">
        <Head title="Techniciens Ultimate V11" />
        <Toast />
        <ConfirmDialog />

        <div class="p-4 md:p-8 bg-[#F8FAFC] min-h-screen">

            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-10 gap-6">
                <div class="flex items-center gap-5">
                    <div class="w-16 h-16 bg-emerald-600 rounded-[2rem] flex items-center justify-center shadow-2xl shadow-emerald-200 rotate-6 hover:rotate-0 transition-all duration-500">
                        <i class="pi pi-users text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic">V11 Ultimate</h1>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-[0.3em]">Master Database Control</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 bg-white p-2.5 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-white">
                    <Button icon="pi pi-file-excel" severity="secondary" text class="rounded-full" @click="exportData('excel')" v-tooltip.bottom="'Export Excel'" />
                    <Button icon="pi pi-file-pdf" severity="secondary" text class="rounded-full" @click="exportData('pdf')" v-tooltip.bottom="'Export PDF'" />
                    <div class="h-8 w-[1px] bg-slate-100 mx-2"></div>
                    <Button label="Enrôler Technicien" icon="pi pi-plus-circle" severity="success" raised @click="openCreate" class="rounded-2xl font-black px-8 py-3 bg-emerald-600 border-none" />
                </div>
            </div>

            <div class="bg-white rounded-[3rem] shadow-2xl shadow-slate-200/60 border border-white overflow-hidden">

                <div class="p-8 flex flex-wrap items-center justify-between gap-6 bg-gradient-to-r from-white to-slate-50/50">
                    <div class="flex items-center gap-4 w-full md:w-auto">
                        <IconField iconPosition="left" class="w-full md:w-96">
                            <InputIcon class="pi pi-search text-emerald-500" />
                            <InputText v-model="search" placeholder="Rechercher par nom, email, matricule..." @input="updateFilters"
                                class="w-full border-none bg-white shadow-inner rounded-2xl py-4 focus:ring-2 focus:ring-emerald-500/10" />
                        </IconField>
                        <Button icon="pi pi-sliders-v" severity="secondary" text class="bg-white shadow-sm border border-slate-100 rounded-2xl p-4" @click="showFilters = true" />
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xs font-black text-slate-400 uppercase mr-2">{{ selectedTechs.length }} sélectionné(s)</span>
                        <Button icon="pi pi-columns" text severity="secondary" @click="(e) => opColumns.toggle(e)" class="rounded-xl border border-slate-200" />
                        <Button v-if="selectedTechs.length" label="Supprimer la sélection" icon="pi pi-trash" severity="danger" @click="confirmDeleteSelected"
                                class="p-button-sm rounded-xl" />
                    </div>
                </div>

                <DataTable :value="technicians.data" v-model:selection="selectedTechs" dataKey="id"
                    class="p-datatable-v11-ultimate" scrollable scrollHeight="600px" :scrollable="true">

                    <Column selectionMode="multiple" headerStyle="width: 4rem" class="pl-8"></Column>

                    <Column header="Profil Technicien" minWidth="300px">
                        <template #body="{ data }">
                            <div class="flex items-center gap-5 group cursor-pointer" @click="openEdit(data)">
                                <div class="relative">
                                    <Avatar :image="data.profile_photo_url || null" :label="data.profile_photo_url ? '' : data.name[0]" shape="circle" size="xlarge"
                                        class="shadow-lg border-2 border-white group-hover:scale-110 transition-transform duration-300" :class="{'bg-slate-200 text-slate-700': !data.profile_photo_url}" />
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full"></div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 text-lg leading-tight">{{ data.name }}</span>
                                    <span class="text-xs text-emerald-600 font-bold uppercase tracking-tighter">{{ data.email }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('fonction')" field="fonction" header="Affectation" minWidth="200px">
                        <template #body="{ data }">
                            <Tag :value="data.fonction" severity="secondary" class="bg-slate-100 text-slate-600 font-black rounded-lg px-3" />
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('region')" field="region" header="Zone">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-map-marker text-red-400"></i>
                                <span class="font-bold text-slate-600">{{ data.region }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="visibleColumns.includes('numero')" field="numero" header="Contact">
                        <template #body="{ data }">
                            <span class="font-mono text-sm bg-blue-50 text-blue-600 px-2 py-1 rounded-md">{{ data.numero }}</span>
                        </template>
                    </Column>

                    <Column header="Expertise" class="text-center">
                        <template #body>
                            <div class="flex gap-1 justify-center">
                                <i class="pi pi-star-fill text-yellow-400 text-[10px]" v-for="i in 3" :key="i"></i>
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen class="text-right pr-8">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="openEdit(data)" class="hover:bg-blue-50" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteTech(data)" class="hover:bg-red-50" />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <Paginator :rows="technicians.per_page" :totalRecords="technicians.total" @page="onPage"
                    :first="(technicians.current_page - 1) * technicians.per_page"
                    template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    :rowsPerPageOptions="[10, 20, 50]" class="py-8 bg-slate-50/50" />
            </div>
        </div>

        <Dialog v-model:visible="isModalOpen" modal position="right" :header="form.id ? 'Fiche Technicien' : 'Nouvel Enrôlement'"
            :style="{ width: '60vw' }" class="v11-dialog-ultimate" :draggable="false">

            <form @submit.prevent="submit" class="p-4 space-y-8">
                <div class="grid grid-cols-12 gap-10">

                 <div class="col-span-12 md:col-span-4">
        <div class="sticky top-0">
            <div class="relative group bg-white rounded-[2.5rem] p-3 border border-slate-200 shadow-2xl transition-all duration-500 hover:border-emerald-300">

               <div class="relative w-full aspect-square overflow-hidden rounded-[2.5rem] bg-slate-100 shadow-2xl border-4 border-white group">

    <div
        class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 group-hover:scale-110"
        :style="{
            backgroundImage: form.profile_photo_preview ? `url(${form.profile_photo_preview})` : 'none'
        }"
    >
        <div v-if="!form.profile_photo_preview" class="w-full h-full flex items-center justify-center">
            <span class="text-[14rem] font-black text-slate-200 uppercase select-none">
                {{ form.name ? form.name[0] : 'U' }}
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

        <Sidebar v-model:visible="showFilters" position="right" class="w-full md:w-96">
            <template #header>
                <div class="flex items-center gap-3">
                    <i class="pi pi-filter-fill text-emerald-500"></i>
                    <span class="font-black text-xl uppercase italic">Filtres Avancés</span>
                </div>
            </template>
            <div class="flex flex-col gap-8 mt-10">
                <div class="flex flex-col gap-3">
                    <label class="v11-label">Par Région</label>
                    <Dropdown v-model="filterForm.region" :options="regionOptions" optionLabel="label" optionValue="value"
                        placeholder="Toutes les régions" showClear class="v11-dropdown-ultimate" />
                </div>
                <div class="flex flex-col gap-3">
                    <label class="v11-label">Par Fonction</label>
                    <Dropdown v-model="filterForm.fonction" :options="fonctionOptions" optionLabel="label" optionValue="value"
                        placeholder="Toutes les fonctions" showClear class="v11-dropdown-ultimate" />
                </div>
                <div class="mt-auto flex flex-col gap-3">
                    <Button label="Appliquer les filtres" severity="primary" raised @click="updateFilters" class="justify-center" />
                    <Button label="Réinitialiser tout" severity="secondary" outlined @click="resetFilters" class="justify-center" />
                </div>
            </div>
        </Sidebar>

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
/* --- ULTIMATE V11 DESIGN SYSTEM --- */

.p-datatable-v11-ultimate {
    .p-datatable-thead > tr > th {
        @apply bg-transparent text-slate-400 font-black text-[10px] uppercase tracking-[0.2em] py-8 border-b border-slate-100;
    }
    .p-datatable-tbody > tr {
        @apply transition-all duration-500 border-b border-slate-50;
        &:hover { @apply bg-emerald-50/30 scale-[1.002] shadow-sm; }
        td { @apply py-6 text-sm font-semibold text-slate-700; }
    }
}

.v11-label {
    @apply text-[10px] font-black text-slate-400 uppercase tracking-widest;
}

.v11-header-label {
    @apply text-xs font-black text-slate-900 uppercase tracking-tighter border-l-4 border-emerald-500 pl-3;
}

.v11-input-ultimate {
    @apply bg-slate-50 border-slate-200/60 rounded-2xl py-4 px-6 text-sm font-bold text-slate-700 transition-all w-full placeholder:text-slate-300;
    &:focus { @apply bg-white ring-4 ring-emerald-500/5 border-emerald-500 shadow-xl shadow-emerald-100/20; }
}

.v11-dropdown-ultimate {
    @apply bg-slate-50 border-slate-200/60 rounded-2xl w-full transition-all py-1 px-2;
    .p-dropdown-label { @apply font-bold text-sm text-slate-700 py-3; }
    &.p-focus { @apply ring-4 ring-emerald-500/5 border-emerald-500 bg-white; }
}

.v11-dialog-ultimate {
    @apply rounded-[4rem] overflow-hidden border-none;
    .p-dialog-header {
        .p-dialog-header-icon {
            @apply w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center hover:bg-slate-200 transition-colors;
            .pi { @apply text-slate-500 text-lg; }
        }
        @apply px-12 pt-12 border-none;
        .p-dialog-title { @apply font-black text-4xl text-slate-900 tracking-tighter italic; }
    }
    .p-dialog-content { @apply px-12 pb-12 scrollbar-hide; }
}

.p-sidebar {
    @apply rounded-l-[3rem] shadow-2xl;
    .p-sidebar-header { @apply px-8 pt-8; }
    .p-sidebar-content { @apply px-8; }
}

/* Scrollbar invisible pour le look moderne */
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>
