<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
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
import ReadOnlyTimeEntryAggregateRow from '@/packages/ui/src/TimeEntry/ReadOnlyTimeEntryAggregateRow.vue';
import ReadOnlyTimeEntryRow from '@/packages/ui/src/TimeEntry/ReadOnlyTimeEntryRow.vue';
import TimeEntryRowHeading from '@/packages/ui/src/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/TimeEntryRow.vue';
import type { TimeEntriesGroupedByType } from '@/types/time-entries';
import axios from 'axios';
import { useNotificationsStore } from '@/utils/notification';
import { Checkbox } from '@/packages/ui/src';
import Submit from '@/Pages/Timesheet/Submit.vue';
import {
    type TimeEntriesQueryParams,
} from '@/packages/api/src';
import dayjs from 'dayjs';
const selectedTimeEntries = defineModel<TimeEntry[]>('selected', {
    default: [],
});
const props = defineProps<{
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    loadEntries: () => Promise<void>;

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
    fetchTimeEntries: () => void;
}>();

const { addNotification } = useNotificationsStore();
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
function copyTimeEntryFromExisting(entry: TimeEntry) {
    props.createTimeEntry({
        project_id: entry.project_id,
        task_id: entry.task_id,
        start: entry.start,
        end: entry.end,
        billable: entry.billable,
        description: entry.description,
        tags: [...entry.tags],
    });
}
type GroupedTimeEntries = Record<
    string, // biMonthKey
    {
        isApproved: boolean;
        isSubmitted: boolean;
        days: Record<string, TimeEntriesGroupedByType[]>; // daily breakdown
    }
>
function getBimonthlyKey(dateInput: string | Date): string {
    const date = new Date(dateInput);
    const year = date.getFullYear();

    const month = new Intl.DateTimeFormat('en-US', { month: 'short' }).format(date); // "Jan", "Feb", etc.

    const isFirstHalf = date.getDate() <= 15;
    const start = isFirstHalf ? '1' : '16';
    const end = isFirstHalf
        ? '15'
        : new Date(year, date.getMonth() + 1, 0).getDate().toString(); // Last day of the month

    return `${month} ${year} (${start}-${end})`;
}


const groupedTimeEntries = ref<GroupedTimeEntries>({});
function isSameBimonthlyPeriod(entryDate: string | Date, submission: TimeEntry): boolean {
    const entryKey = getBimonthlyKey(entryDate);
    const submissionKey = getBimonthlyKey(submission.start);
    return submissionKey === entryKey;
}


const arraysEqual = (a: string[], b: string[]) =>
    a.length === b.length && a.every((val, index) => val === b[index]);


