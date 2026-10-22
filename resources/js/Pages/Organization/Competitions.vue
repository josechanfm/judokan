<template>
  <OrganizationLayout :title="$t('competitions')" :breadcrumb="breadcrumb">
    <div class="container mx-auto pt-5">
      <!-- 工具列 -->
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="m-0 text-sm text-gray-500">
          共
          <span class="mx-1 text-base font-semibold text-[#0081C8]">{{ competitions.total }}</span>
          場賽事
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <inertia-link
            :href="route('manage.competitions.create')"
            class="btn-judo-primary"
            as="button"
          >
            <PlusOutlined />
            {{ $t("competition_create") }}
          </inertia-link>
        </div>
      </div>

      <!-- 篩選條件 -->
      <div class="card-judo p-5">
        <div class="flex flex-wrap items-end gap-3">
          <div>
            <label class="mb-1 block text-xs text-gray-500">
              {{ $t("search") }}
            </label>
            <a-input
              v-model:value="keyword"
              :placeholder="$t('competition_title_zh')"
              allow-clear
              style="width: 240px"
              @press-enter="applyFilters"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">
              {{ $t("state") }}
            </label>
            <a-select
              v-model:value="status"
              :options="statusOptions"
              allow-clear
              :placeholder="$t('all')"
              style="width: 150px"
            />
          </div>
          <button type="button" class="btn-judo-primary" @click="applyFilters">
            <SearchOutlined />
            {{ $t("query") }}
          </button>
          <button type="button" class="btn-judo-outline" @click="resetFilters">
            {{ $t("reset") }}
          </button>
        </div>
      </div>

      <!-- 摘要 -->
      <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="stat-icon"><TrophyOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("total") }}
            </p>
            <p class="m-0 text-sm font-semibold text-gray-800">{{ stats.total }}</p>
          </div>
        </div>
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="stat-icon"><CheckCircleOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("published") }}
            </p>
            <p class="m-0 text-sm font-semibold text-gray-800">{{ stats.published }}</p>
          </div>
        </div>
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="stat-icon"><FileDoneOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("draft") }}
            </p>
            <p class="m-0 text-sm font-semibold text-gray-800">{{ stats.draft }}</p>
          </div>
        </div>
      </div>

      <!-- 賽事列表 -->
      <div class="card-judo mt-4">
        <div class="overflow-x-auto">
          <a-table
            :dataSource="competitions.data"
            :columns="columns"
            :pagination="pagination"
            :scroll="{ x: 1080 }"
            row-key="id"
          >
            <template #headerCell="{ column }">
              <template v-if="column.i18n">{{ $t(column.i18n) }}</template>
              <template v-else-if="typeof column.title === 'string'">
                {{ column.title }}
              </template>
            </template>
            <template #emptyText>
              <div class="py-12 text-center text-gray-400">
                <p class="m-0 text-3xl">🏆</p>
                <p class="m-0 mt-3 text-sm">目前沒有符合條件的賽事</p>
              </div>
            </template>
            <template #bodyCell="{ column, record }">
              <template v-if="column.dataIndex == 'title'">
                <div class="min-w-0">
                  <inertia-link
                    :href="route('manage.competitions.show', record.id)"
                    class="font-medium text-[#0081C8] hover:underline"
                  >
                    {{ record.title_zh || record.title_fn }}
                  </inertia-link>
                  <p v-if="record.title_fn" class="m-0 truncate text-xs text-gray-400">
                    {{ record.title_fn }}
                  </p>
                </div>
              </template>
              <template v-else-if="column.dataIndex == 'date'">
                <span class="whitespace-nowrap text-gray-700">
                  {{ record.start_date }} ~ {{ record.end_date }}
                </span>
              </template>
              <template v-else-if="column.dataIndex == 'scheme'">
                <span v-if="record.score" class="member-pill member-pill-blue">
                  {{ record.score.title }}
                </span>
                <span v-else class="text-gray-300">—</span>
              </template>
              <template v-else-if="column.dataIndex == 'published'">
                <span
                  class="member-pill"
                  :class="record.published ? 'member-pill-green' : 'member-pill-gray'"
                >
                  {{ record.published ? $t("published") : $t("draft") }}
                </span>
              </template>
              <template v-else-if="column.dataIndex == 'operation'">
                <div class="member-actions flex flex-wrap items-center gap-1">
                  <inertia-link
                    :href="route('manage.competitions.show', record.id)"
                    class="ant-btn ant-btn-link ant-btn-sm"
                  >
                    {{ $t("view") }}
                  </inertia-link>
                  <inertia-link
                    :href="route('manage.competitions.edit', record.id)"
                    class="ant-btn ant-btn-link ant-btn-sm"
                  >
                    {{ $t("edit") }}
                  </inertia-link>
                  <inertia-link
                    :href="route('manage.competition.applications.index', record.id)"
                    class="ant-btn ant-btn-link ant-btn-sm"
                  >
                    {{ $t("applications") }}
                  </inertia-link>
                  <inertia-link
                    :href="route('manage.competition.results.index', record.id)"
                    class="ant-btn ant-btn-link ant-btn-sm"
                  >
                    {{ $t("competition_result") }}
                  </inertia-link>
                </div>
              </template>
              <template v-else>
                {{ record[column.dataIndex] }}
              </template>
            </template>
          </a-table>
        </div>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import {
  CheckCircleOutlined,
  FileDoneOutlined,
  PlusOutlined,
  SearchOutlined,
  TrophyOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    OrganizationLayout,
    CheckCircleOutlined,
    FileDoneOutlined,
    PlusOutlined,
    SearchOutlined,
    TrophyOutlined,
  },
  props: ["competitions", "filters", "stats"],
  data() {
    return {
      keyword: this.filters?.keyword || "",
      status: this.filters?.status || null,
      statusOptions: [
        { value: "published", label: this.$t("published") },
        { value: "draft", label: this.$t("draft") },
      ],
      columns: [
        { title: "title", dataIndex: "title", i18n: "competition_title_zh" },
        { title: "date", dataIndex: "date", i18n: "date", width: 210 },
        { title: "scheme", dataIndex: "scheme", i18n: "scheme", width: 250 },
        { title: "state", dataIndex: "published", i18n: "state", width: 110 },
        {
          title: "operation",
          dataIndex: "operation",
          i18n: "operation",
          width: 300,
        },
      ],
    };
  },
  computed: {
    breadcrumb() {
      return [{ label: this.$t("competitions"), url: null }];
    },
    pagination() {
      return {
        current: this.competitions.current_page,
        pageSize: this.competitions.per_page,
        total: this.competitions.total,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
        showTotal: (total) => `共 ${total} 場`,
        onChange: (page, pageSize) => this.goToPage(page, pageSize),
      };
    },
  },
  methods: {
    goToPage(page, pageSize) {
      this.$inertia.get(
        route("manage.competitions.index"),
        {
          ...this.currentQuery(),
          page,
          per_page: pageSize || this.competitions.per_page,
        },
        { preserveState: true, preserveScroll: true }
      );
    },
    currentQuery() {
      const query = {};
      if (this.filters?.keyword) {
        query.keyword = this.filters.keyword;
      }
      if (this.filters?.status) {
        query.status = this.filters.status;
      }
      if (this.competitions?.per_page) {
        query.per_page = this.competitions.per_page;
      }

      return query;
    },
    applyFilters() {
      this.$inertia.get(
        route("manage.competitions.index"),
        {
          keyword: this.keyword || null,
          status: this.status || null,
          per_page: this.competitions.per_page,
        },
        { preserveState: true, preserveScroll: true }
      );
    },
    resetFilters() {
      this.keyword = "";
      this.status = null;
      this.applyFilters();
    },
  },
};
</script>

<style scoped>
.stat-icon {
  display: inline-flex;
  height: 38px;
  width: 38px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
  font-size: 18px;
}
.member-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 1px 10px;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  white-space: nowrap;
}
.member-pill-blue {
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
}
.member-pill-gray {
  background-color: #f1f5f9;
  color: #475569;
}
.member-pill-green {
  background-color: rgba(34, 164, 93, 0.12);
  color: #17713f;
}
.member-actions :deep(.ant-btn) {
  height: 28px;
  padding: 0 10px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  box-shadow: none;
}
.member-actions :deep(.ant-btn-link) {
  color: #0081c8;
}
.member-actions :deep(.ant-btn-link:hover) {
  color: #0b6ea8;
  background-color: rgba(0, 129, 200, 0.08);
}
</style>
