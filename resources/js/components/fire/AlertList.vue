<script setup lang="ts">
import { date, t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import TimesNormal from './TimesNormal.vue';
import type { AlertRow } from './types';

defineProps<{ alerts: AlertRow[]; showArea?: boolean }>();
</script>

<template>
    <ul class="text-sm">
        <li v-for="a in alerts" :key="`${a.slug}-${a.week_start}`">
            <Link :href="a.href ?? `/areas/${a.slug}`" class="-mx-2 flex items-center justify-between gap-4 rounded-md px-2 py-2 hover:bg-muted">
                <span class="min-w-0 truncate">
                    <span>{{ t('Week of :date', { date: date(a.week_start) }) }}</span>
                    <span v-if="showArea !== false" class="text-muted-foreground"> · {{ a.area }}</span>
                </span>
                <TimesNormal :times="a.times" :severity="a.severity" />
            </Link>
        </li>
    </ul>
</template>
