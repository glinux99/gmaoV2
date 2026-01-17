<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, computed, nextTick, onMounted } from 'vue';
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
import Tooltip from 'primevue/tooltip';

// --- MOTEUR GRAPHIQUE ---
import {
    Chart as ChartJS, Title, Tooltip as ChartTooltip, Legend, BarElement,
    CategoryScale, LinearScale, PointElement, LineElement, ArcElement, RadialLinearScale
} from 'chart.js';
import { Bar, Line, Pie, Doughnut, Radar } from 'vue-chartjs';

ChartJS.register(
    Title, ChartTooltip, Legend, BarElement,
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
        await new Promise(r => setTimeout(r, 400));
        // Parse le contenu si c'est une string JSON
        const rawContent = typeof template.content === 'string' ? JSON.parse(template.content) : template.content;
        pages.value = rawContent;
        currentPageIdx.value = 0;

        // Lancer la synchronisation des données réelles
        await syncAllWidgets();
    } catch (e) {
        console.error(e);
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Modèle corrompu.' });
        pages.value = [];
    } finally {
        isLoading.value = false;
    }
};

// --- SYNCHRONISATION DES DONNÉES VIA LARAVEL ---
const syncAllWidgets = async () => {
    if (!pages.value.length) return;
    const promises = [];

    pages.value.forEach(page => {
        page.widgets.forEach(widget => {
            if (['chart', 'kpi', 'table'].includes(widget.type)) {
                widget.isSyncing = true;

                const payload = {
                    type: widget.type,
                    config: {
                        sources: widget.dataSources || widget.dataSource || null,
                        timeScale: widget.config?.timeScale || 'days',
                        chartType: widget.chartType || null
                    }
                };

                const p = axios.post(route('quantum.query'), payload)
                    .then(res => {
                        if (widget.type === 'chart' || widget.type === 'table') {
                            widget.data = res.data;
                        }
                        if (widget.type === 'kpi') {
                            widget.config.value = res.data.value;
                            if (res.data.trend) widget.config.trend = res.data.trend;
                        }
                    })
                    .catch(err => console.error("Sync Error:", err))
                    .finally(() => widget.isSyncing = false);

                promises.push(p);
            }
        });
    });
    await Promise.all(promises);
};

// --- LOGIQUE D'EXPORTATION HD ---
const exportToPDF = async () => {
    isLoading.value = true;
    toast.add({ severity: 'info', summary: 'Export', detail: 'Préparation du rendu HD...' });

    // Sauvegarde du style original pour restauration après capture
    const canvasElement = document.getElementById('report-canvas');
    const originalTransform = canvasElement.style.transform;

    try {
        const doc = new jsPDF('p', 'mm', 'a4');

        for (let i = 0; i < pages.value.length; i++) {
            currentPageIdx.value = i;
            await nextTick();
            await new Promise(r => setTimeout(r, 800)); // Laisse le temps aux graphiques de s'animer

            // On force l'échelle à 1 pour une capture précise
            canvasElement.style.transform = 'scale(1)';

            const canvas = await html2canvas(canvasElement, {
                scale: 3, // Multiplie la résolution par 3
                useCORS: true,
                backgroundColor: null
            });

            const imgData = canvas.toDataURL('image/png');
            if (i > 0) doc.addPage();
            doc.addImage(imgData, 'PNG', 0, 0, 210, 297);

            // On remet le scale visuel pour la page suivante
            canvasElement.style.transform = originalTransform;
        }

        doc.save(`Rapport_${selectedTemplate.value.name.replace(/\s+/g, '_')}.pdf`);
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Le PDF est prêt.' });
    } catch (err) {
        toast.add({ severity: 'error', summary: 'Erreur Export', detail: 'Échec de la capture.' });
    } finally {
        canvasElement.style.transform = originalTransform;
        isLoading.value = false;
    }
};

const sendByEmail = () => {
    confirm.require({
        message: 'Envoyer ce rapport actualisé par email à votre adresse ?',
        header: 'Diffusion Quantum',
        icon: 'pi pi-envelope',
        accept: async () => {
            try {
                await axios.post(route('quantum.share'), {
                    template_id: selectedTemplate.value.id,
                    pages: pages.value
                });
                toast.add({ severity: 'success', summary: 'Envoyé', detail: 'Email en cours d\'envoi.' });
            } catch (e) {
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'envoi.' });
            }
        }
    });
};

// --- STYLES DYNAMIQUE ---
const canvasStyles = computed(() => {
    const page = pages.value[currentPageIdx.value];
    if (!page) return {};
    const format = page.format || { w: 794, h: 1123 }; // A4 96DPI
    const isLandscape = page.orientation === 'landscape';
    return {
        width: (isLandscape ? format.h : format.w) + 'px',
        height: (isLandscape ? format.w : format.h) + 'px',
        backgroundColor: page.background || '#ffffff',
        transform: `scale(0.75)`,
        transformOrigin: 'top center',
        transition: 'all 0.3s ease'
    };
});

