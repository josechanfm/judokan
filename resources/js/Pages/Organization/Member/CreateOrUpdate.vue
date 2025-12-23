<template>
  <OrganizationLayout :title="$t(title)" :breadcrumb="breadcrumb">
    <div class="container mx-auto pt-5">
      <div class="bg-white relative shadow rounded-lg overflow-x-auto p-6">
        <a-form
          ref="formRef"
          :model="form"
          name="memberForm"
          :label-col="{ span: 4 }"
          autocomplete="off"
          :rules="rules"
          :validate-messages="validateMessages"
        >
          <!-- 會員等級信息 -->
          <a-form-item v-if="isEditMode" :label="$t('tier')">
            {{ form.current_tier?.tier_code || "-" }}
          </a-form-item>
          <a-form-item
            v-if="isEditMode"
            :label="$t('valid_at')"
            :label-col="{ span: 12 }"
          >
            {{ form.current_tier?.valid_at || "-" }}
          </a-form-item>
          <a-form-item
            v-if="isEditMode"
            :label="$t('expired_at')"
            :label-col="{ span: 8 }"
          >
            {{ form.current_tier?.expired_at || "-" }}
          </a-form-item>

          <!-- 頭像顯示 -->
          <div class="flex justify-center mb-6">
            <img
              v-if="form.avatar_url"
              :src="form.avatar_url"
              width="200"
              alt="Avatar"
              class="rounded-lg"
            />
          </div>

          <!-- 基本資料 -->
          <a-form-item :label="$t('given_name')" name="given_name">
            <a-input
              v-model:value="form.given_name"
              :placeholder="$t('enter_given_name')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('family_name')" name="family_name">
            <a-input
              v-model:value="form.family_name"
              :placeholder="$t('enter_family_name')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('display_name')" name="display_name">
            <a-input
              v-model:value="form.display_name"
              :placeholder="$t('enter_display_name')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('gender')" name="gender">
            <a-radio-group v-model:value="form.gender" button-style="solid" size="large">
              <a-radio-button value="M">{{ $t("male") }}</a-radio-button>
              <a-radio-button value="F">{{ $t("female") }}</a-radio-button>
            </a-radio-group>
          </a-form-item>

          <a-form-item :label="$t('dob')" name="dob">
            <a-date-picker
              v-model:value="form.dob"
              :format="dateFormat"
              :valueFormat="dateFormat"
              style="width: 100%"
              :placeholder="$t('select_dob')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('email')" name="email">
            <a-input
              v-model:value="form.email"
              :placeholder="$t('enter_email')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('mobile_number')" name="mobile">
            <a-input
              v-model:value="form.mobile"
              :placeholder="$t('enter_mobile')"
              size="large"
            />
          </a-form-item>

          <!-- 會員等級管理 -->
          <div class="border-t pt-6 mt-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold">{{ $t("member_tiers") }}</h3>
              <a-button @click="addTier" type="primary" size="large">
                <PlusOutlined />
                {{ $t("add") }}{{ $t("tier") }}
              </a-button>
            </div>

            <!-- 會員等級表格 -->
            <div v-if="form.tiers && form.tiers.length > 0" class="ant-table">
              <div class="ant-table-container">
                <table style="table-layout: auto" class="w-full">
                  <thead class="ant-table-thead">
                    <tr>
                      <th class="p-3">{{ $t("tier") }}</th>
                      <th class="p-3">{{ $t("valid_at") }}</th>
                      <th class="p-3">{{ $t("expired_at") }}</th>
                      <th class="p-3">{{ $t("operation") }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(tier, idx) in form.tiers" :key="idx">
                      <td class="p-3">
                        <a-form-item
                          :name="'tier_code_' + (tier.id || idx)"
                          :rules="[
                            {
                              required: true,
                              message: $t('select_tier_required'),
                            },
                          ]"
                          class="mb-0"
                        >
                          <a-select
                            v-model:value="tier.tier_code"
                            :options="memberTiers"
                            :fieldNames="{ value: 'value', label: 'label' }"
                            style="width: 100%"
                            :placeholder="$t('select_tier')"
                          />
                        </a-form-item>
                      </td>
                      <td class="p-3">
                        <a-form-item
                          :name="'valid_at_' + (tier.id || idx)"
                          :rules="[
                            {
                              required: true,
                              message: $t('select_valid_at_required'),
                            },
                          ]"
                          class="mb-0"
                        >
                          <a-date-picker
                            v-model:value="tier.valid_at"
                            :format="dateFormat"
                            :valueFormat="dateFormat"
                            style="width: 100%"
                            :placeholder="$t('select_valid_at')"
                          />
                        </a-form-item>
                      </td>
                      <td class="p-3">
                        <a-form-item
                          :name="'expired_at_' + (tier.id || idx)"
                          class="mb-0"
                        >
                          <a-date-picker
                            v-model:value="tier.expired_at"
                            :format="dateFormat"
                            :valueFormat="dateFormat"
                            style="width: 100%"
                            :placeholder="$t('select_expired_at')"
                          />
                        </a-form-item>
                      </td>
                      <td class="p-3 text-center">
                        <a-popconfirm
                          :title="$t('confirm_delete_record')"
                          :ok-text="$t('yes')"
                          :cancel-text="$t('no')"
                          @confirm="deleteMemberTier(idx, tier)"
                        >
                          <a-button danger>
                            <DeleteOutlined />
                          </a-button>
                        </a-popconfirm>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-400">
              {{ $t("no_tiers_added") }}
            </div>
          </div>

          <!-- 表單操作按鈕 -->
          <div class="flex justify-end gap-3 pt-8 border-t">
            <inertia-link
              :href="route('manage.members.index')"
              class="ant-btn ant-btn-default"
              as="button"
              size="large"
            >
              {{ $t("cancel") }}
            </inertia-link>
            <a-button
              type="primary"
              size="large"
              @click="handleSubmit"
              :loading="loading"
            >
              {{ isEditMode ? $t("update") : $t("add") }}
            </a-button>
          </div>
        </a-form>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { PlusOutlined, DeleteOutlined } from "@ant-design/icons-vue";
