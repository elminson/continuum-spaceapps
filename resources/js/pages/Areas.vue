<script setup lang="ts">
import Spark from '@/components/fire/Spark.vue';
import type { AlertRow, Fit } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, number, percent, r, t } from '@/lib/i18n';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { BellPlus, Compass, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props.auth as { user: unknown } | undefined)?.user ?? null);

function forget(id: number) {
    if (confirm(t('Delete this area?'))) router.delete(`/boxes/${id}`, { preserveScroll: true });
}

defineProps<{
    mine: { id: number; slug: string; name: string; bbox: number[] }[];
    zones: { token: string; name: string; email: string; bbox: number[]; email_enabled: boolean; last_sent_at: string | null }[];
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

        <section id="mine" class="mt-12 scroll-mt-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight">{{ t('My areas') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            user
                                ? t('Boxes you saved and zones with alerts. Open one to analyze it again.')
                                : t('Sign in to keep your own boxes and alert zones here.')
                        }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <Link href="/explore" class="inline-flex items-center gap-1.5 rounded-md bg-foreground px-3 py-1.5 text-background"
                        ><Compass class="size-4" /> {{ t('Create a new area') }}</Link
                    >
                    <Link href="/alerts/subscribe" class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 hover:bg-muted"
                        ><BellPlus class="size-4" /> {{ t('Fire alerts') }}</Link
                    >
                    <Link v-if="!user" href="/login" class="inline-flex items-center rounded-md border px-3 py-1.5 hover:bg-muted">{{
                        t('Sign in')
                    }}</Link>
                </div>
            </div>

            <template v-if="user">
                <div v-if="mine.length" class="mt-4 grid gap-3 md:grid-cols-2">
                    <div v-for="b in mine" :key="b.id" class="flex items-center justify-between gap-3 rounded-lg border p-4">
                        <div>
                            <div class="font-medium">{{ b.name }}</div>
                            <div class="text-xs tabular-nums text-muted-foreground">{{ b.bbox.map((v) => v.toFixed(2)).join(', ') }}</div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <Link :href="`/explore?bbox=${b.bbox.join(',')}`" class="underline-offset-4 hover:underline">{{ t('Open') }} →</Link>
                            <Link :href="`/alerts/subscribe`" class="text-muted-foreground underline-offset-4 hover:underline">{{
                                t('Alerts')
                            }}</Link>
                            <button class="text-muted-foreground hover:text-red-600" :aria-label="t('Delete this area?')" @click="forget(b.id)">
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-muted-foreground">{{ t('No saved areas yet. Draw one in Explore and save it.') }}</p>

                <template v-if="zones.length">
                    <h3 class="mt-6 font-medium">{{ t('Zones with alerts') }}</h3>
                    <table class="mt-2 w-full text-sm">
                        <thead class="text-left text-muted-foreground">
                            <tr>
                                <th class="py-2 font-normal">{{ t('Zone') }}</th>
                                <th class="font-normal">{{ t('Email') }}</th>
                                <th class="font-normal">{{ t('Email alerts') }}</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="z in zones" :key="z.token" class="border-t">
                                <td class="py-2">{{ z.name }}</td>
                                <td>{{ z.email }}</td>
                                <td>{{ z.email_enabled ? t('On') : t('Off') }}</td>
                                <td class="text-right">
                                    <Link :href="`/explore?bbox=${z.bbox.join(',')}`" class="underline-offset-4 hover:underline"
                                        >{{ t('Open') }} →</Link
                                    >
                                    <Link :href="`/subscriptions/${z.token}`" class="ml-3 text-muted-foreground underline-offset-4 hover:underline">{{
                                        t('Manage')
                                    }}</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>
            </template>
        </section>
    </SiteLayout>
</template>
