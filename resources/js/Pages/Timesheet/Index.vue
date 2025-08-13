<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';

import type { Tag, TimeEntry } from '@/packages/api/src';
import { computed, onMounted } from 'vue';
import { ref } from 'vue';
import dayjs from 'dayjs';

import { router, usePage } from '@inertiajs/vue3';
import { useNotificationsStore } from '@/utils/notification'; 
import PageTitle from '@/Components/Common/PageTitle.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import { HandThumbUpIcon } from '@heroicons/vue/20/solid';
import isBetween from 'dayjs/plugin/isBetween';
import axios from 'axios';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimesheetTable from './Components/TimesheetTable.vue';
dayjs.extend(isBetween);
const activeTab = ref<'pending' | 'unsubmitted' | 'archive'>('pending');
type Bimontly = {
    user: {
        id: string;
        name: string;
        groups: any[];
        member: { id: string };
    };
    totalHours: string; // calculated from TimeEntry durations
};
const page = usePage<{
    grouped: Record<string, Bimontly[]>;
    timesheets: TimeEntry[];

    unsubmitted_grouped: Record<string, Bimontly[]>;
    unsubmitted_timesheets: TimeEntry[];

    archive_grouped: Record<string, Bimontly[]>;
    archive_timesheets: TimeEntry[];
}>();

const isLoading=ref<boolean>(false);
const isGroupedEmpty = computed(
    () =>
        Object.keys(page.props.grouped as Record<string, Bimontly[]>).length ===
        0
);
const { addNotification } = useNotificationsStore();

const isUnsubmittedGroupedEmpty = computed(
    () =>
        Object.keys(
            page.props.unsubmitted_grouped as Record<string, Bimontly[]>
        ).length === 0
);
const isArchiveGroupedEmpty = computed(
    () =>
        Object.keys(page.props.archive_grouped as Record<string, Bimontly[]>)
            .length === 0
);

async function approveRejectAll(type: 'approve' | 'reject') {
    isLoading.value=true;
    try {
        const ids = page.props.timesheets.map((t) => t.id);
        await axios.post(
            route(`approval.${type}`), // approval.approve | approval.reject
            { timeEntries: ids },
            { withCredentials: true, headers: { Accept: 'application/json' } }
        );

        addNotification(
            'success',
            `${type === 'approve' ? 'Approved' : 'Rejected'}`
        );
    isLoading.value=false;
        setTimeout(() => {
            router.visit(route('approval.index')); // change to your target page
        }, 1500);
    } catch (error) {
        console.error(error);
    isLoading.value=false;
        addNotification('error', 'Failed', `Could not ${type} entries`);
    }
}
function getPeriodInfo(periodKey: string): {
    isHighlighted: boolean;
    endDate: string; // formatted as YYYY-MM-DD
    endDate2: string; // formatted as YYYY-MM-DD
} {
    const [year, month, half] = periodKey.split('-');
    const monthStart = dayjs(`${year}-${month}-01`);

    let windowStart, windowEnd;

    if (half === '1') {
        windowStart = monthStart;
        windowEnd = monthStart.date(15); // 1–15 + 5 days
    } else {
        windowStart = monthStart.date(16);
        windowEnd = monthStart.endOf('month'); // 16–EOM + 5 days
    }

    const isHighlighted = dayjs().isBetween(
        windowStart,
        windowEnd,
        'day',
        '[]'
    );
    return {
        isHighlighted,
        endDate: windowEnd.format('D, YYYY'),
        endDate2: windowEnd.format('YYYY-MM-DD'),
    };
}