const getWidgetStyle = (w) => ({
    position: 'absolute',
    left: w.x + 'px',
    top: w.y + 'px',
    width: w.w + 'px',
    height: w.h + 'px',
    zIndex: w.style?.zIndex || 10,
    backgroundColor: w.style?.backgroundColor || 'transparent',
    borderRadius: (w.style?.borderRadius || 0) + 'px',
    border: w.style?.borderWidth > 0 ? `${w.style.borderWidth}px ${w.style.borderStyle || 'solid'} ${w.style.borderColor || '#000'}` : 'none',
    transform: `rotate(${w.style?.rotation || 0}deg)`,
    padding: (w.style?.padding || 0) + 'px',
    overflow: 'hidden',
    display: 'flex',
    flexDirection: 'column'
});

const getChartComponent = (type) => {
    const map = { bar: Bar, line: Line, pie: Pie, doughnut: Doughnut, radar: Radar };
    return map[type] || Bar;
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 0 }, // Désactivé pour l'export stable
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10, weight: 'bold' } } }
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Quantum Analytics" />
        <ConfirmDialog />

        <div class="bg-[#f1f5f9] min-h-screen p-8">
            <header class="flex justify-between items-center mb-10 max-w-7xl mx-auto">
                <div>
                    <h1 class="text-3xl font-black tracking-tighter text-slate-900 uppercase italic">
                        Quantum <span class="text-primary-600">Analytics</span>
                    </h1>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-1">
                        <i class="pi pi-verified text-primary-500 mr-1"></i> Data Studio Reporting v11.5
                    </p>
                </div>

                <div v-if="selectedTemplate" class="flex gap-3 animate-in fade-in zoom-in duration-300">
                    <Button icon="pi pi-refresh" @click="syncAllWidgets" :loading="isLoading"
                            label="Actualiser" class="p-button-sm p-button-outlined p-button-secondary bg-white font-bold" />

                    <div class="flex bg-white rounded-xl shadow-sm border border-slate-200 p-1">
                        <Button icon="pi pi-file-pdf" @click="exportToPDF" v-tooltip.bottom="'Exporter en PDF HD'"
                                class="p-button-text p-button-sm p-button-secondary" />
                        <Button icon="pi pi-envelope" @click="sendByEmail" v-tooltip.bottom="'Diffuser par Email'"
                                class="p-button-text p-button-sm p-button-secondary" />
                    </div>
                </div>
            </header>

            <section class="mb-12 max-w-7xl mx-auto">
                <Carousel :value="reportTemplates" :numVisible="4" :numScroll="1" :circular="false" class="custom-carousel">
                    <template #item="slotProps">
                        <div class="p-2">
                            <div @click="selectTemplate(slotProps.data)"
                                :class="['card-v11 cursor-pointer transition-all duration-300', isTemplateActive(slotProps.data.id) ? 'active' : '']">
                                <div class="icon-box"><i class="pi pi-chart-line text-xl"></i></div>
                                <div class="content">
                                    <span class="title">{{ slotProps.data.name }}</span>
                                    <span class="subtitle">{{ slotProps.data.updated_at || 'Template Standard' }}</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </Carousel>
            </section>

            <div class="viewport-container flex flex-col items-center">
                <transition name="quantum-fade" mode="out-in">
                    <div v-if="selectedTemplate && pages.length > 0" :key="selectedTemplate.id" class="flex flex-col items-center">

                        <div class="flex items-center gap-8 mb-8 bg-white/90 backdrop-blur-xl border border-white px-6 py-3 rounded-2xl shadow-xl shadow-slate-200/50">
                            <Button icon="pi pi-chevron-left" @click="currentPageIdx--" :disabled="currentPageIdx === 0"
                                    class="p-button-rounded p-button-text p-button-sm" />
                            <div class="flex flex-col items-center">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Document</span>
                                <span class="text-sm font-bold text-slate-800">Page {{ currentPageIdx + 1 }} sur {{ pages.length }}</span>
                            </div>
                            <Button icon="pi pi-chevron-right" @click="currentPageIdx++" :disabled="currentPageIdx === pages.length - 1"
                                    class="p-button-rounded p-button-text p-button-sm" />
                        </div>

                        <div id="report-canvas" :style="canvasStyles" class="relative bg-white shadow-[0_50px_100px_rgba(0,0,0,0.1)] overflow-hidden">
                            <div v-for="w in pages[currentPageIdx].widgets" :key="w.id" :style="getWidgetStyle(w)">

                                <div v-if="w.type === 'text'" :style="{
                                    fontFamily: w.config.font,
                                    fontSize: w.config.size+'px',
                                    color: w.config.color,
                                    textAlign: w.config.align,
                                    fontWeight: w.config.weight,
                                    textTransform: w.config.uppercase ? 'uppercase' : 'none',
                                    fontStyle: w.config.italic ? 'italic' : 'normal',
                                    textDecoration: w.config.underline ? 'underline' : 'none'
                                }">
                                    {{ w.content }}
                                </div>

                                <div v-if="w.type === 'image'" class="w-full h-full">
                                    <img :src="w.imageUrl || w.content" alt="Widget" class="w-full h-full object-cover" />
                                </div>

                                <div v-if="w.type === 'table'" class="w-full h-full flex-grow overflow-hidden">
                                    <table class="w-full text-left border-collapse">
                                        <thead :style="{ background: w.config.headerBg || '#1e293b', color: w.config.headerColor || '#ffffff' }">
                                            <tr>
                                                <th v-for="col in w.data.columns" :key="col" class="p-2 text-[10px] font-black uppercase">
                                                    {{ col }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-slate-700 text-xs">
                                            <tr v-for="(row, ri) in w.data.rows" :key="ri"
                                                :class="w.config.striped && ri % 2 === 0 ? 'bg-slate-50' : 'bg-white'">
                                                <td v-for="col in w.data.columns" :key="col" class="p-2 border-b border-slate-100">
                                                    {{ row[col] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div v-if="w.type === 'chart'" class="w-full h-full p-2 flex-grow">
                                    <component :is="getChartComponent(w.chartType)" :data="w.data" :options="chartOptions" />
                                </div>

                                <div v-if="w.type === 'kpi'" class="w-full h-full flex flex-col justify-center px-4">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest text-center mb-1">
                                        {{ w.config.label }}
                                    </span>
                                    <div class="flex items-baseline gap-1 justify-center">
                                        <span class="text-3xl font-black tracking-tighter" :style="{ color: w.config.color }">
                                            {{ w.config.prefix }}{{ w.config.value }}
                                        </span>
                                        <span v-if="w.config.trend" class="text-[10px] font-bold text-emerald-500">
                                            {{ w.config.trend }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="w.type === 'shape'" class="w-full h-full pointer-events-none">
                                    <svg width="100%" height="100%" :viewBox="`0 0 ${w.w} ${w.h}`" preserveAspectRatio="none">
                                        <line v-if="w.config.shapeType === 'line'" x1="0" :y1="w.config.strokeWidth / 2" :x2="w.w" :y2="w.config.strokeWidth / 2" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" />
                                        <rect v-if="w.config.shapeType === 'rectangle'" x="0" y="0" width="100%" height="100%" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" />
                                        <circle v-if="w.config.shapeType === 'circle'" :cx="w.w/2" :cy="w.h/2" :r="Math.min(w.w, w.h)/2 - w.config.strokeWidth/2" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" />
                                    </svg>
                                </div>

                                <div v-if="w.isSyncing" class="absolute inset-0 bg-white/40 backdrop-blur-[1px] flex items-center justify-center">
                                    <ProgressSpinner style="width:24px;height:24px" strokeWidth="6" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center justify-center min-h-[400px] text-center bg-white rounded-3xl border-2 border-dashed border-slate-200 w-full max-w-2xl mx-auto shadow-inner">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                            <i class="pi pi-clone text-4xl text-slate-200"></i>
                        </div>
                        <h3 class="text-slate-400 font-black uppercase tracking-[0.3em] text-sm">Prêt pour l'analyse</h3>
                        <p class="text-slate-400 text-xs mt-2">Sélectionnez un modèle Quantum Omni pour commencer</p>
                    </div>
                </transition>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.card-v11 {
    @apply bg-white border border-slate-200 p-4 rounded-2xl flex items-center gap-4 shadow-sm hover:shadow-xl hover:-translate-y-1;
}
.card-v11.active {
    @apply border-primary-500 ring-2 ring-primary-500/20 bg-primary-50/30;
}
.card-v11 .icon-box {
    @apply w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400;
}
.card-v11.active .icon-box {
    @apply bg-primary-600 text-white shadow-lg shadow-primary-500/30;
}
.card-v11 .content .title {
    @apply block text-sm font-black text-slate-800 uppercase tracking-tight;
}
.card-v11 .content .subtitle {
    @apply block text-[10px] font-bold text-slate-400 uppercase;
}

.quantum-fade-enter-active, .quantum-fade-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.quantum-fade-enter-from, .quantum-fade-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
