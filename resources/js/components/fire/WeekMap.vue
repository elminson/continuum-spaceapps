<script setup lang="ts">
import { date, number, t } from '@/lib/i18n';
import { addFireCells, loadFireCells, maplibregl, nasaMap, setDailyImagery, type Period } from '@/lib/map';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FireLegend from './FireLegend.vue';
import Loading from './Loading.vue';

const props = withDefaults(defineProps<{ bbox: number[]; week?: string | null; days?: number; times?: number | null; height?: string }>(), {
    height: 'h-[28rem]',
});
const el = ref<HTMLDivElement | null>(null);
const cells = ref<number | null>(null);
const loading = ref(false);
const photo = ref(false);

/** The day shown in the satellite image: mid-week, or two days ago for recent fires (imagery lags a day). */
const photoDay = computed(() => {
    const d = props.week ? new Date(`${props.week}T00:00:00Z`) : new Date();
    d.setUTCDate(d.getUTCDate() + (props.week ? 3 : -2));
    return d.toISOString().slice(0, 10);
});

function applyPhoto() {
    if (map?.isStyleLoaded()) setDailyImagery(map, photo.value ? photoDay.value : null);
}
let map: maplibregl.Map | null = null;

const period = computed<Period>(() => (props.week ? { week: props.week } : { days: props.days ?? 30 }));
const label = computed(() => (props.week ? t('Week of :date', { date: date(props.week) }) : t('Last :n days', { n: props.days ?? 30 })));

async function load() {
    if (!map) return;
    loading.value = true;
    cells.value = await loadFireCells(map, props.bbox, period.value);
    loading.value = false;
}

onMounted(() => {
    map = nasaMap(el.value!, [props.bbox[0], props.bbox[1], props.bbox[2], props.bbox[3]]);
    map.on('load', () => {
        addFireCells(map!);
        load();
    });
});

watch(
    () => [props.week, props.days, props.bbox.join(',')],
    () => {
        map?.fitBounds([props.bbox[0], props.bbox[1], props.bbox[2], props.bbox[3]], { duration: 0 });
        load();
        applyPhoto();
    },
);
watch(photo, applyPhoto);
onBeforeUnmount(() => map?.remove());
</script>

<template>
    <div class="relative overflow-hidden rounded-md bg-neutral-950" :class="height">
        <div ref="el" class="absolute inset-0" />

        <div class="absolute right-3 top-3 flex flex-col items-end gap-2">
            <slot name="controls" />
            <div class="rounded-md bg-neutral-900/90 px-2.5 py-1.5 text-xs text-neutral-200"><FireLegend /></div>
        </div>

        <div class="absolute bottom-3 left-3 w-[min(22rem,calc(100%-1.5rem))] rounded-md bg-neutral-900 p-4 text-neutral-100 shadow-lg">
            <slot name="panel-top" />
            <Loading v-if="loading" class="text-neutral-300" :label="t('Loading fires…')" />
            <template v-else>
                <div class="text-sm text-neutral-300">{{ label }}</div>
                <div v-if="week && times" class="mt-1 flex items-baseline gap-2 tabular-nums">
                    <span class="size-2 self-center rounded-full bg-orange-500" />
                    <span class="text-4xl font-semibold text-orange-500">×{{ number(times, 1) }}</span>
                    <span class="text-neutral-300">{{ t('normal') }}</span>
                </div>
                <div class="mt-1 text-sm text-neutral-300">{{ t(':n cells with fire', { n: number(cells) }) }}</div>
            </template>
            <label class="mt-3 flex cursor-pointer items-center gap-2 border-t border-neutral-700 pt-3 text-xs text-neutral-300">
                <input v-model="photo" type="checkbox" class="size-3.5 accent-orange-600" />
                {{ t('NASA satellite image of :date', { date: date(photoDay) }) }}
            </label>
        </div>
    </div>
</template>
