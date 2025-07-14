<script setup lang="ts">
import { ref, computed } from 'vue';
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

const groupedTimeEntries = computed(() => {
    const groupedEntriesByDay: Record<string, TimeEntry[]> = {};
    for (const entry of props.timeEntries) {
        // skip current time entry
        if (entry.end === null) {
            continue;
        }
        const oldEntries =
            groupedEntriesByDay[getLocalizedDateFromTimestamp(entry.start)];
        groupedEntriesByDay[getLocalizedDateFromTimestamp(entry.start)] = [
            ...(oldEntries ?? []),
            entry,
        ];
    }
    const groupedEntriesByDayAndType: Record<
        string,
        TimeEntriesGroupedByType[]
    > = {};
    for (const dailyEntriesKey in groupedEntriesByDay) {
        const dailyEntries = groupedEntriesByDay[dailyEntriesKey];
        const newDailyEntries: TimeEntriesGroupedByType[] = [];

        for (const entry of dailyEntries) {
            // check if same entry already exists
            const oldEntriesIndex = newDailyEntries.findIndex(
                (e) =>
                    e.project_id === entry.project_id &&
                    e.task_id === entry.task_id &&
                    e.billable === entry.billable &&
                    e.description === entry.description
            );
            if (oldEntriesIndex !== -1 && newDailyEntries[oldEntriesIndex]) {
                newDailyEntries[oldEntriesIndex].timeEntries.push(entry);

                // Add up durations for time entries of the same type
                newDailyEntries[oldEntriesIndex].duration =
                    (newDailyEntries[oldEntriesIndex].duration ?? 0) +
                    (entry?.duration ?? 0);

                // adapt start end times so they show the earliest start and latest end time
                if (
                    getDayJsInstance()(entry.start).isBefore(
                        getDayJsInstance()(
                            newDailyEntries[oldEntriesIndex].start
                        )
                    )
                ) {
                    newDailyEntries[oldEntriesIndex].start = entry.start;
                }
                if (
                    getDayJsInstance()(entry.end).isAfter(
                        getDayJsInstance()(newDailyEntries[oldEntriesIndex].end)
                    )
                ) {
                    newDailyEntries[oldEntriesIndex].end = entry.end;
                }
            } else {
                newDailyEntries.push({ ...entry, timeEntries: [entry] });
            }
        }

        groupedEntriesByDayAndType[dailyEntriesKey] = newDailyEntries;
    }
    return groupedEntriesByDayAndType;
});


const groupedBiMonthly = computed<
    Record<string, TimeEntriesGroupedByType[]>
