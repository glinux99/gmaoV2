<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import debounce from 'lodash/debounce';

// --- IMPORTS PRIMEVUE ---
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import InputSwitch from 'primevue/inputswitch';
import SelectButton from 'primevue/selectbutton';
import ProgressBar from 'primevue/progressbar';
import Divider from 'primevue/divider';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

// --- PROPS DU CONTROLLEUR ---
const props = defineProps({
    transformers: Object,
    equipments: Array,
    networkNodes: Array,
    filters: Object,
});

const { t } = useI18n();
const toast = useToast();

// --- STATE DE RECHERCHE ---
const isLoading = ref(false);
const selectedTransformers = ref([]);

const searchFilters = ref({
    search: props.filters?.search || '',
    status: props.filters?.status || null,
    equipment_id: props.filters?.equipment_id || null,
    network_node_id: props.filters?.network_node_id || null,
});

const statusOptions = ref([
    { label: 'Opérationnel', value: 'operational' },
    { label: 'En Maintenance', value: 'maintenance' },
    { label: 'En Alerte', value: 'alert' },
    { label: 'Hors Ligne', value: 'offline' }
]);

// --- FILTRES ---
const applyFilters = debounce(() => {
    isLoading.value = true;
    router.get(route('transformers.index'), searchFilters.value, {
        preserveState: true,
        replace: true,
        onFinish: () => isLoading.value = false
    });
}, 300);

const clearFilters = () => {
    searchFilters.value = { search: '', status: null, equipment_id: null, network_node_id: null };
    applyFilters();
};

// --- ACTIONS DE SUPPRESSION ---
const deleteTransformer = (id) => {
    if(confirm('Êtes-vous sûr de vouloir supprimer ce transformateur ?')) {
        router.delete(route('transformers.destroy', id), {
            onSuccess: () => toast.add({ severity: 'success', summary: 'Succès', detail: 'Supprimé avec succès', life: 3000 })
        });
    }
};

const deleteSelected = () => {
    if(selectedTransformers.value.length === 0) return;
    if(confirm(`Supprimer ${selectedTransformers.value.length} transformateur(s) ?`)) {
        router.post(route('transformers.destroyMany'), {
            ids: selectedTransformers.value.map(t => t.id)
        }, {
            onSuccess: () => {
                selectedTransformers.value = [];
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Éléments supprimés', life: 3000 });
            }
        });
    }
};

// --- GESTION DE LA MODALE ULTIMATE ---
const showDialog = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    transformer_id: '',
    uuid: '',
    measured_at: null,
    status: 'operational',
    equipment_id: null,
    network_node_id: null,
    load_percentage: 0,
    oil_temperature: null,
    ambient_temperature: null,
    temperature_alarm: false,
    pressure_alarm: false,
    oil_level_alarm: false,
    dmcr_alarm: false,
    dmcr_trip: false,
});

const openDialog = (transformer = null) => {
    form.clearErrors();
    if (transformer) {
        isEditing.value = true;
        form.id = transformer.id;
        form.transformer_id = transformer.transformer_id;
        form.uuid = transformer.uuid;
        form.measured_at = transformer.measured_at ? new Date(transformer.measured_at) : new Date();
        form.status = transformer.status;
        form.equipment_id = transformer.equipment_id;
        form.network_node_id = transformer.network_node_id;
        form.load_percentage = Number(transformer.load_percentage);
        form.oil_temperature = Number(transformer.oil_temperature);
        form.ambient_temperature = Number(transformer.ambient_temperature);
        form.temperature_alarm = !!transformer.temperature_alarm;
        form.pressure_alarm = !!transformer.pressure_alarm;
        form.oil_level_alarm = !!transformer.oil_level_alarm;
        form.dmcr_alarm = !!transformer.dmcr_alarm;
        form.dmcr_trip = !!transformer.dmcr_trip;
    } else {
        isEditing.value = false;
        form.reset();
        form.measured_at = new Date();
    }
    showDialog.value = true;
};

