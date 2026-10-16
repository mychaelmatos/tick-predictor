<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BetDetailModal from '@/Components/BetDetailModal.vue';
import BetHistoryModal from '@/Components/BetHistoryModal.vue';
import InputError from '@/Components/InputError.vue';
import TickChart from '@/Components/TickChart.vue';
import { useDerivTicks } from '@/composables/useDerivTicks';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    balanceCents: Number,
    openBet: Object, // aposta pendente, se houver
    lastBet: Object, // última aposta já decidida, se houver
    game: Object, // ticks, payoutPercent, minStakeCents, maxStakeCents
});

// ---------- Cotação ao vivo (só exibição) ----------
const deriv = usePage().props.deriv;
const { ticks, pipSize, connected } = useDerivTicks(deriv.wsUrl, deriv.symbol);
const lastTick = computed(() => ticks.value.at(-1) ?? null);
const previousTick = computed(() => ticks.value.at(-2) ?? null);
const trend = computed(() => (!lastTick.value || !previousTick.value ? 0 : Math.sign(lastTick.value.quote - previousTick.value.quote)));

// ---------- Aposta ----------
const form = useForm({ stake: 10 });

function bet(direction) {
    form.transform((data) => ({ stake_cents: Math.round(data.stake * 100), direction }))
        .post(route('bets.store'), { preserveScroll: true });
}

// Quantos ticks já chegaram depois da entrada da aposta aberta.
const ticksSinceEntry = computed(() =>
    props.openBet ? ticks.value.filter((t) => t.epoch > props.openBet.entry_epoch).length : 0,
);

// Chegou no 5º tick: pede ao backend para conferir. O backend vai à Deriv e decide sozinho.
let settling = false;
watch(ticksSinceEntry, (count) => {
    if (props.openBet && count >= props.game.ticks && !settling) {
        settling = true;
        router.post(route('bets.settle', props.openBet.id), {}, {
            preserveScroll: true,
            onFinish: () => (settling = false),
        });
    }
});

// Se a aposta aberta estiver "ganhando" agora (só visual; quem decide é o backend)
const winningNow = computed(() => {
    if (!props.openBet || !lastTick.value) return null;
    return props.openBet.direction === 'up'
        ? lastTick.value.quote > props.openBet.entry_quote
        : lastTick.value.quote < props.openBet.entry_quote;
});

const showHistory = ref(false);
const detailBet = ref(null); // aposta aberta no modal de detalhe

// O que marcar no gráfico: a aposta aberta, ou então a última decidida (enquanto os ticks ainda estão na tela).
const marked = computed(() => props.openBet ?? props.lastBet);

