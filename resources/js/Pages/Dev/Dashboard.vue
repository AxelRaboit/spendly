<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/components/ui/AppBadge.vue';
import AppPageHeader from '@/components/ui/AppPageHeader.vue';
import AppPagination from '@/components/ui/AppPagination.vue';
import AppTooltip from '@/components/ui/AppTooltip.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Check, LogIn, Mail, Pencil, Shield, Trash2, UserRound, Wallet, X } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';
import { PlanType } from '@/enums/PlanType';
import { computed, ref } from 'vue';
import { useChartTheme } from '@/composables/ui/useChartTheme';
import { useFmtMonth } from '@/composables/core/useFmtMonth';

const { t } = useI18n();
const { baseOptions, barOptions: barThemeOptions, textColor, gridColor } = useChartTheme();
const { fmtMonth: fmtMonthBase } = useFmtMonth();
function fmtMonth(yyyyMm) { return fmtMonthBase(yyyyMm, { month: 'short', year: 'numeric' }); }

const props = defineProps({
    tab: { type: String, default: 'stats' },
    stats: { type: Object, default: null },
    users: { type: Object, default: null },
    search: { type: String, default: '' },
    parameters: { type: Array, default: () => [] },
    parameterUpdatePath: { type: String, default: '' },
});

// ── Shared options ──────────────────────────────────────────────────────────

const lineOptions = computed(() => ({
    ...barThemeOptions.value,
    scales: {
        x: { ticks: { color: textColor.value }, grid: { color: gridColor.value } },
        y: { ticks: { color: textColor.value }, grid: { color: gridColor.value }, beginAtZero: true },
    },
    plugins: { ...barThemeOptions.value.plugins, legend: { display: false } },
}));

const barOptions = computed(() => ({
    ...barThemeOptions.value,
    scales: {
        x: { ticks: { color: textColor.value }, grid: { color: gridColor.value } },
        y: { ticks: { color: textColor.value }, grid: { color: gridColor.value }, beginAtZero: true },
    },
    plugins: { ...barThemeOptions.value.plugins, legend: { display: false } },
}));

const donutOptions = computed(() => ({
    ...baseOptions.value,
    plugins: {
        legend: { position: 'bottom', labels: { color: textColor.value, padding: 16, boxWidth: 12 } },
    },
}));

// ── Existing charts ─────────────────────────────────────────────────────────

const planDonutData = computed(() => ({
    labels: [t('plan.pro.name'), t('plan.free.name')],
    datasets: [{
        data: [props.stats?.users.pro ?? 0, props.stats?.users.free ?? 0],
        backgroundColor: ['#f59e0b', '#6b7280'],
        borderWidth: 0,
    }],
}));

const usersLineData = computed(() => ({
    labels: props.stats?.usersByMonth.map(m => fmtMonth(m.month)) ?? [],
    datasets: [{
        label: t('admin.stats.users'),
        data: props.stats?.usersByMonth.map(m => m.count) ?? [],
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.1)',
        borderWidth: 2,
        pointRadius: 3,
        tension: 0.4,
        fill: true,
    }],
}));

const transactionsBarData = computed(() => ({
    labels: props.stats?.transactionsByMonth.map(m => fmtMonth(m.month)) ?? [],
    datasets: [{
        label: t('admin.stats.transactions'),
        data: props.stats?.transactionsByMonth.map(m => m.count) ?? [],
        backgroundColor: '#6366f1',
        borderRadius: 6,
    }],
}));

// ── New charts ──────────────────────────────────────────────────────────────

const cumulativeLineData = computed(() => ({
    labels: props.stats?.cumulativeGrowth.map(m => fmtMonth(m.month)) ?? [],
    datasets: [{
        label: t('admin.stats.users'),
        data: props.stats?.cumulativeGrowth.map(m => m.count) ?? [],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16,185,129,0.1)',
        borderWidth: 2,
        pointRadius: 3,
        tension: 0.4,
        fill: true,
    }],
}));

