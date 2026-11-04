<template>
  <OrganizationLayout :title="$t(title)" :breadcrumb="breadcrumb">
    <div class="container mx-auto pt-5">
      <div class="card-judo p-6">
        <a-form
          ref="formRef"
          :model="form"
          name="memberForm"
          layout="vertical"
          autocomplete="off"
          :rules="rules"
          :validate-messages="validateMessages"
        >
          <!-- 會員期限（唯讀，由會員列表的「激活會員」處理） -->
          <div v-if="isEditMode" class="mb-6 rounded-lg bg-slate-50 p-4">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
              <span class="text-gray-500">{{ $t("membership_period") }}：</span>
              <span class="member-pill" :class="expiryClass">{{ expiryLabel }}</span>
              <span class="text-gray-500">
                {{ $t("valid_at") }}：{{ form.valid_at || "—" }}
              </span>
              <span class="text-gray-500">
                {{ $t("expired_at") }}：{{ form.expired_at || "—" }}
              </span>
            </div>
            <p class="m-0 mt-2 text-xs text-gray-400">
              會員期限請在會員列表按「{{ $t("activate_membership") }}」選擇 1 個月／3
              個月／半年／一年續期。
            </p>
          </div>

          <!-- 頭像顯示 -->
          <div v-if="form.avatar_url" class="flex justify-center mb-6">
            <img
              v-if="form.avatar_url"
              :src="form.avatar_url"
              width="200"
              alt="Avatar"
              class="rounded-lg"
            />
          </div>

          <!-- 基本資料 -->
          <div class="grid grid-cols-1 gap-x-6 md:grid-cols-2">
            <a-form-item :label="$t('name_zh')" name="name_zh">
            <a-input
              v-model:value="form.name_zh"
              :placeholder="$t('enter_chinese_name')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('name_fn')" name="name_fn">
            <a-input
              v-model:value="form.name_fn"
              :placeholder="$t('enter_foreign_name')"
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

          <a-form-item :label="$t('mobile')" name="mobile">
            <a-input
              v-model:value="form.mobile"
              :placeholder="$t('enter_mobile')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('nationality')" name="nationality">
            <a-input
              v-model:value="form.nationality"
              :placeholder="$t('enter_nationality')"
              size="large"
            />
          </a-form-item>

          <a-form-item :label="$t('country')" name="country">
            <a-input
              v-model:value="form.country"
              :placeholder="$t('enter_country')"
              size="large"
            />
          </a-form-item>
          </div>

          <!-- 表單操作按鈕 -->
          <div class="mt-8 flex flex-wrap items-center justify-end gap-3 border-t pt-6">
            <inertia-link
              :href="route('manage.members.index')"
              class="btn-judo-outline"
              as="button"
            >
              {{ $t("cancel") }}
            </inertia-link>
            <button
              type="button"
              class="btn-judo-primary"
              :disabled="loading"
              @click="handleSubmit"
            >
              {{ loading ? "儲存中…" : isEditMode ? $t("update") : $t("add") }}
            </button>
          </div>
        </a-form>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { defineComponent } from "vue";
import dayjs from "dayjs";

export default {
  components: {
    OrganizationLayout,
  },
  props: {
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
        // 只有中文姓名必填，其餘欄位可留空（電郵填了仍會檢查格式）
        name_zh: { required: true, message: this.$t("name_zh_required") },
        email: { type: "email", message: this.$t("is_not_valid_email") },
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
          url: route("manage.organizations.show", this.organization.id),
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
    daysLeft() {
      if (!this.form.expired_at) {
        return null;
      }

      return dayjs(this.form.expired_at)
        .startOf("day")
        .diff(dayjs().startOf("day"), "day");
    },
    expiryLabel() {
      const days = this.daysLeft;

      if (days === null) {
        return "未設定期限";
      }

      if (days < 0) {
        return `已過期 ${Math.abs(days)} 天`;
      }

      if (days === 0) {
        return "今日到期";
      }

      return days <= 30 ? `${days} 天後到期` : "有效";
    },
    expiryClass() {
      const days = this.daysLeft;

      return {
        "member-pill-red": days !== null && days < 0,
        "member-pill-amber": days !== null && days >= 0 && days <= 30,
        "member-pill-green": days !== null && days > 30,
        "member-pill-gray": days === null,
      };
    },
  },
  methods: {
    getInitialForm() {
      if (this.member) {
        // 會員期限（valid_at / expired_at）由列表的「激活會員」處理，不在表單送出
        const { tiers, current_tier, ...member } = this.member;

        return { ...member };
      }

      return {
        name_zh: "",
        name_fn: "",
        display_name: "",
        gender: "M",
        dob: "",
        email: "",
        mobile: "",
        nationality: "",
        country: "",
        avatar_url: "",
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

<style scoped>
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
.member-pill-red {
  background-color: rgba(212, 56, 13, 0.12);
  color: #c2410c;
}
.member-pill-amber {
  background-color: rgba(217, 119, 6, 0.12);
  color: #b45309;
}
.member-pill-green {
  background-color: rgba(34, 164, 93, 0.12);
  color: #1c8a4e;
}
.member-pill-gray {
  background-color: #f1f5f9;
  color: #64748b;
}
</style>
