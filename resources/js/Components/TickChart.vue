<script setup>
import { computed } from 'vue';

// Uma coluna por tick. Verde se subiu em relação ao tick ANTERIOR, vermelha se caiu.
// Atenção: a cor não diz se a aposta ganhou. Quem decide é o 5º tick comparado à entrada.
const props = defineProps({
    ticks: { type: Array, required: true }, // { epoch, quote }
    pipSize: { type: Number, default: 2 },
    entryQuote: { type: Number, default: null }, // linha tracejada na cotação de entrada
    entryEpoch: { type: Number, default: null }, // coluna marcada como "entrada"; a partir dela contam-se os 5 ticks
    exitEpoch: { type: Number, default: null }, // coluna marcada como "saída" (o 5º tick)
});

const min = computed(() => Math.min(...props.ticks.map((t) => t.quote)));
const max = computed(() => Math.max(...props.ticks.map((t) => t.quote)));
const range = computed(() => max.value - min.value || 1);

// Altura em %: a menor cotação ainda tem 12% de coluna, para nunca sumir.
const heightOf = (quote) => 12 + ((quote - min.value) / range.value) * 88;

const bars = computed(() =>
    props.ticks.map((t, i) => ({
        epoch: t.epoch,
        height: heightOf(t.quote),
        up: i === 0 || t.quote >= props.ticks[i - 1].quote,
        isEntry: t.epoch === props.entryEpoch,
        isExit: t.epoch === props.exitEpoch,
        counted: props.entryEpoch != null && t.epoch > props.entryEpoch && (props.exitEpoch == null || t.epoch <= props.exitEpoch),
        last: i === props.ticks.length - 1,
    })),
);

// Etiqueta da saída: acima ou abaixo da ENTRADA (é isso que decide a aposta, não a cor da coluna).
const exitLabel = computed(() => {
    const exit = props.ticks.find((t) => t.epoch === props.exitEpoch);
    if (!exit || props.entryQuote == null) return 'saída';
    if (exit.quote > props.entryQuote) return 'saída ▲ acima';
    if (exit.quote < props.entryQuote) return 'saída ▼ abaixo';
    return 'saída = igual';
});

const entryLine = computed(() =>
    props.entryQuote != null && props.entryQuote >= min.value && props.entryQuote <= max.value
        ? heightOf(props.entryQuote)
        : null,
);

const fmt = (v) => v.toFixed(props.pipSize);
</script>

<template>
    <div class="relative h-full w-full select-none">
        <div v-if="ticks.length < 2" class="flex h-full items-center justify-center text-gray-500">Aguardando cotações…</div>

        <template v-else>
            <!-- escala -->
            <div class="absolute left-0 top-0 text-xs tabular-nums text-gray-500">{{ fmt(max) }}</div>
            <div class="absolute bottom-0 left-0 text-xs tabular-nums text-gray-500">{{ fmt(min) }}</div>

            <!-- colunas -->
            <div class="absolute inset-y-0 left-16 right-0 flex items-end gap-[2px]">
                <div
                    v-for="b in bars"
                    :key="b.epoch"
                    class="relative flex-1 rounded-t-sm transition-[height] duration-300"
                    :class="[
                        b.up ? 'bg-emerald-500' : 'bg-red-500',
                        b.counted ? 'ring-1 ring-white/50' : '',
                        b.isEntry ? 'z-20 outline outline-2 outline-yellow-400' : '',
                        b.isExit ? 'z-20 outline outline-2 outline-sky-400' : '',
                        b.last && !b.isEntry && !b.isExit ? 'brightness-125' : 'opacity-90',
                    ]"
                    :style="{ height: b.height + '%' }"
                >
                    <!-- etiquetas por cima de tudo (z-30), para nenhuma coluna seguinte cobrir -->
                    <span v-if="b.isEntry" class="absolute -top-6 left-1/2 z-30 -translate-x-1/2 whitespace-nowrap bg-yellow-400 px-1 text-[10px] font-bold uppercase text-gray-900">entrada</span>
                    <span v-if="b.isExit" class="absolute -top-6 left-1/2 z-30 -translate-x-1/2 whitespace-nowrap bg-sky-400 px-1 text-[10px] font-bold uppercase text-gray-900">{{ exitLabel }}</span>
                </div>
            </div>

            <!-- linha da cotação de entrada -->
            <div
                v-if="entryLine != null"
                class="pointer-events-none absolute left-16 right-0 border-t border-dashed border-yellow-400"
                :style="{ bottom: entryLine + '%' }"
            ></div>
        </template>
    </div>
</template>
