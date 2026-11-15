<script setup lang="ts">
import type { AlertRow } from '@/components/fire/types';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { date, dateTime, number, t } from '@/lib/i18n';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Subscription = {
    email_enabled: boolean;
    phone_enabled: boolean;
    token: string;
    name: string;
    email: string;
    phone: string | null;
    bbox: number[];
    last_alert_week: string | null;
    last_sent_at: string | null;
    created_at: string;
};

const props = defineProps<{
    subscription: Subscription;
    strongest: AlertRow | null;
    current: AlertRow | null;
    preview: string | null;
    mailer: string;
}>();

const page = usePage();
const status = computed(() => (page.props.flash as { status?: string } | undefined)?.status);
const sending = ref(false);

function sendTest() {
    sending.value = true;
    router.post(`/subscriptions/${props.subscription.token}/test`, {}, { preserveScroll: true, onFinish: () => (sending.value = false) });
}

function toggle(field: 'email_enabled' | 'phone_enabled', value: boolean) {
    router.patch(`/subscriptions/${props.subscription.token}`, { [field]: value }, { preserveScroll: true });
}

function stop() {
    if (confirm(t('Stop alerts for this zone?'))) router.delete(`/subscriptions/${props.subscription.token}`);
}

const mapHref = computed(() => `/explore?bbox=${props.subscription.bbox.join(',')}${props.strongest ? `&week=${props.strongest.week_start}` : ''}`);
</script>

<template>
    <Head :title="t('Fire alerts')" />
    <SiteLayout>
        <p class="text-sm text-muted-foreground">
            <Link href="/alerts/subscribe" class="hover:underline">{{ t('Fire alerts') }}</Link> / {{ subscription.name }}
        </p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ subscription.name }}</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            {{ t('Alerts go to :email.', { email: subscription.email }) }}
            <template v-if="subscription.phone"> {{ t('Phone :phone saved for SMS, coming soon.', { phone: subscription.phone }) }}</template>
        </p>

        <p
            v-if="status"
            class="mt-4 rounded-md border border-orange-300/60 bg-orange-50/60 px-3 py-2 text-sm dark:border-orange-900 dark:bg-orange-950/20"
        >
            {{ status }}
        </p>

        <div class="mt-5 flex flex-wrap gap-6 text-sm">
            <label class="flex cursor-pointer items-center gap-2">
                <input
                    type="checkbox"
                    class="size-4 accent-orange-600"
                    :checked="subscription.email_enabled"
                    @change="toggle('email_enabled', ($event.target as HTMLInputElement).checked)"
                />
                {{ t('Email alerts') }}
            </label>
            <label class="flex items-center gap-2 text-muted-foreground">
                <input type="checkbox" class="size-4" disabled :checked="subscription.phone_enabled" />
                {{ t('Phone alerts · coming soon') }}
            </label>
        </div>

        <dl class="mt-6 grid gap-6 sm:grid-cols-3">
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Right now') }}</dt>
                <dd class="mt-1 font-semibold">
                    <template v-if="current">{{
                        t('Unusual: week of :date, ×:t normal', { date: date(current.week_start), t: number(current.times, 1) })
                    }}</template>
                    <template v-else>{{ t('Normal for the last two weeks') }}</template>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Last alert sent') }}</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ subscription.last_sent_at ? dateTime(subscription.last_sent_at) : t('None yet') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ t('Zone') }}</dt>
                <dd class="mt-1 font-semibold tabular-nums">{{ subscription.bbox.map((v) => v.toFixed(2)).join(', ') }}</dd>
            </div>
        </dl>

        <section class="mt-10 max-w-3xl">
            <h2 class="font-semibold">{{ t('What the email says') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ t('The test alert sends the strongest week ever recorded in this zone.') }}</p>
            <div v-if="preview && strongest" class="mt-3 rounded-md border p-4">
                <div class="text-xs text-muted-foreground">{{ t('Fire alert · :name', { name: subscription.name }) }}</div>
                <p class="mt-2 text-[15px] leading-relaxed">{{ preview }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                    <Link :href="mapHref" class="underline-offset-4 hover:underline">{{ t('See the map of that week') }} →</Link>
                    <Link href="/alerts/subscribe#how" class="text-muted-foreground underline-offset-4 hover:underline"
                        >{{ t('How an alert is decided') }} →</Link
                    >
                </div>
            </div>
            <p v-else class="mt-3 text-sm text-muted-foreground">
                {{ t('No unusual week has ever been found in this box, so there is nothing to send yet.') }}
            </p>
        </section>

        <div class="mt-8 flex flex-wrap items-center gap-4 text-sm">
            <button
                class="rounded-md bg-foreground px-3 py-1.5 text-background disabled:opacity-40"
                :disabled="!strongest || sending"
                @click="sendTest"
            >
                {{ sending ? t('Sending…') : t('Send me a test alert') }}
            </button>
            <span v-if="mailer === 'log'" class="text-muted-foreground">{{
                t('This server has no mailer yet: the email is written to the mail log.')
            }}</span>
            <button class="ml-auto text-muted-foreground hover:text-red-600" @click="stop">{{ t('Stop these alerts') }}</button>
        </div>
    </SiteLayout>
</template>
