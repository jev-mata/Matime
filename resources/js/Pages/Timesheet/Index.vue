<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue"; 
import MainContainer from "@/packages/ui/src/MainContainer.vue";
 
import { usePage } from "@inertiajs/vue3";

import type { Tag, TimeEntry } from '@/packages/api/src'; 
import { computed, onMounted } from "vue"; 
import { ref } from "vue";
import dayjs from "dayjs"; 

import PageTitle from '@/Components/Common/PageTitle.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue'; 
import { UserCircleIcon } from '@heroicons/vue/20/solid'; 
import isBetween from 'dayjs/plugin/isBetween';
dayjs.extend(isBetween);
const activeTab = ref<'pending' | 'approved'>('pending');
type Bimontly = {
  user: {
    id: string;
    name: string;
    groups: any[];
    member: { id: string };
  };
  totalHours: string; // calculated from TimeEntry durations

}
const page = usePage<{
  grouped: Record<string, Bimontly[]>;
  timesheets: TimeEntry[];
}>();


const isGroupedEmpty = computed(() =>
  Object.keys(
    page.props.grouped as Record<string, Bimontly[]>
  ).length === 0
)

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

  const isHighlighted = dayjs().isBetween(windowStart, windowEnd, 'day', '[]');
  return {
    isHighlighted,
    endDate: windowEnd.format('D, YYYY'),
    endDate2: windowEnd.format('YYYY-MM-DD')
  };
}
function formatDate(dateString: string, format?: string) {
  if (!format) {
    format = 'MMMM D';
  }
  const formatted = dayjs(dateString).format(format);
  return formatted;
}
</script><template>
  <AppLayout title="Dashboard" data-testid="dashboard_view">
    <MainContainer class="py-5 border-b border-default-background-separator flex justify-between items-center">
      <div class="flex items-center space-x-3 sm:space-x-6">
        <PageTitle :icon="UserCircleIcon" title="Timesheet Approval" />
        <TabBar v-model="activeTab">
          <TabBarItem value="pending">Pending</TabBarItem>
          <TabBarItem value="approved">Approved</TabBarItem>
        </TabBar>
      </div>
    </MainContainer>

    <div class="flow-root max-w-[100vw] overflow-x-auto">
      <div class="inline-block w-full align-middle">
        <div data-testid="client_table" class="grid w-full">
          <div v-if="isGroupedEmpty" class="col-span-3 py-24 text-center">
            <UserCircleIcon class="w-8 text-icon-default inline pb-2" />
            <h3 class="text-text-primary font-semibold">
              No {{ activeTab }} timesheets found
            </h3>
          </div>
          <template v-for="(userEntries, period) in page.props.grouped" :key="period">
            <div class="p-3 font-bold"
              :class="getPeriodInfo(period).isHighlighted ? 'bg-green-900 text-white' : 'bg-transparent'">
              {{ formatDate(period) }} - {{ (getPeriodInfo(period).endDate) }}
            </div>
            <a v-for="entry in userEntries" :key="entry.user.id" class="flex border p-3"
              :href="route('approval.ApprovalOverview', { user_id: entry.user.member.id, date_start: period, date_end: getPeriodInfo(period).endDate2 })">

              <div class="flex-1">{{ entry.user.name }}</div>
              <div class="flex-1">
                {{ entry.user.groups?.[0]?.manager?.name ?? '—' }}
              </div>
              <div class="flex-1">{{ entry.totalHours }}</div>
            </a>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
