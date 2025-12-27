<script setup>
/** * QUANTUM ARCHITECT OS v11.5 - "SUPREME EDITION"
 * Moteur complet : Gestion de pages, Historique, Bridge Laravel, Import CSV & Table Builder
 */
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import { faker } from '@faker-js/faker';
import axios from 'axios';

// --- INITIALISATION MOTEUR GRAPHIQUE ---
import {
    Chart as ChartJS, Title, Tooltip, Legend, BarElement,
    CategoryScale, LinearScale, PointElement, LineElement, ArcElement, RadialLinearScale
} from 'chart.js';
import { Bar, Line, Pie, Doughnut, Radar } from 'vue-chartjs';
import draggable from 'vuedraggable';

ChartJS.register(
    Title, Tooltip, Legend, BarElement,
    CategoryScale, LinearScale, PointElement, LineElement, ArcElement, RadialLinearScale
);

// --- ÉTATS RÉACTIFS DE BASE ---
const toast = useToast();
const confirm = useConfirm();
const isLoading = ref(false);
const currentPageIdx = ref(0);
const selectedWidgetIdx = ref(null);
const zoomLevel = ref(0.80);
const isDragging = ref(false);
const pageDragIndex = ref(null);
const laravelModels = ref([]);

// --- NOUVEAU : Gestion du thème ---
const currentTheme = ref('dark'); // 'dark' ou 'light'

// --- NOUVEAU : Gestion des templates ---
const reportTemplates = ref([]);
const saveTemplateDialog = ref(false);
const newTemplateName = ref('');
const selectedTemplateToLoad = ref(null);

// --- CONFIGURATION DU TABLEAU (MODAL & CSV) ---
const tableModal = ref(false);
const tableConfig = reactive({
    tempColumns: [],
    tempRows: []
});

// --- SYSTÈME D'HISTORIQUE (ANTI-ERREUR .LENGTH) ---
const history = ref([]);
const historyIndex = ref(-1);

const saveToHistory = () => {
    if (!pages.value) return;
    const state = JSON.stringify(pages.value);
    if (historyIndex.value < history.value.length - 1) {
        history.value = history.value.slice(0, historyIndex.value + 1);
    }
    history.value.push(state);
    if (history.value.length > 50) history.value.shift();
    historyIndex.value = history.value.length - 1;
};

const undo = () => {
    if (historyIndex.value > 0) {
        historyIndex.value--;
        pages.value = JSON.parse(history.value[historyIndex.value]);
        toast.add({ severity: 'info', summary: 'Action annulée', life: 800 });
    }
};

const redo = () => {
    if (history.value.length > 0 && historyIndex.value < history.value.length - 1) {
        historyIndex.value++;
        pages.value = JSON.parse(history.value[historyIndex.value]);
        toast.add({ severity: 'info', summary: 'Action rétablie', life: 800 });
    }
};

const canUndo = computed(() => history.value.length > 1 && historyIndex.value > 0);
const canRedo = computed(() => history.value.length > 0 && historyIndex.value < history.value.length - 1);

// --- NOUVEAU : Référentiel de styles ---
const BORDER_STYLES = [
    { label: 'Plein', value: 'solid' },
    { label: 'Tirets', value: 'dashed' },
    { label: 'Pointillés', value: 'dotted' },
    { label: 'Aucun', value: 'none' }
];

// --- NOUVEAU : Formats de page ---
const PAGE_FORMATS = [
    { name: 'A4', w: 793, h: 1122 },
    { name: 'A3', w: 1122, h: 1587 },
    { name: 'A2', w: 1587, h: 2245 },
    { name: 'A1', w: 2245, h: 3179 },
    { name: 'A0', w: 3179, h: 4494 },
    { name: 'US Letter', w: 816, h: 1056 },
];

// --- RÉFÉRENTIELS DE DESIGN ---
const FONTS = ['Inter', 'Roboto', 'Montserrat', 'Playfair Display', 'Fira Code', 'Lato'];
const CHART_TYPES = [
    { label: 'Barres', value: 'bar', icon: 'pi pi-chart-bar' },
    { label: 'Lignes', value: 'line', icon: 'pi pi-chart-line' },
    { label: 'Secteurs', value: 'pie', icon: 'pi pi-chart-pie' },
    { label: 'Donut', value: 'doughnut', icon: 'pi pi-circle' },
    { label: 'Radar', value: 'radar', icon: 'pi pi-directions' }
];

// --- STRUCTURE DU DOCUMENT ---
const pages = ref([
    {
        id: 'p_initial',
        name: 'Nouveau Rapport',
        background: '#ffffff',
        format: PAGE_FORMATS[0],
        orientation: 'portrait',
        layoutMode: 'absolute', // 'absolute' or 'grid'
        widgets: []
    }
]);

// --- GESTION DES WIDGETS (FACTORY) ---
const addWidget = (type) => {
    let newWidget = {
        id: 'w_' + Date.now(),
        type,
        colSpan: 4, // Pour le mode grille
        rowSpan: 2, // Pour le mode grille
        x: 100, y: 150, w: type === 'kpi' ? 250 : 450, h: type === 'kpi' ? 140 : 300,
        isLocked: false,
        isSyncing: false,
        chartType: type === 'chart' ? 'bar' : null,
        style: {
            backgroundColor: type === 'text' ? 'transparent' : '#ffffff',
            borderRadius: 8,
            borderWidth: type === 'text' ? 0 : (type === 'shape' ? 0 : 1),
            borderColor: '#CBD5E1',
            borderStyle: 'solid',
            opacity: 1,
            zIndex: 10,
            shadow: type === 'text' ? 'none' : '0 4px 6px -1px rgba(0,0,0,0.1)',
            rotation: 0,
            padding: 15
        },
        config: { headerBg: '#1e293b', headerColor: '#ffffff', striped: true, fontSize: 12, cellPadding: 8 },
        data: { columns: [], rows: [] }
    };

    // Configuration spécifique par type de widget
    if (type === 'text') {
        newWidget.content = "Double-cliquez pour éditer le texte...";
        newWidget.config = {
            font: 'Inter',
            size: 18,
            color: '#1E293B',
            align: 'left',
            weight: '500',
            italic: false,
            underline: false,
            uppercase: false
        };
    } else if (type === 'chart') {
        newWidget.dataSources = [{ model: null, column: null, formula: 'count', color: '#6366F1' }];
        newWidget.data = {
            labels: ['Jan', 'Fev', 'Mar'],
            datasets: [{ label: 'Exemple', data: [10, 45, 23], backgroundColor: '#6366F1' }]
        };
        newWidget.config = { timeScale: 'months' };
    } else if (type === 'table') {
        newWidget.data = {
            columns: ['Référence', 'Status', 'Total'],
            rows: [{ Référence: 'INV-001', Status: 'Payé', Total: '1,200 €' }]
        };
    } else if (type === 'kpi') {
        newWidget.dataSource = { model: null, column: null, method: 'COUNT' };
        newWidget.config = {
            label: 'NOUVEAU KPI',
            value: '0.00',
            color: '#6366F1',
            prefix: '',
            trend: '',
            timeScale: 'days' // Intervalle par défaut
        };
    } else if (type === 'image') {
        newWidget.imageUrl = null;
    } else if (type === 'shape') {
        newWidget.w = 300; newWidget.h = 10; // Taille par défaut pour une ligne
        newWidget.style.backgroundColor = 'transparent'; // Les formes n'ont pas de fond par défaut
        newWidget.style.borderWidth = 0;
        newWidget.style.padding = 0;
        newWidget.config = {
            shapeType: 'line', // line, rectangle, ellipse, circle, triangle, star
            strokeColor: '#334155', // Couleur du trait
            strokeWidth: 4,
            strokeStyle: 'solid'
        };
    }

    pages.value[currentPageIdx.value].widgets.push(newWidget);
    selectedWidgetIdx.value = pages.value[currentPageIdx.value].widgets.length - 1;
    saveToHistory();
};

// --- GESTION DES WIDGETS (CLONAGE/SUPPRESSION) ---
const cloneWidget = (idx) => {
    const original = pages.value[currentPageIdx.value].widgets[idx];
    const newWidget = JSON.parse(JSON.stringify(original));
    newWidget.id = 'w_' + Date.now();
    newWidget.x += 20;
    newWidget.y += 20;
    pages.value[currentPageIdx.value].widgets.push(newWidget);
    selectedWidgetIdx.value = pages.value[currentPageIdx.value].widgets.length - 1;
    saveToHistory();
    toast.add({ severity: 'info', summary: 'Dupliqué', detail: 'Le bloc a été cloné.', life: 2000 });
};

const deleteWidget = (idx) => {
    confirm.require({
        message: 'Voulez-vous vraiment supprimer ce bloc ?',
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            pages.value[currentPageIdx.value].widgets.splice(idx, 1);
            selectedWidgetIdx.value = null;
            saveToHistory();
        }
    });
};

// --- LOGIQUE ÉDITEUR DE TABLEAU ---
const openTableEditor = () => {
    if (currentWidget.value && currentWidget.value.type === 'table') {
        tableConfig.tempColumns = [...currentWidget.value.data.columns];
        tableConfig.tempRows = JSON.parse(JSON.stringify(currentWidget.value.data.rows));
        tableModal.value = true;
    }
};

const addTableColumn = () => {
    const name = `Col ${tableConfig.tempColumns.length + 1}`;
    tableConfig.tempColumns.push(name);
    tableConfig.tempRows.forEach(r => r[name] = '-');
};

