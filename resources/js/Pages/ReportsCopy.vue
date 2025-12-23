<script setup>
import { ref, computed, onMounted, nextTick, reactive, watch, markRaw } from 'vue';
import { Head } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { useToast } from 'primevue/usetoast';

// --- CORE ENGINE : CHARTS ---
import { Bar, Line, Doughnut, Pie, Radar, PolarArea, Scatter } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend, BarElement,
    CategoryScale, LinearScale, PointElement, LineElement, ArcElement,
    RadialLinearScale, Filler, Decimation, Colors
} from 'chart.js';
import html2pdf from 'html2pdf.js';

// --- UI : PRIMEVUE MEGA-SET ---
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ColorPicker from 'primevue/colorpicker';
import Slider from 'primevue/slider';
import SelectButton from 'primevue/selectbutton';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import FileUpload from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Divider from 'primevue/divider';
import Knob from 'primevue/knob';
import SpeedDial from 'primevue/speeddial';
import SplitButton from 'primevue/splitbutton';
import InputSwitch from 'primevue/inputswitch';
import Tag from 'primevue/tag';
import Sidebar from 'primevue/sidebar';
import Fieldset from 'primevue/fieldset';
import OverlayPanel from 'primevue/overlaypanel';
import Panel from 'primevue/panel';
import Menu from 'primevue/menu';
import TieredMenu from 'primevue/tieredmenu';
import InlineMessage from 'primevue/inlinemessage';
import ScrollPanel from 'primevue/scrollpanel';
import TooltipVue from 'primevue/tooltip';

ChartJS.register(
    Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale,
    PointElement, LineElement, ArcElement, RadialLinearScale, Filler, Decimation, Colors
);

// --- SERVICES & REFS ---
const toast = useToast();
const isPreview = ref(false);
const isPresenting = ref(false);
const isLoading = ref(false);
const sidebarVisible = ref(true);
const activeTab = ref(0);
const configModal = ref(false);
const activeWidgetIdx = ref(null);
const zoomLevel = ref(0.7); // Pour gérer les grands formats A2/A3

// --- FORMATS STANDARDS ISO & US ---
const formats = [
    { name: 'A0 Poster', w: 3179, h: 4494, group: 'Poster' },
    { name: 'A1 Plan', w: 2245, h: 3179, group: 'Poster' },
    { name: 'A2 Grand Format', w: 1587, h: 2245, group: 'Business' },
    { name: 'A3 Document', w: 1123, h: 1587, group: 'Business' },
    { name: 'A4 Standard', w: 794, h: 1123, group: 'Business' },
    { name: 'US Letter', w: 816, h: 1056, group: 'US' },
    { name: 'Web Dashboard', w: 1920, h: 1080, group: 'Digital' }
];

// --- ÉTAT GLOBAL DU DOCUMENT ---
const studio = reactive({
    docTitle: {
        text: 'PROJET ANALYTIQUE ALPHA',
        x: 80, y: 80,
        style: { size: 56, color: '0f172a', weight: '900', align: 'left', letterSpacing: -3, uppercase: true, shadow: false }
    },
    docSubtitle: {
        text: 'Rapport de synthèse stratégique - Direction Générale',
        x: 80, y: 145,
        style: { size: 16, color: '64748b', weight: '500', align: 'left', italic: false }
    },
    config: {
        format: formats[4],
        orientation: 'landscape',
        paperBg: 'ffffff',
        canvasBg: '0a0a0c',
        primaryColor: '6366f1',
        secondaryColor: '10b981',
        borderRadius: 24,
        spacing: 30,
        showGrid: true,
        gridSize: 40,
        gridOpacity: 0.05,
        snapToGrid: true
    },
    branding: {
        logoUrl: 'https://cdn-icons-png.flaticon.com/512/5968/5968282.png',
        logoSize: 120,
        logoX: 1000,
        logoY: 80,
        footerText: 'Quantum Studio Pro © 2025 - Confidentialité Haute'
    }
});

const pages = ref([
    { id: 'p1', name: 'Synthèse Executive', widgets: [], background: null }
]);
const currentPageIdx = ref(0);

