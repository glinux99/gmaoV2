<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import axios from 'axios';

// --- PDF & EXPORT ---
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

// --- COMPOSANTS PRIMEVUE ---
import Carousel from 'primevue/carousel';
import Button from 'primevue/button';
import ProgressSpinner from 'primevue/progressspinner';
import ConfirmDialog from 'primevue/confirmdialog';

// --- MOTEUR GRAPHIQUE ---
import {
    Chart as ChartJS, Title, Tooltip, Legend, BarElement,
    CategoryScale, LinearScale, PointElement, LineElement, ArcElement, RadialLinearScale
} from 'chart.js';
import { Bar, Line, Pie, Doughnut, Radar } from 'vue-chartjs';

ChartJS.register(
    Title, Tooltip, Legend, BarElement,
    CategoryScale, LinearScale, PointElement, LineElement, ArcElement, RadialLinearScale
);

// --- PROPS & ÉTATS ---
const props = defineProps({
    reportTemplates: { type: Array, default: () => [] }
});

const toast = useToast();
const confirm = useConfirm();
const isLoading = ref(false);
const selectedTemplate = ref(null);
const pages = ref([]);
const currentPageIdx = ref(0);

// --- LOGIQUE DE SÉLECTION ---
const isTemplateActive = (id) => selectedTemplate.value?.id === id;

const selectTemplate = async (template) => {
    if (isTemplateActive(template.id)) return;

    isLoading.value = true;
    selectedTemplate.value = template;

    try {
        await new Promise(r => setTimeout(r, 400)); // Effet de transition
        pages.value = typeof template.content === 'string' ? JSON.parse(template.content) : template.content;
        currentPageIdx.value = 0;
        await syncAllWidgets();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Contenu du template corrompu.' });
        pages.value = [];
    } finally {
        isLoading.value = false;
    }
};

// --- SYNCHRONISATION DES DONNÉES ---
const syncAllWidgets = async () => {
    if (!pages.value.length) return;
    const promises = [];

    pages.value.forEach(page => {
        page.widgets.forEach(widget => {
            if (['chart', 'kpi'].includes(widget.type)) {
                widget.isSyncing = true;
                const payload = {
                    type: widget.type,
                    config: widget.type === 'chart' // Correction de la structure de la charge utile pour les KPI
                        ? { sources: widget.dataSources, timeScale: widget.config.timeScale || 'days' }
                        : { ...widget.dataSource, timeScale: widget.config?.timeScale || 'days' }
                };

                const p = axios.post(route('quantum.query'), payload)
                    .then(res => {
                        if (widget.type === 'chart') widget.data = res.data;
                        if (widget.type === 'kpi') widget.config.value = res.data.value;
                    })
                    .finally(() => widget.isSyncing = false);
                promises.push(p);
            }
        });
    });
    await Promise.all(promises);
};

// --- LOGIQUE D'EXPORTATION ---
const exportToPDF = async () => {
    isLoading.value = true;
    toast.add({ severity: 'info', summary: 'Export', detail: 'Génération du document multi-pages...' });

    try {
        const doc = new jsPDF('p', 'mm', 'a4');
        const canvasElement = document.getElementById('report-canvas');

        for (let i = 0; i < pages.value.length; i++) {
            currentPageIdx.value = i;
            await nextTick();
            await new Promise(r => setTimeout(r, 600)); // Temps de rendu

            const canvas = await html2canvas(canvasElement, { scale: 2, useCORS: true });
            const imgData = canvas.toDataURL('image/png');
            if (i > 0) doc.addPage();
            doc.addImage(imgData, 'PNG', 0, 0, 210, 297);
        }

        doc.save(`Rapport_${selectedTemplate.value.name}.pdf`);
        toast.add({ severity: 'success', summary: 'Succès', detail: 'PDF téléchargé.' });
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Erreur Export', detail: 'Échec de la génération.' });
    } finally {
        isLoading.value = false;
    }
};

