<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Paginator from 'primevue/paginator';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
  technicians: Object, // pagination Laravel { data, links, meta }
  regions: Array,
  filters: Object,
});

// Recherche serveur
const search = ref(props.filters?.search || '');
let debounceId = null;

// Toutes les colonnes disponibles
const allColumns = ref([
  { field: 'name', header: 'Technicien' },
  { field: 'fonction', header: 'Fonction' },
  { field: 'region', header: 'Région' },
  { field: 'numero', header: 'Numéro' },
  { field: 'pointure', header: 'Pointure' },
  { field: 'size', header: 'Taille' },
  { field: 'email', header: 'Email' },
  { field: 'extra_attributes', header: 'Autres caractéristiques' },
]);
// Colonnes pour MultiSelect (à définir si ce n'est pas déjà fait)

// Référence pour l'OverlayPanel
const op = ref();

// Fonction pour afficher l'OverlayPanel
const toggleColumnSelection = (event) => {
    op.value.toggle(event);
};
// Colonnes visibles par défaut
const visibleColumns = ref(allColumns.value.slice(0, 4).map(col => col.field)); // Affiche les 4 premières par défaut

const performSearch = () => {
  clearTimeout(debounceId);
  debounceId = setTimeout(() => {
    router.get(route('technicians.index'), { search: search.value }, { preserveState: true, replace: true });
  }, 300);
};

// Liste paginée
const techList = computed(() => props.technicians?.data || []);
const links = computed(() => props.technicians?.links || []);
const totalRecords = computed(() => props.technicians?.meta?.total || 0);
const currentPage = computed(() => props.technicians?.meta?.current_page || 1);
const perPage = computed(() => props.technicians?.meta?.per_page || 10);

const onPage = (event) => {
  router.get(route('technicians.index'), { page: event.page + 1, per_page: event.rows, search: search.value }, { preserveState: true, replace: true });
};


// Sélection multiple
const selected = ref([]);
const bulkDisabled = computed(() => !selected.value || selected.value.length < 2);

// Modales
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isImportModalOpen = ref(false);
const toDelete = ref(null);
const importFile = ref(null);

// Formulaire création/édition
const form = useForm({
  id: null,
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  fonction: null,
  numero: '',
  region: '',
  pointure: '',
  size: '',
  profile_photo: null,
  profile_photo_preview: null,
  extra_attributes: [], // [{ key:'', value:'' }]
});

// Options dropdown
const fonctionOptions = [
  'Technicien Journalier',
  'Technicien Stagiaire',
  "Technicien Chef d'équipe",
  'Superviseur',
  'Chef de Travaux',
  'Superviseur Maintenance',
  'Superviseur Raccordement',
  'Superviseur Intervention',
  'Responsable Réseau',
  'Responsable Centrale',
].map(name => ({ label: name, value: name }));

const regionOptions = computed(() => (props.regions || []).map(r => ({ label: r.designation, value: r.designation })));

// Modale création
const openCreate = () => {
  isModalOpen.value = true;
  form.reset();
  form.extra_attributes = [];
  form.profile_photo = null;
  form.profile_photo_preview = null;
};

// Modale édition
const openEdit = (t) => {
  isModalOpen.value = true;
  form.clearErrors();
  form.id = t.id;
  form.name = t.name;
  form.email = t.email;
  form.fonction = t.fonction;
  form.numero = t.numero;
  form.region = t.region;
  form.pointure = t.pointure;
  form.size = t.size;
  form.password = '';
  form.password_confirmation = '';
  form.profile_photo = null;
  form.profile_photo_preview = t.profile_photo_url || null;
  // extra_attributes si présent (objet -> tableau clé/valeur)
  if (t.extra_attributes && typeof t.extra_attributes === 'object' && !Array.isArray(t.extra_attributes)) {
    form.extra_attributes = Object.entries(t.extra_attributes).map(([key, value]) => ({ key, value }));
  } else if (Array.isArray(t.extra_attributes)) {
    form.extra_attributes = t.extra_attributes;
  } else {
    form.extra_attributes = [];
  }
};

const closeModal = () => { isModalOpen.value = false; };

