<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { useI18n } from 'vue-i18n';
import InputNumber from 'primevue/inputnumber'; // Import nécessaire pour InputNumber

const props = defineProps({
    spareParts: Object, // Changed from labels to spareParts
    filters: Object,
    regions: Array, // To select a region
    labels: Array, // To select a label for the spare part
    users: Array, // To select a responsible user
    sparePartCharacteristics: Array, // Existing characteristics for the spare part
});

const characteristicTypes = ref([
    { label: computed(() => t('characteristics.types.text')), value: 'text' },
    { label: computed(() => t('characteristics.types.number')), value: 'number' },
    { label: computed(() => t('characteristics.types.date')), value: 'date' },
    { label: computed(() => t('characteristics.types.image')), value: 'text' },
    { label: computed(() => t('characteristics.types.boolean')), value: 'boolean' },
    { label: computed(() => t('characteristics.types.select')), value: 'select' },
]);
const { t } = useI18n();

const toast = useToast();
const confirm = useConfirm();
const page = usePage();

const sparePartDialog = ref(false); // Changed from labelDialog to sparePartDialog
const submitted = ref(false);
const editing = ref(false);
const search = ref(props.filters?.search || '');

const form = useForm({
    id: null,
    reference: '',
    quantity: 0,
    min_quantity: 0,
    // --- NOUVEAU: Ajout du champ prix ---
    price: 0.00,
    // ------------------------------------
    location: '',
    region_id: null, // Added based on fillable
    unity_id: null, // Added based on fillable
    label_id: null, // To link to a label
    characteristic_values: {}, // To store values for label characteristics
    user_id: null, // Default to current user
});

const openNew = () => {
    form.reset();
    editing.value = false;
    submitted.value = false;
    sparePartDialog.value = true;
};

const hideDialog = () => {
    sparePartDialog.value = false;
    submitted.value = false;
};

const editSparePart = (sparePart) => { // Changed from editLabel to editSparePart
    form.id = sparePart.id;
    form.reference = sparePart.reference;
    form.quantity = sparePart.quantity;
    form.min_quantity = sparePart.min_quantity;
    // --- MISE À JOUR: Chargement du prix ---
    form.price = sparePart.price;
    // ----------------------------------------
    form.location = sparePart.location;
    form.region_id = sparePart.region_id; // Added based on fillable
    form.unity_id = sparePart.unity_id; // Added based on fillable
    form.user_id = sparePart.user_id; // Added based on fillable
    form.label_id = sparePart.label_id;
    form.characteristic_values = sparePart.spare_part_characteristics ?
        sparePart.spare_part_characteristics.reduce((acc, char) => {
            acc[char.label_characteristic_id] = char.value;
            return acc;
        }, {}) : {};
    editing.value = true;
    sparePartDialog.value = true;
};

const saveSparePart = () => { // Changed from saveLabel to saveSparePart
    submitted.value = true;
    // Ajout de la validation pour 'price'
    if (!form.reference || form.quantity === null || form.min_quantity === null || form.price === null || !form.user_id) {
        return;
    }

    // Validate required characteristics
    const selectedLabel = props.labels.find(l => l.id === form.label_id);
    if (selectedLabel && selectedLabel.label_characteristics) {
        for (const char of selectedLabel.label_characteristics) {
            if (char.is_required && !form.characteristic_values[char.id]) {
                toast.add({ severity: 'error', summary: t('toast.error'), detail: t('spareParts.validation.characteristicRequired', { characteristic: char.name }), life: 3000 });
                return;
            }
        }
    }

    submitForm();
};

const submitForm = () => { // This function now handles spare part submission
    const url = editing.value ? route('spare-parts.update', form.id) : route('spare-parts.store'); // Changed routes
    const method = editing.value ? 'put' : 'post';

    form.transform(data => ({
        ...data, // Send all form data
    })).submit(method, url, {
        onSuccess: () => {
            sparePartDialog.value = false;
            toast.add({ severity: 'success', summary: t('toast.success'), detail: t(`spareParts.toast.${editing.value ? 'updateSuccess' : 'createSuccess'}`), life: 3000 });
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde de la pièce de rechange", errors);
            toast.add({ severity: 'error', summary: t('toast.error'), detail: t('toast.genericError'), life: 3000 });
        }
    });
};