const deleteTableColumn = (colName) => {
    const index = tableConfig.tempColumns.indexOf(colName);
    if (index > -1) {
        tableConfig.tempColumns.splice(index, 1);
        tableConfig.tempRows.forEach(row => {
            delete row[colName];
        });
    }
};

const addTableRow = () => {
    const row = {};
    tableConfig.tempColumns.forEach(c => row[c] = '');
    tableConfig.tempRows.push(row);
};

const saveTableStructure = () => {
    currentWidget.value.data.columns = [...tableConfig.tempColumns];
    currentWidget.value.data.rows = [...tableConfig.tempRows];
    tableModal.value = false;
    saveToHistory();
};

// --- LOGIQUE IMPORT CSV ---
const handleCSVImport = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const content = e.target.result;
        const lines = content.split('\n');
        if (lines.length > 0) {
            const headers = lines[0].split(',').map(h => h.trim());
            const rows = lines.slice(1).filter(l => l.trim() !== "").map(line => {
                const values = line.split(',');
                const obj = {};
                headers.forEach((h, i) => obj[h] = values[i]?.trim() || '');
                return obj;
            });
            tableConfig.tempColumns = headers;
            tableConfig.tempRows = rows;
            if (currentWidget.value && currentWidget.value.type === 'table') {
                currentWidget.value.data.columns = headers;
                currentWidget.value.data.rows = rows;
                saveToHistory();
            }
            toast.add({ severity: 'success', summary: 'CSV Importé', detail: `${rows.length} lignes chargées.` });
        }
    };
    reader.readAsText(file);
};

// --- MANIPULATION DES OBJETS (DRAG/RESIZE) ---
const startDragging = (e, item, idx) => {
    selectedWidgetIdx.value = idx; // Sélectionne le widget au début du drag
    isDragging.value = true;
    const startX = e.clientX; const startY = e.clientY;
    const initX = item.x; const initY = item.y;

    const onMove = (ev) => {
        if (isDragging.value) { item.x = initX + (ev.clientX - startX) / zoomLevel.value; item.y = initY + (ev.clientY - startY) / zoomLevel.value; }
    };

    const onUp = () => {
        isDragging.value = false;
        saveToHistory();
        window.removeEventListener('mouseup', onUp, { once: true });
        window.removeEventListener('mousemove', onMove);
    };
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp, { once: true });
};

const startResizing = (e, item) => {
    e.preventDefault(); e.stopPropagation();
    const startW = item.w; const startH = item.h;
    const startX = e.clientX; const startY = e.clientY;

    const onMove = (ev) => {
        item.w = Math.max(50, startW + (ev.clientX - startX) / zoomLevel.value);
        item.h = Math.max(30, startH + (ev.clientY - startY) / zoomLevel.value);
    };

    const onUp = () => {
        saveToHistory();
        window.removeEventListener('mousemove', onMove);
    };
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp, { once: true });
};

// --- BRIDGE LARAVEL ELOQUENT ---
const syncWithLaravel = async (widget) => {
    if ((widget.type === 'chart' && (!widget.dataSources || widget.dataSources.some(s => !s.model || !s.column))) ||
        (widget.type === 'kpi' && (!widget.dataSource || !widget.dataSource.model || !widget.dataSource.column))) {
        toast.add({ severity: 'warn', summary: 'Configuration incomplète', detail: 'Veuillez sélectionner un modèle et un champ.', life: 4000 });
        return;
    }

    widget.isSyncing = true;
    try {
        const payload = { type: widget.type };
        if (widget.type === 'chart') payload.config = { sources: widget.dataSources, timeScale: widget.config.timeScale };
        if (widget.type === 'kpi') payload.config = { ...widget.dataSource, timeScale: widget.config.timeScale };

        const res = await axios.post(route('quantum.query'), payload);

        if (widget.type === 'chart') {
            widget.data = res.data;
            toast.add({ severity: 'success', summary: 'Synchronisé', detail: 'Les données du graphique ont été mises à jour.', life: 2000 });
        }

        // Logique pour le KPI
        if (widget.type === 'kpi') {
            widget.config.value = res.data.value;
            widget.config.label = res.data.label;
            toast.add({ severity: 'success', summary: 'Synchronisé', detail: 'La valeur du KPI a été mise à jour.', life: 2000 });
        }
        saveToHistory();
    } catch (e) {
        console.error("Erreur lors de la synchronisation:", e);
        toast.add({ severity: 'error', summary: 'Erreur Backend', detail: e.response?.data?.message || 'Impossible de récupérer les données.' });
    } finally { widget.isSyncing = false; }
};

// --- GESTION DES PAGES ---
const addPage = () => {
    pages.value.push({
        id: 'p_' + Date.now(),
        name: `Page ${pages.value.length + 1}`,
        background: '#ffffff',
        format: PAGE_FORMATS[0], // Format A4 par défaut
        orientation: 'portrait', // Orientation par défaut
        layoutMode: 'absolute',
        widgets: []
    });
    saveToHistory();
    toast.add({ severity: 'success', summary: 'Page Ajoutée', life: 1500 });
    // On ne change pas de page automatiquement, l'utilisateur peut cliquer dessus
};

const deletePage = (index) => {
    if (pages.value.length <= 1) {
        toast.add({ severity: 'warn', summary: 'Action impossible', detail: 'Le document doit contenir au moins une page.', life: 3000 });
        return;
    }
    pages.value.splice(index, 1);
    if (currentPageIdx.value >= pages.value.length) {
        currentPageIdx.value = pages.value.length - 1;
    }
    saveToHistory();
    toast.add({ severity: 'info', summary: 'Page supprimée', life: 1500 });
};

const duplicatePage = (index) => {
    const originalPage = pages.value[index];
    const newPage = JSON.parse(JSON.stringify(originalPage));
    newPage.id = 'p_' + Date.now();
    newPage.name = `${originalPage.name} (Copie)`;
    pages.value.splice(index + 1, 0, newPage);
    saveToHistory();
    toast.add({ severity: 'success', summary: 'Page Dupliquée', life: 1500 });
};

const updatePageName = (page) => {
    // Ici, vous pourriez appeler une route API pour sauvegarder le nom
    saveToHistory();
};

const handlePageDrop = (event, dropIndex) => {
    const draggedIndex = pageDragIndex.value;
    if (draggedIndex === null || draggedIndex === dropIndex) return;
    const pageToMove = pages.value.splice(draggedIndex, 1)[0];
    pages.value.splice(dropIndex, 0, pageToMove);
    pageDragIndex.value = null;
    saveToHistory();
};

// --- NOUVEAU : GESTION DES TEMPLATES ---

const saveCurrentReportAsTemplate = async () => {
    if (!newTemplateName.value.trim()) {
        toast.add({ severity: 'warn', summary: 'Nom requis', detail: 'Veuillez donner un nom à votre modèle.', life: 3000 });
        return;
    }
    try {
        const payload = { name: newTemplateName.value, content: JSON.stringify(pages.value) };
        const response = await axios.post(route('report-templates.store'), payload);
        reportTemplates.value.push(response.data);
        saveTemplateDialog.value = false;
        toast.add({ severity: 'success', summary: 'Modèle Sauvegardé', detail: `Le modèle "${newTemplateName.value}" a été créé.`, life: 3000 });
    } catch (error) {
        console.error("Erreur lors de la sauvegarde du modèle:", error);
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de sauvegarder le modèle.', life: 4000 });
    }
};

const loadTemplate = async (templateId) => {
    if (!templateId) return;

    confirm.require({
        message: 'Charger ce modèle remplacera votre rapport actuel. Voulez-vous continuer ?',
        header: 'Confirmation de chargement',
        icon: 'pi pi-exclamation-triangle',
        accept: async () => {
            try {
                const response = await axios.get(route('report-templates.show', templateId));
                pages.value = JSON.parse(response.data.content);
                currentPageIdx.value = 0;
                selectedWidgetIdx.value = null;
                history.value = [JSON.stringify(pages.value)];
                historyIndex.value = 0;
                toast.add({ severity: 'success', summary: 'Modèle Chargé', detail: `Le modèle "${response.data.name}" est prêt.`, life: 3000 });
            } catch (error) {
                console.error("Erreur lors du chargement du modèle:", error);
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger le modèle.', life: 4000 });
            } finally {
                selectedTemplateToLoad.value = null;
            }
        },
        reject: () => {
            selectedTemplateToLoad.value = null; // Réinitialiser le dropdown si l'utilisateur annule
        }
    });
};

const deleteTemplate = (templateId, templateName) => {
    confirm.require({
        message: `Êtes-vous sûr de vouloir supprimer le modèle "${templateName}" ? Cette action est irréversible.`,
        header: 'Confirmation de suppression',
        icon: 'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: async () => {
            try {
                await axios.delete(route('report-templates.destroy', templateId));
                reportTemplates.value = reportTemplates.value.filter(t => t.id !== templateId);
                toast.add({ severity: 'success', summary: 'Supprimé', detail: `Le modèle "${templateName}" a été supprimé.`, life: 3000 });
            } catch (error) {
                console.error("Erreur lors de la suppression du modèle:", error);
                toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de supprimer le modèle.', life: 4000 });
            }
        }
    });
};

// --- NOUVEAU : GESTION DES TEMPLATES ---
const openSaveTemplateDialog = () => {
    newTemplateName.value = pages.value[currentPageIdx.value].name;
    saveTemplateDialog.value = true;
};