// Gérer upload photo
const handlePhotoChange = (e) => {
  const file = e.target.files?.[0];
  if (file) {
    form.profile_photo = file;
    const reader = new FileReader();
    reader.onload = (ev) => { form.profile_photo_preview = ev.target.result; };
    reader.readAsDataURL(file);
  }
};

// Repeater caractéristiques
const addExtra = () => form.extra_attributes.push({ key: '', value: '' });
const removeExtra = (i) => form.extra_attributes.splice(i, 1);

// Soumission création/édition
const submit = () => {
  const url = form.id ? route('technicians.update', form.id) : route('technicians.store');
  form.transform((data) => {
    const fd = new FormData();
    if (data.id) fd.append('_method', 'PUT');
    const simple = ['name','email','password','password_confirmation','fonction','numero','region','pointure','size'];
    simple.forEach(k => { if (data[k] !== undefined && data[k] !== null && data[k] !== '') fd.append(k, data[k]); });
    // extra_attributes en tableau: extra_attributes[0][key], [0][value]
    (data.extra_attributes || []).forEach((item, idx) => {
      if ((item.key ?? '') !== '') {
        fd.append(`extra_attributes[${idx}][key]`, item.key);
        fd.append(`extra_attributes[${idx}][value]`, item.value ?? '');
      }
    });
    if (data.profile_photo instanceof File) fd.append('profile_photo', data.profile_photo);
    return fd;
  }).post(url, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => { closeModal(); },
  });
};

// Suppression simple
const confirmDelete = (t) => { toDelete.value = t; isDeleteModalOpen.value = true; };
const doDelete = () => {
  router.delete(route('technicians.destroy', toDelete.value.id), { onSuccess: () => { isDeleteModalOpen.value = false; } });
};

// Suppression multiple
const bulkDelete = () => {
  if (!selected.value.length) return;
  if (!confirm(`Supprimer ${selected.value.length} technicien(s) sélectionné(s) ?`)) return;
  router.post(route('technicians.bulkdestroy'), { ids: selected.value.map(t => t.id) });
};

// Export/Import
const dt = ref();
const exportCSV = () => {
    dt.value.exportCSV();
};

const openImport = () => { isImportModalOpen.value = true; importFile.value = null; };
const doImport = () => {
  if (!importFile.value) return;
  const fd = new FormData(); fd.append('file', importFile.value);
  router.post(route('technicians.import'), fd, { forceFormData: true, onSuccess: () => { isImportModalOpen.value = false; } });
};
</script>

<template>
  <AppLayout title="Techniciens">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Techniciens</h2>
    </template>

        <div class="grid">
            <div class="col-12">
                <div class="card">
                    <Toast />
                    <ConfirmDialog></ConfirmDialog>
                   <Toolbar class="mb-4">
    <template #start>
        <div class="flex items-center gap-2">
            <Button
                label="Nouveau Technicien"
                icon="pi pi-plus"
                @click="openCreate"
                class="p-button-primary"
            />
            <Button
                label="Importer"
                icon="pi pi-upload"
                @click="openImport"
                class="p-button-success p-button-outlined"
            />
            <Button
                :label="`Supprimer (${selected.length || 0})`"
                icon="pi pi-trash"
                @click="bulkDelete"
                :disabled="bulkDisabled"
                class="p-button-danger"
            />
        </div>
    </template>

    <template #end>
        <div class="flex items-center gap-2">
            <IconField>
                <InputIcon>
                    <i class="pi pi-search" />
                </InputIcon>
                <InputText
                    v-model="search"
                    placeholder="Rechercher..."
                    @input="performSearch"
                />
            </IconField>

            <Button
                label="Exporter"
                icon="pi pi-download"
                class="p-button-help"
                @click="exportCSV($event)"
            />

    <div class="flex items-center gap-2">
        <Button
            icon="pi pi-ellipsis-v"
            class="p-button-secondary p-button-text"
            @click="toggleColumnSelection"
            aria-haspopup="true"
            aria-controls="column_op"
        />

        <OverlayPanel ref="op" appendTo="body" id="column_op" class="p-4">
            <div class="font-semibold mb-3">Sélectionner les colonnes :</div>

            <MultiSelect
                v-model="visibleColumns"
                :options="allColumns"
                optionLabel="header"
                optionValue="field"
                display="chip"
                placeholder="Choisir les colonnes"
                class="w-full max-w-xs"  />
        </OverlayPanel>
    </div>
        </div>
    </template>
