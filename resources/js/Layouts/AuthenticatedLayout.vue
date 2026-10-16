<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gray-950 text-gray-100">
        <nav class="border-b border-gray-800 bg-gray-900">
            <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link :href="route('dashboard')" class="text-lg font-bold tracking-tight">
                    Tick<span class="text-emerald-400">Predictor</span>
                </Link>

                <div class="flex items-center gap-5 text-sm">
                    <Link
                        :href="route('dashboard')"
                        :class="route().current('dashboard') ? 'text-white' : 'text-gray-400 hover:text-white'"
                    >
                        Jogo
                    </Link>
                    <Link
                        :href="route('profile.edit')"
                        :class="route().current('profile.edit') ? 'text-white' : 'text-gray-400 hover:text-white'"
                    >
                        Perfil
                    </Link>
                    <span class="hidden text-gray-500 sm:inline">{{ $page.props.auth.user.name }}</span>
                    <Link :href="route('logout')" method="post" as="button" class="text-gray-400 hover:text-white">
                        Sair
                    </Link>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="border-b border-gray-800 bg-gray-900/50">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
