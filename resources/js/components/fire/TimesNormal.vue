<script setup lang="ts">
import { number, t } from '@/lib/i18n';

/** The signature: how many times the usual activity for that week of the year. */
defineProps<{ times: number | null; severity?: string }>();
</script>

<template>
    <span class="inline-flex items-center gap-1.5 whitespace-nowrap tabular-nums" :title="severity ? t(severity) : undefined">
        <span class="size-1.5 rounded-full bg-orange-600" :class="{ 'opacity-40': severity === 'elevated', 'opacity-70': severity === 'high' }" />
        <span class="font-medium">×{{ number(times, 1) }}</span>
        <span class="text-muted-foreground">{{ t('normal') }}</span>
    </span>
</template>
