<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue';
import * as htmlToImage from 'html-to-image';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/sakai/layout/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Slider from 'primevue/slider';
import Button from 'primevue/button';
import Carousel from 'primevue/carousel';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';

const props = defineProps({
    networks: { type: Array, default: () => [] },
    initialNetwork: Object,
    library: { type: Object, default: () => ({}) },
    regions: Array,
    zones: Array,
});

const toast = useToast();
const confirm = useConfirm();
const { t } = useI18n();

// --- CONSTANTES TECHNIQUES ---
const NODE_WIDTH = 220;
const NODE_HEIGHT = 130;
const FLOW_SPEED = 2.0;
const ELECTRON_COUNT = 4;
const ELECTRON_SPACING = 0.8;

// --- ÉTAT RÉACTIF ---
const projectId = ref(null);
const networkName = ref(t('networks.newProject'));
const equipments = ref([]);
const connections = ref([]);
const labels = ref([]);
const zoomLevel = ref(0.85);
const showStats = ref(true);
const exportContainer = ref(null);

// --- LOGIQUE DE CHARGEMENT ET MAPPING ---
// Cette fonction garantit que les noms et icônes sont récupérés même si les données sont imbriquées
const loadNetwork = (network) => {
    console.log(network);
    if (!network) {
        projectId.value = null;
        networkName.value = t('networks.newEmptyProject');
        equipments.value = [];
        connections.value = [];
        return;
    }

    projectId.value = network.id;
    networkName.value = network.name;
    zoomLevel.value = parseFloat(network.zoom_level) || 0.85;

    // Mapping sécurisé : Priorité à l'équipement lié, puis au nœud, puis fallback
    equipments.value = (network.nodes || []).map(node => {
        const eq = node.equipment || {};
        const type = eq.equipment_type || {};

        return {
            id: node.id,
            tag: eq.tag || node.tag || `ID-${node.id}`,
            designation: eq.designation || node.designation || t('networks.defaultEquipment'),
            icon: type.icon || node.icon || 'pi-box',
            type: type.name || t('networks.defaultComponent'),
            x: parseFloat(node.x),
            y: parseFloat(node.y),
            w: parseFloat(node.w) || NODE_WIDTH,
            h: parseFloat(node.h) || NODE_HEIGHT,
            active: node.is_active !== undefined ? !!node.is_active : true,
            isRoot: !!node.is_root,
            region_id: node.region_id,
            zone_id: node.zone_id,
        };
    });

    connections.value = (network.connections || []).map(conn => ({
        id: conn.id || `conn-${Math.random()}`,
        fromId: conn.from_node_id,
        fromSide: conn.from_side,
        toId: conn.to_node_id,
        toSide: conn.to_side,
        color: conn.color || '#3b82f6'
    }));

    labels.value = network.labels || [];
    requestAnimationFrame(centerView);

    toast.add({ severity: 'info', summary: t('networks.systemUpdated'), detail: t('networks.networkLoaded', { name: network.name }), life: 2000 });
};

// --- CALCULS ÉLECTRIQUES (ALGORITHME DE PROPAGATION) ---
const energizedNodes = computed(() => {
    const liveSet = new Set();
    const traverse = (id) => {
        if (liveSet.has(id)) return;
        const node = equipments.value.find(e => e.id === id);
        if (!node || !node.active) return;
        liveSet.add(id);
        connections.value
            .filter(c => c.fromId === id)
            .forEach(c => traverse(c.toId));
    };
    // On part des sources (isRoot) qui sont actives
    equipments.value.filter(e => e.isRoot && e.active).forEach(s => traverse(s.id));
    return liveSet;
});

const getLocationName = (node) => {
    if (node.region_id) return props.regions.find(r => r.id === node.region_id)?.designation;
    if (node.zone_id) return props.zones.find(z => z.id === node.zone_id)?.name;
    return null;
};

const isWireLive = (wire) => energizedNodes.value.has(wire.fromId);

// --- STATISTIQUES PROFESSIONNELLES COMPACTES ---
const stats = computed(() => {
    const total = equipments.value.length;
    const activeCount = equipments.value.filter(e => e.active).length;
    const efficiency = total > 0 ? Math.round((activeCount / total) * 100) : 0;

    return {
        efficiency,
        active: activeCount,
        total,
        liveConnections: connections.value.filter(c => isWireLive(c)).length,
        sources: equipments.value.filter(e => e.isRoot).length,
        status: efficiency > 80 ? t('networks.status.optimal') : efficiency > 40 ? t('networks.status.unstable') : t('networks.status.critical')
    };
});