// const saveCurrentReportAsTemplate = async () => {
//     if (!newTemplateName.value.trim()) {
//         toast.add({ severity: 'warn', summary: 'Nom requis', detail: 'Veuillez donner un nom à votre modèle.', life: 3000 });
//         return;
//     }
//     try {
//         const payload = {
//             name: newTemplateName.value,
//             content: JSON.stringify(pages.value) // Sauvegarde de l'état complet des pages
//         };
//         // Note: Assurez-vous que la route 'report-templates.store' existe dans votre backend
//         const response = await axios.post(route('report-templates.store'), payload); // Changed from 'report-templates.store' to 'report-templates.store'
//         reportTemplates.value.push(response.data); // Ajouter le nouveau template à la liste
//         saveTemplateDialog.value = false;
//         toast.add({ severity: 'success', summary: 'Modèle Sauvegardé', detail: `Le modèle "${newTemplateName.value}" a été créé.`, life: 3000 });
//     } catch (error) {
//         console.error("Erreur lors de la sauvegarde du modèle:", error);
//         toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de sauvegarder le modèle.', life: 4000 });
//     }
// };

// const loadTemplate = async (templateId) => {
//     if (!templateId) return;

//     confirm.require({
//         message: 'Charger ce modèle remplacera votre rapport actuel. Voulez-vous continuer ?',
//         header: 'Confirmation de chargement',
//         icon: 'pi pi-exclamation-triangle',
//         accept: async () => {
//             try {
//                 // Note: Assurez-vous que la route 'report-templates.show' existe
//                 const response = await axios.get(route('report-templates.show', templateId));
//                 pages.value = JSON.parse(response.data.content);
//                 currentPageIdx.value = 0;
//                 selectedWidgetIdx.value = null;
//                 history.value = [JSON.stringify(pages.value)]; // Réinitialiser l'historique
//                 historyIndex.value = 0;
//                 toast.add({ severity: 'success', summary: 'Modèle Chargé', detail: `Le modèle "${response.data.name}" est prêt.`, life: 3000 });
//             } catch (error) {
//                 console.error("Erreur lors du chargement du modèle:", error);
//                 toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger le modèle.', life: 4000 });
//             } finally {
//                 selectedTemplateToLoad.value = null; // Réinitialiser le dropdown
//             }
//         }
//     });
// };
// --- GESTION DES CALQUES ---
const moveZIndex = (mode) => {
    const w = currentWidget.value;
    if (!w) return;
    if (mode === 'up') w.style.zIndex += 10;
    if (mode === 'down') w.style.zIndex = Math.max(0, w.style.zIndex - 10);
    saveToHistory();
};

// --- GESTION DES IMAGES ---
const imgUploadRefs = ref({});

const handleImageUpload = (event, widget) => {
    const file = event.target.files[0];
    if (file) widget.imageUrl = URL.createObjectURL(file);
}

const triggerImageUpload = (widgetId) => {
    const input = imgUploadRefs.value[widgetId];
    if (input) input.click();
};

// --- EXPORT PDF ---
const exportPDF = async () => {
    const { default: jsPDF } = await import('jspdf');
    const { default: html2canvas } = await import('html2canvas');
    isLoading.value = true;
    const doc = new jsPDF('p', 'mm', 'a4');

    for (let i = 0; i < pages.value.length; i++) {
        currentPageIdx.value = i;
        await nextTick();
        await new Promise(r => setTimeout(r, 600));
        const canvas = await html2canvas(document.getElementById('studio-canvas'), { scale: 2 });
        if (i > 0) doc.addPage();
        doc.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, 210, 297);
    }
    doc.save('Export_Quantum.pdf');
    isLoading.value = false;
};

// --- RACCOURCIS CLAVIER ---
const handleKeys = (e) => {
    if (e.target.tagName === 'INPUT' || e.target.contentEditable === 'true') return;
    if (e.key === 'Delete' && selectedWidgetIdx.value !== null) {
        pages.value[currentPageIdx.value].widgets.splice(selectedWidgetIdx.value, 1);
        selectedWidgetIdx.value = null;
        saveToHistory();
    }
    if (e.ctrlKey && e.key === 'z') { e.preventDefault(); undo(); }
    if (e.ctrlKey && e.key === 'y') { e.preventDefault(); redo(); }
};

// --- COMPUTED HELPERS ---
const currentWidget = computed(() => {
    const p = pages.value[currentPageIdx.value];
    return (p && selectedWidgetIdx.value !== null) ? p.widgets[selectedWidgetIdx.value] : null;
});

const canvasStyles = computed(() => {
    const page = pages.value[currentPageIdx.value];
    if (!page) return {};
    const format = page.format;
    const isLandscape = page.orientation === 'landscape';

    return {
        width: (isLandscape ? format.h : format.w) + 'px',
        height: (isLandscape ? format.w : format.h) + 'px',
        backgroundColor: page.background,
        transform: `scale(${zoomLevel.value})`,
        transformOrigin: 'top center'
    };
});

const getWidgetStyle = (w) => ({
    position: 'absolute',
    left: w.x + 'px',
    top: w.y + 'px',
    width: w.w + 'px',
    height: w.h + 'px',
    zIndex: w.style.zIndex || 10,
    opacity: w.style.opacity,
    backgroundColor: w.style.backgroundColor,
    borderRadius: w.style.borderRadius + 'px',
    border: `${w.style.borderWidth}px ${w.style.borderStyle} ${w.style.borderColor}`,
    boxShadow: w.style.shadow,
    transform: `rotate(${w.style.rotation}deg)`,
    padding: w.style.padding + 'px'
});

const getGridWidgetStyle = (w) => ({
    gridColumn: `span ${w.colSpan || 4}`,
    gridRow: `span ${w.rowSpan || 2}`,
    zIndex: w.style.zIndex || 10,
    opacity: w.style.opacity,
    backgroundColor: w.style.backgroundColor,
    borderRadius: w.style.borderRadius + 'px',
    border: w.style.borderStyle === 'none' ? 'none' : `${w.style.borderWidth}px ${w.style.borderStyle} ${w.style.borderColor}`,
    boxShadow: w.style.shadow,
    transform: `rotate(${w.style.rotation}deg)`,
    padding: w.style.padding + 'px'
});


const getChartType = (type) => {
    const map = { bar: Bar, line: Line, pie: Pie, doughnut: Doughnut, radar: Radar };
    return map[type] || Bar;
};

