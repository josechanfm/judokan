<template>
  <OrganizationLayout :title="$t('training_camps')" :breadcrumb="breadcrumb">
    <!-- 工具列 -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="m-0 text-sm text-gray-500">
        共
        <span class="mx-1 text-base font-semibold text-[#0081C8]">{{ camps.length }}</span>
        場集訓
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="btn-judo-primary" @click="openCreate">
          <PlusOutlined />
          {{ $t("training_camp_create") }}
        </button>
        <button type="button" class="btn-judo-outline" @click="importModal.visible = true">
          <UploadOutlined />
          {{ $t("training_camp_import") }}
        </button>
        <a :href="templateUrl" download class="btn-judo-outline">
          <DownloadOutlined />
          {{ $t("download_template") }}
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
          <label class="mb-1 block text-xs text-gray-500">{{ $t("search") }}</label>
          <a-input
            v-model:value="keyword"
            :placeholder="$t('training_camp')"
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

    <!-- 集訓列表 -->
    <div class="card-judo mt-4">
      <div class="overflow-x-auto">
        <a-table
          :dataSource="camps"
          :columns="columns"
          :pagination="pagination"
          :scroll="{ x: 900 }"
          row-key="id"
        >
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #emptyText>
            <div class="py-12 text-center text-gray-400">
              <p class="m-0 text-3xl">🏋️</p>
              <p class="m-0 mt-3 text-sm">
                尚無集訓記錄，可點右上角「新增集訓」或「匯入出席名單」
              </p>
            </div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex == 'operation'">
              <div class="training-actions flex flex-wrap items-center gap-1">
                <a-button type="link" size="small" @click="openEdit(record)">
                  {{ $t("edit") }}
                </a-button>
                <a-popconfirm
                  :title="$t('confirm_delete_record')"
                  :ok-text="$t('yes')"
                  :cancel-text="$t('no')"
                  @confirm="deleteRecord(record)"
                >
                  <a-button type="link" size="small" danger>
                    {{ $t("delete") }}
                  </a-button>
                </a-popconfirm>
              </div>
            </template>
            <template v-else-if="column.dataIndex == 'points'">
              <span class="training-pill">{{ record.points }}</span>
            </template>
            <template v-else-if="column.dataIndex == 'member_count'">
              <span class="member-pill member-pill-blue">{{ record.member_count }}</span>
            </template>
            <template v-else-if="column.dataIndex == 'total_points'">
              <span class="text-base font-bold text-[#0081C8]">
                {{ record.total_points }}
              </span>
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>

          <!-- 展開：出席名單 -->
          <template #expandedRowRender="{ record }">
            <div class="space-y-2">
              <p class="m-0 text-xs font-semibold text-gray-500">
                {{ $t("attendance_list") }}（{{ record.member_count }}）
              </p>
              <div v-if="record.members.length" class="flex flex-wrap gap-1">
                <span
                  v-for="member in record.members"
                  :key="member.id"
                  class="member-pill member-pill-blue"
                >
                  {{ member.name }}
                  <span class="ml-1 opacity-60">{{ member.member_number }}</span>
                </span>
              </div>
              <p v-else class="m-0 text-sm text-gray-400">
                未記錄出席會員
              </p>
              <p v-if="record.remark" class="m-0 text-xs text-gray-500">
                {{ $t("remark") }}：{{ record.remark }}
              </p>
            </div>
          </template>
        </a-table>
      </div>
    </div>

    <!-- 新增／編輯集訓 -->
    <a-modal v-model:open="modal.isOpen" :title="modal.title" :width="720">
      <a-form layout="vertical">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <a-form-item :label="$t('training_date')" required>
            <a-date-picker
              v-model:value="modal.data.camp_date"
              :value-format="dateFormat"
              :format="dateFormat"
              style="width: 100%"
            />
          </a-form-item>
          <a-form-item :label="$t('points_per_attendance')" required>
            <a-input-number
              v-model:value="modal.data.points"
              :min="0"
              :precision="0"
              style="width: 100%"
            />
          </a-form-item>
        </div>

        <a-form-item :label="$t('title')" required>
          <a-input v-model:value="modal.data.title" />
        </a-form-item>

        <a-form-item :label="$t('remark')">
          <a-input v-model:value="modal.data.remark" />
        </a-form-item>

        <a-form-item>
          <template #label>
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span>{{ $t("attendance_list") }}</span>
              <span class="flex items-center gap-2">
                <button type="button" class="ja-link" @click="selectAllMembers">
                  全選
                </button>
                <span class="text-xs text-slate-300">|</span>
                <button type="button" class="ja-link ja-link-muted" @click="clearMembers">
                  全不選
                </button>
              </span>
            </div>
          </template>
          <a-select
            v-model:value="modal.data.member_ids"
            mode="multiple"
            :options="members"
            :placeholder="$t('member')"
            option-filter-prop="label"
            show-search
            allow-clear
            :max-tag-count="8"
            style="width: 100%"
          />
          <p class="m-0 mt-1 text-xs text-gray-400">
            已選 {{ modal.data.member_ids.length }} 位會員，出席每位可獲
            {{ modal.data.points || 0 }} 分
          </p>
        </a-form-item>
      </a-form>
      <template #footer>
        <a-button @click="modal.isOpen = false">{{ $t("cancel") }}</a-button>
        <a-button type="primary" :loading="saving" @click="submit">
          {{ modal.mode === "EDIT" ? $t("update") : $t("add") }}
        </a-button>
      </template>
    </a-modal>

    <!-- 匯入出席名單 -->
    <a-modal
      v-model:open="importModal.visible"
      :title="$t('training_camp_import')"
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
        <p class="ant-upload-text">點擊或拖曳檔案到這裡匯入集訓出席名單</p>
        <p class="ant-upload-hint">
          支援 .xlsx / .xls 檔案，一次一個。請先下載下方的出席名單範本填寫；
          同一日、同一名稱的列會歸入同一場集訓，會員可用會員號碼或姓名比對。
        </p>
      </a-upload-dragger>

      <div v-if="importModal.done" class="mt-3">
        <div
          v-if="importModal.errors.length === 0"
          class="my-3 font-bold text-green-600"
        >
          成功匯入 {{ importModal.count }} 筆出席記錄
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
            下載出席名單範本
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
  PlusOutlined,
  SearchOutlined,
  UploadOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";
