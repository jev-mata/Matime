<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import type {
    Client,
    Project,
    Tag,
    Task,
    TimeEntry,
} from '@/packages/api/src';
import TimeEntryRowDurationInput from '@/packages/ui/src/TimeEntry/TimeEntryRowDurationInput.vue';
import { computed, inject, type ComputedRef } from 'vue';

import { twMerge } from 'tailwind-merge';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import TagBadge from '@/packages/ui/src/Tag/TagBadge.vue';
import { ChevronRightIcon, LockClosedIcon } from '@heroicons/vue/16/solid';
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
import type { Organization } from '@/packages/api/src';
import {
    formatStartEnd,
} from '@/packages/ui/src/utils/time';
const props = defineProps<{
    timeEntry: TimeEntry;//
    indent?: boolean;
    projects: Project[];//
    tasks: Task[];//
    tags: Tag[];//
    clients: Client[];// 
}>();

const emit = defineEmits<{ selected: []; unselected: [] }>();


const project = defineModel<string | null>('project', {
    default: null,
});

const task = defineModel<string | null>('task', {
    default: null,
});


function timeEntryTags(model: string[]) {
    return props.tags.filter((tag) => model.includes(tag.id));
};
function onSelectChange(checked: boolean) {
    if (checked) {
        emit('selected');
    } else {
        emit('unselected');
    }
}

const currentProject = computed(() => {
    return props.projects.find(
        (iteratingProject) => iteratingProject.id === props.timeEntry.project_id
    );
});

const currentTask = computed(() => {
    console.log("tasks", props.tasks);
    return props.tasks.find(
        (iteratingTasks) => iteratingTasks.id === props.timeEntry.task_id
    );
});

const selectedProjectName = computed(() => {
    if (currentProject.value === null) {
        return 'No Project';
    }
    if (!currentProject.value?.name) {
        return 'No Project';
    }
    return currentProject.value?.name;
});

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});
const organization = inject<ComputedRef<Organization>>('organization');
</script>

<template>
    <div class="  transition min-w-0 bg-row-background"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0 dark:bg-[#171e31]">
            <div class=" grid 2xl:grid-cols-12 xl:grid-cols-12 lg:grid-cols-6 md:grid-cols-6 sm:grid-cols-6 xs:grid-cols-6 py-2 min-w-0 items-center justify-between group">

                <div :title="timeEntry.description || ''"
                    class=" 2xl:col-span-3 xl:col-span-3 lg:col-span-3 md:col-span-3 sm:col-span-6 xs:col-span-6 truncate text-ellipsis whitespace-nowrap overflow-hidden px-0  h-full min-w-0 pl-3 pr-1 left-0 top-0  text-sm text-text-primary font-medium bg-transparent focus-visible:ring-0 rounded-lg border-0">
                    {{ timeEntry.description }}
                </div>
                <ProjectBadge :color="selectedProjectColor" :border="false" tag="button" :name="selectedProjectName"
                    :class="'2xl:col-span-4 xl:col-span-4 lg:col-span-3 md:col-span-3 sm:col-span-6 xs:col-span-6 focus:border-border-tertiary focus:outline-0 focus:bg-card-background-separator min-w-0 relative  '
                        ">
                    <div :title="selectedProjectName + ' > ' + (currentTask && currentTask.name)"
                        class="flex  items-center lg:space-x-1 min-w-0">
                        <span class="whitespace-nowrap text-xs lg:text-sm text-primary">
                            {{ selectedProjectName }}
                        </span>
                        <ChevronRightIcon v-if="currentTask" class="w-4 lg:w-5 text-text-secondary shrink-0">
                        </ChevronRightIcon>
                        <div v-if="currentTask" class="min-w-0 shrink text-xs lg:text-sm truncate">
                            {{ currentTask.name }}
                        </div>
                    </div>
                </ProjectBadge>

                <div class="grid grid-cols-7   2xl:col-span-3   xl:col-span-2 lg:col-span-4 md:col-span-4 sm:col-span-3 xs:col-span-3">
                    <div :border="false" :title="timeEntryTags(timeEntry.tags).map((tag: Tag) => tag.name).join(', ')"
                         size="large"
                        class="border-0 col-span-6 px-2 text-sm truncate sm:px-1.5 text-icon-default group-focus-within/dropdown:text-text-primary">
                        {{timeEntryTags(timeEntry.tags).map((tag: Tag) => tag.name).join(', ')}}</div>


                    <BillableToggleButton v-if="timeEntry.billable" :model-value="timeEntry.billable"
                        :class="twMerge('opacity-50 col-span-1  group-hover:opacity-100 focus-visible:opacity-100')"
                        size="small">
                    </BillableToggleButton>
                </div>

                <div class="  grid grid-cols-8 2xl:col-span-2 xl:col-span-3 lg:col-span-2 md:col-span-2  sm:col-span-3 xs:col-span-3 font-medium  ">

                    <div class=" col-span-6  ">

                        {{ formatStartEnd(timeEntry.start, timeEntry.end, organization?.time_format) }}
                    </div>
                    <TimeEntryRowDurationInput :class="'col-span-2'" :start="timeEntry.start" :end="timeEntry.end">
                    </TimeEntryRowDurationInput>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
