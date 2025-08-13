<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TimeTracker from "@/Components/TimeTracker.vue";
import MainContainer from "@/packages/ui/src/MainContainer.vue";

import { ClockIcon } from "@heroicons/vue/24/outline";
import { usePage } from "@inertiajs/vue3";

import { SecondaryButton } from '@/packages/ui/src';
import type { Project, Tag, Task, TimeEntry } from '@/packages/api/src';
import { useQueryClient } from "@tanstack/vue-query";
import { computed } from "vue";
import axios from "axios";
import { ref } from "vue";
import dayjs from "dayjs";
import ProjectBadge from "@/packages/ui/src/Project/ProjectBadge.vue";
import Modal from "@/packages/ui/src/Modal.vue";
import Button from "@/Components/ui/button/Button.vue";
import type { TimeEntriesGroupedByType } from "@/types/time-entries";
import TagBadge from "@/packages/ui/src/Tag/TagBadge.vue";
// import type { PreviewSheet } from "@/packages/ui/src/TimeEntry/TimeEntryGroupedTable.vue";

import { useNotificationsStore } from '@/utils/notification';
function selectProject(
  id: string | null,
  projects: Project[]
): Project | undefined {
  return id ? projects.find(p => p.id === id) : undefined;
}

function selectTask(
  id: string | null,
  tasks: Task[]
): Task | undefined {
  return id ? tasks.find(t => t.id === id) : undefined;
}

const isLoading=ref<boolean>(false);
function selectTags(
  ids: string[],   // many IDs ↠ many tags
  tags: Tag[]
): string[] {
  // Fast O(n) lookup table
  const nameMap = new Map(tags.map(t => [t.id, t.name]));

  return ids
    .map(id => nameMap.get(id))
    .filter((name): name is string => !!name);  // drop missing IDs
}

type GroupEntries = {
  groupEntries: Record<string, TimeEntriesGroupedByType[]>
  projects: Project[];
  tasks: Task[];
  tags: Tag[];
  isSubmit: boolean;
  periodSelected:string;
}
const props = defineProps<GroupEntries>();
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

const { addNotification } = useNotificationsStore();
const emit = defineEmits(['clear', 'getTimesheet']);   // declare event

const TimeEntriesID = ref<string[] | null>(null)
function sumDuration(timeEntryGroup: TimeEntriesGroupedByType[]) {
  return timeEntryGroup.map((entryGroup) =>
    entryGroup.timeEntries.reduce((acc, entry) => acc + (entry?.duration ?? 0), 0)
  );
}

function pluckID(
  timeEntryGroup: Record<string, TimeEntriesGroupedByType[]>
): string[] {
  return Object.values(timeEntryGroup).flatMap(entryGroups =>
    entryGroups.flatMap(entryGroup =>
      entryGroup.timeEntries.map(timeEntry => timeEntry.id)
    )
  );
}


const submit = async () => {
  const IDlist = pluckID(props.groupEntries);
isLoading.value=true;
  if (!IDlist.length) {
    console.warn('No time entry IDs to submit.');
    return;
  }

  const success = await axios.post(
    route('approval.submit'),
    { ids: IDlist, period:props.periodSelected  },
    {
      withCredentials: true,
      headers: { Accept: 'application/json' },
    }
  );

  // Only emit if request succeeded
  if (success) {
    addNotification('success', 'Submitted', 'Entry Submitted');
    emit('clear');
    emit('getTimesheet');
isLoading.value=false;
  } else {

isLoading.value=false;
    addNotification('error', 'Failed', 'Entry Failed to Submit');
  }
};

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: '2-digit'
  })
}
// const formattedFrom = computed(() => formatDate2(props.period.from))
// const formattedTo = computed(() => formatDate2(props.period.to))

function formatTime(dateString: string) {
  return dateString ? dayjs(dateString).format('h:mm A') : '';
}
function duration(_start: string, _end: string) {
  const start = new Date(_start).getTime();
  const end = new Date(_end).getTime();

  if (isNaN(start) || isNaN(end)) return '0h 0m';

  const diffInMinutes = Math.floor((end - start) / (1000 * 60));
  const hours = Math.floor(diffInMinutes / 60);
  const minutes = diffInMinutes % 60;

  return `${hours}h ${minutes}m`;
}

