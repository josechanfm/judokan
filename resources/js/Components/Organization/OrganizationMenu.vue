<template>
  <div>
    <a-menu
      v-model:openKeys="openKeys"
      v-model:selectedKeys="selectedKeys"
      mode="inline"
      theme="light"
      :inline-collapsed="collapsed"
    >
      <a-menu-item key="">
        <template #icon>
          <PieChartOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage')">
            {{ $t("affiliate") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="members.index">
        <template #icon>
          <TeamOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.members.index')">
            {{ $t("member") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="certificates.index">
        <template #icon>
          <FileProtectOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.certificates.index')">
            {{ $t("certificates") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="forms.index">
        <template #icon>
          <FormOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.forms.index')">
            {{ $t("forms") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="competitions.index">
        <template #icon>
          <MergeCellsOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.competitions.index')">
            {{ $t("competitions") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="events.index">
        <template #icon>
          <CalendarOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.events.index')">
            {{ $t("events") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="articles.index">
        <template #icon>
          <CopyOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.articles.index')">
            {{ $t("articles") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="messages.index">
        <template #icon>
          <MailOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.messages.index')">
            {{ $t("messages") }}
          </inertia-link>
        </span>
      </a-menu-item>
      <a-menu-item key="exams.index">
        <template #icon>
          <MailOutlined />
        </template>
        <span>
          <inertia-link :href="route('manage.exams.index')">
            {{ $t("exams") }}
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
  TeamOutlined,
  FileProtectOutlined,
  FormOutlined,
  MergeCellsOutlined,
  CalendarOutlined,
  NotificationOutlined,
  MessageOutlined,
  CopyOutlined,
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
    TeamOutlined,
    FileProtectOutlined,
    FormOutlined,
    MergeCellsOutlined,
    CalendarOutlined,
    NotificationOutlined,
    MessageOutlined,
    CopyOutlined,
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
      openKeys: [],
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
  data() {
    return {
      openKeys: [],
      selectedKeys: [],
    };
  },
  mounted() {
    console.log(route().current().split(".").slice(1).join("."));
    this.openKeys.push(route().current().split(".").slice(1, 2).join("."));
    this.selectedKeys.push(route().current().split(".").slice(1).join("."));
  },
});
</script>