// --- CYCLE DE VIE ---
onMounted(() => {
    // Récupérer les modèles Laravel disponibles
    // NOUVEAU : Récupérer les templates de rapport existants
    axios.get(route('report-templates.index')).then(res => {
        reportTemplates.value = res.data;
    }).catch(() => {
        toast.add({ severity: 'warn', summary: 'Mode Démo', detail: 'Impossible de charger les modèles de rapport.', life: 3000 });
    });


    axios.get('/quantum/models').then(res => {
        laravelModels.value = res.data;
    }).catch(() => {
        // Utiliser des données Faker en cas d'échec
        laravelModels.value = ['User', 'Product', 'Order', 'Invoice'];
        toast.add({ severity: 'warn', summary: 'Mode Démo', detail: 'Impossible de charger les modèles Laravel. Utilisation de données fictives.', life: 3000 });
    });

    window.addEventListener('keydown', handleKeys);

    // Attendre que les données initiales (si asynchrones) soient chargées avant de sauvegarder l'historique
    nextTick(() => {
        if (pages.value) {
            history.value = [JSON.stringify(pages.value)];
            historyIndex.value = 0;
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeys);
});

// Watcher pour détecter les changements de propriétés et sauvegarder l'historique
watch(currentWidget, (newValue) => {
    if (newValue && !isDragging.value) {
        saveToHistory();
    }
}, { deep: true });


</script>
<template>
  <div :class="[
    'quantum-studio h-screen flex flex-col overflow-hidden font-sans select-none',
    currentTheme === 'dark' ? 'bg-[#050507] text-slate-200' : 'bg-slate-100 text-slate-800'
  ]">

    <header :class="[
        'h-16 flex items-center justify-between px-6 z-[100] shrink-0',
        currentTheme === 'dark' ? 'border-b border-white/5 bg-black/60 backdrop-blur-2xl' : 'border-b border-slate-200 bg-white/80 backdrop-blur-xl'
    ]">
      <div class="flex items-center gap-6">
        <div class="flex items-center gap-3 group cursor-pointer">
          <div class="w-9 h-9 bg-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/20 group-hover:scale-110 transition-transform">
            <i class="pi pi-bolt text-white"></i>
          </div>
          <div class="flex flex-col">
            <span class="text-[11px] font-black tracking-[0.2em] uppercase leading-none">Quantum <span class="text-primary-400">Omni</span></span>
            <span class="text-[8px] text-slate-500 font-bold tracking-tighter uppercase mt-1">v11.5 Studio Engine</span>
          </div>
        </div>

        <div :class="['h-8 w-px mx-2', currentTheme === 'dark' ? 'bg-white/10' : 'bg-slate-300']"></div>

        <div :class="['flex items-center gap-1 p-1 rounded-lg', currentTheme === 'dark' ? 'bg-white/5 border border-white/10' : 'bg-slate-200/50 border border-slate-300/50']">
          <Button icon="pi pi-undo" @click="undo" :disabled="!canUndo" :class="['p-button-text p-button-sm', currentTheme === 'dark' ? 'text-slate-400' : 'text-slate-600']" v-tooltip.bottom="'Annuler (Ctrl+Z)'" />
          <Button icon="pi pi-redo" @click="redo" :disabled="!canRedo" :class="['p-button-text p-button-sm', currentTheme === 'dark' ? 'text-slate-400' : 'text-slate-600']" v-tooltip.bottom="'Rétablir (Ctrl+Y)'" />
        </div>
      </div>

      <!-- NOUVEAU : Gestion des templates -->
      <div class="flex items-center gap-2">
          <Button label="Sauvegarder comme modèle" icon="pi pi-save" @click="openSaveTemplateDialog" :class="['p-button-text p-button-sm', currentTheme === 'dark' ? 'text-slate-400' : 'text-slate-600']" />
          <Dropdown v-model="selectedTemplateToLoad"
                :options="reportTemplates"
                optionLabel="name"
                optionValue="id"
                placeholder="Charger un modèle"
                @change="loadTemplate($event.value)"
                :class="['p-inputtext-sm w-56', currentTheme === 'dark' ? 'bg-black/40 border-white/10' : 'bg-white border-slate-300']"
                :showClear="true">
            <template #option="slotProps">
                <div class="flex justify-between items-center w-full">
                    <span>{{ slotProps.option.name }}</span>
                    <Button icon="pi pi-trash" class="p-button-danger p-button-text p-button-rounded p-button-sm" @click.stop="deleteTemplate(slotProps.option.id, slotProps.option.name)" v-tooltip.left="'Supprimer ce modèle'"/>
                </div>
            </template>
        </Dropdown>
      </div>

      <div class="flex items-center gap-4">
        <div :class="['flex items-center rounded-full px-3 py-1.5 gap-4', currentTheme === 'dark' ? 'bg-black/40 border border-white/10' : 'bg-slate-200/50 border border-slate-300/50']">
          <div class="flex items-center gap-2">
             <Button icon="pi pi-chevron-left" @click="currentPageIdx--" :disabled="currentPageIdx === 0" class="p-button-text p-button-xs" />
             <span class="text-[10px] font-black uppercase tracking-widest min-w-[80px] text-center">Page {{ currentPageIdx + 1 }} / {{ pages.length }}</span>
             <Button icon="pi pi-chevron-right" @click="currentPageIdx++" :disabled="currentPageIdx === pages.length - 1" class="p-button-text p-button-xs" />
          </div>
          <div :class="['h-4 w-px', currentTheme === 'dark' ? 'bg-white/10' : 'bg-slate-300']"></div>
          <div class="flex items-center gap-3">
            <i class="pi pi-search text-[10px] text-slate-500"></i>
            <Slider v-model="zoomLevel" :min="0.2" :max="2" :step="0.01" class="w-24" />
            <span :class="['text-[10px] font-mono w-10 text-right', currentTheme === 'dark' ? 'text-primary-400' : 'text-primary-600']">{{ Math.round(zoomLevel * 100) }}%</span>
          </div>
        </div>

        <!-- NOUVEAU : Bouton de changement de thème -->
        <Button @click="currentTheme = currentTheme === 'dark' ? 'light' : 'dark'"
                :icon="currentTheme === 'dark' ? 'pi pi-sun' : 'pi pi-moon'"
                :class="['p-button-text p-button-rounded', currentTheme === 'dark' ? 'text-slate-400' : 'text-slate-600']"
                v-tooltip.bottom="currentTheme === 'dark' ? 'Thème Clair' : 'Thème Sombre'" />

        <Button icon="pi pi-cloud-download" label="EXPORT PDF HD" @click="exportPDF" :loading="isLoading" class="p-button-primary shadow-xl shadow-primary-500/20 font-black p-button-sm italic" />
      </div>
    </header>

    <div class="flex-grow flex overflow-hidden">

      <aside :class="['w-20 flex flex-col items-center py-8 gap-6 shrink-0', currentTheme === 'dark' ? 'border-r border-white/5 bg-black/20' : 'border-r border-slate-200 bg-slate-100']">
        <button v-for="t in ['text', 'chart', 'table', 'kpi', 'image', 'shape']" :key="t" @click="addWidget(t)"
                :class="['group relative w-12 h-12 rounded-2xl flex items-center justify-center hover:bg-primary-600 transition-all', currentTheme === 'dark' ? 'bg-white/5 border border-white/5' : 'bg-white border border-slate-200 shadow-sm']">
          <i :class="[
              {'pi pi-align-left':t==='text', 'pi pi-chart-bar':t==='chart', 'pi pi-table':t==='table', 'pi pi-bolt':t==='kpi', 'pi pi-image':t==='image', 'pi pi-minus':t==='shape'},
              'text-xl group-hover:text-white',
              currentTheme === 'dark' ? 'text-slate-400' : 'text-slate-500'
          ]"></i>
          <span class="absolute left-16 bg-primary-600 text-[8px] font-bold px-2 py-1 rounded opacity-0 group-hover:opacity-100 uppercase tracking-tighter whitespace-nowrap z-50 shadow-xl">{{ t }}</span>
        </button>
      </aside>

      <main :class="['flex-grow overflow-auto p-20 flex justify-center custom-scrollbar relative', currentTheme === 'dark' ? 'bg-[#0a0a0c]' : 'bg-slate-200']" @click="selectedWidgetIdx = null">

        <div id="studio-canvas" :style="canvasStyles" :class="['relative transition-all duration-300 origin-top', currentTheme === 'dark' ? 'shadow-[0_40px_120px_rgba(0,0,0,0.7)]' : 'shadow-2xl']">

          <!-- MODE ABSOLUTE -->
          <template v-if="pages[currentPageIdx].layoutMode === 'absolute'">
            <div v-for="(w, idx) in pages[currentPageIdx].widgets" :key="w.id"
                 :style="getWidgetStyle(w)"
                 :class="['group/widget', selectedWidgetIdx === idx ? 'ring-2 ring-primary-500' : '', w.isLocked ? 'cursor-default' : 'cursor-move']"
                 @click.stop="selectedWidgetIdx = idx"
                 @mousedown.stop="!w.isLocked && startDragging($event, w, idx)">
                <div :class="['absolute -top-11 left-0 flex gap-1 opacity-0 group-hover/widget:opacity-100 transition-all no-drag z-[60] backdrop-blur-sm rounded-lg shadow-2xl', currentTheme === 'dark' ? 'bg-slate-900/80 border border-white/10' : 'bg-white/80 border border-slate-200']">
                    <div class="p-2 text-primary-400 cursor-move" v-tooltip.bottom="'Déplacer'">
                        <i class="pi pi-arrows-alt text-[10px]"></i>
                    </div>
                    <button @click.stop="w.isLocked = !w.isLocked" class="p-2 hover:text-orange-400" v-tooltip.bottom="'Verrouiller'">
                        <i :class="w.isLocked ? 'pi pi-lock text-orange-400' : 'pi pi-lock-open'" class="text-[10px]"></i>
                    </button>
                    <button @click.stop="cloneWidget(idx)" class="p-2 hover:text-green-400" v-tooltip.bottom="'Dupliquer'">
                        <i class="pi pi-copy text-[10px]"></i>
                    </button>
                    <button @click.stop="deleteWidget(idx)" class="p-2 hover:text-red-400" v-tooltip.bottom="'Supprimer'">
                        <i class="pi pi-trash text-[10px]"></i>
                    </button>
                </div>

                <!-- Contenu du widget (répliqué depuis le mode grille) -->
                <div v-if="w.type === 'text'" class="w-full h-full no-drag flex-grow">
                    <div contenteditable="true" @blur="w.content = $event.target.innerText; saveToHistory();"
                         class="w-full h-full outline-none leading-snug"
                         :style="{
                            fontFamily: w.config.font,
                            fontSize: w.config.size+'px',
                            color: w.config.color,
                            textAlign: w.config.align,
                            fontWeight: w.config.weight,
                            fontStyle: w.config.italic ? 'italic' : 'normal',
                            textDecoration: w.config.underline ? 'underline' : 'none',
                            textTransform: w.config.uppercase ? 'uppercase' : 'none'
                         }">
                      {{ w.content }}
                    </div>
                </div>

                <div v-if="w.type === 'table'" class="w-full h-full overflow-hidden no-drag bg-white rounded-lg shadow-inner flex-grow">
                    <table class="w-full h-full border-collapse">
                        <thead :style="{ background: w.config.headerBg, color: w.config.headerColor }">
                            <tr><th v-for="col in w.data.columns" :key="col" :style="{ padding: w.config.cellPadding+'px' }" class="text-left uppercase text-[9px] font-black">{{ col }}</th></tr>
                        </thead>
                        <tbody :style="{ fontSize: w.config.fontSize+'px' }">
                            <tr v-for="(row, ri) in w.data.rows" :key="ri" :class="w.config.striped && ri%2===0 ? 'bg-slate-50' : 'bg-white'">
                                <td v-for="col in w.data.columns" :key="col" :style="{ padding: w.config.cellPadding+'px' }" class="border-b border-slate-100 text-slate-800">
                                    {{ row[col] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="w.type === 'chart'" class="w-full h-full p-2 pointer-events-none flex-grow">
                    <component :is="getChartType(w.chartType)" :data="w.data" :options="{ responsive: true, maintainAspectRatio: false }" />
                </div>

                <div v-if="w.type === 'kpi'" class="w-full h-full flex flex-col justify-center px-4 no-drag flex-grow">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ w.config.label }}</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-black text-slate-900 tracking-tighter" :style="{ color: w.config.color }">{{ w.config.prefix }}{{ w.config.value }}</span>
                        <span class="text-[10px] font-bold text-emerald-500">{{ w.config.trend }}</span>
                    </div>
                </div>

                <div v-if="w.type === 'image'" class="w-full h-full no-drag flex-grow">
                    <img v-if="w.imageUrl" :src="w.imageUrl" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center flex-col text-slate-400">
                        <i class="pi pi-image text-4xl"></i>
                        <span class="text-xs mt-2">Utilisez l'inspecteur pour choisir</span>
                    </div>
                    <input type="file" accept="image/*" @change="handleImageUpload($event, w)" class="hidden" :ref="el => imgUploadRefs[w.id] = el" />
                </div>

                <div v-if="w.type === 'shape'" class="w-full h-full flex items-center justify-center no-drag flex-grow">
                    <svg width="100%" height="100%" :viewBox="`0 0 ${w.w} ${w.h}`" preserveAspectRatio="none">
                        <line v-if="w.config.shapeType === 'line'" x1="0" :y1="w.config.strokeWidth / 2" :x2="w.w" :y2="w.config.strokeWidth / 2" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" :stroke-dasharray="w.config.strokeStyle === 'dashed' ? '10,5' : (w.config.strokeStyle === 'dotted' ? '2,5' : 'none')" />
                        <rect v-if="w.config.shapeType === 'rectangle'" x="0" y="0" width="100%" height="100%" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" :stroke-dasharray="w.config.strokeStyle === 'dashed' ? '10,5' : (w.config.strokeStyle === 'dotted' ? '2,5' : 'none')" />
                        <ellipse v-if="w.config.shapeType === 'ellipse'" :cx="w.w/2" :cy="w.h/2" :rx="w.w/2 - w.config.strokeWidth/2" :ry="w.h/2 - w.config.strokeWidth/2" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" :stroke-dasharray="w.config.strokeStyle === 'dashed' ? '10,5' : (w.config.strokeStyle === 'dotted' ? '2,5' : 'none')" />
                        <circle v-if="w.config.shapeType === 'circle'" :cx="w.w/2" :cy="w.h/2" :r="Math.min(w.w, w.h)/2 - w.config.strokeWidth/2" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" :stroke-dasharray="w.config.strokeStyle === 'dashed' ? '10,5' : (w.config.strokeStyle === 'dotted' ? '2,5' : 'none')" />
                        <polygon v-if="w.config.shapeType === 'triangle'" :points="`${w.w/2},${w.config.strokeWidth} ${w.config.strokeWidth},${w.h-w.config.strokeWidth} ${w.w-w.config.strokeWidth},${w.h-w.config.strokeWidth}`" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" :stroke-dasharray="w.config.strokeStyle === 'dashed' ? '10,5' : (w.config.strokeStyle === 'dotted' ? '2,5' : 'none')" />
                        <polygon v-if="w.config.shapeType === 'star'" points="100,10 40,198 190,78 10,78 160,198" :transform="`scale(${w.w/200}, ${w.h/208})`" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth * (200/w.w)" :stroke-dasharray="w.config.strokeStyle === 'dashed' ? '10,5' : (w.config.strokeStyle === 'dotted' ? '2,5' : 'none')" />
                    </svg>
                </div>

                <div class="absolute bottom-0 right-0 w-5 h-5 cursor-nwse-resize flex items-end justify-end p-1 no-drag" @mousedown.stop="startResizing($event, w)">
                    <div class="w-2 h-2 bg-primary-500 rounded-sm opacity-0 group-hover/widget:opacity-100"></div>
                </div>
            </div>
          </template>

          <!-- MODE GRID -->
          <draggable v-else-if="pages[currentPageIdx].layoutMode === 'grid'"
                     v-model="pages[currentPageIdx].widgets" item-key="id"
                     class="grid grid-cols-12 auto-rows-min h-full gap-4 p-4"
                     handle=".grid-drag-handle">
            <template #item="{ element: w, index: idx }">
              <div :style="getGridWidgetStyle(w)"
                   :class="['group/widget relative flex flex-col', selectedWidgetIdx === idx ? 'ring-2 ring-primary-500' : '']"
                   @click.stop="selectedWidgetIdx = idx">

                <div class="absolute -top-11 left-0 flex gap-1 opacity-0 group-hover/widget:opacity-100 transition-all no-drag z-[60] bg-slate-900/80 backdrop-blur-sm border border-white/10 rounded-lg shadow-2xl">
                <button :class="['p-2 hover:text-primary-400 cursor-move grid-drag-handle', currentTheme === 'dark' ? '' : 'text-slate-700']" v-tooltip.bottom="'Déplacer'">
                    <i class="pi pi-arrows-alt text-[10px]"></i>
                </button>
                <button @click.stop="w.isLocked = !w.isLocked" class="p-2 hover:text-orange-400" v-tooltip.bottom="'Verrouiller'">
                    <i :class="w.isLocked ? 'pi pi-lock text-orange-400' : 'pi pi-lock-open'" class="text-[10px]"></i>
                </button>
                <button @click.stop="cloneWidget(idx)" class="p-2 hover:text-green-400" v-tooltip.bottom="'Dupliquer'">
                    <i class="pi pi-copy text-[10px]"></i>
                </button>
                <button @click.stop="deleteWidget(idx)" class="p-2 hover:text-red-400" v-tooltip.bottom="'Supprimer'">
                    <i class="pi pi-trash text-[10px]"></i>
                </button>
            </div>
            <div v-if="w.type === 'text'" class="w-full h-full no-drag flex-grow">
                <div contenteditable="true" @blur="w.content = $event.target.innerText; saveToHistory();"
                     class="w-full h-full outline-none leading-snug"
                     :style="{
                        fontFamily: w.config.font,
                        fontSize: w.config.size+'px',
                        color: w.config.color,
                        textAlign: w.config.align,
                        fontWeight: w.config.weight,
                        fontStyle: w.config.italic ? 'italic' : 'normal',
                        textDecoration: w.config.underline ? 'underline' : 'none',
                        textTransform: w.config.uppercase ? 'uppercase' : 'none'
                     }">
                  {{ w.content }}
                </div>
            </div>

            <div v-if="w.type === 'table'" class="w-full h-full overflow-hidden no-drag bg-white rounded-lg shadow-inner flex-grow">
                <table class="w-full h-full border-collapse">
                    <thead :style="{ background: w.config.headerBg, color: w.config.headerColor }">
                        <tr><th v-for="col in w.data.columns" :key="col" :style="{ padding: w.config.cellPadding+'px' }" class="text-left uppercase text-[9px] font-black">{{ col }}</th></tr>
                    </thead>
                    <tbody :style="{ fontSize: w.config.fontSize+'px' }">
                        <tr v-for="(row, ri) in w.data.rows" :key="ri" :class="w.config.striped && ri%2===0 ? 'bg-slate-50' : 'bg-white'">
                            <td v-for="col in w.data.columns" :key="col" :style="{ padding: w.config.cellPadding+'px' }" class="border-b border-slate-100 text-slate-800">
                                {{ row[col] }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="w.type === 'chart'" class="w-full h-full p-2 pointer-events-none flex-grow">
                <component :is="getChartType(w.chartType)" :data="w.data" :options="{ responsive: true, maintainAspectRatio: false }" />
            </div>

            <div v-if="w.type === 'kpi'" class="w-full h-full flex flex-col justify-center px-4 no-drag flex-grow">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ w.config.label }}</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-black text-slate-900 tracking-tighter" :style="{ color: w.config.color }">{{ w.config.prefix }}{{ w.config.value }}</span>
                    <span class="text-[10px] font-bold text-emerald-500">{{ w.config.trend }}</span>
                </div>
            </div>

            <div v-if="w.type === 'image'" class="w-full h-full no-drag flex-grow">
                <img v-if="w.imageUrl" :src="w.imageUrl" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center flex-col text-slate-400">
                    <i class="pi pi-image text-4xl"></i>
                    <span class="text-xs mt-2">Utilisez l'inspecteur pour choisir</span>
                </div>
                <input type="file" accept="image/*" @change="handleImageUpload($event, w)" class="hidden" :ref="el => imgUploadRefs[w.id] = el" />
            </div>

            <div v-if="w.type === 'shape'" class="w-full h-full flex items-center justify-center no-drag flex-grow">
                <svg width="100%" height="100%" :viewBox="`0 0 ${w.w} ${w.h}`" preserveAspectRatio="none">
                    <line v-if="w.config.shapeType === 'line'" x1="0" :y1="w.config.strokeWidth / 2" :x2="w.w" :y2="w.config.strokeWidth / 2" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" />
                    <rect v-if="w.config.shapeType === 'rectangle'" x="0" y="0" width="100%" height="100%" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" :stroke-dasharray="w.config.strokeStyle === 'dashed' ? '10,5' : (w.config.strokeStyle === 'dotted' ? '2,5' : 'none')" />
                    <ellipse v-if="w.config.shapeType === 'ellipse'" :cx="w.w/2" :cy="w.h/2" :rx="w.w/2 - w.config.strokeWidth/2" :ry="w.h/2 - w.config.strokeWidth/2" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" />
                    <circle v-if="w.config.shapeType === 'circle'" :cx="w.w/2" :cy="w.h/2" :r="Math.min(w.w, w.h)/2 - w.config.strokeWidth/2" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" />
                    <polygon v-if="w.config.shapeType === 'triangle'" :points="`${w.w/2},${w.config.strokeWidth} ${w.config.strokeWidth},${w.h-w.config.strokeWidth} ${w.w-w.config.strokeWidth},${w.h-w.config.strokeWidth}`" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth" />
                    <polygon v-if="w.config.shapeType === 'star'" points="100,10 40,198 190,78 10,78 160,198" :transform="`scale(${w.w/200}, ${w.h/208})`" :fill="w.style.backgroundColor" :stroke="w.config.strokeColor" :stroke-width="w.config.strokeWidth * (200/w.w)" />
                </svg>
            </div>

              </div>
            </template>
          </draggable>
        </div>
      </main>

      <aside :class="['w-[380px] flex flex-col shrink-0 overflow-y-auto custom-scrollbar', currentTheme === 'dark' ? 'border-l border-white/5 bg-black/40' : 'border-l border-slate-200 bg-slate-50']">
        <div v-if="currentWidget" class="p-8 space-y-10 animate-in fade-in slide-in-from-right duration-300">

            <header :class="['flex justify-between items-center pb-6', currentTheme === 'dark' ? 'border-b border-white/10' : 'border-b border-slate-200']">
                <h3 :class="['text-[10px] font-black uppercase tracking-[0.3em] italic', currentTheme === 'dark' ? 'text-primary-400' : 'text-primary-600']">Inspecteur Omni</h3>
                <div class="flex gap-2">
                    <button @click="moveZIndex('up')" class="p-2 bg-white/5 rounded-lg hover:bg-white/10"><i class="pi pi-angle-up"></i></button>
                    <button @click="moveZIndex('down')" class="p-2 bg-white/5 rounded-lg hover:bg-white/10"><i class="pi pi-angle-down"></i></button>
                </div>
            </header>

            <!-- SECTION APPARENCE (COMMUNE) -->
            <section class="space-y-6">
                <h4 :class="['text-[9px] font-black uppercase tracking-widest italic', currentTheme === 'dark' ? 'text-slate-500' : 'text-slate-400']">Apparence</h4>
                <div :class="['p-4 rounded-xl space-y-4', currentTheme === 'dark' ? 'bg-white/5' : 'bg-white border border-slate-200']">
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Fond</label>
                        <div :class="['flex items-center gap-2 p-1 rounded-lg', currentTheme === 'dark' ? 'bg-black/20' : 'bg-slate-100']">
                            <input type="color" v-model="currentWidget.style.backgroundColor" class="w-8 h-6 bg-transparent cursor-pointer border-none">
                            <InputText :value="currentWidget.style.backgroundColor" @update:modelValue="v => currentWidget.style.backgroundColor = v" :class="['p-inputtext-sm flex-grow font-mono uppercase !bg-transparent border-none shadow-none p-1', currentTheme === 'dark' ? '' : 'text-slate-700']" />
                        </div>
                    </div>
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Bordure</label>
                        <div class="grid grid-cols-3 gap-2">
                            <InputNumber v-model="currentWidget.style.borderWidth" :min="0" :max="20" class="p-inputtext-sm" :disabled="currentWidget.style.borderStyle === 'none'" />
                            <Dropdown v-model="currentWidget.style.borderStyle" :options="BORDER_STYLES" optionLabel="label" optionValue="value" :class="['p-inputtext-sm col-span-2', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                        </div>
                        <div :class="['flex items-center gap-2 p-1 rounded-lg mt-2', currentTheme === 'dark' ? 'bg-black/20' : 'bg-slate-100']">
                            <input type="color" v-model="currentWidget.style.borderColor" class="w-8 h-6 bg-transparent cursor-pointer border-none">
                            <InputText :value="currentWidget.style.borderColor" @update:modelValue="v => currentWidget.style.borderColor = v" class="p-inputtext-sm flex-grow font-mono uppercase !bg-transparent border-none shadow-none p-1" />
                        </div>
                    </div>
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Rayon ({{ currentWidget.style.borderRadius }}px)</label>
                        <Slider v-model="currentWidget.style.borderRadius" :min="0" :max="50" />
                    </div>
                </div>
            </section>

            <!-- SECTION GRID LAYOUT -->
            <section v-if="pages[currentPageIdx].layoutMode === 'grid'" class="space-y-6">
                <h4 :class="['text-[9px] font-black uppercase tracking-widest italic', currentTheme === 'dark' ? 'text-slate-500' : 'text-slate-400']">Disposition Grille</h4>
                <div :class="['p-4 rounded-xl space-y-4', currentTheme === 'dark' ? 'bg-white/5' : 'bg-white border border-slate-200']">
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Colonnes ({{ currentWidget.colSpan }}/12)</label>
                        <Slider v-model="currentWidget.colSpan" :min="1" :max="12" />
                    </div>
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Lignes ({{ currentWidget.rowSpan }})</label>
                        <Slider v-model="currentWidget.rowSpan" :min="1" :max="12" />
                    </div>
                </div>
            </section>

            <section v-if="currentWidget.type === 'table'" class="space-y-4">
                <Button label="MODIFIER STRUCTURE / CSV" icon="pi pi-table" @click="openTableEditor" class="w-full p-button-outlined p-button-sm" />
                <div class="grid grid-cols-2 gap-4">
                    <div class="field">
                        <label class="text-[8px] uppercase opacity-50 block mb-1">Header Fond</label>
                        <input type="color" v-model="currentWidget.config.headerBg" class="w-full h-8 bg-transparent cursor-pointer" />
                    </div>
                    <div class="field">
                        <label class="text-[8px] uppercase opacity-50 block mb-1">Texte Fond</label>
                        <input type="color" v-model="currentWidget.config.headerColor" class="w-full h-8 bg-transparent cursor-pointer" />
                    </div>
                </div>
            </section>

            <section v-if="currentWidget.type === 'text'" class="space-y-6">
                <h4 :class="['text-[9px] font-black uppercase tracking-widest italic', currentTheme === 'dark' ? 'text-slate-500' : 'text-slate-400']">Typographie</h4>
                <div :class="['p-4 rounded-xl space-y-4', currentTheme === 'dark' ? 'bg-white/5' : 'bg-white border border-slate-200']">
                    <Dropdown v-model="currentWidget.config.font" :options="FONTS" :class="['w-full text-xs', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                    <div class="flex items-center gap-4">
                        <Slider v-model="currentWidget.config.size" :min="8" :max="120" class="flex-grow" />
                        <span :class="['text-[10px] font-mono', currentTheme === 'dark' ? 'text-primary-400' : 'text-primary-600']">{{ currentWidget.config.size }}px</span>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <button @click="currentWidget.config.weight = currentWidget.config.weight === '900' ? '500' : '900'" :class="[{'bg-primary-600 text-white': currentWidget.config.weight === '900'}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors"><i class="pi pi-bold"></i></button>
                        <button @click="currentWidget.config.italic = !currentWidget.config.italic" :class="[{'bg-primary-600 text-white': currentWidget.config.italic}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors"><i class="pi pi-italic"></i></button>
                        <button @click="currentWidget.config.underline = !currentWidget.config.underline" :class="[{'bg-primary-600 text-white': currentWidget.config.underline}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors">U</button>
                        <button @click="currentWidget.config.uppercase = !currentWidget.config.uppercase" :class="[{'bg-primary-600 text-white': currentWidget.config.uppercase}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors">Aa</button>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <button @click="currentWidget.config.align = 'left'" :class="[{'bg-primary-600 text-white': currentWidget.config.align === 'left'}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors"><i class="pi pi-align-left"></i></button>
                        <button @click="currentWidget.config.align = 'center'" :class="[{'bg-primary-600 text-white': currentWidget.config.align === 'center'}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors"><i class="pi pi-align-center"></i></button>
                        <button @click="currentWidget.config.align = 'right'" :class="[{'bg-primary-600 text-white': currentWidget.config.align === 'right'}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors"><i class="pi pi-align-right"></i></button>
                        <button @click="currentWidget.config.align = 'justify'" :class="[{'bg-primary-600 text-white': currentWidget.config.align === 'justify'}, currentTheme === 'dark' ? 'bg-white/5 hover:bg-white/10' : 'bg-slate-200 hover:bg-slate-300']" class="p-2 rounded-lg transition-colors"><i class="pi pi-align-justify"></i></button>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-[9px] font-black uppercase opacity-40">Couleur</label>
                        <div :class="['flex-grow flex items-center gap-2 p-1 rounded-lg', currentTheme === 'dark' ? 'bg-black/20' : 'bg-slate-100']">
                            <input type="color" v-model="currentWidget.config.color" class="w-8 h-6 bg-transparent cursor-pointer border-none">
                            <InputText :value="currentWidget.config.color" @update:modelValue="v => currentWidget.config.color = v" class="p-inputtext-sm flex-grow font-mono uppercase !bg-transparent border-none shadow-none p-1" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION SHAPE -->
            <section v-if="currentWidget.type === 'shape'" class="space-y-6">
                <h4 class="text-[9px] font-black uppercase tracking-widest text-slate-500 italic">Style de la Forme</h4>
                <div class="bg-white/5 p-4 rounded-xl space-y-4">
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Type de Forme</label>
                        <Dropdown v-model="currentWidget.config.shapeType" :options="['line', 'rectangle', 'ellipse', 'circle', 'triangle', 'star']" class="w-full text-xs" />
                    </div>
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Couleur du Trait</label>
                        <div class="flex items-center gap-2 bg-black/20 p-1 rounded-lg">
                            <input type="color" v-model="currentWidget.config.strokeColor" class="w-8 h-6 bg-transparent cursor-pointer border-none">
                            <InputText :value="currentWidget.config.strokeColor" @update:modelValue="v => currentWidget.config.strokeColor = v" class="p-inputtext-sm flex-grow font-mono uppercase !bg-transparent border-none shadow-none p-1" />
                        </div>
                    </div>
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Épaisseur du Trait ({{ currentWidget.config.strokeWidth }}px)</label>
                        <Slider v-model="currentWidget.config.strokeWidth" :min="1" :max="50" />
                    </div>
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Style du Trait</label>
                        <Dropdown v-model="currentWidget.config.strokeStyle" :options="BORDER_STYLES" optionLabel="label" optionValue="value" class="w-full text-xs" />
                    </div>
                </div>
            </section>

            <section v-if="currentWidget.type === 'kpi'" class="space-y-6">
                <h4 class="text-[9px] font-black uppercase tracking-widest text-slate-500 italic">Configuration KPI</h4>
                <div class="bg-white/5 p-4 rounded-xl space-y-4">
                    <!-- Champs de configuration de base du KPI -->
                    <div class="field">
                        <label class="text-[9px] font-black uppercase opacity-40">Préfixe</label>
                        <InputText v-model="currentWidget.config.prefix" class="w-full text-xs" />
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="text-[9px] font-black uppercase opacity-40">Couleur Valeur</label>
                        <div class="flex-grow flex items-center gap-2 bg-black/20 p-1 rounded-lg">
                            <input type="color" v-model="currentWidget.config.color" class="w-8 h-6 bg-transparent cursor-pointer border-none">
                            <InputText :value="currentWidget.config.color" @update:modelValue="v => currentWidget.config.color = v" class="p-inputtext-sm flex-grow font-mono uppercase !bg-transparent border-none shadow-none p-1" :style="{color: currentWidget.config.color}" />
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="currentWidget.type === 'image'" class="space-y-4">
                <Button label="CHANGER L'IMAGE" icon="pi pi-upload" @click="triggerImageUpload(currentWidget.id)" class="w-full p-button-outlined p-button-sm" />
            </section>

            <section v-if="['chart', 'kpi'].includes(currentWidget.type)" class="space-y-6">
                <h4 :class="['text-[9px] font-black uppercase tracking-widest italic', currentTheme === 'dark' ? 'text-primary-400' : 'text-primary-600']">Source de Données</h4>
                <div class="bg-primary-900/20 border border-primary-500/30 p-5 rounded-2xl space-y-4">
                    <div v-if="currentWidget.type === 'kpi'" class="space-y-3">
                        <Dropdown v-model="currentWidget.dataSource.model" :options="laravelModels" optionLabel="name" optionValue="name" placeholder="Modèle Laravel" :class="['w-full text-xs', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                        <Dropdown v-if="currentWidget.dataSource.model" v-model="currentWidget.dataSource.column" :options="laravelModels.find(m => m.name === currentWidget.dataSource.model)?.columns || []" placeholder="Champ" :class="['w-full text-xs font-mono', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                        <Dropdown v-model="currentWidget.dataSource.method" :options="['COUNT', 'SUM', 'AVG', 'MAX', 'MIN']" placeholder="Méthode SQL" :class="['w-full text-xs', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                        <Dropdown v-model="currentWidget.config.timeScale" :options="['minutes', 'hours', 'days', 'weeks', 'months']" placeholder="Intervalle de données" :class="['w-full text-xs', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                    </div>
                    <Button label="SYNC LARAVEL" icon="pi pi-refresh" @click="syncWithLaravel(currentWidget)" :loading="currentWidget.isSyncing" class="w-full p-button-primary p-button-sm font-black" />
                </div>

                <!-- Configuration spécifique aux graphiques -->
                <div v-if="currentWidget.type === 'chart'" class="grid grid-cols-2 gap-2">
                    <button v-for="ct in CHART_TYPES" :key="ct.value" @click="currentWidget.chartType = ct.value"
                            :class="[currentWidget.chartType === ct.value ? 'bg-primary-600 border-primary-500 text-white' : (currentTheme === 'dark' ? 'bg-white/5 border-white/5' : 'bg-white border-slate-200')]"
                            class="flex flex-col items-center p-3 rounded-xl border transition-all">
                        <i :class="ct.icon" class="text-lg"></i>
                        <span class="text-[8px] font-black uppercase mt-2">{{ ct.label }}</span>
                    </button>
                </div>
                <div v-if="currentWidget.type === 'chart'">
                    <h4 :class="['text-[9px] font-black uppercase tracking-widest italic mt-6 mb-2', currentTheme === 'dark' ? 'text-slate-500' : 'text-slate-400']">Échelle de Temps (Axe X)</h4>
                     <Dropdown v-model="currentWidget.config.timeScale" :options="['minutes', 'hours', 'days', 'weeks', 'months']" placeholder="Axe X" :class="['w-full text-xs', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                </div>
            </section>

            <section v-if="currentWidget.type === 'chart'" class="space-y-4">
                <h4 :class="['text-[9px] font-black uppercase tracking-widest italic', currentTheme === 'dark' ? 'text-primary-400' : 'text-primary-600']">Séries de Données</h4>
                <div v-for="(source, i) in currentWidget.dataSources" :key="i" :class="['p-4 rounded-xl space-y-3 border-l-4', currentTheme === 'dark' ? 'bg-white/5' : 'bg-white border border-slate-200']" :style="{borderColor: source.color}">
                    <Dropdown v-model="source.model" :options="laravelModels" optionLabel="name" optionValue="name" placeholder="Modèle Laravel" :class="['w-full text-xs', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                    <Dropdown v-if="source.model" v-model="source.column" :options="laravelModels.find(m => m.name === source.model)?.columns || []" placeholder="Champ" :class="['w-full text-xs font-mono mt-2', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                    <input type="color" v-model="source.color" class="w-full h-8 bg-transparent cursor-pointer" />
                    <Button icon="pi pi-times" @click="currentWidget.dataSources.splice(i, 1)" class="p-button-danger p-button-text p-button-sm" />
                </div>
                <Button label="Ajouter une série" icon="pi pi-plus" @click="currentWidget.dataSources.push({ model: null, column: null, formula: null, color: '#'+(Math.random()*0xFFFFFF<<0).toString(16) })" class="p-button-outlined p-button-sm w-full" />
            </section>
        </div>

        <div v-else class="p-8 space-y-8">
            <TabView>
                <TabPanel header="Document">
                    <div class="p-4 space-y-6">
                        <h3 :class="['text-[10px] font-black uppercase tracking-[0.4em] italic', currentTheme === 'dark' ? 'text-primary-400' : 'text-primary-600']">Format & Fond</h3>
                        <div :class="['p-6 rounded-2xl space-y-6', currentTheme === 'dark' ? 'bg-white/5' : 'bg-white border border-slate-200']">
                            <div class="field">
                                <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Format</label>
                                <Dropdown v-model="pages[currentPageIdx].format" :options="PAGE_FORMATS" optionLabel="name" :class="['w-full text-xs', currentTheme === 'dark' ? '' : 'light-dropdown']" />
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Orientation</label>
                                <SelectButton v-model="pages[currentPageIdx].orientation" :options="[{label: 'Portrait', value: 'portrait'}, {label: 'Paysage', value: 'landscape'}]" optionLabel="label" optionValue="value" class="w-full" />
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Couleur de Fond</label>
                                <input type="color" v-model="pages[currentPageIdx].background" class="w-full h-10 bg-transparent cursor-pointer" />
                            </div>
                            <div class="field">
                                <label class="text-[9px] font-black uppercase opacity-40 mb-2 block">Mode de Disposition</label>
                                <SelectButton v-model="pages[currentPageIdx].layoutMode" :options="[{label: 'Libre', value: 'absolute'}, {label: 'Grille', value: 'grid'}]" optionLabel="label" optionValue="value" class="w-full" />
                            </div>
                        </div>
                    </div>
                </TabPanel>
                <TabPanel header="Pages">
                    <div class="p-4 space-y-4">
                        <Button label="Ajouter une page" icon="pi pi-plus" @click="addPage" class="w-full p-button-outlined p-button-sm" />
                        <div class="space-y-2 max-h-[60vh] overflow-y-auto custom-scrollbar pr-2">
                            <div v-for="(page, index) in pages" :key="page.id"
                                 class="flex items-center gap-3 p-2 rounded-lg border-2"
                                 :class="currentPageIdx === index ? 'bg-primary-500/20 border-primary-500' : (currentTheme === 'dark' ? 'bg-white/5 border-transparent hover:border-white/20' : 'bg-white border-transparent hover:border-slate-300')"
                                 draggable="true"
                                 @dragstart="pageDragIndex = index"
                                 @dragover.prevent
                                 @drop="handlePageDrop($event, index)"
                                 @click="currentPageIdx = index">
                                <div class="w-16 h-20 bg-white flex-shrink-0 rounded-sm shadow-md overflow-hidden">
                                    <!-- Mini preview could go here -->
                                </div>
                                <div class="flex-grow">
                                    <InputText v-model="page.name" @click.stop @blur="updatePageName(page)" :class="['p-inputtext-sm w-full mt-1', currentTheme === 'dark' ? 'bg-black/20 border-white/10' : 'bg-white border-slate-300']" />
                                </div>
                                <div class="flex flex-col">
                                    <Button icon="pi pi-copy" @click.stop="duplicatePage(index)" v-tooltip.left="'Dupliquer'" class="p-button-secondary p-button-text p-button-sm" />
                                    <Button icon="pi pi-trash" @click.stop="deletePage(index)" v-tooltip.left="'Supprimer'" class="p-button-danger p-button-text p-button-sm" />
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>
            </TabView>
        </div>
      </aside>
    </div>

    <Dialog v-model:visible="tableModal" header="Éditeur de Données Quantum" :style="{width: '80vw'}" modal class="p-fluid">
        <div class="grid grid-cols-12 gap-8 p-4">
            <div class="col-span-3 border-r border-white/10 pr-6 space-y-4">
                <div class="flex justify-between items-center">
                    <h5 class="text-primary-400 font-bold uppercase text-[10px]">Colonnes</h5>
                    <Button icon="pi pi-plus" @click="addTableColumn" class="p-button-rounded p-button-text p-button-sm" />
                </div>
                <div v-for="(col, idx) in tableConfig.tempColumns" :key="idx" class="flex gap-2">
                    <InputText v-model="tableConfig.tempColumns[idx]" class="p-inputtext-sm" />
                    <Button icon="pi pi-times" @click="deleteTableColumn(col)" class="p-button-danger p-button-text p-button-sm" />
                </div>
                <div class="pt-6 border-t border-white/10">
                    <Button label="IMPORTER CSV" icon="pi pi-upload" class="p-button-help p-button-sm" @click="$refs.csvInput.click()" />
                    <input type="file" ref="csvInput" class="hidden" accept=".csv" @change="handleCSVImport" />
                </div>
            </div>
            <div class="col-span-9">
                <div class="flex justify-between items-center mb-4">
                    <h5 class="text-slate-400 font-bold uppercase text-[10px]">Prévisualisation des données</h5>
                    <Button label="Ajouter une ligne" icon="pi pi-plus-circle" @click="addTableRow" class="p-button-sm p-button-outlined w-auto" />
                </div>
                <div class="max-h-[500px] overflow-auto border border-white/5 rounded-xl">
                    <table class="w-full text-xs">
                        <thead class="bg-white/5 sticky top-0">
                            <tr><th v-for="col in tableConfig.tempColumns" :key="col" class="p-3 text-left font-black uppercase opacity-40">{{ col }}</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, rIdx) in tableConfig.tempRows" :key="rIdx" class="border-t border-white/5">
                                <td v-for="col in tableConfig.tempColumns" :key="col" class="p-1">
                                    <InputText v-model="row[col]" class="p-inputtext-sm border-none bg-transparent" />
                                </td>
                                <td class="p-1 w-12 text-center">
                                    <Button icon="pi pi-trash" @click="tableConfig.tempRows.splice(rIdx, 1)" class="p-button-danger p-button-text p-button-sm" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <template #footer>
            <Button label="Annuler" icon="pi pi-times" @click="tableModal = false" class="p-button-text" />
            <Button label="Enregistrer les modifications" icon="pi pi-check" @click="saveTableStructure" class="p-button-primary" />
        </template>
    </Dialog>

    <!-- NOUVEAU : Dialogue pour sauvegarder un template -->
    <Dialog v-model:visible="saveTemplateDialog" header="Sauvegarder comme Modèle" :style="{width: '30rem'}" modal>
        <div class="field">
            <label for="templateName" class="font-semibold">Nom du modèle</label>
            <InputText id="templateName" v-model="newTemplateName" class="w-full mt-2" @keyup.enter="saveCurrentReportAsTemplate" />
        </div>
        <template #footer>
            <Button label="Annuler" icon="pi pi-times" @click="saveTemplateDialog = false" class="p-button-text" />
            <Button label="Sauvegarder" icon="pi pi-check" @click="saveCurrentReportAsTemplate" />
        </template>
    </Dialog>


    <Toast position="bottom-right" />
    <ConfirmDialog />
  </div>
</template>
<style scoped>
/* =========================================================================================
   1. VARIABLES & CORE ARCHITECTURE
   ========================================================================================= */

/* QUANTUM STUDIO OS - DESIGN SYSTEM v6.5
  Core Stylesheet: Layout, Components & Animations
*/
/* QUANTUM ARCHITECT UI - DESIGN SYSTEM v7.2
  Architectural Stylesheet for High-End Document Engine
*/

/* 1. RESET & VARIABLES GLOBALES */
:root {
  --q-bg: #020203;
  --q-surface: #08080a;
  --q-primary: #6366f1;
  --q-primary-glow: rgba(99, 102, 241, 0.4);
  --q-border: rgba(255, 255, 255, 0.05);
  --q-text-muted: #64748b;
  --q-canvas-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.7);
  --q-transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

* {
  box-sizing: border-box;
  outline-color: var(--q-primary);
}

body {
  margin: 0;
  padding: 0;
  background-color: var(--q-bg);
  color: #e2e8f0;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  overflow: hidden;
}

/* 2. GESTION DES SCROLLBARS (Style Cyber-Minimaliste) */
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: var(--q-primary);
}

/* 3. STRUCTURE DU CANEVAS (L60-L150) */
#studio-capture-area {
  user-select: none;
  background-color: #ffffff;
  position: relative;
  box-shadow: var(--q-canvas-shadow);
  transform-origin: top center;
  transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.3s;
}

