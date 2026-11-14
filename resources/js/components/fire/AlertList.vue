<script setup lang="ts">
import { date, number, t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import type { AlertRow } from './types';

defineProps<{ alerts: AlertRow[] }>();
</script>

<template>
    <ul class="divide-y rounded-lg border text-sm">
        <li v-for="a in alerts" :key="`${a.slug}-${a.week_start}`">
            <Link :href="`/areas/${a.slug}`" class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-muted/50">
                <span>
                    <span class="font-medium">{{ a.area }}</span>
                    <span class="text-muted-foreground"> · {{ t('week of :date', { date: date(a.week_start) }) }}</span>
                </span>
                <span class="shrink-0 tabular-nums">
                    ×{{ number(a.times, 1) }} {{ t('normal') }}
                    <span
                        class="ml-2 rounded px-1.5 py-0.5 text-xs"
                        :class="
                            a.severity === 'extreme'
                                ? 'bg-red-100 text-red-700'
                                : a.severity === 'high'
                                  ? 'bg-orange-100 text-orange-700'
                                  : 'bg-yellow-100 text-yellow-800'
                        "
                        >{{ t(a.severity) }}</span
                    >
                </span>
            </Link>
        </li>
    </ul>
</template>