const saveTransformer = () => {
    if (isEditing.value) {
        form.put(route('transformers.update', form.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Mise à jour effectuée', life: 3000 });
            }
        });
    } else {
        form.post(route('transformers.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
                toast.add({ severity: 'success', summary: 'Succès', detail: 'Enregistré avec succès', life: 3000 });
            }
        });
    }
};

// --- HELPERS D'AFFICHAGE ---
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('fr-FR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const getStatusConfig = (status) => {
    const configs = {
        'operational': { severity: 'success', icon: 'pi pi-check-circle', label: 'Opérationnel' },
        'maintenance': { severity: 'warning', icon: 'pi pi-wrench', label: 'Maintenance' },
        'alert': { severity: 'danger', icon: 'pi pi-exclamation-triangle', label: 'Alerte' },
        'offline': { severity: 'secondary', icon: 'pi pi-power-off', label: 'Hors Ligne' },
    };
    return configs[status] || { severity: 'info', icon: 'pi pi-info-circle', label: status };
};

const getActiveAlarms = (data) => {
    const alarms = [];
    if (data.temperature_alarm) alarms.push({ label: 'Température', color: 'bg-rose-500' });
    if (data.pressure_alarm) alarms.push({ label: 'Pression', color: 'bg-orange-500' });
    if (data.oil_level_alarm) alarms.push({ label: 'Niveau Huile', color: 'bg-amber-500' });
    if (data.dmcr_alarm) alarms.push({ label: 'DMCR Alerte', color: 'bg-red-500' });
    if (data.dmcr_trip) alarms.push({ label: 'DMCR Trip', color: 'bg-purple-600' });
    return alarms;
};

// --- KPIS ---
const totalAlerts = computed(() => props.transformers.data.filter(t => t.status === 'alert' || getActiveAlarms(t).length > 0).length);
const avgLoad = computed(() => {
    const data = props.transformers.data;
    if(!data.length) return 0;
    const total = data.reduce((acc, curr) => acc + parseFloat(curr.load_percentage || 0), 0);
    return (total / data.length).toFixed(1);
});
</script>

