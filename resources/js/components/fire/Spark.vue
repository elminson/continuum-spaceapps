<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ values: (number | null)[] }>();

const path = computed(() => {
    const v = props.values.map((x) => x ?? 0);
    const max = Math.max(1, ...v);

    return v.map((x, i) => `${i === 0 ? 'M' : 'L'}${(i / Math.max(1, v.length - 1)) * 100},${30 - (x / max) * 28}`).join(' ');
});
</script>

<template>
    <svg viewBox="0 0 100 30" preserveAspectRatio="none" class="h-10 w-full">
        <path :d="path" fill="none" stroke="#16a34a" stroke-width="1.5" vector-effect="non-scaling-stroke" />
    </svg>
</template>
