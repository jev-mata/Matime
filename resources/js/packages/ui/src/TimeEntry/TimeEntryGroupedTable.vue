<script setup lang="ts">
import { ref, onMounted, watchEffect } from 'vue';
import { useRouter } from 'vue-router';
import type {
    CreateClientBody,
    CreateProjectBody,
    CreateTimeEntryBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
} from '@/packages/api/src';
import {
    getDayJsInstance,
    getLocalizedDateFromTimestamp,
} from '@/packages/ui/src/utils/time';
import TimeEntryAggregateRow from '@/packages/ui/src/TimeEntry/TimeEntryAggregateRow.vue';
import TimeEntryRowHeading from '@/packages/ui/src/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/TimeEntryRow.vue';
import type { TimeEntriesGroupedByType } from '@/types/time-entries';
import axios from 'axios';
const selectedTimeEntries = defineModel<TimeEntry[]>('selected', {
    default: [],
});
const props = defineProps<{
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    updateTimeEntry: (entry: TimeEntry) => void;
    updateTimeEntries: (ids: string[], changes: Partial<TimeEntry>) => void;
    deleteTimeEntries: (entries: TimeEntry[]) => void;
    createTimeEntry: (entry: Omit<CreateTimeEntryBody, 'member_id'>) => void;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    currency: string;
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();
type GroupedTimeEntries = Record<
    string, // biMonthKey
    {
        isApproved: boolean;
        isSubmitted: boolean;
        days: Record<string, TimeEntriesGroupedByType[]>; // daily breakdown
    }
>
export type TimesheetRecord = {
    id: string;
    user_id: string;
    date_start: string;   // e.g. '2025-06-16'
    date_end: string;     // e.g. '2025-06-30'
    hours: string;        // stored as string, e.g. '29.11'
    status: 'pending' | 'approved' | 'rejected'; // inferred enum
    approved_at: string | null;
    approved_by: string | null;
    description: string | null;
    created_at: string;
    updated_at: string;
};

export type IsSubmitted = {
    isSubmitted: TimesheetRecord[]
};

function getBimonthlyKey(dateInput: string | Date): string {
    const date = new Date(dateInput);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const isFirstHalf = date.getDate() <= 15;
    const start = isFirstHalf ? '1' : '16';
    const end = isFirstHalf
        ? '15'
        : new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate().toString();

    return `${year}-${month} (${start}-${end})`;
}

function isSameBimonthlyPeriod(entryDate: string | Date, submittedEntries: TimesheetRecord[]): boolean {
    const entryKey = getBimonthlyKey(entryDate);

    return submittedEntries.some((submission) => {
        const submissionKey = getBimonthlyKey(submission.date_end);
        return submissionKey === entryKey;
    });
}


const groupedTimeEntries = ref<GroupedTimeEntries>({});

onMounted(async () => {

    const grouped: GroupedTimeEntries = {}

    const data = await getTimesheet() as IsSubmitted;
    for (const entry of props.timeEntries) {
        if (entry.end === null) continue

        const date = getDayJsInstance()(entry.start);
        const year = date.year();
        const month = String(date.month() + 1).padStart(2, '0');
        const dayKey = date.format('YYYY-MM-DD');
        const isFirstHalf = date.date() <= 15;
        const startLabel = isFirstHalf ? '1' : '16';
        const endLabel = isFirstHalf
            ? '15'
            : String(date.daysInMonth()) // Get last day of month

        const periodLabel = `${startLabel}-${endLabel}`
        const biMonthKey = `${year}-${month} (${periodLabel})`

        if (!grouped[biMonthKey]) {
            grouped[biMonthKey] = {
                isApproved: false,
                isSubmitted: isSameBimonthlyPeriod(entry.start, data.isSubmitted),
                days: {}
            }
        }

        if (!grouped[biMonthKey].days[dayKey]) {
            grouped[biMonthKey].days[dayKey] = []
        }
        // Now find if this entry matches an existing grouped entry (by type/project/task)
        const existingGroup = grouped[biMonthKey].days[dayKey]
        const index = existingGroup.findIndex(
            (e) =>
                e.project_id === entry.project_id &&
                e.task_id === entry.task_id &&
                e.billable === entry.billable &&
                e.description === entry.description
        )

        if (index !== -1) {
            const group = existingGroup[index]
            group.timeEntries.push(entry)
            group.duration = (group.duration ?? 0) + (entry.duration ?? 0)

            if (getDayJsInstance()(entry.start).isBefore(getDayJsInstance()(group.start))) {
                group.start = entry.start
            }
            if (getDayJsInstance()(entry.end).isAfter(getDayJsInstance()(group.end))) {
                group.end = entry.end
            }
        } else {
            existingGroup.push({ ...entry, timeEntries: [entry] })
        }
    }
    console.log(grouped);
    groupedTimeEntries.value = grouped;
})

function startTimeEntryFromExisting(entry: TimeEntry) {
    props.createTimeEntry({
        project_id: entry.project_id,
        task_id: entry.task_id,
        start: getDayJsInstance().utc().format(),
        end: null,
        billable: entry.billable,
        description: entry.description,
        tags: [...entry.tags],
    });
}
function sumDuration(timeEntries: TimeEntry[]) {
    return timeEntries.reduce((acc, entry) => acc + (entry?.duration ?? 0), 0);
}
function selectAllTimeEntries(value: TimeEntriesGroupedByType[]) {
    for (const timeEntry of value) {
        if ('timeEntries' in timeEntry) {
            for (const subTimeEntry of timeEntry.timeEntries) {
                selectedTimeEntries.value.push(subTimeEntry);
            }
        } else {
            selectedTimeEntries.value.push(timeEntry);
        }
    }
}
function unselectAllTimeEntries(value: TimeEntriesGroupedByType[]) {
    selectedTimeEntries.value = selectedTimeEntries.value.filter(
        (timeEntry) => {
            return !value.find(
                (filterTimeEntry) =>
                    filterTimeEntry.id === timeEntry.id ||
                    filterTimeEntry.timeEntries?.find(
                        (subTimeEntry) => subTimeEntry.id === timeEntry.id
                    )
            );
        }
    );
}
function getFirstDayKey(days: Record<string, any>) {
  const keys = Object.keys(days);
  return keys.length ? keys[0] : null;
}

function SubmitBTN(date: Date | string) {
    window.location.href = `/time/submit?date=${date}`;
}
function unSubmitBTN(date: Date | string) {

    window.location.href = `/time/unsubmit?date=${date}`;
}
const getTimesheet = async () => {

    const response = await axios.get('/api/time/showAll', {
        withCredentials: true,
        headers: {
            Accept: 'application/json',
        }
    });
    const data = response.data;

    return data;
}
onMounted(async () => {
}); 
</script>

<template>

    <div v-for="(bimonthly, bimonthlykey) in groupedTimeEntries" :key="bimonthlykey">
        <div class=" bg-gray-800 mt-2 border-1 p-1">

            <button @click="SubmitBTN(getFirstDayKey(bimonthly.days))" v-if="!bimonthly.isSubmitted"
                class=" p-2 border-1 mx-2 button text-blue-400">
                submit
            </button>
            <button @click="unSubmitBTN(getFirstDayKey(bimonthly.days))" v-if="!bimonthly.isApproved && bimonthly.isSubmitted"
                class=" p-2 border-1 mx-2 button text-blue-400">
                unsubmit
            </button>{{ bimonthlykey }}
            <span v-if="bimonthly.isApproved" class="ml-4 bg-green-800 p-2 rounded-md">Approved</span>
            <span v-if="!bimonthly.isApproved && bimonthly.isSubmitted"
                class="ml-4 bg-orange-700 p-2 rounded-md">Pending</span>
        </div>
        <div v-for="(value, key) in bimonthly.days" :key="key">

            <TimeEntryRowHeading :date="key" :duration="sumDuration(value)" :checked="value.every((timeEntry: TimeEntry) =>
                selectedTimeEntries.includes(timeEntry)
            )
                " @select-all="selectAllTimeEntries(value)" @unselect-all="unselectAllTimeEntries(value)">
            </TimeEntryRowHeading>
            <template v-for="entry in value" :key="entry.id">
                <TimeEntryAggregateRow v-if="'timeEntries' in entry && entry.timeEntries.length > 1" :create-project
                    :can-create-project :enable-estimated-time :selected-time-entries="selectedTimeEntries"
                    :create-client :projects="projects" :tasks="tasks" :tags="tags" :clients
                    :on-start-stop-click="startTimeEntryFromExisting" :update-time-entries :update-time-entry
                    :delete-time-entries :create-tag :currency="currency" :time-entry="entry" @selected="
                        (timeEntries: TimeEntry[]) => {
                            selectedTimeEntries = [
                                ...selectedTimeEntries,
                                ...timeEntries,
                            ];
                        }
                    " @unselected="
                        (timeEntriesToUnselect: TimeEntry[]) => {
                            selectedTimeEntries = selectedTimeEntries.filter(
                                (item: TimeEntry) =>
                                    !timeEntriesToUnselect.find(
                                        (filterEntry: TimeEntry) =>
                                            filterEntry.id === item.id
                                    )
                            );
                        }
                    "></TimeEntryAggregateRow>
                <TimeEntryRow v-else :create-client :enable-estimated-time :can-create-project :create-project
                    :projects="projects" :selected="!!selectedTimeEntries.find(
                        (filterEntry: TimeEntry) => filterEntry.id === entry.id
                    )
                        " :tasks="tasks" :tags="tags" :clients :create-tag :update-time-entry
                    :on-start-stop-click="() => startTimeEntryFromExisting(entry)"
                    :delete-time-entry="() => deleteTimeEntries([entry])" :currency="currency"
                    :time-entry="entry.timeEntries[0]" @selected="selectedTimeEntries.push(entry)" @unselected="
                        selectedTimeEntries = selectedTimeEntries.filter(
                            (item: TimeEntry) => item.id !== entry.id
                        )
                        "></TimeEntryRow>
            </template>
        </div>
    </div>
</template>

<style scoped></style>
