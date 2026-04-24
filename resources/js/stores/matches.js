import { defineStore } from 'pinia';
import { afconApi } from '../services/afconApi';
import { quarterFinalMatches } from '../data/quarterFinals';

export const useMatchesStore = defineStore('matches', {
    state: () => ({
        matches: [],
        matchDetailsById: {},
        matchesPerPage: 2,
        currentPage: 1,
        loadingMatches: false,
        loadingDetails: false,
        error: null,
        notice: null,
    }),
    getters: {
        totalMatches: (state) => state.matches.length,
        totalPages: (state) => Math.ceil(state.matches.length / state.matchesPerPage),
        paginatedMatches: (state) => {
            const startIndex = (state.currentPage - 1) * state.matchesPerPage;
            return state.matches.slice(startIndex, startIndex + state.matchesPerPage);
        },
        getMatchById: (state) => (matchId) =>
            state.matches.find((match) => match.id === Number(matchId)),
        getMatchDetailsById: (state) => (matchId) => state.matchDetailsById[Number(matchId)] ?? null,
    },
    actions: {
        setPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },
        async fetchMatches() {
            this.loadingMatches = true;
            this.error = null;
            this.notice = null;

            try {
                const apiMatches = await afconApi.fetchQuarterFinals();

                if (apiMatches && apiMatches.length > 0) {
                    this.matches = apiMatches;
                } else {
                    this.matches = quarterFinalMatches;
                    this.notice = 'Live data is unavailable; showing sample quarter-final results.';
                }

                this.currentPage = 1;
            } catch (error) {
                this.matches = quarterFinalMatches;
                this.notice = 'Live data is unavailable; showing sample quarter-final results.';
            } finally {
                this.loadingMatches = false;
            }
        },
        async fetchMatchDetails(matchId) {
            const id = Number(matchId);

            if (this.matchDetailsById[id]) {
                return this.matchDetailsById[id];
            }

            this.loadingDetails = true;
            this.error = null;

            try {
                const matchDetails = await afconApi.fetchMatchDetails(id);

                if (matchDetails) {
                    this.matchDetailsById[id] = matchDetails;
                    return matchDetails;
                }

                const fallbackMatch = this.getMatchById(id);
                return fallbackMatch;
            } catch (error) {
                const fallbackMatch = this.getMatchById(id);
                return fallbackMatch;
            } finally {
                this.loadingDetails = false;
            }
        },
    },
});
