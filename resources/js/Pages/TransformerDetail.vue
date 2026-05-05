<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import VueApexCharts from "vue3-apexcharts";
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Dialog from 'primevue/dialog';
import Card from 'primevue/card';
import InputSwitch from 'primevue/inputswitch';
import Slider from 'primevue/slider';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
  transformer: { type: Object, default: () => ({ transformer_id: 'TR-GOMA-01', uuid: 'UUID-8982-XYZ' }) },
  history: { type: Array, default: () => [] }
});

const goBack = () => router.back();
const DATA_LENGTH = 850;

const timeUnits = [
  { label: 'Seconde(s)', value: 1000 },
  { label: 'Minute(s)', value: 60000 },
  { label: 'Heure(s)', value: 3600000 },
  { label: 'Jour(s)', value: 86400000 }
];

const sizeOptions = [
  { label: 'Pleine largeur', value: 'md:col-span-2' },
  { label: 'Demi-largeur', value: 'md:col-span-1' }
];

const rangeOptions = [
  { label: '25 points', value: 25 },
  { label: '50 points', value: 50 },
  { label: '100 points', value: 100 },
  { label: '150 points', value: 150 },
  { label: '200 points', value: 200 },
  { label: '300 points', value: 300 },
  { label: '500 points', value: 500 },
  { label: '850 points', value: 850 }
];

const getNewValue = (base, variance) => parseFloat((base + (Math.random() * variance * 2 - variance)).toFixed(2));

const generateHistory = (bases, variances, windowMs, count) => {
  const now = Date.now();
  const stepMs = windowMs / count;
  return bases.map((base, idx) =>
    Array.from({ length: count }, (_, i) => [now - (count - i) * stepMs, getNewValue(base, variances[idx])])
  );
};

const formatTimeLabel = (val, unitValue) => {
  const unit = timeUnits.find(u => u.value === unitValue);
  return `${val} ${unit ? unit.label : ''}`;
};

const buildOptions = (chart) => ({
  chart: {
    id: chart.id,
    type: chart.type,
    animations: { enabled: false },
    toolbar: {
      show: true,
      autoSelected: 'zoom',
      tools: {
        download: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: true,
        reset: true,
        selection: false
      }
    },
    zoom: { enabled: true, type: 'x', autoScaleYaxis: true, allowMouseWheelZoom: true },
    background: '#fff',
    fontFamily: 'inherit',
    redrawOnParentResize: true,
    redrawOnWindowResize: true,
    events: {
      zoomed: (_, { xaxis }) => {
        chart.zoomRange = { min: xaxis.min, max: xaxis.max };
        chart.userZoomed = true;
      },
      beforeResetZoom: () => ({
        xaxis: {
          min: chart.fullRange.min,
          max: chart.fullRange.max
        }
      })
    }
  },
  stroke: { curve: 'straight', width: 2 },
  colors: chart.chartColors,
  grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
  markers: { size: 0, hover: { size: 4 } },
  dataLabels: { enabled: false },
  tooltip: { theme: 'light', shared: true, intersect: false, x: { format: 'dd MMM yyyy - HH:mm:ss' } },
  xaxis: {
    type: 'datetime',
    tickAmount: 8,
    labels: { datetimeUTC: false, style: { colors: '#64748b', fontSize: '12px', fontWeight: 500 } },
    min: chart.userZoomed ? chart.zoomRange?.min : chart.visibleRange?.min,
    max: chart.userZoomed ? chart.zoomRange?.max : chart.visibleRange?.max
  },
  yaxis: { min: chart.yMin, max: chart.yMax, labels: { style: { colors: '#64748b', fontSize: '12px', fontWeight: 600 } } },
  legend: { position: 'top', horizontalAlign: 'left' },
  annotations: chart.threshold ? { yaxis: [{ y: chart.threshold.val, borderColor: '#ef4444', strokeDashArray: 5, label: { borderColor: '#ef4444', style: { color: '#fff', background: '#ef4444' }, text: chart.threshold.label } }] } : {}
});

