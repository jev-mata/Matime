import type { Manager, Project, Team, User } from '@/Pages/Members.vue'
import axios from 'axios'
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'

export const useAppStore = defineStore('app', () => {
  // main lists
  const users = ref<User[]>([])
  const projects = ref<Project[]>([])
  const teams = ref<Team[]>([])
  const managers = ref<User[]>([])

  // selections
  const selectedManagers = reactive<Record<string, User[]>>({})
  const selectedProject = reactive<Record<string, Project[]>>({})
  const selectedUsers = reactive<Record<string, User[]>>({})
  const selectedUserIds = reactive<Record<string, string[]>>({})
  const selectedProjectIds = reactive<Record<string, string[]>>({})

  const currentOrganization = ref<string>('')

  // loading & error states
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // async fetch
  async function fetchCurrentOrg() {
    isLoading.value = true
    error.value = null

    try {
      const res = await axios.post(`/get/current/org`)

      currentOrganization.value = res.data.org
      projects.value = res.data.projects
      users.value = res.data.user
      teams.value = res.data.teams
      managers.value = []

      // build selections
      teams.value.forEach((team) => {
        selectedUsers[team.id] = [...team.users]
        selectedUserIds[team.id] = team.users.map((u) => u.id)

        selectedProjectIds[team.id] = team.projects.map((p) => p.id)
        selectedProject[team.id] = [...team.projects]
      })

      res.data.managers.forEach((manager: Manager) => {
        const admins = manager.users.filter(
          (user) => user.organizations[0].membership.role === 'admin'
        )
        const managersOnly = manager.users.filter(
          (user) => user.organizations[0].membership.role === 'manager'
        )

        if (admins.length > 0) {
          selectedManagers[manager.id] = admins
          managers.value.push(...admins)
        } else if (managersOnly.length > 0) {
          selectedManagers[manager.id] = managersOnly
          managers.value.push(...managersOnly)
        }
      })
    } catch (err: any) {
      error.value = err.message ?? 'Failed to fetch organization'
      console.error('❌ fetchCurrentOrg error:', err)
    } finally {
      isLoading.value = false
    }
  }

  // auto-fetch once when store is first used
  fetchCurrentOrg()

  return {
    users,
    projects,
    teams,
    managers,
    selectedManagers,
    selectedProject,
    selectedUsers,
    selectedUserIds,
    selectedProjectIds,
    currentOrganization,
    isLoading,
    error,
    fetchCurrentOrg,
  }
})
