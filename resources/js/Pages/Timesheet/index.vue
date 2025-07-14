<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TimeTracker from "@/Components/TimeTracker.vue";
import MainContainer from "@/packages/ui/src/MainContainer.vue";

import { ClockIcon } from "@heroicons/vue/24/outline";
import { usePage } from "@inertiajs/vue3";

import type { Tag } from '@/packages/api/src';
import { useQueryClient } from "@tanstack/vue-query";
import { computed, onMounted } from "vue";
import axios from "axios";
import { ref } from "vue";
import dayjs from "dayjs";
import ProjectBadge from "@/packages/ui/src/Project/ProjectBadge.vue";
import Modal from "@/packages/ui/src/Modal.vue";

import PageTitle from '@/Components/Common/PageTitle.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import TableRow from '@/Components/TableRow.vue';
import TableHeading from '@/Components/Common/TableHeading.vue';
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
  timesheets: Record<string, Bimontly[]>;
}>();
const isGroupedEmpty = computed(() =>
  Object.keys(
    page.props.timesheets as Record<string, Bimontly[]>
  ).length === 0
)

function highlightPeriod(periodKey: string): boolean {
  // periodKey format is "YYYY-MM-1" or "YYYY-MM-2"
  const [year, month, half] = periodKey.split('-');

  // start of month
  const monthStart = dayjs(`${year}-${month}-01`);

  // compute window start & end
  const windowStart = half === '1' ? monthStart : monthStart.date(16);
  const windowEnd =
    half === '1'
      ? monthStart.date(15).add(5, 'day') // 1‑15 + 5 days
      : monthStart.endOf('month').add(5, 'day'); // 16‑EOM + 5 days

  // today inside [windowStart, windowEnd] inclusive?
  return dayjs().isBetween(windowStart, windowEnd, 'day', '[]');
}
onMounted(console.log(page.props.timesheets));
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
          <template v-for="(userEntries, period) in page.props.timesheets" :key="period">
            <div class="p-3 font-bold" :class="highlightPeriod(period) ? 'bg-green-900 text-white' : 'bg-transparent'">
              {{ period }}
            </div>
            <a v-for="entry in userEntries" :key="entry.user.id" class="flex border p-3"
              :href="route('approval.ApprovalOverview', { user_id: entry.user.member.id })">

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
