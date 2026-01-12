<template>
    <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-primary-600">403</h1>
            <p class="mt-4 text-2xl font-semibold text-gray-700 dark:text-gray-200">Accès non autorisé</p>
            <p class="mt-2 text-gray-500 dark:text-gray-400">
                Désolé, vous n'avez pas les permissions nécessaires pour accéder à cette page.
            </p>
            <div class="mt-6">
                <a
                    :href="route('dashboard')"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    Retourner au tableau de bord
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
// Aucune logique spécifique n'est nécessaire ici pour un affichage simple.
// Le composant est purement présentationnel.
</script>

<style scoped>
/* Vous pouvez ajouter des styles spécifiques ici si nécessaire */
</style>