// --- WIDGET TYPES DEFINITION ---
const componentLibrary = [
    { type: 'chart', label: 'Analyses Graphiques', icon: 'pi pi-chart-bar', category: 'Visualisation' },
    { type: 'kpi', label: 'Scorecard KPI', icon: 'pi pi-bolt', category: 'Performance' },
    { type: 'table', label: 'Grille de Données', icon: 'pi pi-table', category: 'Données' },
    { type: 'text', label: 'Éditeur de Texte', icon: 'pi pi-align-left', category: 'Contenu' },
    { type: 'image', label: 'Média Interactif', icon: 'pi pi-image', category: 'Design' },
    { type: 'progress', label: 'Barre d\'Objectif', icon: 'pi pi-sliders-h', category: 'Visualisation' }
];

// --- GESTION DES COMPOSANTS ---
const currentWidget = ref({});

const openWidgetCreator = (type = 'chart') => {
    currentWidget.value = {
        id: 'w_' + Math.random().toString(36).substr(2, 9),
        type: type,
        name: 'Nouveau Bloc ' + type,
        colSpan: 4, rowSpan: 2,
        chartType: 'bar',
        content: 'Éditez votre texte ici avec le panneau de droite...',
        settings: {
            bg: 'ffffff',
            textColor: '1e293b',
            padding: 24,
            border: true,
            borderColor: 'f1f5f9',
            shadow: 'xl',
            opacity: 100,
            zIndex: 1,
            glass: false,
            blur: 10
        },
        data: {
            labels: ['Q1', 'Q2', 'Q3', 'Q4'],
            values: [45, 78, 52, 91],
            rows: [
                { id: 1, label: 'Service Cloud', val: '1.2M€' },
                { id: 2, label: 'Licences', val: '0.8M€' }
            ]
        }
    };
    activeWidgetIdx.value = null;
    configModal.value = true;
};

const editWidget = (idx) => {
    activeWidgetIdx.value = idx;
    currentWidget.value = JSON.parse(JSON.stringify(pages.value[currentPageIdx.value].widgets[idx]));
    configModal.value = true;
};

const cloneWidget = (idx) => {
    const clone = JSON.parse(JSON.stringify(pages.value[currentPageIdx.value].widgets[idx]));
    clone.id = 'w_' + Date.now();
    pages.value[currentPageIdx.value].widgets.push(clone);
    toast.add({ severity: 'info', summary: 'Duplication', detail: 'Élément cloné.' });
};

const saveWidget = () => {
    if (activeWidgetIdx.value !== null) {
        pages.value[currentPageIdx.value].widgets[activeWidgetIdx.value] = { ...currentWidget.value };
    } else {
        pages.value[currentPageIdx.value].widgets.push({ ...currentWidget.value });
    }
    configModal.value = false;
};

// --- CALCULS DYNAMIQUES DU CANVAS ---
const canvasSize = computed(() => {
    const f = studio.config.format;
    return studio.config.orientation === 'landscape'
        ? { w: f.h > f.w ? f.h : f.w, h: f.h > f.w ? f.w : f.h }
        : { w: f.h > f.w ? f.w : f.h, h: f.h > f.w ? f.h : f.w };
});

// --- CHART RENDERING ---
const getChartData = (w) => {
    return {
        labels: w.data.labels,
        datasets: [{
            label: w.name,
            data: w.data.values,
            backgroundColor: [`#${studio.config.primaryColor}CC`, `#${studio.config.secondaryColor}CC`],
            borderColor: `#${studio.config.primaryColor}`,
            borderWidth: 2,
            borderRadius: 12
        }]
    };
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { display: false }, x: { grid: { display: false } } }
};

// --- DRAG & DROP POSITIONING (TITRES & LOGO) ---
const activeDrag = ref(null);
const startMove = (e, target) => {
    if (isPreview.value) return;
    activeDrag.value = target;
    const startX = e.clientX - target.x;
    const startY = e.clientY - target.y;

    const onMove = (mE) => {
        if (!activeDrag.value) return;
        target.x = mE.clientX - startX;
        target.y = mE.clientY - startY;

        // Snap to grid logic
        if (studio.config.snapToGrid) {
            target.x = Math.round(target.x / studio.config.gridSize) * studio.config.gridSize;
            target.y = Math.round(target.y / studio.config.gridSize) * studio.config.gridSize;
        }
    };
    const onUp = () => {
        activeDrag.value = null;
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onUp);
    };
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
};

