<script setup lang="ts">
import BoxPicker from '@/components/fire/BoxPicker.vue';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { dateTime, t } from '@/lib/i18n';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { BellRing, CalendarCheck, Mail, MessageSquare, Radar, Scale, Send } from 'lucide-vue-next';
import { computed } from 'vue';

type Place = { slug: string; name: string; bbox: number[] };
type Subscription = { token: string; name: string; email: string; bbox: number[]; last_alert_week: string | null; last_sent_at: string | null };

defineProps<{ areas: Place[]; places: Place[]; boxes: Place[]; mine: Subscription[]; mailer: string }>();

const page = usePage();
const status = computed(() => (page.props.flash as { status?: string } | undefined)?.status);

const sample = useForm({ email: '' });

function sendSample() {
    sample.post('/alerts/sample', { preserveScroll: true, onSuccess: () => sample.reset() });
}

const form = useForm<{ name: string; email: string; phone: string; bbox: number[] | null }>({ name: '', email: '', phone: '', bbox: null });

function submit() {
    form.post('/subscriptions', { preserveScroll: true });
}

const steps = [
    {
        icon: Radar,
        title: 'Every hour',
        body: 'The latest NASA FIRMS detections (MODIS, NOAA-20, NOAA-21) are pulled, snapped to the 1 km grid and harmonized.',
    },
    {
        icon: Scale,
        title: 'Every day at 08:00 (Santo Domingo)',
        body: 'For each subscribed box, the last complete week is compared with the same week in every other year since 2000: median and median absolute deviation.',
    },
    {
        icon: BellRing,
        title: 'A week is flagged when',
        body: 'it is at least 3 deviations above that median, at least twice it, and has at least 10 burning cell-days. Elevated ≥3, high ≥5, extreme ≥8.',
    },
    {
        icon: Mail,
        title: 'One email per flagged week',
        body: 'Written from the computed numbers with a fixed template, with a link to the map of that week. No AI model touches the alert.',
    },
    { icon: MessageSquare, title: 'Phone alerts', body: 'SMS and WhatsApp use the same decision; delivery is coming soon.' },
];
</script>

