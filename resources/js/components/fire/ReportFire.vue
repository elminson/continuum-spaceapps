<script setup lang="ts">
import { t } from '@/lib/i18n';
import { Phone } from 'lucide-vue-next';
</script>

<template>
    <aside class="rounded-md border border-orange-200 bg-orange-50 p-4 text-sm dark:border-orange-900/50 dark:bg-orange-950/30">
        <p class="flex items-center gap-2 font-medium">
            <Phone class="size-4 text-orange-600" /> {{ t('See a fire in the Dominican Republic? Report it now') }}
        </p>
        <ul class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-muted-foreground">
            <li><span class="text-foreground">9-1-1</span> · {{ t('emergencies') }}</li>
            <li><span class="text-foreground">(809) 567-4300 ext. 8000</span> · {{ t('Línea Verde, Ministry of Environment') }}</li>
            <li><span class="text-foreground">(849) 356-6400</span> · WhatsApp</li>
            <li>
                <a href="https://lineaverde.gob.do" target="_blank" rel="noopener" class="underline underline-offset-4">lineaverde.gob.do</a> ·
                {{ t('web and the Línea Verde RD app') }}
            </li>
        </ul>
        <p class="mt-2 text-xs text-muted-foreground">
            {{
                t(
                    'A satellite detection is not a confirmed fire; what you see on the ground is. Channels published by the Ministry of Environment (ambiente.gob.do).',
                )
            }}
        </p>
    </aside>
</template>
