<script setup lang="ts">
import { getOrganizationCurrencyString } from '@/utils/money';
import {
    formatHumanReadableDuration,
    getDayJsInstance,
    getLocalizedDayJs,
    getLocalizedDateFromTimestamp,
} from '@/packages/ui/src/utils/time';
import { formatCents } from '@/packages/ui/src/utils/money';
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import ReportingChart from '@/Components/Common/Reporting/ReportingChart.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TimeReportRowHeading from '@/Pages/Timesheet/TimeReportRowHeading.vue'; 
import ReportingPieChart from '@/Components/Common/Reporting/ReportingPieChart.vue';

import { Link } from '@inertiajs/vue3';
import type {
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
} from '@/packages/api/src';
import type { TimeEntriesGroupedByType } from '@/types/time-entries'; 
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, type ComputedRef, inject, onMounted, ref, watch, watchEffect } from 'vue';
import { useReportingStore, type GroupingOption } from '@/utils/useReporting';
import { storeToRefs } from 'pinia';
import {
    type AggregatedTimeEntriesQueryParams, 
    type Organization,
} from '@/packages/api/src';
import {
    getCurrentMembershipId,
    getCurrentRole,
} from '@/utils/useUser';
import { useTagsStore } from '@/utils/useTags';
import { useSessionStorage, useStorage } from '@vueuse/core';
import { useNotificationsStore } from '@/utils/notification';
import { getRandomColorWithSeed } from '@/packages/ui/src/utils/color';
import { useProjectsStore } from '@/utils/useProjects';
import { router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';

import { ArrowLeftIcon } from '@heroicons/vue/24/solid';
import ReadOnlyTimeEntryRow from './ReadOnlyTimeEntryRow.vue';
import { SecondaryButton } from '@/packages/ui/src';
import axios from 'axios';
import ReadOnlyTimeEntryAggregateRow from './ReadOnlyTimeEntryAggregateRow.vue'; 

const page = usePage<{
    userid: string;
    period: { start: string; end: string };
    name: string;
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
}>();
const isLoading=ref<boolean>(false);
const approvalStatus = computed(() => {
    const entries = page.props.timeEntries;

    if (entries.length === 0) return 'none';

    const first = entries[0].approval;
    const allSame = entries.every(entry => entry.approval === first);

    return allSame ? first : 'mixed';
});
const startDate = useSessionStorage<string>('reporting-start-date', '');
const endDate = useSessionStorage<string>('reporting-end-date', '');
const selectedTags = ref<string[]>([]);
const selectedProjects = ref<string[]>([]);
const selectedMembers = ref<string[]>([]);
const selectedTasks = ref<string[]>([]);
const selectedClients = ref<string[]>([]);

const billable = ref<'true' | 'false' | null>(null);
watchEffect(() => {
    startDate.value = page.props.period.start;
    endDate.value = page.props.period.end;
    selectedMembers.value = [page.props.userid];
});
const group = useStorage<GroupingOption>('reporting-group', 'project');
const subGroup = useStorage<GroupingOption>('reporting-sub-group', 'task');
watch(
    () => selectedMembers,               // source
    (newVal,  ) => {                // callback
        console.log('selectedMembers changed:', newVal);
    },
    { deep: true }                       // optional: use if it's an array or object
);
const reportingStore = useReportingStore();
const { aggregatedGraphTimeEntries, aggregatedTableTimeEntries } =
    storeToRefs(reportingStore);

const { groupByOptions } = reportingStore;
const organization = inject<ComputedRef<Organization>>('organization');

function getFilterAttributes(): AggregatedTimeEntriesQueryParams {
    let params: AggregatedTimeEntriesQueryParams = {
        start: getLocalizedDayJs(startDate.value).startOf('day').utc().format(),
        end: getLocalizedDayJs(endDate.value).endOf('day').utc().format(),
    };
    params = {
        ...params,
        member_ids:
            selectedMembers.value.length > 0
                ? selectedMembers.value
                : undefined,
        project_ids:
            selectedProjects.value.length > 0
                ? selectedProjects.value
                : undefined,
        task_ids:
            selectedTasks.value.length > 0 ? selectedTasks.value : undefined,
        client_ids:
            selectedClients.value.length > 0
                ? selectedClients.value
                : undefined,
        tag_ids: selectedTags.value.length > 0 ? selectedTags.value : undefined,
        billable: billable.value !== null ? billable.value : undefined,
        member_id:
            getCurrentRole() === 'employee'
                ? getCurrentMembershipId()
                : undefined,
    };
    return params;
}

function updateGraphReporting() {
    const params = getFilterAttributes();
    if (getCurrentRole() === 'employee') {
        params.member_id = getCurrentMembershipId();
    }
    params.fill_gaps_in_time_groups = 'true';
    params.group = getOptimalGroupingOption(startDate.value, endDate.value);
    useReportingStore().fetchGraphReporting(params);
}

function updateTableReporting() {
    const params = getFilterAttributes();
    if (group.value === subGroup.value) {
        const fallbackOption = groupByOptions.find(
            (el) => el.value !== group.value
        );
        if (fallbackOption?.value) {
            subGroup.value = fallbackOption.value;
        }
    }
    if (getCurrentRole() === 'employee') {
        params.member_id = getCurrentMembershipId();
    }
    params.group = group.value;
    params.sub_group = subGroup.value;
    useReportingStore().fetchTableReporting(params);
}
 
function getOptimalGroupingOption(
    startDate: string,
    endDate: string
): 'day' | 'week' | 'month' {
    const diffInDays = getDayJsInstance()(endDate).diff(
        getDayJsInstance()(startDate),
        'd'
    );

    if (diffInDays <= 31) {
        return 'day';
    } else if (diffInDays <= 200) {
        return 'week';
    } else {
        return 'month';
    }
}

onMounted(() => {
    updateGraphReporting();
    updateTableReporting();
});

const { tags } = storeToRefs(useTagsStore());

async function createTag(tag: string) {
    return await useTagsStore().createTag(tag);
}
 
const { addNotification } = useNotificationsStore();
async function approveReject(type: 'approve' | 'reject') {
    isLoading.value=true;
    try {
        const ids = page.props.timeEntries.map(t => t.id);
  await axios.post(
            route(`approval.${type}`),          // approval.approve | approval.reject
            { timeEntries: ids,period:
                    `${ formatDate(page.props.period.start, 'ddd, MMMM D') } -
                    ${ formatDate(page.props.period.end, 'D - YYYY') }` },
            { withCredentials: true, headers: { Accept: 'application/json' } }
        );

        addNotification(
            'success',
            `${type === 'approve' ? 'Approved' : 'Rejected'}`,
        );
    isLoading.value=false;
        setTimeout(() => {
            router.visit(route('approval.index')); // change to your target page
        }, 1500);
    } catch (error) {
        console.error(error);
        addNotification(
            'error',
            'Failed',
            `Could not ${type} entries`
        );
    isLoading.value=false;
    }
}

const { getNameForReportingRowEntry, emptyPlaceholder } = useReportingStore();

const projectsStore = useProjectsStore();
const { projects } = storeToRefs(projectsStore); 

const groupedPieChartData = computed(() => {
    return (
        aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
            const name = getNameForReportingRowEntry(
                entry.key,
                aggregatedTableTimeEntries.value?.grouped_type
            );
            let color = getRandomColorWithSeed(entry.key ?? 'none');
            if (
                name &&
                aggregatedTableTimeEntries.value?.grouped_type &&
                emptyPlaceholder[
                aggregatedTableTimeEntries.value?.grouped_type
                ] === name
            ) {
                color = '#CCCCCC';
            } else if (
                aggregatedTableTimeEntries.value?.grouped_type === 'project'
            ) {
                color =
                    projects.value?.find((project) => project.id === entry.key)
                        ?.color ?? '#CCCCCC';
            }
            return {
                value: entry.seconds,
                name:
                    getNameForReportingRowEntry(
                        entry.key,
                        aggregatedTableTimeEntries.value?.grouped_type
                    ) ?? '',
                color: color,
            };
        }) ?? []
    );
});