/* Effet de grain sur le papier */
#studio-capture-area::before {
  content: "";
  position: absolute;
  inset: 0;
  opacity: 0.02;
  pointer-events: none;
  background-image: url("https://www.transparenttextures.com/patterns/pinstripe-light.png");
}

/* 4. ÉLÉMENTS MOBILES & WIDGETS (L110-L200) */
.group\/widget {
  transition: box-shadow 0.3s ease, border-color 0.3s ease;
}

.grid .group\/widget {
    display: flex; flex-direction: column;
}
.group\/widget:hover {
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
}

/* Poignée de redimensionnement */
.cursor-nwse-resize {
  transition: opacity 0.2s;
  background: linear-gradient(135deg, transparent 50%, var(--q-primary) 50%);
  border-bottom-right-radius: 12px;
}

/* 5. OVERRIDES PRIME VUE (L160-L280) */

/* TabView Sidebar */
.quantum-sidebar-tabs .p-tabview-nav {
  background: transparent !important;
  border: none !important;
  display: flex !important;
}

.quantum-sidebar-tabs .p-tabview-nav li {
  flex: 1;
}

.quantum-sidebar-tabs .p-tabview-nav li .p-tabview-nav-link {
  background: transparent !important;
  border: none !important;
  padding: 1.25rem 0.5rem !important;
  display: flex;
  flex-direction: column;
  align-items: center;
  color: var(--q-text-muted) !important;
  transition: var(--q-transition);
  border-bottom: 2px solid transparent !important;
}

