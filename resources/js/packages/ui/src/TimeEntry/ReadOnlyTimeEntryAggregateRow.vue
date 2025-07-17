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
};

const currentProject = computed(() => {
    return props.projects.find(
        (iteratingProject) => iteratingProject.id === props.timeEntry.timeEntries[0].project_id
    );
});

const currentTask = computed(() => {
    return props.tasks.find(
        (iteratingTasks) => iteratingTasks.id === props.timeEntry.task_id
    );
});

const selectedProjectName = computed(() => {

    return currentProject.value?.name ? currentProject.value.name : 'No Project';
});

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});
</script>

<template>
    <div class="border-b border-default-background-separator bg-row-background min-w-0 transition"
        data-testid="time_entry_row"  title="Editing disabled for submitted entries" >
        <MainContainer class="min-w-0 opacity-40">
            <div class="grid grid-cols-8 py-1.5 items-center min-w-0 justify-between group">
                <div class="flex items-center col-span-2 min-w-0">
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
                        <div class="min-w-0 pl-3 pr-1 text-sm text-text-primary font-medium truncate" :title="timeEntry.description">
                            {{ timeEntry.description }}
                        </div>

                    </div>
                </div>
                <div class="flex items-center px-2  w-full col-span-3 bg-secondary min-w-0"  :title=" selectedProjectName +currentTask.name ">
                    <ProjectBadge :color="selectedProjectColor" :border="false" tag="button"
                        @click="expanded = !expanded" :name="selectedProjectName" :class="'focus:border-border-tertiary w-full focus:outline-0 focus:bg-card-background-separator min-w-0 relative w-35'
                            ">
                        <div class="flex items-center lg:space-x-1 min-w-0">
                            <span class="whitespace-nowrap text-xs lg:text-sm">
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
                <div class="flex items-center px-2  w-full  col-span-1  bg-secondary min-w-0">
                    <TagBadge :border="false" size="large"
                        class="border-0 sm:px-1.5 text-icon-default group-focus-within/dropdown:text-text-primary"
                        :name="timeEntryTags(timeEntry.tags).map((tag: Tag) => tag.name).join(', ')
                            "></TagBadge>
                </div>
                <div class="flex items-center space-x-2  justify-end pl-4">
                    <div class="text-sm font-medium whitespace-nowrap">
                        {{ formatStartEnd(timeEntry.start, timeEntry.end, organization?.time_format) }}
                    </div>
                </div>
                <div class="flex items-center font-medium lg:space-x-2">
                    <button
                        class="flex-1 text-text-primary min-w-[90px] px-2.5 py-1.5 bg-transparent text-right hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-semibold focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary"
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

                    <div class="" title="Editing disabled for submitted entries">
                        <LockClosedIcon class="w-4" title="Editing disabled for submitted entries"></LockClosedIcon>
                    </div>
                </div>
            </div>
        </MainContainer>
        <div v-if="expanded" class="w-full border-t border-default-background-separator bg-black/15">
            <TimeEntryRow v-for="subEntry in timeEntry.timeEntries" :key="subEntry.id" :projects="projects"
                :enable-estimated-time :tasks="tasks" :selected="!!selectedTimeEntries.find(
                    (filterEntry: TimeEntry) =>
                        filterEntry.id === subEntry.id
                )
                    " :clients :currency="currency" :tags="tags"
                :delete-time-entry="() => deleteTimeEntries([subEntry])" :time-entry="subEntry"
                @selected="emit('selected', [subEntry])" @unselected="emit('unselected', [subEntry])"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
