<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, watch } from 'vue';

const props = defineProps({ show: Boolean, pipSize: { type: Number, default: 2 } });
const emit = defineEmits(['close', 'select']);

const page = ref(null); // resposta paginada do Laravel: data, current_page, last_page, total
const loading = ref(false);

async function load(number = 1) {
    loading.value = true;
    try {
        page.value = (await axios.get(route('bets.index', { page: number }))).data;
    } finally {
        loading.value = false;
    }
}

// Toda vez que abre, recarrega a primeira página.
watch(() => props.show, (open) => open && load(1));

const money = (cents) => (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
const quote = (v) => (v == null ? '—' : Number(v).toFixed(props.pipSize));
const when = (iso) => new Date(iso).toLocaleString('pt-BR');
const statusLabel = { pending: 'pendente', won: 'ganhou', lost: 'perdeu' };
const directionLabel = { up: 'Subir', down: 'Descer' };
</script>

<template>
    <Modal :show="show" max-width="3xl" @close="emit('close')">
        <div class="flex items-center justify-between border-b border-gray-800 px-5 py-3">
            <h3 class="font-semibold">Histórico de operações</h3>
            <span v-if="page" class="text-sm text-gray-500">{{ page.total }} apostas · clique numa linha para ver os ticks</span>
        </div>

        <div class="max-h-[60vh] overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead class="sticky top-0 bg-gray-900 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-2">Horário</th>
                        <th class="px-4 py-2">Valor</th>
                        <th class="px-4 py-2">Direção</th>
                        <th class="px-4 py-2">Entrada</th>
                        <th class="px-4 py-2">Saída</th>
                        <th class="px-4 py-2">Lucro/Prejuízo</th>
                        <th class="px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <tr v-if="loading && !page"><td colspan="7" class="px-4 py-6 text-center text-gray-500">Carregando…</td></tr>
                    <tr v-else-if="page && page.data.length === 0"><td colspan="7" class="px-4 py-6 text-center text-gray-500">Nenhuma aposta ainda.</td></tr>
                    <tr
                        v-for="b in page?.data ?? []"
                        :key="b.id"
                        class="cursor-pointer tabular-nums hover:bg-gray-800"
                        title="Ver os 5 ticks desta aposta"
                        @click="emit('select', b)"
                    >
                        <td class="px-4 py-2 text-gray-400">{{ when(b.created_at) }}</td>
                        <td class="px-4 py-2">{{ money(b.stake_cents) }}</td>
                        <td class="px-4 py-2" :class="b.direction === 'up' ? 'text-emerald-400' : 'text-red-400'">{{ directionLabel[b.direction] }}</td>
                        <td class="px-4 py-2">{{ quote(b.entry_quote) }}</td>
                        <td class="px-4 py-2">{{ quote(b.exit_quote) }}</td>
                        <td class="px-4 py-2 font-semibold" :class="{ 'text-emerald-400': b.profit_cents > 0, 'text-red-400': b.profit_cents < 0 }">
                            {{ b.profit_cents == null ? '—' : money(b.profit_cents) }}
                        </td>
                        <td class="px-4 py-2">
                            <span
                                class="px-2 py-0.5 text-xs font-semibold uppercase"
                                :class="{
                                    'bg-yellow-500/20 text-yellow-300': b.status === 'pending',
                                    'bg-emerald-500/20 text-emerald-300': b.status === 'won',
                                    'bg-red-500/20 text-red-300': b.status === 'lost',
                                }"
                            >{{ statusLabel[b.status] }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-gray-800 px-5 py-3">
            <span class="text-sm text-gray-500">Página {{ page?.current_page ?? 1 }} de {{ page?.last_page ?? 1 }}</span>
            <div class="flex gap-2">
                <SecondaryButton :disabled="loading || !page || page.current_page <= 1" @click="load(page.current_page - 1)">Anterior</SecondaryButton>
                <SecondaryButton :disabled="loading || !page || page.current_page >= page.last_page" @click="load(page.current_page + 1)">Próxima</SecondaryButton>
                <SecondaryButton @click="emit('close')">Fechar</SecondaryButton>
            </div>
        </div>
    </Modal>
</template>
