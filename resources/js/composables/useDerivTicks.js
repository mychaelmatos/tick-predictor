import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Abre o WebSocket público da Deriv e mantém os últimos N ticks do símbolo.
 * Só exibição: nada daqui decide aposta. Usado na tela do jogo e no login.
 */
export function useDerivTicks(wsUrl, symbol, keep = 40) {
    const ticks = ref([]); // { epoch, quote }
    const pipSize = ref(2);
    const connected = ref(false);
    let socket = null;
    let pingTimer = null;
    let leaving = false;

    function connect() {
        socket = new WebSocket(wsUrl);

        socket.onopen = () => {
            connected.value = true;
            // Uma mensagem só: traz os últimos N ticks e já assina os próximos.
            socket.send(JSON.stringify({ ticks_history: symbol, end: 'latest', count: keep, style: 'ticks', subscribe: 1 }));
            // Ping a cada 30 s mantém a conexão viva (recomendação da Deriv).
            pingTimer = setInterval(() => socket.send(JSON.stringify({ ping: 1 })), 30000);
        };

        socket.onmessage = (event) => {
            const msg = JSON.parse(event.data);
            if (msg.msg_type === 'history') {
                pipSize.value = msg.pip_size;
                ticks.value = msg.history.times.map((epoch, i) => ({ epoch, quote: msg.history.prices[i] }));
            } else if (msg.msg_type === 'tick' && msg.tick) {
                ticks.value = [...ticks.value, { epoch: msg.tick.epoch, quote: msg.tick.quote }].slice(-keep);
            }
        };

        socket.onclose = () => {
            connected.value = false;
            clearInterval(pingTimer);
            if (!leaving) setTimeout(connect, 3000); // caiu? tenta de novo em 3 s
        };
    }

    onMounted(connect);
    onUnmounted(() => {
        leaving = true;
        clearInterval(pingTimer);
        socket?.close();
    });

    return { ticks, pipSize, connected };
}