import dayjs from "dayjs";

export default {
  components: {
    OrganizationLayout,
    DownloadOutlined,
    FileExcelOutlined,
    PlusOutlined,
    SearchOutlined,
    UploadOutlined,
    WarningOutlined,
  },
  props: ["camps", "members", "filters"],
  data() {
    return {
      breadcrumb: [
        { label: this.$t("members"), url: route("manage.members.index") },
        { label: this.$t("training_camps"), url: null },
      ],
      dateFormat: "YYYY-MM-DD",
      range: [this.filters.start, this.filters.end],
      keyword: this.filters.keyword || "",
      templateUrl: "/templates/training_camp_list.xlsx",
      saving: false,
      modal: {
        isOpen: false,
        mode: "CREATE",
        title: "",
        data: { camp_date: "", title: "", points: 10, remark: "", member_ids: [] },
      },
      importModal: {
        visible: false,
        files: [],
        done: false,
        count: 0,
        errors: [],
        loading: false,
      },
      columns: [
        {
          title: "date",
          dataIndex: "camp_date",
          i18n: "date",
          width: 130,
          sorter: (a, b) => String(a.camp_date || "").localeCompare(String(b.camp_date || "")),
        },
        {
          title: "title",
          dataIndex: "title",
          i18n: "training_camp",
          ellipsis: true,
        },
        {
          title: "points",
          dataIndex: "points",
          i18n: "points_per_attendance",
          width: 130,
        },
        {
          title: "attendance_count",
          dataIndex: "member_count",
          i18n: "attendance_count",
          width: 120,
          sorter: (a, b) => a.member_count - b.member_count,
        },
        {
          title: "total",
          dataIndex: "total_points",
          i18n: "total_score",
          width: 110,
          sorter: (a, b) => a.total_points - b.total_points,
        },
        {
          title: "operation",
          dataIndex: "operation",
          key: "operation",
          i18n: "operation",
          width: 160,
        },
      ],
      pagination: {
        defaultPageSize: 20,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
      },
    };
  },
  methods: {
    openCreate() {
      this.modal.mode = "CREATE";
      this.modal.title = this.$t("training_camp_create");
      this.modal.data = {
        camp_date: dayjs().format("YYYY-MM-DD"),
        title: "",
        points: 10,
        remark: "",
        member_ids: [],
      };
      this.modal.isOpen = true;
    },
    openEdit(record) {
      this.modal.mode = "EDIT";
      this.modal.title = this.$t("edit");
      this.modal.data = {
        id: record.id,
        camp_date: record.camp_date,
        title: record.title,
        points: record.points,
        remark: record.remark || "",
        member_ids: [...record.member_ids],
      };
      this.modal.isOpen = true;
    },
    selectAllMembers() {
      this.modal.data.member_ids = this.members.map((member) => member.value);
    },
    clearMembers() {
      this.modal.data.member_ids = [];
    },
    submit() {
      const data = this.modal.data;

      if (!data.camp_date) {
        message.warning("請選擇集訓日期");
        return;
      }

      if (!data.title || !data.title.trim()) {
        message.warning("請輸入集訓名稱");
        return;
      }

      const payload = {
        camp_date: data.camp_date,
        title: data.title.trim(),
        points: data.points || 0,
        remark: data.remark || null,
        member_ids: data.member_ids,
      };

      this.saving = true;

      const options = {
        preserveScroll: true,
        onSuccess: () => {
          this.modal.isOpen = false;
        },
        onError: (errors) => {
          const first = Object.values(errors || {})[0];
          message.error(Array.isArray(first) ? first[0] : "儲存失敗，請檢查資料");
        },
        onFinish: () => {
          this.saving = false;
        },
      };

      if (this.modal.mode === "EDIT") {
        this.$inertia.put(
          route("manage.trainingCamps.update", data.id),
          payload,
          options
        );
      } else {
        this.$inertia.post(route("manage.trainingCamps.store"), payload, options);
      }
    },
    deleteRecord(record) {
      this.$inertia.delete(route("manage.trainingCamps.destroy", record.id), {
        preserveScroll: true,
        onSuccess: () => message.success(this.$t("delete_success")),
        onError: () => message.error(this.$t("delete_failed")),
      });
    },
    applyFilters() {
      this.$inertia.get(
        route("manage.trainingCamps.index"),
        {
          start: this.range?.[0] || "",
          end: this.range?.[1] || "",
          keyword: this.keyword,
        },
        { preserveState: true, preserveScroll: true, replace: true }
      );
    },
    resetFilters() {
      const end = dayjs().format(this.dateFormat);
      this.range = [
        dayjs(end).subtract(2, "year").add(1, "day").format(this.dateFormat),
        end,
      ];
      this.keyword = "";
      this.applyFilters();
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
        .post(route("manage.trainingCamps.import"), formData, {
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

          message.success(`成功匯入 ${this.importModal.count} 筆出席記錄`);

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
  padding-top: 12px;
  padding-bottom: 12px;
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

.training-actions :deep(.ant-btn) {
  height: 28px;
  padding: 0 10px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  box-shadow: none;
}
.training-actions :deep(.ant-btn-link) {
  color: #0081c8;
}
.training-actions :deep(.ant-btn-link:hover) {
  color: #0b6ea8;
  background-color: rgba(0, 129, 200, 0.08);
}
.training-actions :deep(.ant-btn-link.ant-btn-dangerous) {
  color: #d4380d;
}
.training-actions :deep(.ant-btn-link.ant-btn-dangerous:hover) {
  color: #a8071a;
  background-color: rgba(212, 56, 13, 0.08);
}

.member-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 1px 10px;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
}
.member-pill-blue {
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
}
.training-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  background-color: #f1f5f9;
  padding: 1px 10px;
  font-size: 12px;
  font-weight: 600;
  line-height: 20px;
  color: #475569;
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
