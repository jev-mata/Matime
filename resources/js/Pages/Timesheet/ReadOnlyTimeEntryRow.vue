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

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});
const organization = inject<ComputedRef<Organization>>('organization');
</script>

<template>
    <div class="border-b border-default-background-separator transition min-w-0 bg-row-background"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0 ">
            <div class="sm:flex py-2 min-w-0 items-center justify-between group">
                <div class="flex items-center min-w-0 ">
                    <div
                        class=" text-ellipsis whitespace-nowrap overflow-hidden px-0 h-full min-w-0 pl-3 pr-1 left-0 top-0  text-sm text-text-primary font-medium bg-transparent focus-visible:ring-0 rounded-lg border-0">
                        {{ timeEntry.description }}
                    </div>

                    <ProjectBadge :color="selectedProjectColor" :border="false" tag="button" :name="selectedProjectName"
                        :class="'focus:border-border-tertiary focus:outline-0 focus:bg-card-background-separator min-w-0 relative  '
                            ">
                        <div class="flex items-center lg:space-x-1 min-w-0">
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

                </div>
                <div class="flex items-center font-medium space-x-1 lg:space-x-2">

                    <div class="flex-1">

                        {{ formatStartEnd(timeEntry.start, timeEntry.end, organization?.time_format) }}
                    </div>
                    <TimeEntryRowDurationInput :start="timeEntry.start" :end="timeEntry.end">
                    </TimeEntryRowDurationInput>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