>(() => {
    const result: Record<string, TimeEntriesGroupedByType[]> = {};

    // 1️⃣  Loop over the daily buckets we already have
    Object.values(groupedTimeEntries.value).forEach((dailyList) => {
        dailyList.forEach((entry) => {
            const d = new Date(entry.start);
            const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getDate() <= 15 ? '1' : '2'}`;

            const bucket = (result[key] ||= []);

            // 2️⃣  Merge “same‑type” entries inside this half‑month
            const i = bucket.findIndex(
                (e) =>
                    e.project_id === entry.project_id &&
                    e.task_id === entry.task_id &&
                    e.billable === entry.billable &&
                    e.description === entry.description
            );

            if (i !== -1) {
                // Already have an aggregate for this combo → extend it
                const agg = bucket[i];

                // merge the inner time‑entry arrays
                agg.timeEntries.push(...entry.timeEntries);

                // total duration
                agg.duration = (agg.duration ?? 0) + (entry.duration ?? 0);

                // earliest start / latest end
                if (new Date(entry.start) < new Date(agg.start)) agg.start = entry.start;
                if (
                    entry.end &&
                    (!agg.end || new Date(entry.end) > new Date(agg.end))
                ) {
                    agg.end = entry.end;
                }
            } else {
                // First time we see this combo in this half‑month
                bucket.push({ ...entry });
            }
        });
    });

    return result;
});
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
function SubmitBTN(days: string, sheet: TimeEntriesGroupedByType[]) {
    previewSheets.value = sheet;
    selectedPeriod.value = days;
    isSubmit.value = true;
}
function pluckID(
    timeEntryGroup: TimeEntriesGroupedByType[]
): string[] {
    return timeEntryGroup.flatMap(entryGroup =>
        entryGroup.timeEntries.map(timeEntry => timeEntry.id)
    );
}

const unSubmitBTN = async (days: string, sheet: TimeEntriesGroupedByType[]) => {
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
    selectedPeriod.value = "";
}
const previewSheets = ref<TimeEntriesGroupedByType[] | null>(null);
const isSubmit = ref<boolean>(false);
const selectedPeriod = ref<string>('');
</script>

<template>

    <div v-for="(entriesGroup, period) in groupedBiMonthly" :key="period" class="">
        <div class=" border border-1 border-tertiary mt-5 border-b-4">

            <div class=" bg-default-background   border-1 p-1 ">

                <button @click="SubmitBTN(period, entriesGroup)" v-if="!isSubmitted(entriesGroup)"
                    class=" p-2 border-1 mx-2 button text-blue-500">
                    submit
                </button>
                <button @click="unSubmitBTN(period, entriesGroup)" v-if="isSubmitted(entriesGroup)"
                    class=" p-2 border-1 mx-2 button text-blue-500">
                    unsubmit
                </button>

                {{ periodLabel(period) }}
                <span v-if="isSubmitted(entriesGroup) && isApproved(entriesGroup)"
                    class="ml-4 bg-green-800 p-2 rounded-md">Approved</span>
                <span v-if="isSubmitted(entriesGroup)"
                    class="ml-4 bg-orange-700 p-2 rounded-md">Pending</span>
            </div>

            <template v-for="entryGroup in entriesGroup" :key="entryGroup.id">
                <div v-if="!isSubmitted(entriesGroup)">

                    <TimeEntryRowHeading :date="entryGroup.start" :duration="sumDuration(entryGroup.timeEntries)"
                        :checked="entriesGroup.every((timeEntry: TimeEntry) =>
                            selectedTimeEntries.includes(timeEntry)
                        )
                            " @select-all="selectAllTimeEntries(entriesGroup)"
                        @unselect-all="unselectAllTimeEntries(entriesGroup)">
                    </TimeEntryRowHeading>
                    <TimeEntryAggregateRow v-if="'timeEntries' in entryGroup && entryGroup.timeEntries.length > 1"
                        :create-project :can-create-project :enable-estimated-time
                        :selected-time-entries="selectedTimeEntries" :create-client :projects="projects" :tasks="tasks"
                        :tags="tags" :clients :on-start-stop-click="startTimeEntryFromExisting" :update-time-entries
                        :update-time-entry :delete-time-entries :create-tag :currency="currency"
                        :time-entry="entryGroup" @selected="
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
                            (filterEntry: TimeEntry) => filterEntry.id === entryGroup.id
                        )
                            " :tasks="tasks" :tags="tags" :clients :create-tag :update-time-entry :loadEntries
                        :on-start-stop-click="() => startTimeEntryFromExisting(entryGroup)"
                        :delete-time-entry="() => deleteTimeEntries([entryGroup])" :currency="currency"
                        :time-entry="entryGroup.timeEntries[0]" @selected="selectedTimeEntries.push(entryGroup)"
                        @unselected="
                            selectedTimeEntries = selectedTimeEntries.filter(
                                (item: TimeEntry) => item.id !== entryGroup.id
                            )
                            "></TimeEntryRow>
                </div>
                <div v-if="isSubmitted(entriesGroup)">


                    <TimeEntryRowHeading class="opacity-70" :date="entryGroup.start"
                        :duration="sumDuration(entryGroup.timeEntries)" :checked="entriesGroup.every((timeEntry: TimeEntry) =>
                            selectedTimeEntries.includes(timeEntry)
                        )
                            " @select-all="selectAllTimeEntries(entriesGroup)"
                        @unselect-all="unselectAllTimeEntries(entriesGroup)">
                    </TimeEntryRowHeading>


                    <ReadOnlyTimeEntryAggregateRow
                        v-if="'timeEntries' in entryGroup && entryGroup.timeEntries.length > 1" :create-project
                        :can-create-project :enable-estimated-time :selected-time-entries="selectedTimeEntries"
                        :create-client :projects="projects" :tasks="tasks" :tags="tags" :clients
                        :on-start-stop-click="startTimeEntryFromExisting" :update-time-entries :update-time-entry
                        :delete-time-entries :create-tag :currency="currency" :time-entry="entryGroup" @selected="
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
                            (filterEntry: TimeEntry) => filterEntry.id === entryGroup.id
                        )
                            " :tasks="tasks" :tags="tags" :clients :create-tag :update-time-entry :loadEntries
                        :on-start-stop-click="() => startTimeEntryFromExisting(entryGroup)"
                        :delete-time-entry="() => deleteTimeEntries([entryGroup])" :currency="currency"
                        :time-entry="entryGroup.timeEntries[0]" @selected="selectedTimeEntries.push(entryGroup)"
                        @unselected="
                            selectedTimeEntries = selectedTimeEntries.filter(
                                (item: TimeEntry) => item.id !== entryGroup.id
                            )
                            "></ReadOnlyTimeEntryRow>
                </div>
            </template>
        </div>
    </div>
    <Submit v-if="previewSheets" :groupEntries="previewSheets" :period="periodLabel(selectedPeriod)" @clear="clearClick"
        :isSubmit="isSubmit" :projects="projects" :tags="tags" :tasks="tasks" @getTimesheet="fetchTimeEntries">
    </Submit>
</template>

<style scoped></style>