const charts = ref([
  {
    id: 'current', title: 'Évolution des Courants', icon: 'pi pi-bolt text-yellow-500',
    seriesNames: ['Current L1', 'Current L2', 'Current L3'], bases: [90, 95, 88], variances: [10, 10, 10],
    type: 'line', chartColors: ['#475569', '#3b82f6', '#ea580c'], yMin: 70, yMax: 110, threshold: null,
    config: { refreshVal: 5, refreshUnit: 1000, windowVal: 1, windowUnit: 3600000, sizeClass: 'md:col-span-2', visiblePoints: 200 },
    timer: null, series: [], fullRange: {}, visibleRange: {}, userZoomed: false, options: {}
  },
  {
    id: 'voltage', title: 'Tensions par Phase', icon: 'pi pi-bolt text-blue-500',
    seriesNames: ['Voltage V1', 'Voltage V2', 'Voltage V3'], bases: [240, 239.5, 240.5], variances: [3, 3, 3],
    type: 'line', chartColors: ['#475569', '#3b82f6', '#ea580c'], yMin: 225, yMax: 245, threshold: null,
    config: { refreshVal: 5, refreshUnit: 1000, windowVal: 1, windowUnit: 3600000, sizeClass: 'md:col-span-2', visiblePoints: 200 },
    timer: null, series: [], fullRange: {}, visibleRange: {}, userZoomed: false, options: {}
  },
  {
    id: 'freq', title: 'Stabilité de la Fréquence', icon: 'pi pi-wave-pulse text-purple-500',
    seriesNames: ['Fréquence Hz'], bases: [49.8], variances: [0.2],
    type: 'line', chartColors: ['#ea580c'], yMin: 47.5, yMax: 50.5, threshold: { val: 50, label: 'Cible (50Hz)' },
    config: { refreshVal: 15, refreshUnit: 1000, windowVal: 2, windowUnit: 3600000, sizeClass: 'md:col-span-2', visiblePoints: 150 },
    timer: null, series: [], fullRange: {}, visibleRange: {}, userZoomed: false, options: {}
  },
  {
    id: 'peak', title: 'Puissance Crête', icon: 'pi pi-chart-line text-green-500',
    seriesNames: ['Peak of Mean 3 phases'], bases: [165], variances: [15],
    type: 'line', chartColors: ['#ea580c'], yMin: 150, yMax: 190, threshold: null,
    config: { refreshVal: 1, refreshUnit: 60000, windowVal: 24, windowUnit: 3600000, sizeClass: 'md:col-span-2', visiblePoints: 300 },
    timer: null, series: [], fullRange: {}, visibleRange: {}, userZoomed: false, options: {}
  },
  {
    id: 'temp', title: 'Température Interne', icon: 'pi pi-thermometer text-red-500',
    seriesNames: ['Température °C'], bases: [65], variances: [10],
    type: 'line', chartColors: ['#ef4444'], yMin: 40, yMax: 100, threshold: { val: 85, label: 'Seuil Critique (85°C)' },
    config: { refreshVal: 10, refreshUnit: 1000, windowVal: 12, windowUnit: 3600000, sizeClass: 'md:col-span-1', visiblePoints: 120 },
    timer: null, series: [], fullRange: {}, visibleRange: {}, userZoomed: false, options: {}
  },
  {
    id: 'press', title: "Pression d'Huile", icon: 'pi pi-gauge text-cyan-500',
    seriesNames: ['Pression Bar'], bases: [1.8], variances: [0.4],
    type: 'line', chartColors: ['#0ea5e9'], yMin: 0.5, yMax: 3.0, threshold: { val: 2.5, label: 'Surpression (2.5 Bar)' },
    config: { refreshVal: 10, refreshUnit: 1000, windowVal: 12, windowUnit: 3600000, sizeClass: 'md:col-span-1', visiblePoints: 120 },
    timer: null, series: [], fullRange: {}, visibleRange: {}, userZoomed: false, options: {}
  }
]);

const initVisibleRange = (chart) => {
  const all = chart.series[0]?.data || [];
  if (!all.length) return;
  const visiblePoints = Math.max(25, Number(chart.config.visiblePoints || 100));
  const startIndex = Math.max(0, all.length - visiblePoints);
  chart.visibleRange = { min: all[startIndex][0], max: all[all.length - 1][0] };
};

const refreshChartOptions = (chart) => {
  chart.options = buildOptions(chart);
};

