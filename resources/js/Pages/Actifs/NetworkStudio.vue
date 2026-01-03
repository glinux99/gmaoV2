<script setup>
import { ref, computed, reactive, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import Slider from 'primevue/slider';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Badge from 'primevue/badge';
import ColorPicker from 'primevue/colorpicker';
import SelectButton from 'primevue/selectbutton';
import Dropdown from 'primevue/dropdown';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    initialNetwork: Object,
    library: {
 type: Object,
 default: () => ({})
    },
    regions: Array,
    zones: Array,
});

const toast = useToast();

// --- PARAMÈTRES TECHNIQUES ---
const NODE_WIDTH = 220;
const GRID_SIZE = 20;
const CANVAS_SIZE = 10000;

const NODE_HEIGHT = 130;

const wireTypes = [
    { label: 'Phase L1', value: '#ef4444', dash: '0' },
    { label: 'Phase L2', value: '#111827', dash: '0' },
    { label: 'Phase L3', value: '#94a3b8', dash: '0' },
    { label: 'Neutre', value: '#3b82f6', dash: '0' },
    { label: 'Terre', value: '#22c55e', dash: '12,6' },
    { label: 'Commande', value: '#a855f7', dash: '5,5' }
];

// --- ÉTAT DU PROJET ---

const networkName = ref("PROJET_ELECTRIQUE_BETA_V2");
const equipments = ref([]);
const connections = ref([]);
const labels = ref([]); // Nouveau : gestion des étiquettes texte

// --- UI / NAVIGATION ---
const zoomLevel = ref(0.85);
const selectedIds = ref([]);
const selectedConnectionId = ref(null);
const selectedLabelIds = ref([]); // Supporte la multi-sélection de labels
const isSidebarOpen = ref(true);
const searchQuery = ref("");

const mainContainer = ref(null);
const scrollLeft = ref(0);
const scrollTop = ref(0);
const isMinimapVisible = ref(true);
// Modals
const isAnalyseVisible = ref(true);

const minimapPosition = ref({ x: window.innerWidth - 240 - 24 - 256 - 12, y: 24 });
const minimapPanel = ref(null);
const analysisPosition = ref({ x: window.innerWidth - 240 - 24 - 256 - 12, y: 24 });
const analysisPanel = ref(null);


const showAddModal = ref(false);
const showEditModal = ref(false);

const marquee = reactive({
    active: false,
    x1: 0, y1: 0,
    x2: 0, y2: 0
});

const marqueeRect = computed(() => getMarqueeRect(marquee));

const newNodeData = reactive({
    tag: '',
    designation: '',
    type: '',
    isRoot: false,
    placementType: null, // 'region' or 'zone'
    region_id: null,
    status: 'en service', // Statut par défaut
    zone_id: null,
});
const characteristicsData = ref({}); // Structure: { charId: { value: '...', date: '...' } }

const editingNodeData = reactive({
    id: null,
    tag: '',
    designation: '',
    isRoot: false,
    region_id: null,
    zone_id: null,
    status: 'en service',
    active: true,
});


// --- Gestion des Labels (Création/Édition) ---
const showLabelModal = ref(false);
const editingLabelId = ref(null); // null pour création, ID pour édition
const labelFormData = reactive({ text: '', fontSize: 14, color: '#94a3b8', bold: false, rotation: 0 });

const openNewLabelModal = () => {
    editingLabelId.value = null;
    labelFormData.text = 'NOUVEAU LABEL';
    labelFormData.fontSize = 14;
    labelFormData.color = '#94a3b8';
    labelFormData.bold = false;
    labelFormData.rotation = 0;
    showLabelModal.value = true;
};

const openEditLabelModal = (label) => {
    editingLabelId.value = label.id;
    labelFormData.text = label.text;
    labelFormData.fontSize = label.fontSize;
    labelFormData.color = label.color;
    labelFormData.bold = label.bold;
    labelFormData.rotation = label.rotation || 0;
    showLabelModal.value = true;
};

// --- GESTION DU CÂBLAGE ---
const linking = reactive({
    active: false,
    fromId: null,
    fromSide: null,
    mouseX: 0,
    mouseY: 0,
    currentColor: '#3b82f6',
    currentDash: '0'
});

// --- GESTION DU MINI-PLAN (MINIMAP) ---
const MINIMAP_WIDTH = 240; // Largeur du mini-plan en pixels

const minimapScale = computed(() => MINIMAP_WIDTH / CANVAS_SIZE);

const viewportStyle = computed(() => {
    if (!mainContainer.value) return {};
    const container = mainContainer.value;
    return {
        width: `${(container.clientWidth / zoomLevel.value) * minimapScale.value}px`,
        height: `${(container.clientHeight / zoomLevel.value) * minimapScale.value}px`,
        left: `${(scrollLeft.value / zoomLevel.value) * minimapScale.value}px`,
        top: `${(scrollTop.value / zoomLevel.value) * minimapScale.value}px`,
    };
});

const handleMinimapMouseDown = (e) => {
    const minimapRect = e.currentTarget.getBoundingClientRect();
    const isViewportClick = e.target.id === 'minimap-viewport';

    const updateScroll = (me) => {
        let newLeft = (me.clientX - minimapRect.left) / minimapScale.value;
        let newTop = (me.clientY - minimapRect.top) / minimapScale.value;

        if (isViewportClick) {
            const viewportWidth = (mainContainer.value.clientWidth / zoomLevel.value);
            const viewportHeight = (mainContainer.value.clientHeight / zoomLevel.value);
            newLeft -= viewportWidth / 2;
            newTop -= viewportHeight / 2;
        }

        mainContainer.value.scrollLeft = newLeft * zoomLevel.value;
        mainContainer.value.scrollTop = newTop * zoomLevel.value;
    };

    updateScroll(e); // Move on first click

    const onMouseMove = (me) => updateScroll(me);
    const onMouseUp = () => {
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
    };

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
};

const handleScroll = (e) => {
    scrollLeft.value = e.target.scrollLeft;
    scrollTop.value = e.target.scrollTop;
};

// --- HISTORIQUE (UNDO/REDO) ---
const history = ref([]);
const historyIndex = ref(-1);

const recordState = () => {
    const snapshot = JSON.stringify({
        equipments: equipments.value,
        connections: connections.value,
        labels: labels.value
    });
    if (historyIndex.value < history.value.length - 1) {
        history.value = history.value.slice(0, historyIndex.value + 1);
    }
    history.value.push(snapshot);
    if (history.value.length > 100) history.value.shift();
    historyIndex.value = history.value.length - 1;
};

const undo = () => {
    if (historyIndex.value > 0) {
        historyIndex.value--;
        const data = JSON.parse(history.value[historyIndex.value]);
        equipments.value = data.equipments;
        connections.value = data.connections;
        labels.value = data.labels || [];
    }
};

// --- SIMULATION ÉLECTRIQUE ---
const energizedNodes = computed(() => {
    const liveSet = new Set();
    const traverse = (nodeId) => {
        if (liveSet.has(nodeId)) return;
        const node = equipments.value.find(e => e.id === nodeId);
        if (!node || !node.active) return;

        liveSet.add(nodeId);
        connections.value
            .filter(c => c.fromId === nodeId)
            .forEach(c => traverse(c.toId));
    };

    equipments.value.filter(e => e.isRoot && e.active).forEach(s => traverse(s.id));
    return liveSet;
});

const isWireLive = (wire) => energizedNodes.value.has(wire.fromId);

// --- FONCTIONS GÉOMÉTRIQUES ---
const getPortPos = (node, side) => {
    if (!node) return { x: 0, y: 0 };
    switch(side) {
        case 'N': return { x: node.x + node.w / 2, y: node.y };
        case 'S': return { x: node.x + node.w / 2, y: node.y + node.h };
        case 'E': return { x: node.x + node.w, y: node.y + node.h / 2 };
        case 'W': return { x: node.x, y: node.y + node.h / 2 };
        default: return { x: node.x, y: node.y };
    }
};

