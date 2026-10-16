<script setup>
import TickChart from '@/Components/TickChart.vue';
import { useDerivTicks } from '@/composables/useDerivTicks';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const deriv = usePage().props.deriv;
const { ticks, pipSize, connected } = useDerivTicks(deriv.wsUrl, deriv.symbol);
const last = computed(() => ticks.value.at(-1)?.quote);
</script>

<template>
    <div class="flex min-h-screen bg-gray-950 text-gray-100">
        <!-- esquerda: formulário -->
        <div class="flex w-full flex-col justify-center border-r border-gray-800 bg-gray-900 px-8 py-10 lg:w-[420px] lg:px-12">
            <div class="mb-8 text-2xl font-bold tracking-tight">
                Tick<span class="text-emerald-400">Predictor</span>
            </div>
            <slot />
        </div>

        <!-- direita: mercado ao vivo -->
        <div class="hidden flex-1 flex-col justify-center p-12 lg:flex">
            <div class="mb-6 flex items-end justify-between">
                <div>
                    <div class="text-sm uppercase tracking-wide text-gray-500">{{ deriv.symbol }} · ao vivo</div>
                    <div class="text-5xl font-bold tabular-nums">{{ last != null ? last.toFixed(pipSize) : '—' }}</div>
                </div>
                <div class="text-sm text-gray-500">
                    <span class="mr-1 inline-block h-2 w-2 rounded-full" :class="connected ? 'bg-emerald-500' : 'bg-red-500'"></span>
                    {{ connected ? 'conectado à Deriv' : 'reconectando' }}
                </div>
            </div>
            <div class="h-72">
                <TickChart :ticks="ticks" :pip-size="pipSize" />
            </div>
            <p class="mt-8 max-w-md text-gray-400">
                Preveja se a cotação vai subir ou descer nos próximos 5 ticks. Saldo fictício, cotação real.
            </p>
        </div>
    </div>
</template>
