<template>
    <AppLayout>
        <Head title="Gestion des donateurs - APROJED" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-900/50 to-teal-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-emerald-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-teal-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module Donateurs" severity="success" class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Gestion des donateurs</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Suivez les contributions, contactez les donateurs et gérez les reçus fiscaux.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="bg-slate-800/60 backdrop-blur-xl border border-slate-700/50 p-3 rounded-2xl">
                            <span class="text-white text-sm font-bold">Total des dons : </span>
                            <span class="text-emerald-400 text-xl font-black">{{ totalDonationsFormatted }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                    <!-- Barre d'outils -->
                    <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex flex-1 flex-wrap items-center gap-3">
                            <span class="p-input-icon-left w-full sm:w-72 lg:w-80">
                                <i class="pi pi-search text-slate-400" />
                                <InputText
                                    v-model="search"
                                    placeholder="Rechercher nom, email..."
                                    class="w-full rounded-xl bg-white border-slate-200"
                                    @keyup.enter="performSearch"
                                />
                            </span>
                            <Dropdown
                                v-model="filterStatus"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Statut contact"
                                class="w-full sm:w-40"
                                showClear
                            />
                        </div>
                        <Button icon="pi pi-plus" label="Nouveau donateur" class="bg-emerald-600 hover:bg-emerald-700 border-none shadow-lg shadow-emerald-500/30 text-white font-bold w-full sm:w-auto px-6" @click="openNewDonor" />
                    </div>

                    <!-- Tableau des donateurs -->
                    <DataTable
                        :value="donorsList"
                        v-model:selection="selectedDonors"
                        dataKey="id"
                        :paginator="true"
                        :rows="10"
                        :filters="filters"
                        responsiveLayout="scroll"
                        class="custom-table"
                        stripedRows
                        @row-reorder="onRowReorder"
                        reorderableRows
                    >
                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-16 text-center">
                                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
                                    <i class="pi pi-users text-4xl text-slate-300"></i>
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 mb-2">Aucun donateur</h3>
                                <p class="text-slate-500 max-w-md mb-6">Commencez par ajouter un premier donateur à votre base.</p>
                                <Button label="Ajouter un donateur" icon="pi pi-plus" class="p-button-outlined p-button-lg rounded-xl" @click="openNewDonor" />
                            </div>
                        </template>

                        <Column :rowReorder="true" headerStyle="width: 3rem" :reorderableColumn="false" />
                        <Column field="full_name" header="Nom complet" sortable style="min-width: 14rem">
                            <template #body="{ data }">
                                <div class="font-bold text-slate-800">{{ data.first_name }} {{ data.last_name }}</div>
                            </template>
                        </Column>
                        <Column field="email" header="Email" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <a :href="`mailto:${data.email}`" class="text-emerald-600 hover:underline">{{ data.email }}</a>
                            </template>
                        </Column>
                        <Column field="phone" header="Téléphone" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span>{{ data.phone || '—' }}</span>
                            </template>
                        </Column>
                        <Column field="amount" header="Montant (USD)" sortable style="min-width: 8rem; text-align: right;">
                            <template #body="{ data }">
                                <span class="font-mono font-bold text-emerald-700">{{ data.amount?.toLocaleString() || 0 }} USD</span>
                            </template>
                        </Column>
                        <Column field="donation_type" header="Type" sortable style="min-width: 8rem">
                            <template #body="{ data }">
                                <Tag :severity="data.donation_type === 'once' ? 'info' : 'success'" :value="data.donation_type === 'once' ? 'Unique' : 'Mensuel'" />
                            </template>
                        </Column>
                        <Column field="created_at" header="Date du don" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                {{ formatDate(data.created_at) }}
                            </template>
                        </Column>
                        <Column field="contacted" header="Contacté" sortable style="min-width: 8rem; text-align: center;">
                            <template #body="{ data }">
                                <i :class="data.contacted ? 'pi pi-check-circle text-emerald-500 text-lg' : 'pi pi-clock text-slate-400 text-lg'" v-tooltip="data.contacted ? 'Contacté' : 'En attente'"></i>
                            </template>
                        </Column>
                        <Column field="tax_receipt" header="Reçu fiscal" sortable style="min-width: 9rem; text-align: center;">
                            <template #body="{ data }">
                                <i :class="data.tax_receipt ? 'pi pi-file-pdf text-rose-500' : 'pi pi-file text-slate-300'" v-tooltip="data.tax_receipt ? 'Reçu émis' : 'Non émis'"></i>
                            </template>
                        </Column>
                        <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                            <template #body="{ data }">
                                <Button icon="pi pi-phone" class="p-button-rounded p-button-text p-button-success mr-1" @click="contactDonor(data)" v-tooltip.top="'Contacter'" />
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-1" @click="editDonor(data)" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteDonor(data)" v-tooltip.top="'Supprimer'" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- MODALE AJOUT / MODIFICATION DONATEUR -->
        <Dialog v-model:visible="donorDialog" :style="{ width: '650px', maxWidth: '95vw' }" :modal="true" class="custom-dialog" :closable="false">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 shadow-sm">
                            <i class="pi pi-user-plus text-xl"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-xl text-slate-800 leading-tight">{{ isEditing ? 'Modifier le donateur' : 'Ajouter un donateur' }}</h2>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Saisissez les informations du donateur et son don.</p>
                        </div>
                    </div>
                    <Button icon="pi pi-times" class="p-button-rounded p-button-text p-button-secondary bg-slate-50 hover:bg-slate-100 text-slate-500" @click="donorDialog = false" />
                </div>
            </template>

            <div class="space-y-5 pt-2">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-700">Prénom <span class="text-red-500">*</span></label>
                        <InputText v-model="form.first_name" :class="{ 'border-red-500': submitted && !form.first_name }" />
                        <small v-if="submitted && !form.first_name" class="text-red-500">Prénom requis.</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-700">Nom <span class="text-red-500">*</span></label>
                        <InputText v-model="form.last_name" :class="{ 'border-red-500': submitted && !form.last_name }" />
                        <small v-if="submitted && !form.last_name" class="text-red-500">Nom requis.</small>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-700">Email <span class="text-red-500">*</span></label>
                        <InputText v-model="form.email" type="email" :class="{ 'border-red-500': submitted && !form.email }" />
                        <small v-if="submitted && !form.email" class="text-red-500">Email requis.</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-700">Téléphone</label>
                        <InputText v-model="form.phone" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-700">Montant (USD) <span class="text-red-500">*</span></label>
                        <InputNumber v-model="form.amount" mode="currency" currency="USD" :min="1" :class="{ 'border-red-500': submitted && (!form.amount || form.amount <= 0) }" />
                        <small v-if="submitted && (!form.amount || form.amount <= 0)" class="text-red-500">Montant valide requis.</small>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-700">Type de don</label>
                        <Select v-model="form.donation_type" :options="donationTypeOptions" optionLabel="label" optionValue="value" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-sm font-bold text-slate-700">Date du don</label>
                        <Calendar v-model="form.donation_date" dateFormat="dd/mm/yy" class="w-full" />
                    </div>
                    <div class="flex flex-col gap-1 justify-end">
                        <div class="flex items-center gap-3">
                            <ToggleButton v-model="form.contacted" onLabel="Contacté" offLabel="Non contacté" onIcon="pi pi-check" offIcon="pi pi-times" class="w-32" />
                            <span class="text-xs text-slate-500">Marquer comme contacté</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3">
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.tax_receipt" binary />
                        <label class="text-sm font-medium text-slate-700">Émettre un reçu fiscal</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.newsletter" binary />
                        <label class="text-sm font-medium text-slate-700">Inscrire à la newsletter</label>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-sm font-bold text-slate-700">Notes (optionnel)</label>
                    <Textarea v-model="form.notes" rows="2" placeholder="Informations complémentaires..." />
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between items-center border-t border-slate-100 pt-5 mt-3">
                    <span class="text-xs text-slate-400"><span class="text-red-500">*</span> Champs obligatoires</span>
                    <div class="flex gap-3">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary font-bold" @click="donorDialog = false" />
                        <Button label="Enregistrer" icon="pi pi-check" class="bg-emerald-600 border-none hover:bg-emerald-700 shadow-lg shadow-emerald-500/30 font-bold px-6 py-3 rounded-xl text-white" @click="saveDonor" :loading="saving" />
                    </div>
                </div>
            </template>
        </Dialog>

        <ConfirmDialog>
            <template #message="slotProps">
                <div class="flex items-center w-full gap-4 border-b border-slate-100 pb-4 mb-4">
                    <i :class="slotProps.message.icon" class="text-4xl text-rose-500"></i>
                    <p class="text-slate-700 font-medium m-0">{{ slotProps.message.message }}</p>
                </div>
            </template>
        </ConfirmDialog>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode } from '@primevue/core/api';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import ToggleButton from 'primevue/togglebutton';