const getOrthogonalPath = (wire) => {
    const n1 = equipments.value.find(e => e.id === wire.fromId);
    const n2 = equipments.value.find(e => e.id === wire.toId);
    if (!n1 || !n2) return "";

    const p1 = getPortPos(n1, wire.fromSide);
    const p2 = getPortPos(n2, wire.toSide);

    const dx = Math.abs(p2.x - p1.x);
    const dy = Math.abs(p2.y - p1.y);

    if (dx > dy) {
        const midX = p1.x + (p2.x - p1.x) / 2;
        return `M ${p1.x} ${p1.y} L ${midX} ${p1.y} L ${midX} ${p2.y} L ${p2.x} ${p2.y}`;
    } else {
        const midY = p1.y + (p2.y - p1.y) / 2;
        return `M ${p1.x} ${p1.y} L ${p1.x} ${midY} L ${p2.x} ${midY} L ${p2.x} ${p2.y}`;
    }
};

const getMarqueeRect = (m) => {
    const x = Math.min(m.x1, m.x2);
    const y = Math.min(m.y1, m.y2);
    const width = Math.abs(m.x1 - m.x2);
    const height = Math.abs(m.y1 - m.y2);
    return { x, y, width, height };
};

const isNodeInMarquee = (node, rect) => {
    const nodeRight = node.x + node.w;
    const nodeBottom = node.y + node.h;
    const rectRight = rect.x + rect.width;
    const rectBottom = rect.y + rect.height;
    return node.x < rectRight && nodeRight > rect.x && node.y < rectBottom && nodeBottom > rect.y;
};

const isLabelInMarquee = (label, rect) => {
    const labelRight = label.x + (label.text.length * (label.fontSize / 2)); // Approximation de la largeur
    const labelBottom = label.y + label.fontSize;
    const rectRight = rect.x + rect.width;
    const rectBottom = rect.y + rect.height;
    return label.x < rectRight && labelRight > rect.x && label.y < rectBottom && labelBottom > rect.y;
};
// --- GESTION DES OBJETS (ADD/DELETE) ---
const openAddModal = (item) => {
    newNodeData.tag = item.tag;
    newNodeData.libraryId = item.id; // Renamed for better clarity
    newNodeData.designation = item.designation;
    newNodeData.type = item.type?.category || 'Composant';
    // On prend la valeur de la base de données (is_root), sinon on le définit comme root si c'est le premier équipement.
    newNodeData.isRoot = item.is_root !== undefined ? !!item.is_root : equipments.value.length === 0;
    newNodeData.region_id = null;
    newNodeData.status = 'en service'; // Statut par défaut
    newNodeData.zone_id = null;
    showAddModal.value = true;
    // Réinitialiser avec la structure objet pour les caractéristiques
    const initialChars = {};
    if (item.characteristics) {
        item.characteristics.forEach(char => {
            initialChars[char.id] = { value: null, date: new Date() };
        });
    }
    characteristicsData.value = initialChars;
};

const createNode = () => {
    const id = `node-${Date.now()}`;

    equipments.value.push({
        id, ...newNodeData,
        x: 400, y: 300, w: NODE_WIDTH, h: NODE_HEIGHT,
        active: true, // Les équipements sont actifs par défaut.
        // On attache une copie profonde des caractéristiques pour garantir l'isolation des données.
        characteristics: JSON.parse(JSON.stringify(characteristicsData.value))
    });
    showAddModal.value = false;
    recordState();
};

const saveLabel = () => {
    if (editingLabelId.value) {
        // Mode édition
        const label = labels.value.find(l => l.id === editingLabelId.value);
        if (label) {
            Object.assign(label, labelFormData);
        }
    } else {
        // Mode création
        const id = `label-${Date.now()}`;
        labels.value.push({ id, ...labelFormData, x: 500, y: 400 });
    }
    showLabelModal.value = false;
    editingLabelId.value = null;
    recordState();
};

const openEditModal = (node) => {
    editingNodeData.id = node.id;
    editingNodeData.tag = node.tag;
    editingNodeData.designation = node.designation;
    editingNodeData.isRoot = node.isRoot;
    editingNodeData.region_id = node.region_id;
    editingNodeData.zone_id = node.zone_id;
    editingNodeData.status = node.status || 'en service';
    editingNodeData.active = node.active !== undefined ? node.active : true;
    editingNodeData.libraryId = node.libraryId; // Important pour retrouver les caractéristiques

    // 1. Initialiser les caractéristiques attendues depuis la bibliothèque
    const initialChars = {};
    const libraryItem = Object.values(props.library).flat().find(item => item.id === node.libraryId);
    if (libraryItem && libraryItem.characteristics) {
        libraryItem.characteristics.forEach(char => {
            initialChars[char.id] = { value: null, date: new Date() };
        });
    }

    // 2. Remplacer avec les valeurs sauvegardées venant du backend si elles existent
    if (node.characteristics) {
        if (Array.isArray(node.characteristics)) {
            node.characteristics.forEach(savedChar => {
                // La clé est l'ID de la caractéristique de l'équipement (equipment_characteristic_id)
                const charId = savedChar.equipment_characteristic_id;
                initialChars[charId] = {
                    value: savedChar.value,
                    date: savedChar.date ? new Date(savedChar.date) : new Date()
                };
            });
        } else { // Logique pour les caractéristiques modifiées localement (non-sauvegardées)
            Object.keys(node.characteristics).forEach(charId => {
                if (initialChars[charId]) {
                    initialChars[charId] = { ...node.characteristics[charId] };
                }
            });
        }
    }
    characteristicsData.value = initialChars;
    showEditModal.value = true;
};

const saveNodeChanges = () => {
    const node = equipments.value.find(e => e.id === editingNodeData.id);
    if (node) {
        Object.assign(node, editingNodeData); // Copie les propriétés de editingNodeData vers node
        node.characteristics = { ...characteristicsData.value }; // Sauvegarder les caractéristiques modifiées
        showEditModal.value = false;
        recordState();
        toast.add({
            severity: 'success',
            summary: 'Mise à jour réussie',
            detail: 'Les informations du composant ont été enregistrées.',
            life: 3000

        });
    }
};

const deleteSelected = () => {
    if (selectedIds.value.length) {
        const ids = selectedIds.value;
        equipments.value = equipments.value.filter(e => !ids.includes(e.id));
        connections.value = connections.value.filter(c => !ids.includes(c.fromId) && !ids.includes(c.toId));
        selectedIds.value = [];
    }
    if (selectedConnectionId.value) {
        connections.value = connections.value.filter(c => c.id !== selectedConnectionId.value);
        selectedConnectionId.value = null;
    }
    if (selectedLabelIds.value.length) {
        const ids = selectedLabelIds.value;
        labels.value = labels.value.filter(l => !ids.includes(l.id));
        selectedLabelIds.value = [];
    }
    recordState();
};

const groupSelection = () => {
    if (selectedIds.value.length < 2) return;

    const selectedNodes = equipments.value.filter(e => selectedIds.value.includes(e.id));

    // Calculer la boîte englobante
    const minX = Math.min(...selectedNodes.map(n => n.x));
    const minY = Math.min(...selectedNodes.map(n => n.y));
    const maxX = Math.max(...selectedNodes.map(n => n.x + n.w));
    const maxY = Math.max(...selectedNodes.map(n => n.y + n.h));

    // Créer le nœud de groupe
    const groupId = `group-${Date.now()}`;
    const groupNode = {
        id: groupId,
        tag: 'GRP',
        designation: 'Groupe de composants',
        icon: 'pi-object-group',
        type: 'Groupe',
        x: minX - 20,
        y: minY - 40,
        w: (maxX - minX) + 40,
        h: (maxY - minY) + 60,
        active: true,
        isRoot: false,
        isGroup: true,
        children: selectedIds.value
    };

    // Ajouter le groupe et supprimer les anciens nœuds
    equipments.value.push(groupNode);
    equipments.value = equipments.value.filter(e => !selectedIds.value.includes(e.id));

    // Réinitialiser la sélection
    selectedIds.value = [groupId];
    recordState();
};

