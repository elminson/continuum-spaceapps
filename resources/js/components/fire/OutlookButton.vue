<script setup lang="ts">
import { date, number, t } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';
import { ChevronRight, CloudRain, CloudSun, Droplets, Sun, Thermometer, Wind } from 'lucide-vue-next';
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
        class="group flex w-full max-w-sm items-center gap-4 rounded-lg bg-neutral-900 px-4 py-3 text-neutral-100 shadow-md transition hover:bg-neutral-800"
        :aria-label="t('Next week outlook')"
    >
        <component :is="icon" class="size-10 shrink-0" :class="hot ? 'text-orange-500' : 'text-neutral-300'" :stroke-width="1.5" />
        <span class="min-w-0 flex-1">
            <span class="block text-xs text-neutral-400">{{ t('Next week') }} · {{ date(forecast.week_start) }}</span>
            <span class="flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums" :class="hot ? 'text-orange-500' : ''">
                    {{ forecast.times_normal !== null ? `×${number(forecast.times_normal, 1)}` : number(forecast.expected) }}
                </span>
                <span class="text-sm text-neutral-300">{{ forecast.times_normal !== null ? t('of normal') : t('burning cell-days') }}</span>
            </span>
            <span v-if="w" class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs tabular-nums text-neutral-300">
                <span class="inline-flex items-center gap-1"><Thermometer class="size-3.5 text-neutral-400" />{{ number(w.temperature, 0) }} °C</span>
                <span class="inline-flex items-center gap-1"><Droplets class="size-3.5 text-neutral-400" />{{ number(w.humidity) }}%</span>
                <span class="inline-flex items-center gap-1"><CloudRain class="size-3.5 text-neutral-400" />{{ number(w.rain) }} mm</span>
                <span class="inline-flex items-center gap-1"><Wind class="size-3.5 text-neutral-400" />{{ number(w.wind, 1) }} m/s</span>
            </span>
        </span>
        <ChevronRight class="size-5 shrink-0 text-neutral-400 transition group-hover:translate-x-0.5 group-hover:text-neutral-100" />
    </Link>
</template>
