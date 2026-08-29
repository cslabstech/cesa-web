import { createRouter, createWebHistory } from 'vue-router';
import RequestManPowerView from '../views/RequestManPowerView.vue';
import JobPostingsView from '../views/JobPostingsView.vue';
import JobApplicationsView from '../views/JobApplicationsView.vue';
import RecruitmentProgressView from '../views/RecruitmentProgressView.vue';
import ConfigurationsView from '../views/ConfigurationsView.vue';

const routes = [
    // Manpower Requests
    {
        path: '/admin/request-man-powers',
        alias: ['/rekrutmen/requests', '/rekrutmen'],
        name: 'requests',
        component: RequestManPowerView,
        meta: { title: 'Manpower Requests' }
    },
    // Job Postings
    {
        path: '/admin/job-postings',
        alias: ['/rekrutmen/postings'],
        name: 'postings',
        component: JobPostingsView,
        meta: { title: 'Job Postings' }
    },
    // Job Applications
    {
        path: '/admin/job-applications',
        alias: ['/rekrutmen/applications'],
        name: 'applications',
        component: JobApplicationsView,
        meta: { title: 'Job Applications' }
    },
    // Recruitment Progress
    {
        path: '/admin/recruitment-progress',
        alias: ['/rekrutmen/progress'],
        name: 'progress',
        component: RecruitmentProgressView,
        meta: { title: 'Recruitment Progress' }
    },
    // Configurations
    {
        path: '/admin/configurations',
        alias: ['/rekrutmen/configurations', '/admin/rekrutmen/configurations'],
        name: 'configurations',
        component: ConfigurationsView,
        meta: { title: 'Configurations' }
    },
    // Wildcard fallback
    {
        path: '/:pathMatch(.*)*',
        redirect: '/admin/request-man-powers'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        }
        return { top: 0 };
    }
});

router.afterEach((to) => {
    document.title = (to.meta.title ? `${to.meta.title} - ` : '') + 'YourERP';
});

export default router;