const centerView = () => {
    if (equipments.value.length === 0 || !mainContainer.value) return;

    const container = mainContainer.value;
    const containerWidth = container.clientWidth;
    const containerHeight = container.clientHeight;

    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
    equipments.value.forEach(node => {
        minX = Math.min(minX, node.x);
        minY = Math.min(minY, node.y);
        maxX = Math.max(maxX, node.x + node.w);
        maxY = Math.max(maxY, node.y + node.h);
    });

    const networkWidth = maxX - minX;
    const networkHeight = maxY - minY;

    if (networkWidth <= 0 || networkHeight <= 0) return;

    const padding = 0.2; // 20% de marge
    const zoomX = containerWidth / (networkWidth * (1 + padding));
    const zoomY = containerHeight / (networkHeight * (1 + padding));
    const newZoom = Math.min(zoomX, zoomY, 1.5); // Zoom max de 150%

    zoomLevel.value = newZoom;

    nextTick(() => {
        const centerX = minX + networkWidth / 2;
        const centerY = minY + networkHeight / 2;
        container.scrollLeft = (centerX * newZoom) - (containerWidth / 2);
        container.scrollTop = (centerY * newZoom) - (containerHeight / 2);
    });
};

const resetPanelPositions = () => {
    const padding = 24;
    const minimapWidth = 240;
    const analysisWidth = 256;
    const spacing = 12;

    minimapPosition.value = { x: window.innerWidth - minimapWidth - padding, y: padding };
    analysisPosition.value = { x: window.innerWidth - minimapWidth - analysisWidth - spacing - padding, y: padding };
};

const startDrag = (event, panelPositionRef) => {
    const startX = event.clientX;
    const startY = event.clientY;
    const initialX = panelPositionRef.value.x;
    const initialY = panelPositionRef.value.y;

    const onMouseMove = (moveEvent) => {
        const panelEl = event.target.closest('.draggable-panel');
        if (!panelEl) return;

        const panelWidth = panelEl.offsetWidth;
        const panelHeight = panelEl.offsetHeight;

        const dx = moveEvent.clientX - startX;
        const dy = moveEvent.clientY - startY;

        let newX = initialX + dx;
        let newY = initialY + dy;

        // Brider les positions pour rester dans la fenêtre
        newX = Math.max(0, Math.min(newX, window.innerWidth - panelWidth));
        newY = Math.max(0, Math.min(newY, window.innerHeight - panelHeight));

        panelPositionRef.value.x = newX;
        panelPositionRef.value.y = newY;
    };

    const onMouseUp = () => {
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
    };

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
};


// --- LOGIQUE DE DRAG & DROP ---
const handlePortClick = (nodeId, side) => {
    if (!linking.active) {
        linking.active = true;
        linking.fromId = nodeId;
        linking.fromSide = side;
        // Désélectionner tout pour éviter les confusions
        selectedIds.value = [];
        selectedConnectionId.value = null;
        selectedLabelId.value = null;
    } else {
        if (linking.fromId === nodeId) {
            linking.active = false;
            return;
        }
        // Vérifier si la connexion existe déjà
        const exists = connections.value.some(c =>
            (c.fromId === linking.fromId && c.toId === nodeId) ||
            (c.fromId === nodeId && c.toId === linking.fromId)
        );

        if (!exists) {
            connections.value.push({
                id: `conn-${Date.now()}`,
                fromId: linking.fromId, fromSide: linking.fromSide,
                toId: nodeId, toSide: side,
                color: linking.currentColor, dash: linking.currentDash
            });
            linking.active = false;
            recordState();
        } else {
            toast.add({ severity: 'warn', summary: 'Connexion existante', detail: 'Cette liaison existe déjà.', life: 2000 });
            linking.active = false;
        }
    }
};

const startMove = (e, item, type = 'node') => {
    if (linking.active) return;

    handleSelection(e, item, type);

    const startX = e.clientX;
    const startY = e.clientY;

    // Capturer les positions initiales pour les déplacements multiples
    const isMovingGroup = (type === 'node' && selectedIds.value.includes(item.id)) || (type === 'label' && selectedLabelIds.value.includes(item.id));

    let targets = [];
    if (isMovingGroup) {
        targets.push(...equipments.value.filter(n => selectedIds.value.includes(n.id)));
        targets.push(...labels.value.filter(l => selectedLabelIds.value.includes(l.id)));
    } else {
        targets.push(item);
    }
    const initialPos = targets.map(t => ({ id: t.id, x: t.x, y: t.y, type: t.text === undefined ? 'node' : 'label' }));

    const onMouseMove = (me) => {
        const dx = (me.clientX - startX) / zoomLevel.value; // Calculer le delta en tenant compte du zoom
        const dy = (me.clientY - startY) / zoomLevel.value;

        initialPos.forEach(pos => {
            const current = type === 'node'
                ? equipments.value.find(n => n.id === pos.id) // Cette partie est maintenant simplifiée par la nouvelle logique
                : labels.value.find(l => l.id === pos.id);

            if (current) {
                current.x = Math.round((pos.x + dx) / 5) * 5; // Magnétisme à la grille (5px)
                current.y = Math.round((pos.y + dy) / 5) * 5;
            }
        });
    };

    const onMouseUp = () => {
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
        recordState();
    };

    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
};

const handleSelection = (e, item, type) => {
    if (type === 'node') {
        selectedLabelIds.value = [];
        selectedConnectionId.value = null;
        if (e.shiftKey) {
            // Mode multi-sélection (Maj)
            const index = selectedIds.value.indexOf(item.id);
            if (index > -1) {
                selectedIds.value.splice(index, 1); // Désélectionner
            } else {
                selectedIds.value.push(item.id); // Sélectionner
            }
        } else {
            // Sélection simple ou déplacement de groupe
            if (!selectedIds.value.includes(item.id)) {
                selectedIds.value = [item.id];
            }
        }
    } else if (type === 'label') {
        selectedIds.value = [];
        selectedConnectionId.value = null;
        if (e.shiftKey) {
            const index = selectedLabelIds.value.indexOf(item.id);
            if (index > -1) selectedLabelIds.value.splice(index, 1);
            else selectedLabelIds.value.push(item.id);
        } else {
            if (!selectedLabelIds.value.includes(item.id))
                selectedLabelIds.value = [item.id];
        }
    }
};

const handleCanvasMouseDown = (e) => {
    // Ne pas démarrer le rectangle de sélection si on clique sur un élément
    if (e.target.closest('.node-container, .p-button')) return;

    selectedIds.value = [];
    selectedConnectionId.value = null;
    selectedLabelIds.value = [];

    const rect = e.currentTarget.getBoundingClientRect();
    marquee.active = true;
    marquee.x1 = (e.clientX - rect.left) / zoomLevel.value;
    marquee.y1 = (e.clientY - rect.top) / zoomLevel.value;
    marquee.x2 = marquee.x1;
    marquee.y2 = marquee.y1;
};

