<script setup>
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};

</script>

<template>
    <div class="bg-surface-50 light:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div>
                <div class="w-full bg-surface-0 light:bg-surface-900 py-10 px-8 sm:px-12 shadow-lg" style="border-radius: 10px">
                    <div class="text-center mb-8">
                        <img src="/assets/media/logos/logo.png" alt="" class="h-16 inline-block mb-4">
                        <div class="text-surface-900 light:text-surface-0 text-2xl font-medium mb-2">Bienvenue sur MaintVE</div>
                        <span class="text-muted-color font-medium text-sm">Connectez-vous pour continuer</span>
                    </div>

                    <form @submit.prevent="submit">
                        <div>
                            <label for="email1" class="block text-surface-900 light:text-surface-0 text-base font-medium mb-2">Adresse e-mail</label>
                            <InputText id="email1" type="text" placeholder="Adresse e-mail" class="w-full w-[32rem] mb-4" v-model="form.email" />
                            <Message v-if="form.errors.email" class="mb-4" severity="error">{{ form.errors.email }}</Message>

                            <label for="password1" class="block text-surface-900 light:text-surface-0 font-medium text-base mb-2">Password</label>
                            <Password id="password1" v-model="form.password" placeholder="Password" :toggleMask="true" class="w-full w-[32rem] mb-4" fluid :feedback="false"></Password>
                            <Message v-if="form.errors.password" class="mb-4" severity="error">{{ form.errors.password }}</Message>

                            <div class="flex items-center justify-between mt-2 mb-8 gap-8">
                                <div class="flex items-center">
                                    <Checkbox v-model="form.remember" id="rememberme1" binary class="mr-2"></Checkbox>
                                    <label for="rememberme1">Se souvenir de moi</label>
                                </div>
                                <a class="font-medium no-underline ml-2 text-right cursor-pointer text-primary">Mot de passe oublié ?</a>

                            </div>
                               <Button type="submit" label="Se connecter" class="w-full" :loading="form.processing"></Button>
                               <div class="my-4 flex items-center">
                <div class="flex-grow border-t border-gray-300"></div>
                <span class="mx-4 flex-shrink text-sm text-gray-500">OU</span>
                <div class="flex-grow border-t border-gray-300"></div>
            </div>
                            <div class="flex flex-col gap-4 mb-4">
                                <a :href="route('socialite.redirect', 'google')" class="no-underline">
                                    <Button label="Se connecter avec Google" icon="pi pi-google" class="p-button-outlined p-button-secondary w-full"></Button>
                                </a>
                                <a :href="route('socialite.redirect', 'facebook')" class="no-underline">
                                    <Button label="Se connecter avec Facebook" icon="pi pi-facebook" class="p-button-outlined p-button-secondary w-full"></Button>
                                </a>
                                <a href="/login/linkedin" class="no-underline d-none">
                                    <Button label="Se connecter avec LinkedIn" icon="pi pi-linkedin" class="p-button-outlined p-button-secondary w-full"></Button>
                                </a>
                            </div>


                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
