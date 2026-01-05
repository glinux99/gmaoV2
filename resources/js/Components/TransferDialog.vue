<script setup>
import { defineProps, defineEmits } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import AutoComplete from 'primevue/autocomplete';
import Tag from 'primevue/tag';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    itemsToTransfer: {
        type: Array,
        required: true,
    },
    availableItemsForTransfer: {
        type: Array,
        required: true,
    },
regions: {
        type: Array,
        required: true,
    },
    searchQuery: String,
    config: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits([
    'update:visible',
    'update:searchQuery',
    'confirm',
    'search',
    'addItem',
    'removeItem',
]);

const onUpdateVisible = (value) => emit('update:visible', value);
const onUpdateSearchQuery = (event) => emit('update:searchQuery', event.target.value);
const onConfirm = () => emit('confirm');
const onSearch = (event) => emit('search', event);
const onAddItem = (event) => emit('addItem', event);
const onRemoveItem = (itemId) => emit('removeItem', itemId);

</script>

<template>
    <Dialog
        :visible="props.visible"
        @update:visible="onUpdateVisible"
        modal :header="false" :closable="false"
        :style="{ width: '95vw', maxWidth: '600px' }"
        :pt="{ root: { class: 'rounded-[3rem] overflow-hidden border-none shadow-2xl' }, mask: { style: 'backdrop-filter: blur(8px)' } }"
    >
        <div class="px-8 py-5 bg-slate-900 text-white flex justify-between items-center relative z-50 rounded-xl">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-blue-500/20 rounded-xl border border-blue-500/30">
                    <i class="pi pi-arrows-h text-blue-400 text-xl"></i>
                </div>
                <div class="flex flex-col">
                    <h2 class="text-sm font-black uppercase tracking-[0.15em] text-white leading-none">{{ props.config.title }}</h2>
                    <span class="text-[9px] text-blue-300 font-bold uppercase tracking-tighter mt-1.5 opacity-80 italic">{{ props.config.subtitle }}</span>
                </div>
            </div>
            <Button icon="pi pi-times" variant="text" severity="secondary" rounded @click="onUpdateVisible(false)" class="text-white hover:bg-white/10" />
        </div>

        <div class="p-8 bg-white max-h-[70vh] overflow-y-auto scroll-smooth space-y-6">
            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ props.config.addBySerialLabel }}</label>
                <AutoComplete :modelValue="props.searchQuery" @update:modelValue="onUpdateSearchQuery" :suggestions="props.availableItemsForTransfer" @complete="onSearch" @item-select="onAddItem" field="serial_number" :placeholder="props.config.searchPlaceholder" class="w-full" inputClass="p-inputtext-lg">
                    <template #option="slotProps">
                        <div class="flex items-center justify-between">
                            <span>{{ slotProps.option.serial_number }}</span>
                            <Tag :value="props.config.itemStatusLabel" severity="info" />
                        </div>
                    </template>
                </AutoComplete>
            </div>

            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">{{ props.config.itemCountLabel(props.itemsToTransfer.length) }}</h4>
                <div class="max-h-48 overflow-y-auto space-y-2 pr-2">
                    <div v-for="item in props.itemsToTransfer" :key="item.id" class="flex items-center justify-between bg-white p-2 rounded-lg border border-slate-200">
                        <span class="text-sm font-bold text-slate-700">{{ item.serial_number }}</span>
                        <Button icon="pi pi-times" text rounded severity="danger" @click="onRemoveItem(item.id)" class="w-6 h-6" />
                    </div>
                    <div v-if="props.itemsToTransfer.length === 0" class="text-center py-4 text-slate-400 italic text-xs">{{ props.config.noItemsSelectedLabel }}</div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">{{ props.config.destinationRegionLabel }}</label>
                <Dropdown v-model="props.form.region_id" :options="props.regions" optionLabel="designation" optionValue="id" filter class="w-full" :placeholder="props.config.regionPlaceholder" :invalid="props.form.errors.region_id" />
                <small class="p-error" v-if="props.form.errors.region_id">{{ props.form.errors.region_id }}</small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full px-8 py-5 bg-slate-50 border-t border-slate-100">
                <Button :label="props.config.cancelLabel" icon="pi pi-times" text severity="secondary" @click="onUpdateVisible(false)" class="font-bold uppercase text-[10px] tracking-widest" />
                <Button :label="props.config.confirmLabel" icon="pi pi-check-circle" @click="onConfirm" :loading="props.form.processing" class="px-10 h-14 rounded-2xl shadow-xl shadow-indigo-100 font-black uppercase tracking-widest text-xs" :disabled="props.itemsToTransfer.length === 0" />
            </div>
        </template>
    </Dialog>
</template>
