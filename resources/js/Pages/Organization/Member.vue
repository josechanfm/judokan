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
              <span v-if="currentTier" class="member-pill member-pill-amber">
                {{ currentTier }}
              </span>
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

      <!-- 會員級別 -->
      <div class="card-judo mt-5 p-6">
        <h3 class="m-0 mb-4 text-base font-semibold text-gray-700">
          {{ $t("member_tiers") }}
        </h3>
        <div v-if="tiers.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500">
                <th class="py-2 font-semibold">{{ $t("tier") }}</th>
                <th class="py-2 font-semibold">{{ $t("valid_at") }}</th>
                <th class="py-2 font-semibold">{{ $t("expired_at") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="tier in tiers"
                :key="tier.id"
                class="border-t border-slate-100"
              >
                <td class="py-2">
                  <span class="member-pill member-pill-blue">
                    {{ tierName(tier.tier_code) }}
                  </span>
                </td>
                <td class="py-2 text-gray-700">{{ tier.valid_at || "—" }}</td>
                <td class="py-2 text-gray-700">{{ tier.expired_at || "—" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="m-0 text-sm text-gray-400">
          {{ $t("no_tiers_added") }}
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
  props: ["organization", "member", "memberTiers"],
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
    tiers() {
      return this.member.tiers || [];
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
    // 目前生效的會員級別（Member 模型的 current_tier）
    currentTier() {
      return this.tierName(this.member.current_tier?.tier_code);
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
        { label: this.$t("address"), value: this.member.address || "—" },
        { label: this.$t("valid_at"), value: this.member.valid_at || "—" },
        { label: this.$t("expired_at"), value: this.member.expired_at || "—" },
        { label: this.$t("created_at"), value: this.createdDate },
      ];
    },
  },
  methods: {
    // 會員級別代號 → 級別名稱（例：TEAM_A → 代表隊A）
    tierName(code) {
      if (!code) {
        return "";
      }
      const tier = (this.memberTiers || []).find((t) => t.value === code);
      return (tier?.label || code).trim();
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
.member-pill-amber {
  background-color: rgba(217, 119, 6, 0.12);
  color: #b45309;
}
</style>