const deleteSparePart = (sparePart) => { // Changed from deleteLabel to deleteSparePart
    confirm.require({
        message: t('spareParts.confirm.deleteMessage', { reference: sparePart.reference }),
        header: t('confirm.deleteHeader'),
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-secondary p-button-outlined',
        rejectLabel: t('confirm.cancel'),
        acceptLabel: t('confirm.delete'),
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('spare-parts.destroy', sparePart.id), { // Changed route
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: t('toast.success'), detail: t('spareParts.toast.deleteSuccess'), life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: t('toast.error'), detail: t('spareParts.toast.deleteError'), life: 3000 });
                }
            });
        },
    });
};

// Computed property to get characteristics of the selected label
const selectedLabelCharacteristics = computed(() => {
    if (form.label_id) {
        const label = props.labels.find(l => l.id === form.label_id);
        return label ? label.label_characteristics : [];
    }
    return [];
});

const dt = ref();
const exportCSV = () => {
    dt.value.exportCSV();
};

let timeoutId = null;
const performSearch = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(route('spare-parts.index'), { search: search.value }, { // Changed route
            preserveState: true,
            replace: true,
        });
    }, 300);
};

onMounted(() => {
    // Logic for spare parts loading
});

const formatCurrency = (value) => {
 return value.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
};

const dialogTitle = computed(() => editing.value ? t('spareParts.dialog.editTitle') : t('spareParts.dialog.createTitle'));

</script>
<template>
    <AppLayout :title="t('spareParts.title')">
        <Head :title="t('spareParts.headTitle')" />

        <div class="p-6 max-w-[1600px] mx-auto">
            <Toast />
            <ConfirmDialog />

            <div class="flex flex-wrap items-center justify-between bg-white/80 backdrop-blur-md p-5 rounded-[2.5rem] shadow-sm border border-slate-100 mb-8 gap-4">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 bg-primary-500 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-200">
                        <i class="pi pi-box text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-slate-800 tracking-tighter leading-none">{{ t('spareParts.title') }}</h1>
                        <p class="text-[10px] uppercase font-bold text-slate-400 mt-2 tracking-[0.2em]">Asset Stock Management v16</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <IconField class="hidden md:block">
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="search" :placeholder="t('spareParts.toolbar.searchPlaceholder')"
                            @input="performSearch" class="!rounded-2xl border-none bg-slate-100/50 w-64 focus:w-80 transition-all font-medium" />
                    </IconField>
                    <Button :label="t('spareParts.toolbar.add')" icon="pi pi-plus"
                        class="!rounded-2xl !bg-slate-900 !border-none !px-6 !py-3 shadow-xl hover:scale-105 transition-transform" @click="openNew" />
                    <Button icon="pi pi-upload" severity="secondary" text class="!rounded-2xl" @click="exportCSV" />
                </div>
            </div>

            <div class="bg-white rounded-[3rem] shadow-xl border border-slate-50 overflow-hidden p-2">
                <DataTable :value="spareParts.data" dataKey="id" :paginator="true" :rows="10"
                    class="p-datatable-sm" responsiveLayout="scroll" :rowHover="true">

                    <Column field="reference" header="REF" class="font-mono font-bold text-slate-600"></Column>

                    <Column header="Composant">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-8 rounded-full" :style="{ background: data.label?.color }"></div>
                                <div>
                                    <div class="text-sm font-black text-slate-700 uppercase">{{ data.label?.designation }}</div>
                                    <div class="text-[10px] text-slate-400 font-bold italic">{{ data.location }}</div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Stock / Alerte">
                        <template #body="{ data }">
                            <div class="flex items-center gap-4">
                                <span :class="['text-base font-black', data.quantity <= data.min_quantity ? 'text-rose-500' : 'text-primary-600']">
                                    {{ data.quantity }}
                                </span>
                                <div class="h-1 w-12 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-slate-300" :style="{ width: (data.min_quantity/data.quantity*100) + '%' }"></div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Prix">
                        <template #body="{ data }">
                            <span class="font-bold text-slate-500">{{ formatCurrency(data.price) }}</span>
                        </template>
                    </Column>

                    <Column header="Actions" alignFrozen="right" frozen>
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" text rounded severity="info" @click="editSparePart(data)" />
                                <Button icon="pi pi-trash" text rounded severity="danger" @click="deleteSparePart(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

        <Dialog
    v-model:visible="sparePartDialog"
    modal
    :header="false"
    :style="{ width: '65rem' }"
    class="p-0 overflow-hidden shadow-2xl"
    :pt="{
        root: { class: 'border-none rounded-3xl bg-slate-50' },
        mask: { style: 'backdrop-filter: blur(8px)' }
    }"
