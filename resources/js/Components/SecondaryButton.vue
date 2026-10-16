<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center justify-center rounded-none border border-gray-700 bg-gray-900 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-gray-300 transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-emerald-400 disabled:opacity-50"
    >
        <slot />
    </button>
</template>