async function UnsubmittedRemindAll(type: 'withdraw' | 'remind') {
    isLoading.value=true;
    try {
      
        const ids = type=='withdraw'?page.props.archive_timesheets.map((t) => t.id):page.props.unsubmitted_timesheets.map((t) => t.id);

        await axios.post(
            route(`approval.${type}`), // approval.approve | approval.reject
            { timeEntries: ids },
            { withCredentials: true, headers: { Accept: 'application/json' } }
        );

        addNotification(
            'success',
            `${type === 'withdraw' ? 'Withdraw Entries' : 'Sent Reminder Successfuly'}`
        );
    isLoading.value=false;
        setTimeout(() => {
            router.visit(route('approval.index')); // change to your target page
        }, 1500);
    } catch (error) {
        console.error(error);
    isLoading.value=false;
        addNotification('error', 'Failed', `Could not ${type} entries`);
    }
}
function formatDate(dateString: string, format?: string) {
    if (!format) {
        format = 'MMMM D';
    }
    const formatted = dayjs(dateString).format(format);
    return formatted;
}
</script>
<template>
    <AppLayout title="Dashboard" data-testid="dashboard_view">
        <MainContainer
            class="py-5 border-b dark:bg-[#171E31] dark:border-[#303F61] flex justify-between items-center w-full">
            <div class="flex items-center space-x-1 sm:space-x-3 w-full">
                <PageTitle :icon="HandThumbUpIcon" title="Approval" />
                <TabBar v-model="activeTab">
                    <TabBarItem value="pending">Pending</TabBarItem>
                    <TabBarItem value="unsubmitted">Unsubmitted</TabBarItem>
                    <TabBarItem value="archive">Archive</TabBarItem>
                </TabBar>
                <div class="w-full flex items-end justify-end  dark:text-[#7D88A1] ">
                    <PrimaryButton
                        v-if="activeTab == 'archive'"
                        :loading="isLoading"
                        class="border-0 px-2  mx-2 "
                        @click="UnsubmittedRemindAll('withdraw')">
                        WITHDRAW ALL
                    </PrimaryButton>
                    <PrimaryButton
                        v-if="activeTab == 'unsubmitted'"
                        :loading="isLoading"
                        class="border-0 px-2  mx-2 "
                        @click="UnsubmittedRemindAll('remind')">
                        REMIND ALL
                    </PrimaryButton>
                    <PrimaryButton
                        v-if="activeTab == 'pending'"
                        :loading="isLoading"
                        class="border-0 px-2  mx-2 "
                        @click="approveRejectAll('approve')">
                        APPROVE ALL
                    </PrimaryButton>
                    <DangerButton
                        v-if="activeTab == 'pending'"
                        :loading="isLoading"
                        class="border-1 px-2  mx-2  "
                        @click="approveRejectAll('reject')">
                        REJECT ALL
                    </DangerButton>
                </div>
            </div>
        </MainContainer>

        <div class="flow-root max-w-[100vw] overflow-x-auto">
            <div class="inline-block w-full align-middle">
                <div
                    data-testid="client_table"
                    class="grid w-full"
                    v-if="activeTab == 'pending'">
                    <div
                        v-if="isGroupedEmpty"
                        class="col-span-3 py-24 text-center">
                        <UserCircleIcon
                            class="w-8 text-icon-default inline pb-2" />
                        <h3 class="text-text-primary font-semibold">
                            No {{ activeTab }} timesheets found
                        </h3>
                    </div>
                    <template
                        v-for="(userEntries, period) in page.props.grouped"
                        :key="period">
                        <TimesheetTable
                            :get-period-info="getPeriodInfo"
                            :format-date="formatDate"
                            :period="period"
                            :user-entries="userEntries" />
                    </template>
                </div>
                <div
                    data-testid="client_table"
                    class="grid w-full"
                    v-if="activeTab == 'unsubmitted'">
                    <div
                        v-if="isUnsubmittedGroupedEmpty"
                        class="col-span-3 py-24 text-center">
                        <UserCircleIcon
                            class="w-8 text-icon-default inline pb-2" />
                        <h3 class="text-text-primary font-semibold">
                            No {{ activeTab }} timesheets found
                        </h3>
                    </div>
                    <template
                        v-for="(userEntries, period) in page.props
                            .unsubmitted_grouped"
                        :key="period">
                        
                        <TimesheetTable
                            :get-period-info="getPeriodInfo"
                            :format-date="formatDate"
                            :period="period"
                            :user-entries="userEntries" />
                    </template>
                </div>
                <div
                    data-testid="client_table"
                    class="grid w-full"
                    v-if="activeTab == 'archive'">
                    <div
                        v-if="isArchiveGroupedEmpty"
                        class="col-span-3 py-24 text-center">
                        <UserCircleIcon
                            class="w-8 text-icon-default inline pb-2" />
                        <h3 class="text-text-primary font-semibold">
                            No {{ activeTab }} timesheets found
                        </h3>
                    </div>
                    <template
                        v-for="(userEntries, period) in page.props
                            .archive_grouped"
                        :key="period">
                        <TimesheetTable
                            :get-period-info="getPeriodInfo"
                            :format-date="formatDate"
                            :period="period"
                            :user-entries="userEntries" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
