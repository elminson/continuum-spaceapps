<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { number, t } from '@/lib/i18n';
import { Info } from 'lucide-vue-next';

defineProps<{ example: { area: string; modis: number; viirs: number; days: number } | null }>();

const symbols = [
    ['r', 'The ratio: how many MODIS fire cells correspond to one VIIRS fire cell. Below 1 because VIIRS sees more.'],
    ['Σ', 'Sum: add up the values of every day being compared.'],
    [
        'MODIS cell-days',
        'For one day, the number of 1 km grid cells where MODIS (Terra or Aqua) saw fire. A cell counts once a day, however many pixels hit it.',
    ],
    ['VIIRS cell-days', 'The same count for VIIRS (Suomi NPP, NOAA-20 or NOAA-21), on the same 1 km grid.'],
    ['÷', 'Divide the MODIS total by the VIIRS total.'],
] as const;
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <button
                class="inline-flex items-center gap-1 rounded-md px-1.5 py-1 text-sm text-muted-foreground hover:bg-background hover:text-foreground"
                :aria-label="t('What does this formula mean?')"
            >
                <Info class="size-4" /> <span class="font-sans">{{ t('What does this mean?') }}</span>
            </button>
        </DialogTrigger>
        <DialogContent class="max-w-xl">
            <DialogHeader>
                <DialogTitle>{{ t('Reading the ratio') }}</DialogTitle>
                <DialogDescription>
                    {{
                        t(
                            'It measures how much more fire VIIRS reports than MODIS on days both sensors were flying, so one record can be put on the other one’s scale.',
                        )
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="rounded-md bg-muted px-4 py-3 text-center font-mono text-sm">r = Σ MODIS cell-days ÷ Σ VIIRS cell-days</div>

            <dl class="space-y-3 text-sm">
                <div v-for="[symbol, meaning] in symbols" :key="symbol" class="grid grid-cols-[8.5rem_1fr] gap-3">
                    <dt class="font-mono font-medium">{{ symbol }}</dt>
                    <dd class="text-muted-foreground">{{ t(meaning) }}</dd>
                </div>
            </dl>

            <div v-if="example" class="border-t pt-4 text-sm">
                <div class="font-medium">{{ t('Example: :area', { area: example.area }) }}</div>
                <p class="mt-1 text-muted-foreground">
                    {{ t('On the :days days both sensors flew since 2012:', { days: number(example.days) }) }}
                </p>
                <p class="mt-2 font-mono tabular-nums">
                    r = {{ number(example.modis) }} ÷ {{ number(example.viirs) }} =
                    <span class="font-semibold text-orange-700 dark:text-orange-400">{{ number(example.modis / example.viirs, 3) }}</span>
                </p>
                <p class="mt-2 text-muted-foreground">
                    {{
                        t(
                            'So VIIRS sees about :x times more fire cells here. On days without MODIS, VIIRS × r gives a MODIS-scale value; before 2012, MODIS ÷ r gives a VIIRS-scale value.',
                            {
                                x: number(example.viirs / example.modis, 1),
                            },
                        )
                    }}
                </p>
            </div>
            <p class="text-xs text-muted-foreground">
                {{ t('Each calendar month gets its own r when it has at least 20 shared days; otherwise the all-year r is used.') }}
            </p>
        </DialogContent>
    </Dialog>
</template>