import Checkbox from 'primevue/checkbox';
import Select from 'primevue/select';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    donors: [Array, Object],
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterStatus = ref(null);
const statusOptions = [
    { label: 'Tous', value: null },
    { label: 'Contacté', value: true },
    { label: 'Non contacté', value: false }
];

const donorsList = computed(() => {
    let list = props.donors?.data ?? props.donors ?? [];
    if (filterStatus.value !== null) {
        list = list.filter(d => d.contacted === filterStatus.value);
    }
    return list;
});

const totalDonations = computed(() => {
    return donorsList.value.reduce((sum, d) => sum + (d.amount || 0), 0);
});
const totalDonationsFormatted = computed(() => {
    return totalDonations.value.toLocaleString() + ' USD';
});

const selectedDonors = ref([]);
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// États modale
const donorDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);

// Types de dons
const donationTypeOptions = [
    { label: 'Don unique', value: 'once' },
    { label: 'Don mensuel', value: 'monthly' }
];

// Formulaire
const defaultDonor = {
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    amount: null,
    donation_type: 'once',
    donation_date: null,
    contacted: false,
    tax_receipt: false,
    newsletter: false,
    notes: ''
};

const form = reactive({ ...defaultDonor });

// Formatage date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};

// Recherche
const performSearch = () => {
    router.get(route('donors.index'), { search: search.value, contacted: filterStatus.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Ouvrir modale création
const openNewDonor = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultDonor)));
    submitted.value = false;
    isEditing.value = false;
    donorDialog.value = true;
};

