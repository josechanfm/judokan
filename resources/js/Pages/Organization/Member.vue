<template>
  <OrganizationLayout
    :title="$t('view_member_profile')"
    :breadcrumb="breadcrumb"
  >
    <div class="container mx-auto pt-5">
      <!-- 會員識別卡 -->
      <div class="card-judo p-6">
        <div class="flex flex-wrap items-center gap-5">
          <img
            v-if="member.avatar_url"
            :src="member.avatar_url"
            :alt="member.display_name"
            class="member-avatar-lg"
          />
          <span v-else class="member-avatar-lg">{{ initial }}</span>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="m-0 text-xl font-bold text-gray-800">
                {{ member.display_name || member.name_zh || member.name_fn }}
              </h2>
              <span class="member-pill member-pill-blue">
                {{ $t("member_number") }} {{ member.member_number }}
              </span>
              <span class="member-pill" :class="expiryClass">{{ expiryLabel }}</span>
            </div>
            <p class="m-0 mt-1 text-sm text-gray-500">
              {{ member.name_zh || "—" }}
              <span v-if="member.name_fn">／{{ member.name_fn }}</span>
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <inertia-link
              :href="route('manage.members.index')"
              class="btn-judo-outline"
            >
              {{ $t("members") }}
            </inertia-link>
            <inertia-link
              :href="route('manage.members.edit', member.id)"
              class="btn-judo-primary"
              as="button"
            >
              <EditOutlined />
              {{ $t("edit_member") }}
            </inertia-link>
          </div>
        </div>
      </div>

      <!-- 基本資料 -->
      <div class="card-judo mt-5 p-6">
        <h3 class="m-0 mb-4 text-base font-semibold text-gray-700">
          {{ $t("profile") }}
        </h3>
        <dl
          class="grid grid-cols-1 gap-x-8 gap-y-1 lg:grid-cols-2 xl:grid-cols-3"
        >
          <div
            v-for="item in fields"
            :key="item.label"
            class="flex gap-3 border-b border-slate-100 py-2"
          >
            <dt class="w-28 shrink-0 text-sm text-gray-500">{{ item.label }}</dt>
            <dd class="m-0 min-w-0 flex-1 break-words text-sm text-gray-800">
              <span
                v-if="item.pill"
                class="member-pill"
                :class="item.pillClass"
              >
                {{ item.value }}
              </span>
              <template v-else>{{ item.value }}</template>
            </dd>
          </div>
        </dl>
      </div>

      <!-- 會員期限 -->
      <div class="card-judo mt-5 p-6">
        <h3 class="m-0 mb-4 text-base font-semibold text-gray-700">
          {{ $t("membership_period") }}
        </h3>
        <dl class="grid grid-cols-1 gap-x-8 gap-y-1 lg:grid-cols-3">
          <div class="flex gap-3 border-b border-slate-100 py-2">
            <dt class="w-24 shrink-0 text-sm text-gray-500">{{ $t("membership_status") }}</dt>
            <dd class="m-0 flex-1">
              <span class="member-pill" :class="expiryClass">{{ expiryLabel }}</span>
            </dd>
          </div>
          <div class="flex gap-3 border-b border-slate-100 py-2">
            <dt class="w-24 shrink-0 text-sm text-gray-500">{{ $t("valid_at") }}</dt>
            <dd class="m-0 flex-1 text-sm text-gray-800">{{ member.valid_at || "—" }}</dd>
          </div>
          <div class="flex gap-3 border-b border-slate-100 py-2">
            <dt class="w-24 shrink-0 text-sm text-gray-500">{{ $t("expired_at") }}</dt>
            <dd class="m-0 flex-1 text-sm text-gray-800">
              {{ member.expired_at || "—" }}
            </dd>
          </div>
        </dl>
        <p class="m-0 mt-3 text-xs text-gray-400">
          續期請到會員列表按「{{ $t("activate_membership") }}」，可選 1 個月／3 個月／半年／一年。
        </p>
      </div>
    </div>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { EditOutlined } from "@ant-design/icons-vue";
import dayjs from "dayjs";

export default {
  components: {
    OrganizationLayout,
    EditOutlined,
  },
  props: ["organization", "member"],
  computed: {
    breadcrumb() {
      return [
        { label: this.$t("members"), url: route("manage.members.index") },
        { label: this.$t("member"), url: null },
      ];
    },
    // 沒有頭像時用姓名第一個字
    initial() {
      const name = (this.member.name_zh || this.member.name_fn || "").trim();
      return name ? name.charAt(0) : "?";
    },
    createdDate() {
      return this.member.created_at
        ? dayjs(this.member.created_at).format("YYYY-MM-DD HH:mm")
        : "—";
    },
    genderLabel() {
      if (this.member.gender === "M") {
        return this.$t("male");
      }
      if (this.member.gender === "F") {
        return this.$t("female");
      }
      return "—";
    },
    daysLeft() {
      if (!this.member.expired_at) {
        return null;
      }

      return dayjs(this.member.expired_at)
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
    fields() {
      return [
        { label: this.$t("name_zh"), value: this.member.name_zh || "—" },
        { label: this.$t("name_fn"), value: this.member.name_fn || "—" },
        {
          label: this.$t("display_name"),
          value: this.member.display_name || "—",
        },
        {
          label: this.$t("gender"),
          value: this.genderLabel,
          pill: !!this.member.gender,
          pillClass:
            this.member.gender === "F" ? "member-pill-pink" : "member-pill-blue",
        },
        { label: this.$t("dob"), value: this.member.dob || "—" },
        { label: this.$t("email"), value: this.member.email || "—" },
        { label: this.$t("mobile"), value: this.member.mobile || "—" },
        { label: this.$t("nationality"), value: this.member.nationality || "—" },
        { label: this.$t("country"), value: this.member.country || "—" },
      ];
    },
  },
};
</script>

<style scoped>
.member-avatar-lg {
  display: inline-flex;
  height: 76px;
  width: 76px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  border-radius: 9999px;
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
  font-size: 30px;
  font-weight: 600;
  object-fit: cover;
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
.member-pill-pink {
  background-color: rgba(219, 39, 119, 0.1);
  color: #be185d;
}
.member-pill-red {
  background-color: rgba(212, 56, 13, 0.12);
  color: #c2410c;
}
.member-pill-green {
  background-color: rgba(34, 164, 93, 0.12);
  color: #1c8a4e;
}
.member-pill-gray {
  background-color: #f1f5f9;
  color: #64748b;
}
.member-pill-amber {
  background-color: rgba(217, 119, 6, 0.12);
  color: #b45309;
}
</style>
