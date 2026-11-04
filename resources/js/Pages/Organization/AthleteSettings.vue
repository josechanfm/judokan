<template>
  <OrganizationLayout :title="$t('athlete_settings')" :breadcrumb="breadcrumb">
    <!-- 工具列 -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="m-0 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500">
        <span>
          共
          <span class="mx-1 text-base font-semibold text-[#0081C8]">{{ tableRows.length }}</span>
          位會員
        </span>
        <span class="member-pill member-pill-blue">
          {{ $t("club_athlete") }} {{ counts.club }}
        </span>
        <span class="member-pill member-pill-green">
          {{ $t("team_member") }} {{ counts.team }}
        </span>
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="btn-judo-primary" :disabled="saving" @click="saveAll">
          <SaveOutlined />
          {{ saving ? "儲存中…" : $t("save") }}
        </button>
        <inertia-link :href="route('manage.trainingRecords.index')" class="btn-judo-outline">
          {{ $t("training_camps") }}
        </inertia-link>
      </div>
    </div>

    <!-- 篩選條件 -->
    <div class="card-judo p-5">
      <div class="flex flex-wrap items-end gap-3">
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("member_scope") }}</label>
          <a-select
            v-model:value="scope"
            :options="scopeOptions"
            style="width: 170px"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("search") }}</label>
          <a-input
            v-model:value="keyword"
            :placeholder="$t('member')"
            allow-clear
            style="width: 200px"
          />
        </div>
        <p class="m-0 pb-1 text-xs text-gray-400">
          勾選後按右上角「{{ $t("save") }}」套用；「{{ $t("training_camps") }}」只會顯示{{ $t("team_member") }}。
        </p>
      </div>

      <!-- 批次勾選（套用到目前篩選出的會員） -->
      <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-3 text-xs">
        <span class="flex items-center gap-2">
          <span class="font-medium text-gray-600">{{ $t("club_athlete") }}：</span>
          <button type="button" class="ja-link" @click="markAll('is_club_athlete', true)">
            全選
          </button>
          <span class="text-slate-300">|</span>
          <button
            type="button"
            class="ja-link ja-link-muted"
            @click="markAll('is_club_athlete', false)"
          >
            全不選
          </button>
        </span>
        <span class="flex items-center gap-2">
          <span class="font-medium text-gray-600">{{ $t("team_member") }}：</span>
          <button type="button" class="ja-link" @click="markAll('is_team_member', true)">
            全選
          </button>
          <span class="text-slate-300">|</span>
          <button
            type="button"
            class="ja-link ja-link-muted"
            @click="markAll('is_team_member', false)"
          >
            全不選
          </button>
        </span>
        <span class="text-gray-400">目前篩選 {{ filteredRows.length }} 位</span>
      </div>
    </div>

    <!-- 會員列表 -->
    <div class="card-judo mt-4">
      <div class="overflow-x-auto">
        <a-table
          :dataSource="filteredRows"
          :columns="columns"
          :pagination="pagination"
          :scroll="{ x: 780 }"
          row-key="member_id"
        >
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #emptyText>
            <div class="py-12 text-center text-gray-400">
              <p class="m-0 text-3xl">🥋</p>
              <p class="m-0 mt-3 text-sm">沒有符合的會員</p>
            </div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'name'">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-medium text-gray-800">{{ record.name }}</span>
                <span class="member-pill">{{ record.member_number }}</span>
              </div>
            </template>
            <template v-else-if="column.dataIndex === 'is_club_athlete'">
              <a-switch v-model:checked="record.is_club_athlete" size="small" />
            </template>
            <template v-else-if="column.dataIndex === 'is_team_member'">
              <a-switch v-model:checked="record.is_team_member" size="small" />
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import { message } from "ant-design-vue";

export default {
  components: {
    OrganizationLayout,
    SaveOutlined,
  },
  props: ["rows", "counts"],
  data() {
    return {
      breadcrumb: [
        { label: this.$t("members"), url: route("manage.members.index") },
        { label: this.$t("athlete_settings"), url: null },
      ],
      saving: false,
      keyword: "",
      scope: "all",
      tableRows: [],
      scopeOptions: [
        { value: "all", label: this.$t("all") },
        { value: "club", label: this.$t("club_athlete") },
        { value: "team", label: this.$t("team_member") },
        { value: "unset", label: "未設定" },
      ],
      columns: [
        {
          title: "member",
          dataIndex: "name",
          i18n: "member",
          width: 320,
        },
        {
          title: "club_athlete",
          dataIndex: "is_club_athlete",
          i18n: "club_athlete",
          width: 180,
          align: "center",
        },
        {
          title: "team_member",
          dataIndex: "is_team_member",
          i18n: "team_member",
          width: 180,
          align: "center",
        },
      ],
      pagination: {
        defaultPageSize: 50,
        showSizeChanger: true,
        pageSizeOptions: ["20", "50", "100", "200"],
      },
    };
  },
  computed: {
    filteredRows() {
      const keyword = this.keyword.trim().toLowerCase();

      return this.tableRows.filter((row) => {
        if (this.scope === "club" && !row.is_club_athlete) {
          return false;
        }

        if (this.scope === "team" && !row.is_team_member) {
          return false;
        }

        if (this.scope === "unset" && (row.is_club_athlete || row.is_team_member)) {
          return false;
        }

        if (keyword === "") {
          return true;
        }

        return (
          String(row.name || "").toLowerCase().includes(keyword) ||
          String(row.member_number || "").includes(keyword)
        );
      });
    },
  },
  watch: {
    rows(value) {
      this.tableRows = value.map((row) => ({ ...row }));
    },
  },
  created() {
    this.tableRows = this.rows.map((row) => ({ ...row }));
  },
  methods: {
    markAll(field, value) {
      this.filteredRows.forEach((row) => {
        row[field] = value;
      });
    },
    saveAll() {
      this.saving = true;

      this.$inertia.post(
        route("manage.athleteSettings.save"),
        {
          rows: this.tableRows.map((row) => ({
            member_id: row.member_id,
            is_club_athlete: !!row.is_club_athlete,
            is_team_member: !!row.is_team_member,
          })),
        },
        {
          preserveScroll: true,
          onSuccess: () => message.success("已儲存"),
          onError: () => message.error("儲存失敗，請檢查資料"),
          onFinish: () => {
            this.saving = false;
          },
        }
      );
    },
  },
};
</script>

<style scoped>
:deep(.ant-table-thead > tr > th) {
  background-color: #f8fafc;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.02em;
  border-bottom: 1px solid #e2e8f0;
}
:deep(.ant-table-tbody > tr > td) {
  padding-top: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  color: #334155;
}
:deep(.ant-table-tbody > tr:last-child > td) {
  border-bottom: none;
}
:deep(.ant-table-tbody > tr:hover > td) {
  background-color: rgba(0, 129, 200, 0.04);
}
.member-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  background-color: #f1f5f9;
  padding: 1px 8px;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  color: #475569;
}
.member-pill-blue {
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
}
.member-pill-green {
  background-color: rgba(34, 164, 93, 0.12);
  color: #1c8a4e;
}
.ja-link {
  border: 0;
  background: transparent;
  padding: 0;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
  line-height: 1;
  color: #0081c8;
  cursor: pointer;
}
.ja-link:hover {
  text-decoration: underline;
}
.ja-link-muted {
  color: #94a3b8;
}
</style>
