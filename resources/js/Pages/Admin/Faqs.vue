<template>
    <AppLayout>
        <Head title="FAQ - Ultra Pro" />

        <div class="min-h-screen bg-slate-50/50 pb-12">
            <!-- HEADER HERO -->
            <div class="bg-slate-900 pt-8 pb-24 px-4 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-sky-900/50 to-blue-900/50 mix-blend-multiply"></div>
                <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-sky-500 rounded-full blur-[100px] opacity-30 pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-72 h-72 bg-blue-500 rounded-full blur-[100px] opacity-20 pointer-events-none"></div>
                <div class="max-w-screen-2xl mx-auto relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Badge value="Module FAQ" severity="info" class="bg-sky-500/20 text-sky-300 border border-sky-500/30 font-mono text-[10px] tracking-widest" />
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tight">Foire Aux Questions</h1>
                        <p class="text-slate-400 mt-2 text-lg max-w-2xl font-light">Gérez vos questions fréquentes et leur ordre d'affichage (glisser‑déposer).</p>
                    </div>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="max-w-screen-2xl mx-auto px-4 lg:px-8 -mt-14 relative z-20">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-xl shadow-slate-200/40 overflow-hidden">
                    <!-- Barre d'outils -->
                    <div class="p-4 lg:p-6 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 w-full sm:w-80">
                            <InputText v-model="search" placeholder="Rechercher une question..." class="w-full rounded-xl bg-white border-slate-200" @keyup.enter="performSearch" />
                            <Button icon="pi pi-search" class="p-button-rounded p-button-text" @click="performSearch" v-tooltip.bottom="'Rechercher'" />
                        </div>
                        <Button icon="pi pi-plus" label="Nouvelle question" class="bg-sky-500 hover:bg-sky-600 border-none shadow-lg shadow-sky-500/30 text-white font-bold w-full sm:w-auto" @click="openNewFaq" />
                    </div>

                    <!-- Tableau -->
                    <DataTable :value="faqsList" v-model:selection="selectedFaqs" dataKey="id"
                        :paginator="true" :rows="10" :filters="filters"
                        responsiveLayout="scroll" class="p-datatable-lg custom-table" stripedRows
                        @row-reorder="onRowReorder" reorderableRows>

                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-12 text-center">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <i class="pi pi-question-circle text-3xl text-slate-400"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800">Aucune question</h3>
                                <p class="text-slate-500 max-w-sm mb-4">Vous n'avez pas encore enregistré de FAQ.</p>
                                <Button label="Créer une question" class="p-button-outlined" @click="openNewFaq" />
                            </div>
                        </template>

                        <Column :rowReorder="true" headerStyle="width: 3rem" :reorderableColumn="false" />
                        <Column field="order" header="Ordre" sortable style="min-width: 5rem">
                            <template #body="{ data }">
                                <Badge :value="data.order" severity="secondary" class="bg-slate-100 text-slate-700 font-bold" />
                            </template>
                        </Column>
                        <Column field="question" header="Question" sortable style="min-width: 14rem">
                            <template #body="{ data }">
                                <span class="font-bold text-slate-800">{{ data.question }}</span>
                            </template>
                        </Column>
                        <Column field="answer" header="Réponse" style="min-width: 16rem">
                            <template #body="{ data }">
                                <span class="text-slate-600 text-sm line-clamp-2">{{ data.answer }}</span>
                            </template>
                        </Column>
                        <Column field="is_active" header="Statut" sortable style="min-width: 8rem; text-align: center;">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'danger'" :value="data.is_active ? 'Actif' : 'Inactif'" />
                            </template>
                        </Column>
                        <Column :exportable="false" style="min-width: 10rem; text-align: right;">
                            <template #body="{ data }">
                                <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-info mr-2" @click="editFaq(data)" v-tooltip.top="'Modifier'" />
                                <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger" @click="confirmDeleteFaq(data)" v-tooltip.top="'Supprimer'" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- MODALE FAQ -->
        <Dialog v-model:visible="faqDialog" :style="{ width: '650px' }" header="Question" :modal="true" class="custom-dialog">
            <template #header>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-sky-100 rounded-lg flex items-center justify-center text-sky-600">
                        <i class="pi pi-question-circle"></i>
                    </div>
                    <span class="font-bold text-xl text-slate-800">{{ isEditing ? 'Modifier la question' : 'Nouvelle question' }}</span>
                </div>
            </template>

            <div class="space-y-5 pt-2">
                <!-- Question -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Question <span class="text-red-500">*</span></label>
                    <InputText v-model="form.question" autofocus :class="{ 'p-invalid': submitted && !form.question }" class="w-full rounded-xl" placeholder="Ex: Quels sont les horaires d'ouverture ?" />
                    <small v-if="submitted && !form.question" class="p-error">La question est requise.</small>
                </div>

                <!-- Réponse -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-700">Réponse <span class="text-red-500">*</span></label>
                    <Textarea v-model="form.answer" rows="5" class="w-full rounded-xl" placeholder="Saisissez la réponse..." :class="{ 'p-invalid': submitted && !form.answer }" />
                    <small v-if="submitted && !form.answer" class="p-error">La réponse est requise.</small>
                </div>

                <!-- Actif / Inactif -->
                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">Question active</label>
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
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary" @click="faqDialog = false" />
                    <Button label="Enregistrer" icon="pi pi-check" class="bg-sky-500 border-none hover:bg-sky-600" @click="saveFaq" :loading="saving" />
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

