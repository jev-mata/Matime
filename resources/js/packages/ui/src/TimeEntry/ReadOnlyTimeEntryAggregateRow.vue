<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TimeTrackerStartStop from '../TimeTrackerStartStop.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
    Organization,
} from '@/packages/api/src';
import { ref, inject, type ComputedRef, computed } from 'vue';
import {
    formatHumanReadableDuration,
    formatStartEnd,
} from '@/packages/ui/src/utils/time';

import { twMerge } from 'tailwind-merge';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/ReadOnlyTimeEntryRow.vue';
import GroupedItemsCountButton from '@/packages/ui/src/GroupedItemsCountButton.vue';
import type { TimeEntriesGroupedByType } from '@/types/time-entries';
import { Checkbox } from '@/packages/ui/src';
import TagBadge from '@/packages/ui/src/Tag/TagBadge.vue';
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
import { ChevronRightIcon, LockClosedIcon } from '@heroicons/vue/16/solid';
const props = defineProps<{
    timeEntry: TimeEntriesGroupedByType;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    onStartStopClick: (timeEntry: TimeEntry) => void;
    updateTimeEntries: (ids: string[], changes: Partial<TimeEntry>) => void;
    updateTimeEntry: (timeEntry: TimeEntry) => void;
    deleteTimeEntries: (timeEntries: TimeEntry[]) => void;
    currency: string;
    selectedTimeEntries: TimeEntry[];
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();
const emit = defineEmits<{
    selected: [TimeEntry[]];
    unselected: [TimeEntry[]];
}>();

const organization = inject<ComputedRef<Organization>>('organization');

const expanded = ref(false);

function onSelectChange(checked: boolean) {
    if (checked) {
        emit('selected', [...props.timeEntry.timeEntries]);
    } else {
        emit('unselected', [...props.timeEntry.timeEntries]);
    }
}

function timeEntryTags(model: string[]) {
    return props.tags.filter((tag) => model.includes(tag.id));
}

const currentProject = computed(() => {
    return props.projects.find(
        (iteratingProject) =>
            iteratingProject.id === props.timeEntry.timeEntries[0].project_id
    );
});

const currentTask = computed(() => {
    return props.tasks.find(
        (iteratingTasks) => iteratingTasks.id === props.timeEntry.task_id
    );
});

const selectedProjectName = computed(() => {
    return currentProject.value?.name
        ? currentProject.value.name
        : 'No Project';
});

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});
</script>

<template>
    <div class="hover:border-y dark:border-[#3F4961] dark:bg-[#171E31] bg-row-background min-w-0 transition" data-testid="time_entry_row"
        title="Editing disabled for submitted entries">
        <MainContainer class="min-w-0 opacity-40">
            <div class="grid sm:grid-cols-8 md:grid-cols-8 xl:grid-cols-8 2xl:grid-cols-10 items-center py-2 group">
                <div class="flex  items-center 2xl:col-span-2 xl:col-span-3 md:col-span-3 sm:col-span-3 min-w-0">
                    <Checkbox :checked="timeEntry.timeEntries.every(
                        (aggregateTimeEntry: TimeEntry) =>
                            selectedTimeEntries.includes(
                                aggregateTimeEntry
                            )
                    )
                        " @update:checked="onSelectChange" />
                    <div class="flex items-center min-w-0">
                        <GroupedItemsCountButton :expanded="expanded" @click="expanded = !expanded">
                            {{ timeEntry?.timeEntries?.length }}
                        </GroupedItemsCountButton>
                        <div class="min-w-0 pl-3 pr-1 text-sm text-text-primary font-medium truncate"
                            title="{{timeEntry.description}}">
                            {{ timeEntry.description }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center px-2 w-full 2xl:col-span-3 xl:col-span-5 md:col-span-5   sm:col-span-5    bg-secondary min-w-0"
                    title="{{selectedProjectName +currentTask.name}}">
                    <ProjectBadge :color="selectedProjectColor" :border="false" tag="button"
                        @click="expanded = !expanded" :name="selectedProjectName"
                        :class="'focus:dark:border-[#3F4961] w-full focus:outline-0 focus:bg-card-background-separator min-w-0 relative w-35 bg-transparent dark:bg-transparent hover:bg-transparent '">
                        <div class="flex items-center lg:space-x-1 min-w-0">
                            <span
                                class="whitespace-nowrap text-xs lg:text-sm  font-semibold  text-text-primary text-base">
                                {{ selectedProjectName }}
                            </span>
                            <ChevronRightIcon v-if="currentTask" class="w-4 lg:w-5 text-text-secondary shrink-0">
                            </ChevronRightIcon>
                            <div v-if="currentTask"
                                class="min-w-0 shrink text-xs lg:text-sm truncate  font-semibold  text-text-primary text-base">
                                {{ currentTask.name }}
                            </div>
                        </div>
                    </ProjectBadge>
                </div>
                <div class="flex items-center     md:col-span-2 sm:col-span-2 xl:col-span-2 2xl:col-span-2 bg-secondary min-w-0">
                    <div class="flex-1 flex   grid grid-cols-5  ">
                        <TagBadge :border="false" size="large"
                            class="border-0 sm:px-1.5  col-span-4 text-icon-default group-focus-within/dropdown:text-text-primary  bg-transparent dark:bg-transparent hover:bg-transparent"
                            :name="timeEntryTags(timeEntry.tags)
                                    .map((tag: Tag) => tag.name)
                                    .join(', ')
                                "></TagBadge>
                        <BillableToggleButton :model-value="timeEntry.billable"
                            :class="twMerge('col-span-1 opacity-50 group-hover:opacity-100 focus-visible:opacity-100')"
                            size="small" ></BillableToggleButton>
                    </div>
                </div>
                <div class="flex items-center    md:col-span-4 xl:col-span-4 2xl:col-span-3 justify-end sm:col-span-6">
                    <div class=" text-sm   whitespace-nowrap  px-4">
                        {{
                            formatStartEnd(
                                timeEntry.start,
                                timeEntry.end,
                                organization?.time_format
                            )
                        }}
                    </div>
                    <button
                        class="  text-text-primary min-w-[90px] px-2.5 py-1.5 bg-transparent text-right hover:bg-card-background rounded-lg border border-transparent hover:dark:border-[#3F4961] text-sm font-semibold focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary"
                        @click="expanded = !expanded">
                        {{
                            formatHumanReadableDuration(
                                timeEntry.duration ?? 0,
                                organization?.interval_format,
                                organization?.number_format
                            )
                        }}
                    </button>

                    <TimeTrackerStartStop :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex group-hover:opacity-100 focus-visible:opacity-100" @changed="
                            onStartStopClick(timeEntry)
                            "></TimeTrackerStartStop>

                    <div class="px-2" title="Editing disabled for submitted entries">
                        <LockClosedIcon class="w-4" title="Editing disabled for submitted entries"></LockClosedIcon>
                    </div>
                </div>
            </div>
        </MainContainer>
        <div v-if="expanded" class="w-full border-t dark:border-[#3F4961] bg-black/15">
            <TimeEntryRow v-for="subEntry in timeEntry.timeEntries" :key="subEntry.id" :projects="projects"
                :enable-estimated-time :tasks="tasks" :selected="!!selectedTimeEntries.find(
                    (filterEntry: TimeEntry) =>
                        filterEntry.id === subEntry.id
                )
                    " :clients :currency="currency" :tags="tags" :delete-time-entry="() => deleteTimeEntries([subEntry])"
                :time-entry="subEntry" @selected="emit('selected', [subEntry])"
                @unselected="emit('unselected', [subEntry])"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