const startChartTimer = (chart) => {
  if (chart.timer) clearInterval(chart.timer);

  const windowMs = chart.config.windowVal * chart.config.windowUnit;
  const refreshMs = chart.config.refreshVal * chart.config.refreshUnit;
  const hist = generateHistory(chart.bases, chart.variances, windowMs, DATA_LENGTH);
  const allPoints = hist[0].map(p => p[0]);

  chart.fullRange = { min: allPoints[0], max: allPoints[allPoints.length - 1] };
  chart.series = chart.seriesNames.map((name, i) => ({ name, data: hist[i] }));
  chart.userZoomed = false;
  initVisibleRange(chart);
  refreshChartOptions(chart);

  chart.timer = setInterval(() => {
    const now = Date.now();
    chart.series = chart.series.map((s, idx) => {
      const variance = Number(chart.config.spread ?? chart.variances[idx]);
      let val = getNewValue(chart.bases[idx], variance);
      if (chart.id === 'voltage' && Math.random() > 0.95) val -= 10;
      if (chart.id === 'freq' && Math.random() > 0.95) val -= 1.5;
      const data = [...s.data, [now, val]].slice(-DATA_LENGTH);
      return { ...s, data };
    });

    if (!chart.userZoomed) {
      const all = chart.series[0]?.data || [];
      const visiblePoints = Math.max(25, Number(chart.config.visiblePoints || 100));
      const startIndex = Math.max(0, all.length - visiblePoints);
      chart.visibleRange = { min: all[startIndex][0], max: all[all.length - 1][0] };
      refreshChartOptions(chart);
    }
  }, refreshMs);
};

const displayConfigModal = ref(false);
const activeChart = ref(null);
const editConfig = ref({});
const ui = ref({ liveMode: true });

const openConfig = async (chart) => {
  activeChart.value = chart;
  editConfig.value = JSON.parse(JSON.stringify(chart.config));
  displayConfigModal.value = true;
  await nextTick();
};

const applyChartConfig = () => {
  if (!activeChart.value) return;
  activeChart.value.config = JSON.parse(JSON.stringify(editConfig.value));
  startChartTimer(activeChart.value);
  displayConfigModal.value = false;
};

const resetZoomAndWindow = async (chart) => {
  chart.userZoomed = false;
  startChartTimer(chart);
  await nextTick();
};

const applyVisiblePointsPreview = () => {
  if (!activeChart.value) return;
  const chart = activeChart.value;
  const all = chart.series[0]?.data || [];
  const visiblePoints = Math.max(25, Number(editConfig.value.visiblePoints || 100));
  const startIndex = Math.max(0, all.length - visiblePoints);
  chart.visibleRange = { min: all[startIndex]?.[0], max: all[all.length - 1]?.[0] };
  chart.options = buildOptions(chart);
};

const resetZoomInModal = () => {
  if (!activeChart.value) return;
  editConfig.value.visiblePoints = activeChart.value.config.visiblePoints;
  applyVisiblePointsPreview();
};

const draggedIndex = ref(null);
const onDragStart = (e, index) => { draggedIndex.value = index; e.dataTransfer.effectAllowed = 'move'; };
const onDrop = (index) => {
  if (draggedIndex.value !== null && draggedIndex.value !== index) {
    charts.value.splice(index, 0, charts.value.splice(draggedIndex.value, 1)[0]);
  }
};

const gaugeSeries = ref([121]);
const gaugeOptions = ref({
  chart: { type: 'radialBar', height: 220, toolbar: { show: false } },
  plotOptions: {
    radialBar: {
      startAngle: -135,
      endAngle: 135,
      hollow: { size: '62%' },
      track: { background: '#f1f5f9' },
      dataLabels: {
        name: { show: false },
        value: { fontSize: '30px', fontWeight: '800', color: '#16a34a', offsetY: 8 }
      }
    }
  },
  colors: ['#16a34a'],
  stroke: { lineCap: 'round' }
});

onMounted(() => charts.value.forEach(startChartTimer));
onUnmounted(() => charts.value.forEach(c => clearInterval(c.timer)));
</script>

