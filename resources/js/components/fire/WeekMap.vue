<script setup lang="ts">
import { date, number, t } from '@/lib/i18n';
import { addFireCells, loadFireCells, maplibregl, nasaMap, setDailyImagery, type Period } from '@/lib/map';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import FireLegend from './FireLegend.vue';
import Loading from './Loading.vue';
import TimesNormal from './TimesNormal.vue';

const props = defineProps<{ bbox: number[]; week?: string | null; days?: number; times?: number | null }>();
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
    <div>
        <div class="mb-2 flex flex-wrap gap-4 text-xs text-muted-foreground">
            <Loading v-if="loading" :label="t('Loading fires…')" />
            <span v-else>{{ label }} · {{ t(':n cells with fire', { n: number(cells) }) }}</span>
            <TimesNormal v-if="week && times" :times="times" />
            <label class="ml-auto inline-flex cursor-pointer items-center gap-1.5">
                <input v-model="photo" type="checkbox" class="size-3.5 accent-orange-600" />
                {{ t('NASA satellite image of :date', { date: date(photoDay) }) }}
            </label>
            <FireLegend />
        </div>
        <div ref="el" class="h-[28rem] w-full overflow-hidden rounded-md" />
    </div>
</template>
