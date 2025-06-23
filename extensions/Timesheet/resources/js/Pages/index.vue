<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TimeTracker from "@/Components/TimeTracker.vue";
import RecentlyTrackedTasksCard from "@/Components/Dashboard/RecentlyTrackedTasksCard.vue";
import LastSevenDaysCard from "@/Components/Dashboard/LastSevenDaysCard.vue";
import TeamActivityCard from "@/Components/Dashboard/TeamActivityCard.vue";
import ThisWeekOverview from "@/Components/Dashboard/ThisWeekOverview.vue";
import ActivityGraphCard from "@/Components/Dashboard/ActivityGraphCard.vue";
import MainContainer from "@/packages/ui/src/MainContainer.vue";
import { canViewMembers } from "@/utils/permissions";
import { useQueryClient } from "@tanstack/vue-query";

import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import AlertDialog from "@/Components/ui/alert-dialog/AlertDialog.vue";
import AlertDialogTitle from "@/Components/ui/alert-dialog/AlertDialogTitle.vue";
import Popover from "@/Components/ui/popover/Popover.vue";
import AlertDialogContent from "@/Components/ui/alert-dialog/AlertDialogContent.vue";
import AlertDialogFooter from "@/Components/ui/alert-dialog/AlertDialogFooter.vue";
import { usePage } from "@inertiajs/vue3";
import NavLink from "@/Components/NavLink.vue";
import { ClockIcon } from "@heroicons/vue/24/outline";
import Button from "@/Components/ui/button/Button.vue";
const queryClient = useQueryClient();

const page = usePage();
const refreshDashboardData = () => {
  // Invalidate all dashboard queries to trigger refetching
  queryClient.invalidateQueries({ queryKey: ["latestTasks"] });
  queryClient.invalidateQueries({ queryKey: ["lastSevenDays"] });
  queryClient.invalidateQueries({ queryKey: ["dailyTrackedHours"] });
  queryClient.invalidateQueries({ queryKey: ["latestTeamActivity"] });
  queryClient.invalidateQueries({ queryKey: ["weeklyProjectOverview"] });
  queryClient.invalidateQueries({ queryKey: ["totalWeeklyTime"] });
  queryClient.invalidateQueries({ queryKey: ["totalWeeklyBillableTime"] });
  queryClient.invalidateQueries({ queryKey: ["totalWeeklyBillableAmount"] });
  queryClient.invalidateQueries({ queryKey: ["weeklyHistory"] });
  queryClient.invalidateQueries({ queryKey: ["timeEntries"] });
};

const openModal = ref(true);


function closeModal() {
  openModal.value = false;
}
function duration(entry: { start: string; end: string }) {
  const start = new Date(entry.start).getTime();
  const end = new Date(entry.end).getTime();

  if (isNaN(start) || isNaN(end)) return '0h 0m';

  const diffInMinutes = Math.floor((end - start) / (1000 * 60));
  const hours = Math.floor(diffInMinutes / 60);
  const minutes = diffInMinutes % 60;

  return `${hours}h ${minutes}m`;
}


const props = defineProps<{ entries: any[]; period: any; totalHours: any, totalHoursNotForm: any }>()
const entries = ref(props.entries)
function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: '2-digit'
  })
}

function goBack() {
  window.history.back();
}

function formatDate2(dateStr: string) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  })
}
const formattedFrom = computed(() => formatDate2(props.period.from))
const formattedTo = computed(() => formatDate2(props.period.to))
const form = ref({
  date_start: props.period.from.split('T')[0],
  date_end: props.period.to.split('T')[0],
  hours: props.totalHoursNotForm,
})


const submit = async () => {
  await axios.post('/api/timesheet/submit', form.value)
  // load()
  goBack();
}
const load = async () => {
  window.location.reload();

}

onMounted(() => {
  // load();
  openModal.value = true; // 👈 open modal when component is mounted
});
</script>

<template>
  <AppLayout title="Dashboard" data-testid="dashboard_view">
    <MainContainer class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
      <TimeTracker @change="refreshDashboardData" />
    </MainContainer>

    <MainContainer
      class="grid gap-5 sm:gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 pt-3 sm:pt-5 pb-4 sm:pb-6 border-b border-default-background-separator items-stretch">

      <div v-if="openModal" @click.self="goBack"
        class="fixed inset-0 z-50 flex items-center justify-center" style="background-color: rgba(0,0,0,0.8);">

        <!-- Modal -->
        <div class=" my-1 p-5 border-2 " style="width: 40%;">
          <div class="col-span-full my-1 py-1"> 
          <h2 class="text-lg font-semibold mb-1">My Entries</h2>
          <div class="text-md mb-2">{{ formattedFrom }} - {{ formattedTo }}</div>
          <div class="max-h-20 overflow-y-scroll " style="max-height: 50vh;">
            <div v-for="(entries, date2) in entries" :key="date2" class="mt-4 mb-4 border">
              <h2 class="font-bold text-md mb-2 py-2 px-3 bg-gray-900 text-gray-400">{{ formatDate(date2) }}</h2>

              <ul class="">
                <li v-for="entry in entries" :key="entry.id" class="py-1 px-3 rounded">
                  {{ duration(entry) }} - {{ entry.description || 'No description' }}
                </li>
              </ul>
            </div>

          </div>

          <div
            class=" text-muted-foreground text-sm bold text-text-primary font-semibold text-sm lg:text-base flex items-center space-x-2 lg:space-x-2.5">
            <component :is="ClockIcon" v-if="ClockIcon" class="w-5 lg:w-4 text-icon-default mr-2"></component>
            <div>{{ props.totalHours }}</div>
          </div>
        </div>
        <form @submit.prevent="submit" class="space-y-4 w-full">
          <div class="flex w-full items-center space-x-2 lg:space-x-2.5">

            <Button @click.prevent="goBack" class="px-4 py-2 bg-blue-600 text-white rounded">
              Close
            </Button>
            <Button type="submit" class=" px-4 py-2 bg-green-600 text-white rounded">Submit</Button>
          </div>
        </form>
      </div>
      </div>

    </MainContainer>
  </AppLayout>
</template>