// --- ACTIONS CRUD ---
const editNetwork = (id) => router.get(route('networks.edit', id));
const copyNetwork = (id) => router.get(route('networks.edit', id), { data: { action: 'duplicate' } });

const confirmDelete = (id) => {
    confirm.require({
        message: t('networks.deleteConfirmMessage'),
        header: t('networks.deleteConfirmHeader'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger p-button-sm',
        accept: () => {
            router.delete(route('networks.destroy', id), { // Assurez-vous que la route 'networks.destroy' existe
                onSuccess: () => toast.add({ severity: 'success', summary: t('networks.deletedSummary'), detail: t('networks.deletedSuccess'), life: 3000 })
            });
        }
    });
};

// --- FONCTIONS GRAPHIQUES ---
const getPortPos = (node, side) => {
    if (!node) return { x: 0, y: 0 };
    if (side === 'N') return { x: node.x + node.w / 2, y: node.y };
    if (side === 'S') return { x: node.x + node.w / 2, y: node.y + node.h };
    if (side === 'E') return { x: node.x + node.w, y: node.y + node.h / 2 };
    if (side === 'W') return { x: node.x, y: node.y + node.h / 2 };
    return { x: node.x, y: node.y };
};

const getOrthogonalPath = (wire) => {
    const n1 = equipments.value.find(e => e.id === wire.fromId);
    const n2 = equipments.value.find(e => e.id === wire.toId);
    if (!n1 || !n2) return "";
    const p1 = getPortPos(n1, wire.fromSide);
    const p2 = getPortPos(n2, wire.toSide);
    const midY = p1.y + (p2.y - p1.y) / 2;
    return `M ${p1.x} ${p1.y} V ${midY} H ${p2.x} V ${p2.y}`;
};

const startMove = (e, item) => {
    const startX = e.clientX;
    const startY = e.clientY;
    const origX = item.x;
    const origY = item.y;
    const onMouseMove = (me) => {
        item.x = Math.round((origX + (me.clientX - startX) / zoomLevel.value) / 10) * 10;
        item.y = Math.round((origY + (me.clientY - startY) / zoomLevel.value) / 10) * 10;
    };
    const onMouseUp = () => {
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
    };
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
};

const centerView = () => {
    if (equipments.value.length === 0) return;

    const container = exportContainer.value;
    if (!container) return;

    const containerWidth = container.clientWidth;
    const containerHeight = container.clientHeight;

    // 1. Calculer la boîte englobante de tous les nœuds
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    equipments.value.forEach(node => {
        minX = Math.min(minX, node.x);
        minY = Math.min(minY, node.y);
        maxX = Math.max(maxX, node.x + node.w);
        maxY = Math.max(maxY, node.y + node.h);
    });

    const networkWidth = maxX - minX;
    const networkHeight = maxY - minY;

    if (networkWidth === 0 || networkHeight === 0) return;

    // 2. Calculer le niveau de zoom optimal avec une marge de 10%
    const padding = 0.1;
    const zoomX = containerWidth / (networkWidth * (1 + padding));
    const zoomY = containerHeight / (networkHeight * (1 + padding));
    const newZoom = Math.min(zoomX, zoomY, 1.5); // Limiter le zoom max à 150%

    zoomLevel.value = newZoom;

    // 3. Calculer la position de défilement pour centrer la vue
    const centerX = minX + networkWidth / 2;
    const centerY = minY + networkHeight / 2;
    container.scrollLeft = (centerX * newZoom) - (containerWidth / 2);
    container.scrollTop = (centerY * newZoom) - (containerHeight / 2);
};

const exportDiagram = (format) => {
    if (!exportContainer.value) return;

    const options = {
        quality: 0.98,
        pixelRatio: 2, // Augmente la résolution
        backgroundColor: 'var(--surface-ground)',
        skipAutoScale: true, // Correction pour l'export SVG
    };

    const exporter = format === 'png' ? htmlToImage.toPng : htmlToImage.toSvg;

    exporter(exportContainer.value, options)
        .then(function (dataUrl) {
            const link = document.createElement('a');
            link.download = `${networkName.value}_${new Date().toISOString().slice(0,10)}.${format}`;
            link.href = dataUrl;
            link.click();
            toast.add({ severity: 'success', summary: 'Exportation réussie', detail: `Le schéma a été téléchargé en ${format.toUpperCase()}.`, life: 3000 });
        })
        .catch(function (error) {
            console.error('Erreur lors de l\'exportation:', error);
            toast.add({ severity: 'error', summary: 'Erreur d\'exportation', detail: 'Impossible de générer l\'image.', life: 3000 });
        });
};


onMounted(() => {
    if (props.initialNetwork) loadNetwork(props.initialNetwork);
    else if (props.networks.length > 0) loadNetwork(props.networks[0]);
});
</script>

<template>
    <AppLayout>
        <Head :title="`${t('networks.title')} - ${networkName}`" />
        <div class="h-screen flex flex-col bg-surface-ground overflow-hidden select-none font-sans">
            <Toast />
            <ConfirmDialog />

            <header class="h-16 bg-surface-overlay/80 backdrop-blur-md border-b border-surface-border flex items-center justify-between px-8 z-[100] shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="p-2.5 bg-surface-900 rounded-xl shadow-lg shadow-surface-200 dark:shadow-surface-800">
                        <i class="pi pi-share-alt text-primary-contrast text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-black text-color leading-none uppercase tracking-tighter">{{ networkName }}</h1>
                        <span class="text-[9px] font-bold text-primary-color uppercase tracking-widest animate-pulse">{{ t('networks.monitoringLive') }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-4 bg-surface-100/50 px-4 py-2 rounded-full border border-surface-200 shadow-inner">
                        <i class="pi pi-search text-color-secondary text-xs"></i>
                        <Slider v-model="zoomLevel" :min="0.2" :max="2" :step="0.01" class="w-32" />
                        <span class="text-[10px] font-mono font-black text-color">{{ Math.round(zoomLevel*100) }}%</span>
                        <Button icon="pi pi-arrows-alt" @click="centerView" class="p-button-text p-button-sm !text-color-secondary" v-tooltip.bottom="'Centrer la vue'" />
                        <div class="h-5 w-px bg-surface-200 dark:bg-surface-700 mx-2"></div>
                        <Button icon="pi pi-image" @click="exportDiagram('png')" class="p-button-text p-button-sm !text-color-secondary" v-tooltip.bottom="'Exporter en PNG'" />
                        <Button icon="pi pi-file" @click="exportDiagram('svg')" class="p-button-text p-button-sm !text-color-secondary" v-tooltip.bottom="'Exporter en SVG'" />
                    </div>
                    <div class="flex items-center gap-2">
                        <Button icon="pi pi-save" :label="t('networks.save')" class="p-button-sm p-button-raised shadow-md" />
                    </div>
                </div>
            </header>

            <div class="bg-surface-card border-b border-surface-border px-8 py-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black text-color-secondary uppercase tracking-[0.2em]">{{ t('networks.infrastructureDirectory') }}</span>
                    <Button icon="pi pi-plus-circle" :label="t('networks.newNetwork')" @click="router.get(route('networks.create'))" class="p-button-text p-button-sm font-bold" />
                </div>
                <Carousel :value="props.networks" :numVisible="7" :numScroll="1" class="custom-carousel">
                    <template #item="slotProps">
                        <div class="p-1.5 relative group">

                            <div @click="loadNetwork(slotProps.data)"
                                :class="['h-20 p-3 rounded-2xl border-2 transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden shadow-sm',
                                projectId === slotProps.data.id ? 'border-primary-color bg-primary-50 dark:bg-primary-900/20' : 'border-surface-50 bg-surface-50/50 hover:bg-surface-0 hover:border-surface-200']">

                                <div class="z-10 flex justify-between items-start">
                                    <span class="text-[10px] font-black text-color truncate w-3/4">{{ slotProps.data.name }}</span>
                                    <div v-if="projectId === slotProps.data.id" class="w-2 h-2 bg-primary-color rounded-full animate-ping"></div>
                                </div>

                                <div class="z-10 flex items-center justify-between">
                                    <span class="text-[8px] font-black text-color-secondary uppercase tracking-widest">{{ slotProps.data.nodes_count || 0 }} {{ t('networks.elements') }}</span>
                                </div>

                                <div class="absolute inset-0 bg-surface-card/90 backdrop-blur-[2px] opacity-0 group-hover:opacity-100 transition-all duration-200 flex items-center justify-center gap-2 z-20">
                                    <Button icon="pi pi-copy" @click.stop="copyNetwork(slotProps.data.id)" class="p-button-rounded p-button-text p-button-secondary bg-surface-0 shadow-md !p-2" />
                                    <Button icon="pi pi-pencil" @click.stop="editNetwork(slotProps.data.id)" class="p-button-rounded p-button-text p-button-primary bg-surface-0 shadow-md !p-2" />
                                    <Button icon="pi pi-trash" @click.stop="confirmDelete(slotProps.data.id)" class="p-button-rounded p-button-text p-button-danger bg-surface-0 shadow-md !p-2" />
                                </div>
                            </div>
                        </div>
                    </template>
                </Carousel>
            </div>

            <div class="flex-grow flex relative overflow-hidden">
                <main ref="exportContainer" class="flex-grow relative bg-surface-ground overflow-auto shadow-inner scroll-smooth" @mousedown="selectedIds = []">
                    <div :style="{ transform: `scale(${zoomLevel})`, transformOrigin: '0 0' }" class="absolute inset-0 transition-transform duration-75">

                        <svg id="canvas-svg" width="5000" height="5000" class="absolute inset-0 pointer-events-none">
                            <defs>
                                <filter id="red-glow" x="-50%" y="-50%" width="200%" height="200%">
                                    <feGaussianBlur stdDeviation="4" result="blur" />
                                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                                </filter>
                            </defs>

                            <g v-for="w in connections" :key="w.id">
                                <path :d="getOrthogonalPath(w)"
                                      :stroke="isWireLive(w) ? 'var(--danger-color)' : w.color"
                                      stroke-width="3.5" fill="none" class="transition-colors duration-700"
                                      :style="{ filter: isWireLive(w) ? 'url(#red-glow)' : 'none' }" />
                                         <path :d="getOrthogonalPath(w)"
                      :stroke="isWireLive(w) ? w.color : '#1e293b'"
                      :stroke-width="selectedConnectionId === w.id ? 4 : 2"
                      :stroke-dasharray="w.dash"
                      fill="none"
                      class="transition-all duration-500"
                      :filter="isWireLive(w) ? 'url(#neon-glow)' : ''" />

                                <g v-if="isWireLive(w)">
                                    <circle v-for="n in ELECTRON_COUNT" :key="n" r="4" fill="#81f50c" class="electron-red shadow-lg">
                                        <animateMotion :path="getOrthogonalPath(w)" :dur="FLOW_SPEED + 's'"
                                            :begin="((n - 1) * ELECTRON_SPACING) + 's'" repeatCount="indefinite" />
                                    </circle>
                                </g>
                            </g>
                        </svg>

                        <div v-for="label in labels" :key="label.id"
                             :style="{ left: label.x + 'px', top: label.y + 'px', color: label.color, fontSize: label.font_size + 'px', fontWeight: label.is_bold ? 'bold' : 'normal', transform: `rotate(${label.rotation || 0}deg)` }"
                             class="absolute z-10 px-2 py-1 whitespace-nowrap pointer-events-none">
                             {{ label.text }}
                        </div>


                        <div v-for="node in equipments" :key="node.id"
                             :style="{ left: node.x + 'px', top: node.y + 'px', width: node.w + 'px', height: node.h + 'px' }"
                             @mousedown.stop="startMove($event, node)"
                             class="absolute z-20 node-card">

                            <div :class="['w-full h-full bg-surface-card border-2 rounded-[1rem]  shadow-xl transition-all duration-300 flex flex-col overflow-hidden',
                                          node.active ? 'border-surface-100 shadow-surface-200 dark:shadow-surface-800' : 'border-danger-100 grayscale opacity-60']">

                                <div class="h-9 bg-surface-50 dark:bg-surface-800 border-b border-surface-border flex items-center justify-between px-5">
                                    <span class="text-[9px] font-black text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full tracking-wider">{{ node.tag }}</span>
                                    <i @click.stop="node.active = !node.active"
                                       :class="['pi pi-power-off text-[10px] cursor-pointer transition-colors', node.active ? 'text-green-500' : 'text-surface-300']"></i>
                                </div>

                                <div class="p-4 flex flex-col items-center justify-center text-center flex-grow">
                                    <i :class="['pi', node.icon, 'text-xl mb-2 text-color-secondary']"></i>
                                    <span class="text-xs font-black text-color uppercase tracking-tighter leading-none px-2">{{ node.designation }}</span>

                                     <div v-if="energizedNodes.has(node.id)" class="mt-3 flex items-center gap-2">
                                        <div class="relative flex items-center justify-center w-6 h-4">
                                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-bounce-1"></span>
                                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-bounce-2"></span>
                                            <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-bounce-3"></span>
                                              </div>

                                    </div>
                                     <div v-if="getLocationName(node)" class="absolute bottom-2 left-2 flex items-center gap-1.5 bg-slate-900/50 border border-white/10 rounded-full px-2 py-0.5">
                                        <i class="pi pi-map-marker text-[8px] text-amber-400"></i>
                                        <span class="text-[8px] font-bold text-slate-300 uppercase tracking-wider">{{ getLocationName(node) }}</span>
                                    </div>
                                </div>

                                <div v-for="side in ['N','S','E','W']" :key="side" :class="['port', side]"></div>
                            </div>
                        </div>
                    </div>
                </main>
                  <div v-if="showStats" class="absolute top-4 right-4 z-50 bg-surface-900/90 backdrop-blur text-surface-0 p-4 rounded-2xl shadow-2xl border border-surface-0/10 w-56">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-[10px] font-bold text-primary-400 uppercase">{{ t('networks.stats.title') }}</span>
                        <i class="pi pi-chart-bar text-primary-400"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-xs text-surface-400">{{ t('networks.stats.equipments') }}</span>
                            <span class="text-xs font-bold">{{ stats.active }}/{{ stats.total }}</span>
                        </div>
                        <div class="h-1 w-full bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-primary-color" :style="{width: stats.efficiency + '%'}"></div>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-surface-0/5">
                            <span class="text-[10px] text-surface-400 uppercase">{{ t('networks.stats.activeFlux') }}</span>
                            <span class="text-xs font-bold text-green-400">{{ stats.liveConnections }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[10px] text-surface-400 uppercase">{{ t('networks.stats.sources') }}</span>
                            <span class="text-xs font-bold text-amber-400">{{ stats.sources }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Look & Feel des cartes */
.node-card { cursor: grab; }
.node-card:active { cursor: grabbing; }

/* Ports de connexion minimalistes */
.port {
    position: absolute;
    width: 12px;
    height: 12px;
    background: #334155; /* slate-700 */
    border: 2px solid var(--surface-ground);
    border-radius: 50%;
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.node-card:hover .port { opacity: 1; transform: scale(1.2); }
.node-card:hover .port:hover {
    background: #6366f1; /* indigo-500 */
    transform: scale(1.5);
}

.port.N { top: -6px; left: 50%; transform: translateX(-50%); }
.port.S { bottom: -6px; left: 50%; transform: translateX(-50%); }
.port.E { right: -6px; top: 50%; transform: translateY(-50%); }
.port.W { left: -6px; top: 50%; transform: translateY(-50%); }

/* Animation des électrons rouges */
.electron-red {
    filter: drop-shadow(0 0 6px var(--danger-color));
}

/* Personnalisation Carousel */
.custom-carousel :deep(.p-carousel-content) { padding: 0 0.5rem; }
.custom-carousel :deep(.p-carousel-prev),
.custom-carousel :deep(.p-carousel-next) {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    width: 2.2rem;
    height: 2.2rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

/* Scrollbar Sidebar */
aside::-webkit-scrollbar { width: 4px; }
aside::-webkit-scrollbar-thumb { background: var(--surface-300); border-radius: 10px; }

/* Transitions */
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse-slow {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
.animate-pulse-slow { animation: pulse-slow 1s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

@keyframes bounce-1 {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}
.animate-bounce-1 { animation: bounce-1 0.9s ease-in-out infinite; }

@keyframes bounce-2 {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}
.animate-bounce-2 { animation: bounce-2 0.9s ease-in-out infinite 0.1s; }

@keyframes bounce-3 {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}
.animate-bounce-3 { animation: bounce-3 0.9s ease-in-out infinite 0.2s; }

@keyframes bounce-4 {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}
.animate-bounce-4 { animation: bounce-4 0.9s ease-in-out infinite 0.3s; }
</style>