// --- INITIALISATION ---
const loadNetwork = (network) => {
    if (!network) {
        projectId.value = null;
        networkName.value = "Nouveau Projet";
 form.region_id = null; // Initialize region_id for new projects
        equipments.value = [{
            id: 'root-0', tag: 'G1', designation: 'Arrivée réseau générale', icon: 'pi-bolt',
            type: 'Source', x: 200, y: 200, w: NODE_WIDTH, h: NODE_HEIGHT, active: true, isRoot: true
        }];
        connections.value = [];
        labels.value = [];
        zoomLevel.value = 0.85;
        return;
    }

    projectId.value = network.id;
    networkName.value = network.name;
 form.region_id = network.region_id; // Initialize region_id from existing network
    zoomLevel.value = parseFloat(network.zoom_level) || 0.85;

    equipments.value = (network.nodes || []).map(node => {
        const eq = node.equipment || {};
        const type = eq.equipment_type || {};
        return {
            ...node,
            id: node.id,
            libraryId: eq.id,
            tag: eq.tag || `ID-${node.id}`,
            designation: eq.designation || 'Équipement Standard',
            icon: type.icon || 'pi-box',
            type: type.name || 'Composant',
            x: parseFloat(node.x),
            y: parseFloat(node.y),
            w: parseFloat(node.w) || NODE_WIDTH,
            h: parseFloat(node.h) || NODE_HEIGHT,
            active: node.is_active !== undefined ? (node.is_active ? 1 : 0) : 1,
            isRoot: !!node.is_root,
            region_id: node.region_id,
            zone_id: node.zone_id, // Assurez-vous que zone_id est correctement mappé
            characteristics: node.characteristics || {}, // Assurer que les caractéristiques sont là
            next_maintenance_date: node.next_maintenance_date, // Ajout de la date de maintenance
        };
    });

    connections.value = (network.connections || []).map(conn => ({
        id: conn.id,
        fromId: conn.from_node_id, fromSide: conn.from_side,
        toId: conn.to_node_id, toSide: conn.to_side,
        color: conn.color || '#3b82f6', dash: conn.dash_array || '0'
    })); // Assurez-vous que les IDs sont des strings si nécessaire

    labels.value = network.labels || [];
};

const getLocationName = (node) => {

    if (node.zone_id) return props.zones.find(z => z.id === node.zone_id)?.nomenclature;
    return 'N/A';
};

onMounted(() => {
    loadNetwork(props.initialNetwork);

    recordState();

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Delete' || e.key === 'Backspace') {
            if (e.target.tagName !== 'INPUT') deleteSelected();
        }
        if (e.ctrlKey && e.key === 'z') { e.preventDefault(); undo(); } // Empêche le comportement par défaut du navigateur
    });
});

const filteredLibrary = computed(() => {
    if (!searchQuery.value) return props.library;
    const q = searchQuery.value.toLowerCase();
    const res = {};
    for (const [cat, items] of Object.entries(props.library)) { // Itérer sur les catégories de la bibliothèque
        const matches = items.filter(i => i.designation.toLowerCase().includes(q) || i.tag.toLowerCase().includes(q));
        if (matches.length) res[cat] = matches;
    }
    return res;
});

const updateMousePos = (e) => {
    if (linking.active) {
        const rect = document.getElementById('canvas-svg').getBoundingClientRect();
        linking.mouseX = (e.clientX - rect.left) / zoomLevel.value;
        linking.mouseY = (e.clientY - rect.top) / zoomLevel.value;
    }

    if (marquee.active && e.currentTarget) {
        const rect = e.currentTarget.getBoundingClientRect();
        marquee.x2 = (e.clientX - rect.left) / zoomLevel.value;
        marquee.y2 = (e.clientY - rect.top) / zoomLevel.value;
    }
};

const handleMouseUp = () => {
    if (marquee.active) {
        marquee.active = false;
        const rect = getMarqueeRect(marquee);
        if (rect.width > 5 && rect.height > 5) { // Seuil minimal
            selectedIds.value = equipments.value
                .filter(node => isNodeInMarquee(node, rect))
                .map(node => node.id);
            selectedLabelIds.value = labels.value
                .filter(label => isLabelInMarquee(label, rect))
                .map(label => label.id);
        }
    }
};
// Ajustez ces valeurs pour changer le flux
const ELECTRON_COUNT = 10; // Nombre d'électrons par câble
const ELECTRON_SPACING = 0.6; // Décalage en secondes entre chaque électron
const FLOW_SPEED = 1.8; // Vitesse totale du parcours
// --- LOGIQUE DES STATISTIQUES ---
const showStats = ref(false);

const stats = computed(() => {
    const total = equipments.value.length;
    if (total === 0) return null;

    // Répartition par catégorie (Source, Protection, etc.)
    const categories = equipments.value.reduce((acc, curr) => {
        acc[curr.type] = (acc[curr.type] || 0) + 1;
        return acc;
    }, {});

    // Santé du réseau
    const operational = equipments.value.filter(e => e.status === 'online').length;
    const inAlert = equipments.value.filter(e => e.status === 'error').length;
    const healthScore = Math.round((operational / total) * 100);

    return {
        total,
        categories,
        healthScore,
        inAlert,
        connections: connections.value.length,
        energized: energizedNodes.value.size
    };
});
// --- GESTION DES DONNÉES (EXPORT / SAUVEGARDE) ---

