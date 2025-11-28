<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
   import AppLayout from "@/sakai/layout/AppLayout.vue";
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import DangerButton from '@/Components/DangerButton.vue';

// Props reçues du contrôleur Laravel
const props = defineProps({
    technicians: Array,
    regions: Array, // Assuming regions are passed as props
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const isDeleteModalOpen = ref(false);
const technicianToDelete = ref(null);

// Formulaire Inertia pour la création et la modification
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
});

const fonctionOptions = ref([]);

const defaultFonctions = [
    { id: 'technicien_journalier', name: 'Technicien Journalier' },
    { id: 'technicien_stagiaire', name: 'Technicien Stagiaire' },
    { id: 'technicien_chef_equipe', name: 'Technicien Chef d\'équipe' },
    { id: 'superviseur', name: 'Superviseur' },
    { id: 'chef_de_travaux', name: 'Chef de Travaux' },
    { id: 'superviseur_maintenance', name: 'Superviseur Maintenance' },
    { id: 'superviseur_raccordement', name: 'Superviseur Raccordement' },
    { id: 'superviseur_intervention', name: 'Superviseur Intervention' },
    { id: 'responsable_reseau', name: 'Responsable Réseau' },
    { id: 'responsable_centrale', name: 'Responsable Centrale' },
];

const loadFonctionOptions = () => {
    const localFonctions = JSON.parse(localStorage.getItem('customFonctions') || '[]');
    const mergedFonctions = [...defaultFonctions];

    localFonctions.forEach(localFonction => {
        if (!mergedFonctions.some(mf => mf.name === localFonction.name)) {
            mergedFonctions.push(localFonction);
        }
    });
    fonctionOptions.value = mergedFonctions;
};

onMounted(() => {
    loadFonctionOptions();
});

// Ouvre la modale pour créer un technicien
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    isModalOpen.value = true;
};

// Ouvre la modale pour modifier un technicien
const openEditModal = (technician) => {
    isEditing.value = true;
    form.id = technician.id;
    form.name = technician.name;
    form.email = technician.email;
    form.fonction = technician.fonction;
    form.numero = technician.numero;
    form.region = technician.region;
    form.pointure = technician.pointure;
    form.size = technician.size;
    form.password = '';
    form.password_confirmation = '';
    form.profile_photo = null; // Réinitialiser le champ de fichier
    form.profile_photo_preview = technician.profile_photo || null; // Afficher la photo existante
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

// Soumet le formulaire (création ou mise à jour)
const submit = () => {
    const url = isEditing.value ? route('technicians.update', form.id) : route('technicians.store');
    const method = isEditing.value ? 'put' : 'post';
    console.log(form);
    form.submit(method, url, {

        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            // Optionally add a toast notification here if you have a toast service
            // toast.add({ severity: 'success', summary: 'Succès', detail: `Technicien ${isEditing.value ? 'mis à jour' : 'créé'} avec succès`, life: 3000 });
        },
        onError: (errors) => {
            console.error("Erreur lors de la sauvegarde du technicien", errors);
            // Optionally add a toast notification here for errors
            // toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de la sauvegarde.', life: 3000 });
        }
    });
};

// Ouvre la modale de confirmation de suppression
const openDeleteModal = (technician) => {
    technicianToDelete.value = technician;
    isDeleteModalOpen.value = true;
};

// Ferme la modale de confirmation de suppression
const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    technicianToDelete.value = null;
};

// Supprime un technicien
const deleteTechnician = () => {
    router.delete(route('technicians.destroy', technicianToDelete.value.id), { onSuccess: () => closeDeleteModal() });
};

// Gérer le changement de fichier pour la photo de profil
const handleProfilePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.profile_photo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            form.profile_photo_preview = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};
</script>