function groupTimeEntriesFunc() {
    const grouped: GroupedTimeEntries = {}

    for (const entry of props.timeEntries) {
        if (entry.end === null) continue

        const date = getDayJsInstance()(entry.start);
        const year = date.year();
        const month = date.format('MMM'); // "Jan", "Feb", "Mar", etc.

        const dayKey = date.format('MM-DD-YYYY');
        const isFirstHalf = date.date() <= 15;
        const startLabel = isFirstHalf ? '1' : '16';
        const endLabel = isFirstHalf
            ? '15'
            : String(date.daysInMonth()) // Get last day of month

        const biMonthKey = `${month} ${startLabel} - ${month}  ${endLabel}, ${year}`

        if (!grouped[biMonthKey]) {
            grouped[biMonthKey] = {
                isApproved: entry.approval == "approved",
                isSubmitted: entry.approval == "submitted" || entry.approval == "approved",
                days: {}
            }
        }
        console.log(grouped);
        if (!grouped[biMonthKey].days[dayKey]) {
            grouped[biMonthKey].days[dayKey] = []
        }
        // Now find if this entry matches an existing grouped entry (by type/project/task)
        const existingGroup = grouped[biMonthKey].days[dayKey];
        const index = existingGroup.findIndex(
            (e) =>
                e.project_id === entry.project_id &&
                e.task_id === entry.task_id &&
                e.billable === entry.billable &&
                e.description === entry.description &&
                arraysEqual(e.tags, entry.tags)
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
    groupedTimeEntries.value = grouped;
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
    if (days == null)
        return null;
    const keys = Object.keys(days);
    return keys.length ? keys[0] : null;
}
function SubmitBTN(days: string, sheet: Record<string, TimeEntriesGroupedByType[]>) {
    previewSheets.value = sheet;
    isSubmit.value = true;
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


const unSubmitBTN = async (days: string, sheet: Record<string, TimeEntriesGroupedByType[]>) => {
    // previewSheets.value = sheet;
    // selectedPeriod.value = days;
    // isSubmit.value = false;

    const IDlist = pluckID(sheet);
    const success = await axios.post(
        route('approval.unsubmit'),
        { ids: IDlist },
        {
            withCredentials: true,
            headers: { Accept: 'application/json' },
        }
    );

    // Only emit if request succeeded
    if (success) {
        addNotification('success', 'Unsubmitted', 'Entry Unsubmitted');
        props.fetchTimeEntries();
    } else {

        addNotification('error', 'Failed', 'Entry Failed to Submit');
    }
}
function periodLabel(key: string) {
    const [year, month, half] = key.split('-');
    const firstDay = half === '1' ? 1 : 16;
    const lastDay = half === '1'
        ? 15
        : new Date(Number(year), Number(month), 0).getDate(); // end of month
    const monthName = new Date(`${year}-${month}-01`).toLocaleString('default', {
        month: 'short',
    });

    return `${firstDay}–${lastDay} ${monthName} ${year}`;
}

function isSubmitted(entries: TimeEntry[]): boolean {
    return entries.every(entry => entry.approval !== 'unsubmitted');
}
function isApproved(entries: TimeEntry[]): boolean {
    return entries.every(entry => entry.approval === 'approved');
}
function clearClick() {

    previewSheets.value = null;
}
const previewSheets = ref<Record<string, TimeEntriesGroupedByType[]> | null>(null);
const isSubmit = ref<boolean>(false);
watch(
    () => props.timeEntries,  // 👈 Reactive dependency
    () => {
        groupTimeEntriesFunc(); // 👈 Your function\ 
    },
    { deep: true, immediate: true } // Optional: runs on load + deep compare
);

</script>

<template>

    <div v-for="(bimonthly, bimonthlykey) in groupedTimeEntries" :key="bimonthlykey" class="">
        <div class=" border border-1 border-tertiary mt-5 border-b-4">

            <div class="  border-1 p-1 ">

                <button @click="SubmitBTN(bimonthlykey, bimonthly.days)"
                    v-if="!bimonthly.isSubmitted && !bimonthly.isApproved"
                    class=" p-2 border-1 mx-2 button text-blue-400">
                    submit
                </button>
                <button @click="unSubmitBTN(bimonthlykey, bimonthly.days)" v-if="bimonthly.isSubmitted"
                    class=" p-2 border-1 mx-2 button text-blue-400">
                    unsubmit
                </button>

                {{ bimonthlykey }}
                <span v-if="bimonthly.isApproved" class="ml-4 bg-green-800 p-2 rounded-md">Approved</span>
                <span v-if="!bimonthly.isApproved && bimonthly.isSubmitted"
                    class="ml-4 bg-orange-700 p-2 rounded-md">Pending</span>
            </div>


            <template v-for="(value, key) in bimonthly.days" :key="key" class="mb-5">
                <div v-if="!isSubmitted(value)" class=" bg-gray-900   border-1 p-1">
                    <TimeEntryRowHeading :date="key" :duration="sumDuration(value)" :checked="value.every((timeEntry: TimeEntry) =>
                        selectedTimeEntries.includes(timeEntry)
                    )
                        " @select-all="selectAllTimeEntries(value)" @unselect-all="unselectAllTimeEntries(value)">
                    </TimeEntryRowHeading>
                    <template v-for="entry in value" :key="entry.id">
                        <TimeEntryAggregateRow v-if="'timeEntries' in entry && entry.timeEntries.length > 1"
                            :create-project :can-create-project :enable-estimated-time
                            :create-time-entry="createTimeEntry" :selected-time-entries="selectedTimeEntries"
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
                            :duplicate-time-entry="copyTimeEntryFromExisting" :projects="projects" :selected="!!selectedTimeEntries.find(
                                (filterEntry: TimeEntry) => filterEntry.id === entry.id
                            )
                                " :tasks="tasks" :tags="tags" :clients :create-tag :update-time-entry :loadEntries
                            :on-start-stop-click="() => startTimeEntryFromExisting(entry)"
                            :delete-time-entry="() => deleteTimeEntries([entry])" :currency="currency"
                            :time-entry="entry.timeEntries[0]" @selected="selectedTimeEntries.push(entry)" @unselected="
                                selectedTimeEntries = selectedTimeEntries.filter(
                                    (item: TimeEntry) => item.id !== entry.id
                                )
                                "></TimeEntryRow>

                    </template>
                </div>
                <div v-if="isSubmitted(value)">


                    <TimeEntryRowHeading class="opacity-70" :date="key" :duration="sumDuration(value)" :checked="value.every((timeEntry: TimeEntry) =>
                        selectedTimeEntries.includes(timeEntry)
                    )
                        " @select-all="selectAllTimeEntries(value)" @unselect-all="unselectAllTimeEntries(value)">
                    </TimeEntryRowHeading>


                    <template v-for="entry in value" :key="entry.id">
                        <ReadOnlyTimeEntryAggregateRow v-if="'timeEntries' in entry && entry.timeEntries.length > 1"
                            :create-project :can-create-project :enable-estimated-time
                            :selected-time-entries="selectedTimeEntries" :create-client :projects="projects"
                            :tasks="tasks" :tags="tags" :clients :on-start-stop-click="startTimeEntryFromExisting"
                            :update-time-entries :update-time-entry :delete-time-entries :create-tag
                            :currency="currency" :time-entry="entry" @selected="
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
                            "></ReadOnlyTimeEntryAggregateRow>
                        <ReadOnlyTimeEntryRow v-else :create-client :enable-estimated-time :can-create-project
                            :create-project :projects="projects" :selected="!!selectedTimeEntries.find(
                                (filterEntry: TimeEntry) => filterEntry.id === entry.id
                            )
                                " :tasks="tasks" :tags="tags" :clients :create-tag :update-time-entry :loadEntries
                            :on-start-stop-click="() => startTimeEntryFromExisting(entry)"
                            :delete-time-entry="() => deleteTimeEntries([entry])" :currency="currency"
                            :time-entry="entry.timeEntries[0]" @selected="selectedTimeEntries.push(entry)" @unselected="
                                selectedTimeEntries = selectedTimeEntries.filter(
                                    (item: TimeEntry) => item.id !== entry.id
                                )
                                "></ReadOnlyTimeEntryRow>
                    </template>

                </div>

            </template>
        </div>
    </div>
    <Submit v-if="previewSheets" :groupEntries="previewSheets" @clear="clearClick" :isSubmit="isSubmit"
        :projects="projects" :tags="tags" :tasks="tasks" @getTimesheet="fetchTimeEntries">
    </Submit>
</template>

<style scoped></style>