// --- EXPORT PDF MULTI-FORMAT ---
const exportPDF = async () => {
    isLoading.value = true;
    const originalZoom = zoomLevel.ref;
    zoomLevel.value = 1.0; // Reset zoom for capture

    const element = document.getElementById('studio-capture-area');
    const opt = {
        margin: 0,
        filename: `${studio.docTitle.text}.pdf`,
        image: { type: 'jpeg', quality: 1.0 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'px', format: [canvasSize.value.w, canvasSize.value.h], orientation: studio.config.orientation === 'landscape' ? 'l' : 'p' }
    };

    try {
        await html2pdf().set(opt).from(element).save();
        toast.add({ severity: 'success', summary: 'Export Réussi', detail: 'Le document PDF a été généré.' });
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de la génération.' });
    } finally {
        zoomLevel.value = originalZoom;
        isLoading.value = false;
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Quantum Designer Pro v5" />
        <Toast position="bottom-right" />

        <div class="studio-container h-screen flex flex-col bg-[#020203] text-slate-300 font-sans overflow-hidden" :class="{'is-presenting': isPresenting}">

            <header class="h-14 border-b border-white/5 bg-black/60 backdrop-blur-xl flex items-center justify-between px-6 z-[200]">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/20">
                            <i class="pi pi-box text-white text-lg"></i>
                        </div>
                        <div class="leading-none">
                            <span class="block text-[10px] font-black tracking-widest text-white uppercase">Quantum <span class="text-indigo-400">Studio</span></span>
                            <span class="text-[8px] text-slate-500 font-bold uppercase">Desktop Publishing Engine v5.0</span>
                        </div>
                    </div>

                    <div class="h-6 w-px bg-white/10 mx-2"></div>

                    <div class="flex bg-white/5 p-1 rounded-xl border border-white/5">
                        <Button @click="isPreview = false" :class="{'!bg-indigo-600 !text-white shadow-lg': !isPreview}" class="p-button-rounded p-button-text p-button-sm !py-1 !px-4 text-[10px] font-black" label="BUILDER" />
                        <Button @click="isPreview = true" :class="{'!bg-indigo-600 !text-white shadow-lg': isPreview}" class="p-button-rounded p-button-text p-button-sm !py-1 !px-4 text-[10px] font-black" label="PREVIEW" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 bg-black/40 p-1 rounded-xl border border-white/5 mr-4">
                        <Button icon="pi pi-minus" class="p-button-text p-button-xs" @click="zoomLevel -= 0.1" />
                        <span class="text-[10px] font-mono px-2">{{ Math.round(zoomLevel * 100) }}%</span>
                        <Button icon="pi pi-plus" class="p-button-text p-button-xs" @click="zoomLevel += 0.1" />
                    </div>

                    <Button @click="isPresenting = !isPresenting" :icon="isPresenting ? 'pi pi-window-minimize' : 'pi pi-window-maximize'" :label="isPresenting ? 'Quitter' : 'Présenter'" class="p-button-secondary p-button-sm" />
                    <SplitButton label="AJOUTER" icon="pi pi-plus" :model="componentLibrary.map(c => ({label: c.label, icon: c.icon, command: () => openWidgetCreator(c.type)}))" class="p-button-indigo p-button-sm" />
                    <Button @click="exportPDF" icon="pi pi-cloud-download" label="PDF" :loading="isLoading" class="p-button-success p-button-sm !px-6" />
                </div>
            </header>

            <div class="flex-grow flex overflow-hidden" :class="{'h-full': isPresenting}">

                <aside v-if="!isPreview" class="w-80 border-r border-white/5 bg-[#08080a] flex flex-col shrink-0">
                    <TabView v-model:activeIndex="activeTab">
                        <TabPanel>
                            <template #header><i class="pi pi-copy mr-2 text-[10px]"></i><span class="text-[9px] font-black uppercase">Structure</span></template>

                            <div class="p-4 space-y-6">
                                <section>
                                    <h3 class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-4">Pages du Document</h3>
                                    <div class="space-y-2">
                                        <div v-for="(p, idx) in pages" :key="p.id"
                                             @click="currentPageIdx = idx"
                                             :class="['group flex items-center justify-between p-3 rounded-xl cursor-pointer border transition-all',
                                                      currentPageIdx === idx ? 'bg-indigo-500/10 border-indigo-500/30 text-white shadow-xl shadow-indigo-500/5' : 'bg-transparent border-transparent text-slate-500 hover:bg-white/5']">
                                            <div class="flex items-center gap-3">
                                                <span class="text-[10px] font-black opacity-30">0{{ idx + 1 }}</span>
                                                <span class="text-xs font-bold">{{ p.name }}</span>
                                            </div>
                                            <i class="pi pi-ellipsis-v text-[10px] opacity-0 group-hover:opacity-100"></i>
                                        </div>
                                        <Button icon="pi pi-plus-circle" label="Ajouter une page" class="p-button-text p-button-secondary p-button-sm w-full !text-[10px] mt-2" @click="pages.push({id: Date.now(), name: 'Nouvelle Section', widgets: []})" />
                                    </div>
                                </section>

                                <Divider class="opacity-5" />

                                <section>
                                    <h3 class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-4">Calques (Page {{ currentPageIdx + 1 }})</h3>
                                    <draggable v-model="pages[currentPageIdx].widgets" item-key="id" class="space-y-1" handle=".drag-handle">
                                        <template #item="{ element, index }">
                                            <div class="p-2 bg-white/5 rounded-lg border border-white/5 flex items-center justify-between group hover:border-indigo-500/40">
                                                <div class="flex items-center gap-3 overflow-hidden">
                                                    <i class="pi pi-bars drag-handle text-[10px] opacity-20 cursor-grab active:cursor-grabbing"></i>
                                                    <span class="text-[11px] font-medium truncate">{{ element.name }}</span>
                                                </div>
                                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button @click="editWidget(index)" class="p-1 hover:text-indigo-400"><i class="pi pi-pencil text-[10px]"></i></button>
                                                    <button @click="cloneWidget(index)" class="p-1 hover:text-emerald-400"><i class="pi pi-copy text-[10px]"></i></button>
                                                    <button @click="pages[currentPageIdx].widgets.splice(index, 1)" class="p-1 hover:text-red-400"><i class="pi pi-trash text-[10px]"></i></button>
                                                </div>
                                            </div>
                                        </template>
                                    </draggable>
                                </section>
                            </div>
                        </TabPanel>

                        <TabPanel>
                            <template #header><i class="pi pi-palette mr-2 text-[10px]"></i><span class="text-[9px] font-black uppercase">Thème</span></template>
                            <div class="p-6 space-y-8">
                                <section>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase mb-4">Format du Support</label>
                                    <Dropdown v-model="studio.config.format" :options="formats" optionLabel="name" optionGroupLabel="group" optionGroupChildren="items" class="p-inputtext-sm w-full mb-3" />
                                    <SelectButton v-model="studio.config.orientation" :options="['portrait', 'landscape']" class="p-button-sm w-full" />
                                </section>

                                <section>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase mb-4">Palette de Marque</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-white/5 p-3 rounded-xl flex flex-col items-center gap-2 border border-white/5">
                                            <ColorPicker v-model="studio.config.primaryColor" />
                                            <span class="text-[9px] font-bold">Accent</span>
                                        </div>
                                        <div class="bg-white/5 p-3 rounded-xl flex flex-col items-center gap-2 border border-white/5">
                                            <ColorPicker v-model="studio.config.paperBg" />
                                            <span class="text-[9px] font-bold">Papier</span>
                                        </div>
                                    </div>
                                </section>

                                <section>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase mb-4">Bordures & Arrondis ({{ studio.config.borderRadius }}px)</label>
                                    <Slider v-model="studio.config.borderRadius" :min="0" :max="60" />
                                </section>
                            </div>
                        </TabPanel>
                    </TabView>
                </aside>

                <section class="flex-grow overflow-auto p-20 bg-[#050505] relative flex justify-center custom-scrollbar" :style="{ backgroundColor: '#' + studio.config.canvasBg }">

                    <div id="studio-capture-area"
                         class="relative transition-all duration-700 shadow-[0_50px_100px_-20px_rgba(0,0,0,0.9)] origin-top border border-white/10"
                         :style="{
                            width: canvasSize.w + 'px',
                            height: canvasSize.h + 'px',
                            backgroundColor: '#' + studio.config.paperBg,
                            transform: `scale(${zoomLevel})`,
                            borderRadius: isPreview ? '0' : '4px'
                         }">

                        <div v-if="!isPreview && studio.config.showGrid" class="absolute inset-0 pointer-events-none"
                             :style="{
                                backgroundImage: `radial-gradient(#${studio.config.gridSize % 2 === 0 ? 'cbd5e1' : '6366f1'} 1.5px, transparent 1.5px)`,
                                backgroundSize: `${studio.config.gridSize}px ${studio.config.gridSize}px`,
                                opacity: studio.config.gridOpacity
                             }">
                        </div>

                        <div class="absolute z-[100] group"
                             :style="{ left: studio.docTitle.x + 'px', top: studio.docTitle.y + 'px' }"
                             @mousedown="startMove($event, studio.docTitle)">
                            <div :class="{'cursor-move border-2 border-dashed border-transparent hover:border-indigo-500/40 p-3': !isPreview}">
                                <h1 :style="{
                                    fontSize: studio.docTitle.style.size + 'px',
                                    color: '#' + studio.docTitle.style.color,
                                    fontWeight: studio.docTitle.style.weight,
                                    letterSpacing: studio.docTitle.style.letterSpacing + 'px',
                                    textTransform: studio.docTitle.style.uppercase ? 'uppercase' : 'none'
                                }" class="leading-none m-0 font-sans select-none">
                                    {{ studio.docTitle.text }}
                                </h1>
                                <p :style="{ color: '#' + studio.docSubtitle.style.color, fontSize: studio.docSubtitle.style.size + 'px' }" class="mt-4 font-medium italic opacity-70">
                                    {{ studio.docSubtitle.text }}
                                </p>
                            </div>
                        </div>

                        <div class="absolute z-[100]"
                             :style="{ left: studio.branding.logoX + 'px', top: studio.branding.logoY + 'px' }"
                             @mousedown="startMove($event, {x: studio.branding.logoX, y: studio.branding.logoY}, (v) => { studio.branding.logoX = v.x; studio.branding.logoY = v.y; })">
                            <div :class="{'cursor-move border-2 border-dashed border-transparent hover:border-indigo-500/40 p-2': !isPreview}">
                                <img :src="studio.branding.logoUrl" :style="{ width: studio.branding.logoSize + 'px' }" class="block pointer-events-none" />
                            </div>
                        </div>

                        <div class="absolute inset-0 p-[80px] pt-[250px] pointer-events-none">
                            <draggable v-model="pages[currentPageIdx].widgets" item-key="id"
                                       class="grid grid-cols-12 auto-rows-min h-full pointer-events-auto gap-8"
                                       handle=".widget-drag-btn" :disabled="isPreview">
                                <template #item="{ element, index }">
                                    <div class="relative flex flex-col group/widget transition-all duration-500"
                                         :style="{
                                            gridColumn: `span ${element.colSpan}`,
                                            gridRow: `span ${element.rowSpan}`,
                                            backgroundColor: element.settings.glass ? 'rgba(255,255,255,0.7)' : '#' + element.settings.bg,
                                            backdropFilter: element.settings.glass ? `blur(${element.settings.blur}px)` : 'none',
                                            borderRadius: studio.config.borderRadius + 'px',
                                            padding: element.settings.padding + 'px',
                                            border: element.settings.border ? `1px solid #${element.settings.borderColor}` : 'none',
                                            boxShadow: isPreview ? 'none' : '0 30px 60px -12px rgba(0,0,0,0.1)',
                                            opacity: element.settings.opacity / 100,
                                            zIndex: element.settings.zIndex
                                         }">

                                        <div v-if="!isPreview" class="absolute -top-3 -right-3 flex gap-2 opacity-0 group-hover/widget:opacity-100 z-[200] transition-all">
                                            <button @click="editWidget(index)" class="w-9 h-9 rounded-xl bg-white shadow-2xl text-indigo-600 flex items-center justify-center hover:scale-110 active:scale-90 border border-slate-100"><i class="pi pi-sliders-h text-xs"></i></button>
                                            <button @click="cloneWidget(index)" class="w-9 h-9 rounded-xl bg-white shadow-2xl text-emerald-600 flex items-center justify-center hover:scale-110 active:scale-90 border border-slate-100"><i class="pi pi-copy text-xs"></i></button>
                                            <button @click="pages[currentPageIdx].widgets.splice(index, 1)" class="w-9 h-9 rounded-xl bg-red-500 shadow-2xl text-white flex items-center justify-center hover:scale-110 active:scale-90"><i class="pi pi-trash text-xs"></i></button>
                                        </div>
                                        <div v-if="!isPreview" class="widget-drag-btn absolute -top-3 -left-3 w-9 h-9 rounded-xl bg-indigo-600 text-white shadow-2xl flex items-center justify-center cursor-move opacity-0 group-hover/widget:opacity-100 transition-all"><i class="pi pi-arrows-alt text-xs"></i></div>

                                        <div class="flex items-center justify-between mb-6 border-b border-black/5 pb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">{{ element.name }}</span>
                                            </div>
                                            <i :class="componentLibrary.find(l => l.type === element.type).icon" class="text-[10px] opacity-20"></i>
                                        </div>

                                        <div class="flex-grow relative overflow-hidden">
                                            <div v-if="element.type === 'chart'" class="h-full">
                                                <component :is="element.chartType === 'bar' ? Bar : (element.chartType === 'line' ? Line : (element.chartType === 'pie' ? Pie : Radar))"
                                                           :data="getChartData(element)" :options="chartOptions" />
                                            </div>

                                            <div v-else-if="element.type === 'kpi'" class="h-full flex flex-col justify-center items-center text-center">
                                                <div class="text-6xl font-black tracking-tighter" :style="{ color: '#' + studio.config.primaryColor }">
                                                    {{ element.data.values[0] }}<span class="text-2xl opacity-30">%</span>
                                                </div>
                                                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mt-2">Conversion Réalisée</p>
                                            </div>

                                            <div v-else-if="element.type === 'text'" class="h-full overflow-y-auto custom-scrollbar-thin">
                                                <div class="prose prose-sm max-w-none" :style="{ color: '#' + element.settings.textColor }">
                                                    {{ element.content }}
                                                </div>
                                            </div>

                                            <div v-else-if="element.type === 'table'" class="h-full overflow-hidden">
                                                <table class="w-full text-left text-[11px]">
                                                    <thead>
                                                        <tr class="border-b-2 border-black/5">
                                                            <th class="py-2 font-black uppercase opacity-40">Item</th>
                                                            <th class="py-2 text-right font-black uppercase opacity-40">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="row in element.data.rows" :key="row.id" class="border-b border-black/5 last:border-0 hover:bg-black/5 transition-colors">
                                                            <td class="py-3 font-bold">{{ row.label }}</td>
                                                            <td class="py-3 text-right font-black text-indigo-600">{{ row.val }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </draggable>
                        </div>

                        <footer class="absolute bottom-[60px] left-[80px] right-[80px] flex justify-between items-end border-t border-black/5 pt-6">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-30 italic">{{ studio.branding.footerText }}</span>
                                <div class="flex gap-2 mt-2">
                                    <div class="h-1.5 w-12 bg-indigo-500 rounded-full"></div>
                                    <div class="h-1.5 w-4 bg-slate-200 rounded-full"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[12px] font-black opacity-20 tracking-tighter">PAGE {{ currentPageIdx + 1 }} / {{ pages.length }}</span>
                            </div>
                        </footer>
                    </div>
                </section>

                <aside v-if="!isPreview" class="w-80 border-l border-white/5 bg-[#08080a] flex flex-col shrink-0 overflow-y-auto custom-scrollbar">
                    <div class="p-6 space-y-10">
                        <section>
                            <h3 class="text-[9px] font-black text-indigo-400 uppercase tracking-widest mb-6">Éditeur de Titre Principal</h3>
                            <div class="space-y-4">
                                <div class="field">
                                    <label class="text-[10px] font-bold block mb-2">Libellé</label>
                                    <Textarea v-model="studio.docTitle.text" rows="2" class="w-full text-xs !bg-white/5 border-white/10" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="field">
                                        <label class="text-[10px] font-bold block mb-2">Taille ({{ studio.docTitle.style.size }}px)</label>
                                        <Slider v-model="studio.docTitle.style.size" :min="12" :max="150" />
                                    </div>
                                    <div class="field">
                                        <label class="text-[10px] font-bold block mb-2">Espacement</label>
                                        <Slider v-model="studio.docTitle.style.letterSpacing" :min="-10" :max="10" />
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-white/5 rounded-xl">
                                    <span class="text-[10px] font-bold uppercase">Couleur</span>
                                    <ColorPicker v-model="studio.docTitle.style.color" />
                                </div>
                            </div>
                        </section>

                        <Divider class="opacity-5" />

                        <section>
                            <h3 class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-6">Outils de Précision</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium">Magnétisme (Snap)</span>
                                    <InputSwitch v-model="studio.config.snapToGrid" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-medium">Grille Visible</span>
                                    <InputSwitch v-model="studio.config.showGrid" />
                                </div>
                                <div class="field">
                                    <label class="text-[10px] font-bold block mb-2">Taille de Grille</label>
                                    <Slider v-model="studio.config.gridSize" :min="10" :max="100" />
                                </div>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>
        </div>

        <Dialog v-model:visible="configModal" modal :header="'Module : ' + currentWidget.name" :style="{ width: '65rem' }" class="p-fluid studio-dialog-dark">
            <div class="grid grid-cols-12 gap-10 py-6">
                <div class="col-span-3 border-r border-white/5 pr-8 space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-6 tracking-widest">Type de Composant</label>
                    <div v-for="lib in componentLibrary" :key="lib.type"
                         @click="currentWidget.type = lib.type"
                         :class="['flex items-center gap-4 p-4 rounded-2xl cursor-pointer border-2 transition-all',
                                  currentWidget.type === lib.type ? 'border-indigo-600 bg-indigo-500/10 text-indigo-400 shadow-xl shadow-indigo-500/10' : 'border-white/5 text-slate-500 hover:border-white/10']">
                        <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', currentWidget.type === lib.type ? 'bg-indigo-600 text-white' : 'bg-white/5']">
                            <i :class="lib.icon"></i>
                        </div>
                        <div>
                            <span class="block text-[11px] font-black uppercase tracking-tighter">{{ lib.label }}</span>
                            <span class="text-[9px] opacity-40 uppercase font-bold">{{ lib.category }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-9">
                    <TabView>
                        <TabPanel header="DONNÉES & LOGIQUE">
                            <div class="grid grid-cols-2 gap-8 pt-6">
                                <div class="field col-span-2">
                                    <label class="text-[11px] font-black uppercase text-slate-400 mb-2 block">Libellé du Module</label>
                                    <InputText v-model="currentWidget.name" class="p-inputtext-lg" />
                                </div>
                                <div v-if="currentWidget.type === 'chart'" class="field">
                                    <label class="text-[11px] font-black uppercase text-slate-400 mb-2 block">Format Graphique</label>
                                    <SelectButton v-model="currentWidget.chartType" :options="['bar', 'line', 'pie', 'radar']" />
                                </div>
                                <div v-if="currentWidget.type === 'chart'" class="field">
                                    <label class="text-[11px] font-black uppercase text-slate-400 mb-2 block">Données (Série CSV)</label>
                                    <InputText :modelValue="currentWidget.data.values.join(',')" @update:modelValue="v => currentWidget.data.values = v.split(',').map(Number)" />
                                </div>
                                <div v-if="currentWidget.type === 'text'" class="field col-span-2">
                                    <label class="text-[11px] font-black uppercase text-slate-400 mb-2 block">Contenu Narratif</label>
                                    <Textarea v-model="currentWidget.content" rows="8" />
                                </div>
                            </div>
                        </TabPanel>

                        <TabPanel header="APPARENCE & DESIGN">
                            <div class="grid grid-cols-2 gap-10 pt-6">
                                <div class="space-y-6">
                                    <div class="field">
                                        <label class="text-[11px] font-black uppercase text-slate-400 mb-4 block">Emprise Horizontale ({{ currentWidget.colSpan }}/12)</label>
                                        <Slider v-model="currentWidget.colSpan" :min="1" :max="12" />
                                    </div>
                                    <div class="field">
                                        <label class="text-[11px] font-black uppercase text-slate-400 mb-4 block">Emprise Verticale ({{ currentWidget.rowSpan }})</label>
                                        <Slider v-model="currentWidget.rowSpan" :min="1" :max="10" />
                                    </div>
                                    <div class="field">
                                        <label class="text-[11px] font-black uppercase text-slate-400 mb-4 block">Z-Order (Position Calque)</label>
                                        <InputNumber v-model="currentWidget.settings.zIndex" showButtons :min="1" :max="100" />
                                    </div>
                                </div>
                                <div class="bg-white/5 p-6 rounded-3xl space-y-6 border border-white/5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-black uppercase text-indigo-400">Effet de Verre (Glass)</span>
                                        <InputSwitch v-model="currentWidget.settings.glass" />
                                    </div>
                                    <div class="field" v-if="currentWidget.settings.glass">
                                        <label class="text-[11px] font-black uppercase text-slate-400 mb-2 block">Force du flou : {{ currentWidget.settings.blur }}px</label>
                                        <Slider v-model="currentWidget.settings.blur" :min="0" :max="40" />
                                    </div>
                                    <div class="field">
                                        <label class="text-[11px] font-black uppercase text-slate-400 mb-2 block">Fond du Module</label>
                                        <div class="flex items-center gap-4">
                                            <ColorPicker v-model="currentWidget.settings.bg" />
                                            <InputText v-model="currentWidget.settings.bg" class="p-inputtext-sm" />
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="text-[11px] font-black uppercase text-slate-400 mb-2 block">Opacité Globale ({{ currentWidget.settings.opacity }}%)</label>
                                        <Slider v-model="currentWidget.settings.opacity" :min="0" :max="100" />
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center border-t border-white/5 pt-6 mt-4">
                    <span class="text-[9px] font-black text-slate-600 uppercase tracking-widest">ID Unique : {{ currentWidget.id }}</span>
                    <div class="flex gap-2">
                        <Button label="Annuler" class="p-button-text p-button-secondary" @click="configModal = false" />
                        <Button label="Enregistrer le Module" icon="pi pi-check" class="p-button-indigo h-12 !px-10 shadow-2xl" @click="saveWidget" />
                    </div>
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
/* ENGINE : CSS GRID DENSE */
.grid-cols-12 { display: grid; grid-template-columns: repeat(12, 1fr); grid-auto-flow: dense; }

/* SCROLLBARS DESIGN */
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.1); border-radius: 10px; }
.custom-scrollbar-thin::-webkit-scrollbar { width: 2px; }
.custom-scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); }

/* ANIMATIONS */
.studio-container { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
@keyframes widgetIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.group\/widget { animation: widgetIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

/* PRIMEVUE CUSTOM LOOK */
.p-button-indigo { background: #6366f1; border: none; font-weight: 900; letter-spacing: 0.1em; transition: all 0.3s; }
.p-button-indigo:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 15px 30px -10px rgba(99,102,241,0.5); }

:deep(.p-tabview-nav) { background: transparent !important; border: none !important; }
:deep(.p-tabview-nav-link) { font-size: 10px !important; font-weight: 900 !important; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b !important; }
:deep(.p-tabview-selected .p-tabview-nav-link) { color: #6366f1 !important; border-color: #6366f1 !important; }
:deep(.p-dialog-header) { background: #08080a !important; color: white !important; border-bottom: 1px solid rgba(255,255,255,0.05) !important; padding: 2rem !important; }
:deep(.p-dialog-content) { background: #08080a !important; color: white !important; }

/* CAPTURE ZONE */
#studio-capture-area { transform-origin: top center; overflow: hidden; position: relative; }
@media print {
    .studio-container { background: white !important; }
    #studio-capture-area { box-shadow: none !important; border: none !important; width: 100% !important; height: 100% !important; background-color: white !important; }
}
</style>
