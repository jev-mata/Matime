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
import TableRow from '@/Components/TableRow.vue';
import TableHeading from '@/Components/Common/TableHeading.vue';
const activeTab = ref<'pending' | 'approved'>('pending');
interface Group {
  id: number | string;
  name: string;
  user: any[];
}
const page = usePage<{
  groups: Group[];
}>();

</script>

<template>
  <AppLayout title="Dashboard" data-testid="dashboard_view">
    <MainContainer class="py-5 border-b border-default-background-separator flex justify-between items-center">
      <div class="flex items-center space-x-3 sm:space-x-6">
        <PageTitle :icon="UserCircleIcon" title="Clients"> </PageTitle>
        <TabBar v-model="activeTab">
          <TabBarItem value="pending">Pending</TabBarItem>
          <TabBarItem value="approved">
            Approved
          </TabBarItem>
        </TabBar>
      </div>
    </MainContainer>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
      <div class="inline-block min-w-full align-middle">
        <div data-testid="client_table" class="grid min-w-full" style="grid-template-columns: 1fr 150px 200px 80px">

          <TableHeading>
            <div class="py-1.5 pr-3 text-left font-semibold text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
              Name
            </div>
            <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Total Hours</div>
            <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
            <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Edit</div>
          </TableHeading>
          <div v-if="page.props.groups.length === 0" class="col-span-3 py-24 text-center">
            <UserCircleIcon class="w-8 text-icon-default inline pb-2"></UserCircleIcon>
            <h3 class="text-text-primary font-semibold">
              No pening {{ activeTab }} found
            </h3>
          </div>
          <template v-for="group in page.props.groups" :key="group.id">

            <TableRow>
              <div class="py-1.5 pr-3 text-left font-semibold text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                {{ group.name }}
              </div>
              <div class="px-3 py-1.5 text-left font-semibold text-text-primary"></div>
              <div class="px-3 py-1.5 text-left font-semibold text-text-primary">{{ activeTab }} </div>
              <div class="px-3 py-1.5 text-left font-semibold text-text-primary">
                <Button>{{ activeTab == "approved" ? "unsubmit" : "View" }} </Button>
              </div>
            </TableRow>
            <!-- <ClientTableRow :client="group"></ClientTableRow> -->
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>