function durationAll(timeEntryGroup: Record<string, TimeEntriesGroupedByType[]>): string {
  let totalMinutes = 0;

  Object.values(timeEntryGroup).forEach((entryGroups) => {
    entryGroups.forEach((entryGroup) => {
      entryGroup.timeEntries.forEach((timeEntry) => {
        const start = new Date(timeEntry.start).getTime();
        const end = timeEntry.end ? new Date(timeEntry.end).getTime() : NaN;

        if (!isNaN(start) && !isNaN(end)) {
          const diffInMinutes = Math.floor((end - start) / (1000 * 60));
          totalMinutes += diffInMinutes;
        }
      });
    });
  });

  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  return `${hours}h ${minutes}m`;
}


</script>

<template>
  <Modal :show="true" max-width="5xl">
    <template #default>
      <div class="col-span-full my-1 px-5 pt-5">
        <h2 class="text-lg font-semibold mb-1">My Entries</h2> 
        <div class="max-h-20 overflow-y-scroll " style="max-height: 50vh;">
          <div v-for="(entries, date2) in props.groupEntries" :key="date2" class="mt-4 mb-4   dark:bg-[#171E31] bg-[#E0E3E5] rounded-lg">
            <h2 class="font-semibold text-md py-2 px-3 bg-quaternary text-secondary">2{{ date2 }}
            </h2>

            <ul class="">
              <li v-for="(entry) in entries" :key="entry.id" class="flex flex-row px-3 rounded border dark:border-[#303F61] dark:bg-[#171E31] bg-[#F3F3F4] py-2">

                <div class="basis-64 flex content-center">
                  {{ formatTime(entry.start) }} - {{ entry.end && formatTime(entry.end) }}
                </div>
                <div class="basis-32 flex content-center">
                  {{ entry.end && duration(entry.start, entry.end) }}
                </div>
                <div class="basis-64 flex content-center">
                  {{ entry.description || 'No description' }}
                </div>
                <div class="basis-64">

                  <ProjectBadge v-if="entry.project_id" :color="selectProject(entry.project_id, props.projects)?.color"
                    :size="'large'" :border="true" :class="'focus:border-border-tertiary w-full focus:outline-0 focus:bg-card-background-separator min-w-0 relative  dark:bg-[#171E31] bg-[#E0E3E5]'" :project="selectProject(entry.project_id, props.projects)"
                      >

                    <div class="flex items-center lg:space-x-1 min-w-0">
                      <span class="whitespace-nowrap text-xs lg:text-sm">
                        {{ selectProject(entry.project_id, props.projects)?.name }}
                      </span>
                      <ChevronRightIcon v-if="entry.task" class="w-4 lg:w-5 text-text-secondary shrink-0">
                      </ChevronRightIcon>
                      <div v-if="entry.task" class="min-w-0 shrink text-xs lg:text-sm truncate">
                        {{ selectTask(entry.task_id, props.tasks)?.name }}
                      </div>
                    </div>
                  </ProjectBadge>
                </div>
                <div class="basis-64 overflow-hidden">
                  <div v-if="entry.tags.length > 0" class=" w-full">
                    <TagBadge class="truncate text-ellipsis overflow-hidden whitespace-nowrap" :border="false"
                      size="large" :name="selectTags(entry.tags, props.tags).join(',')" />
                  </div>
                </div>


              </li>
            </ul>
          </div>

        </div>

        <div
          class=" text-muted-foreground text-sm bold text-text-primary font-semibold text-sm lg:text-base flex items-center mt-2">
          Total Hours:
          <component :is="ClockIcon" v-if="ClockIcon" class="w-5 lg:w-4 text-icon-default ml-2 mr-1"></component>
          <div>{{ durationAll(props.groupEntries) }}</div>
        </div>
      </div>
    </template>

    <template #footer>
      <form @submit.prevent="submit" class="space-y-4 w-full px-5 pb-5">
        <div class="flex w-full items-center space-x-2 lg:space-x-2.5">

                    <SecondaryButton class="px-4 py-2  text-white rounded"
                        :loading="isLoading"
                        @click="emit('clear')">Close
                    </SecondaryButton> 

                    <SecondaryButton   type="submit" class="px-4 py-2  text-white rounded"
                        :loading="isLoading"
                         >Submit
                    </SecondaryButton>  
        </div>
      </form>
    </template>
  </Modal>
</template>