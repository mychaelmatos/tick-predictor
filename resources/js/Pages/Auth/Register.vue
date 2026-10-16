<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Criar conta" />

        <h1 class="text-lg font-semibold">Criar conta</h1>
        <p class="mb-6 text-sm text-gray-400">Você começa com R$ 1.000,00 fictícios.</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Nome" />
                <TextInput id="name" type="text" class="mt-1" v-model="form.name" required autofocus autocomplete="name" />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="E-mail" />
                <TextInput id="email" type="email" class="mt-1" v-model="form.email" required autocomplete="username" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Senha" />
                <TextInput id="password" type="password" class="mt-1" v-model="form.password" required autocomplete="new-password" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirmar senha" />
                <TextInput id="password_confirmation" type="password" class="mt-1" v-model="form.password_confirmation" required autocomplete="new-password" />
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Criar conta
            </PrimaryButton>

            <p class="text-center text-sm text-gray-400">
                Já tem conta?
                <Link :href="route('login')" class="text-emerald-400 hover:text-emerald-300">Entrar</Link>
            </p>
        </form>
    </GuestLayout>
</template>
