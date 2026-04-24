import axios from 'axios';

export const afconApi = {
    async fetchQuarterFinals() {
        const response = await axios.get('/api/afcon/quarter-finals');
        return response.data.data ?? [];
    },

    async fetchMatchDetails(matchId) {
        const response = await axios.get(`/api/afcon/matches/${matchId}`);
        return response.data.data ?? null;
    },
};
