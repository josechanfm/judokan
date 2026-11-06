<template>
  <OrganizationLayout :title="$t('training_camps')" :breadcrumb="breadcrumb">
    <!-- 工具列 -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="m-0 text-sm text-gray-500">
        共
        <span class="mx-1 text-base font-semibold text-[#0081C8]">{{ tableRows.length }}</span>
        位{{ $t("athlete") }}（{{ $t("team_member") }} + {{ $t("club_athlete") }}）
        <span class="ml-2 text-xs text-gray-400">{{ monthValue }}</span>
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="btn-judo-primary"
          :disabled="saving"
          @click="saveAll"
        >
          <SaveOutlined />
          {{ saving ? "儲存中…" : $t("save") }}
        </button>
        <a :href="exportUrl" class="btn-judo-outline" download>
          <DownloadOutlined />
          {{ $t("export") }}
        </a>
        <button type="button" class="btn-judo-outline" @click="openRates">
          <SettingOutlined />
          {{ $t("point_rates") }}
        </button>
        <button type="button" class="btn-judo-outline" @click="importModal.visible = true">
          <UploadOutlined />
          {{ $t("import") }}
        </button>
        <a :href="templateUrl" download class="btn-judo-outline">
          <FileExcelOutlined />
          {{ $t("download_template") }}
        </a>
      </div>
    </div>

    <!-- 篩選條件 -->
    <div class="card-judo p-5">
      <div class="flex flex-wrap items-end gap-3">
        <div>
          <label class="mb-1 block text-xs text-gray-500">{{ $t("month") }}</label>
          <a-date-picker
            v-model:value="monthValue"
            picker="month"
            :value-format="monthFormat"
            :format="monthFormat"
            :allow-clear="false"
            style="width: 160px"
            @change="applyFilters"
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
          顯示{{ $t("team_member") }}及{{ $t("club_athlete") }}，可到
          <inertia-link
            :href="route('manage.athleteSettings.index')"
            class="font-medium text-[#0081C8] hover:underline"
          >
            {{ $t("athlete_settings") }}
          </inertia-link>
          調整；修改後按右上角「{{ $t("save") }}」套用。
        </p>
      </div>
    </div>

    <!-- 每月記錄表 -->
    <div class="card-judo mt-4">
      <div class="overflow-x-auto">
        <a-table
          :dataSource="filteredRows"
          :columns="columns"
          :pagination="pagination"
          :scroll="{ x: 1150 }"
          row-key="member_id"
        >
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #emptyText>
            <div class="py-12 text-center text-gray-400">
              <p class="m-0 text-3xl">🏋️</p>
              <p class="m-0 mt-3 text-sm">
                尚未有{{ $t("team_member") }}或{{ $t("club_athlete") }}，請先到「{{ $t("athlete_settings") }}」勾選
              </p>
            </div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'name'">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-medium text-gray-800">{{ record.name }}</span>
                <span class="member-pill">{{ record.member_number }}</span>
              </div>
            </template>
            <template v-else-if="column.dataIndex === 'athlete_type'">
              <div class="flex flex-wrap items-center gap-1">
                <span
                  v-if="record.is_team_member"
                  class="member-pill member-pill-blue"
                >
                  {{ $t("team_member") }}
                </span>
                <span
                  v-if="record.is_club_athlete"
                  class="member-pill member-pill-gray"
                >
                  {{ $t("club_athlete") }}
                </span>
              </div>
            </template>
            <template v-else-if="column.dataIndex === 'points'">
              <span class="text-base font-bold text-[#0081C8]">
                {{ rowPoints(record) }}
              </span>
            </template>
            <template v-else-if="isCountField(column.dataIndex)">
              <a-input-number
                v-model:value="record[column.dataIndex]"
                :min="0"
                :precision="0"
                size="small"
                class="training-input"
              />
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
    </div>

    <!-- 計分設定 -->
    <a-modal v-model:open="ratesModal.visible" :title="$t('point_rates')" :width="520">
      <p class="m-0 mb-3 text-xs text-gray-500">
        每項次數可換算的分數，運動員的集訓積分 = 四項次數 × 對應分數。
      </p>
      <a-form layout="vertical">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <a-form-item v-for="field in countFields" :key="field" :label="$t(countLabels[field])">
            <a-input-number
              v-model:value="ratesModal.data[field]"
              :min="0"
              :precision="0"
              style="width: 100%"
            />
          </a-form-item>
        </div>
      </a-form>
      <template #footer>
        <a-button @click="ratesModal.visible = false">{{ $t("cancel") }}</a-button>
        <a-button type="primary" :loading="ratesModal.loading" @click="submitRates">
          {{ $t("save") }}
        </a-button>
      </template>
    </a-modal>

    <!-- 匯入每月名單 -->
    <a-modal
      v-model:open="importModal.visible"
      :title="$t('import')"
      :width="640"
      @cancel="closeImportModal"
    >
      <a-upload-dragger
        v-model:fileList="importModal.files"
        name="file"
        :beforeUpload="() => false"
        :multiple="false"
        :maxCount="1"
        accept=".xlsx,.xls"
      >
        <p class="ant-upload-drag-icon">
          <FileExcelOutlined />
        </p>
        <p class="ant-upload-text">點擊或拖曳檔案到這裡匯入每月記錄</p>
        <p class="ant-upload-hint">
          支援 .xlsx / .xls 檔案，一次一個。請先下載下方的範本填寫；
          一行代表一位運動員一個月，會員可用會員號碼或姓名比對，
          已存在的月份會以檔案內容為準覆蓋。
        </p>
      </a-upload-dragger>

      <div v-if="importModal.done" class="mt-3">
        <div
          v-if="importModal.errors.length === 0"
          class="my-3 font-bold text-green-600"
        >
          成功匯入 {{ importModal.count }} 筆記錄
        </div>
        <div v-else class="my-3 font-bold text-yellow-600">
          成功匯入 {{ importModal.count }} 筆，以下
          {{ importModal.errors.length }} 筆未匯入
        </div>
        <p
          v-for="(error, index) in importModal.errors"
          :key="index"
          class="m-0 font-mono"
        >
          <WarningOutlined class="!text-yellow-500" />
          第 {{ error.row }} 行（{{ error.attribute }}）: {{ error.errors[0] }}
        </p>
      </div>

      <template #footer>
        <div class="flex w-full flex-wrap items-center justify-between gap-2">
          <a :href="templateUrl" download class="btn-judo-outline">
            <DownloadOutlined />
            下載範本
          </a>
          <a-button
            type="primary"
            :loading="importModal.loading"
            :disabled="importModal.files.length === 0"
            @click="handleImport"
          >
            {{ $t("import") }}
          </a-button>
        </div>
      </template>
    </a-modal>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import {
  DownloadOutlined,
  FileExcelOutlined,
  SaveOutlined,
  SettingOutlined,
  UploadOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";

const COUNT_FIELDS = [
  "centre_count",
  "win_count",
  "session_count",
  "competition_day_count",
];

export default {
  components: {
    OrganizationLayout,
    DownloadOutlined,
    FileExcelOutlined,
    SaveOutlined,
    SettingOutlined,
    UploadOutlined,
    WarningOutlined,
  },
  props: ["rows", "month", "rates"],
  data() {
    return {
      breadcrumb: [
        { label: this.$t("members"), url: route("manage.members.index") },
        { label: this.$t("training_camps"), url: null },
      ],
      monthFormat: "YYYY-MM",
      countFields: COUNT_FIELDS,
      countLabels: {
        centre_count: "centre_training",
        win_count: "wins",
        session_count: "training_sessions",
        competition_day_count: "competition_days",
      },
      templateUrl: "/templates/training_record_list.xlsx",
      monthValue: this.month,
      keyword: "",
      saving: false,
      tableRows: [],
      columns: [
        {
          title: "member",
          dataIndex: "name",
          i18n: "member",
          width: 220,
        },
        {
          title: "athlete_type",
          dataIndex: "athlete_type",
          i18n: "type",
          width: 210,
          // 集訓隊排最前，同類型再按姓名
          sorter: (a, b) =>
            Number(b.is_team_member) - Number(a.is_team_member) ||
            Number(b.is_club_athlete) - Number(a.is_club_athlete),
        },
        {
          title: "centre",
          dataIndex: "centre_count",
          i18n: "centre_training",
          width: 140,
          align: "center",
        },
        {
          title: "wins",
          dataIndex: "win_count",
          i18n: "wins",
          width: 110,
          align: "center",
        },
        {
          title: "sessions",
          dataIndex: "session_count",
          i18n: "training_sessions",
          width: 140,
          align: "center",
        },
        {
          title: "competition_days",
          dataIndex: "competition_day_count",
          i18n: "competition_days",
          width: 140,
          align: "center",
        },
        {
          title: "points",
          dataIndex: "points",
          i18n: "score",
          width: 100,
          align: "right",
          sorter: (a, b) => this.rowPoints(a) - this.rowPoints(b),
        },
      ],
      pagination: {
        defaultPageSize: 50,
        showSizeChanger: true,
        pageSizeOptions: ["20", "50", "100", "200"],
      },
      ratesModal: {
        visible: false,
        loading: false,
        data: { ...this.rates },
      },
      importModal: {
        visible: false,
        files: [],
        done: false,
        count: 0,
        errors: [],
        loading: false,
      },
    };
  },
  computed: {
    filteredRows() {
      const keyword = this.keyword.trim().toLowerCase();

      if (keyword === "") {
        return this.tableRows;
      }

      return this.tableRows.filter(
        (row) =>
          String(row.name || "").toLowerCase().includes(keyword) ||
          String(row.member_number || "").includes(keyword)
      );
    },
    exportUrl() {
      const params = new URLSearchParams({ month: this.monthValue });

      return `${route("manage.trainingRecords.export")}?${params.toString()}`;
    },
  },
  watch: {
    // 換月份或儲存後由後端回傳最新資料
    rows(value) {
      this.tableRows = value.map((row) => ({ ...row }));
    },
    month(value) {
      this.monthValue = value;
    },
  },
  created() {
    this.tableRows = this.rows.map((row) => ({ ...row }));
  },
  methods: {
    isCountField(dataIndex) {
      return COUNT_FIELDS.includes(dataIndex);
    },
    rowPoints(row) {
      return COUNT_FIELDS.reduce(
        (total, field) =>
          total + (Number(row[field]) || 0) * (Number(this.rates[field]) || 0),
        0
      );
    },
    applyFilters() {
      this.$inertia.get(
        route("manage.trainingRecords.index"),
        { month: this.monthValue },
        { preserveState: true, preserveScroll: true, replace: true }
      );
    },
    saveAll() {
      this.saving = true;

      this.$inertia.post(
        route("manage.trainingRecords.save"),
        {
          month: this.monthValue,
          records: this.tableRows.map((row) => ({
            member_id: row.member_id,
            centre_count: Number(row.centre_count) || 0,
            win_count: Number(row.win_count) || 0,
            session_count: Number(row.session_count) || 0,
            competition_day_count: Number(row.competition_day_count) || 0,
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
    openRates() {
      this.ratesModal.data = { ...this.rates };
      this.ratesModal.visible = true;
    },
    submitRates() {
      this.ratesModal.loading = true;

      this.$inertia.post(route("manage.trainingRecords.rates"), this.ratesModal.data, {
        preserveScroll: true,
        onSuccess: () => {
          this.ratesModal.visible = false;
          message.success("已更新計分設定");
        },
        onError: () => message.error("儲存失敗，請檢查資料"),
        onFinish: () => {
          this.ratesModal.loading = false;
        },
      });
    },
    closeImportModal() {
      this.importModal.visible = false;
      this.importModal.files = [];
      this.importModal.done = false;
      this.importModal.count = 0;
      this.importModal.errors = [];
      this.importModal.loading = false;
    },
    handleImport() {
      if (this.importModal.files.length === 0) {
        message.warning("請先選擇檔案");
        return;
      }

      this.importModal.loading = true;

      const formData = new FormData();
      formData.append("file", this.importModal.files[0].originFileObj);

      window.axios
        .post(route("manage.trainingRecords.import"), formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(({ data }) => {
          this.importModal.loading = false;
          this.importModal.files = [];
          this.importModal.count = data.imported || 0;
          this.importModal.errors = data.errors || [];
          this.importModal.done = true;

          message.success(`成功匯入 ${this.importModal.count} 筆記錄`);

          this.$inertia.reload({ preserveState: true });
        })
        .catch((error) => {
          this.importModal.loading = false;

          const validationErrors = error?.response?.data?.errors;
          message.error(
            validationErrors
              ? Object.values(validationErrors)[0][0]
              : "匯入失敗，請稍後再試"
          );
        });
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
  padding-top: 8px;
  padding-bottom: 8px;
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
.training-input {
  width: 90px;
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
.member-pill-gray {
  background-color: #f1f5f9;
  color: #475569;
}
</style>
