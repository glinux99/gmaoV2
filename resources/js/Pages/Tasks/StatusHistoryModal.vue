<template>
    <Modal :show="show" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Historique des statuts pour : {{ maintenance.title }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Visualisez tous les changements de statut pour cette maintenance.
            </p>

            <div class="mt-6 space-y-4">
                <div v-if="maintenance.status_histories && maintenance.status_histories.length > 0">
                    <ul class="divide-y divide-gray-200">
                        <li v-for="history in maintenance.status_histories" :key="history.id" class="py-4 flex">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">
                                    De <span class="font-bold">{{ history.old_status || 'N/A' }}</span> à <span class="font-bold">{{ history.new_status }}</span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Par {{ history.user ? history.user.name : 'Système' }} le {{ new Date(history.created_at).toLocaleString() }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div v-else>
                    <p class="text-sm text-gray-500">Aucun historique de changement de statut pour cette maintenance.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">
                    Fermer
                </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maintenance: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['close']);

const closeModal = () => {
    emit('close');
};
</script>