.quantum-sidebar-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link {
  color: white !important;
  border-bottom-color: var(--q-primary) !important;
  background: rgba(99, 102, 241, 0.05) !important;
}

/* Accordion Inspecteur */
.p-accordion .p-accordion-header .p-accordion-header-link {
  background: #0d0d11 !important;
  border: 1px solid var(--q-border) !important;
  color: #94a3b8 !important;
  font-size: 9px !important;
  font-weight: 900 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.15em !important;
  padding: 1.25rem !important;
  border-radius: 12px !important;
  margin-top: 0.5rem;
}

.p-accordion .p-accordion-content {
  background: transparent !important;
  border: none !important;
  padding: 1.5rem 0.5rem !important;
}

/* Inputs & Numbers */
.quantum-field-number .p-inputnumber-input {
  background: rgba(0, 0, 0, 0.4) !important;
  border: 1px solid var(--q-border) !important;
  color: #fff !important;
  font-size: 11px !important;
  padding: 0.75rem !important;
  border-radius: 10px !important;
  width: 100%;
}

/* Styles pour le thème clair */
.light-dropdown .p-dropdown-label,
.light-dropdown .p-dropdown-trigger {
    color: #334155; /* text-slate-700 */
}
.light-dropdown {
    background-color: #ffffff;
}
.light-dropdown:not(.p-disabled):hover {
    border-color: #94a3b8; /* slate-400 */
}

