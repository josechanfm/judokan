<template>
  <OrganizationLayout :title="$t('members')" :breadcrumb="breadcrumb">
    <!-- 工具列：統計 + 操作按鈕 -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <p class="m-0 text-sm text-gray-500">
        共
        <span class="mx-1 text-base font-semibold text-[#0081C8]">{{
          members.length
        }}</span>
        位會員
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <inertia-link
          :href="route('manage.members.create')"
          class="btn-judo-primary"
          as="button"
        >
          <PlusOutlined />
          {{ $t("create_member") }}
        </inertia-link>
        <button type="button" class="btn-judo-outline" @click="visible = true">
          <UploadOutlined />
          {{ $t("import_member") }}
        </button>
        <a
          :href="route('organization.member.export', organization.id)"
          class="btn-judo-outline"
          download
        >
          <DownloadOutlined />
          {{ $t("export_member") }}
        </a>
      </div>
    </div>

    <!-- 會員列表表格 -->
    <div class="card-judo">
      <div class="overflow-x-auto">
        <a-table
          :dataSource="members"
          :columns="columns"
          :pagination="pagination"
          :scroll="{ x: 1060 }"
          row-key="id"
        >
          <template #headerCell="{ column }">
            {{ column.i18n ? $t(column.i18n) : column.title }}
          </template>
          <template #emptyText>
            <div class="py-12 text-center text-gray-400">
              <p class="m-0 text-3xl">🥋</p>
              <p class="m-0 mt-3 text-sm">
                尚無會員資料，可點右上角「新增會員」或「導入會員」
              </p>
            </div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex == 'operation'">
              <div class="member-actions flex flex-wrap items-center gap-1">
                <inertia-link
                  :href="route('manage.members.show', record.id)"
                  class="ant-btn ant-btn-link ant-btn-sm"
                  as="button"
                >
                  {{ $t("view") }}
                </inertia-link>
                <inertia-link
                  :href="route('manage.members.edit', record.id)"
                  class="ant-btn ant-btn-link ant-btn-sm"
                  as="button"
                >
                  {{ $t("edit") }}
                </inertia-link>
                <a-popconfirm
                  :title="$t('confirm_delete_record')"
                  :ok-text="$t('yes')"
                  :cancel-text="$t('no')"
                  @confirm="deleteConfirmed(record.id)"
                >
                  <a-button type="link" size="small" danger>
                    {{ $t("delete") }}
                  </a-button>
                </a-popconfirm>
                <a-tooltip :title="record.email ? '' : '需先填寫電郵才能建立帳號'">
                  <a-button
                    type="link"
                    size="small"
                    :disabled="record.user != null || !record.email"
                    @click="createLogin(record.id)"
                  >
                    {{ $t("create_login") }}
                  </a-button>
                </a-tooltip>
              </div>
            </template>
            <template v-else-if="column.dataIndex == 'tier'">
              <span v-if="tierLabel(record)" class="member-pill member-pill-blue">
                {{ tierLabel(record) }}
              </span>
              <span v-else class="text-gray-300">—</span>
            </template>
            <template v-else-if="column.dataIndex == 'gender'">
              <span
                v-if="record.gender"
                class="member-pill"
                :class="record.gender === 'F' ? 'member-pill-pink' : 'member-pill-blue'"
              >
                {{ record.gender === "F" ? $t("female") : $t("male") }}
              </span>
              <span v-else class="text-gray-300">—</span>
            </template>
            <template v-else-if="column.dataIndex == 'avatar'">
              <img
                v-if="record.avatar_url"
                :src="record.avatar_url"
                :alt="record.display_name"
                class="member-avatar"
              />
              <span v-else class="member-avatar">{{ initial(record) }}</span>
            </template>
            <template v-else-if="column.dataIndex == 'dob'">
              {{ record.dob || "—" }}
            </template>
            <template v-else>
              {{ record[column.dataIndex] }}
            </template>
          </template>
        </a-table>
      </div>
    </div>

    <!-- 導入會員模態框 -->
    <a-modal
      :title="$t('import_member')"
      v-model:open="visible"
      :width="640"
      @cancel="closeImportModal"
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
        <p class="ant-upload-text">點擊或拖曳檔案到這裡導入會員</p>
        <p class="ant-upload-hint">
          支援 .xlsx / .xls檔案，一次一個。請先下載下方的會員名單模板填寫，
          匯入的會員會直接加入本屬會；電郵重複的列會被略過。
        </p>
      </a-upload-dragger>

      <div v-if="imported" class="mt-3">
        <div v-if="errors.length === 0" class="font-bold my-3 text-green-600">
          成功導入 {{ importedCount }} 筆會員資料
        </div>
        <div v-else class="font-bold my-3 text-yellow-600">
          成功導入 {{ importedCount }} 筆，以下 {{ errors.length }} 筆未導入
        </div>
        <p v-for="(error, index) in errors" :key="index" class="font-mono m-0">
          <WarningOutlined class="!text-yellow-500" />
          第 {{ error.row }} 行（{{ error.attribute }}）: {{ error.errors[0] }}
        </p>
      </div>

      <template #footer>
        <div class="flex w-full justify-between">
          <a :href="templateUrl" download>
            <a-button type="link">
              <template #icon>
                <DownloadOutlined />
              </template>
              下載會員名單模板（member_list.xlsx）
            </a-button>
          </a>
          <a-button
            type="primary"
            class="btn-judo-primary"
            @click="handleImport"
            :disabled="files.length === 0"
            :loading="importLoading"
          >
            導入
          </a-button>
        </div>
      </template>
    </a-modal>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { Modal as PopupModal } from "ant-design-vue";
