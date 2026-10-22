<template>
  <OrganizationLayout :title="$t('points_statistics')" :breadcrumb="breadcrumb">
    <div class="container mx-auto pt-5">
      <!-- 工具列 -->
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="m-0 text-sm text-gray-500">
          共
          <span class="mx-1 text-base font-semibold text-[#0081C8]">{{ ranking.length }}</span>
          位會員有成績
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="btn-judo-outline" @click="openImport">
            <UploadOutlined />
            {{ $t("import_results") }}
          </button>
          <a :href="exportUrl" class="btn-judo-primary" download>
            <DownloadOutlined />
            {{ $t("export_points") }}
          </a>
        </div>
      </div>

      <!-- 篩選條件 -->
      <div class="card-judo p-5">
        <div class="flex flex-wrap items-end gap-3">
          <div>
            <label class="mb-1 block text-xs text-gray-500">{{ $t("period") }}</label>
            <a-range-picker
              v-model:value="range"
              :value-format="dateFormat"
              :format="dateFormat"
              :allow-clear="false"
              style="width: 250px"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">{{ $t("role") }}</label>
            <a-select
              v-model:value="role"
              :options="roleOptions"
              allow-clear
              :placeholder="$t('all')"
              style="width: 150px"
            />
          </div>
          <div>
            <label class="mb-1 block text-xs text-gray-500">{{ $t("search") }}</label>
            <a-input
              v-model:value="keyword"
              :placeholder="$t('member')"
              allow-clear
              style="width: 200px"
              @press-enter="applyFilters"
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
          <span class="stat-icon"><CalendarOutlined /></span>
          <div class="min-w-0">
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("period") }}
            </p>
            <p class="m-0 truncate text-sm font-semibold text-gray-800">
              {{ filters.start }} ~ {{ filters.end }}
            </p>
          </div>
        </div>
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="stat-icon"><TeamOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("member_count") }}
            </p>
            <p class="m-0 text-sm font-semibold text-gray-800">
              {{ ranking.length }}
            </p>
          </div>
        </div>
        <div class="card-judo flex items-center gap-3 px-5 py-4">
          <span class="stat-icon"><TrophyOutlined /></span>
          <div>
            <p class="m-0 whitespace-nowrap text-xs text-gray-500">
              {{ $t("highest_score") }}
            </p>
            <p class="m-0 text-sm font-semibold text-gray-800">{{ topScore }}</p>
          </div>
        </div>
      </div>

      <!-- 積分排行 -->
      <div class="card-judo mt-4">
        <div class="overflow-x-auto">
          <a-table
            :dataSource="ranking"
            :columns="columns"
            :pagination="pagination"
            :scroll="{ x: 860 }"
            row-key="key"
          >
            <template #headerCell="{ column }">
              <template v-if="column.i18n">{{ $t(column.i18n) }}</template>
              <template v-else-if="typeof column.title === 'string'">
                {{ column.title }}
              </template>
            </template>
            <template #emptyText>
              <div class="py-12 text-center text-gray-400">
                <p class="m-0 text-3xl">🥋</p>
                <p class="m-0 mt-3 text-sm">{{ $t("no_points_records") }}</p>
              </div>
            </template>
            <template #bodyCell="{ column, record, index }">
              <template v-if="column.dataIndex == 'rank'">
                <span class="member-pill" :class="rankClass(index)">
                  {{ index + 1 }}
                </span>
              </template>
              <template v-else-if="column.dataIndex == 'name'">
                <div class="flex flex-wrap items-center gap-2">
                  <inertia-link
                    v-if="record.is_member"
                    :href="route('manage.members.show', record.member_id)"
                    class="font-medium text-[#0081C8] hover:underline"
                  >
                    {{ record.name }}
                  </inertia-link>
                  <span v-else class="font-medium text-gray-700">
                    {{ record.name }}
                  </span>
                  <span v-if="record.member_number" class="member-pill member-pill-gray">
                    {{ $t("member_number") }} {{ record.member_number }}
                  </span>
                  <span v-if="!record.is_member" class="member-pill member-pill-amber">
                    {{ $t("guest_member") }}
                  </span>
                </div>
              </template>
              <template v-else-if="column.dataIndex == 'total_score'">
                <span class="text-base font-bold text-[#0081C8]">
                  {{ record.total_score }}
                </span>
              </template>
              <template v-else>
                {{ record[column.dataIndex] }}
              </template>
            </template>

            <!-- 展開：該會員各場比賽成績 -->
            <template #expandedRowRender="{ record }">
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-gray-500">
                      <th class="py-2 pr-3 font-semibold">{{ $t("competition") }}</th>
                      <th class="py-2 pr-3 font-semibold">{{ $t("date") }}</th>
                      <th class="py-2 pr-3 font-semibold">{{ $t("category") }}</th>
                      <th class="py-2 pr-3 font-semibold">{{ $t("weight") }}</th>
                      <th class="py-2 pr-3 font-semibold">{{ $t("role") }}</th>
                      <th class="py-2 pr-3 font-semibold">{{ $t("result_rank") }}</th>
                      <th class="py-2 text-right font-semibold">
                        {{ $t("result_score") }}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(result, idx) in record.results"
                      :key="idx"
                      class="border-t border-slate-100"
                    >
                      <td class="py-2 pr-3 text-gray-800">
                        {{ result.competition_title }}
                      </td>
                      <td class="py-2 pr-3 text-gray-600">{{ result.date }}</td>
                      <td class="py-2 pr-3 text-gray-600">{{ result.category }}</td>
                      <td class="py-2 pr-3 text-gray-600">{{ result.weight }}</td>
                      <td class="py-2 pr-3 text-gray-600">
                        {{ roleLabel(result.role) }}
                      </td>
                      <td class="py-2 pr-3">
                        <span class="member-pill member-pill-blue">
                          {{ rankLabel(result.result_rank) }}
                        </span>
                      </td>
                      <td class="py-2 text-right font-semibold text-gray-800">
                        {{ result.result_score }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </a-table>
        </div>
      </div>
    </div>

    <!-- 匯入成績 -->
    <a-modal
      :title="$t('import_results')"
      v-model:open="importVisible"
      :width="640"
      @cancel="closeImport"
    >
      <a-upload-dragger
        v-model:fileList="files"
        name="file"
        :beforeUpload="() => false"
        :multiple="false"
        :maxCount="1"
        accept=".xlsx,.xls"
      >
        <p class="ant-upload-drag-icon">
          <FileExcelOutlined />
        </p>
        <p class="ant-upload-text">點擊或拖曳檔案到這裡匯入比賽成績</p>
        <p class="ant-upload-hint">
          支援 .xlsx / .xls 檔案，一次一個。請先下載下方的成績匯入範本填寫；
          新的比賽會自動建立，姓名會自動比對本屬會會員。
        </p>
      </a-upload-dragger>

      <div v-if="importDone" class="mt-3">
        <div v-if="importErrors.length === 0" class="font-bold my-3 text-green-600">
          成功匯入 {{ importedCount }} 筆成績
        </div>
        <div v-else class="font-bold my-3 text-yellow-600">
          成功匯入 {{ importedCount }} 筆，以下 {{ importErrors.length }} 筆未匯入
        </div>
        <p
          v-for="(error, index) in importErrors"
          :key="index"
          class="font-mono m-0"
        >
          <WarningOutlined class="!text-yellow-500" />
          第 {{ error.row }} 行（{{ error.attribute }}）: {{ error.errors[0] }}
        </p>
      </div>

      <template #footer>
        <div class="flex w-full flex-wrap justify-between gap-2">
          <a href="/templates/result_list.xlsx" download>
            <a-button type="link">
              <template #icon>
                <DownloadOutlined />
              </template>
              下載成績匯入範本（result_list.xlsx）
            </a-button>
          </a>
          <a-button
            type="primary"
            class="btn-judo-primary"
            @click="handleImport"
            :disabled="files.length === 0"
            :loading="importLoading"
          >
            匯入
          </a-button>
        </div>
      </template>
    </a-modal>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import {
  CalendarOutlined,
  DownloadOutlined,
  FileExcelOutlined,
  SearchOutlined,
  TeamOutlined,
  TrophyOutlined,
  UploadOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";
import dayjs from "dayjs";

export default {
  components: {
    OrganizationLayout,
    CalendarOutlined,
    DownloadOutlined,
    FileExcelOutlined,
    SearchOutlined,
    TeamOutlined,
    TrophyOutlined,
    UploadOutlined,
    WarningOutlined,
  },
  props: ["ranking", "competitionResults", "competitionRoles", "filters"],
  data() {
    return {
      dateFormat: "YYYY-MM-DD",
      range: [this.filters.start, this.filters.end],
      role: this.filters.role || null,
      keyword: this.filters.keyword || "",
      columns: [
        { title: "rank", dataIndex: "rank", i18n: "rank", width: 80 },
        { title: "name", dataIndex: "name", i18n: "member" },
        {
          title: "competition_count",
          dataIndex: "competition_count",
          i18n: "competition_count",
          width: 120,
          sorter: (a, b) => a.competition_count - b.competition_count,
        },
        {
          title: "total_score",
          dataIndex: "total_score",
          i18n: "total_score",
          width: 140,
          sorter: (a, b) => a.total_score - b.total_score,
          defaultSortOrder: "descend",
        },
      ],
      pagination: {
        defaultPageSize: 20,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
      },
      importVisible: false,
      importDone: false,
      importErrors: [],
      importedCount: 0,
      importLoading: false,
      files: [],
    };
  },
  computed: {
    breadcrumb() {
      return [
        { label: this.$t("members"), url: route("manage.members.index") },
        { label: this.$t("points_statistics"), url: null },
      ];
    },
    roleOptions() {
      return (this.competitionRoles || []).map((role) => ({
        value: role.value,
        label: role.label,
      }));
    },
    topScore() {
      return this.ranking.length
        ? Math.max(...this.ranking.map((row) => row.total_score))
        : 0;
    },
    // 匯出時帶上目前的篩選條件
    exportUrl() {
      const params = new URLSearchParams({
        start: this.filters.start,
        end: this.filters.end,
      });
      if (this.filters.role) {
        params.set("role", this.filters.role);
      }
      if (this.filters.keyword) {
        params.set("keyword", this.filters.keyword);
      }

      return `${route("manage.member.points.export")}?${params.toString()}`;
    },
  },
  methods: {
    openImport() {
      this.importVisible = true;
      this.importDone = false;
      this.importErrors = [];
      this.importedCount = 0;
      this.files = [];
    },
    closeImport() {
      this.importVisible = false;
      this.importDone = false;
      this.importErrors = [];
      this.importedCount = 0;
      this.files = [];
      this.importLoading = false;
    },
    handleImport() {
      if (this.files.length === 0) {
        this.$message.warning("請先選擇檔案");
        return;
      }

      this.importLoading = true;
      const formData = new FormData();
      formData.append("file", this.files[0].originFileObj);

      window.axios
        .post(route("manage.member.points.import"), formData, {
          headers: { "Content-Type": "multipart/form-data" },
        })
        .then(({ data }) => {
          this.importLoading = false;
          this.files = [];
          this.importedCount = data.imported || 0;
          this.importErrors = data.errors || [];
          this.importDone = true;
          this.$message.success(`成功匯入 ${this.importedCount} 筆成績`);

          this.$inertia.reload({ preserveState: true });
        })
        .catch((error) => {
          this.importLoading = false;
          console.error(error);
          this.$message.error("匯入失敗");
        });
    },
    rankLabel(value) {
      const item = (this.competitionResults || []).find((r) => r.value === value);
      return item?.label || value || "—";
    },
    roleLabel(value) {
      const item = (this.competitionRoles || []).find((r) => r.value === value);
      return item?.label || value || "—";
    },
    rankClass(index) {
      if (index === 0) {
        return "member-pill-gold";
      }
      if (index === 1) {
        return "member-pill-silver";
      }
      if (index === 2) {
        return "member-pill-bronze";
      }
      return "member-pill-gray";
    },
    applyFilters() {
      this.$inertia.get(
        route("manage.member.points"),
        {
          start: this.range?.[0] || null,
          end: this.range?.[1] || null,
          role: this.role || null,
          keyword: this.keyword || null,
        },
        { preserveState: true, preserveScroll: true }
      );
    },
    resetFilters() {
      const end = dayjs().format(this.dateFormat);
      this.range = [
        dayjs(end).subtract(2, "year").add(1, "day").format(this.dateFormat),
        end,
      ];
      this.role = null;
      this.keyword = "";
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
.member-pill-amber {
  background-color: rgba(217, 119, 6, 0.12);
  color: #b45309;
}
.member-pill-gold {
  background-color: rgba(202, 138, 4, 0.16);
  color: #a16207;
}
.member-pill-silver {
  background-color: rgba(100, 116, 139, 0.16);
  color: #475569;
}
.member-pill-bronze {
  background-color: rgba(180, 83, 9, 0.16);
  color: #9a3412;
}
</style>
