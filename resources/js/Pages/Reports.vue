<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ColorPicker from 'primevue/colorpicker';
import Slider from 'primevue/slider';
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

// --- DATA SOURCES DE TEST ---
const dataOptions = [
    { label: 'Performance Commerciale', value: 'sales', icon: 'pi pi-shopping-cart' },
    { label: 'Taux de Conversion', value: 'conversion', icon: 'pi pi-users' },
    { label: 'Flux de Trésorerie', value: 'cashflow', icon: 'pi pi-money-bill' },
    { label: 'Satisfaction Client', value: 'csat', icon: 'pi pi-heart' },
];

const toast = useToast();
const localReports = ref([]);
const widgetDialog = ref(false);
const globalConfigDialog = ref(false);
const activeWidgetIndex = ref(null);

// --- CONFIGURATION GLOBALE ---
const studioSettings = ref({
    canvasBg: 'f1f5f9',
    widgetBg: 'ffffff',
    primaryColor: '4f46e5',
    textColor: '1e293b',
    borderRadius: 16,
    gap: 20,
    shadowSize: 'shadow-sm',
    fontFamily: 'Inter, sans-serif'
});

const form = ref({
    id: null,
    name: '',
    chart_type: 'bar',
    data_source: 'sales',
    col_span: 6,
    row_span: 1,
    customStyle: {
        useCustom: false,
        bg: 'ffffff',
        text: '1e293b'
    }
});

// --- LOGIQUE D'ÉDITION GROUPÉE ---
const applyGlobalStyles = () => {
    localReports.value = localReports.value.map(widget => ({
        ...widget,
        customStyle: {
            ...widget.customStyle,
            useCustom: false // Réinitialise le style local pour suivre le global
        }
    }));
    globalConfigDialog.value = false;
    toast.add({ severity: 'success', summary: 'Style Global Appliqué', detail: 'Tous les widgets ont été harmonisés.' });
};

// --- GESTION DES WIDGETS ---
const openWidgetSettings = (widget = null, index = null) => {
    if (widget) {
        form.value = JSON.parse(JSON.stringify(widget));
        activeWidgetIndex.value = index;
    } else {
        form.value = {
            id: Date.now(),
            name: 'Nouveau Rapport',
            chart_type: 'bar',
            data_source: 'sales',
            col_span: 4,
            row_span: 1,
            customStyle: { useCustom: false, bg: 'ffffff', text: '1e293b' }
        };
    }
    widgetDialog.value = true;
};

const saveWidget = () => {
    if (activeWidgetIndex.value !== null) {
        localReports.value[activeWidgetIndex.value] = { ...form.value };
    } else {
        localReports.value.push({ ...form.value });
    }
    widgetDialog.value = false;
    activeWidgetIndex.value = null;
};

// --- GÉNÉRATEUR DE TEST INITIAL ---
onMounted(() => {
    localReports.value = [
        { id: 1, name: 'Revenus Annuels', chart_type: 'line', data_source: 'sales', col_span: 8, row_span: 2, customStyle: { useCustom: false } },
        { id: 2, name: 'Utilisateurs', chart_type: 'kpi', data_source: 'conversion', col_span: 4, row_span: 1, customStyle: { useCustom: false } },
        { id: 3, name: 'Objectif Q4', chart_type: 'doughnut', data_source: 'csat', col_span: 4, row_span: 1, customStyle: { useCustom: false } },
    ];
});
</script>

