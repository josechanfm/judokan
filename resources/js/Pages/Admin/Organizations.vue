<template>
  <AdminLayout title="Dashboard">
    <template #header>
      <div>
        <h1 class="text-2xl font-bold">{{ $t("organizations") }}</h1>
      </div>
    </template>

    <div class="p-6 min-h-screen">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("total_organizations") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ organizations.length }}
              </h3>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
              <HomeOutlined class="text-blue-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("active_organizations") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ organizations.filter((org) => org.status).length }}
              </h3>
            </div>
            <div class="p-3 bg-green-50 rounded-lg">
              <CheckCircleOutlined class="text-green-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">{{ $t("regions") }}</p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ new Set(organizations.map((org) => org.region)).size }}
              </h3>
            </div>
            <div class="p-3 bg-orange-50 rounded-lg">
              <GlobalOutlined class="text-orange-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">{{ $t("managers") }}</p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{
                  new Set(
                    organizations.flatMap((org) => org.users.map((user) => user.id))
                  ).size
                }}
              </h3>
            </div>
            <div class="p-3 bg-purple-50 rounded-lg">
              <TeamOutlined class="text-purple-500 text-xl" />
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mb-6 flex justify-end">
        <a-button
          type="primary"
          @click="createRecord"
          class="flex items-center bg-blue-600 hover:bg-blue-700 border-blue-600 hover:border-blue-700"
        >
          <PlusOutlined class="mr-2" />
          {{ $t("create_organization") }}
        </a-button>
      </div>

      <!-- Organizations Table Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div
          class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800">
              {{ $t("organizations_list") }}
            </h3>
            <p class="text-gray-500 text-sm mt-1">{{ $t("manage_all_organizations") }}</p>
          </div>
          <div
            class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto"
          >
            <a-input-search
              type="input"
              v-model:value="searchText"
              :placeholder="$t('search_organizations')"
              class="w-full sm:w-64"
              @search="handleSearch"
            />
            <a-button class="flex items-center justify-center">
              <FilterOutlined class="mr-2" />
              {{ $t("filter") }}
            </a-button>
          </div>
        </div>

        <!-- 表格容器 -->
        <div class="w-full overflow-x-auto">
          <div class="min-w-[800px]">
            <a-table
              :dataSource="filteredOrganizations"
              :columns="columns"
              :pagination="{
                pageSize: 10,
                showSizeChanger: true,
                showQuickJumper: true,
                showTotal: (total, range) =>
                  `${$t('showing')} ${range[0]}-${range[1]} ${$t('of')} ${total} ${$t(
                    'organizations'
                  )}`,
              }"
              rowKey="id"
              class="custom-table w-full"
              :scroll="{ x: 'max-content' }"
            >
              <template #headerCell="{ column }">
                <span class="font-semibold text-gray-700 flex items-center">
                  {{ column.i18n ? $t(column.i18n) : column.title }}
                </span>
              </template>

              <template #bodyCell="{ column, text, record, index }">
                <template v-if="column.dataIndex == 'operation'">
                  <div class="flex flex-wrap gap-2 min-w-[200px]">
                    <a-tooltip :title="$t('view_members')">
                      <inertia-link
                        :href="route('admin.organization.members', record.id)"
                        class="ant-btn ant-btn-default flex border items-center space-x-1 text-blue-600 hover:text-blue-800 border-blue-200 hover:border-blue-300 rounded-lg px-3 py-1 transition-colors duration-200"
                      >
                        <TeamOutlined />
                        <span class="whitespace-nowrap">{{ $t("members") }}</span>
                      </inertia-link>
                    </a-tooltip>

                    <a-tooltip :title="$t('edit_organization')">
                      <a-button
                        @click="editRecord(record)"
                        class="flex items-center space-x-1 text-green-600 hover:text-green-800 border-green-200 hover:border-green-300 rounded-lg px-3 py-1 transition-colors duration-200"
                      >
                        <EditOutlined />
                        <span class="whitespace-nowrap">{{ $t("edit") }}</span>
                      </a-button>
                    </a-tooltip>

                    <a-tooltip :title="$t('delete_organization')">
                      <a-popconfirm
                        :title="$t('confirm_delete_record')"
                        :ok-text="$t('yes')"
                        :cancel-text="$t('no')"
                        @confirm="deleteRecord(record)"
                        okType="danger"
                        placement="topRight"
                      >
                        <a-button
                          class="flex items-center space-x-1 text-red-600 hover:text-red-800 border-red-200 hover:border-red-300 rounded-lg px-3 py-1 transition-colors duration-200"
                        >
                          <DeleteOutlined />
                          <span class="whitespace-nowrap">{{ $t("delete") }}</span>
                        </a-button>
                      </a-popconfirm>
                    </a-tooltip>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'region'">
                  <a-tag
                    :color="getRegionColor(record[column.dataIndex])"
                    class="rounded-full px-3 py-1 font-medium whitespace-nowrap"
                  >
                    {{
                      regions.find((z) => z.value == record[column.dataIndex])?.label ??
                      record[column.dataIndex]
                    }}
                  </a-tag>
                </template>

                <template v-else-if="column.dataIndex == 'manager'">
                  <div class="max-w-xs">
                    <a-tag
                      v-for="user in record['users']"
                      :key="user.id"
                      color="blue"
                      class="mb-1 rounded-full px-2 py-0.5 text-xs whitespace-nowrap"
                    >
                      {{ user.name }}
                    </a-tag>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'status'">
                  <a-badge
                    :status="record.status ? 'success' : 'error'"
                    :text="record.status ? $t('active') : $t('inactive')"
                    class="whitespace-nowrap"
                  />
                </template>

                <template v-else-if="column.dataIndex == 'email'">
                  <a
                    :href="`mailto:${record[column.dataIndex]}`"
                    class="text-blue-600 hover:text-blue-800 hover:underline flex items-center whitespace-nowrap"
                  >
                    <MailOutlined class="mr-1" />
                    {{ record[column.dataIndex] }}
                  </a>
                </template>

                <template v-else-if="column.dataIndex == 'full_name'">
                  <div class="flex items-center min-w-[150px]">
                    <div
                      class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 flex-shrink-0"
                    >
                      <HomeOutlined class="text-blue-500 text-xl" />
                    </div>
                    <span class="font-medium text-gray-800 truncate">{{
                      record[column.dataIndex]
                    }}</span>
                  </div>
                </template>

                <template v-else>
                  <span class="text-gray-700 whitespace-nowrap">{{
                    record[column.dataIndex]
                  }}</span>
                </template>
              </template>
            </a-table>
          </div>
        </div>
      </div>
    </div>

    <!-- Organization Modal -->
    <a-modal
      v-model:open="modal.isOpen"
      :title="modal.title"
      width="800px"
      :confirm-loading="submitting"
      :after-close="resetForm"
      class="organization-modal"
      @ok="handleModalOk"
      :destroyOnClose="true"
    >
      <a-form
        ref="modalFormRef"
        :model="modal.data"
        :rules="rules"
        :validate-messages="validateMessages"
        layout="vertical"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- 左側欄位 -->
          <div class="space-y-4">
            <a-form-item :label="$t('full_name')" name="full_name">
              <a-input
                type="input"
                v-model:value="modal.data.full_name"
                :placeholder="$t('enter_full_name')"
              />
            </a-form-item>

            <a-form-item :label="$t('abbreviation')" name="abbr">
              <a-input
                type="input"
                v-model:value="modal.data.abbr"
                :placeholder="$t('enter_abbreviation')"
              />
            </a-form-item>

            <a-form-item :label="$t('region')" name="region">
              <a-select
                v-model:value="modal.data.region"
                :placeholder="$t('select_region')"
                show-search
                option-filter-prop="label"
              >
                <a-select-option
                  v-for="region in regions"
                  :key="region.value"
                  :value="region.value"
                  :label="region.label"
                >
                  {{ region.label }}
                </a-select-option>
              </a-select>
            </a-form-item>

            <a-form-item :label="$t('status')" name="status">
              <a-select
                v-model:value="modal.data.status"
                :placeholder="$t('select_status')"
              >
                <a-select-option :value="true">
                  {{ $t("active") }}
                </a-select-option>
                <a-select-option :value="false">
                  {{ $t("inactive") }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </div>

          <!-- 右側欄位 -->
          <div class="space-y-4">
            <a-form-item :label="$t('email')" name="email">
              <a-input
                v-model:value="modal.data.email"
                :placeholder="$t('enter_email')"
                type="input"
              />
            </a-form-item>

            <a-form-item :label="$t('mobile')" name="mobile">
              <a-input
                type="input"
                v-model:value="modal.data.mobile"
                :placeholder="$t('enter_mobile')"
              />
            </a-form-item>

            <a-form-item :label="$t('managers')" name="user_ids">
              <a-select
                v-model:value="modal.data.user_ids"
                mode="multiple"
                :placeholder="$t('select_managers')"
                show-search
                option-filter-prop="label"
                :max-tag-count="3"
              >
                <a-select-option
                  v-for="user in users"
                  :key="user.id"
                  :value="user.id"
                  :label="user.name"
                >
                  {{ user.name }} ({{ user.email }})
                </a-select-option>
              </a-select>
            </a-form-item>
          </div>
        </div>

        <!-- 全寬欄位 -->
        <div class="mt-4">
          <a-form-item :label="$t('description')" name="description">
            <a-textarea
              v-model:value="modal.data.description"
              :placeholder="$t('enter_description')"
              :rows="3"
              show-count
              :maxlength="500"
            />
          </a-form-item>
        </div>
      </a-form>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <a-button @click="modal.isOpen = false">
            {{ $t("cancel") }}
          </a-button>
          <a-button
            type="primary"
            :loading="submitting"
            @click="handleModalOk"
            class="bg-blue-600 hover:bg-blue-700 border-blue-600 hover:border-blue-700"
          >
            {{ submitting ? $t("saving") : $t("save") }}
          </a-button>
        </div>
      </template>
    </a-modal>
  </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  TeamOutlined,
  CheckCircleOutlined,
  HomeOutlined,
  GlobalOutlined,
  FilterOutlined,
  MailOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";

export default {
  components: {
    AdminLayout,
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    TeamOutlined,
    HomeOutlined,
    CheckCircleOutlined,
    GlobalOutlined,
    FilterOutlined,
    MailOutlined,
  },
  props: ["regions", "organizations", "users"],
  data() {
    return {
      searchText: "",
      modal: {
        isOpen: false,
        data: {},
        title: "Modal",
        mode: "",
      },
      submitting: false,
      columns: [
        {
          title: "Full name",
          i18n: "full_name",
          dataIndex: "full_name",
          width: 200,
          fixed: "left",
        },
        {
          title: "Region",
          i18n: "region",
          dataIndex: "region",
          width: 120,
        },
        {
          title: "Abbreviation",
          i18n: "abbreviation",
          dataIndex: "abbr",
          width: 120,
        },
        {
          title: "Email",
          i18n: "email",
          dataIndex: "email",
          width: 180,
        },
        {
          title: "Manager",
          i18n: "manager",
          dataIndex: "manager",
          width: 180,
        },
        {
          title: "Status",
          i18n: "status",
          dataIndex: "status",
          width: 100,
        },
        {
          title: "Actions",
          i18n: "actions",
          dataIndex: "operation",
          key: "operation",
          width: 250,
          fixed: "right",
        },
      ],
      rules: {
        full_name: [{ required: true, message: "Please input full name!" }],
      },
      validateMessages: {
        required: "${label} is required!",
        types: {
          email: "${label} is not a valid email!",
          number: "${label} is not a valid number!",
        },
      },
    };
  },
  computed: {
    filteredOrganizations() {
      if (!this.searchText) {
        return this.organizations;
      }
      const search = this.searchText.toLowerCase();
      return this.organizations.filter(
        (org) =>
          org.full_name?.toLowerCase().includes(search) ||
          org.abbr?.toLowerCase().includes(search) ||
          org.email?.toLowerCase().includes(search) ||
          org.region?.toLowerCase().includes(search)
      );
    },
  },
  methods: {
    getRegionColor(region) {
      const colors = {
        APAC: "blue",
        EMEA: "green",
        AMER: "orange",
        Global: "purple",
      };
      return colors[region] || "default";
    },

    createRecord() {
      console.log("Creating new record");
      this.modal.data = {
        status: true,
        user_ids: [],
      };
      this.modal.mode = "CREATE";
      this.modal.title = this.$t("create_organization") || "Create Organization";
      this.modal.isOpen = true;
      console.log("Modal state:", this.modal);
    },

    editRecord(record) {
      console.log("Editing record:", record);

      // 深拷貝記錄數據
      this.modal.data = {
        id: record.id,
        full_name: record.full_name,
        abbr: record.abbr,
        region: record.region,
        email: record.email,
        mobile: record.mobile,
        description: record.description,
        status: record.status,
        user_ids: record.users ? record.users.map((user) => user.id) : [],
      };

      this.modal.mode = "EDIT";
      this.modal.title = this.$t("edit_organization") || "Edit Organization";
      this.modal.isOpen = true;

      console.log("Modal data after edit:", this.modal.data);
      console.log("Modal state after edit:", this.modal);

      // 確保表單引用存在後重置驗證狀態
      this.$nextTick(() => {
        if (this.$refs.modalFormRef) {
          this.$refs.modalFormRef.clearValidate();
        }
      });
    },

    resetForm() {
      this.modal.data = {};
      this.submitting = false;
      // 重置表單驗證
      if (this.$refs.modalFormRef) {
        this.$refs.modalFormRef.resetFields();
      }
    },

    handleModalOk() {
      console.log("Modal OK clicked, mode:", this.modal.mode);
      if (this.modal.mode === "CREATE") {
        this.storeRecord();
      } else {
        this.updateRecord();
      }
    },

    async storeRecord() {
      try {
        this.submitting = true;

        // 驗證表單
        await this.$refs.modalFormRef.validate();

        this.$inertia.post(route("admin.organizations.store"), this.modal.data, {
          onSuccess: () => {
            this.modal.isOpen = false;
            message.success("Organization created successfully");
          },
          onError: (errors) => {
            console.error("Create errors:", errors);
            message.error("Failed to create organization");
          },
          onFinish: () => {
            this.submitting = false;
          },
        });
      } catch (error) {
        console.error("Validation error:", error);
        this.submitting = false;
        message.error("Please check the form for errors");
      }
    },

    async updateRecord() {
      try {
        this.submitting = true;

        // 驗證表單
        await this.$refs.modalFormRef.validate();

        this.$inertia.patch(
          route("admin.organizations.update", this.modal.data.id),
          this.modal.data,
          {
            onSuccess: () => {
              this.modal.isOpen = false;
              message.success("Organization updated successfully");
            },
            onError: (errors) => {
              console.error("Update errors:", errors);
              message.error("Failed to update organization");
            },
            onFinish: () => {
              this.submitting = false;
            },
          }
        );
      } catch (error) {
        console.error("Validation error:", error);
        this.submitting = false;
        message.error("Please check the form for errors");
      }
    },

    deleteRecord(record) {
      this.$inertia.delete(route("admin.organizations.destroy", record.id), {
        onSuccess: () => {
          message.success("Organization deleted successfully");
        },
        onError: (error) => {
          console.error("Delete error:", error);
          message.error("Failed to delete organization");
        },
      });
    },

    handleSearch() {
      // 搜索功能已經通過 computed property 實現
    },
  },
};
</script>

<style scoped>
/* 確保表格容器可以水平滾動 */
.overflow-x-auto {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* 自定義表格樣式 */
.custom-table :deep(.ant-table) {
  min-width: 100%;
}

.custom-table :deep(.ant-table-container) {
  overflow: auto;
}

.custom-table :deep(.ant-table-thead > tr > th) {
  @apply bg-gray-50 text-gray-700 font-semibold border-b border-gray-200 whitespace-nowrap;
}

.custom-table :deep(.ant-table-tbody > tr > td) {
  @apply whitespace-nowrap;
}

.custom-table :deep(.ant-table-tbody > tr:hover > td) {
  @apply bg-blue-50;
}

/* 模態框樣式優化 */
.organization-modal :deep(.ant-modal-header) {
  @apply border-b border-gray-200 pb-4 mb-4 rounded-t-lg;
}

.organization-modal :deep(.ant-modal-title) {
  @apply text-xl font-semibold text-gray-800;
}

.organization-modal :deep(.ant-form-item-label > label) {
  @apply font-medium text-gray-700;
}

.organization-modal :deep(.ant-modal-content) {
  @apply rounded-xl;
}

.organization-modal :deep(.ant-form-vertical .ant-form-item-label) {
  padding-bottom: 4px;
}

/* 響應式設計 */
@media (max-width: 768px) {
  .custom-table :deep(.ant-table) {
    font-size: 14px;
  }

  .custom-table :deep(.ant-table-thead > tr > th),
  .custom-table :deep(.ant-table-tbody > tr > td) {
    padding: 8px 12px;
  }

  .organization-modal :deep(.ant-modal) {
    margin: 20px;
    width: auto !important;
  }
}

/* 按鈕懸停效果 */
.ant-btn:hover {
  transform: translateY(-1px);
  transition: all 0.2s ease;
}
</style>
