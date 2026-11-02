<template>
  <OrganizationLayout :title="$t('main_page')">
    <div class="container mx-auto pt-5">
      <!-- 屬會資訊 -->
      <div class="card-judo mb-4 px-6 py-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex min-w-0 items-center gap-4">
            <span class="org-badge">{{ organization.abbr }}</span>
            <div class="min-w-0">
              <p class="m-0 truncate text-lg font-semibold text-gray-800">
                {{ organization.full_name }}
              </p>
              <p class="m-0 mt-1 text-sm text-gray-500">
                <span v-if="organization.registration_code">
                  {{ organization.registration_code }}
                </span>
                <span v-if="organization.email" class="ml-2 inline-flex items-center gap-1">
                  <MailOutlined />
                  {{ organization.email }}
                </span>
              </p>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <inertia-link
              :href="route('manage.members.create')"
              class="btn-judo-primary"
              as="button"
            >
              <UserAddOutlined />
              {{ $t("create_member") }}
            </inertia-link>
            <inertia-link
              :href="route('manage.competitions.create')"
              class="btn-judo-outline"
              as="button"
            >
              <MergeCellsOutlined />
              {{ $t("competition_create") }}
            </inertia-link>
            <inertia-link
              v-if="member"
              :href="route('member.dashboard')"
              class="btn-judo-outline"
              as="button"
            >
              <IdcardOutlined />
              {{ $t("member_area") }}
            </inertia-link>
          </div>
        </div>

        <!-- 多個屬會時可切換 -->
        <div
          v-if="organizations && organizations.length > 1"
          class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4"
        >
          <span class="text-xs text-gray-500">{{ $t("switch_organization") }}</span>
          <inertia-link
            v-for="item in organizations"
            :key="item.id"
            :href="route('manage.select', item.id)"
            class="rounded-full px-3 py-1 text-xs ring-1 transition"
            :class="
              item.id === organization.id
                ? 'bg-[#0081C8] text-white ring-[#0081C8]'
                : 'bg-white text-gray-600 ring-gray-200 hover:text-[#0081C8] hover:ring-[#0081C8]/40'
            "
          >
            {{ item.abbr || item.full_name }}
          </inertia-link>
        </div>
      </div>

      <!-- 統計 -->
      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <inertia-link
          v-for="card in statCards"
          :key="card.key"
          :href="card.href"
          class="card-judo group flex items-center gap-3 px-4 py-4 transition hover:shadow-md"
        >
          <span class="stat-icon">
            <component :is="card.icon" />
          </span>
          <div class="min-w-0">
            <p class="m-0 truncate text-xs text-gray-500">{{ card.label }}</p>
            <p class="m-0 text-xl font-semibold text-gray-800 group-hover:text-[#0081C8]">
              {{ card.value }}
            </p>
            <p v-if="card.hint" class="m-0 truncate text-xs text-gray-400">
              {{ card.hint }}
            </p>
          </div>
        </inertia-link>
      </div>

      <!-- 最近賽事 / 最新報名 / 最新會員 -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card-judo">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <p class="m-0 flex items-center gap-2 text-sm font-semibold text-gray-700">
              <MergeCellsOutlined class="text-[#0081C8]" />
              {{ $t("recent_competitions") }}
            </p>
            <inertia-link
              :href="route('manage.competitions.index')"
              class="text-xs text-[#0081C8]"
            >
              {{ $t("all") }} &gt;
            </inertia-link>
          </div>
          <ul v-if="recentCompetitions.length" class="m-0 list-none p-0">
            <li
              v-for="item in recentCompetitions"
              :key="item.id"
              class="border-b border-gray-100 px-5 py-3 last:border-b-0 hover:bg-gray-50"
            >
              <div class="flex items-center justify-between gap-2">
                <inertia-link
                  :href="route('manage.competitions.show', item.id)"
                  class="min-w-0 truncate text-sm text-gray-800 hover:text-[#0081C8]"
                >
                  {{ item.title_zh || item.title_fn || "-" }}
                </inertia-link>
                <a-tag :color="item.published ? 'green' : undefined" class="m-0 shrink-0">
                  {{ item.published ? $t("published") : $t("draft") }}
                </a-tag>
              </div>
              <div class="mt-1 flex items-center justify-between gap-2 text-xs text-gray-400">
                <span class="truncate">
                  {{ item.applications_count }} {{ $t("applications") }}
                </span>
                <span class="shrink-0">{{ formatDate(item.start_date) }}</span>
              </div>
            </li>
          </ul>
          <p v-else class="m-0 px-5 py-10 text-center text-sm text-gray-400">
            {{ $t("no_records") }}
          </p>
        </div>

        <div class="card-judo">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <p class="m-0 flex items-center gap-2 text-sm font-semibold text-gray-700">
              <FileDoneOutlined class="text-[#0081C8]" />
              {{ $t("recent_applications") }}
            </p>
            <inertia-link
              :href="route('manage.competitions.index')"
              class="text-xs text-[#0081C8]"
            >
              {{ $t("all") }} &gt;
            </inertia-link>
          </div>
          <ul v-if="recentApplications.length" class="m-0 list-none p-0">
            <li
              v-for="item in recentApplications"
              :key="item.id"
              class="border-b border-gray-100 px-5 py-3 last:border-b-0 hover:bg-gray-50"
            >
              <div class="flex items-center justify-between gap-2">
                <inertia-link
                  v-if="item.competition_id"
                  :href="route('manage.competition.applications.index', item.competition_id)"
                  class="min-w-0 truncate text-sm text-gray-800 hover:text-[#0081C8]"
                >
                  {{ applicationName(item) }}
                </inertia-link>
                <span v-else class="min-w-0 truncate text-sm text-gray-800">
                  {{ applicationName(item) }}
                </span>
                <a-tag
                  :color="item.accepted ? 'green' : undefined"
                  class="m-0 shrink-0"
                >
                  {{
                    item.accepted
                      ? $t("competition_accepted")
                      : $t("competition_unaccepted")
                  }}
                </a-tag>
              </div>
              <div class="mt-1 flex items-center justify-between gap-2 text-xs text-gray-400">
                <span class="truncate">{{ competitionTitle(item.competition) }}</span>
                <span class="shrink-0">{{ formatDate(item.created_at) }}</span>
              </div>
            </li>
          </ul>
          <p v-else class="m-0 px-5 py-10 text-center text-sm text-gray-400">
            {{ $t("no_records") }}
          </p>
        </div>

        <div class="card-judo">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
            <p class="m-0 flex items-center gap-2 text-sm font-semibold text-gray-700">
              <TeamOutlined class="text-[#0081C8]" />
              {{ $t("recent_members") }}
            </p>
            <inertia-link :href="route('manage.members.index')" class="text-xs text-[#0081C8]">
              {{ $t("all") }} &gt;
            </inertia-link>
          </div>
          <ul v-if="recentMembers.length" class="m-0 list-none p-0">
            <li
              v-for="item in recentMembers"
              :key="item.id"
              class="flex items-center justify-between gap-2 border-b border-gray-100 px-5 py-3 last:border-b-0 hover:bg-gray-50"
            >
              <div class="flex min-w-0 items-center gap-2">
                <img
                  v-if="item.avatar_url"
                  :src="item.avatar_url"
                  class="h-8 w-8 shrink-0 rounded-full object-cover"
                  alt="avatar"
                />
                <span v-else class="member-badge">{{ memberInitial(item) }}</span>
                <inertia-link
                  :href="route('manage.members.show', item.id)"
                  class="min-w-0 truncate text-sm text-gray-800 hover:text-[#0081C8]"
                >
                  {{ memberName(item) }}
                </inertia-link>
              </div>
              <span class="shrink-0 text-xs text-gray-400">
                {{ formatDate(item.created_at) }}
              </span>
            </li>
          </ul>
          <p v-else class="m-0 px-5 py-10 text-center text-sm text-gray-400">
            {{ $t("no_records") }}
          </p>
        </div>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import {
  CalendarOutlined,
  CopyOutlined,
  FileDoneOutlined,
  FileProtectOutlined,
  FormOutlined,
  IdcardOutlined,
  MailOutlined,
  MergeCellsOutlined,
  SolutionOutlined,
  TeamOutlined,
  UserAddOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    OrganizationLayout,
    CalendarOutlined,
    CopyOutlined,
    FileDoneOutlined,
    FileProtectOutlined,
    FormOutlined,
    IdcardOutlined,
    MailOutlined,
    MergeCellsOutlined,
    SolutionOutlined,
    TeamOutlined,
    UserAddOutlined,
  },
  props: {
    organization: { type: Object, required: true },
    organizations: { type: Array, default: () => [] },
    member: { type: Object, default: null },
    stats: { type: Object, default: () => ({}) },
    recentCompetitions: { type: Array, default: () => [] },
    recentApplications: { type: Array, default: () => [] },
    recentMembers: { type: Array, default: () => [] },
  },
  computed: {
    statCards() {
      const stats = this.stats || {};
      return [
        {
          key: "members",
          label: this.$t("members"),
          value: stats.members || 0,
          icon: TeamOutlined,
          href: route("manage.members.index"),
        },
        {
          key: "forms",
          label: this.$t("forms"),
          value: stats.forms || 0,
          icon: FormOutlined,
          href: route("manage.forms.index"),
        },
        {
          key: "competitions",
          label: this.$t("competitions"),
          value: stats.competitions || 0,
          hint: stats.published_competitions
            ? `${this.$t("published")} ${stats.published_competitions}`
            : null,
          icon: MergeCellsOutlined,
          href: route("manage.competitions.index"),
        },
        {
          key: "events",
          label: this.$t("events"),
          value: stats.events || 0,
          icon: CalendarOutlined,
          href: route("manage.events.index"),
        },
        {
          key: "articles",
          label: this.$t("articles"),
          value: stats.articles || 0,
          icon: CopyOutlined,
          href: route("manage.articles.index"),
        },
        {
          key: "certificates",
          label: this.$t("certificates"),
          value: stats.certificates || 0,
          icon: FileProtectOutlined,
          href: route("manage.certificates.index"),
        },
        {
          key: "messages",
          label: this.$t("messages"),
          value: stats.messages || 0,
          icon: MailOutlined,
          href: route("manage.messages.index"),
        },
        {
          key: "exams",
          label: this.$t("exams"),
          value: stats.exams || 0,
          icon: SolutionOutlined,
          href: route("manage.exams.index"),
        },
      ];
    },
  },
  methods: {
    formatDate(value) {
      if (!value) return "-";
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) return value;
      const locale = this.$page.props.lang === "en" ? "en-US" : "zh-TW";
      return date.toLocaleDateString(locale, {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
      });
    },
    memberName(item) {
      return item.display_name || item.name_zh || item.name_fn || `#${item.id}`;
    },
    memberInitial(item) {
      const name = this.memberName(item);
      return name ? name.charAt(0).toUpperCase() : "?";
    },
    applicationName(item) {
      return item.display_name || item.name_zh || item.name_fn || `#${item.id}`;
    },
    competitionTitle(competition) {
      if (!competition) return "-";
      return competition.title_zh || competition.title_fn || "-";
    },
  },
};
</script>

<style scoped>
.org-badge,
.member-badge {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
  font-weight: 600;
}

.org-badge {
  height: 52px;
  width: 52px;
  font-size: 1rem;
}

.member-badge {
  height: 32px;
  width: 32px;
  font-size: 0.8rem;
}

.stat-icon {
  display: inline-flex;
  height: 38px;
  width: 38px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
  font-size: 1.05rem;
}
</style>