import { defineComponent } from "vue";
import {
  DownloadOutlined,
  FileExcelOutlined,
  PlusOutlined,
  UploadOutlined,
  WarningOutlined,
} from "@ant-design/icons-vue";

export default {
  components: {
    OrganizationLayout,
    PopupModal,
    DownloadOutlined,
    FileExcelOutlined,
    PlusOutlined,
    UploadOutlined,
    WarningOutlined,
  },
  props: ["memberTiers", "members", "organization"],
  data() {
    return {
      breadcrumb: [
        {
          label: this.$t("home"),
          url: route("manage.organizations.show", this.organization.id),
        },
        { label: this.$t("members"), url: null },
      ],
      pagination: {
        defaultPageSize: 20,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
      },
      columns: [
        {
          title: "name_zh",
          dataIndex: "name_zh",
          i18n: "name_zh",
          width: 150,
          ellipsis: true,
          sorter: (a, b) =>
            String(a.name_zh || "").localeCompare(String(b.name_zh || ""), "zh-Hant"),
        },
        {
          title: "name_fn",
          dataIndex: "name_fn",
          i18n: "name_fn",
          width: 210,
          ellipsis: true,
          sorter: (a, b) =>
            String(a.name_fn || "").localeCompare(String(b.name_fn || "")),
        },
        {
          title: "gender",
          dataIndex: "gender",
          i18n: "gender",
          width: 100,
        },
        {
          title: "dob",
          dataIndex: "dob",
          i18n: "dob",
          width: 150,
          sorter: (a, b) => String(a.dob || "").localeCompare(String(b.dob || "")),
        },
        {
          title: "tier",
          dataIndex: "tier",
          i18n: "tier",
          width: 130,
          filters: (this.memberTiers || []).map((t) => ({
            value: t.value,
            text: (t.label || "").trim(),
          })),
          onFilter: (value, record) => record.current_tier?.tier_code === value,
        },
        {
          title: "avatar",
          dataIndex: "avatar",
          i18n: "avatar",
          width: 100,
        },
        {
          title: "Operation",
          dataIndex: "operation",
          key: "operation",
          i18n: "operation",
          width: 300,
        },
      ],
      visible: false,
      imported: false,
      importedCount: 0,
      errors: [],
      files: [],
      importLoading: false,
      templateUrl: "/templates/member_list.xlsx",
    };
  },
  methods: {
    deleteConfirmed(recordId) {
      PopupModal.confirm({
        title: this.$t("confirm_delete"),
        content: this.$t("confirm_delete_content"),
        okText: this.$t("yes"),
        cancelText: this.$t("no"),
        onOk: () => {
          this.$inertia.delete(route("manage.members.destroy", { member: recordId }), {
            onSuccess: () => {
              this.$message.success(this.$t("delete_success"));
            },
            onError: () => {
              this.$message.error(this.$t("delete_failed"));
            },
          });
        },
      });
    },
    createLogin(recordId) {
      PopupModal.confirm({
        title: this.$t("create_login_confirm"),
        content: this.$t("create_login_content"),
        okText: this.$t("yes"),
        cancelText: this.$t("no"),
        onOk: () => {
          axios
            .post(route("manage.member.createLogin", recordId))
            .then((response) => {
              if (response.data.result === false) {
                PopupModal.warning({
                  title: this.$t("create_login"),
                  content: response.data.message,
                });
              } else {
                this.$message.success(this.$t("create_login_success"));
              }
            })
            .catch((error) => {
              console.error(error);
              this.$message.error(this.$t("create_login_failed"));
            });
        },
      });
    },
    // 會員級別代號 → 級別名稱（例：TEAM_A → 代表隊A）
    tierLabel(record) {
      const code = record.current_tier?.tier_code;
      if (!code) {
        return "";
      }
      const tier = (this.memberTiers || []).find((t) => t.value === code);
      return (tier?.label || code).trim();
    },
    // 沒有頭像時用姓名第一個字當預設頭像
    initial(record) {
      const name = (record.name_zh || record.name_fn || "").trim();
      return name ? name.charAt(0) : "?";
    },
    closeImportModal() {
      this.visible = false;
      this.files = [];
      this.imported = false;
      this.importedCount = 0;
      this.errors = [];
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
        .post(route("organization.member.import", this.organization.id), formData, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(({ data }) => {
          this.importLoading = false;
          this.files = [];
          this.importedCount = data.imported || 0;
          this.errors = data.errors || [];
          this.$message.success(`成功導入 ${this.importedCount} 筆會員資料`);
          this.imported = true;

          // 刷新會員列表（preserveState 讓上面的匯入結果不會被清掉）
          this.$inertia.reload({ preserveState: true });
        })
        .catch((error) => {
          this.importLoading = false;
          console.error(error);
          this.$message.error("導入失敗");
        });
    },
  },
};
</script>

<style scoped>
/* ---------- 表格質感 ---------- */
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

/* ---------- 操作欄按鈕 ---------- */
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
.member-actions :deep(.ant-btn-link.ant-btn-dangerous) {
  color: #d4380d;
}
.member-actions :deep(.ant-btn-link.ant-btn-dangerous:hover) {
  color: #a8071a;
  background-color: rgba(212, 56, 13, 0.08);
}
.member-actions :deep(.ant-btn[disabled]) {
  color: #cbd5e1;
}
.member-actions :deep(.ant-btn[disabled]:hover) {
  background-color: transparent;
}

/* ---------- 性別／級別標籤、預設頭像 ---------- */
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
.member-pill-pink {
  background-color: rgba(219, 39, 119, 0.1);
  color: #be185d;
}
.member-avatar {
  display: inline-flex;
  height: 34px;
  width: 34px;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 9999px;
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
  font-size: 15px;
  font-weight: 600;
  object-fit: cover;
  vertical-align: middle;
}

/* ---------- 匯入視窗 ---------- */
:deep(.ant-upload.ant-upload-drag) {
  border-radius: 12px;
}
:deep(.ant-upload.ant-upload-drag:hover) {
  border-color: #0081c8;
}
:deep(.ant-upload-drag-icon .anticon) {
  color: rgba(0, 129, 200, 0.85);
}
</style>
