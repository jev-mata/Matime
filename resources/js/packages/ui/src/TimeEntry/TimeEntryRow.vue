<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import TimeEntryRangeSelector from '@/packages/ui/src/TimeEntry/TimeEntryRangeSelector.vue';
import type {
    Client,
    CreateClientBody,
    CreateProjectBody,
    Member,
    Project,
    Tag,
    Task,
    TimeEntry,
} from '@/packages/api/src';
import TimeEntryDescriptionInput from '@/packages/ui/src/TimeEntry/TimeEntryDescriptionInput.vue';
import TimeEntryRowTagDropdown from '@/packages/ui/src/TimeEntry/TimeEntryRowTagDropdown.vue';
import TimeEntryRowDurationInput from '@/packages/ui/src/TimeEntry/TimeEntryRowDurationInput.vue';
import TimeEntryMoreOptionsDropdown from '@/packages/ui/src/TimeEntry/TimeEntryMoreOptionsDropdown.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import { computed } from 'vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { Checkbox } from '@/packages/ui/src';
import { twMerge } from 'tailwind-merge';

import {
    type TimeEntriesQueryParams,
} from '@/packages/api/src';

const props = defineProps<{
    timeEntry: TimeEntry;
    indent?: boolean;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    members?: Member[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    onStartStopClick: () => void;
    deleteTimeEntry: () => void;
    updateTimeEntry: (timeEntry: TimeEntry) => void;
    duplicateTimeEntry?: (timeEntry: TimeEntry) => void;
    currency: string;
    showMember?: boolean;
    showDate?: boolean;
    selected?: boolean;
    canCreateProject: boolean;
    enableEstimatedTime: boolean;
}>();

const emit = defineEmits<{ selected: []; unselected: [] }>();

function updateTimeEntryDescription(description: string) {
    props.updateTimeEntry({ ...props.timeEntry, description });
}

function updateTimeEntryTags(tags: string[]) {
    props.updateTimeEntry({ ...props.timeEntry, tags });
}

function updateTimeEntryBillable(billable: boolean) {
    props.updateTimeEntry({ ...props.timeEntry, billable });
}

function updateStartEndTime(start: string, end: string | null) {
    props.updateTimeEntry({ ...props.timeEntry, start, end });
}

function updateProjectAndTask(projectId: string, taskId: string) {
    props.updateTimeEntry({
        ...props.timeEntry,
        project_id: projectId,
        task_id: taskId,
    });
}

const memberName = computed(() => {
    if (props.members) {
        const member = props.members.find(
            (member) => member.user_id === props.timeEntry.user_id
        );
        if (member) {
            return member.name;
        }
    }
    return '';
});

function onSelectChange(checked: boolean) {
    if (checked) {
        emit('selected');
    } else {
        emit('unselected');
    }
}
</script>

<template>
    <div class="border-b border-default-background-separator transition min-w-0 bg-row-background"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0">
            <div class="grid grid-cols-8   py-2 min-w-0 items-center  group">
                <div class="flex col-span-2  items-center min-w-0">
                    <Checkbox :checked="selected" @update:checked="onSelectChange" />
                    <div v-if="indent === true" class="w-10 h-7"></div>
                    <TimeEntryDescriptionInput class="min-w-0 mr-4" :model-value="timeEntry.description
                        " @changed="updateTimeEntryDescription"></TimeEntryDescriptionInput>
                    <div v-if="showMember && members" class="text-sm px-2">
                        {{ memberName }}
                    </div>
                </div>
                <div class="flex w-full col-span-3 text-sm  px-2">
                    <TimeTrackerProjectTaskDropdown :create-project :create-client :can-create-project :clients
                        :projects="projects" :tasks="tasks" :show-badge-border="false" :project="timeEntry.project_id"
                        :currency="currency" :enable-estimated-time :task="timeEntry.task_id
                            " @changed="updateProjectAndTask"></TimeTrackerProjectTaskDropdown>
                </div>
                <div class="px-2 flex  bg-secondary min-w-0">
                    <div class="flex-1 ">
                    <TimeEntryRowTagDropdown :create-tag :tags="tags" :model-value="timeEntry.tags"
                        @changed="updateTimeEntryTags"></TimeEntryRowTagDropdown></div>
                    <!-- <div class="flex-1 text-sm px-2 justify-end ">
                        <BillableToggleButton :model-value="timeEntry.billable"
                            :class="twMerge('opacity-50 group-hover:opacity-100 focus-visible:opacity-100')"
                            size="small" @changed="
                                updateTimeEntryBillable
                            "></BillableToggleButton>
                    </div> -->
                </div>
                <div class="flex items-center space-x-2 col-span-2 justify-end  ">
                    <TimeEntryRangeSelector class="hidden lg:block" :start="timeEntry.start" :end="timeEntry.end"
                        :show-date @changed="
                            updateStartEndTime
                        "></TimeEntryRangeSelector>
                    <TimeEntryRowDurationInput :start="timeEntry.start" :end="timeEntry.end" @changed="
                        updateStartEndTime
                    "></TimeEntryRowDurationInput>
                    <TimeTrackerStartStop :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex focus-visible:opacity-100 group-hover:opacity-100"
                        @changed="onStartStopClick"></TimeTrackerStartStop>
                    <TimeEntryMoreOptionsDropdown :entry="timeEntry" @duplicate="duplicateTimeEntry"
                        :haveduplicate="true" @delete="deleteTimeEntry"></TimeEntryMoreOptionsDropdown>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
