<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TimeTracker from "@/Components/TimeTracker.vue";
import MainContainer from "@/packages/ui/src/MainContainer.vue";

import { ClockIcon } from "@heroicons/vue/24/outline";
import { usePage } from "@inertiajs/vue3";

import { defineEmits, defineProps } from 'vue'
import type { Tag } from '@/packages/api/src';
import { useQueryClient } from "@tanstack/vue-query";
import { computed } from "vue";
import axios from "axios";
import { ref } from "vue";
import dayjs from "dayjs";
import ProjectBadge from "@/packages/ui/src/Project/ProjectBadge.vue";
import Modal from "@/packages/ui/src/Modal.vue";
import Button from "@/Components/ui/button/Button.vue";
import type { PreviewSheet } from "@/packages/ui/src/TimeEntry/TimeEntryGroupedTable.vue";

const queryClient = useQueryClient();
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
const props = defineProps<PreviewSheet>();
// const page = usePage<{
//   entries: any[];
//   totalHours: string;
//   totalHoursNotForm: any;
//   isSubmit: boolean;
//   from: string;
//   to: string;
//   timesheet: any[];
//   groups: any[];
// }>();

const emit = defineEmits(['clear', 'getTimesheet']);   // declare event

const form = ref({
  date_start: '',
  date_end: '',
  hours: props.totalHoursNotForm,
})

function formatDate2(dateStr: string) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  })
}
const submit = async () => {
  form.value.date_start = props.period.from;
  form.value.date_end = props.period.to;
  form.value.hours = props.totalHoursNotForm;
  console.log(form.value);
  if (!props.isSubmit) {

    await axios.post('/timesheet/submit', form.value,
      {
        withCredentials: true,
        headers: {
          Accept: 'application/json',
        },
      })
  } else {
    await axios.post('/timesheet/unsubmit', form.value,
      {
        withCredentials: true,
        headers: {
          Accept: 'application/json',
        },
      })

  }
  emit('clear');
  emit('getTimesheet');
}
function formatDate(date: number) {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: '2-digit'
  })
}
const formattedFrom = computed(() => formatDate2(props.period.from))
const formattedTo = computed(() => formatDate2(props.period.to))

function formatTime(dateString: string) {
  return dateString ? dayjs(dateString).format('hh:mm:ss A') : '';
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

</script>

<template>
  <Modal :show="true" max-width="5xl">
    <template #default>
      <div class="col-span-full my-1 p-5">
        <h2 class="text-lg font-semibold mb-1">My Entries</h2>
        <div class="text-md mb-2">{{ formattedFrom }} - {{ formattedTo }}</div>
        <div class="max-h-20 overflow-y-scroll " style="max-height: 50vh;">
          <div v-for="(entries, date2) in props.entries" :key="date2" class="mt-4 mb-4 border">
            <h2 class="font-semibold text-md py-2 px-3 bg-quaternary text-secondary">{{ formatDate(date2) }}</h2>

            <ul class="">
              <li v-for="entry in entries" :key="entry.id" class="flex flex-row px-3 rounded border py-2">

                <div class="basis-64 flex content-center">
                  {{ formatTime(entry.start) }} - {{ formatTime(entry.end) }}
                </div>
                <div class="basis-64 flex content-center">
                  {{ duration(entry) }} - {{ entry.description || 'No description' }}
                </div>
                <div class="basis-64">

                  <ProjectBadge v-if="entry.project" :color="entry.project.color" :size="'large'" :border="true" :class="'focus:border-border-tertiary w-full focus:outline-0 focus:bg-card-background-separator min-w-0 relative '
                    ">

                    <div class="flex items-center lg:space-x-1 min-w-0">
                      <span class="whitespace-nowrap text-xs lg:text-sm">
                        {{ entry.project.name }}
                      </span>
                      <ChevronRightIcon v-if="entry.task" class="w-4 lg:w-5 text-text-secondary shrink-0">
                      </ChevronRightIcon>
                      <div v-if="entry.task" class="min-w-0 shrink text-xs lg:text-sm truncate">
                        {{ entry.task.name }}
                      </div>
                    </div>
                  </ProjectBadge>
                </div>
                <div class="basis-64 overflow-hidden">
                  <div v-if="entry.tags.length > 0" class=" w-full">
                    <TagBadge class="truncate text-ellipsis overflow-hidden whitespace-nowrap" :border="false"
                      size="large" :name="entry.tags.map((tag: Tag) => tag.name).join(', ')" />
                  </div>
                </div>


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
    </template>

    <template #footer>
      <form @submit.prevent="submit" class="space-y-4 w-full px-5 pb-5">
        <div class="flex w-full items-center space-x-2 lg:space-x-2.5">

          <Button @click.prevent="emit('clear')" class="px-4 py-2 bg-blue-600 text-white rounded">
            Close
          </Button>
          <Button type="submit" :class="' px-4 py-2 bg-green-600 text-white rounded'">Submit</Button>
        </div>
      </form>
    </template>
  </Modal>
</template>