>
    <div class="bg-slate-900 p-6 flex justify-between items-center text-white">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-primary-500 rounded-2xl shadow-lg shadow-primary-500/20">
                <i class="pi pi-box text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-black uppercase tracking-tight m-0">{{ dialogTitle }}</h2>
                <p class="text-xs text-slate-400 m-0">{{ editing ? t('spareParts.dialog.editSubtitle') : t('spareParts.dialog.createSubtitle') }}</p>
            </div>
        </div>
        <Button icon="pi pi-times" @click="hideDialog" text rounded class="text-white hover:bg-white/10" />
    </div>

    <div class="p-6">
        <div class="grid grid-cols-12 gap-6">

            <div class="col-span-12 lg:col-span-7 space-y-6">

                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200/60">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                        <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                        Informations Générales
                    </h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600 ml-1">{{ t('spareParts.form.partType') }}</label>
                            <Dropdown v-model="form.label_id" :options="labels" optionLabel="designation" optionValue="id"
                                class="w-full !rounded-xl !border-slate-200 !bg-slate-50/50" />
                            <small class="p-error" v-if="form.errors.label_id">{{ form.errors.label_id }}</small>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600 ml-1">{{ t('spareParts.form.reference') }}</label>
                            <InputText v-model.trim="form.reference" class="!rounded-xl !border-slate-200 font-mono font-bold" />
                            <small class="p-error" v-if="form.errors.reference">{{ form.errors.reference }}</small>
                        </div>

                        <div class="flex flex-col gap-2 col-span-2 mt-2">
                            <label class="font-bold text-sm text-slate-600 ml-1">{{ t('spareParts.form.responsible') }}</label>
                            <Dropdown v-model="form.user_id" :options="users" optionLabel="name" optionValue="id"
                                class="w-full !rounded-xl !border-slate-200" />
                        </div>
                    </div>
                </div>

                <div v-if="selectedLabelCharacteristics.length > 0" class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200/60">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6 flex items-center gap-2">
                        <i class="pi pi-sliders-h text-primary-500"></i>
                        Spécifications Techniques
                    </h3>

                    <div class="grid grid-cols-2 gap-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="char in selectedLabelCharacteristics" :key="char.id" class="flex flex-col gap-2 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                            <label class="text-[11px] font-black text-slate-500 uppercase">{{ char.name }} <span v-if="char.is_required" class="text-red-500">*</span></label>

                            <InputText v-if="['text', 'image'].includes(char.type)" v-model="form.characteristic_values[char.id]" class="!p-inputtext-sm !border-none !bg-white !rounded-lg shadow-sm" />
                            <InputNumber v-else-if="char.type === 'number'" v-model="form.characteristic_values[char.id]" class="!border-none !bg-white !rounded-lg shadow-sm" />
                            <Calendar v-else-if="char.type === 'date'" v-model="form.characteristic_values[char.id]" dateFormat="dd/mm/yy" class="!border-none !bg-white !rounded-lg shadow-sm" />
                            <InputSwitch v-else-if="char.type === 'boolean'" v-model="form.characteristic_values[char.id]" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-5 space-y-6">

                <div class="bg-primary-600 p-8 rounded-[2.5rem] shadow-xl shadow-primary-500/20 text-white relative overflow-hidden">
                    <i class="pi pi-chart-line absolute -right-4 -bottom-4 text-8xl opacity-10 rotate-12"></i>

                    <div class="relative z-10">
                        <label class="text-[10px] font-black uppercase opacity-70 mb-2 block">{{ t('spareParts.form.price') }} (EUR)</label>
                        <InputNumber v-model="form.price" mode="currency" currency="EUR" locale="fr-FR"
                            class="v11-price-input w-full"
                            :pt="{ input: { class: 'bg-transparent border-none text-white text-4xl font-black p-0 focus:ring-0 shadow-none' } }" />

                        <div class="grid grid-cols-2 gap-4 mt-8 pt-6 border-t border-white/20">
                            <div class="flex flex-col gap-1 text-center border-r border-white/20">
                                <span class="text-[10px] font-bold opacity-70 uppercase tracking-widest">Actuel</span>
                                <InputNumber v-model="form.quantity" class="v11-compact-num" :pt="{ input: { class: 'bg-transparent border-none text-white text-xl font-bold p-0 text-center shadow-none' } }" />
                            </div>
                            <div class="flex flex-col gap-1 text-center">
                                <span class="text-[10px] font-bold opacity-70 uppercase tracking-widest">Alerte Min</span>
                                <InputNumber v-model="form.min_quantity" class="v11-compact-num" :pt="{ input: { class: 'bg-transparent border-none text-white text-xl font-bold p-0 text-center shadow-none' } }" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200/60">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Emplacement</h3>
                    <div class="space-y-4">
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600">{{ t('spareParts.form.region') }}</label>
                            <Dropdown v-model="form.region_id" :options="regions" optionLabel="designation" optionValue="id" class="!rounded-xl !bg-slate-50/50" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="font-bold text-sm text-slate-600">{{ t('spareParts.form.location') }}</label>
                            <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-xl border border-slate-200 shadow-inner">
                                <i class="pi pi-map-marker text-primary-500 ml-2"></i>
                                <InputText v-model.trim="form.location" class="!border-none !bg-transparent w-full font-bold text-sm" placeholder="Rayon / Étagère" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template #footer>
        <div class="flex justify-end items-center gap-3 p-4 bg-white border-t border-slate-100">
            <Button :label="t('dialog.cancel')" icon="pi pi-times" @click="hideDialog" text severity="secondary" class="!rounded-xl font-bold" />
            <Button :label="t('dialog.save')" icon="pi pi-check-circle" @click="saveSparePart" :loading="form.processing"
                class="!rounded-xl !bg-slate-900 !border-none !px-8 shadow-lg shadow-slate-200 font-bold" />
        </div>
    </template>
</Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Styles spécifiques si nécessaire */
.p-datatable .p-column-header-content {
    justify-content: space-between;
}

.p-colorpicker-preview {
    border-radius: 4px;
}

.p-button.p-button-warning, .p-button.p-button-warning:hover {
    background: var(--orange-500);
    border-color: var(--orange-500);
}

.p-button.p-button-warning:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--orange-700), 0 1px 2px 0 black;
}

.p-button.p-button-success, .p-button.p-button-success:hover {
    background: var(--green-500);
    border-color: var(--green-500);
}

.p-button.p-button-success:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--green-700), 0 1px 2px 0 black;
}

.p-button.p-button-danger, .p-button.p-button-danger:hover {
    background: var(--red-500);
    border-color: var(--red-500);
}

.p-button.p-button-danger:focus {
    box-shadow: 0 0 0 2px var(--surface-200), 0 0 0 4px var(--red-700), 0 1px 2px 0 black;
}

</style>