<template>
  <AppLayout>
    <div class="p-4 lg:p-6 bg-slate-100 min-h-screen font-sans">
      <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
          <Button icon="pi pi-arrow-left" class="p-button-rounded bg-white text-slate-800 shadow-sm border-slate-200" @click="goBack" />
          <div>
            <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Tableau de bord : {{ props.transformer.transformer_id }}</h1>
            <p class="text-sm text-slate-500 font-semibold">Télémétrie Avancée • UUID: {{ props.transformer.uuid }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 bg-white border rounded-xl px-3 py-2 shadow-sm">
          <span class="text-sm font-semibold text-slate-600">Mode live</span>
          <InputSwitch v-model="ui.liveMode" />
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mb-8">
        <div class="xl:col-span-10 flex flex-col gap-3">
          <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <Card class="bg-[#ea580c] text-white border-0 shadow-sm"><template #content><div class="text-center"><div class="text-xs font-bold uppercase tracking-wider">Power (kVa)</div><div class="text-3xl font-black mt-1">160</div></div></template></Card>
            <Card class="bg-[#ea580c] text-white border-0 shadow-sm"><template #content><div class="text-center"><div class="text-xs font-bold uppercase tracking-wider">cos φ</div><div class="text-3xl font-black mt-1">0,87</div></div></template></Card>
            <Card class="col-span-2 bg-[#475569] text-white border-0 shadow-sm"><template #content><div class="text-center"><div class="text-xs font-bold uppercase tracking-wider">Unbalance Current L</div><div class="text-3xl font-black mt-1">5,08%</div></div></template></Card>
            <Card class="bg-[#ea580c] text-white border-0 shadow-sm"><template #content><div class="text-center"><div class="text-[11px] font-bold uppercase tracking-wider">Unbalance Voltage V</div><div class="text-3xl font-black mt-1">0,16%</div></div></template></Card>
          </div>

          <div class="grid grid-cols-4 md:grid-cols-8 gap-3">
            <Card v-for="(v, k) in {'Current L1':'182,83', 'Current L2':'183,86', 'Current L3':'179,01', 'Neutral L':'0,38'}" :key="k" class="bg-[#475569] text-white border-0 shadow-sm"><template #content><div class="text-center"><div class="text-[10px] font-bold uppercase opacity-80">{{ k }}</div><div class="text-2xl font-black">{{ v }}</div></div></template></Card>
            <Card v-for="(v, k) in {'Voltage V1':'250,18', 'Voltage V2':'249,73', 'Voltage V3':'250,33', 'Frequency Hz':'50,87'}" :key="k" class="bg-[#ea580c] text-white border-0 shadow-sm"><template #content><div class="text-center"><div class="text-[10px] font-bold uppercase opacity-80">{{ k }}</div><div class="text-2xl font-black">{{ v }}</div></div></template></Card>
          </div>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 flex flex-col items-center justify-center relative shadow-sm overflow-hidden">
          <span class="absolute top-4 text-xs font-bold text-slate-700 uppercase tracking-widest">Peak Power(kVA)</span>
          <VueApexCharts type="radialBar" height="220" :options="gaugeOptions" :series="gaugeSeries" class="mt-4" />
        </div>
      </div>

      <TransitionGroup name="list" tag="div" class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-10">
        <div v-for="(chart, index) in charts" :key="chart.id" :class="['bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden', chart.config.sizeClass]" draggable="true" @dragstart="onDragStart($event, index)" @dragover.prevent @dragenter.prevent @drop="onDrop(index)">
          <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200 p-4 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-slate-200"><i :class="chart.icon" class="text-lg"></i></div>
              <div>
                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide">{{ chart.title }}</h3>
                <p class="text-[11px] text-slate-500 font-medium">Glissez pour réorganiser • Zoom X activé</p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded-lg shadow-sm">Refresh: {{ formatTimeLabel(chart.config.refreshVal, chart.config.refreshUnit) }}</span>
              <span class="text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded-lg shadow-sm">Axe X: {{ formatTimeLabel(chart.config.windowVal, chart.config.windowUnit) }}</span>
              <span class="text-[10px] font-bold text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded-lg shadow-sm">Visible: {{ chart.config.visiblePoints }} points</span>
              <Button icon="pi pi-search-plus" label="Reset zoom" severity="secondary" outlined class="h-8 text-xs" @click="resetZoomAndWindow(chart)" />
              <Button icon="pi pi-cog" label="Config" class="h-8 text-xs" @click="openConfig(chart)" />
            </div>
          </div>
          <div class="p-2">
            <VueApexCharts :type="chart.type" height="300" :options="chart.options" :series="chart.series" />
          </div>
        </div>
      </TransitionGroup>

      <Dialog v-model:visible="displayConfigModal" modal maximizable :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '95vw' }" class="dashboard-dialog" :draggable="false">
        <template #header>
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-slate-900 text-white flex items-center justify-center shadow"><i class="pi pi-sliders-h"></i></div>
            <div>
              <div class="text-lg font-bold text-slate-900">Configuration du graphique</div>
              <div class="text-sm text-slate-500">{{ activeChart?.title }}</div>
            </div>
          </div>
        </template>

        <div v-if="activeChart" class="space-y-5 pt-2">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50">
              <label class="text-sm font-bold text-slate-700 block mb-2">Taille d’affichage</label>
              <Dropdown v-model="editConfig.sizeClass" :options="sizeOptions" optionLabel="label" optionValue="value" class="w-full" />
              <p class="text-xs text-slate-500 mt-2">Choisissez la largeur du bloc graphique.</p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50">
              <label class="text-sm font-bold text-slate-700 block mb-2">Mode de mise à jour</label>
              <div class="flex items-center justify-between gap-3"><span class="text-sm text-slate-600">Rafraîchissement live</span><InputSwitch v-model="ui.liveMode" /></div>
              <p class="text-xs text-slate-500 mt-2">Le zoom se conserve pendant les changements de données.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
              <label class="text-sm font-bold text-slate-700 block mb-2"><i class="pi pi-sync text-emerald-500 mr-1"></i> Taux de rafraîchissement</label>
              <div class="flex gap-2">
                <InputNumber v-model="editConfig.refreshVal" :min="1" :useGrouping="false" class="w-28 input-visible" inputClass="text-center font-bold text-slate-900 bg-white" />
                <Dropdown v-model="editConfig.refreshUnit" :options="timeUnits" optionLabel="label" optionValue="value" class="flex-1" />
              </div>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
              <label class="text-sm font-bold text-slate-700 block mb-2"><i class="pi pi-calendar text-indigo-500 mr-1"></i> Axe du temps</label>
              <div class="flex gap-2">
                <InputNumber v-model="editConfig.windowVal" :min="1" :useGrouping="false" class="w-28 input-visible" inputClass="text-center font-bold text-slate-900 bg-white" />
                <Dropdown v-model="editConfig.windowUnit" :options="timeUnits" optionLabel="label" optionValue="value" class="flex-1" />
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
              <label class="text-sm font-bold text-slate-700 block mb-2">Nombre de valeurs visibles</label>
              <div class="flex items-center gap-3">
                <Slider v-model="editConfig.visiblePoints" :min="25" :max="850" :step="5" class="flex-1" />
                <span class="w-20 text-right text-sm font-bold text-slate-700">{{ editConfig.visiblePoints }}</span>
              </div>
              <p class="text-xs text-slate-500 mt-2">Plus ce nombre est petit, plus la vue est zoomée. La configuration reste mémorisée.</p>
            </div>
            <div class="p-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
              <label class="text-sm font-bold text-slate-700 block mb-2">Aperçu rapide</label>
              <div class="flex gap-2">
                <Button label="Vue serrée" outlined severity="secondary" class="flex-1" @click="editConfig.visiblePoints = 50; applyVisiblePointsPreview()" />
                <Button label="Vue large" outlined severity="secondary" class="flex-1" @click="editConfig.visiblePoints = 300; applyVisiblePointsPreview()" />
              </div>
            </div>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-between items-center w-full">
            <Button label="Annuler" icon="pi pi-times" text severity="secondary" @click="displayConfigModal = false" />
            <Button label="Enregistrer" icon="pi pi-check" class="bg-slate-900 border-0" @click="applyChartConfig" />
          </div>
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>

