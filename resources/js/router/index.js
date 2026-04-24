import { createRouter, createWebHistory } from 'vue-router';
import MatchListView from '../views/MatchListView.vue';
import MatchDetailView from '../views/MatchDetailView.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'matches',
            component: MatchListView,
        },
        {
            path: '/matches/:id',
            name: 'match-details',
            component: MatchDetailView,
            props: true,
        },
    ],
});

export default router;