const sendByEmail = () => {
    confirm.require({
        message: 'Souhaitez-vous recevoir ce rapport par email immédiatement ?',
        header: 'Envoi par Email',
        icon: 'pi pi-envelope',
        accept: async () => {
            try {
                await axios.post(route('quantum.share'), { template_id: selectedTemplate.value.id, pages: pages.value });
                toast.add({ severity: 'success', summary: 'Envoyé', detail: 'Vérifiez votre boîte de réception.' });
            } catch (e) {
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d\'envoyer l\'email.' });
            }
        }
    });
};

// --- STYLES & RENDU ---
const canvasStyles = computed(() => {
    const page = pages.value[currentPageIdx.value];
    if (!page) return {};
    const format = page.format || { w: 793, h: 1122 };
    const isLandscape = page.orientation === 'landscape';
    return {
        width: (isLandscape ? format.h : format.w) + 'px',
        height: (isLandscape ? format.w : format.h) + 'px',
        backgroundColor: page.background || '#ffffff',
        transform: `scale(0.75)`,
        transformOrigin: 'top center'
    };
});

const getWidgetStyle = (w) => ({
    position: 'absolute',
    left: w.x + 'px', top: w.y + 'px',
    width: w.w + 'px', height: w.h + 'px',
    zIndex: w.style?.zIndex || 10,
    backgroundColor: w.style?.backgroundColor || 'transparent',
    borderRadius: (w.style?.borderRadius || 0) + 'px',
    border: `${w.style?.borderWidth || 0}px ${w.style?.borderStyle || 'solid'} ${w.style?.borderColor || '#000'}`,
    transform: `rotate(${w.style?.rotation || 0}deg)`,
    padding: (w.style?.padding || 0) + 'px',
    overflow: 'hidden'
});

const getChartComponent = (type) => {
    const map = { bar: Bar, line: Line, pie: Pie, doughnut: Doughnut, radar: Radar };
    return map[type] || Bar;
};

const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } } };
</script>

