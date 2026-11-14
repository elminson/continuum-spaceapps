<script setup lang="ts">
import Spark from '@/components/fire/Spark.vue';
import type { AlertRow, Fit } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, number, percent, r, t } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    areas: {
        slug: string;
        name: string;
        bbox: number[];
        first_month: string | null;
        last_month: string | null;
        spark: (number | null)[];
        latest_alert: AlertRow | null;
        fit: Fit | null;
    }[];
}>();
</script>

<template>
    <Head :title="t('Areas of interest')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Areas of interest') }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            {{ t('Each area carries its full NASA FIRMS record and its own MODIS/VIIRS fit. Want a smaller box? Use Explore.') }}
        </p>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <Link v-for="a in areas" :key="a.slug" :href="`/areas/${a.slug}`" class="rounded-lg border p-5 transition hover:border-foreground/40">
                <div class="font-medium">{{ a.name }}</div>
                <div class="text-xs text-muted-foreground">{{ a.first_month }} → {{ a.last_month }} · {{ a.bbox.join(', ') }}</div>
                <Spark class="mt-3" :values="a.spark" />
                <dl v-if="a.fit?.validation" class="mt-3 grid grid-cols-3 gap-2 text-xs">
                    <div>
                        <dt class="text-muted-foreground">MODIS ÷ VIIRS</dt>
                        <dd class="tabular-nums">×{{ number(a.fit.ratio, 3) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Error vs naive merge') }}</dt>
                        <dd class="tabular-nums">−{{ percent(a.fit.validation.improvement) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Held-out days') }}</dt>
                        <dd class="tabular-nums">{{ number(a.fit.validation.test_days) }} · {{ r(a.fit.validation.correlation) }}</dd>
                    </div>
                </dl>
                <div v-if="a.latest_alert" class="mt-2 text-xs text-muted-foreground">
                    {{ t('Last unusual week: :date', { date: date(a.latest_alert.week_start) }) }}
                </div>
            </Link>
        </div>
    </SiteLayout>
</template>
