<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { computed } from 'vue';

// Mostra exatamente o que o backend usou para decidir: a entrada e os 5 ticks seguintes.
const props = defineProps({ bet: Object, pipSize: { type: Number, default: 2 } });
const emit = defineEmits(['close']);

const money = (cents) => (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const quote = (v) => (v == null ? '—' : Number(v).toFixed(props.pipSize));
const time = (epoch) => new Date(epoch * 1000).toLocaleTimeString('pt-BR');
const when = (iso) => new Date(iso).toLocaleString('pt-BR');
const directionLabel = { up: 'Subir', down: 'Descer' };

const entry = computed(() => Number(props.bet?.entry_quote));
const exit = computed(() => (props.bet?.exit_quote == null ? null : Number(props.bet.exit_quote)));

const movement = computed(() => {
    if (exit.value == null) return null;
    if (exit.value > entry.value) return 'subiu';
    if (exit.value < entry.value) return 'desceu';
    return 'ficou igual';
});
</script>

<template>
    <Modal :show="bet != null" max-width="lg" @close="emit('close')">
        <div v-if="bet" class="p-5">
            <div class="flex items-baseline justify-between">
                <h3 class="font-semibold">Detalhe da aposta</h3>
                <span class="text-sm text-gray-500">{{ when(bet.created_at) }}</span>
            </div>

            <div class="mt-3 flex items-baseline justify-between text-sm">
                <span>
                    Você apostou <strong>{{ money(bet.stake_cents) }}</strong> em
                    <strong :class="bet.direction === 'up' ? 'text-emerald-400' : 'text-red-400'">{{ directionLabel[bet.direction] }}</strong>
                </span>
                <span v-if="bet.status !== 'pending'" class="font-bold tabular-nums" :class="bet.status === 'won' ? 'text-emerald-400' : 'text-red-400'">
                    {{ bet.status === 'won' ? 'ganhou' : 'perdeu' }} {{ money(bet.profit_cents) }}
                </span>
                <span v-else class="text-yellow-400">pendente</span>
            </div>

            <table class="mt-4 w-full text-sm tabular-nums">
                <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="py-1">Tick</th>
                        <th class="py-1">Horário</th>
                        <th class="py-1 text-right">Cotação</th>
                        <th class="py-1 text-right">vs. entrada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <tr class="bg-yellow-400/10">
                        <td class="py-1.5 font-semibold text-yellow-400">Entrada</td>
                        <td class="py-1.5">{{ time(bet.entry_epoch) }}</td>
                        <td class="py-1.5 text-right">{{ quote(entry) }}</td>
                        <td class="py-1.5 text-right text-gray-500">—</td>
                    </tr>
                    <tr v-if="!bet.ticks">
                        <td colspan="4" class="py-3 text-center text-gray-500">
                            {{ bet.status === 'pending' ? 'Aguardando os ticks seguintes…' : 'Aposta decidida antes de o registro dos ticks existir.' }}
                        </td>
                    </tr>
                    <tr
                        v-for="(t, i) in bet.ticks ?? []"
                        :key="t.epoch"
                        :class="i === bet.ticks.length - 1 ? 'bg-sky-400/10' : ''"
                    >
                        <td class="py-1.5" :class="i === bet.ticks.length - 1 ? 'font-semibold text-sky-400' : ''">
                            {{ i + 1 }}º{{ i === bet.ticks.length - 1 ? ' · Saída' : '' }}
                        </td>
                        <td class="py-1.5">{{ time(t.epoch) }}</td>
                        <td class="py-1.5 text-right">{{ quote(t.quote) }}</td>
                        <td class="py-1.5 text-right" :class="t.quote > entry ? 'text-emerald-400' : t.quote < entry ? 'text-red-400' : 'text-gray-400'">
                            {{ t.quote > entry ? '▲ acima' : t.quote < entry ? '▼ abaixo' : '= igual' }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <p v-if="movement" class="mt-4 border border-gray-800 bg-gray-950 p-3 text-sm text-gray-300">
                Do tick de entrada ({{ quote(entry) }}) ao 5º tick ({{ quote(exit) }}) a cotação
                <strong>{{ movement }}</strong>. Você apostou <strong>{{ directionLabel[bet.direction] }}</strong>,
                então <strong :class="bet.status === 'won' ? 'text-emerald-400' : 'text-red-400'">{{ bet.status === 'won' ? 'ganhou' : 'perdeu' }}</strong>.
                <span v-if="movement === 'ficou igual'" class="text-gray-500">Empate conta como perda.</span>
            </p>

            <p class="mt-3 text-xs text-gray-500">
                Só o 5º tick decide. As cores das colunas do gráfico comparam cada tick com o anterior, não com a entrada.
            </p>

            <div class="mt-4 flex justify-end">
                <SecondaryButton @click="emit('close')">Fechar</SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
