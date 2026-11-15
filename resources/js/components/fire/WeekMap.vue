<script setup lang="ts">
import { date, number, t } from '@/lib/i18n';
import { addFireCells, downloadMapImage, loadFireCells, maplibregl, nasaMap, setDailyImagery, setFireDay, type Period } from '@/lib/map';
import { Pause, Play } from 'lucide-vue-next';
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
const csvHref = computed(
    () =>
        `/api/week?bbox=${props.bbox.map((v) => v.toFixed(2)).join(',')}&${'week' in period.value ? `week=${period.value.week}` : `days=${period.value.days}`}&format=csv`,
);
function downloadImage() {
    if (!map) return;
    const name = 'week' in period.value ? period.value.week : `last-${period.value.days}-days`;
    downloadMapImage(
        map,
        [dayLabel.value ?? label.value, t(':n cells with fire', { n: number(cells.value) }), 'Continuum · NASA FIRMS (MODIS, VIIRS) · NASA GIBS'],
        `continuum-fires-${name}.png`,
    );
}
const label = computed(() => (props.week ? t('Week of :date', { date: date(props.week) }) : t('Last :n days', { n: props.days ?? 30 })));

async function load() {
    if (!map) return;
    stop();
    loading.value = true;
    cells.value = await loadFireCells(map, props.bbox, period.value);
    loading.value = false;
}

// Play the period day by day: cells appear on the day they first burned.
const playing = ref(false);
const day = ref<number | null>(null);
let timer: ReturnType<typeof setInterval> | null = null;
const totalDays = computed(() => (props.week ? 7 : (props.days ?? 30)));
const startDate = computed(() => {
    if (props.week) return new Date(`${props.week}T00:00:00Z`);
    const d = new Date();
    d.setUTCDate(d.getUTCDate() - (totalDays.value - 1));
    return d;
});
const dayLabel = computed(() => {
    if (day.value === null) return null;
    const d = new Date(startDate.value);
    d.setUTCDate(d.getUTCDate() + day.value);
    return t('Day :n of :total · :date', { n: day.value + 1, total: totalDays.value, date: date(d.toISOString().slice(0, 10)) });
});

function stop() {
    if (timer) clearInterval(timer);
    timer = null;
    playing.value = false;
    day.value = null;
    if (map) setFireDay(map, null);
}

function play() {
    if (!map || playing.value) return stop();
    playing.value = true;
    day.value = 0;
    setFireDay(map, 0);
    timer = setInterval(
        () => {
            if (day.value === null || !map) return stop();
            if (day.value >= totalDays.value - 1) {
                clearInterval(timer!);
                timer = null;
                playing.value = false;
                setTimeout(() => !playing.value && stop(), 1500);
                return;
            }
            day.value++;
            setFireDay(map, day.value);
        },
        props.week ? 900 : 350,
    );
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
onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
    map?.remove();
});
</script>

<template>
    <div class="relative overflow-hidden rounded-md bg-neutral-950" :class="height">
        <div ref="el" class="h-full w-full" />

        <button
            class="absolute left-3 top-3 inline-flex items-center gap-2 rounded-md bg-neutral-900/90 px-3 py-1.5 text-sm font-medium text-white hover:bg-neutral-800"
            :aria-label="playing ? t('Pause') : t('Play day by day')"
            @click="play"
        >
            <component :is="playing ? Pause : Play" class="size-4" />
            {{ playing ? t('Pause') : t('Play day by day') }}
        </button>

        <div class="absolute right-3 top-3 flex flex-col items-end gap-2">
            <slot name="controls" />
            <div class="rounded-md bg-neutral-900/90 px-2.5 py-1.5 text-xs text-neutral-200"><FireLegend /></div>
        </div>

        <div class="absolute bottom-3 left-3 w-[min(22rem,calc(100%-1.5rem))] rounded-md bg-neutral-900 p-4 text-neutral-100 shadow-lg">
            <slot name="panel-top" />
            <Loading v-if="loading" class="text-neutral-300" :label="t('Loading fires…')" />
            <template v-else>
                <div class="text-sm text-neutral-300">{{ dayLabel ?? label }}</div>
                <div v-if="week && times" class="mt-1 flex items-baseline gap-2 tabular-nums">
                    <span class="size-2 self-center rounded-full bg-orange-500" />
                    <span class="text-4xl font-semibold text-orange-500">×{{ number(times, 1) }}</span>
                    <span class="text-neutral-300">{{ t('normal') }}</span>
                </div>
                <div class="mt-1 text-sm text-neutral-300">{{ t(':n cells with fire', { n: number(cells) }) }}</div>
                <div v-if="cells" class="mt-2 flex gap-4 text-xs text-neutral-300">
                    <a :href="csvHref" class="underline-offset-4 hover:underline">{{ t('Download CSV') }}</a>
                    <button class="underline-offset-4 hover:underline" @click="downloadImage">{{ t('Download image') }}</button>
                </div>
            </template>
            <label class="mt-3 flex cursor-pointer items-center gap-2 border-t border-neutral-700 pt-3 text-xs text-neutral-300">
                <input v-model="photo" type="checkbox" class="size-3.5 accent-orange-600" />
                {{ t('NASA satellite image of :date', { date: date(photoDay) }) }}
            </label>
        </div>
    </div>
</template>
