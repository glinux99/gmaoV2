<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';

// Moteur de Graphiques & Export
import { Bar, Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend, BarElement,
    CategoryScale, LinearScale, PointElement, LineElement, ArcElement
} from 'chart.js';
import html2pdf from 'html2pdf.js';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ColorPicker from 'primevue/colorpicker';
import Slider from 'primevue/slider';
import SelectButton from 'primevue/selectbutton';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import FileUpload from 'primevue/fileupload';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, ArcElement);

const dataOptions = [
    { label: 'Performance Commerciale', value: 'sales' },
    { label: 'Taux de Conversion', value: 'conversion' },
    { label: 'Flux de Trésorerie', value: 'cashflow' },
    { label: 'Satisfaction Client', value: 'csat' },
];

const toast = useToast();
const widgetDialog = ref(false);
const globalConfigDialog = ref(false);
const isPreviewMode = ref(false);
const activeWidgetIndex = ref(null);

// --- GESTION MULTI-PAGES ---
const currentPageIndex = ref(0);
const pages = ref([
    { id: Date.now(), name: 'Page 1', widgets: [] }
]);

const currentWidgets = computed({
    get: () => pages.value[currentPageIndex.value].widgets,
    set: (val) => pages.value[currentPageIndex.value].widgets = val
});

const addPage = () => {
    pages.value.push({ id: Date.now(), name: `Page ${pages.value.length + 1}`, widgets: [] });
    currentPageIndex.value = pages.value.length - 1;
};

const removePage = (index) => {
    if (pages.value.length > 1) {
        pages.value.splice(index, 1);
        currentPageIndex.value = Math.max(0, index - 1);
    }
};

// --- CONFIGURATION DU STUDIO ---
const studioSettings = ref({
    canvasBg: 'e2e8f0',
    paperBg: 'ffffff',
    primaryColor: '4f46e5',
    textColor: '1e293b',
    borderRadius: 12,
    gap: 24,
    pagePadding: 60,
    dashboardTitle: 'ANALYTICS REPORT PRO',
    titlePos: { x: 60, y: 50 },
    titleStyle: { size: 48, color: '1e293b', weight: '900', letterSpacing: -2 },
    footerText: '© 2025 BUSINESS INTELLIGENCE - DOCUMENT CONFIDENTIEL',
    footerPos: { x: 60, y: 740 },
    footerStyle: { size: 10, color: '94a3b8', spacing: 2 },
    logoUrl: null,
    logoWidth: 100,
    logoPos: { x: 950, y: 40 },
});

const form = ref({
    id: null,
    name: '',
    chart_type: 'bar',
    data_source: 'sales',
    col_span: 6,
    row_span: 1,
    customStyle: { useCustom: false, bg: 'ffffff', text: '1e293b' }
});

// --- DRAG & DROP ---
const activeDrag = ref(null);
let dragOffset = { x: 0, y: 0 };

const startDragging = (target, e) => {
    if (isPreviewMode.value) return;
    activeDrag.value = target;
    dragOffset.x = e.clientX - target.x;
    dragOffset.y = e.clientY - target.y;
    window.addEventListener('mousemove', doDrag);
    window.addEventListener('mouseup', stopDrag);
};

const doDrag = (e) => {
    if (activeDrag.value) {
        activeDrag.value.x = e.clientX - dragOffset.x;
        activeDrag.value.y = e.clientY - dragOffset.y;
    }
};

const stopDrag = () => {
    activeDrag.value = null;
    window.removeEventListener('mousemove', doDrag);
};

const generateChartData = (label) => ({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
    datasets: [{
        label: label,
        backgroundColor: '#' + studioSettings.value.primaryColor,
        borderColor: '#' + studioSettings.value.primaryColor,
        data: Array.from({ length: 6 }, () => Math.floor(Math.random() * 100)),
        borderRadius: 6,
        tension: 0.4
    }]
});