// Composants PrimeVue
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';
import ToggleButton from 'primevue/togglebutton';
import InputNumber from 'primevue/inputnumber';
import Tag from 'primevue/tag';

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const props = defineProps({
    faqs: [Array, Object],
    filters: Object, // { search: string } renvoyé par le contrôleur
});

// Liste paginée ou brute
const faqsList = computed(() => props.faqs?.data ?? props.faqs ?? []);
const selectedFaqs = ref([]);

// Recherche serveur
const search = ref(props.filters?.search || '');
const performSearch = () => {
    router.get(route('faqs.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Filtre global pour le DataTable (non utilisé pour la recherche serveur, mais conservé pour la compatibilité)
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

// États modale
const faqDialog = ref(false);
const isEditing = ref(false);
const submitted = ref(false);
const saving = ref(false);

// Formulaire réactif
const defaultFaq = {
    id: null,
    question: '',
    answer: '',
    is_active: true,
    order: 0
};

const form = reactive({ ...defaultFaq });

// Ouvrir modale (création)
const openNewFaq = () => {
    Object.assign(form, JSON.parse(JSON.stringify(defaultFaq)));
    submitted.value = false;
    isEditing.value = false;
    faqDialog.value = true;
};

// Ouvrir modale (édition)
const editFaq = (faq) => {
    Object.assign(form, {
        id: faq.id,
        question: faq.question,
        answer: faq.answer,
        is_active: faq.is_active,
        order: faq.order
    });
    isEditing.value = true;
    faqDialog.value = true;
};

// Sauvegarde
const saveFaq = () => {
    submitted.value = true;

    if (!form.question?.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'La question est requise.' });
        return;
    }
    if (!form.answer?.trim()) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'La réponse est requise.' });
        return;
    }

    saving.value = true;

    if (isEditing.value) {
        router.put(route('faqs.update', form.id), form, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Question modifiée.', life: 3000 });
                faqDialog.value = false;
                saving.value = false;
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(errors).flat()[0] });
                saving.value = false;
            }
        });
    } else {
        router.post(route('faqs.store'), form, {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Question ajoutée.', life: 3000 });
                faqDialog.value = false;
                saving.value = false;
            },
            onError: (errors) => {
                toast.add({ severity: 'error', summary: 'Erreur', detail: Object.values(errors).flat()[0] });
                saving.value = false;
            }
        });
    }
};

// Suppression
const confirmDeleteFaq = (faq) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer la question "${faq.question}" ?`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('faqs.destroy', faq.id), {
                preserveScroll: true,
                onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Question supprimée.', life: 3000 }),
                onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Suppression impossible' })
            });
        }
    });
};

// Réordonnancement
const onRowReorder = (event) => {
    const newOrderIds = event.value.map(p => p.id);
    router.post(route('faqs.reorder'), { order: newOrderIds }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Ordre mis à jour', life: 3000 }),
        onError: () => toast.add({ severity: 'error', summary: 'Erreur', detail: 'Le réordonnancement a échoué.' })
    });
};

// Message flash
watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) toast.add({ severity: 'success', summary: 'Succès', detail: newVal, life: 3000 });
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
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