<template>
    <AppLayout>
        <Head title="Quantum Analytics" />
        <ConfirmDialog />

        <div class="bg-[#f8fafc] min-h-screen p-6">
            <header class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-black tracking-tighter text-slate-900 uppercase">
                        Repporting <span class="text-primary-600">Data</span>
                    </h1>
                    <p class="text-slate-500 text-xs font-medium">Reporting Haute Fidélité v11.5</p>
                </div>

                <div v-if="selectedTemplate" class="flex gap-2">
                    <Button icon="pi pi-refresh" @click="syncAllWidgets" :loading="isLoading" class="p-button-sm p-button-text p-button-secondary" />
                    <div class="flex bg-white rounded-xl shadow-sm border border-slate-200 p-1">
                        <Button icon="pi pi-file-pdf" @click="exportToPDF" v-tooltip.bottom="'Télécharger PDF'" class="p-button-text p-button-sm p-button-secondary" />
                        <Button icon="pi pi-envelope" @click="sendByEmail" v-tooltip.bottom="'Envoyer Email'" class="p-button-text p-button-sm p-button-secondary" />
                    </div>
                </div>
            </header>

            <section class="mb-10">
                <Carousel :value="reportTemplates" :numVisible="4" :numScroll="1" :circular="true" class="custom-carousel">
                    <template #item="slotProps">
                        <div class="p-2">
                            <div @click="selectTemplate(slotProps.data)"
                                :class="['card-v11', isTemplateActive(slotProps.data.id) ? 'active' : '']">
                                <div class="icon-box"><i class="pi pi-file text-xl"></i></div>
                                <div class="content">
                                    <span class="title">{{ slotProps.data.name }}</span>
                                    <span class="subtitle">Format A4</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Carousel>
            </section>

            <div class="viewport-container">
                <transition name="quantum-fade" mode="out-in">
                    <div v-if="selectedTemplate && pages.length > 0" :key="selectedTemplate.id" class="flex flex-col items-center">
                        <div class="flex items-center gap-6 mb-8 bg-white/80 backdrop-blur-md border border-white px-4 py-2 rounded-2xl shadow-sm">
                            <Button icon="pi pi-chevron-left" @click="currentPageIdx--" :disabled="currentPageIdx === 0" class="p-button-rounded p-button-text p-button-sm" />
                            <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Page {{ currentPageIdx + 1 }} / {{ pages.length }}</span>
                            <Button icon="pi pi-chevron-right" @click="currentPageIdx++" :disabled="currentPageIdx === pages.length - 1" class="p-button-rounded p-button-text p-button-sm" />
                        </div>

                        <div id="report-canvas" :style="canvasStyles" class="relative bg-white shadow-2xl">
                            <div v-for="w in pages[currentPageIdx].widgets" :key="w.id" :style="getWidgetStyle(w)">
                                <div v-if="w.type === 'text'" :style="{ fontFamily: w.config.font, fontSize: w.config.size+'px', color: w.config.color, textAlign: w.config.align, fontWeight: w.config.weight }">
                                    {{ w.content }}
                                </div>
                                <!-- AJOUT : Logique d'affichage pour les tableaux -->
                                <div v-if="w.type === 'table'" class="w-full h-full overflow-auto custom-scrollbar">
                                    <table class="w-full text-left border-collapse">
                                        <thead :style="{ background: w.config.headerBg || '#1e293b', color: w.config.headerColor || '#ffffff' }" class="sticky top-0">
                                            <tr>
                                                <th v-for="col in w.data.columns" :key="col" class="p-2 text-xs font-bold uppercase">{{ col }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-slate-700 text-sm">
                                            <tr v-for="(row, ri) in w.data.rows" :key="ri" :class="w.config.striped && ri % 2 === 0 ? 'bg-slate-50' : 'bg-white'">
                                                <td v-for="col in w.data.columns" :key="col" class="p-2 border-b border-slate-100">
                                                    {{ row[col] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-if="w.type === 'chart'" class="w-full h-full p-2">
                                    <component :is="getChartComponent(w.chartType)" :data="w.data" :options="chartOptions" />
                                </div>
                                <div v-if="w.type === 'kpi'" class="w-full h-full flex flex-col justify-center text-center">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ w.config.label }}</span>
                                    <div class="text-3xl font-black" :style="{ color: w.config.color || '#4F46E5' }">
                                        {{ w.config.prefix || '' }}{{ w.config.value }}
                                    </div>
                                </div>
                                <div v-if="w.isSyncing" class="absolute inset-0 bg-white/60 backdrop-blur-[2px] flex items-center justify-center">
                                    <ProgressSpinner style="width:20px;height:20px" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty-state">
                        <i class="pi pi-clone text-4xl text-slate-300 mb-4"></i>
                        <h3 class="text-slate-400 font-bold uppercase tracking-widest text-sm">Sélectionnez un modèle</h3>
                    </div>
                </transition>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.card-v11 { @apply bg-white border border-slate-200 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all duration-300 relative; }
.card-v11:hover { @apply border-primary-300 -translate-y-1 shadow-lg; }
.card-v11.active { @apply border-primary-600 bg-primary-50/50 ring-2 ring-primary-500/10; }
.icon-box { @apply w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400; }
.active .icon-box { @apply bg-primary-600 text-white shadow-lg; }
.content .title { @apply block text-sm font-bold text-slate-800 truncate w-32; }
.content .subtitle { @apply block text-[10px] text-slate-400 font-medium uppercase mt-0.5; }
.viewport-container { @apply bg-slate-200/50 rounded-[2rem] p-10 min-h-[800px] border border-slate-200/60 flex justify-center; }
.quantum-fade-enter-active, .quantum-fade-leave-active { transition: all 0.4s ease; }
.quantum-fade-enter-from, .quantum-fade-leave-to { opacity: 0; transform: translateY(10px); }
.empty-state { @apply flex flex-col items-center justify-center w-full h-[500px]; }
</style>
