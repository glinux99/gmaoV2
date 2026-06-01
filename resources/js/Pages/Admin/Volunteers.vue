<template>
    <AppLayout>
        <Head title="Bénévoles - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-900/50 to-teal-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-emerald-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-teal-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module Bénévoles" severity="success" class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Bénévoles</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez vos bénévoles et leur ordre d'affichage (glisser‑déposer).</p>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                    <!-- Barre d'outils -->
                    <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 w-full sm:w-80">
                            <InputText v-model="search" placeholder="Rechercher un bénévole..." class="w-full rounded-xl bg-white border-slate-200" @keyup.enter="performSearch" />
                            <Button icon="pi pi-search" class="p-button-rounded p-button-text" @click="performSearch" v-tooltip.bottom="'Rechercher'" />
                        </div>
                        <Button icon="pi pi-plus" label="Nouveau bénévole" class="bg-emerald-500 hover:bg-emerald-600 border-none shadow-lg shadow-emerald-500/30 text-white font-bold w-full sm:w-auto" @click="openNewVolunteer" />
                    </div>

                    <!-- Tableau -->
                    <DataTable :value="volunteersList" v-model:selection="selectedVolunteers" dataKey="id"
                        :paginator="true" :rows="10" :filters="filters"
                        responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows
                        @row-reorder="onRowReorder" reorderableRows>

                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-12 text-center">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="pi pi-user-plus text-3xl text-slate-400"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800">Aucun bénévole</h3>
                                <p class="text-slate-500 max-w-sm mb-4">Vous n'avez pas encore enregistré de bénévole.</p>
                                <Button label="Créer un bénévole" class="p-button-outlined" @click="openNewVolunteer" />
                            </div>
                        </template>

                        <Column :rowReorder="true" headerStyle="width: 3rem" :reorderableColumn="false" />
                        <Column field="order" header="Ordre" sortable style="min-width: 5rem">
                            <template #body="{ data }">
                                <Badge :value="data.order" severity="secondary" class="bg-slate-100 text-slate-700 font-bold" />
                            </template>
                        </Column>
                        <Column field="photo" header="Photo" style="min-width: 6rem">
                            <template #body="{ data }">
                                <img v-if="data.photo_url" :src="data.photo_url" class="h-10 w-10 object-cover rounded-full" />
                                <span v-else class="text-slate-400 text-xs">-</span>
                            </template>
                        </Column>
                        <Column field="name" header="Nom" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-800">{{ data.name }}</span>
                            </template>
                        </Column>
                        <Column field="email" header="Email" sortable style="min-width: 12rem">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.email || '-' }}</span>
                            </template>
                        </Column>
                        <Column field="phone" header="Téléphone" sortable style="min-width: 10rem">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.phone || '-' }}</span>
                            </template>
                        </Column>
                        <Column field="skills" header="Compétences" style="min-width: 10rem">
                            <template #body="{ data }">
                                <div class="flex flex-wrap gap-1">
                                    <Tag v-for="skill in data.skills_list" :key="skill" :value="skill" severity="info" class="bg-emerald-100 text-emerald-800" />
                                </div>
                            </template>
                        </Column>
                        <Column field="is_active" header="Statut" sortable style="min-width: 8rem; text-align: center;">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Actif' : 'Inactif'" />
                            </template>
                        </Column>
                        <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2" @click="editVolunteer(data)" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteVolunteer(data)" v-tooltip.top="'Supprimer'" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- MODALE BÉNÉVOLE -->
        <Dialog v-model:visible="volunteerDialog" :style="{ width: '650px' }" header="Bénévole" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600">
                        <i class="pi pi-user"></i>
                    </div>
                    <span class="font-bold text-xl text-slate-800">{{ isEditing ? 'Modifier le bénévole' : 'Nouveau bénévole' }}</span>
                </div>
            </template>

            <div class="space-y-5 pt-2">
                <!-- Nom -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Nom <span class="text-red-500">*</span></label>
                    <InputText v-model="form.name" autofocus :class="{ 'p-invalid': submitted && !form.name }" class="w-full rounded-xl" placeholder="Prénom et nom" />
                    <small v-if="submitted && !form.name" class="p-error">Le nom est requis.</small>
                </div>

                <!-- Email & Téléphone -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-bold text-slate-700">Email</label>
                        <InputText v-model="form.email" class="w-full rounded-xl" placeholder="exemple@mail.com" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-bold text-slate-700">Téléphone</label>
                        <InputText v-model="form.phone" class="w-full rounded-xl" placeholder="+33 6 12 34 56 78" />
                    </div>
                </div>

                <!-- Description -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Description / Bio</label>
                    <Textarea v-model="form.description" rows="3" class="w-full rounded-xl" placeholder="Courte biographie ou motivation..." />
                </div>

                <!-- Compétences (chips) -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Compétences</label>
                    <Chips v-model="form.skills" class="w-full rounded-xl" placeholder="Ex: communication, design..." />
                    <small class="text-slate-400 text-xs">Appuyez sur Entrée pour ajouter une compétence.</small>
                </div>

                <!-- Photo (upload) -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Photo</label>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-4">
                            <input type="file" accept="image/*" @change="onPhotoSelected" ref="fileInput" class="hidden" />
                            <Button type="button" label="Choisir une photo" icon="pi pi-upload" class="p-button-outlined" @click="$refs.fileInput.click()" />
                            <span v-if="photoPreview" class="text-sm text-slate-600">{{ photoFileName || 'Fichier sélectionné' }}</span>
                            <Button v-if="form.photo || photoPreview" type="button" icon="pi pi-times" class="p-button-rounded p-button-text p-button-danger" @click="removePhoto" v-tooltip.top="'Supprimer la photo'" />
                        </div>
                        <div v-if="photoPreview" class="mt-2">
                            <img :src="photoPreview" class="h-20 w-20 object-cover rounded-full border-2 border-slate-200" />
                        </div>
                        <div v-else-if="form.existing_photo_url && !photoPreview" class="mt-2">
                            <img :src="form.existing_photo_url" class="h-20 w-20 object-cover rounded-full border-2 border-slate-200" />
                            <small class="text-slate-400 text-xs">Photo actuelle</small>
                        </div>
                        <small class="text-slate-400 text-xs">Formats acceptés : JPG, PNG, GIF, WebP (max 2 Mo)</small>
                    </div>
                </div>

                <!-- Actif / Inactif -->
                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">Bénévole actif</label>
                    <ToggleButton v-model="form.is_active" onLabel="Oui" offLabel="Non" class="w-20" />
                </div>

                <!-- Ordre -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Ordre d'affichage</label>
                    <InputNumber v-model="form.order" class="w-full rounded-xl" :min="0" showButtons />
                    <small class="text-slate-400 text-xs">L'ordre est prioritairement géré par glisser-déposer dans le tableau.</small>
                </div>
            </div>

            <template #footer>
                <div class="flex gap-2 justify-end border-t border-slate-100 pt-4 mt-4">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="volunteerDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-check" class="bg-emerald-500 border-none hover:bg-emerald-600" @click="saveVolunteer" :loading="saving" />
                </div>
            </template>
        </Dialog>

        <ConfirmDialog />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Head, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { FilterMatchMode } from '@primevue/core/api';

