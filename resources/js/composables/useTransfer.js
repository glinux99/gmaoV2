import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

/**
 * useTransfer - Composable V11 Ultimate
 * Gère le transfert de stock (unitaire ou groupé) avec validation de statut flexible.
 */
export function useTransfer(options) {
    const {
        allItems,
        selectedItems,
        transferRouteName,
        idKey,
        stockStatus,
        translations
    } = options;

    const toast = useToast();
    const transferDialog = ref(false);
    const itemsToTransfer = ref([]);
    const transferSearchQuery = ref('');

    // Initialisation du formulaire Inertia
    const transferForm = useForm({
        [idKey]: [],
        region_id: null,
    });

    /**
     * Utilitaire de validation de statut (Gère String et Array)
     */
    const isValidStatus = (status) => {
        if (Array.isArray(stockStatus)) {
            return stockStatus.includes(status);
        }
        return status === stockStatus;
    };

    /**
     * Ouvre le dialogue de transfert
     * Filtre automatiquement les éléments invalides (déjà installés ou mauvais statut)
     */
    const openTransferDialog = () => {
        const initialSelection = [...selectedItems.value];

        // On ne garde que les éléments transférables
        itemsToTransfer.value = initialSelection.filter(item => {
            const statusOk = isValidStatus(item.status);
            const notInstalled = !item.installed_at_location_id;
            return statusOk && notInstalled;
        });

        // Notification si des éléments ont été écartés de la sélection
        const rejectedCount = initialSelection.length - itemsToTransfer.value.length;
        if (rejectedCount > 0) {
            toast.add({
                severity: 'warn',
                summary: translations.attention || 'Attention',
                detail: `${rejectedCount} élément(s) ignoré(s) car invalides ou déjà installés.`,
                life: 5000
            });
        }

        // Si aucun élément n'est transférable, on n'ouvre pas inutilement
        if (itemsToTransfer.value.length === 0 && initialSelection.length > 0) return;

        transferForm[idKey] = itemsToTransfer.value.map(item => item.id);
        transferSearchQuery.value = '';
        transferDialog.value = true;
    };

    /**
     * Recherche prédictive pour ajout manuel
     * Filtre les éléments déjà présents, installés ou ayant un mauvais statut
     */
    const availableItemsForTransfer = computed(() => {

        const query = (transferSearchQuery.value || '').toLowerCase();

        return allItems.value.filter(item => {
            const isMatch = query ? item.serial_number.toLowerCase().includes(query) : true;
            const isAlreadySelected = itemsToTransfer.value.some(ti => ti.id === item.id);

            const canBeTransferred =
                isValidStatus(item.status) &&
                item.installed_at_location_id === null;

            return isMatch && !isAlreadySelected ;
        });
    });

    /**
     * Ajoute un élément à la liste de transfert via l'AutoComplete
     */
    const addItemToTransfer = (event) => {
        const itemToAdd = event.value;
        if (itemToAdd && !itemsToTransfer.value.some(i => i.id === itemToAdd.id)) {
            itemsToTransfer.value.push(itemToAdd);
        }
        transferSearchQuery.value = '';
        transferForm[idKey] = itemsToTransfer.value.map(i => i.id);
    };

    /**
     * Retire un élément de la liste de transfert
     */
    const removeItemFromTransfer = (itemId) => {
        itemsToTransfer.value = itemsToTransfer.value.filter(i => i.id !== itemId);
        transferForm[idKey] = itemsToTransfer.value.map(i => i.id);
    };

    /**
     * Soumission du transfert au serveur
     */
    const confirmTransfer = () => {
        // Mise à jour finale des IDs
        transferForm[idKey] = itemsToTransfer.value.map(item => item.id);

        if (transferForm[idKey].length === 0) {
            toast.add({ severity: 'error', summary: 'Erreur', detail: 'La liste de transfert est vide.', life: 3000 });
            return;
        }

        if (!transferForm.region_id) {
            toast.add({
                severity: 'warn',
                summary: translations.attention,
                detail: translations.selectDestinationRegion,
                life: 3000
            });
            return;
        }

        // --- NOUVELLE VALIDATION ---
        // Vérifie si au moins un des éléments sélectionnés appartient déjà à la région de destination.
        const isTransferToSameRegion = itemsToTransfer.value.some(
            item => item.region_id === transferForm.region_id
        );

        if (isTransferToSameRegion) {
            toast.add({
                severity: 'error',
                summary: translations.transferError || 'Erreur de Transfert',
                detail: 'Vous ne pouvez pas transférer un ou plusieurs articles vers leur propre région d\'origine.', // Idéalement, ceci devrait aussi être une traduction
                life: 5000
            });
            return; // Bloque la soumission
        }

        transferForm.post(transferRouteName, {
            onSuccess: () => {
                transferDialog.value = false;
                selectedItems.value = []; // Vide la sélection de la DataTable
                itemsToTransfer.value = [];
                toast.add({
                    severity: 'success',
                    summary: translations.success,
                    detail: translations.transferSuccess(transferForm[idKey].length),
                    life: 3000
                });
            },
            onError: (errors) => {
                const errorDetails = Object.values(errors).join(', ');
                toast.add({
                    severity: 'error',
                    summary: translations.transferError,
                    detail: errorDetails,
                    life: 5000
                });
            }
        });
    };

    return {
        // États
        transferDialog,
        transferForm,
        itemsToTransfer,
        transferSearchQuery,
        // Computed
        availableItemsForTransfer,
        // Actions
        openTransferDialog,
        confirmTransfer,
        searchItemsForTransfer: (event) => { transferSearchQuery.value = event.query; },
        addItemToTransfer,
        removeItemFromTransfer,
    };
}
