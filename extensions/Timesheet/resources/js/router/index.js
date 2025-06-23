import { createRouter, createWebHistory } from 'vue-router';
import timesheetRoutes from './modules/timesheet';

const routes = [
  // ...your core routes
  ...timesheetRoutes, // <- spread the array
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