// 1. Exportation en fichier JSON (Téléchargement direct)
const exportProject = () => {
    const projectData = {
        name: networkName.value,
        region_id: form.region_id, // Assurez-vous que form.region_id est défini
        version: (parseFloat(network.version || "1.9") + 0.1).toFixed(1),
        date: new Date().toISOString(),
        equipments: equipments.value,
        connections: connections.value,
        labels: labels.value
    };

    const blob = new Blob([JSON.stringify(projectData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${networkName.value.replace(/\s+/g, '_')}_export.json`;
    link.click();
    URL.revokeObjectURL(url);

    toast.add({ severity: 'success', summary: 'Export réussi', detail: 'Fichier JSON téléchargé', life: 3000 });
};

// 2. Sauvegarde en Base de Données (ou LocalStorage par défaut)
const saveToDatabase = async () => {
    const payload = {
        name: networkName.value,
        data: {
            equipments: equipments.value, // Les équipements avec leurs caractéristiques
            connections: connections.value,
            labels: labels.value
        }
    };

    try {
        // Option A: LocalStorage (Immédiat)
        localStorage.setItem('neocad_last_project', JSON.stringify(payload));

        // Option B: API (Décommentez si vous avez un backend)
        /*
        await fetch('https://votre-api.com/projects/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        */

        toast.add({ severity: 'success', summary: 'Projet sauvegardé', detail: 'Données enregistrées avec succès', life: 3000 });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de sauvegarder', life: 3000 });
    }
};

// 3. Importation d'un fichier JSON
const triggerFileInput = () => document.getElementById('import-file').click();

const importProject = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const imported = JSON.parse(e.target.result);
            equipments.value = imported.equipments || [];
            connections.value = imported.connections || [];
            labels.value = imported.labels || [];
            networkName.value = imported.name || "PROJET_IMPORTE";

            recordState();
            toast.add({ severity: 'info', summary: 'Importation', detail: 'Le projet a été chargé', life: 3000 });
        } catch (err) {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'Fichier JSON invalide', life: 3000 });
        }
    };
    reader.readAsText(file);
};
const form = useForm({
    id: null,
    name: networkName.value,
    region_id: null,
    zoom_level: zoomLevel.value,
    version: "2.0",
    date: new Date(),
    equipments: [],
    connections: [],
    labels: []
});
const projectId = ref(null);

const exportApiProject = () => {
    console.log(form);
    // 1. Mise à jour des données du formulaire avant envoi
    form.name = networkName.value; // Assurez-vous que networkName.value est à jour
    form.region_id = form.region_id; // Assurer que la région est dans le formulaire
    form.zoom_level = zoomLevel.value;
    form.equipments = equipments.value;
    form.connections = connections.value;
    form.labels = labels.value;

    const isUpdate = !!projectId.value;
    const url = isUpdate ? route('networks.update', projectId.value) : route('networks.store');

    // 2. Transformation en FormData pour une sérialisation correcte des tableaux et objets imbriqués
    form.transform((originalData) => {
        const formData = new FormData();

        // Simulation de PUT pour Laravel si c'est un update
        if (isUpdate) {
            formData.append('_method', 'PUT');
        }

        // Ajout des champs simples et conversion des dates
        for (const key in originalData) {
            if (['equipments', 'connections', 'labels'].includes(key)) continue; // Gérer ces clés séparément

            const value = originalData[key];
            if (value instanceof Date) {
                formData.append(key, value.toISOString().split('T')[0]);
            } else if (value !== null && value !== undefined) {
                formData.append(key, value); // Ajout des autres champs simples
            }
        }

        // Sérialisation des tableaux complexes (Equipements, Connexions, Labels)
        originalData.equipments.forEach((item, index) => {
            Object.keys(item).forEach(prop => {
                // Ajout du nodeId si l'ID de l'item est numérique (ID de la BDD)
                if (isUpdate && typeof item.id === 'number') {
                    formData.append(`equipments[${index}][nodeId]`, item.id ?? '');
                    formData.append(`equipments[${index}][zone_id]`, item.zone_id ?? '');
                    formData.append(`equipments[${index}][is_root]`, item.isRoot ? 1 : 0);
                }

                if (prop === 'characteristics') {
                    // Sérialiser les caractéristiques comme un objet JSON
                    const characteristics = item[prop] || {};
                    // Convertir l'objet des caractéristiques en un tableau de la forme attendue par le backend
                    const formattedCharacteristics = Object.keys(characteristics).map(charId => ({
                        equipment_characteristic_id: charId, // Correction du nom de la clé
                        equipment_id: item.libraryId, // Correction: Utiliser l'ID de l'équipement, pas l'ID du noeud
                        value: characteristics[charId].value, // La valeur peut être une chaîne ou un nombre
                        date: characteristics[charId].date ? new Date(characteristics[charId].date).toISOString().slice(0, 19).replace('T', ' '):null,
                        // date: characteristics[charId].date ? new Date(characteristics[charId].date).toISOString().split('T')[0] : null,
                    }));
                    // Ajouter chaque caractéristique individuellement au FormData
                    formattedCharacteristics.forEach((char, charIndex) => {
                        for (const charProp in char) {
                            formData.append(`equipments[${index}][characteristics][${charIndex}][${charProp}]`, char[charProp] ?? '');
                        }
                    });
                } else {
                    // Correction pour s'assurer que les ID null ne sont pas envoyés comme "null"
                    if ((prop === 'zone_id' || prop === 'region_id') && (item[prop] === null || item[prop] === undefined)) {
                        formData.append(`equipments[${index}][${prop}]`, '');
                    } else {
                        formData.append(`equipments[${index}][${prop}]`, item[prop] ?? '');
                    }
                }
            });

        });

        originalData.connections.forEach((conn, index) => {
            Object.keys(conn).forEach(prop => {
                formData.append(`connections[${index}][${prop}]`, conn[prop] ?? '');
            });
        });

        originalData.labels.forEach((label, index) => {
            Object.keys(label).forEach(prop => {
                formData.append(`labels[${index}][${prop}]`, label[prop] ?? '');
            });
        });

        return formData;
    })
    // 3. Exécution de l'envoi avec Inertia
    .submit('post', url, {
        forceFormData: true,
        onSuccess: (page) => {
            // Si c'est une création, on récupère l'ID depuis la session ou les props
            if (!isUpdate && page.props.flash?.id) {
                projectId.value = page.props.flash.id;
            }
            toast.add({
                severity: 'success',
                summary: 'Sauvegarde Cloud',
                detail: isUpdate ? 'Projet mis à jour' : 'Projet créé avec succès',
                life: 3000
            });
        },
        onError: (errors) => {
            const errorDetail = Object.values(errors).flat().join(' ; ');
            toast.add({
                severity: 'error', // Afficher les erreurs
                summary: 'Erreur de sauvegarde',
                detail: errorDetail || 'Veuillez vérifier les champs',
                life: 5000
            });
        }
    });
};

const getCharacteristicName = (charId) => {
    // 1. Chercher dans la bibliothèque (plus rapide)
    for (const category in props.library) {
        for (const item of props.library[category]) {
            const char = item.characteristics?.find(c => c.id == charId);
            if (char) return char.name;
        }
    }

    // 2. Si non trouvé, chercher dans les données chargées du réseau initial (cas de l'édition)
    if (props.initialNetwork?.nodes) {
        for (const node of props.initialNetwork.nodes) {
            if (node.characteristics) {
                const savedChar = node.characteristics.find(c => c.equipment_characteristic_id == charId);
                // La relation 'equipmentCharacteristic' a été chargée dans le contrôleur
                if (savedChar && savedChar.equipment_characteristic) {
                    return savedChar.equipment_characteristic.name;
                }
            }
        }
    }
    return `Caractéristique ${charId}`;
};

</script>

<template>
  <div class="app-shell h-screen flex flex-col bg-[#020408] text-slate-300 font-sans overflow-hidden select-none">
    <Toast />

    <header class="h-14 bg-[#0a0f1d] border-b border-white/5 flex items-center justify-between px-4 z-[100] shadow-xl">


<transition name="slide-fade">
    <div v-if="showStats && stats" class="absolute top-4 right-4 w-80 bg-[#0a0f1d]/95 backdrop-blur-xl border border-white/10 rounded-2xl shadow-2xl z-[90] overflow-hidden">
        <div class="p-4 border-b border-white/5 bg-indigo-500/5 flex justify-between items-center">
            <span class="text-xs font-black text-indigo-400 uppercase tracking-widest">Supervision Système</span>
            <i class="pi pi-times cursor-pointer text-slate-500 hover:text-white" @click="showStats = false"></i>
        </div>

        <div class="p-5 space-y-6">
            <div class="text-center">
                <div class="inline-flex relative items-center justify-center">
                    <svg class="w-20 h-20 transform -rotate-90">
                        <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="4" fill="transparent" class="text-slate-800" />
                        <circle cx="40" cy="40" r="36" stroke="currentColor" stroke-width="4" fill="transparent"
                                :stroke-dasharray="226"
                                :stroke-dashoffset="226 - (226 * stats.healthScore) / 100"
                                :class="['transition-all duration-1000', stats.healthScore > 80 ? 'text-green-500' : 'text-amber-500']" />
                    </svg>
                    <span class="absolute text-sm font-black font-mono">{{ stats.healthScore }}%</span>
                </div>
                <p class="text-[10px] text-slate-500 uppercase mt-2 tracking-tighter">Indice de Santé Réseau</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white/[0.03] p-3 rounded-xl border border-white/5">
                    <span class="text-[9px] text-slate-500 block mb-1">COMPOSANTS</span>
                    <span class="text-xl font-mono font-bold">{{ stats.total }}</span>
                </div>
                <div class="bg-white/[0.03] p-3 rounded-xl border border-white/5">
                    <span class="text-[9px] text-slate-500 block mb-1">EN ALERTE</span>
                    <span :class="['text-xl font-mono font-bold', stats.inAlert > 0 ? 'text-red-500' : 'text-slate-200']">
                        {{ stats.inAlert }}
                    </span>
                </div>
            </div>

            <div class="space-y-3">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Répartition Asset</span>
                <div v-for="(count, cat) in stats.categories" :key="cat" class="space-y-1">
                    <div class="flex justify-between text-[10px]">
                        <span class="text-slate-400">{{ cat }}</span>
                        <span class="font-mono text-indigo-400">{{ count }}</span>
                    </div>
                    <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-indigo-500 transition-all duration-700"
                             :style="{ width: (count / stats.total * 100) + '%' }"></div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-white/5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-ping"></span>
                    <span class="text-[9px] text-slate-500 uppercase">Flux Électrique Live</span>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-[11px] text-slate-300 italic">Noeuds alimentés</span>
                    <span class="text-lg font-mono text-green-400">{{ stats.energized }} / {{ stats.total }}</span>
                </div>
            </div>
        </div>
    </div>
</transition>
        <div class="flex items-center gap-4">
        <div class="flex items-center gap-2 px-2">
            <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="pi pi-bolt text-white text-sm"></i>
            </div>
            <div class="flex flex-col">
                <span class="text-white font-bold text-xs tracking-tight">NEO<span class="text-indigo-400">CAD</span></span>
                <input v-model="networkName" class="bg-transparent border-none text-[9px] text-slate-500 outline-none w-32" />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-[9px] font-bold text-slate-500 uppercase">Région:</label>
            <Dropdown v-model="form.region_id" :options="props.regions" optionLabel="designation" optionValue="id"
                      placeholder="Choisir Région"
                      class="!w-48 dark-dropdown-header" />
        </div>

        <div class="h-6 w-px bg-white/10 mx-2"></div>

        <div class="flex items-center gap-1">
            <Button icon="pi pi-undo" @click="undo" class="p-button-text p-button-sm !text-slate-400 hover:!text-white" v-tooltip="'Annuler (Ctrl+Z)'" />
            <Button icon="pi pi-tag" label="Label" @click="openNewLabelModal" class="p-button-text p-button-sm !text-slate-400" />
            <Button icon="pi pi-save" label="Enregistre" @click="exportApiProject" class="p-button-text p-button-sm !text-indigo-400" />
            <Button icon="pi pi-download" label="Export JSON" @click="exportProject" class="p-button-text p-button-sm !text-slate-400" />
            <input type="file" id="import-file" class="hidden" accept=".json" @change="importProject" />
    <Button icon="pi pi-upload" label="Importer" @click="triggerFileInput" class="p-button-text p-button-sm !text-slate-400" />
     <Button v-if="selectedIds.length > 1"
                    icon="pi pi-object-group" @click="groupSelection" class="p-button-text p-button-sm !text-amber-400" v-tooltip.bottom="'Grouper la sélection'" />
     <Button v-if="selectedIds.length > 0 || selectedConnectionId || selectedLabelId"
                    icon="pi pi-trash" @click="deleteSelected" severity="danger" class="p-button-text p-button-sm animate-pulse" />
        </div>

      </div>

      <div class="flex items-center gap-4">
        <div class="flex items-center gap-3 bg-black/40 px-3 py-1.5 rounded-full border border-white/5">
            <i class="pi pi-search text-slate-600 text-[10px]"></i>
            <Slider v-model="zoomLevel" :min="0.1" :max="3" :step="0.01" class="w-24" />
            <span class="text-[10px] font-mono text-indigo-400 w-8">{{ Math.round(zoomLevel*100) }}%</span>
            <Button icon="pi pi-map" @click="isMinimapVisible = !isMinimapVisible" :class="['p-button-text p-button-sm', isMinimapVisible ? '!text-indigo-400' : '!text-slate-500']" v-tooltip.bottom="'Afficher le mini-plan'" />
            <Button icon="pi pi-arrows-alt" @click="centerView" class="p-button-text p-button-sm !text-slate-400" v-tooltip.bottom="'Centrer la vue'" />
            <Button icon="pi pi-window-maximize" @click="resetPanelPositions" class="p-button-text p-button-sm !text-slate-400" v-tooltip.bottom="'Réinitialiser la position des panneaux'" />
            <Button icon="pi pi-info-circle" @click="isAnalyseVisible = !isAnalyseVisible" :class="['p-button-text p-button-sm', isAnalyseVisible ? '!text-indigo-400' : '!text-slate-500']" v-tooltip.bottom="'Afficher l\'analyse réseau'" />
        </div>
        <Button icon="pi pi-bars" @click="isSidebarOpen = !isSidebarOpen" class="p-button-text p-button-secondary" />
        <Button icon="pi pi-chart-bar"
        label="Statistiques"
        @click="showStats = !showStats"
        :class="['p-button-text p-button-sm transition-all', showStats ? '!text-indigo-400 bg-indigo-500/10' : '!text-slate-400']" />
      </div>
    </header>

    <div class="flex-grow flex relative overflow-hidden">
      <aside :class="['bg-[#0a0f1d] border-r border-white/5 flex flex-col z-50 transition-all duration-300', isSidebarOpen ? 'w-72' : 'w-0 overflow-hidden']">
        <div class="p-4 border-b border-white/5">
            <div class="relative">
                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                <InputText v-model="searchQuery" placeholder="Rechercher..." class="w-full pl-9 bg-black/20 border-white/10 text-xs py-2 rounded-lg" />
            </div>
        </div>

        <div class="flex-grow overflow-y-auto p-3 space-y-6 custom-scrollbar">
            <div v-for="(items, category) in filteredLibrary" :key="category">
                <div class="flex items-center gap-2 mb-3 px-2">
                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ category }}</span>
                    <div class="flex-grow h-px bg-white/5"></div>
                </div>

                <div class="grid grid-cols-1 gap-1.5">
                    <div v-for="item in items" :key="item.id"
                         @click="openAddModal(item)"
                         class="group flex items-center gap-3 p-2.5 bg-white/[0.02] hover:bg-indigo-500/10 border border-white/5 hover:border-indigo-500/50 rounded-xl cursor-pointer transition-all">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center group-hover:bg-indigo-600/20 transition-colors">
                             <i :class="['pi', item.type?.icon || 'pi-box', 'text-slate-500 group-hover:text-indigo-400']"></i>
                        </div>
                        <div class="flex flex-col overflow-hidden">
                            <span class="text-[10px] font-bold text-slate-200 group-hover:text-white truncate">{{ item.tag }}</span>
                            <span class="text-[9px] text-slate-500 truncate lowercase">{{ item.designation }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </aside>

      <main ref="mainContainer"
            class="flex-grow relative overflow-auto bg-dot-pattern custom-scrollbar"
            @mousemove="updateMousePos"
            @mousedown="handleCanvasMouseDown"
            @scroll="handleScroll"
            @mouseup="handleMouseUp">

        <div :style="{ transform: `scale(${zoomLevel})`, transformOrigin: '0 0' }" class="absolute inset-0 transition-transform duration-75">

          <svg id="canvas-svg" :width="CANVAS_SIZE" :height="CANVAS_SIZE" class="absolute inset-0 pointer-events-none">
            <!-- Rectangle de sélection (Marquee) -->
            <rect v-if="marquee.active"
                  :x="marqueeRect.x" :y="marqueeRect.y"
                  :width="marqueeRect.width" :height="marqueeRect.height"
                  fill="rgba(99, 102, 241, 0.1)"
                  stroke="rgba(99, 102, 241, 0.5)"
                  stroke-width="1" />
            <defs>
                <filter id="neon-glow" x="-20%" y="-20%" width="140%" height="140%">
                    <feGaussianBlur stdDeviation="3" result="blur" />
                    <feComposite in="SourceGraphic" in2="blur" operator="over" />
                </filter>
            </defs>

            <g v-for="w in connections" :key="w.id" class="pointer-events-auto cursor-pointer" @mousedown.stop="selectedConnectionId = w.id; selectedIds = []">
                <path :d="getOrthogonalPath(w)" stroke="transparent" stroke-width="12" fill="none" @mousedown.stop="selectedConnectionId = w.id" />

                <path :d="getOrthogonalPath(w)"
                      :stroke="isWireLive(w) ? w.color : '#1e293b'"
                      :stroke-width="selectedConnectionId === w.id ? 4 : 2"
                      :stroke-dasharray="w.dash"
                      fill="none"
                      class="transition-all duration-500"
                      :filter="isWireLive(w) ? 'url(#neon-glow)' : ''" />

               <g v-if="isWireLive(w)">
    <circle v-for="n in 3" :key="n" r="2.5" fill="#fff" class="electron-glow">
        <animateMotion
            :path="getOrthogonalPath(w)"
            :dur="FLOW_SPEED + 's'"
            :begin="((n - 1) * 0.6) + 's'"
            repeatCount="indefinite"
        />
    </circle>
</g>
            </g>

            <path v-if="linking.active"
                  :d="`M ${getPortPos(equipments.find(e => e.id === linking.fromId), linking.fromSide).x}
                        ${getPortPos(equipments.find(e => e.id === linking.fromId), linking.fromSide).y}
                        L ${linking.mouseX} ${linking.mouseY}`"
                  stroke="#fbbf24" stroke-width="2" stroke-dasharray="4,4" fill="none" />
          </svg>

          <div v-for="label in labels" :key="label.id"
               :style="{ left: label.x + 'px', top: label.y + 'px', color: label.color, fontSize: label.fontSize + 'px', fontWeight: label.bold ? 'bold' : 'normal', transform: `rotate(${label.rotation || 0}deg)` }"
               @mousedown.stop="startMove($event, label, 'label')"
               @dblclick.stop="openEditLabelModal(label)"
               :class="['absolute z-10 px-2 py-1 cursor-move whitespace-nowrap border border-transparent hover:border-white/10 rounded', selectedLabelIds.includes(label.id) ? '!border-indigo-500 bg-indigo-500/10' : '']">
               {{ label.text }}
          </div>

          <div v-for="node in equipments" :key="node.id"
               :style="{ left: node.x + 'px', top: node.y + 'px', width: node.w + 'px', height: node.h + 'px' }"
               @mousedown.stop="startMove($event, node, 'node')" @dblclick="openEditModal(node)"
               class="absolute z-20 node-container group">
            <div :class="['relative w-full h-full bg-[#0f172a] border-2 rounded-2xl transition-all shadow-xl',
                          selectedIds.includes(node.id) ? 'border-indigo-500 shadow-indigo-500/20' : 'border-slate-800',
                          !node.active ? 'opacity-50' : '',
                          node.isGroup ? 'bg-indigo-900/20' : '']">

                <!-- NOUVEAU : Indicateur de maintenance planifiée -->
                <div v-if="node.next_maintenance_date"
                     v-tooltip.top="`Maintenance planifiée le ${new Date(node.next_maintenance_date).toLocaleDateString('fr-FR')}`"
                     class="absolute -top-2 -right-2 w-6 h-6 bg-amber-500 rounded-full flex items-center justify-center shadow-lg border-2 border-[#0f172a] animate-pulse z-30">
                    <i class="pi pi-calendar-clock text-white text-xs"></i>
                </div>

                <div class="h-9 px-3 flex items-center justify-between border-b border-white/5 bg-white/[0.02]">
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-black text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded">{{ node.tag }}</span>
                        <i v-if="node.isRoot" class="pi pi-bolt text-amber-500 text-[10px] animate-pulse"></i>
                    </div>
                    <div class="flex items-center gap-2">
                        <i @click.stop="node.active = !node.active"
                           :class="['pi pi-power-off text-[10px] cursor-pointer hover:scale-110', node.active ? 'text-green-500' : 'text-slate-500']"></i>
                        <i :class="['pi', node.icon, 'text-slate-500 text-[10px]']"></i>
                    </div>
                </div>

                <div class="p-4 flex flex-col items-center justify-center text-center h-[calc(100%-36px)]">
                    <span class="text-[11px] font-bold text-slate-100 leading-tight uppercase tracking-tight">
                        {{ node.designation }}
                    </span>
                    <div v-if="energizedNodes.has(node.id)" class="mt-2 flex gap-1">
                        <span class="w-1 h-1 bg-green-500 rounded-full animate-ping"></span>
                        <span class="text-[8px] font-mono text-green-500/70">ACTIVE</span>
                    </div>
                    <div v-if="getLocationName(node)" class="absolute bottom-2 left-2 flex items-center gap-1.5 bg-slate-900/50 border border-white/10 rounded-full px-2 py-0.5">
                        <i class="pi pi-map-marker text-[8px] text-amber-400"></i>
                        <span class="text-[8px] font-bold text-slate-300 uppercase tracking-wider">{{ getLocationName(node) }}</span>
                    </div>

                </div>

                <div v-for="side in ['N', 'S', 'E', 'W']" :key="side"
                     @click.stop="handlePortClick(node.id, side)"
                     :class="['absolute w-4 h-4 rounded-full border-2 border-[#020408] z-30 transition-all cursor-crosshair opacity-0 group-hover:opacity-100 scale-75 hover:scale-125',
                              side === 'N' ? '-top-2 left-1/2 -translate-x-1/2' : '',
                              side === 'S' ? '-bottom-2 left-1/2 -translate-x-1/2' : '',
                              side === 'E' ? '-right-2 top-1/2 -translate-y-1/2' : '',
                              side === 'W' ? '-left-2 top-1/2 -translate-y-1/2' : '',
                              linking.fromId === node.id && linking.fromSide === side ? 'bg-amber-400 !opacity-100 animate-bounce' : 'bg-slate-700 hover:bg-indigo-500']">
                </div>
            </div>
          </div>
        </div>

        <!-- MINI-PLAN (MINIMAP) -->
        <transition name="slide-fade">
            <div v-if="isMinimapVisible"
                 ref="minimapPanel"
                 :style="{ left: minimapPosition.x + 'px', top: minimapPosition.y + 'px' }"
                 class="absolute w-60 bg-[#0a0f1d]/80 backdrop-blur-md border border-white/10 rounded-2xl shadow-2xl z-[80] overflow-hidden flex flex-col draggable-panel">
                <div @mousedown.stop="startDrag($event, minimapPosition)" class="h-6 bg-black/20 cursor-move flex items-center justify-center">
                    <i class="pi pi-ellipsis-h text-slate-600 text-xs"></i>
                </div>
                <div class="relative w-full h-48" @mousedown.stop="handleMinimapMouseDown">
                    <!-- Nodes in minimap -->
                    <div v-for="node in equipments" :key="'mini-' + node.id"
                         :style="{
                             left: `${node.x * minimapScale}px`,
                             top: `${node.y * minimapScale}px`,
                             width: `${node.w * minimapScale}px`,
                             height: `${node.h * minimapScale}px`,
                             backgroundColor: selectedIds.includes(node.id) ? 'var(--indigo-500)' : 'rgba(255,255,255,0.2)'
                         }"
                         class="absolute rounded-[2px] transition-colors"></div>
                    <!-- Viewport -->
                    <div id="minimap-viewport" :style="viewportStyle" class="absolute bg-red-500/30 border-2 border-red-500 rounded cursor-grab"></div>
                </div>
            </div>
        </transition>

        <transition name="slide-fade">
            <div v-if="isAnalyseVisible"
                 ref="analysisPanel"
                 :style="{ left: analysisPosition.x + 'px', top: analysisPosition.y + 'px' }"
                 class="absolute w-64 bg-[#0a0f1d]/80 backdrop-blur-md border border-white/5 rounded-2xl shadow-2xl z-[80] flex flex-col draggable-panel">
                <div @mousedown.stop="startDrag($event, analysisPosition)" class="h-6 bg-black/20 cursor-move flex items-center justify-center">
                     <i class="pi pi-ellipsis-h text-slate-600 text-xs"></i>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Analyse Réseau</span>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-[10px] text-slate-500">Appareils</span>
                            <span class="text-xs font-mono font-bold">{{ equipments.length }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[10px] text-slate-500">Liaisons</span>
                            <span class="text-xs font-mono font-bold text-indigo-400">{{ connections.length }}</span>
                        </div>
                        <div class="flex justify-between border-t border-white/5 pt-2 mt-2">
                            <span class="text-[10px] text-slate-500 italic">Disponibilité</span>
                            <span class="text-xs font-mono text-green-400">99.8%</span>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <div v-if="linking.active" class="absolute top-20 left-1/2 -translate-x-1/2 bg-[#0f172a] border border-amber-500/50 rounded-xl p-3 z-[200] flex items-center gap-4 shadow-2xl animate-fade-in">
            <span class="text-[10px] font-bold text-amber-500 uppercase px-2">Type de conducteur:</span>
            <div class="flex gap-1.5">
                <div v-for="w in wireTypes" :key="w.value"
                     @click="linking.currentColor = w.value; linking.currentDash = w.dash"
                     :style="{ backgroundColor: w.value }"
                     :class="['w-5 h-5 rounded-full cursor-pointer border-2 transition-transform hover:scale-110',
                              linking.currentColor === w.value ? 'border-white scale-110' : 'border-transparent opacity-40']">
                </div>
            </div>
            <Button icon="pi pi-times" @click="linking.active = false" class="p-button-rounded p-button-danger p-button-text p-button-sm" />
        </div>
      </main>
    </div>

    <Dialog v-model:visible="showAddModal" header="Configuration du Composant" modal :style="{ width: '380px' }" class="dark-dialog">
        <div class="flex flex-col gap-5 py-2">
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">Tag Identifiant</label>
                <InputText v-model="newNodeData.tag" class="w-full !bg-white/5 border-white/10" />
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">Désignation Technique</label>
                <InputText v-model="newNodeData.designation" class="w-full !bg-white/5 border-white/10" />
            </div>
            <!-- Section pour les caractéristiques dynamiques -->
            <div v-if="characteristicsData && Object.keys(characteristicsData).length > 0" class="flex flex-col gap-4 p-4 bg-slate-800/50 rounded-lg border border-white/10">
                <label class="text-[10px] font-black text-indigo-400 uppercase tracking-tighter">Caractéristiques</label>
                <div v-for="(charData, charId) in characteristicsData" :key="charId" class="space-y-2">
                    <label class="text-[10px] font-medium text-slate-400 uppercase">{{ getCharacteristicName(charId) }}</label>
                    <div class="grid grid-cols-2 gap-2">
                        <!-- On lie la valeur à notre ref `characteristicsData` -->
                        <InputText v-model="charData.value" placeholder="Valeur" class="w-full !bg-white/5 border-white/10 text-xs" />
                        <Calendar v-model="charData.date" placeholder="Date" class="w-full" inputClass="!bg-white/5 !border-white/10 !text-xs" showIcon />
                    </div>
                </div>
            </div>


            <div class="flex items-center gap-3 p-3 bg-indigo-500/5 border border-indigo-500/20 rounded-xl">
                <input type="checkbox" v-model="newNodeData.isRoot" class="w-4 h-4 accent-indigo-500" />
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-indigo-300">Point d'injection</span>
                    <span class="text-[9px] text-slate-500">Source d'énergie principale</span>
                </div>
            </div>

            <div class="flex flex-col gap-3 p-3 bg-white/5 border border-white/10 rounded-xl">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">Localisation</label>
                <div v-if="!form.region_id" class="text-center p-4 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                    <p class="text-xs text-amber-400 font-semibold">
                        Veuillez d'abord sélectionner une région pour le réseau en haut de la page.
                    </p>
                </div>
                <Dropdown v-else
                      v-model="newNodeData.zone_id"
                      :options="props.regions.find(r => r.id === form.region_id)?.zones || []"
                          optionLabel="nomenclature"
                          optionValue="id"
                          placeholder="Sélectionner une zone"
                          class="w-full"
                          filter />
            </div>
            <Button label="Insérer sur le schéma" icon="pi pi-plus" @click="createNode" class="w-full !bg-indigo-600 border-none !rounded-xl py-3 mt-2" />
        </div>
    </Dialog>

    <Dialog v-model:visible="showEditModal" header="Modifier le Composant" modal :style="{ width: '380px' }" class="dark-dialog">
        <div class="flex flex-col gap-5 py-2">
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">Tag Identifiant</label>
                <InputText v-model="editingNodeData.tag" class="w-full !bg-white/5 border-white/10" />
            </div>
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">Désignation Technique</label>
                <InputText v-model="editingNodeData.designation" class="w-full !bg-white/5 border-white/10" />
            </div>

            <!-- Section pour les caractéristiques dynamiques en édition -->
            <div v-if="characteristicsData && Object.keys(characteristicsData).length > 0" class="flex flex-col gap-4 p-4 bg-slate-800/50 rounded-lg border border-white/10">
                <label class="text-[10px] font-black text-indigo-400 uppercase tracking-tighter">Caractéristiques</label>
                <div v-for="(charData, charId) in characteristicsData" :key="charId" class="space-y-2">
                    <label class="text-[10px] font-medium text-slate-400 uppercase">{{ getCharacteristicName(charId) }}</label>
                    <div class="grid grid-cols-2 gap-2">
                        <InputText v-model="charData.value" placeholder="Valeur" class="w-full !bg-white/5 border-white/10 text-xs" />
                        <Calendar v-model="charData.date" placeholder="Date" class="w-full" inputClass="!bg-white/5 !border-white/10 !text-xs" showIcon />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 p-3 bg-indigo-500/5 border border-indigo-500/20 rounded-xl">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">Statut</label>
                <Dropdown v-model="editingNodeData.status" :options="['en service', 'en maintenance', 'en panne', 'hors service']"
                          placeholder="Sélectionner un statut" class="w-full" />
            </div>

            <div class="flex items-center gap-3 p-3 bg-indigo-500/5 border border-indigo-500/20 rounded-xl">
                <input type="checkbox" v-model="editingNodeData.isRoot" class="w-4 h-4 accent-indigo-500" />
                <div class="flex flex-col">
                    <span class="text-xs font-bold text-indigo-300">Point d'injection</span>
                    <span class="text-[9px] text-slate-500">Source d'énergie principale</span>
                </div>
            </div>

            <div class="flex flex-col gap-3 p-3 bg-white/5 border border-white/10 rounded-xl">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">Localisation</label>
                <div v-if="!form.region_id" class="text-center p-4 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                    <p class="text-xs text-amber-400 font-semibold">
                        Veuillez d'abord sélectionner une région pour le réseau en haut de la page.
                    </p>
                </div>
                <Dropdown v-else
                      v-model="editingNodeData.zone_id"
                      :options="props.regions.find(r => r.id === form.region_id)?.zones || []"
                          optionLabel="nomenclature"
                          optionValue="id"
                          placeholder="Sélectionner une zone"
                          class="w-full"
                          filter />
            </div>

            <Button label="Enregistrer les modifications" icon="pi pi-save" @click="saveNodeChanges" class="w-full !bg-indigo-600 border-none !rounded-xl py-3 mt-2" />
        </div>
    </Dialog>

    <Dialog v-model:visible="showLabelModal" :header="editingLabelId ? 'Modifier l\'Annotation' : 'Ajouter une Annotation'" modal :style="{ width: '350px' }" class="dark-dialog">
        <div class="flex flex-col gap-4 py-2">
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase">Texte</label>
                <InputText v-model="labelFormData.text" class="w-full !bg-white/5 border-white/10" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase">Taille</label>
                    <InputNumber v-model="labelFormData.fontSize" :min="8" :max="72" showButtons class="!bg-white/5" />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase">Couleur</label>
                    <div class="flex items-center gap-2 bg-white/5 p-2 rounded-lg border border-white/10">
                        <ColorPicker v-model="labelFormData.color" />
                        <span class="text-[10px] font-mono">{{ labelFormData.color }}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-500 uppercase">Rotation (°)</label>
                    <InputNumber v-model="labelFormData.rotation" :min="-180" :max="180" showButtons suffix="°" class="!bg-white/5" />
                </div>
            </div>
            <Button :label="editingLabelId ? 'Mettre à jour' : 'Ajouter le label'" icon="pi pi-check" @click="saveLabel" class="w-full !bg-indigo-600 border-none !rounded-xl py-3" />
        </div>
    </Dialog>
  </div>
</template>

<style>
/* FOND TECHNIQUE */
.bg-dot-pattern {
    background-color: #020408;
    background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 30px 30px;
}

/* SCROLLBARS */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #334155; }

/* ANIMATIONS */
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }

.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateX(-20px) translateY(20px); opacity: 0; }

/* PRIME VUE OVERRIDES DARK THEME */
.dark-dialog .p-dialog-header {
    background: #0a0f1d !important;
    color: white !important;
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
    padding: 1.25rem !important;
}
.dark-dialog .p-dialog-content {
    background: #0a0f1d !important;
    color: #cbd5e1 !important;
    padding: 1.5rem !important;
}
.p-inputtext, .p-inputnumber-input {
    background: rgba(255,255,255,0.03) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: white !important;
    font-size: 0.8rem !important;
}
.dark-dropdown-header .p-dropdown {
    background: rgba(255,255,255,0.03) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: white !important;
    font-size: 0.8rem !important;
    border-radius: 0.5rem;
}
.p-inputtext:focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2) !important;
}

/* COMPOSANTS SPECIFIQUES */
.node-container {
    user-select: none;
    cursor: grab;
}
.node-container:active {
    cursor: grabbing;
}
.electron-glow {
    filter: blur(1px);
    box-shadow: 0 0 10px #fff;
    opacity: 0.8;
}


</style>