<template>
    <AppLayout title="Techniciens">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestion des Techniciens
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h1 class="text-2xl font-medium text-gray-900">
                                Liste des Techniciens
                            </h1>
                            <PrimaryButton @click="openCreateModal" severity="warn">
                                <i class="pi pi-plus mr-2"></i>
                                Créer un technicien
                            </PrimaryButton>

                        </div>

                        <!-- Grille des techniciens -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="tech in technicians" :key="tech.id" class="p-4 border rounded-lg shadow-sm">
                                <div class="flex items-center space-x-4">
                                    <div v-if="!tech.profile_photo" class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                                        <i class="pi pi-user text-2xl text-gray-500"></i>
                                    </div>
                                    <div v-else><img  :src="tech.profile_photo" alt="Profile" class="w-20 h-20  rounded-full object-cover"></div>

                                    <h3 class="text-lg font-semibold">{{ tech.name }}</h3>
                                </div>
                                <p class="text-sm text-gray-600">{{ tech.email }}</p>
                                <div class="mt-2 text-sm">
                                    <p v-if="tech.fonction"><strong>Fonction:</strong> {{ tech.fonction }}</p>
                                    <p v-if="tech.numero"><strong>Numéro:</strong> {{ tech.numero }}</p>
                                    <p v-if="tech.region"><strong>Région:</strong> {{ tech.region }}</p>
                                    <p v-if="tech.pointure"><strong>Pointure:</strong> {{ tech.pointure }}</p>
                                    <p v-if="tech.size"><strong>Taille:</strong> {{ tech.size }}</p>
                                </div>
                                <div class="mt-4">
                                    <SecondaryButton @click="openEditModal(tech)" class="p-button-sm">
                                        Modifier
                                    </SecondaryButton>
                                    <DangerButton @click="openDeleteModal(tech)" class="p-button-sm ml-2">
                                        Supprimer
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale de Création/Modification -->
        <Dialog v-model:visible="isModalOpen" modal :header="isEditing ? 'Modifier le technicien' : 'Créer un nouveau technicien'" :style="{ width: '40rem' }" class="p-fluid">
               <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <span v-if="isEditing" class="text-surface-500 dark:text-surface-400 block mb-8">Mettez à jour les informations du technicien.</span>
                    <div>
                        <InputLabel for="name" value="Nom" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
  <div>
 <InputLabel for="fonction" value="Fonction" />
 <Dropdown id="fonction" v-model="form.fonction" :options="fonctionOptions" optionLabel="name" optionValue="name"
 placeholder="Sélectionner une fonction" class="mt-1 block w-full" filter />
 <InputError :message="form.errors.fonction" class="mt-2" />
                    </div></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template v-if="!isEditing || isEditing">
                            <div>
                                <InputLabel for="password" value="Mot de passe" />
                                <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full"  />
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full"  />
                                <InputError :message="form.errors.password_confirmation" class="mt-2" />
                            </div>
                        </template>
                    </div>

                    <!-- Champs additionnels -->

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="numero" value="Numéro" />
                        <TextInput id="numero" v-model="form.numero" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.numero" class="mt-2" />
                    </div>

                    <div>
 <InputLabel for="region" value="Région" />
 <Dropdown id="region" v-model="form.region" :options="props.regions" optionLabel="designation" optionValue="designation"
 placeholder="Sélectionner une région" class="mt-1 block w-full" />
                        <InputError :message="form.errors.region" class="mt-2" />
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
                    <div class="flex flex-col items-center">
                        <InputLabel for="profile_photo" value="Photo de profil" />
                        <div v-if="form.profile_photo_preview" class="mt-2">
                            <img :src="form.profile_photo_preview" alt="Profile Photo Preview" class="w-24 h-24 object-cover rounded-full" />
                        </div>
                        <!-- Afficher la photo existante lors de la modification -->

                        <input id="profile_photo" type="file" @change="handleProfilePhotoChange" class="mt-1 block w-full" />
                        <InputError :message="form.errors.profile_photo" class="mt-2" />
                    </div>

                    <!-- Actions du formulaire -->
                    <div class="flex items-center justify-end mt-6">
                        <SecondaryButton @click="closeModal">
                            Annuler
                        </SecondaryButton>

                        <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            {{ isEditing ? 'Mettre à jour' : 'Créer' }}
                        </PrimaryButton>
                    </div>
                </form>
        </Dialog>

        <!-- Modale de Confirmation de Suppression -->
        <Dialog v-model:visible="isDeleteModalOpen" modal header="Confirmer la suppression" :style="{ width: '30rem' }">
            <div class="p-fluid">
                <p>Êtes-vous sûr de vouloir supprimer le {{  technicianToDelete?.fonction }} <strong>{{ technicianToDelete?.name }}</strong> ?</p>
                <div class="flex justify-end mt-6">
                    <SecondaryButton @click="closeDeleteModal" class="mr-2">
                        Annuler
                    </SecondaryButton>
                    <DangerButton @click="deleteTechnician">
                        Supprimer
                    </DangerButton>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Ajoutez ici des styles spécifiques si nécessaire */
</style>
