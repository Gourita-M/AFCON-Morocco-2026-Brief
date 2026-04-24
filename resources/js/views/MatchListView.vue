<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useMatchesStore } from '../stores/matches';

const matchesStore = useMatchesStore();
const { currentPage, paginatedMatches, totalMatches, totalPages, loadingMatches, error, notice } =
    storeToRefs(matchesStore);

const hasPreviousPage = computed(() => currentPage.value > 1);
const hasNextPage = computed(() => currentPage.value < totalPages.value);

const formatDate = (date) =>
    new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

const goToPage = (page) => {
    matchesStore.setPage(page);
};

onMounted(async () => {
    await matchesStore.fetchMatches();
});
</script>

<template>
    <section class="space-y-4">
        <p v-if="loadingMatches" class="rounded bg-slate-200 px-3 py-2 text-sm text-slate-700">
            Loading quarter-final results...
        </p>

        <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
            {{ error }}
        </p>

        <p v-if="notice" class="rounded border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700">
            {{ notice }}
        </p>

        <p class="text-sm text-slate-600">
            Showing all {{ totalMatches }} quarter-final matches.
        </p>

        <article
            v-for="match in paginatedMatches"
            :key="match.id"
            class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
                <span>{{ match.stage }}</span>
                <span>{{ formatDate(match.date) }} at {{ match.time }}</span>
            </div>

            <div class="mb-4 flex items-center justify-between gap-4">
                <div class="flex-1 text-left font-semibold text-slate-900">{{ match.homeTeam }}</div>
                <div class="text-2xl font-bold text-slate-900">
                    {{ match.homeScore }} - {{ match.awayScore }}
                </div>
                <div class="flex-1 text-right font-semibold text-slate-900">{{ match.awayTeam }}</div>
            </div>

            <div class="mb-4 text-sm text-slate-600">
                <strong>Stadium:</strong> {{ match.stadium }}
            </div>

            <div class="mb-5 text-sm text-slate-600">
                <strong>Man of the Match:</strong>
                {{ match.manOfTheMatch || 'Not available' }}
            </div>

            <RouterLink
                :to="{ name: 'match-details', params: { id: match.id } }"
                class="inline-flex rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
            >
                View match details
            </RouterLink>
        </article>

        <nav v-if="totalPages > 0" class="mt-6 flex items-center justify-between">
            <button
                class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!hasPreviousPage"
                @click="goToPage(currentPage - 1)"
            >
                Previous
            </button>

            <span class="text-sm text-slate-600">
                Page {{ currentPage }} of {{ totalPages }}
            </span>

            <button
                class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!hasNextPage"
                @click="goToPage(currentPage + 1)"
            >
                Next
            </button>
        </nav>
    </section>
</template>
