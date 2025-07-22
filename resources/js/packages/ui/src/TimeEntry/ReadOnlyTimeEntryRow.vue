<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
// import TimeEntryRangeSelector from '@/packages/ui/src/TimeEntry/TimeEntryRangeSelector.vue';
import type { Client, Project, Tag, Task, TimeEntry } from '@/packages/api/src';
// import TimeEntryDescriptionInput from '@/packages/ui/src/TimeEntry/TimeEntryDescriptionInput.vue';
// import TimeEntryRowTagDropdown from '@/packages/ui/src/TimeEntry/TimeEntryRowTagDropdown.vue';
import TimeEntryRowDurationInput from '@/packages/ui/src/TimeEntry/TimeEntryRowDurationInput.vue';
// import TimeEntryMoreOptionsDropdown from '@/packages/ui/src/TimeEntry/TimeEntryMoreOptionsDropdown.vue';
// import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import { computed, inject, type ComputedRef } from 'vue';
// import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { Checkbox } from '@/packages/ui/src';
// import { twMerge } from 'tailwind-merge';

import TagBadge from '@/packages/ui/src/Tag/TagBadge.vue';
import { ChevronRightIcon, LockClosedIcon } from '@heroicons/vue/16/solid';
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
// import {
//     type TimeEntriesQueryParams,
// } from '@/packages/api/src';

import type { Organization } from '@/packages/api/src';
import { formatStartEnd } from '@/packages/ui/src/utils/time';
const props = defineProps<{
    timeEntry: TimeEntry; //
    indent?: boolean;
    projects: Project[]; //
    tasks: Task[]; //
    tags: Tag[]; //
    clients: Client[]; //
    deleteTimeEntry: () => void;
    showMember?: boolean;
    showDate?: boolean;
    selected?: boolean; //
    enableEstimatedTime: boolean; //
    currency: string;
}>();

const emit = defineEmits<{ selected: []; unselected: [] }>();

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

function timeEntryTags(model: string[]) {
    return props.tags.filter((tag) => model.includes(tag.id));
}

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});
const organization = inject<ComputedRef<Organization>>('organization');
</script>

<template>
    <div
        class="border-b border-default-background-separator transition min-w-0 bg-row-background"
        data-testid="time_entry_row"
        title="Editing disabled for submitted entries">
        <MainContainer class="min-w-0 opacity-40">
            <div
                class="grid sm:grid-cols-5 md:grid-cols-6 xl:grid-cols-8 2xl:grid-cols-8 items-center py-2 group">
                <!-- Checkbox + Description -->
                <div class="flex items-center 2xl:col-span-2 xl:col-span-2 md:col-span-1 min-w-0">
                    <Checkbox
                        :checked="selected"
                        @update:checked="onSelectChange" />
                    <div v-if="indent === true" class="w-10 h-7"></div>
                    <div
                        class="min-w-0 pl-3 pr-1 text-sm text-text-primary font-medium truncate">
                        {{ timeEntry.description }}
                    </div>
                </div>

                <!-- Project/Task Badge -->
                <div class="px-2 w-full 2xl:col-span-3 xl:col-span-2 md:col-span-2 sm:col-span-2 bg-secondary min-w-0">
                    <ProjectBadge
                        :color="selectedProjectColor"
                        :border="false"
                        tag="button"
                        class="flex focus:border-border-tertiary w-full focus:outline-0 focus:bg-card-background-separator min-w-0">
                        <div class="flex lg:space-x-1 min-w-0">
                            <span class="whitespace-nowrap text-xs lg:text-sm  font-semibold  text-text-primary text-base">
                                {{ selectedProjectName }}
                            </span>
                            <ChevronRightIcon
                                v-if="currentTask"
                                class="w-4 lg:w-5 text-text-secondary shrink-0" />
                            <div
                                v-if="currentTask"
                                class="min-w-0 shrink text-xs lg:text-sm truncate  font-semibold  text-text-primary text-base">
                                {{ currentTask.name }}
                            </div>
                        </div>
                    </ProjectBadge>
                </div>

                <!-- Time Range -->
                <!-- Duration Input -->

                <div class="px-2 bg-secondary min-w-0    sm:col-span-1 md:col-span-2 xl:col-span-1 2xl:col-span-1">
                    <TagBadge
                        :border="false"
                        size="large"
                        class="border-0 sm:px-1.5 text-icon-default group-focus-within/dropdown:text-text-primary"
                        :name="
                            timeEntryTags(timeEntry.tags)
                                .map((tag: Tag) => tag.name)
                                .join(', ')
                        "></TagBadge>
                </div>
                <!-- Lock/Action -->
                <div class="flex items-center space-x-2 justify-end 2xl:col-span-1 xl:col-span-2 md:col-span-1 sm:col-span-1">
                    <div class="text-sm font-medium whitespace-nowrap">
                        {{
                            formatStartEnd(
                                timeEntry.start,
                                timeEntry.end,
                                organization?.time_format
                            )
                        }}
                    </div>
                </div>
                <div class="flex items-center font-medium lg:space-x-2 md:col-span-6 xl:col-span-1 2xl:col-span-1">
                    <TimeEntryRowDurationInput
                        :start="timeEntry.start"
                        :end="timeEntry.end"
                        class="flex-1 text-sm" />
                    <TimeTrackerStartStop
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex group-hover:opacity-100 focus-visible:opacity-100" />
                    <div
                        class=""
                        title="Editing disabled for submitted entries">
                        <LockClosedIcon
                            class="w-4"
                            title="Editing disabled for submitted entries" />
                    </div>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
