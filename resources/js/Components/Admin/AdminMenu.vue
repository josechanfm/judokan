<template>
  <div>
    <a-menu
      v-model:openKeys="openKeys"
      v-model:selectedKeys="selectedKeys"
      mode="inline"
      theme="light"
      :inline-collapsed="collapsed"
    >
      <a-menu-item key="1">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.dashboard')">
            {{ $t("dashboard") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="2">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.organizations.index')">
            {{ $t("organization") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="3">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.members.index')">
            {{ $t("members") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="4">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('admin.users.index')">
            {{ $t("user") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="4">
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
import { defineComponent, reactive, toRefs, watch } from "vue";
import {
  MenuFoldOutlined,
  MenuUnfoldOutlined,
  PieChartOutlined,
  MailOutlined,
  DesktopOutlined,
  InboxOutlined,
  AppstoreOutlined,
} from "@ant-design/icons-vue";
export default defineComponent({
  components: {
    MenuFoldOutlined,
    MenuUnfoldOutlined,
    PieChartOutlined,
    MailOutlined,
    DesktopOutlined,
    InboxOutlined,
    AppstoreOutlined,
  },
  props: ["organization"],
  setup() {
    const state = reactive({
      collapsed: false,
      selectedKeys: ["1"],
      openKeys: ["sub1"],
      preOpenKeys: ["sub1"],
    });
    watch(
      () => state.openKeys,
      (_val, oldVal) => {
        state.preOpenKeys = oldVal;
      }
    );
    const toggleCollapsed = () => {
      state.collapsed = !state.collapsed;
      state.openKeys = state.collapsed ? [] : state.preOpenKeys;
      console.log(state.collapsed.value);
    };
    return {
      ...toRefs(state),
      toggleCollapsed,
    };
  },
});
</script>
