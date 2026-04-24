<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useMatchesStore } from '../stores/matches';

const route = useRoute();
const matchesStore = useMatchesStore();
const { loadingDetails, error } = storeToRefs(matchesStore);
const matchDetails = ref(null);

const match = computed(
    () => matchDetails.value || matchesStore.getMatchDetailsById(route.params.id) || matchesStore.getMatchById(route.params.id),
);

const formatDate = (date) =>
    new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(date));

onMounted(async () => {
    matchDetails.value = await matchesStore.fetchMatchDetails(route.params.id);
});
</script>

<template>
    <section v-if="loadingDetails" class="rounded bg-slate-200 p-4 text-sm text-slate-700">
        Loading match details...
    </section>

    <section v-else-if="error" class="rounded-lg border border-rose-200 bg-rose-50 p-6 text-rose-700">
        {{ error }}
    </section>

    <section v-else-if="match" class="space-y-5">
        <RouterLink
            :to="{ name: 'matches' }"
            class="inline-flex rounded border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
        >
            Back to results
        </RouterLink>

        <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 text-sm text-slate-600">{{ match.stage }}</div>

            <h2 class="mb-3 text-2xl font-bold text-slate-900">
                {{ match.homeTeam }} {{ match.homeScore }} - {{ match.awayScore }} {{ match.awayTeam }}
            </h2>

            <div class="space-y-1 text-sm text-slate-700">
                <p><strong>Date:</strong> {{ formatDate(match.date) }}</p>
                <p><strong>Time:</strong> {{ match.time }}</p>
                <p><strong>Stadium:</strong> {{ match.stadium }}</p>
                <p><strong>Man of the Match:</strong> {{ match.manOfTheMatch || 'Not available' }}</p>
            </div>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-3 text-lg font-semibold text-slate-900">Main Statistics</h3>
            <ul class="space-y-2 text-sm text-slate-700">
                <li
                    v-for="stat in match.statistics"
                    :key="stat.label"
                    class="flex items-center justify-between rounded bg-slate-50 px-3 py-2"
                >
                    <span class="w-1/4 text-left font-medium">{{ stat.home }}</span>
                    <span class="w-2/4 text-center text-slate-600">{{ stat.label }}</span>
                    <span class="w-1/4 text-right font-medium">{{ stat.away }}</span>
                </li>
            </ul>
        </article>

        <div class="grid gap-5 md:grid-cols-2">
            <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-3 text-lg font-semibold text-slate-900">Scorers</h3>
                <ul v-if="match.scorers?.length" class="space-y-2 text-sm text-slate-700">
                    <li
                        v-for="(scorer, index) in match.scorers"
                        :key="`${scorer.player}-${index}`"
                        class="rounded bg-slate-50 px-3 py-2"
                    >
                        <strong>{{ scorer.minute }}</strong> - {{ scorer.player }} ({{ scorer.team }})
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-600">No scorer details available.</p>
            </article>

            <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-3 text-lg font-semibold text-slate-900">Cards</h3>
                <ul v-if="match.cards?.length" class="space-y-2 text-sm text-slate-700">
                    <li
                        v-for="(card, index) in match.cards"
                        :key="`${card.player}-${index}`"
                        class="rounded bg-slate-50 px-3 py-2"
                    >
                        <strong>{{ card.minute }}</strong> - {{ card.player }} ({{ card.team }}) -
                        {{ card.type }}
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-600">No card details available.</p>
            </article>
        </div>
    </section>

    <section v-else class="rounded-lg border border-rose-200 bg-rose-50 p-6 text-rose-700">
        Match not found.
    </section>
</template>