<template>
    <AppLayout>
        <Head title="Studio de Rapport" />
        <Toast />

        <div class="flex flex-col h-screen overflow-hidden">
            <div class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between z-30">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <div class="bg-indigo-600 w-8 h-8 rounded-lg flex items-center justify-center text-white">
                            <i class="pi pi-chart-line text-sm"></i>
                        </div>
                        <h1 class="text-sm font-black tracking-tight text-slate-800 uppercase">Pro Studio Builder</h1>
                    </div>
                    <span class="h-6 w-px bg-slate-200"></span>
                    <div class="flex gap-1">
                        <Button label="Configurer Dashboard" icon="pi pi-sliders-v" class="p-button-text p-button-sm !text-slate-600" @click="globalConfigDialog = true" />
                        <Button label="Ajouter Widget" icon="pi pi-plus-circle" class="p-button-text p-button-sm !text-slate-600" @click="openWidgetSettings()" />
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button icon="pi pi-eye" class="p-button-rounded p-button-secondary p-button-text" />
                    <Button label="Exporter Rapport" icon="pi pi-file-pdf" class="p-button-primary !rounded-xl px-4 shadow-lg shadow-indigo-100" />
                </div>
            </div>

            <div class="flex-grow flex overflow-hidden">
                <div class="flex-grow overflow-y-auto p-12 transition-all duration-700"
                     :style="{ backgroundColor: '#' + studioSettings.canvasBg }">

                    <div class="max-w-6xl mx-auto">
                        <div class="flex items-center justify-between mb-12">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-slate-800 rounded-xl flex items-center justify-center text-white font-bold">A</div>
                                <h2 class="text-xl font-bold tracking-tighter" :style="{ color: '#' + studioSettings.textColor }">ANALYTICS REPORT</h2>
                            </div>
                            <Tag value="Confidentiel - 2025" severity="secondary" class="!bg-white/50 backdrop-blur-md" />
                        </div>

                        <draggable
                            v-model="localReports"
                            item-key="id"
                            class="grid grid-cols-12"
                            :style="{ gap: studioSettings.gap + 'px' }"
                            handle=".drag-handle"
                            ghost-class="ghost-card"
                        >
                            <template #item="{ element, index }">
                                <div
                                    class="group relative flex flex-col transition-all duration-500 border border-transparent hover:border-indigo-400"
                                    :style="{
                                        gridColumn: `span ${element.col_span}`,
                                        gridRow: `span ${element.row_span}`,
                                        backgroundColor: element.customStyle?.useCustom ? '#' + element.customStyle.bg : '#' + studioSettings.widgetBg,
                                        borderRadius: studioSettings.borderRadius + 'px',
                                        color: element.customStyle?.useCustom ? '#' + element.customStyle.text : '#' + studioSettings.textColor
                                    }"
                                    :class="studioSettings.shadowSize"
                                >
                                    <div class="absolute -top-3 -right-3 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                                        <Button icon="pi pi-cog" class="p-button-rounded p-button-primary shadow-xl !w-8 !h-8" @click="openWidgetSettings(element, index)" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger shadow-xl !w-8 !h-8" @click="localReports.splice(index, 1)" />
                                    </div>

                                    <div class="drag-handle absolute top-4 left-4 cursor-move opacity-0 group-hover:opacity-100 text-slate-300">
                                        <i class="pi pi-th-large"></i>
                                    </div>

                                    <div class="p-8 flex flex-col h-full">
                                        <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-40 mb-2">{{ element.name }}</span>

                                        <div class="flex-grow flex items-center justify-center">
                                            <template v-if="element.chart_type === 'kpi'">
                                                <div class="text-center">
                                                    <div class="text-5xl font-black leading-none tracking-tighter">
                                                        {{ Math.floor(Math.random() * 80) + 20 }}<span class="text-indigo-500">.4</span>
                                                    </div>
                                                    <div class="text-xs font-bold mt-2 opacity-60">{{ element.data_source }}</div>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div class="w-full flex flex-col gap-4">
                                                    <div class="flex items-end gap-2 h-24">
                                                        <div v-for="i in 6" :key="i"
                                                             class="flex-grow bg-indigo-500/20 rounded-t-lg transition-all duration-1000"
                                                             :style="{ height: Math.floor(Math.random() * 100) + '%', backgroundColor: i % 2 === 0 ? '#' + studioSettings.primaryColor : '#' + studioSettings.primaryColor + '44' }">
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="globalConfigDialog" modal header="Paramètres Globaux du Design" :style="{ width: '35rem' }" class="p-fluid">
            <div class="grid grid-cols-2 gap-6 pt-4">
                <div class="col-span-2 flex items-center gap-3 p-4 bg-indigo-50 rounded-2xl border border-indigo-100 mb-2">
                    <i class="pi pi-info-circle text-indigo-600 text-xl"></i>
                    <p class="text-xs text-indigo-700 m-0 leading-relaxed font-medium">Les changements ici s'appliqueront à <b>tous les widgets</b> n'ayant pas de style personnalisé activé.</p>
                </div>

                <div class="field">
                    <label class="font-bold text-xs uppercase tracking-widest text-slate-400">Fond Canvas</label>
                    <div class="flex items-center gap-3 mt-2">
                        <ColorPicker v-model="studioSettings.canvasBg" />
                        <span class="text-sm font-mono uppercase">#{{ studioSettings.canvasBg }}</span>
                    </div>
                </div>
                <div class="field">
                    <label class="font-bold text-xs uppercase tracking-widest text-slate-400">Fond Widgets</label>
                    <div class="flex items-center gap-3 mt-2">
                        <ColorPicker v-model="studioSettings.widgetBg" />
                        <span class="text-sm font-mono uppercase">#{{ studioSettings.widgetBg }}</span>
                    </div>
                </div>
                <div class="field">
                    <label class="font-bold text-xs uppercase tracking-widest text-slate-400">Couleur Principale</label>
                    <div class="flex items-center gap-3 mt-2">
                        <ColorPicker v-model="studioSettings.primaryColor" />
                    </div>
                </div>
                <div class="field">
                    <label class="font-bold text-xs uppercase tracking-widest text-slate-400">Bords Arrondis ({{ studioSettings.borderRadius }}px)</label>
                    <Slider v-model="studioSettings.borderRadius" :min="0" :max="40" class="mt-4" />
                </div>
            </div>
            <template #footer>
                <Button label="Réinitialiser tout le Dashboard" icon="pi pi-check" class="p-button-primary !rounded-xl p-4 shadow-lg" @click="applyGlobalStyles" />
            </template>
        </Dialog>

        <Dialog v-model:visible="widgetDialog" modal header="Propriétés du Widget" :style="{ width: '45rem' }" class="p-fluid">
            <TabView>
                <TabPanel header="Contenu & Données">
                    <div class="grid grid-cols-2 gap-6 pt-4">
                        <div class="col-span-2">
                            <label class="font-bold text-slate-700 block mb-2">Titre du Rapport</label>
                            <InputText v-model="form.name" class="!rounded-xl" />
                        </div>
                        <div>
                            <label class="font-bold text-slate-700 block mb-2">Source de Données</label>
                            <Dropdown v-model="form.data_source" :options="dataOptions" optionLabel="label" optionValue="value" class="!rounded-xl" />
                        </div>
                        <div>
                            <label class="font-bold text-slate-700 block mb-2">Type de Graphique</label>
                            <SelectButton v-model="form.chart_type" :options="['bar', 'line', 'kpi', 'doughnut']" class="compact-select">
                                <template #option="slotProps"><i :class="'pi pi-chart-' + slotProps.option"></i></template>
                            </SelectButton>
                        </div>
                        <div class="col-span-2 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <span class="block font-bold text-slate-700 mb-4">Dimensions sur la Grille</span>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs">Largeur ({{ form.col_span }}/12)</label>
                                    <Slider v-model="form.col_span" :min="2" :max="12" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="text-xs">Hauteur (Lignes)</label>
                                    <InputNumber v-model="form.row_span" showButtons buttonLayout="horizontal" :min="1" :max="3" />
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>
                <TabPanel header="Style Local (Canva-like)">
                    <div class="flex flex-col gap-6 pt-4">
                        <div class="flex items-center gap-3 p-3 border-2 border-dashed border-slate-200 rounded-2xl">
                            <i class="pi pi-palette text-slate-400"></i>
                            <span class="text-sm font-medium">Activer le style spécifique pour ce widget ?</span>
                            <div class="ml-auto"><input type="checkbox" v-model="form.customStyle.useCustom" class="w-5 h-5" /></div>
                        </div>

                        <div v-if="form.customStyle.useCustom" class="grid grid-cols-2 gap-4 animate-fadein">
                            <div class="field">
                                <label class="block font-bold mb-2">Fond du Widget</label>
                                <ColorPicker v-model="form.customStyle.bg" />
                            </div>
                            <div class="field">
                                <label class="block font-bold mb-2">Couleur du Texte</label>
                                <ColorPicker v-model="form.customStyle.text" />
                            </div>
                        </div>
                        <div v-else class="text-center py-10 text-slate-400 italic text-sm">
                            Ce widget suit les paramètres du Dashboard Global.
                        </div>
                    </div>
                </TabPanel>
            </TabView>
            <template #footer>
                <Button label="Valider" icon="pi pi-check" class="p-button-primary !rounded-xl px-8" @click="saveWidget" />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.ghost-card {
    opacity: 0.1;
    transform: scale(0.9);
}

.animate-fadein {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Custom Grille */
.grid-cols-12 {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-flow: dense;
}

/* PrimeVue SelectButton Compact */
:deep(.p-selectbutton .p-button) {
    @apply border-slate-200 !text-slate-400;
}
:deep(.p-selectbutton .p-button.p-highlight) {
    @apply !bg-indigo-600 !text-white !border-indigo-600;
}
</style>