import { defineComponent } from "vue";

export default {
  components: {
    OrganizationLayout,
    PlusOutlined,
    DeleteOutlined,
  },
  props: {
    memberTiers: {
      type: Array,
      required: true,
    },
    member: {
      type: Object,
      default: null,
    },
    errors: {
      type: Object,
      default: () => ({}),
    },
    organization: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      loading: false,
      dateFormat: "YYYY-MM-DD",
      form: this.getInitialForm(),
      rules: {
        given_name: { required: true, message: this.$t("given_name_required") },
        family_name: { required: true, message: this.$t("family_name_required") },
        gender: { required: true, message: this.$t("gender_required") },
        dob: { required: true, message: this.$t("dob_required") },
        email: {
          required: true,
          type: "email",
          message: this.$t("email_required"),
        },
        display_name: { required: true, message: this.$t("display_name_required") },
      },
      validateMessages: {
        required: "${label} " + this.$t("is_required"),
        types: {
          email: "${label} " + this.$t("is_not_valid_email"),
          number: "${label} " + this.$t("is_not_valid_number"),
        },
      },
    };
  },
  computed: {
    isEditMode() {
      return !!this.member?.id;
    },
    title() {
      return this.isEditMode ? "edit_member" : "create_member";
    },
    breadcrumb() {
      const breadcrumb = [
        {
          label: this.$t("home"),
          url: route("manage.organization.show", this.organization.id),
        },
        {
          label: this.$t("members"),
          url: route("manage.members.index"),
        },
        {
          label: this.isEditMode ? this.$t("edit_member") : this.$t("create_member"),
          url: null,
        },
      ];
      return breadcrumb;
    },
  },
  methods: {
    getInitialForm() {
      if (this.member) {
        return {
          ...this.member,
          tiers: this.member.tiers || [],
        };
      }
      return {
        given_name: "",
        family_name: "",
        display_name: "",
        gender: "M",
        dob: "",
        email: "",
        mobile: "",
        avatar_url: "",
        current_tier: {},
        tiers: [],
      };
    },
    handleSubmit() {
      this.$refs.formRef
        .validateFields()
        .then(() => {
          this.loading = true;

          if (this.isEditMode) {
            this.$inertia.patch(route("manage.members.update", this.form.id), this.form, {
              onSuccess: () => {
                this.loading = false;
                this.$message.success(this.$t("update_success"));
              },
              onError: (errors) => {
                this.loading = false;
                console.error(errors);
                this.$message.error(this.$t("update_failed"));
              },
            });
          } else {
            this.$inertia.post(route("manage.members.store"), this.form, {
              onSuccess: () => {
                this.loading = false;
                this.$message.success(this.$t("create_success"));
              },
              onError: (errors) => {
                this.loading = false;
                console.error(errors);
                this.$message.error(this.$t("create_failed"));
              },
            });
          }
        })
        .catch((err) => {
          console.log("Validation error:", err);
          this.loading = false;
        });
    },
    addTier() {
      if (!this.form.tiers) {
        this.form.tiers = [];
      }
      this.form.tiers.unshift({
        tier_code: null,
        valid_at: null,
        expired_at: null,
      });
    },
    deleteMemberTier(tierIdx, tier) {
      if (tier.id) {
        // 如果有ID，表示是從數據庫獲取的，需要調用API刪除
        axios
          .delete(route("manage.member.tier.destroy", tier.id))
          .then(() => {
            this.form.tiers.splice(tierIdx, 1);
            this.$message.success(this.$t("delete_success"));
          })
          .catch(() => {
            this.$message.error(this.$t("delete_failed"));
          });
      } else {
        // 如果沒有ID，表示是新增的，直接刪除本地數據
        this.form.tiers.splice(tierIdx, 1);
      }
    },
  },
  watch: {
    member: {
      handler(newValue) {
        this.form = this.getInitialForm();
      },
      deep: true,
    },
  },
};
</script>
