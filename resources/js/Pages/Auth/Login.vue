<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    demo: Object, // email e senha da conta de teste (vêm do config, criada pelo seeder)
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// Preenche com a conta de teste e entra. É um projeto de demonstração: ninguém precisa decorar senha.
const loginAsDemo = () => {
    form.email = props.demo.email;
    form.password = props.demo.password;
    submit();
};
</script>

<template>
    <GuestLayout>
        <Head title="Entrar" />

        <h1 class="text-lg font-semibold">Entrar</h1>
        <p class="mb-6 text-sm text-gray-400">Use sua conta para começar a jogar.</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="E-mail" />
                <TextInput id="email" type="email" class="mt-1" v-model="form.email" required autofocus autocomplete="username" />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Senha" />
                <TextInput id="password" type="password" class="mt-1" v-model="form.password" required autocomplete="current-password" />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <label class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="ms-2 text-sm text-gray-400">Lembrar de mim</span>
            </label>

            <PrimaryButton class="w-full" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Entrar
            </PrimaryButton>

            <!-- conta de teste: um clique para entrar -->
            <div class="border border-dashed border-gray-700 p-3 text-sm">
                <div class="text-xs uppercase tracking-wide text-gray-500">Conta de teste</div>
                <div class="mt-1 text-gray-400">
                    e-mail <span class="text-gray-200">{{ demo.email }}</span> · senha <span class="text-gray-200">{{ demo.password }}</span>
                </div>
                <button
                    type="button"
                    :disabled="form.processing"
                    @click="loginAsDemo"
                    class="mt-2 w-full border border-emerald-600 py-1.5 text-xs font-semibold uppercase tracking-widest text-emerald-400 hover:bg-emerald-600/10 disabled:opacity-50"
                >
                    Entrar com a conta de teste
                </button>
            </div>

            <p class="text-center text-sm text-gray-400">
                Não tem conta?
                <Link :href="route('register')" class="text-emerald-400 hover:text-emerald-300">Criar conta</Link>
            </p>
        </form>
    </GuestLayout>
</template>