// Ouvrir modale édition
const editDonor = (donor) => {
    Object.assign(form, {
        id: donor.id,
        first_name: donor.first_name,
        last_name: donor.last_name,
        email: donor.email,
        phone: donor.phone || '',
        amount: donor.amount,
        donation_type: donor.donation_type || 'once',
        donation_date: donor.donation_date ? new Date(donor.donation_date) : null,
        contacted: donor.contacted || false,
        tax_receipt: donor.tax_receipt || false,
        newsletter: donor.newsletter || false,
        notes: donor.notes || ''
    });
    submitted.value = false;
    isEditing.value = true;
    donorDialog.value = true;
};

// Sauvegarde
const saveDonor = () => {
    submitted.value = true;
    if (!form.first_name?.trim() || !form.last_name?.trim() || !form.email?.trim() || !form.amount || form.amount <= 0) {
        toast.add({ severity: 'error', summary: 'Formulaire incomplet', detail: 'Veuillez remplir tous les champs obligatoires.' });
        return;
    }

    saving.value = true;

    const data = {
        first_name: form.first_name,
        last_name: form.last_name,
        email: form.email,
        phone: form.phone,
        amount: form.amount,
        donation_type: form.donation_type,
        donation_date: form.donation_date ? new Date(form.donation_date).toISOString().split('T')[0] : null,
        contacted: form.contacted,
        tax_receipt: form.tax_receipt,
        newsletter: form.newsletter,
        notes: form.notes
    };

    const requestOptions = {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Succès', detail: isEditing.value ? 'Donateur mis à jour.' : 'Donateur ajouté.', life: 3000 });
            donorDialog.value = false;
            saving.value = false;
        },
        onError: (errors) => {
            const firstError = Object.values(errors).flat()[0];
            toast.add({ severity: 'error', summary: 'Erreur', detail: firstError || 'Une erreur est survenue.' });
            saving.value = false;
        }
    };

    if (isEditing.value) {
        router.put(route('donors.update', form.id), data, requestOptions);
    } else {
        router.post(route('donors.store'), data, requestOptions);
    }
};

// Suppression
const confirmDeleteDonor = (donor) => {
    confirm.require({
        message: `Supprimer définitivement ${donor.first_name} ${donor.last_name} ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('donors.destroy', donor.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Supprimé', detail: 'Donateur retiré.', life: 3000 })
            });
        }
    });
};

// Action contacter (exemple : ouvrir email ou marquer comme contacté)
const contactDonor = (donor) => {
    // Exemple : marquer comme contacté et ouvrir email
    router.patch(route('donors.markContacted', donor.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'info', summary: 'Contacté', detail: `${donor.first_name} ${donor.last_name} a été marqué comme contacté.` });
        }
    });
    // Ou simplement ouvrir un email : window.location.href = `mailto:${donor.email}`;
};

// Réordonnancement (optionnel)
const onRowReorder = (event) => {
    const newOrderIds = event.value.map(d => d.id);
    router.post(route('donors.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'info', summary: 'Ordre sauvegardé', life: 2000 })
    });
};

// Écoute des flash messages
watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) toast.add({ severity: 'success', summary: 'Succès', detail: newVal, life: 3000 });
});
</script>

<style scoped>
/* Réutilisation des styles de la page Initiatives */
:deep(.custom-table .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1.25rem 1rem;
    border-bottom: 2px solid #e2e8f0;
}
:deep(.custom-table .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}
:deep(.custom-table .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.p-datatable .p-datatable-tbody > tr > td .p-row-reorder-icon) {
    color: #cbd5e1;
    cursor: grab;
}
:deep(.custom-dialog .p-dialog-header) {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem 2rem;
}
:deep(.custom-dialog .p-dialog-content) {
    padding: 0 2rem 1rem 2rem;
}
:deep(.custom-dialog .p-dialog-footer) {
    padding: 0 2rem 1.5rem 2rem;
}
</style>