// --- EXPORT PDF SANS ERREUR DE NODE ---
const exportPDF = async () => {
    const originalPageIndex = currentPageIndex.value;
    isPreviewMode.value = true;

    toast.add({ severity: 'info', summary: 'Export en cours', detail: 'Veuillez patienter...' });

    // Configuration
    const opt = {
        margin: 0,
        filename: `${studioSettings.value.dashboardTitle}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, logging: false },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
    };

    try {
        // Initialisation de l'objet html2pdf
        let worker = html2pdf().set(opt).from(document.getElementById('report-canvas')).toPdf();

        // Ajout des pages suivantes
        for (let i = 0; i < pages.value.length; i++) {
            currentPageIndex.value = i;
            await nextTick();
            // Petit délai pour le rendu des graphiques Chart.js
            await new Promise(resolve => setTimeout(resolve, 600));

            const element = document.getElementById('report-canvas');

            if (i === 0) {
                worker = worker.get('pdf').then((pdf) => { /* page 1 déjà chargée via .from() */ });
            } else {
                worker = worker.get('pdf').then((pdf) => {
                    pdf.addPage();
                    return html2pdf().set(opt).from(element).toContainer().toCanvas().toPdf();
                });
            }
        }

        await worker.save();
        toast.add({ severity: 'success', summary: 'Export terminé' });
    } catch (err) {
        console.error(err);
        toast.add({ severity: 'error', summary: 'Erreur Export', detail: 'Impossible de générer le PDF' });
    } finally {
        currentPageIndex.value = originalPageIndex;
        isPreviewMode.value = false;
    }
};

const openWidgetSettings = (widget = null, index = null) => {
    if (widget) {
        form.value = JSON.parse(JSON.stringify(widget));
        activeWidgetIndex.value = index;
    } else {
        form.value = {
            id: Date.now(), name: 'Nouveau Graphique', chart_type: 'bar', data_source: 'sales',
            col_span: 6, row_span: 1, customStyle: { useCustom: false, bg: 'ffffff', text: '1e293b' }
        };
    }
    widgetDialog.value = true;
};

const saveWidget = () => {
    const data = { ...form.value, chartData: generateChartData(form.value.name) };
    if (activeWidgetIndex.value !== null) currentWidgets.value[activeWidgetIndex.value] = data;
    else currentWidgets.value.push(data);
    widgetDialog.value = false;
    activeWidgetIndex.value = null;
};

const onLogoUpload = (event) => {
    const file = event.files[0];
    const reader = new FileReader();
    reader.onload = (e) => { studioSettings.value.logoUrl = e.target.result; };
    reader.readAsDataURL(file);
};

onMounted(() => {
    pages.value[0].widgets = [
        { id: 1, name: 'Revenus Annuels', chart_type: 'line', col_span: 8, row_span: 1, chartData: generateChartData('Revenus'), customStyle: { useCustom: false } },
        { id: 2, name: 'Satisfaction', chart_type: 'kpi', col_span: 4, row_span: 1, customStyle: { useCustom: false } }
    ];
});
</script>

<template>
    <AppLayout>
        <Head title="Studio Pro Builder" />
        <Toast />

        <div class="flex flex-col h-screen bg-slate-900 overflow-hidden font-sans">
            <div class="h-16 bg-slate-800 border-b border-white/5 px-6 flex items-center justify-between z-50 shadow-xl">
                <div class="flex items-center gap-4">
                    <div class="bg-indigo-600 p-2 rounded-xl shadow-lg shadow-indigo-500/20">
                        <i class="pi pi-palette text-white"></i>
                    </div>
                    <span class="text-white font-black text-xs uppercase tracking-tighter">Report Studio <span class="text-indigo-400">v3.5</span></span>
                </div>

                <div class="flex items-center gap-3">
                    <Button :icon="isPreviewMode ? 'pi pi-pencil' : 'pi pi-eye'" :label="isPreviewMode ? 'Éditer' : 'Aperçu'"
                            class="p-button-text p-button-sm !text-slate-300" @click="isPreviewMode = !isPreviewMode" />
                    <Button label="Design" icon="pi pi-sliders-h" class="p-button-text p-button-sm !text-slate-300" @click="globalConfigDialog = true" />
                    <Button label="Ajouter" icon="pi pi-plus" class="p-button-indigo !rounded-xl shadow-lg" @click="openWidgetSettings()" />
                    <Button label="Exporter PDF" icon="pi pi-file-export" class="p-button-success !rounded-xl ml-2 shadow-lg" @click="exportPDF" />
                </div>
            </div>

            <div class="flex-grow overflow-auto p-8 flex flex-col items-center custom-scrollbar" :style="{ backgroundColor: '#' + studioSettings.canvasBg }">

                <div v-if="!isPreviewMode" class="flex items-center gap-2 mb-6 bg-slate-800/50 p-1.5 rounded-2xl border border-white/5 shadow-2xl">
                    <div v-for="(page, idx) in pages" :key="page.id" class="flex items-center group relative">
                        <Button :label="page.name" @click="currentPageIndex = idx"
                            :class="['p-button-sm !rounded-xl !px-4 !py-2 !transition-all !border-none', currentPageIndex === idx ? 'p-button-indigo shadow-lg shadow-indigo-500/20' : 'p-button-text !text-slate-400']"
                        />
                        <button v-if="pages.length > 1" @click.stop="removePage(idx)" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px] z-10">
                           <i class="pi pi-times"></i>
                        </button>
                    </div>
                    <Button icon="pi pi-plus" @click="addPage" class="p-button-rounded p-button-text p-button-sm !text-indigo-400 ml-1" />
                </div>

                <div id="report-canvas" class="transition-all duration-500 shadow-2xl relative overflow-hidden flex-shrink-0"
                     :style="{ width: '1120px', height: '791px', backgroundColor: '#' + studioSettings.paperBg, borderRadius: isPreviewMode ? '0px' : '8px' }">

                    <div class="absolute cursor-move select-none group" :style="{ left: studioSettings.titlePos.x + 'px', top: studioSettings.titlePos.y + 'px', zIndex: 100 }" @mousedown="startDragging(studioSettings.titlePos, $event)">
                        <input v-model="studioSettings.dashboardTitle" :disabled="isPreviewMode" class="bg-transparent border-none outline-none focus:ring-1 ring-indigo-500 rounded p-1"
                               :style="{ fontSize: studioSettings.titleStyle.size + 'px', color: '#' + studioSettings.titleStyle.color, fontWeight: studioSettings.titleStyle.weight, letterSpacing: studioSettings.titleStyle.letterSpacing + 'px', width: '850px' }" />
                    </div>

                    <div class="absolute cursor-move group" :style="{ left: studioSettings.logoPos.x + 'px', top: studioSettings.logoPos.y + 'px', zIndex: 101 }" @mousedown="startDragging(studioSettings.logoPos, $event)">
                        <div v-if="studioSettings.logoUrl" class="relative">
                            <img :src="studioSettings.logoUrl" :style="{ width: studioSettings.logoWidth + 'px' }" class="object-contain" />
                        </div>
                        <div v-else-if="!isPreviewMode" class="border-2 border-dashed border-slate-200 rounded-xl p-4 bg-white/50">
                            <FileUpload mode="basic" @select="onLogoUpload" :auto="true" chooseLabel="Logo" class="p-button-text p-button-sm" />
                        </div>
                    </div>

                    <div class="absolute inset-0 pointer-events-none" :style="{ padding: studioSettings.pagePadding + 'px', paddingTop: '160px' }">
                        <draggable v-model="currentWidgets" item-key="id" class="grid grid-cols-12 pointer-events-auto" :style="{ gap: studioSettings.gap + 'px' }" handle=".drag-handle" :disabled="isPreviewMode">
                            <template #item="{ element, index }">
                                <div class="group relative flex flex-col transition-all border border-transparent"
                                     :style="{ gridColumn: `span ${element.col_span}`, gridRow: `span ${element.row_span}`, backgroundColor: element.customStyle?.useCustom ? '#' + element.customStyle.bg : '#ffffff', borderRadius: studioSettings.borderRadius + 'px', color: element.customStyle?.useCustom ? '#' + element.customStyle.text : '#' + studioSettings.textColor, boxShadow: isPreviewMode ? 'none' : '0 4px 6px -1px rgb(0 0 0 / 0.1)' }">
                                    <div v-if="!isPreviewMode" class="drag-handle absolute -left-3 top-4 cursor-move opacity-0 group-hover:opacity-100 bg-white shadow-md p-1.5 rounded-md text-slate-300 z-20"><i class="pi pi-th-large"></i></div>
                                    <div v-if="!isPreviewMode" class="absolute -top-3 -right-3 flex gap-2 opacity-0 group-hover:opacity-100 z-20">
                                        <Button icon="pi pi-cog" class="p-button-rounded !w-8 !h-8 shadow-lg" @click="openWidgetSettings(element, index)" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger !w-8 !h-8 shadow-lg" @click="currentWidgets.splice(index, 1)" />
                                    </div>
                                    <div class="p-8 h-full flex flex-col">
                                        <input v-model="element.name" :disabled="isPreviewMode" class="canvas-input-small text-[10px] font-black uppercase tracking-[0.3em] opacity-40 mb-6 w-full" />
                                        <div class="flex-grow flex items-center justify-center min-h-[160px]">
                                            <template v-if="element.chart_type === 'kpi'"><div class="text-center"><div class="text-6xl font-black">{{ Math.floor(Math.random() * 90) + 10 }}<span class="text-indigo-500">.4%</span></div></div></template>
                                            <template v-else-if="element.chart_type === 'bar'"><Bar :data="element.chartData" :options="{ responsive: true, maintainAspectRatio: false }" /></template>
                                            <template v-else-if="element.chart_type === 'line'"><Line :data="element.chartData" :options="{ responsive: true, maintainAspectRatio: false }" /></template>
                                            <template v-else-if="element.chart_type === 'doughnut'"><Doughnut :data="element.chartData" :options="{ responsive: true, maintainAspectRatio: false }" /></template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <div class="absolute cursor-move select-none group" :style="{ left: studioSettings.footerPos.x + 'px', top: studioSettings.footerPos.y + 'px', zIndex: 100 }" @mousedown="startDragging(studioSettings.footerPos, $event)">
                        <input v-model="studioSettings.footerText" :disabled="isPreviewMode" class="bg-transparent border-none outline-none w-[1000px] text-center"
                               :style="{ fontSize: studioSettings.footerStyle.size + 'px', color: '#' + studioSettings.footerStyle.color, letterSpacing: studioSettings.footerStyle.spacing + 'px' }" />
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="globalConfigDialog" modal header="Configuration du Studio" :style="{ width: '30rem' }" class="p-fluid">
            <TabView>
                <TabPanel header="Couleurs & Grille">
                    <div class="flex flex-col gap-6 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="field"><label class="font-bold text-xs uppercase text-slate-400 block mb-2">Plan de travail</label><ColorPicker v-model="studioSettings.canvasBg" /></div>
                            <div class="field"><label class="font-bold text-xs uppercase text-slate-400 block mb-2">Papier PDF</label><ColorPicker v-model="studioSettings.paperBg" /></div>
                        </div>
                        <div class="field"><label class="font-bold text-xs uppercase text-slate-400 block mb-4">Gouttière Grille ({{ studioSettings.gap }}px)</label><Slider v-model="studioSettings.gap" :min="0" :max="60" /></div>
                        <div class="field"><label class="font-bold text-xs uppercase text-slate-400 block mb-4">Arrondis Widgets ({{ studioSettings.borderRadius }}px)</label><Slider v-model="studioSettings.borderRadius" :min="0" :max="40" /></div>
                    </div>
                </TabPanel>
            </TabView>
        </Dialog>

        <Dialog v-model:visible="widgetDialog" modal header="Propriétés du Widget" :style="{ width: '40rem' }" class="p-fluid">
            <TabView>
                <TabPanel header="Contenu">
                    <div class="grid grid-cols-2 gap-4 py-4">
                        <div class="col-span-2"><label class="font-bold block mb-2">Titre du Widget</label><InputText v-model="form.name" /></div>
                        <div class="field"><label class="font-bold block mb-2">Type</label><SelectButton v-model="form.chart_type" :options="['bar', 'line', 'kpi', 'doughnut']" /></div>
                        <div class="field"><label class="font-bold block mb-2">Données Source</label><Dropdown v-model="form.data_source" :options="dataOptions" optionLabel="label" optionValue="value" /></div>
                        <div class="col-span-2 bg-slate-50 p-6 rounded-2xl"><label class="font-bold block mb-4">Largeur Grille ({{ form.col_span }} colonnes)</label><Slider v-model="form.col_span" :min="2" :max="12" /></div>
                    </div>
                </TabPanel>
                <TabPanel header="Style Local">
                    <div class="py-4">
                        <div class="flex items-center justify-between p-4 bg-indigo-50 rounded-xl mb-6">
                            <span class="font-bold">Outrepasser le design global</span>
                            <input type="checkbox" v-model="form.customStyle.useCustom" class="w-5 h-5" />
                        </div>
                        <div v-if="form.customStyle.useCustom" class="grid grid-cols-2 gap-4">
                            <div><label class="block mb-2 font-bold">Fond Widget</label><ColorPicker v-model="form.customStyle.bg" /></div>
                            <div><label class="block mb-2 font-bold">Couleur Texte</label><ColorPicker v-model="form.customStyle.text" /></div>
                        </div>
                    </div>
                </TabPanel>
            </TabView>
            <template #footer><Button label="Enregistrer" icon="pi pi-check" @click="saveWidget" class="p-button-indigo !rounded-xl" /></template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.grid-cols-12 { display: grid; grid-template-columns: repeat(12, 1fr); grid-auto-flow: dense; }
#report-canvas { background-image: radial-gradient(#e2e8f0 1.2px, transparent 1.2px); background-size: 25px 25px; }
.canvas-input-small { background: transparent; border: none; outline: none; border-bottom: 1px dashed transparent; transition: all 0.2s; width: 100%; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
:deep(.p-selectbutton .p-button.p-highlight) { background: #4f46e5 !important; color: white !important; }
</style>