const localeColors = { fr: '#6366f1', en: '#f59e0b', de: '#10b981', es: '#f43f5e' };
const localeLabels = { fr: 'Français', en: 'English', de: 'Deutsch', es: 'Español' };

const localeDonutData = computed(() => ({
    labels: props.stats?.localeDistribution.map(l => localeLabels[l.locale] ?? l.locale) ?? [],
    datasets: [{
        data: props.stats?.localeDistribution.map(l => l.count) ?? [],
        backgroundColor: props.stats?.localeDistribution.map(l => localeColors[l.locale] ?? '#6b7280') ?? [],
        borderWidth: 0,
    }],
}));

const activityDonutData = computed(() => ({
    labels: [t('admin.stats.activeUsers'), t('admin.stats.inactiveUsers')],
    datasets: [{
        data: [props.stats?.activeUsers ?? 0, props.stats?.inactiveUsers ?? 0],
        backgroundColor: ['#10b981', '#6b7280'],
        borderWidth: 0,
    }],
}));

// ── Users tab ───────────────────────────────────────────────────────────────

const searchInput = ref('');
const performSearch = () => {
    router.get(route('dev.dashboard.users'), { search: searchInput.value });
};

const pendingUser = ref(null);
const confirmToggleRole = (user) => { pendingUser.value = user; };
const doToggleRole = () => {
    if (!pendingUser.value) return;
    useForm({}).post(route('dev.dashboard.users.toggle-role', pendingUser.value.id));
    pendingUser.value = null;
};

const pendingImpersonateUser = ref(null);
const confirmImpersonate = (user) => { pendingImpersonateUser.value = user; };
const doImpersonate = () => {
    if (!pendingImpersonateUser.value) return;
    useForm({}).post(route('dev.dashboard.users.impersonate', pendingImpersonateUser.value.id));
    pendingImpersonateUser.value = null;
};

const pendingDeleteUser = ref(null);
const confirmDeleteUser = (user) => { pendingDeleteUser.value = user; };
const doDeleteUser = () => {
    if (!pendingDeleteUser.value) return;
    useForm({}).delete(route('dev.dashboard.users.destroy', pendingDeleteUser.value.id));
    pendingDeleteUser.value = null;
};

// ── Parameters tab ──────────────────────────────────────────────────────────

const editingKey = ref(null);
const editingValue = ref('');
const editSaving = ref(false);

const startEdit = (param) => {
    editingKey.value = param.key;
    editingValue.value = param.value ?? '';
};

const cancelEdit = () => {
    editingKey.value = null;
};

const saveParameter = async (param) => {
    if (editSaving.value) return;
    editSaving.value = true;
    const url = props.parameterUpdatePath.replace('__key__', encodeURIComponent(param.key));
    try {
        const res = await fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ value: editingValue.value }),
        });
        if (res.ok) {
            param.value = editingValue.value || null;
            editingKey.value = null;
        }
    } finally {
        editSaving.value = false;
    }
};

// ── Invitations tab ─────────────────────────────────────────────────────────

const invitationForm = useForm({
    email: '',
    message: '',
    credential_email: '',
    credential_password: '',
});

