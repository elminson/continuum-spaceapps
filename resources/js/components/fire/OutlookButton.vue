<script setup lang="ts">
import { number, t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { CloudRain, CloudSun, Sun, Wind } from 'lucide-vue-next';
import { computed } from 'vue';
import type { ForecastData } from './ForecastCard.vue';

const props = defineProps<{ forecast: ForecastData; href: string }>();

const w = computed(() => props.forecast.weather?.last_7_days ?? null);
const icon = computed(() => {
    if (!w.value) return CloudSun;
    if ((w.value.rain ?? 0) >= 20) return CloudRain;
    if ((w.value.wind ?? 0) >= 4) return Wind;
    if ((w.value.humidity ?? 100) < 60) return Sun;
    return CloudSun;
});
const hot = computed(() => (props.forecast.times_normal ?? 0) >= 2);
</script>

<template>
    <Link
        :href="href"
        class="inline-flex items-center gap-3 rounded-md border px-3 py-2 text-sm transition hover:bg-muted"
        :class="hot ? 'border-orange-600 bg-orange-50/60 dark:bg-orange-950/20' : 'border-border'"
    >
        <component :is="icon" class="size-5 shrink-0" :class="hot ? 'text-orange-600' : 'text-muted-foreground'" />
        <span class="flex flex-col leading-tight">
            <span class="font-medium tabular-nums">
                {{ t('Next week') }}:
                <span :class="hot ? 'text-orange-600' : ''">{{
                    forecast.times_normal !== null ? `×${number(forecast.times_normal, 1)}` : number(forecast.expected)
                }}</span>
                <span class="text-muted-foreground"> {{ t('of normal') }}</span>
            </span>
            <span v-if="w" class="text-xs tabular-nums text-muted-foreground">
                {{ number(w.temperature, 0) }} °C · {{ number(w.humidity) }}% · {{ number(w.rain) }} mm · {{ t('forecast and weather') }} →
            </span>
        </span>
    </Link>
</template>
