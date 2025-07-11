<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";
import TimeTracker from "@/Components/TimeTracker.vue";
import MainContainer from "@/packages/ui/src/MainContainer.vue";

import { ClockIcon } from "@heroicons/vue/24/outline";
import { usePage } from "@inertiajs/vue3";

import type { Tag } from '@/packages/api/src';
import { useQueryClient } from "@tanstack/vue-query";
import { computed } from "vue";
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
const queryClient = useQueryClient();
const refreshDashboardData = () => {
  // Invalidate all dashboard queries to trigger refetching
  queryClient.invalidateQueries({ queryKey: ["latestTasks"] });
  queryClient.invalidateQueries({ queryKey: ["lastSevenDays"] });
  queryClient.invalidateQueries({ queryKey: ["dailyTrackedHours"] });
  queryClient.invalidateQueries({ queryKey: ["latestTeamActivity"] });
  queryClient.invalidateQueries({ queryKey: ["weeklyProjectOverview"] });
  queryClient.invalidateQueries({ queryKey: ["totalWeeklyTime"] });
  queryClient.invalidateQueries({ queryKey: ["totalWeeklyBillableTime"] });
  queryClient.invalidateQueries({ queryKey: ["totalWeeklyBillableAmount"] });
  queryClient.invalidateQueries({ queryKey: ["weeklyHistory"] });
  queryClient.invalidateQueries({ queryKey: ["timeEntries"] });
};

const activeTab = ref<'active' | 'archived'>('active');
const page = usePage<{ 
  groups: any[];
}>();
 
</script>

<template>
  <AppLayout title="Dashboard" data-testid="dashboard_view">
    <MainContainer class="py-5 border-b border-default-background-separator flex justify-between items-center">
      <div class="flex items-center space-x-3 sm:space-x-6">
        <PageTitle :icon="UserCircleIcon" title="Clients"> </PageTitle>
        <TabBar v-model="activeTab">
          <TabBarItem value="active">Pending</TabBarItem>
          <TabBarItem value="archived">
            Approved
          </TabBarItem>
        </TabBar>
      </div>
    </MainContainer>
    <template>
      <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
          <div data-testid="client_table" class="grid min-w-full" style="grid-template-columns: 1fr 150px 200px 80px">
            <ClientTableHeading></ClientTableHeading>
            <div v-if="page.groups.length === 0" class="col-span-3 py-24 text-center">
              <UserCircleIcon class="w-8 text-icon-default inline pb-2"></UserCircleIcon>
              <h3 class="text-text-primary font-semibold">
                No clients found
              </h3>  
            </div>
            <template v-for="group in page.groups" :key="client.id">
              <ClientTableRow :client="group"></ClientTableRow>
            </template>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>