<template>
    <AppLayout>
        <Toast />
        <div class="dashboard-container p-4 lg:p-8 bg-[#F1F5F9] min-h-screen font-sans">

            <!-- HEADER -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-6">
                <div class="flex items-center gap-5">
                    <div class="brand-icon bg-primary-600 p-4 rounded-3xl shadow-xl shadow-primary-100">
                        <i class="pi pi-box text-white text-3xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Transformateurs</h1>
                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                            <i class="pi pi-database text-xs"></i>
                            <span>Télémétrie et Monitoring Électrique</span>
                        </div>
                    </div>
                </div>

                <!-- FILTRES -->
                <div class="glass-panel flex flex-wrap items-center gap-2 p-3 bg-white/70 backdrop-blur-xl border border-white rounded-[2.5rem] shadow-xl shadow-slate-200/50">
                    <span class="p-input-icon-left w-full sm:w-auto">
                        <i class="pi pi-search text-primary-500" />
                        <InputText v-model="searchFilters.search" @input="applyFilters" placeholder="Rechercher ID..." class="!rounded-xl !border-none !bg-slate-50 w-full sm:w-48" />
                    </span>
                    <Divider layout="vertical" class="hidden lg:block" />
                    <Dropdown v-model="searchFilters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Statut" @change="applyFilters" showClear class="custom-dropdown w-40" />
                    <Dropdown v-model="searchFilters.equipment_id" :options="equipments" optionLabel="designation" optionValue="id" placeholder="Équipement" @change="applyFilters" showClear class="custom-dropdown w-48" />
                    <Dropdown v-model="searchFilters.network_node_id" :options="networkNodes" optionLabel="name" optionValue="id" placeholder="Noeud Réseau" @change="applyFilters" showClear class="custom-dropdown w-48" />
                    <Button icon="pi pi-refresh" @click="clearFilters" :loading="isLoading" class="p-button-rounded p-button-secondary p-button-text hover:bg-slate-100 transition-all" v-tooltip.top="'Réinitialiser'" />
                </div>
            </header>

            <!-- KPIs -->
            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <div v-for="(kpi, index) in [
                    { label: 'Total Actifs', value: props.transformers.total, icon: 'pi-server', color: 'primary' },
                    { label: 'En Alerte / Anomalie', value: totalAlerts, icon: 'pi-exclamation-triangle', color: 'rose' },
                    { label: 'Charge Moyenne', value: avgLoad + '%', icon: 'pi-chart-line', color: 'amber' },
                    { label: 'Disponibilité', value: '98.5%', icon: 'pi-bolt', color: 'emerald' }
                ]" :key="index" class="kpi-card group">
                    <div class="relative bg-white p-6 rounded-xl border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-xl hover:-translate-y-1">
                        <div :class="`absolute -right-4 -top-4 w-24 h-24 bg-${kpi.color}-50 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500`"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div :class="`p-3 rounded-xl bg-${kpi.color}-500 text-white shadow-lg`">
                                <i :class="`pi ${kpi.icon} text-xl`"></i>
                            </div>
                        </div>
                        <h3 class="text-slate-500 text-xs font-black uppercase tracking-widest mb-1">{{ kpi.label }}</h3>
                        <span class="text-3xl font-black text-slate-800">{{ kpi.value }}</span>
                    </div>
                </div>
            </section>

            <!-- DATATABLE -->
            <Card class="!rounded-2xl border-none shadow-xl shadow-slate-200/40 bg-white overflow-hidden">
                <template #title>
                    <div class="flex justify-between items-center px-4 pt-2">
                        <span class="text-lg font-black text-slate-800">Inventaire des Transformateurs</span>
                        <div class="flex gap-2">
                            <Button v-if="selectedTransformers.length > 0"
                                    :label="`Supprimer (${selectedTransformers.length})`"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    @click="deleteSelected"
                                    class="p-button-sm !rounded-xl shadow-md" />

                            <Button label="Nouveau" icon="pi pi-plus" @click="openDialog()" class="p-button-primary p-button-sm !rounded-xl shadow-md shadow-primary-200" />
                        </div>
                    </div>
                </template>
                <template #content>
                    <DataTable :value="props.transformers.data"
                               v-model:selection="selectedTransformers"
                               dataKey="id"
                               responsiveLayout="scroll"
                               class="p-datatable-sm custom-erp-table mt-4"
                               :loading="isLoading">

                        <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                        <Column field="transformer_id" header="ID / UUID">
                            <template #body="{data}">
                                <div class="flex flex-col">
                                    <span class="font-black text-[11px] text-primary-600 uppercase">{{ data.transformer_id }}</span>
                                    <span class="text-[9px] text-slate-400 font-mono">{{ data.uuid?.substring(0,8) || 'N/A' }}...</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Localisation">
                            <template #body="{data}">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1 text-[10px] font-bold text-slate-700">
                                        <i class="pi pi-cog text-slate-400"></i>
                                        {{ data.equipment?.designation || 'N/A' }}
                                    </div>
                                    <div class="flex items-center gap-1 text-[9px] text-slate-500">
                                        <i class="pi pi-sitemap text-slate-400"></i>
                                        {{ data.network_node?.name || 'N/A' }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column field="status" header="Statut">
                            <template #body="{data}">
                                <Tag :value="getStatusConfig(data.status).label"
                                     :severity="getStatusConfig(data.status).severity"
                                     :icon="getStatusConfig(data.status).icon"
                                     class="!px-3 !py-1 !rounded-full !text-[10px] !font-black uppercase tracking-wider shadow-sm" />
                            </template>
                        </Column>

                        <Column header="Télémétrie">
                            <template #body="{data}">
                                <div class="flex flex-col gap-2 w-32">
                                    <div class="flex items-center justify-between text-[9px] font-black uppercase text-slate-500">
                                        <span>Charge</span>
                                        <span :class="data.load_percentage > 80 ? 'text-rose-500' : 'text-primary-600'">{{ data.load_percentage || 0 }}%</span>
                                    </div>
                                    <ProgressBar :value="parseFloat(data.load_percentage || 0)" :showValue="false" class="!h-1.5 !bg-slate-100">
                                        <template #default>
                                            <div class="h-full rounded-full" :class="data.load_percentage > 80 ? 'bg-rose-500' : 'bg-primary-500'" :style="{width: (data.load_percentage || 0) + '%'}"></div>
                                        </template>
                                    </ProgressBar>
                                    <div class="flex gap-3 text-[10px] font-bold text-slate-600">
                                        <span v-tooltip.top="'Température Huile'"><i class="pi pi-thermometer text-orange-400"></i> {{ data.oil_temperature || '--' }}°C</span>
                                        <span v-tooltip.top="'Température Ambiante'"><i class="pi pi-cloud text-blue-400"></i> {{ data.ambient_temperature || '--' }}°C</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Alarmes Actives">
                            <template #body="{data}">
                                <div class="flex flex-wrap gap-1 w-40">
                                    <span v-if="getActiveAlarms(data).length === 0" class="text-[10px] text-slate-400 font-bold bg-slate-100 px-2 py-1 rounded-md">
                                        Aucune anomalie
                                    </span>
                                    <span v-for="(alarm, idx) in getActiveAlarms(data)" :key="idx"
                                          class="text-[9px] text-white font-black px-2 py-0.5 rounded-md shadow-sm"
                                          :class="alarm.color">
                                        {{ alarm.label }}
                                    </span>
                                </div>
                            </template>
                        </Column>

                        <Column field="measured_at" header="Dernière Mesure">
                            <template #body="{data}">
                                <span class="text-[10px] font-bold text-slate-500"><i class="pi pi-clock mr-1"></i>{{ formatDate(data.measured_at) }}</span>
                            </template>
                        </Column>

                        <!-- ACTIONS -->
                        <Column header="Actions" alignFrozen="right" :frozen="true">
                            <template #body="{data}">
                                <div class="flex gap-1">
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-text p-button-warning p-button-sm" @click="openDialog(data)" v-tooltip.top="'Modifier'" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-text p-button-danger p-button-sm" @click="deleteTransformer(data.id)" v-tooltip.top="'Supprimer'" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- PAGINATION -->
                    <div class="flex justify-between items-center p-4 border-t border-slate-50">
                        <span class="text-xs text-slate-500 font-medium">Affichage de {{ props.transformers.from || 0 }} à {{ props.transformers.to || 0 }} sur {{ props.transformers.total || 0 }}</span>
                        <div class="flex gap-1" v-if="props.transformers.links?.length > 3">
                            <Button v-for="(link, i) in props.transformers.links" :key="i"
                                    :label="link.label.replace('&laquo; Previous', 'Préc.').replace('Next &raquo;', 'Suiv.')"
                                    class="p-button-sm !text-xs !font-bold"
                                    :class="link.active ? 'p-button-primary' : 'p-button-text p-button-secondary'"
                                    :disabled="!link.url"
                                    @click="link.url ? router.get(link.url, {}, {preserveState: true}) : null"
                                    v-html="link.label.includes('Previous') ? '<i class=\'pi pi-chevron-left text-[10px] mr-1\'></i> Préc.' : (link.label.includes('Next') ? 'Suiv. <i class=\'pi pi-chevron-right text-[10px] ml-1\'></i>' : link.label)"
                            />
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- ========================================== -->
        <!-- MODALE "ULTIMATE PRO" (QUANTUM DIALOG)     -->
        <!-- ========================================== -->
        <Dialog v-model:visible="showDialog" modal :header="false" :closable="false"
                class="quantum-dialog w-full max-w-6xl" :pt="{ mask: { style: 'backdrop-filter: blur(4px)' } }">

            <!-- HEADER SOMBRE FLOTTANT -->
            <div class="px-8 py-4 bg-slate-900 rounded-xl text-white flex justify-between items-center shadow-lg relative z-50">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-blue-500/20 rounded-lg border border-blue-500/30">
                        <i class="pi pi-bolt text-blue-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <h2 class="text-sm font-black uppercase tracking-widest text-white leading-none">
                            {{ isEditing ? 'Éditer Transformateur' : 'Créer Transformateur' }}
                        </h2>
                        <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1 italic">
                            GMAO Console • Télémétrie • {{ form.uuid ? form.uuid.substring(0,12) + '...' : 'Génération UUID automatique' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex flex-col items-end mr-4">
                        <span class="text-[9px] font-bold text-slate-400 uppercase mb-1">Status Opérationnel</span>
                        <SelectButton v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="p-selectbutton-sm custom-dark-priority" />
                    </div>
                    <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="showDialog = false" class="text-white hover:bg-white/10" />
                </div>
            </div>

            <!-- BODY DE LA MODALE -->
            <div class="p-2">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 p-4">

                    <!-- COLONNE 1 : IDENTIFIANTS -->
                    <div class="md:col-span-4 space-y-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 h-full">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">Informations Réseau</label>

                            <div class="space-y-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase text-slate-500">Identifiant Unique (ID)</label>
                                    <InputText v-model="form.transformer_id" class="w-full quantum-input font-bold" placeholder="EX: TR-ZN1-04" :class="{'p-invalid': form.errors.transformer_id}" />
                                    <small v-if="form.errors.transformer_id" class="text-rose-500 text-[10px]">{{ form.errors.transformer_id }}</small>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase text-slate-500">Équipement Associé</label>
                                    <Dropdown v-model="form.equipment_id" :options="equipments" optionLabel="designation" optionValue="id" filter placeholder="Sélectionner..." class="w-full quantum-input" />
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase text-slate-500">Nœud de Rattachement</label>
                                    <Dropdown v-model="form.network_node_id" :options="networkNodes" optionLabel="name" optionValue="id" filter placeholder="Sélectionner..." class="w-full quantum-input" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLONNE 2 : TELEMETRIE -->
                    <div class="md:col-span-4 space-y-4">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 h-full">
                            <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-3">Télémétrie & Mesures</label>

                            <div class="space-y-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase text-slate-500">Horodatage de la mesure</label>
                                    <Calendar v-model="form.measured_at" showTime hourFormat="24" class="w-full quantum-input" />
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-bold uppercase text-slate-500">Charge du transformateur (%)</label>
                                    <InputNumber v-model="form.load_percentage" :min="0" :max="150" suffix=" %" class="w-full" inputClass="quantum-input font-black text-primary-600" />
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[9px] font-bold uppercase text-slate-500">Temp. Huile</label>
                                        <InputNumber v-model="form.oil_temperature" suffix=" °C" class="w-full" inputClass="quantum-input text-orange-600 font-bold" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-[9px] font-bold uppercase text-slate-500">Temp. Ambiante</label>
                                        <InputNumber v-model="form.ambient_temperature" suffix=" °C" class="w-full" inputClass="quantum-input text-blue-600 font-bold" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COLONNE 3 : ALARMES (PANNEAU SOMBRE) -->
                    <div class="md:col-span-4 space-y-4">
                        <div class="p-5 bg-slate-900 rounded-xl text-white shadow-xl h-full flex flex-col">
                            <h4 class="text-[10px] font-black uppercase tracking-widest mb-6 text-rose-400 flex items-center gap-2">
                                <i class="pi pi-shield"></i> Sécurité & Déclenchements
                            </h4>

                            <div class="space-y-5 flex-1">
                                <!-- Ligne Alarme -->
                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-lg border border-white/10">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-white">Alarme Température</span>
                                        <span class="text-[9px] text-slate-400 uppercase tracking-widest">Sonde Interne</span>
                                    </div>
                                    <InputSwitch v-model="form.temperature_alarm" />
                                </div>

                                <!-- Ligne Alarme -->
                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-lg border border-white/10">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-white">Alarme Pression</span>
                                        <span class="text-[9px] text-slate-400 uppercase tracking-widest">Surpression Cuve</span>
                                    </div>
                                    <InputSwitch v-model="form.pressure_alarm" />
                                </div>

                                <!-- Ligne Alarme -->
                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-lg border border-white/10">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-white">Niveau d'Huile</span>
                                        <span class="text-[9px] text-slate-400 uppercase tracking-widest">Alerte Baisse</span>
                                    </div>
                                    <InputSwitch v-model="form.oil_level_alarm" />
                                </div>

                                <!-- Ligne Alarme -->
                                <div class="flex items-center justify-between bg-white/5 p-3 rounded-lg border border-white/10">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-white">Relais DMCR (Alerte)</span>
                                        <span class="text-[9px] text-slate-400 uppercase tracking-widest">Gaz / Temp / Pres</span>
                                    </div>
                                    <InputSwitch v-model="form.dmcr_alarm" />
                                </div>

                                <!-- Ligne Critique -->
                                <div class="flex items-center justify-between bg-rose-500/20 p-3 rounded-lg border border-rose-500/50 mt-auto">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-rose-400 uppercase">Déclenchement (Trip)</span>
                                        <span class="text-[9px] text-rose-300/70 uppercase tracking-widest">Coupure DMCR</span>
                                    </div>
                                    <InputSwitch v-model="form.dmcr_trip" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER SANS BORDURE POUR LE LOOK QUANTUM -->
            <template #footer>
                <div class="flex justify-between items-center w-full px-6 pb-2 pt-2 border-t border-slate-100">
                    <Button label="Annuler" icon="pi pi-times" class="p-button-text p-button-secondary font-bold" @click="showDialog = false" />
                    <Button :label="isEditing ? 'Enregistrer les modifications' : 'Créer le Transformateur'"
                            icon="pi pi-save"
                            class="px-8 py-3 !rounded-xl font-black shadow-lg shadow-primary-200"
                            @click="saveTransformer"
                            :loading="form.processing" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* Animations et styles généraux */
.dashboard-container {
    animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Glassmorphism panel (Header) */
.glass-panel {
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(255,255,255,0.8);
}

.custom-dropdown {
    @apply !bg-slate-50 !border-none !rounded-xl !shadow-none;
}
:deep(.p-dropdown-label) {
    @apply !text-xs !font-black !text-slate-600;
}

/* Tableau ERP */
.custom-erp-table :deep(.p-datatable-thead > tr > th) {
    background: #ffffff;
    color: #94a3b8;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.custom-erp-table :deep(.p-datatable-tbody > tr) {
    transition: all 0.2s;
    border-bottom: 1px dashed #f1f5f9;
}

.custom-erp-table :deep(.p-datatable-tbody > tr:hover) {
    background: #f8fafc;
    transform: scale(1.001);
}

.custom-erp-table :deep(.p-datatable-tbody > tr > td) {
    padding: 1rem;
    border: none;
}

/* ========================================== */
/* STYLES MODALE QUANTUM / ULTIMATE PRO       */
/* ========================================== */
:deep(.quantum-dialog .p-dialog-header) {
    display: none !important; /* On cache le header natif car on a un header custom absolu */
}
:deep(.quantum-dialog .p-dialog-content) {
    padding: 0 !important;
    background: #ffffff;
    border-radius: 1rem;
}
:deep(.quantum-dialog) {
    border: none;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Inputs dans la modale */
:deep(.quantum-input) {
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    padding: 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s;
}
:deep(.quantum-input:focus), :deep(.p-inputtext:focus) {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

/* SelectButton custom dans le header sombre */
:deep(.custom-dark-priority .p-button) {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #94a3b8;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    padding: 0.5rem 1rem;
}
:deep(.custom-dark-priority .p-button.p-highlight) {
    background: #3b82f6; /* Blue-500 */
    border-color: #3b82f6;
    color: #ffffff;
}

/* Custom InputSwitch pour le panneau sombre */
:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
    background: #ef4444; /* Rose-500 par défaut pour les alarmes */
}
</style>
