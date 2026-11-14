<script setup lang="ts">
import AlertList from '@/components/fire/AlertList.vue';
import type { AlertRow } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { t } from '@/lib/i18n';
import { Head } from '@inertiajs/vue3';

defineProps<{ strongest: AlertRow[]; recent: AlertRow[] }>();
</script>

<template>
    <Head :title="t('Unusual periods')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Unusual periods') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Weeks far above the same week in every other year (median and MAD, MODIS scale). Only a harmonized record makes weeks before and after 2012 comparable.',
                )
            }}
        </p>
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section>
                <h2 class="text-lg font-semibold">{{ t('Most recent') }}</h2>
                <AlertList class="mt-3" :alerts="recent" />
            </section>
            <section>
                <h2 class="text-lg font-semibold">{{ t('Strongest on record') }}</h2>
                <AlertList class="mt-3" :alerts="strongest" />
            </section>
        </div>
    </SiteLayout>
</template>
