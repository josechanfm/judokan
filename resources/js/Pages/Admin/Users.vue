<template>
  <AdminLayout title="Dashboard">
    <template #header>
      <div>
        <h1 class="text-2xl font-bold">{{ $t("users") }}</h1>
      </div>
    </template>

    <div class="p-6 min-h-screen">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("total_users") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ users.length }}
              </h3>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
              <TeamOutlined class="text-blue-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">
                {{ $t("active_users") }}
              </p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{ users.filter((user) => user.status).length }}
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
              <p class="text-gray-500 text-sm font-medium">{{ $t("total_roles") }}</p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{
                  new Set(users.flatMap((user) => user.roles.map((role) => role.id))).size
                }}
              </h3>
            </div>
            <div class="p-3 bg-orange-50 rounded-lg">
              <SafetyCertificateOutlined class="text-orange-500 text-xl" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-gray-500 text-sm font-medium">{{ $t("organizations") }}</p>
              <h3 class="text-2xl font-bold text-gray-800 mt-1">
                {{
                  new Set(
                    users.flatMap((user) => user.organizations.map((org) => org.id))
                  ).size
                }}
              </h3>
            </div>
            <div class="p-3 bg-purple-50 rounded-lg">
              <HomeOutlined class="text-purple-500 text-xl" />
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
          {{ $t("create_user") }}
        </a-button>
      </div>

      <!-- Users Table Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div
          class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-800">
              {{ $t("users_list") }}
            </h3>
            <p class="text-gray-500 text-sm mt-1">{{ $t("manage_all_users") }}</p>
          </div>
          <div
            class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto"
          >
            <a-input-search
              type="input"
              v-model:value="searchText"
              :placeholder="$t('search_users')"
              class="w-full sm:w-64"
              @search="handleSearch"
            />
            <a-button class="flex items-center justify-center">
              <FilterOutlined class="mr-2" />
              {{ $t("filter") }}
            </a-button>
          </div>
        </div>

        <!-- Table Container -->
        <div class="w-full overflow-x-auto">
          <div class="min-w-[900px]">
            <a-table
              :dataSource="filteredUsers"
              :columns="columns"
              :pagination="{
                pageSize: 10,
                showSizeChanger: true,
                showQuickJumper: true,
                showTotal: (total, range) =>
                  `${$t('showing')} ${range[0]}-${range[1]} ${$t('of')} ${total} ${$t(
                    'users'
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
                  <div class="flex flex-wrap gap-2 min-w-[180px]">
                    <a-tooltip :title="$t('edit_user')">
                      <a-button
                        @click="editRecord(record)"
                        class="flex items-center space-x-1 text-green-600 hover:text-green-800 border-green-200 hover:border-green-300 rounded-lg px-3 py-1 transition-colors duration-200"
                      >
                        <EditOutlined />
                        <span class="whitespace-nowrap">{{ $t("edit") }}</span>
                      </a-button>
                    </a-tooltip>

                    <a-tooltip :title="$t('delete_user')">
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

                <template v-else-if="column.dataIndex == 'organizations'">
                  <div class="max-w-xs">
                    <a-tag
                      v-for="org in record.organizations"
                      :key="org.id"
                      color="blue"
                      class="mb-1 rounded-full px-2 py-0.5 text-xs whitespace-nowrap"
                    >
                      {{ org.abbr }}
                    </a-tag>
                  </div>
                </template>

                <template v-else-if="column.dataIndex == 'roles'">
                  <div class="max-w-xs">
                    <a-tag
                      v-for="role in record.roles"
                      :key="role.id"
                      :color="getRoleColor(role.name)"
                      class="mb-1 rounded-full px-2 py-0.5 text-xs whitespace-nowrap"
                    >
                      {{ role.name }}
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

                <template v-else-if="column.dataIndex == 'name'">
                  <div class="flex items-center min-w-[150px]">
                    <div
                      class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 flex-shrink-0"
                    >
                      <UserOutlined class="text-blue-500 text-sm" />
                    </div>
                    <div>
                      <span class="font-medium text-gray-800 block">{{
                        record.name
                      }}</span>
                      <span class="text-gray-500 text-xs">{{ record.email }}</span>
                    </div>
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

    <!-- User Modal -->
    <a-modal
      v-model:open="modal.isOpen"
      :title="modal.title"
      width="800px"
      :confirm-loading="submitting"
      :after-close="resetForm"
      class="user-modal"
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
          <!-- Left Column -->
          <div class="space-y-4">
            <a-form-item :label="$t('username')" name="name">
              <a-input
                type="input"
                v-model:value="modal.data.name"
                :placeholder="$t('enter_username')"
              />
            </a-form-item>

            <a-form-item :label="$t('email')" name="email">
              <a-input
                type="input"
                v-model:value="modal.data.email"
                :placeholder="$t('enter_email')"
              />
            </a-form-item>

            <a-form-item
              :label="$t('password')"
              name="password"
              v-if="modal.mode === 'CREATE'"
            >
              <a-input-password
                type="input"
                v-model:value="modal.data.password"
                :placeholder="$t('enter_password')"
              />
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

          <!-- Right Column -->
          <div class="space-y-4">
            <a-form-item :label="$t('organizations')" name="organization_ids">
              <a-select
                v-model:value="modal.data.organization_ids"
                mode="multiple"
                :placeholder="$t('select_organizations')"
                show-search
                option-filter-prop="label"
                :max-tag-count="3"
              >
                <a-select-option
                  v-for="org in organizations"
                  :key="org.id"
                  :value="org.id"
                  :label="org.full_name"
                >
                  {{ org.full_name }} ({{ org.abbr }})
                </a-select-option>
              </a-select>
            </a-form-item>
          </div>
        </div>

        <!-- Roles and Permissions Section -->
        <div class="mt-6 border-t pt-6">
          <h4 class="text-lg font-semibold text-gray-800 mb-4">
            {{ $t("roles_and_permissions") }}
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Roles Section -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <h5 class="font-medium text-gray-700">{{ $t("roles") }}</h5>
                <span class="text-xs text-gray-500">
                  {{ modal.data.role_ids?.length || 0 }} {{ $t("selected") }}
                </span>
              </div>
              <div class="border border-gray-200 rounded-lg p-3 max-h-60 overflow-y-auto">
                <a-checkbox-group
                  v-model:value="modal.data.role_ids"
                  class="w-full space-y-2"
                >
                  <a-checkbox
                    v-for="role in roles"
                    :key="role.id"
                    :value="role.id"
                    class="w-full !ml-0"
                  >
                    <div
                      class="flex justify-between items-center w-full py-1 px-2 rounded hover:bg-gray-50"
                    >
                      <span class="text-sm">{{ role.name }}</span>
                      <a-tag :color="getRoleColor(role.name)" class="text-xs !m-0">
                        {{ role.name }}
                      </a-tag>
                    </div>
                  </a-checkbox>
                </a-checkbox-group>
              </div>
            </div>

            <!-- Permissions Section -->
            <div>
              <div class="flex items-center justify-between mb-3">
                <h5 class="font-medium text-gray-700">{{ $t("permissions") }}</h5>
                <span class="text-xs text-gray-500">
                  {{ modal.data.permission_ids?.length || 0 }} {{ $t("selected") }}
                </span>
              </div>
              <div class="border border-gray-200 rounded-lg p-3 max-h-60 overflow-y-auto">
                <a-checkbox-group
                  v-model:value="modal.data.permission_ids"
                  class="w-full space-y-2"
                >
                  <a-checkbox
                    v-for="permission in permissions"
                    :key="permission.id"
                    :value="permission.id"
                    class="w-full !ml-0"
                  >
                    <div class="py-1 px-2 rounded hover:bg-gray-50">
                      <span class="text-sm">{{ permission.name }}</span>
                    </div>
                  </a-checkbox>
                </a-checkbox-group>
              </div>
            </div>
          </div>
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
            {{
              submitting
                ? $t("saving")
                : modal.mode === "CREATE"
                ? $t("add")
                : $t("update")
            }}
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
  FilterOutlined,
  MailOutlined,
  UserOutlined,
  SafetyCertificateOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";

export default {
  components: {
    AdminLayout,
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    TeamOutlined,
    CheckCircleOutlined,
    HomeOutlined,
    FilterOutlined,
    MailOutlined,
    UserOutlined,
    SafetyCertificateOutlined,
  },
  props: ["organizations", "users", "roles", "permissions"],
  data() {
    return {
      searchText: "",
      modal: {
        isOpen: false,
        data: {
          name: "",
          email: "",
          password: "",
          status: true,
          organization_ids: [],
          role_ids: [],
          permission_ids: [],
        },
        title: "Modal",
        mode: "",
      },
      submitting: false,
      columns: [
        {
          title: "User",
          i18n: "user",
          dataIndex: "name",
          width: 200,
          fixed: "left",
        },
        {
          title: "Email",
          i18n: "email",
          dataIndex: "email",
          width: 180,
        },
        {
          title: "Organizations",
          i18n: "organizations",
          dataIndex: "organizations",
          width: 180,
        },
        {
          title: "Roles",
          i18n: "roles",
          dataIndex: "roles",
          width: 150,
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
          width: 180,
          fixed: "right",
        },
      ],
      rules: {
        name: [{ required: true, message: "Please input username!" }],
        email: [
          { required: true, message: "Please input email!" },
          { type: "email", message: "Please enter a valid email!" },
        ],
        password: [
          {
            required: true,
            message: "Please input password!",
            validator: this.validatePassword,
          },
        ],
        organization_ids: [
          { required: true, message: "Please select at least one organization!" },
        ],
        // 移除 role_ids 和 permission_ids 的必填驗證，因為它們是可選的
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
    filteredUsers() {
      if (!this.searchText) {
        return this.users;
      }
      const search = this.searchText.toLowerCase();
      return this.users.filter(
        (user) =>
          user.name?.toLowerCase().includes(search) ||
          user.email?.toLowerCase().includes(search) ||
          user.organizations?.some((org) => org.abbr?.toLowerCase().includes(search)) ||
          user.roles?.some((role) => role.name?.toLowerCase().includes(search))
      );
    },
  },
  methods: {
    // 自定義密碼驗證
    validatePassword(rule, value) {
      if (this.modal.mode === "CREATE" && !value) {
        return Promise.reject("Please input password!");
      }
      if (value && value.length < 6) {
        return Promise.reject("Password must be at least 6 characters!");
      }
      return Promise.resolve();
    },

    getRoleColor(roleName) {
      const colors = {
        admin: "red",
        manager: "blue",
        user: "green",
        editor: "orange",
        viewer: "purple",
        superadmin: "volcano",
        moderator: "cyan",
      };
      return colors[roleName?.toLowerCase()] || "default";
    },

    createRecord() {
      this.modal.data = {
        name: "",
        email: "",
        password: "",
        status: true,
        organization_ids: [],
        role_ids: [],
        permission_ids: [],
      };
      this.modal.mode = "CREATE";
      this.modal.title = this.$t("create_user") || "Create User";
      this.modal.isOpen = true;
    },

    editRecord(record) {
      console.log("Editing user:", record);
      this.modal.data = {
        id: record.id,
        name: record.name || "",
        email: record.email || "",
        status: record.status !== undefined ? record.status : true,
        organization_ids: record.organizations
          ? record.organizations.map((org) => org.id)
          : [],
        role_ids: record.roles ? record.roles.map((role) => role.id) : [],
        permission_ids: record.permissions
          ? record.permissions.map((perm) => perm.id)
          : [],
      };
      this.modal.mode = "EDIT";
      this.modal.title = this.$t("edit_user") || "Edit User";
      this.modal.isOpen = true;

      // 確保表單引用存在後重置驗證狀態
      this.$nextTick(() => {
        if (this.$refs.modalFormRef) {
          this.$refs.modalFormRef.clearValidate();
        }
      });
    },

    resetForm() {
      this.modal.data = {
        name: "",
        email: "",
        password: "",
        status: true,
        organization_ids: [],
        role_ids: [],
        permission_ids: [],
      };
      this.submitting = false;
      if (this.$refs.modalFormRef) {
        this.$refs.modalFormRef.resetFields();
      }
    },

    handleModalOk() {
      console.log("Modal OK clicked, mode:", this.modal.mode);
      console.log("Modal data:", this.modal.data);

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

        // 準備提交數據
        const submitData = {
          ...this.modal.data,
          // 確保數組字段不為 null
          organization_ids: this.modal.data.organization_ids || [],
          role_ids: this.modal.data.role_ids || [],
          permission_ids: this.modal.data.permission_ids || [],
        };

        console.log("Submitting data:", submitData);

        this.$inertia.post(route("admin.users.store"), submitData, {
          onSuccess: () => {
            this.modal.isOpen = false;
            message.success("User created successfully");
          },
          onError: (errors) => {
            console.error("Create errors:", errors);
            message.error("Failed to create user");
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

        // 驗證表單（編輯模式下密碼不是必須的）
        await this.$refs.modalFormRef.validateFields([
          "name",
          "email",
          "organization_ids",
        ]);

        // 準備提交數據，移除密碼字段如果為空
        const submitData = { ...this.modal.data };
        if (!submitData.password) {
          delete submitData.password;
        }

        // 確保數組字段不為 null
        submitData.organization_ids = submitData.organization_ids || [];
        submitData.role_ids = submitData.role_ids || [];
        submitData.permission_ids = submitData.permission_ids || [];

        console.log("Updating data:", submitData);

        this.$inertia.patch(route("admin.users.update", this.modal.data.id), submitData, {
          onSuccess: () => {
            this.modal.isOpen = false;
            message.success("User updated successfully");
          },
          onError: (errors) => {
            console.error("Update errors:", errors);
            message.error("Failed to update user");
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

    deleteRecord(record) {
      this.$inertia.delete(route("admin.users.destroy", record.id), {
        onSuccess: () => {
          message.success("User deleted successfully");
        },
        onError: (error) => {
          console.error("Delete error:", error);
          message.error("Failed to delete user");
        },
      });
    },

    handleSearch() {
      // Search functionality handled by computed property
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
.user-modal :deep(.ant-modal-header) {
  @apply border-b border-gray-200 pb-4 mb-4 rounded-t-lg;
}

.user-modal :deep(.ant-modal-title) {
  @apply text-xl font-semibold text-gray-800;
}

.user-modal :deep(.ant-form-item-label > label) {
  @apply font-medium text-gray-700;
}

.user-modal :deep(.ant-modal-content) {
  @apply rounded-xl;
}

.user-modal :deep(.ant-form-vertical .ant-form-item-label) {
  padding-bottom: 4px;
}

/* 角色和權限區域樣式 */
.user-modal :deep(.ant-checkbox-group) {
  width: 100%;
}

.user-modal :deep(.ant-checkbox-wrapper) {
  width: 100%;
  margin-left: 0;
  align-items: flex-start;
}

.user-modal :deep(.ant-checkbox) {
  margin-top: 4px;
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

  .user-modal :deep(.ant-modal) {
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