const tableData = computed(() => {
    return aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
        return {
            seconds: entry.seconds,
            cost: entry.cost,
            description: getNameForReportingRowEntry(
                entry.key,
                aggregatedTableTimeEntries.value?.grouped_type
            ),
            grouped_data:
                entry.grouped_data?.map((el) => {
                    return {
                        seconds: el.seconds,
                        cost: el.cost,
                        description: getNameForReportingRowEntry(
                            el.key,
                            entry.grouped_type
                        ),
                    };
                }) ?? [],
        };
    });
});

function formatDate(dateString: string, format?: string) {
    if (!format) {
        format = 'MMMM D';
    }
    const formatted = dayjs(dateString).format(format);
    return formatted;
} 

const groupedTimeEntries = computed(() => {
    const groupedEntriesByDay: Record<string, TimeEntry[]> = {};
    for (const entry of page.props.timeEntries) {
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

function sumDuration(timeEntries: TimeEntry[]) {
    return timeEntries.reduce((acc, entry) => acc + (entry?.duration ?? 0), 0);
}


async function UnsubmittedRemind(type: 'withdraw' | 'remind') {
    isLoading.value=true;
    try {
        const ids = page.props.timeEntries.map(t => t.id);

          await axios.post(
            route(`approval.${type}`),          // approval.approve | approval.reject
            { timeEntries: ids,period:
                    `${ formatDate(page.props.period.start, 'ddd, MMMM D') } -
                    ${ formatDate(page.props.period.end, 'D - YYYY') }` },
            { withCredentials: true, headers: { Accept: 'application/json' } }
        );

        addNotification(
            'success',
            `${type === 'withdraw' ? 'Withdraw Entries' : 'Sent Reminder Successfuly'}`,
        );
        setTimeout(() => {
            router.visit(route('approval.index')); // change to your target page
        }, 1500);
    isLoading.value=false;
    } catch (error) {
        console.error(error);
        addNotification(
            'error',
            'Failed',
            `Could not ${type} entries`
        );
    isLoading.value=false;
    }
}
</script>

<template>
    <AppLayout title="Reporting" data-testid="reporting_view" class="overflow-hidden">
        <div class="flex">

        </div>
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex space-x-2">
            <Link :href="route('approval.index')" title="back">
            <ArrowLeftIcon class="w-5"></ArrowLeftIcon>
            </Link> 
                <div class="px-2 font-bold text-lg">
                    {{ page.props.name }}</div>
                <div class="font-bold text-lg">
                    -
                </div>
                <div class="pl-2 font-semibold">(
                    {{ formatDate(page.props.period.start, 'ddd, MMMM D') }} -
                    {{ formatDate(page.props.period.end, 'D - YYYY') }})</div>
            </div>
        </MainContainer>
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="relative flex space-x-2 w-full">
                <div class="px-2  text-sm">
                    Submitted by: {{ page.props.name }}</div>
                <div class="pl-2 font-semibold">(
                    {{ formatDate(page.props.period.end, 'ddd, MMMM D - YYYY') }})
                </div>
                <div class="absolute right-5 pl-2 font-semibold" v-if="approvalStatus == 'submitted'">
                    <SecondaryButton class="border-0 px-2 bg-blue-600 mx-2 text-quaternary"
                        :loading="isLoading"
                        @click="approveReject('approve')">APPROVE
                    </SecondaryButton>
                    <SecondaryButton class="border-0 px-2 bg-red-600 mx-2 text-quaternary"
                        :loading="isLoading"
                        @click="approveReject('reject')">REJECT</SecondaryButton>
                </div>
                <div class="absolute right-5 pl-2 font-semibold" v-if="approvalStatus == 'unsubmitted'">
                    <SecondaryButton class="border-0 px-2 bg-blue-600 mx-2 text-quaternary"
                        :loading="isLoading"
                        @click="UnsubmittedRemind('remind')">REMIND TO SUBMIT
                    </SecondaryButton>
                </div>
                <div class="absolute right-5 pl-2 font-semibold" v-if="approvalStatus != 'submitted' && approvalStatus != 'unsubmitted'">
                    <SecondaryButton class="border-0 px-2 bg-blue-600 mx-2 text-quaternary"
                        :loading="isLoading"
                        @click="UnsubmittedRemind('withdraw')">WITHDRAW
                    </SecondaryButton>
                </div>

            </div>
        </MainContainer>
        <MainContainer>
            <div class="pt-10 w-full px-3 relative">
                <ReportingChart :grouped-type="aggregatedGraphTimeEntries?.grouped_type" :grouped-data="aggregatedGraphTimeEntries?.grouped_data
                    "></ReportingChart>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="sm:grid grid-cols-4 pt-6 items-start">
                <div class="col-span-3 bg-card-background rounded-lg border border-card-border pt-3">

                    <div class="grid items-center" style="grid-template-columns: 1fr 100px 150px">
                        <div
                            class="contents [&>*]:border-card-background-separator [&>*]:border-b [&>*]:bg-tertiary [&>*]:pb-1.5 [&>*]:pt-1 text-text-secondary text-sm">
                            <div class="pl-6">Name</div>
                            <div class="text-right">Duration</div>
                            <div class="text-right pr-6">Cost</div>
                        </div>
                        <template v-if="
                            aggregatedTableTimeEntries?.grouped_data &&
                            aggregatedTableTimeEntries.grouped_data?.length > 0
                        ">
                            <ReportingRow v-for="entry in tableData" :key="entry.description ?? 'none'"
                                :currency="getOrganizationCurrencyString()"
                                :type="aggregatedTableTimeEntries.grouped_type" :entry="entry"></ReportingRow>
                            <div class="contents [&>*]:transition text-text-tertiary [&>*]:h-[50px]">
                                <div class="flex items-center pl-6 font-medium">
                                    <span>Total</span>
                                </div>
                                <div class="justify-end flex items-center font-medium">
                                    {{
                                        formatHumanReadableDuration(
                                            aggregatedTableTimeEntries.seconds,
                                            organization?.interval_format,
                                            organization?.number_format
                                        )
                                    }}
                                </div>
                                <div class="justify-end pr-6 flex items-center font-medium">
                                    {{
                                        aggregatedTableTimeEntries.cost
                                            ? formatCents(
                                                aggregatedTableTimeEntries.cost,
                                                getOrganizationCurrencyString(),
                                                organization?.currency_format,
                                                organization?.currency_symbol,
                                                organization?.number_format
                                            )
                                            : '--'
                                    }}
                                </div>
                            </div>
                        </template>
                        <div v-else class="chart flex flex-col items-center justify-center py-12 col-span-3">
                            <p class="text-lg text-text-primary font-semibold">
                                No time entries found
                            </p>
                            <p>Try to change the filters and time range</p>
                        </div>
                    </div>
                </div>
                <div class="px-2 lg:px-4">
                    <ReportingPieChart :data="groupedPieChartData"></ReportingPieChart>
                </div>
            </div>
        </MainContainer>
        <MainContainer class=" ">

            <div v-for="(value, key) in groupedTimeEntries" :key="key">
                <div class=" border border-1 mt-5 border-tertiary border-b-4">



                    <TimeReportRowHeading class=" " :date="key" :duration="sumDuration(value)">
                    </TimeReportRowHeading>


                    <template v-for="entry in value" :key="entry.id">
                        <ReadOnlyTimeEntryAggregateRow v-if="'timeEntries' in entry && entry.timeEntries.length > 1"
                            :projects="projects" :tasks="page.props.tasks" :tags="tags" :clients="page.props.clients"
                            :create-tag :time-entry="entry"></ReadOnlyTimeEntryAggregateRow>
                        <ReadOnlyTimeEntryRow v-else :projects="projects" :tasks="page.props.tasks" :tags="tags"
                            :clients="page.props.clients" :time-entry="entry.timeEntries[0]">
                        </ReadOnlyTimeEntryRow>
                    </template>

                </div>
            </div>
        </MainContainer>
    </AppLayout>
</template>

<style scoped></style>