// PrimeVue components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';
import Chips from 'primevue/chips';
import InputNumber from 'primevue/inputnumber';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    volunteers: [Array, Object],
    filters: Object,
});

// Liste paginée ou brute
const volunteersList = computed(() => props.volunteers?.data ?? props.volunteers ?? []);
const selectedVolunteers = ref([]);

// Recherche serveur
const search = ref(props.filters?.search || '');
const performSearch = () => {
    router.get(route('volunteers.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Filtre global pour DataTable (non utilisé pour la recherche serveur)
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// État de la modale
const volunteerDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);

// Formulaire par défaut
const defaultVolunteer = {
    id: null,
    name: '',
    email: '',
    phone: '',
    description: '',
    skills: [],           // tableau pour Chips
    photo: null,          // Fichier image
    existing_photo_url: null, // URL photo actuelle
    is_active: true,
    order: 0,
    delete_photo: false
};

const form = reactive({ ...defaultVolunteer });

// Gestion de la photo
const photoPreview = ref(null);
const photoFileName = ref('');
const fileInput = ref(null);

const onPhotoSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        toast.add({ severity: 'error', summary: 'Format invalide', detail: 'Veuillez sélectionner une image.' });
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        toast.add({ severity: 'error', summary: 'Fichier trop volumineux', detail: 'Taille max : 2 Mo.' });
        return;
    }
    form.photo = file;
    photoFileName.value = file.name;
    const reader = new FileReader();
    reader.onload = (e) => { photoPreview.value = e.target.result; };
    reader.readAsDataURL(file);
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
    photoFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    if (isEditing.value && form.existing_photo_url) {
        form.delete_photo = true;
    }
};