/* 6. SYSTÈME DE GRILLE & GUIDES (L290-L340) */
.grid-active {
  background-image: radial-gradient(var(--q-primary) 1px, transparent 0);
  background-size: 20px 20px;
}

/* 7. ANIMATIONS DE L'INTERFACE (L310+) */
@keyframes quantum-fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-in {
  animation: quantum-fade-in 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Mode Chargement */
.system-loading {
  filter: grayscale(0.5) blur(4px);
  pointer-events: none;
  cursor: wait;
}

/* Bouton Spécial primary */
.p-button-primary {
  background: var(--q-primary) !important;
  border: none !important;
  transition: var(--q-transition) !important;
}

.p-button-primary:hover {
  background: #4f46e5 !important;
  transform: translateY(-2px);
  box-shadow: 0 10px 20px var(--q-primary-glow) !important;
}

/* 8. ÉTAT ÉDITION (ContentEditable) */
[contenteditable="true"]:focus {
  outline: 2px solid var(--q-primary);
  background: rgba(99, 102, 241, 0.05);
  border-radius: 4px;
}

/* 9. EXPORT PDF FIXES */
@media print {
  header, aside, footer, button {
    display: none !important;
  }
  body, .quantum-os-root {
    background: white !important;
  }
  #studio-capture-area {
    transform: none !important;
    box-shadow: none !important;
    margin: 0 !important;
  }
}

/* 10. EFFETS DE CALQUES (Layers) */
.layer-item {
  position: relative;
  overflow: hidden;
}

.layer-item::after {
  content: "";
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 2px;
  background: var(--q-primary);
  transition: width 0.3s ease;
}

.layer-item:hover::after {
  width: 100%;
}
</style>
