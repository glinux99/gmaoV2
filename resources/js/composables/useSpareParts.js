import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function useSpareParts(form, allSpareParts) {
    const { t } = useI18n();

    // --- STATE ---
    const sparePartDialogVisible = ref(false);
    const sparePartData = ref({
        ids: [],
        quantity: 1,
        index: -1,
        type: 'used' // 'used' or 'returned'
    });

    // --- COMPUTED ---

    /**
     * Calcule le coût total des pièces de rechange utilisées.
     */
    const serviceOrderCost = computed(() => {
        if (!form.spare_parts_used || !Array.isArray(allSpareParts.value)) {
            return 0;
        }
        return form.spare_parts_used.reduce((total, usedPart) => {
            const partDetails = allSpareParts.value.find(p => p.id === usedPart.id);
            const price = partDetails?.price || 0;
            return total + (price * usedPart.quantity);
        }, 0);
    });

    /**
     * Formate les pièces détachées pour le composant Dropdown/MultiSelect.
     */
    const sparePartOptions = computed(() => {
        if (!Array.isArray(allSpareParts.value)) {
            return [];
        }
        return allSpareParts.value.map(part => ({
            label: `${part.reference} (${part.name}) - Stock: ${part.stock}`,
            value: part.id
        }));
    });

    // --- METHODS ---

    /**
     * Ouvre la boîte de dialogue pour ajouter/modifier une pièce.
     * @param {string} type - 'used' ou 'returned'.
     * @param {object|null} part - La pièce à modifier.
     * @param {number} index - L'index de la pièce à modifier.
     */
    const openSparePartDialog = (type, part = null, index = -1) => {
        const isEditing = index > -1 && part;
        sparePartData.value = {
            ids: isEditing ? [part.id] : [],
            quantity: part ? part.quantity : 1,
            index: index,
            type: type
        };
        sparePartDialogVisible.value = true;
    };

    /**
     * Enregistre la pièce dans la liste correspondante (utilisée ou retournée).
     */
    const saveSparePart = () => {
        if (sparePartData.value.ids.length === 0) return;

        const target = sparePartData.value.type === 'used'
            ? form.spare_parts_used
            : form.spare_parts_returned;

        sparePartData.value.ids.forEach(id => {
            const exists = target.find(p => p.id === id);
            if (exists) {
                exists.quantity += sparePartData.value.quantity;
            } else {
                target.push({ id: id, quantity: sparePartData.value.quantity });
            }
        });

        sparePartDialogVisible.value = false;
    };

    const removeSparePart = (type, index) => {
        const target = type === 'used' ? form.spare_parts_used : form.spare_parts_returned;
        target.splice(index, 1);
    };

    const getSparePartReference = (id) => {
        if (!Array.isArray(allSpareParts.value)) return t('myActivities.unknownReference');
        const part = allSpareParts.value.find(p => p.id === id);
        return part ? part.reference : t('myActivities.unknownReference');
    };

    return {
        sparePartDialogVisible,
        sparePartData,
        serviceOrderCost,
        sparePartOptions,
        openSparePartDialog,
        saveSparePart,
        removeSparePart,
        getSparePartReference,
    };
}