// Ouvrir modale (création)
const openNewVolunteer = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultVolunteer)));
    submitted.value = false;
    isEditing.value = false;
    photoPreview.value = null;
    photoFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    volunteerDialog.value = true;
};

// Ouvrir modale (édition)
const editVolunteer = (volunteer) => {
    Object.assign(form, {
        id: volunteer.id,
        name: volunteer.name,
        email: volunteer.email || '',
        phone: volunteer.phone || '',
        description: volunteer.description || '',
        skills: volunteer.skills ? JSON.parse(JSON.stringify(volunteer.skills)) : [],
        photo: null,
        existing_photo_url: volunteer.photo_url || null,
        is_active: volunteer.is_active,
        order: volunteer.order,
        delete_photo: false
    });
    photoPreview.value = null;
    photoFileName.value = '';
    if (fileInput.value) fileInput.value.value = '';
    isEditing.value = true;
    volunteerDialog.value = true;
};

// Sauvegarde
const saveVolunteer = () => {
    submitted.value = true;
    if (!form.name?.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le nom est requis.' });
        return;
    }

    saving.value = true;
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('email', form.email || '');
    formData.append('phone', form.phone || '');
    formData.append('description', form.description || '');
    formData.append('is_active', form.is_active ? 1 : 0);
    formData.append('order', form.order);

    // Envoyer les compétences sous forme de tableau JSON
    formData.append('skills', JSON.stringify(form.skills));

    if (form.photo instanceof File) {
        formData.append('photo', form.photo);
    }
    if (form.delete_photo) {
        formData.append('delete_photo', '1');
    }

    if (isEditing.value) {
        formData.append('_method', 'PUT');
        router.post(route('volunteers.update', form.id), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Bénévole modifié.', life: 3000 });
                volunteerDialog.value = false;
                saving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                saving.value = false;
            }
        });
    } else {
        router.post(route('volunteers.store'), formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Bénévole créé.', life: 3000 });
                volunteerDialog.value = false;
                saving.value = false;
            },
            onError: (err) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(err).flat()[0] });
                saving.value = false;
            }
        });
    }
};

// Suppression
const confirmDeleteVolunteer = (volunteer) => {
    confirm.require({
        message: `Supprimer le bénévole "${volunteer.name}" ?`,
        header: 'Confirmation',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('volunteers.destroy', volunteer.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Bénévole supprimé.' }),
            });
        }
    });
};

// Réordonnancement
const onRowReorder = (event) => {
    const newOrderIds = event.value.map(v => v.id);
    router.post(route('volunteers.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Ordre mis à jour' }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec du réordonnancement.' })
    });
};

// Message flash
watch(() => page.props.flash?.success, (val) => {
    if (val) toast.add({ severity: 'success', summary: 'Succès', detail: val, life: 3000 });
});
</script>

<style scoped>
:deep(.custom-table .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem;
    border-bottom: 2px solid #e2e8f0;
    border-top: 1px solid #e2e8f0;
}
:deep(.custom-table .p-datatable-tbody > tr:hover) {
    background-color: #f8fafc !important;
}
:deep(.custom-table .p-datatable-tbody > tr > td) {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.custom-dialog .p-dialog-header) {
    border-bottom: 1px solid #f1f5f9;
    padding: 1.5rem;
}
:deep(.custom-dialog .p-dialog-content) {
    padding: 1.5rem;
}
:deep(.custom-dialog .p-dialog-footer) {
    padding: 0 1.5rem 1.5rem 1.5rem;
}
:deep(.p-datatable .p-datatable-tbody > tr > td .p-row-reorder-icon) {
    color: #94a3b8;
    transition: color 0.2s;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover .p-row-reorder-icon) {
    color: #0f172a;
}
</style>