<template>
    <Head :title="t('Fire alerts')" />
    <SiteLayout>
        <h1 class="text-2xl font-semibold tracking-tight">{{ t('Fire alerts by email') }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
            {{
                t(
                    'Draw the zone you care about. When one of its weeks is unusual against 26 years of the same week, you get one email, and you can test it right away. No account needed; each alert has its own page to switch email or phone on and off, and signed-in users see all theirs here.',
                )
            }}
        </p>

        <p
            v-if="status"
            class="mt-4 rounded-md border border-orange-300/60 bg-orange-50/60 px-3 py-2 text-sm dark:border-orange-900 dark:bg-orange-950/20"
        >
            {{ status }}
        </p>

        <form
            class="mt-6 flex flex-wrap items-end gap-2 rounded-md border border-orange-300/60 bg-orange-50/60 p-4 text-sm dark:border-orange-900 dark:bg-orange-950/20"
            @submit.prevent="sendSample"
        >
            <div class="mr-auto">
                <p class="font-medium">{{ t('See what an alert looks like') }}</p>
                <p class="text-muted-foreground">
                    {{ t('We email you the strongest week ever recorded in the Dominican Republic, marked as a test. Up to 5 a day per address.') }}
                </p>
            </div>
            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                <input
                    v-model="sample.email"
                    type="email"
                    required
                    :placeholder="t('Email')"
                    class="w-full rounded-md border bg-background px-3 sm:w-72"
                />
                <button class="rounded-md bg-foreground px-3 py-2 text-background disabled:opacity-40" :disabled="sample.processing">
                    {{ sample.processing ? t('Sending…') : t('Send me a sample alert') }}
                </button>
            </div>
            <p v-if="sample.errors.email" class="w-full text-red-600">{{ sample.errors.email }}</p>
        </form>

        <form class="mt-6" @submit.prevent="submit">
            <BoxPicker v-model="form.bbox" :areas="areas" :places="places" :boxes="boxes" />
            <p v-if="form.errors.bbox" class="mt-2 text-sm text-red-600">{{ form.errors.bbox }}</p>

            <div class="mt-4 grid gap-3 sm:grid-cols-[1.2fr_1.4fr_1fr_auto]">
                <label class="text-sm">
                    <span class="text-muted-foreground">{{ t('Zone name') }}</span>
                    <input v-model="form.name" required maxlength="80" class="mt-1 w-full rounded-md border bg-background px-2 py-1.5" />
                    <span v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</span>
                </label>
                <label class="text-sm">
                    <span class="text-muted-foreground">{{ t('Email') }}</span>
                    <input v-model="form.email" type="email" required class="mt-1 w-full rounded-md border bg-background px-2 py-1.5" />
                    <span v-if="form.errors.email" class="text-red-600">{{ form.errors.email }}</span>
                </label>
                <label class="text-sm">
                    <span class="text-muted-foreground">{{ t('Phone · coming soon') }}</span>
                    <input
                        v-model="form.phone"
                        type="tel"
                        maxlength="30"
                        :placeholder="t('SMS and WhatsApp')"
                        class="mt-1 w-full rounded-md border bg-background px-2 py-1.5"
                    />
                </label>
                <button
                    class="self-end rounded-md bg-foreground px-3 py-1.5 text-sm text-background disabled:opacity-40"
                    :disabled="!form.bbox || form.processing"
                >
                    {{ form.processing ? t('Saving…') : t('Get alerts for this box') }}
                </button>
            </div>
        </form>

        <section v-if="mine.length" class="mt-10">
            <h2 class="font-semibold">{{ t('My alerts') }}</h2>
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-normal">{{ t('Zone') }}</th>
                        <th class="font-normal">{{ t('Email') }}</th>
                        <th class="font-normal">{{ t('Last alert sent') }}</th>
                        <th />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in mine" :key="s.token" class="border-t">
                        <td class="py-2">{{ s.name }}</td>
                        <td>{{ s.email }}</td>
                        <td class="tabular-nums">{{ s.last_sent_at ? dateTime(s.last_sent_at) : '—' }}</td>
                        <td class="text-right">
                            <Link :href="`/subscriptions/${s.token}`" class="underline-offset-4 hover:underline">{{ t('Open') }} →</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section id="how" class="mt-12 scroll-mt-6">
            <h2 class="text-xl font-semibold tracking-tight">{{ t('How an alert is decided') }}</h2>
            <ol class="mt-6">
                <li v-for="(s, i) in steps" :key="i" class="relative grid gap-x-6 gap-y-1 pb-7 pl-12 last:pb-0 md:grid-cols-[16rem_1fr]">
                    <span v-if="i < steps.length - 1" class="absolute left-[15px] top-8 h-full w-px bg-orange-300 dark:bg-orange-800" />
                    <span
                        class="absolute left-0 top-0 grid size-8 place-items-center rounded-full border border-orange-300 bg-background text-orange-600 dark:border-orange-800"
                    >
                        <component :is="s.icon" class="size-4" />
                    </span>
                    <div class="font-medium">{{ t(s.title) }}</div>
                    <p class="text-sm text-muted-foreground">{{ t(s.body) }}</p>
                </li>
            </ol>
            <p class="mt-6 inline-flex items-center gap-2 text-sm text-muted-foreground">
                <Send class="size-4" />
                {{ t('After subscribing you can send yourself a test alert: the strongest week ever recorded in your box, marked as a test.') }}
            </p>
            <p class="mt-2 inline-flex items-center gap-2 text-sm text-muted-foreground">
                <CalendarCheck class="size-4" />
                {{
                    t(
                        'In the code: fire:notify runs daily from the scheduler and queues one SendFireAlert job per flagged box; a queue worker delivers them every minute.',
                    )
                }}
            </p>
        </section>
    </SiteLayout>
</template>