const submitInvitation = () => {
    invitationForm.post(route('dev.dashboard.invitations.send'), {
        onSuccess: () => invitationForm.reset(),
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="t('admin.title')" />

        <template #header>
            <AppPageHeader
                :crumbs="[
                    { label: t('admin.title'), href: route('dev.dashboard.stats') },
                    { label: tab === 'stats' ? t('admin.stats.title') : tab === 'users' ? t('admin.users.title') : tab === 'parameters' ? t('admin.parameters.title') : t('admin.invitations.title') },
                ]"
            />
        </template>

        <div class="space-y-6 p-4 sm:p-6">
            <div class="border-b border-base overflow-x-auto">
                <nav class="flex gap-6 sm:gap-8 whitespace-nowrap min-w-max">
                    <Link
                        :href="route('dev.dashboard.stats')"
                        class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base"
                        :class="tab === 'stats' ? 'border-primary text-primary font-medium' : 'border-transparent text-secondary hover:text-primary'"
                    >
                        {{ t('admin.stats.title') }}
                    </Link>
                    <Link
                        :href="route('dev.dashboard.users')"
                        class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base"
                        :class="tab === 'users' ? 'border-primary text-primary font-medium' : 'border-transparent text-secondary hover:text-primary'"
                    >
                        {{ t('admin.users.title') }}
                    </Link>
                    <Link
                        :href="route('dev.dashboard.invitations')"
                        class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base"
                        :class="tab === 'invitations' ? 'border-primary text-primary font-medium' : 'border-transparent text-secondary hover:text-primary'"
                    >
                        {{ t('admin.invitations.title') }}
                    </Link>
                    <Link
                        :href="route('dev.dashboard.parameters')"
                        class="py-3 px-1 border-b-2 transition-colors text-sm sm:text-base"
                        :class="tab === 'parameters' ? 'border-primary text-primary font-medium' : 'border-transparent text-secondary hover:text-primary'"
                    >
                        {{ t('admin.parameters.title') }}
                    </Link>
                </nav>
            </div>

            <!-- Stats tab -->
            <div v-if="tab === 'stats' && stats" class="space-y-6">
                <!-- KPI cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-surface border border-base/60 rounded-xl p-4">
                        <p class="text-xs text-secondary mb-1">{{ t('admin.stats.usersTotal') }}</p>
                        <p class="text-2xl font-bold text-primary">{{ stats.users.total }}</p>
                    </div>
                    <div class="bg-surface border border-base/60 rounded-xl p-4">
                        <p class="text-xs text-secondary mb-1">{{ t('admin.stats.usersPro') }}</p>
                        <p class="text-2xl font-bold text-amber-400">{{ stats.users.pro }}</p>
                    </div>
                    <div class="bg-surface border border-base/60 rounded-xl p-4">
                        <p class="text-xs text-secondary mb-1">{{ t('admin.stats.transactionsTotal') }}</p>
                        <p class="text-2xl font-bold text-primary">{{ stats.transactions.total.toLocaleString() }}</p>
                    </div>
                    <div class="bg-surface border border-base/60 rounded-xl p-4">
                        <p class="text-xs text-secondary mb-1">{{ t('admin.stats.usersNewThisMonth') }}</p>
                        <p class="text-2xl font-bold text-indigo-400">{{ stats.users.newThisMonth }}</p>
                    </div>
                </div>

                <!-- Row 1: inscriptions + transactions par mois -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-surface border border-base/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('admin.stats.usersPerMonth') }}</h3>
                        <div class="h-48 sm:h-64">
                            <LineChart :data="usersLineData" :options="lineOptions" />
                        </div>
                    </div>
                    <div class="bg-surface border border-base/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('admin.stats.transactionsPerMonth') }}</h3>
                        <div class="h-48 sm:h-64">
                            <BarChart :data="transactionsBarData" :options="barOptions" />
                        </div>
                    </div>
                </div>

                <!-- Row 2: croissance cumulée + langues -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-surface border border-base/60 rounded-xl p-5 lg:col-span-2">
                        <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('admin.stats.cumulativeGrowth') }}</h3>
                        <div class="h-48 sm:h-64">
                            <LineChart :data="cumulativeLineData" :options="lineOptions" />
                        </div>
                    </div>
                    <div class="bg-surface border border-base/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('admin.stats.localeDistribution') }}</h3>
                        <div class="h-48 sm:h-64">
                            <DoughnutChart :data="localeDonutData" :options="donutOptions" />
                        </div>
                    </div>
                </div>

                <!-- Row 3: plan + activité + wallets/autres -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-surface border border-base/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('admin.stats.planDistribution') }}</h3>
                        <div class="h-48 sm:h-56">
                            <DoughnutChart :data="planDonutData" :options="donutOptions" />
                        </div>
                    </div>

                    <div class="bg-surface border border-base/60 rounded-xl p-5">
                        <h3 class="text-sm font-semibold text-secondary mb-4">{{ t('admin.stats.activityDistribution') }}</h3>
                        <div class="h-48 sm:h-56">
                            <DoughnutChart :data="activityDonutData" :options="donutOptions" />
                        </div>
                    </div>

                    <div class="bg-surface border border-base/60 rounded-xl p-5 flex flex-col gap-4">
                        <div class="flex flex-col justify-between gap-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-secondary">{{ t('admin.stats.wallets') }}</h3>
                                <div class="p-2 rounded-lg bg-indigo-600/10">
                                    <Wallet class="w-4 h-4 text-indigo-400" />
                                </div>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-primary">{{ stats.wallets }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ t('admin.stats.walletsTotal') }}</p>
                            </div>
                            <p class="text-xs text-secondary border-t border-base pt-3">
                                {{ t('admin.stats.avgPerUser') }}
                                <span class="font-semibold text-primary ml-1">
                                    {{ stats.users.total > 0 ? (stats.wallets / stats.users.total).toFixed(1) : '—' }}
                                </span>
                            </p>
                        </div>

                        <div class="border-t border-base pt-3 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-secondary">{{ t('admin.stats.goals') }}</span>
                                <span class="text-sm font-bold text-primary">{{ stats.goals }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-secondary">{{ t('admin.stats.recurring') }}</span>
                                <span class="text-sm font-bold text-primary">{{ stats.recurring }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-secondary">{{ t('admin.stats.transactionsThisMonth') }}</span>
                                <span class="text-sm font-bold text-indigo-400">{{ stats.transactions.thisMonth.toLocaleString() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users tab -->
            <div v-if="tab === 'users' && users" class="space-y-4">
                <div class="flex gap-2">
                    <input
                        v-model="searchInput"
                        type="text"
                        :placeholder="t('admin.users.searchPlaceholder')"
                        class="flex-1 px-4 py-2 rounded-lg bg-surface-2 border border-base text-primary placeholder-secondary focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        v-on:keyup.enter="performSearch"
                    >
                    <button
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors"
                        v-on:click="performSearch"
                    >
                        {{ t('admin.users.search') }}
                    </button>
                </div>

                <div class="bg-surface border border-base rounded-lg overflow-x-auto">
                    <table class="w-full min-w-[560px]">
                        <thead class="bg-surface-2 border-b border-base">
                            <tr>
                                <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.users.name') }}</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary hidden sm:table-cell">{{ t('admin.users.email') }}</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary hidden md:table-cell">{{ t('admin.users.plan') }}</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-sm font-semibold text-primary hidden lg:table-cell">{{ t('admin.users.created') }}</th>
                                <th class="px-4 sm:px-6 py-3 text-right text-sm font-semibold text-primary">{{ t('admin.users.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-surface-2/50 transition-colors">
                                <td class="px-4 sm:px-6 py-3 text-sm text-primary">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ user.name }}</span>
                                        <AppBadge v-if="user.is_demo" variant="indigo">Demo</AppBadge>
                                    </div>
                                    <div class="text-xs text-secondary sm:hidden">{{ user.email }}</div>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-sm text-secondary hidden sm:table-cell">{{ user.email }}</td>
                                <td class="px-4 sm:px-6 py-3 text-sm hidden md:table-cell">
                                    <AppBadge :variant="user.plan === PlanType.Pro ? 'amber' : 'default'">
                                        {{ t('plan.' + user.plan + '.name') }}
                                    </AppBadge>
                                </td>
                                <td class="px-4 sm:px-6 py-3 text-sm text-secondary hidden lg:table-cell">{{ new Date(user.created_at).toLocaleDateString() }}</td>
                                <td class="px-4 sm:px-6 py-3 text-sm text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <AppTooltip :text="t('admin.users.impersonate', { name: user.name })">
                                            <button
                                                class="p-1.5 rounded text-muted hover:text-amber-400 transition-colors"
                                                v-on:click="confirmImpersonate(user)"
                                            >
                                                <LogIn class="w-4 h-4" />
                                            </button>
                                        </AppTooltip>
                                        <AppTooltip :text="user.roles.some(r => r.name === 'ROLE_DEV') ? t('admin.users.makeUser') : t('admin.users.makeDev')">
                                            <button
                                                class="p-1.5 rounded text-muted transition-colors"
                                                :class="user.roles.some(r => r.name === 'ROLE_DEV')
                                                    ? 'hover:text-indigo-400'
                                                    : 'hover:text-rose-400'"
                                                v-on:click="confirmToggleRole(user)"
                                            >
                                                <component :is="user.roles.some(r => r.name === 'ROLE_DEV') ? UserRound : Shield" class="w-4 h-4" />
                                            </button>
                                        </AppTooltip>
                                        <AppTooltip :text="t('admin.users.deleteUser', { name: user.name })">
                                            <button
                                                class="p-1.5 rounded text-muted hover:text-red-400 transition-colors"
                                                v-on:click="confirmDeleteUser(user)"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </AppTooltip>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <AppPagination :meta="users" />
            </div>

            <!-- Parameters tab -->
            <div v-if="tab === 'parameters'">
                <div class="bg-surface border border-base rounded-xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-base bg-surface-2">
                        <p class="text-sm font-semibold text-primary">{{ t('admin.parameters.title') }}</p>
                    </div>
                    <table class="w-full">
                        <thead class="bg-surface-2 border-b border-base">
                            <tr>
                                <th class="px-5 py-3 text-left text-sm font-semibold text-primary w-1/3">{{ t('admin.parameters.key') }}</th>
                                <th class="px-5 py-3 text-left text-sm font-semibold text-primary w-1/4">{{ t('admin.parameters.value') }}</th>
                                <th class="px-5 py-3 text-left text-sm font-semibold text-primary">{{ t('admin.parameters.description') }}</th>
                                <th class="px-4 py-3 w-16" />
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base">
                            <tr
                                v-for="param in parameters"
                                :key="param.key"
                                class="group hover:bg-surface-2/50 transition-colors"
                            >
                                <td class="px-5 py-3 font-mono text-sm text-indigo-500 font-medium w-1/3">{{ param.key }}</td>
                                <td class="px-5 py-3 w-1/4">
                                    <template v-if="editingKey === param.key">
                                        <input
                                            v-model="editingValue"
                                            class="w-full bg-surface-2 border border-base rounded-lg px-2.5 py-1 text-sm text-primary focus:outline-none focus:border-indigo-500"
                                            autofocus
                                            v-on:keydown.enter="saveParameter(param)"
                                            v-on:keydown.esc="cancelEdit"
                                        >
                                    </template>
                                    <span v-else class="text-sm font-medium text-primary">{{ param.value ?? '—' }}</span>
                                </td>
                                <td class="px-5 py-3 text-sm text-secondary">{{ param.description ?? '' }}</td>
                                <td class="px-4 py-3 w-16">
                                    <div class="flex items-center gap-1 justify-end">
                                        <template v-if="editingKey === param.key">
                                            <button
                                                :disabled="editSaving"
                                                class="p-1.5 text-muted hover:text-emerald-400 transition-colors"
                                                v-on:click="saveParameter(param)"
                                            >
                                                <Check class="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                class="p-1.5 text-muted hover:text-rose-400 transition-colors"
                                                v-on:click="cancelEdit"
                                            >
                                                <X class="w-3.5 h-3.5" />
                                            </button>
                                        </template>
                                        <button
                                            v-else
                                            class="p-1.5 text-muted hover:text-primary transition-colors opacity-0 group-hover:opacity-100"
                                            v-on:click="startEdit(param)"
                                        >
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invitations tab -->
            <div v-if="tab === 'invitations'" class="max-w-lg space-y-4">
                <p class="text-sm text-secondary">{{ t('admin.invitations.description') }}</p>

                <form class="space-y-4" v-on:submit.prevent="submitInvitation">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-primary">{{ t('admin.invitations.email') }}</label>
                        <input
                            v-model="invitationForm.email"
                            type="email"
                            :placeholder="t('admin.invitations.emailPlaceholder')"
                            class="w-full px-4 py-2 rounded-lg bg-surface-2 border border-base text-primary placeholder-secondary focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                        <p v-if="invitationForm.errors.email" class="text-xs text-red-400">{{ invitationForm.errors.email }}</p>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-primary">{{ t('admin.invitations.message') }}</label>
                        <textarea
                            v-model="invitationForm.message"
                            rows="5"
                            :placeholder="t('admin.invitations.messagePlaceholder')"
                            class="w-full px-4 py-2 rounded-lg bg-surface-2 border border-base text-primary placeholder-secondary focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                        />
                        <p v-if="invitationForm.errors.message" class="text-xs text-red-400">{{ invitationForm.errors.message }}</p>
                    </div>

                    <div class="border border-base rounded-lg p-4 space-y-3 bg-surface-2/50">
                        <p class="text-xs text-secondary">{{ t('admin.invitations.credentialsHint') }}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-primary">{{ t('admin.invitations.credentialEmail') }}</label>
                                <input
                                    v-model="invitationForm.credential_email"
                                    type="email"
                                    :placeholder="t('admin.invitations.emailPlaceholder')"
                                    class="w-full px-4 py-2 rounded-lg bg-surface border border-base text-primary placeholder-secondary focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                <p v-if="invitationForm.errors.credential_email" class="text-xs text-red-400">{{ invitationForm.errors.credential_email }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-primary">{{ t('admin.invitations.credentialPassword') }}</label>
                                <input
                                    v-model="invitationForm.credential_password"
                                    type="text"
                                    class="w-full px-4 py-2 rounded-lg bg-surface border border-base text-primary placeholder-secondary focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                <p v-if="invitationForm.errors.credential_password" class="text-xs text-red-400">{{ invitationForm.errors.credential_password }}</p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="invitationForm.processing"
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg transition-colors"
                    >
                        <Mail class="w-4 h-4" />
                        {{ invitationForm.processing ? t('admin.invitations.sending') : t('admin.invitations.send') }}
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>

    <ConfirmModal
        :show="!!pendingImpersonateUser"
        :message="pendingImpersonateUser ? t('admin.users.confirmImpersonate', { name: pendingImpersonateUser.name }) : ''"
        :confirm-label="pendingImpersonateUser ? t('admin.users.impersonate', { name: pendingImpersonateUser.name }) : ''"
        confirm-variant="primary"
        v-on:confirm="doImpersonate"
        v-on:cancel="pendingImpersonateUser = null"
    />

    <ConfirmModal
        :show="!!pendingUser"
        :message="pendingUser ? t('admin.users.confirmToggle', { name: pendingUser.name }) : ''"
        :confirm-label="pendingUser?.roles.some(r => r.name === 'ROLE_DEV') ? t('admin.users.makeUser') : t('admin.users.makeDev')"
        confirm-variant="primary"
        v-on:confirm="doToggleRole"
        v-on:cancel="pendingUser = null"
    />

    <ConfirmModal
        :show="!!pendingDeleteUser"
        :message="pendingDeleteUser ? t('admin.users.confirmDelete', { name: pendingDeleteUser.name }) : ''"
        confirm-variant="danger"
        v-on:confirm="doDeleteUser"
        v-on:cancel="pendingDeleteUser = null"
    />
</template>