</Toolbar>
                        <DataTable :value="techList"  ref="dt"  dataKey="id" :paginator="true" :rows="10" v-model:selection="selected" selectionMode="multiple" responsiveLayout="scroll" stripedRows >
              <Column selectionMode="multiple" headerStyle="width: 3rem" :exportable="false"></Column>
              <Column v-if="visibleColumns.includes('name')" field="name" header="Technicien" :sortable="true">
                <template #body="{ data }">
                  <div class="flex items-center gap-3">
                    <Avatar :image="data.profile_photo_url || '/assets/media/avatars/blank.png'" :label="!data.profile_photo_url ? (data.name?.charAt(0) || '') : ''" shape="circle" size="large" />
                    <div>
                      <div class="font-semibold">{{ data.name }}</div>
                      <div class="text-sm text-gray-500">{{ data.email }}</div>
                    </div>
                  </div>
                </template>
              </Column>
              <Column v-if="visibleColumns.includes('fonction')" field="fonction" header="Fonction" :sortable="true" style="min-width: 10rem;"></Column>
              <Column v-if="visibleColumns.includes('region')" field="region" header="Région" :sortable="true" style="min-width: 10rem;"></Column>
              <Column v-if="visibleColumns.includes('numero')" field="numero" header="Numéro" style="min-width: 10rem;"></Column>
              <Column v-if="visibleColumns.includes('pointure')" field="pointure" header="Pointure" :sortable="true" style="min-width: 8rem;"></Column>
              <Column v-if="visibleColumns.includes('size')" field="size" header="Taille" :sortable="true" style="min-width: 8rem;"></Column>
              <Column v-if="visibleColumns.includes('email')" field="email" header="Email" :sortable="true" style="min-width: 15rem;"></Column>
              <Column v-if="visibleColumns.includes('extra_attributes')" header="Autres caractéristiques" class="hidden md:table-cell">
                <template #body="{ data }">
                  <div v-if="data.extra_attributes && Object.keys(data.extra_attributes).length > 0">
                    <ul class="list-disc list-inside">
                      <li v-for="(value, key) in data.extra_attributes" :key="key">
                        <strong>{{ key }}:</strong> {{ value }}
                      </li>
                    </ul>
                  </div>
                  <div v-else>
                    N/A
                  </div>
                </template>
              </Column>
              <Column header="Actions" :exportable="false" style="min-width: 9rem">
                <template #body="{ data }">
                  <SecondaryButton class="mr-2" @click="openEdit(data)"><i class="pi pi-pencil"></i></SecondaryButton>
                  <DangerButton @click="confirmDelete(data)"><i class="pi pi-trash"></i></DangerButton>
                </template>
              </Column>
            </DataTable>
            <Paginator v-if="totalRecords > perPage" :rows="perPage" :totalRecords="totalRecords" :rowsPerPageOptions="[5, 10, 20, 50]" @page="onPage" :first="(currentPage - 1) * perPage"></Paginator>
                </div>
            </div>
            </div>


    <!-- Modale création/édition -->
    <Dialog v-model:visible="isModalOpen" modal :header="form.id ? 'Modifier le technicien' : 'Créer un technicien'" :style="{ width: '50rem' }" class="p-fluid">
      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <InputLabel for="name" value="Nom" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
            <InputError :message="form.errors.name" class="mt-2" />
          </div>
          <div>
            <InputLabel for="email" value="Email" />
            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
            <InputError :message="form.errors.email" class="mt-2" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <InputLabel for="fonction" value="Fonction" />
            <Dropdown id="fonction" v-model="form.fonction" :options="fonctionOptions" optionLabel="label" optionValue="value" placeholder="Sélectionner une fonction" class="mt-1 block w-full" filter />
            <InputError :message="form.errors.fonction" class="mt-2" />
          </div>
          <div>
            <InputLabel for="region" value="Région" />
            <Dropdown id="region" v-model="form.region" :options="regionOptions" optionLabel="label" optionValue="value" placeholder="Sélectionner une région" class="mt-1 block w-full" filter />
            <InputError :message="form.errors.region" class="mt-2" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <InputLabel for="numero" value="Numéro" />
            <TextInput id="numero" v-model="form.numero" type="text" class="mt-1 block w-full" />
            <InputError :message="form.errors.numero" class="mt-2" />
          </div>
          <div>
            <InputLabel for="pointure" value="Pointure" />
            <TextInput id="pointure" v-model="form.pointure" type="text" class="mt-1 block w-full" />
            <InputError :message="form.errors.pointure" class="mt-2" />
          </div>
          <div>
            <InputLabel for="size" value="Taille (vêtements)" />
            <TextInput id="size" v-model="form.size" type="text" class="mt-1 block w-full" />
            <InputError :message="form.errors.size" class="mt-2" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <InputLabel for="password" :value="form.id ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe'" />
            <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" :required="!form.id" />
            <InputError :message="form.errors.password" class="mt-2" />
          </div>
          <div>
            <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
            <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" :required="!form.id" />
            <InputError :message="form.errors.password_confirmation" class="mt-2" />
          </div>
        </div>

        <div class="mt-2">
          <InputLabel value="Caractéristiques additionnelles" />
          <div v-for="(item, idx) in form.extra_attributes" :key="idx" class="flex gap-2 mt-2">
            <TextInput v-model="item.key" placeholder="Clé" class="flex-1" />
            <TextInput v-model="item.value" placeholder="Valeur" class="flex-1" />
            <DangerButton type="button" @click="removeExtra(idx)"><i class="pi pi-trash"></i></DangerButton>
          </div>
          <SecondaryButton type="button" class="mt-2" @click="addExtra"><i class="pi pi-plus mr-2"></i>Ajouter une caractéristique</SecondaryButton>
        </div>

        <div class="mt-4 p-4 border rounded">
          <InputLabel value="Photo de profil" />
          <div class="flex items-center gap-4 mt-2">
            <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden">
              <img v-if="form.profile_photo_preview" :src="form.profile_photo_preview" class="w-full h-full object-cover" />
              <i v-else class="pi pi-user text-2xl text-gray-400"></i>
            </div>
            <input type="file" accept="image/*" @change="handlePhotoChange" />
          </div>
          <InputError :message="form.errors.profile_photo" class="mt-2" />
        </div>

        <div class="flex justify-end gap-2 mt-6">
          <SecondaryButton type="button" @click="closeModal">Annuler</SecondaryButton>
          <PrimaryButton type="submit">{{ form.id ? 'Mettre à jour' : 'Créer' }}</PrimaryButton>
        </div>
      </form>
    </Dialog>

    <!-- Modale suppression -->
    <Dialog v-model:visible="isDeleteModalOpen" modal header="Confirmer la suppression" :style="{ width: '30rem' }">
      <div>
        <p>Supprimer le technicien <strong>{{ toDelete?.name }}</strong> ?</p>
        <div class="flex justify-end gap-2 mt-4">
          <SecondaryButton @click="isDeleteModalOpen = false">Annuler</SecondaryButton>
          <DangerButton @click="doDelete">Supprimer</DangerButton>
        </div>
      </div>
    </Dialog>

    <!-- Modale import -->
    <Dialog v-model:visible="isImportModalOpen" modal header="Importer des techniciens (CSV)" :style="{ width: '30rem' }">
      <div class="p-fluid">
        <input type="file" accept=".csv,text/csv" @change="e => importFile.value = e.target.files?.[0]" />
        <div class="flex justify-end gap-2 mt-4">
          <SecondaryButton @click="isImportModalOpen = false">Annuler</SecondaryButton>
          <PrimaryButton @click="doImport">Importer</PrimaryButton>
        </div>
      </div>
    </Dialog>
  </AppLayout>
</template>

<style scoped>
    .p-overlaypanel {
    max-width: 100vw !important;
}
.p-overlaypanel-content {
    max-width: 100vw !important;
    box-sizing: border-box; /* S'assure que le padding est inclus dans la largeur */
}
</style>