// ---------- Formatação ----------
const money = (cents) => (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const quote = (v) => (v == null ? '—' : Number(v).toFixed(pipSize.value));
const directionLabel = { up: 'Subir', down: 'Descer' };
</script>

<template>
    <Head title="Jogo" />

    <AuthenticatedLayout>
        <!-- Duas colunas da mesma altura (600px no desktop): gráfico à esquerda, operação à direita -->
        <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:h-[600px] lg:grid-cols-3 lg:px-8">
            <!-- esquerda + centro: gráfico -->
            <section class="flex flex-col border border-gray-800 bg-gray-900 lg:col-span-2">
                <div class="flex h-24 items-center justify-between border-b border-gray-800 px-5">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ deriv.symbol }} · últimos {{ ticks.length }} ticks</div>
                        <div
                            class="text-4xl font-bold leading-tight tabular-nums"
                            :class="{ 'text-emerald-400': trend > 0, 'text-red-400': trend < 0 }"
                        >
                            {{ quote(lastTick?.quote) }}
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        <span class="mr-1 inline-block h-2 w-2 rounded-full" :class="connected ? 'bg-emerald-500' : 'bg-red-500'"></span>
                        {{ connected ? 'ao vivo' : 'reconectando' }}
                    </div>
                </div>

                <div class="h-[360px] p-5 pt-8 lg:h-auto lg:min-h-0 lg:flex-1">
                    <TickChart
                        :ticks="ticks"
                        :pip-size="pipSize"
                        :entry-quote="marked ? Number(marked.entry_quote) : null"
                        :entry-epoch="marked ? marked.entry_epoch : null"
                        :exit-epoch="marked ? marked.exit_epoch : null"
                    />
                </div>
            </section>

            <!-- direita: operação -->
            <aside class="flex flex-col gap-4">
                <!-- saldo: mesma altura do cabeçalho do gráfico -->
                <div class="flex h-24 shrink-0 flex-col justify-center border border-gray-800 bg-gray-900 px-5">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Saldo</div>
                    <div class="text-4xl font-bold leading-tight tabular-nums">{{ money(balanceCents) }}</div>
                </div>

                <!-- aposta: ocupa o espaço restante, mesma altura com ou sem aposta aberta -->
                <div
                    class="flex min-h-[260px] flex-1 flex-col justify-center border bg-gray-900 p-5"
                    :class="openBet ? 'border-yellow-500/40' : 'border-gray-800'"
                >
                    <template v-if="openBet">
                        <div class="text-xs uppercase tracking-wide text-yellow-400">Aposta em andamento</div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-lg font-semibold" :class="openBet.direction === 'up' ? 'text-emerald-400' : 'text-red-400'">
                                {{ directionLabel[openBet.direction] }} {{ openBet.direction === 'up' ? '▲' : '▼' }}
                            </span>
                            <span class="tabular-nums text-gray-300">{{ money(openBet.stake_cents) }}</span>
                        </div>
                        <div class="mt-1 text-sm text-gray-400">Entrada {{ quote(openBet.entry_quote) }}</div>

                        <div class="mt-6 flex gap-1">
                            <div
                                v-for="i in game.ticks"
                                :key="i"
                                class="h-2 flex-1"
                                :class="i <= ticksSinceEntry ? 'bg-yellow-400' : 'bg-gray-700'"
                            ></div>
                        </div>
                        <div class="mt-2 flex justify-between text-sm">
                            <span class="text-gray-400">Tick {{ Math.min(ticksSinceEntry, game.ticks) }} de {{ game.ticks }}</span>
                            <span v-if="ticksSinceEntry >= game.ticks" class="text-yellow-400">conferindo…</span>
                            <span v-else-if="winningNow != null" :class="winningNow ? 'text-emerald-400' : 'text-red-400'">
                                {{ winningNow ? 'ganhando' : 'perdendo' }}
                            </span>
                        </div>
                    </template>

                    <template v-else>
                        <label for="stake" class="block text-xs uppercase tracking-wide text-gray-500">Valor da aposta (R$)</label>
                        <input
                            id="stake"
                            v-model.number="form.stake"
                            type="number"
                            step="0.01"
                            :min="game.minStakeCents / 100"
                            :max="game.maxStakeCents / 100"
                            class="mt-1 block w-full rounded-none border border-gray-700 bg-gray-950 px-3 py-2 text-lg tabular-nums text-gray-100 focus:border-emerald-500 focus:ring-0"
                        />
                        <InputError class="mt-1" :message="form.errors.stake_cents ?? form.errors.direction" />

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                :disabled="form.processing || !connected"
                                @click="bet('up')"
                                class="h-12 bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-500 disabled:opacity-40"
                            >
                                Subir ▲
                            </button>
                            <button
                                type="button"
                                :disabled="form.processing || !connected"
                                @click="bet('down')"
                                class="h-12 bg-red-600 text-lg font-bold text-white hover:bg-red-500 disabled:opacity-40"
                            >
                                Descer ▼
                            </button>
                        </div>

                        <p class="mt-3 text-xs text-gray-500">
                            Decide em {{ game.ticks }} ticks. Acertou: +{{ game.payoutPercent }}%. Errou: perde o valor.
                        </p>
                    </template>
                </div>

                <!-- última aposta: sempre presente, altura fixa, para o layout não pular -->
                <button
                    type="button"
                    :disabled="!lastBet"
                    @click="detailBet = lastBet"
                    class="flex h-20 shrink-0 flex-col justify-center border border-gray-800 bg-gray-900 px-5 text-left text-sm hover:border-gray-600 disabled:hover:border-gray-800"
                    title="Ver os 5 ticks desta aposta"
                >
                    <div class="flex justify-between text-xs uppercase tracking-wide text-gray-500">
                        <span>Última aposta</span>
                        <span v-if="lastBet" class="normal-case text-sky-400">ver ticks ›</span>
                    </div>
                    <div v-if="lastBet" class="mt-1 flex items-baseline justify-between">
                        <span>{{ directionLabel[lastBet.direction] }} · {{ quote(lastBet.entry_quote) }} → {{ quote(lastBet.exit_quote) }}</span>
                        <span class="font-bold tabular-nums" :class="lastBet.status === 'won' ? 'text-emerald-400' : 'text-red-400'">
                            {{ lastBet.status === 'won' ? 'ganhou' : 'perdeu' }} {{ money(lastBet.profit_cents) }}
                        </span>
                    </div>
                    <div v-else class="mt-1 text-gray-500">Nenhuma aposta decidida ainda.</div>
                </button>

                <button
                    type="button"
                    @click="showHistory = true"
                    class="h-11 shrink-0 border border-gray-700 text-sm text-gray-300 hover:bg-gray-800"
                >
                    Histórico de operações
                </button>
            </aside>
        </div>

        <BetHistoryModal :show="showHistory" :pip-size="pipSize" @close="showHistory = false" @select="detailBet = $event" />
        <BetDetailModal :bet="detailBet" :pip-size="pipSize" @close="detailBet = null" />
    </AuthenticatedLayout>
</template>
