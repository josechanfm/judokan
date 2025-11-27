<template>
  <div>
    <a-menu
      v-model:openKeys="openKeys"
      :selected-keys="currentSelectedKeys"
      mode="inline"
      theme="light"
      :inline-collapsed="collapsed"
    >
      <a-menu-item key="dashboard">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.dashboard')">
            {{ $t("dashboard") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="organizations">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.organizations.index')">
            {{ $t("organization") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="members">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.members.index')">
            {{ $t("members") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="users">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.users.index')">
            {{ $t("user") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="configs">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.configs.index')">
            {{ $t("configs") }}
          </inertia-link>
        </span>
      </a-menu-item>
    </a-menu>
  </div>
</template>

<script>
import { defineComponent, reactive, toRefs, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { PieChartOutlined } from "@ant-design/icons-vue";

export default defineComponent({
  components: {
    PieChartOutlined,
  },
  props: ["organization"],
  setup() {
    const page = usePage();
    const state = reactive({
      collapsed: false,
      openKeys: [],
      preOpenKeys: [],
    });

    // 使用计算属性来动态确定选中的菜单项
    const currentSelectedKeys = computed(() => {
      try {
        // 多种方式获取当前路由
        const currentRoute = page.url || window.location.pathname || router.page.url;

        if (!currentRoute) {
          console.warn("无法获取当前路由信息");
          return [];
        }

        console.log("Current route:", page);

        // 根据当前路由匹配菜单项
        if (currentRoute == "/admin" || currentRoute.includes("admin.dashboard")) {
          return ["dashboard"];
        } else if (
          currentRoute == "/admin/organizations" ||
          currentRoute.includes("admin.organizations")
        ) {
          return ["organizations"];
        } else if (
          currentRoute == "/admin/members" ||
          currentRoute.includes("admin.members")
        ) {
          return ["members"];
        } else if (
          currentRoute == "/admin/users" ||
          currentRoute.includes("admin.users")
        ) {
          return ["users"];
        } else if (
          currentRoute == "/admin/configs" ||
          currentRoute.includes("admin.configs")
        ) {
          return ["configs"];
        }

        return [];
      } catch (error) {
        console.error("Error in currentSelectedKeys:", error);
        return [];
      }
    });

    const toggleCollapsed = () => {
      state.collapsed = !state.collapsed;
      state.openKeys = state.collapsed ? [] : state.preOpenKeys;
    };

    return {
      ...toRefs(state),
      currentSelectedKeys,
      toggleCollapsed,
    };
  },
});
</script>