<style scoped>
.list-move,.list-enter-active,.list-leave-active{transition:all .35s ease}
.list-enter-from,.list-leave-to{opacity:0;transform:translateY(12px)}
.list-leave-active{position:absolute}
:deep(.dashboard-dialog .p-dialog-header){padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0}
:deep(.dashboard-dialog .p-dialog-content){padding:1.5rem}
:deep(.dashboard-dialog .p-dialog-footer){padding:1rem 1.5rem;border-top:1px solid #e2e8f0;background:#f8fafc}
:deep(.dashboard-dialog .p-inputtext){background:#fff !important;color:#0f172a !important;border:1px solid #cbd5e1 !important}
:deep(.dashboard-dialog .p-inputtext:focus){box-shadow:0 0 0 2px rgba(15,23,42,.08) !important;border-color:#0f172a !important}
:deep(.dashboard-dialog .p-dropdown){background:#fff !important;color:#0f172a !important;border:1px solid #cbd5e1 !important}
:deep(.dashboard-dialog .p-dropdown-label){color:#0f172a !important}
:deep(.dashboard-dialog .p-inputnumber-input){background:#fff !important;color:#0f172a !important;border:1px solid #cbd5e1 !important}
:deep(.dashboard-dialog .p-button){white-space:nowrap}
</style>
