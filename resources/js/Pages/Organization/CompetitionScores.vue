<template>
  <OrganizationLayout
    :title="$t('competition_score_categories')"
    :breadcrumb="breadcrumb"
  >
    <div class="container mx-auto pt-5">
      <!-- 說明 -->
      <div class="card-judo p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <h2 class="m-0 text-xl font-bold text-gray-800">
              {{ $t("competition_score_categories") }}
            </h2>
            <p class="m-0 mt-2 text-sm text-gray-500">
              各級賽事的名次積分。建立賽事時選擇積分表，賽果的積分就會依這裡的設定計算。
            </p>
            <p class="m-0 mt-1 text-xs text-gray-400">
              共 {{ competitionScores.length }} 個積分表 · 已使用
              {{ usedCount }} 個
            </p>
          </div>
          <button type="button" class="btn-judo-primary" @click="createRecord">
            <PlusOutlined />
            {{ $t("competition_score_new") }}
          </button>
        </div>
      </div>

      <!-- 類別篩選 -->
      <div class="card-judo mt-4 p-5">
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="ja-chip"
            :class="{ 'ja-chip-active': activeCategory === null }"
            @click="activeCategory = null"
          >
            {{ $t("all") }} ({{ competitionScores.length }})
          </button>
          <button
            v-for="category in categories"
            :key="category.value"
            type="button"
            class="ja-chip"
            :class="{ 'ja-chip-active': activeCategory === category.value }"
            @click="activeCategory = category.value"
          >
            {{ category.label }}
            <span class="opacity-60">({{ categoryCount(category.value) }})</span>
          </button>
        </div>
      </div>

      <!-- 積分表列表 -->
      <div class="card-judo mt-4 p-4">
        <a-table
          :dataSource="filteredScores"
          :columns="columns"
          :pagination="pagination"
          :scroll="{ x: 1080 }"
          row-key="id"
        >
          <template #headerCell="{ column }">
            <span class="whitespace-nowrap">
              <template v-if="column.i18n">{{ $t(column.i18n) }}</template>
              <template v-else-if="typeof column.title === 'string'">
                {{ column.title }}
              </template>
            </span>
          </template>
          <template #emptyText>
            <div class="py-12 text-center text-gray-400">
              <p class="m-0 text-3xl">🎯</p>
              <p class="m-0 mt-3 text-sm">{{ $t("no_competition_scores") }}</p>
            </div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex == 'title'">
              <div class="min-w-0">
                <p class="m-0 font-medium text-gray-800">{{ record.title }}</p>
                <p class="m-0 text-xs text-gray-400">
                  {{ $t("shared_scheme") }} · ID {{ record.id }}
                </p>
              </div>
            </template>
            <template v-else-if="column.dataIndex == 'category'">
              <span class="ja-pill ja-pill-gray">{{ categoryLabel(record.category) }}</span>
            </template>
            <template v-else-if="column.score">
              <span :class="column.dataIndex === 'first' ? 'ja-points-top' : 'ja-points'">
                {{ record[column.dataIndex] ?? 0 }}
              </span>
            </template>
            <template v-else-if="column.dataIndex == 'competitions_count'">
              <span
                class="ja-pill"
                :class="record.competitions_count ? 'ja-pill-blue' : 'ja-pill-gray'"
              >
                {{ record.competitions_count }} 場
              </span>
            </template>
            <template v-else-if="column.dataIndex == 'operation'">
              <div class="flex flex-wrap items-center gap-1">
                <a-button type="link" size="small" @click="editRecord(record)">
                  {{ $t("edit") }}
                </a-button>
                <a-popconfirm
                  :title="$t('confirm_delete_record')"
                  :ok-text="$t('confirm')"
                  :cancel-text="$t('cancel')"
                  @confirm="deleteRecord(record)"
                >
                  <a-button type="link" size="small" danger>
                    {{ $t("delete") }}
                  </a-button>
                </a-popconfirm>
              </div>
            </template>
            <template v-else>
              {{ record[column.dataIndex] ?? "—" }}
            </template>
          </template>
        </a-table>
      </div>
    </div>

    <!-- 新增／編輯積分表 -->
    <a-modal
      v-model:open="modal.isOpen"
      :title="modal.mode === 'CREATE' ? $t('competition_score_new') : $t('edit')"
      :width="720"
      :footer="null"
    >
      <a-form
        ref="formRef"
        :model="modal.data"
        :rules="rules"
        :label-col="{ span: 24 }"
        :wrapper-col="{ span: 24 }"
        autocomplete="off"
      >
        <a-form-item :label="$t('title')" name="title">
          <a-input
            v-model:value="modal.data.title"
            placeholder="例如：全澳柔道計分賽-二級賽事"
          />
        </a-form-item>

        <a-form-item :label="$t('score_category')" name="category">
          <a-select
            v-model:value="modal.data.category"
            :options="categories"
            :placeholder="$t('all')"
          />
        </a-form-item>

        <p class="m-0 mb-2 text-sm font-semibold text-gray-700">
          {{ $t("result_score") }}
        </p>
        <div class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4">
          <a-form-item
            v-for="field in rankFields"
            :key="field.key"
            :label="$t(field.i18n)"
            :label-col="{ span: 24 }"
            :wrapper-col="{ span: 24 }"
            class="mb-0"
          >
            <a-input-number
              v-model:value="modal.data[field.key]"
              :min="0"
              :max="100000"
              :precision="0"
              class="w-full"
            />
          </a-form-item>
        </div>

        <div v-if="modal.mode === 'EDIT' && modal.usage > 0" class="mt-5">
          <a-checkbox v-model:checked="syncResults">
            {{ $t("sync_results") }}（{{ modal.usage }} 場）
          </a-checkbox>
          <p class="m-0 mt-1 text-xs text-gray-400">
            {{ $t("sync_results_hint") }}
          </p>
        </div>
      </a-form>

      <div class="mt-6 flex justify-end gap-2">
        <button type="button" class="btn-judo-outline" @click="modal.isOpen = false">
          {{ $t("cancel") }}
        </button>
        <button
          type="button"
          class="btn-judo-primary"
          :disabled="saving"
          @click="submit"
        >
          {{ $t("save") }}
        </button>
      </div>
    </a-modal>
  </OrganizationLayout>
</template>

<script>
import OrganizationLayout from "@/Layouts/OrganizationLayout.vue";
import { PlusOutlined } from "@ant-design/icons-vue";

const RANK_FIELDS = [
  { key: "first", i18n: "competition_score_first" },
  { key: "second", i18n: "competition_score_second" },
  { key: "third", i18n: "competition_score_third" },
  { key: "fifth", i18n: "competition_score_fifth" },
  { key: "seventh", i18n: "competition_score_seventh" },
  { key: "advance", i18n: "competition_score_advance" },
  { key: "participate", i18n: "competition_score_participate" },
];

function emptyScore() {
  const data = { title: "", category: null };
  RANK_FIELDS.forEach((field) => {
    data[field.key] = 0;
  });

  return data;
}

export default {
  components: { OrganizationLayout, PlusOutlined },
  props: ["competitionScores", "competitionScoreCategories"],
  data() {
    return {
      activeCategory: null,
      modal: { isOpen: false, mode: "EDIT", data: emptyScore(), usage: 0 },
      syncResults: false,
      saving: false,
      rankFields: RANK_FIELDS,
      rules: {
        title: { required: true, message: "請輸入積分表名稱" },
        category: { required: true, message: "請選擇積分表類別" },
      },
      columns: [
        { title: "title", i18n: "scheme", dataIndex: "title", width: 320 },
        { title: "category", i18n: "score_category", dataIndex: "category", width: 120 },
        { title: "first", i18n: "competition_score_first", dataIndex: "first", score: true, width: 90 },
        { title: "second", i18n: "competition_score_second", dataIndex: "second", score: true, width: 90 },
        { title: "third", i18n: "competition_score_third", dataIndex: "third", score: true, width: 90 },
        { title: "fifth", i18n: "competition_score_fifth", dataIndex: "fifth", score: true, width: 90 },
        { title: "seventh", i18n: "competition_score_seventh", dataIndex: "seventh", score: true, width: 90 },
        { title: "advance", i18n: "competition_score_advance", dataIndex: "advance", score: true, width: 90 },
        { title: "participate", i18n: "competition_score_participate", dataIndex: "participate", score: true, width: 90 },
        { title: "usage", i18n: "usage_count", dataIndex: "competitions_count", width: 110 },
        { title: "operation", i18n: "operation", dataIndex: "operation", width: 140 },
      ],
      pagination: {
        defaultPageSize: 20,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "50", "100"],
        showTotal: (total) => `共 ${total} 個積分表`,
      },
    };
  },
  computed: {
    breadcrumb() {
      return [{ label: this.$t("competition_score_categories"), url: null }];
    },
    categories() {
      return this.competitionScoreCategories || [];
    },
    usedCount() {
      return (this.competitionScores || []).filter((item) => item.competitions_count > 0)
        .length;
    },
    filteredScores() {
      if (!this.activeCategory) {
        return this.competitionScores || [];
      }

      return (this.competitionScores || []).filter(
        (item) => item.category === this.activeCategory
      );
    },
  },
  methods: {
    categoryLabel(code) {
      const category = this.categories.find((item) => item.value === code);

      return category?.label || code || "—";
    },
    categoryCount(code) {
      return (this.competitionScores || []).filter((item) => item.category === code)
        .length;
    },
    createRecord() {
      this.syncResults = false;
      this.modal = { isOpen: true, mode: "CREATE", data: emptyScore(), usage: 0 };
    },
    editRecord(record) {
      const data = { ...emptyScore(), id: record.id };
      Object.keys(emptyScore()).forEach((key) => {
        if (record[key] !== undefined && record[key] !== null) {
          data[key] = record[key];
        }
      });
      this.syncResults = false;
      this.modal = {
        isOpen: true,
        mode: "EDIT",
        data,
        usage: record.competitions_count || 0,
      };
    },
    submit() {
      if (this.saving) {
        return;
      }

      const form = this.$refs.formRef;
      const validated = form && form.validate ? form.validate() : Promise.resolve();

      validated
        .then(() => {
          this.saving = true;

          const options = {
            preserveScroll: true,
            onSuccess: () => {
              this.saving = false;
              this.modal.isOpen = false;
              this.$message.success(
                this.modal.mode === "CREATE" ? "積分表已新增" : "積分表已更新"
              );
            },
            onError: (errors) => {
              this.saving = false;
              this.$message.error(Object.values(errors || {})[0] || "儲存失敗");
            },
            onFinish: () => {
              this.saving = false;
            },
          };

          if (this.modal.mode === "CREATE") {
            this.$inertia.post(
              route("manage.competitionScores.store"),
              this.modal.data,
              options
            );
            return;
          }

          this.$inertia.put(
            route("manage.competitionScores.update", this.modal.data.id),
            { ...this.modal.data, sync_results: this.syncResults || false },
            options
          );
        })
        .catch((error) => {
          this.saving = false;
          if (error && error.errorFields) {
            this.$message.warning("請先填寫必填欄位");
          } else if (error) {
            this.$message.error(String(error?.message || error));
          }
        });
    },
    deleteRecord(record) {
      this.$inertia.delete(route("manage.competitionScores.destroy", record.id), {
        preserveScroll: true,
        onSuccess: () => {
          this.$message.success("積分表已刪除");
        },
        onError: (errors) => {
          this.$message.error(Object.values(errors || {})[0] || "刪除失敗");
        },
      });
    },
  },
};
</script>

<style scoped>
.ja-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  border-radius: 9999px;
  border: 1px solid #e2e8f0;
  background-color: #fff;
  padding: 4px 14px;
  font-size: 13px;
  color: #475569;
  transition: all 0.15s ease-out;
}
.ja-chip:hover {
  border-color: rgba(0, 129, 200, 0.45);
  color: #0081c8;
}
.ja-chip-active {
  background-color: #0081c8;
  border-color: #0081c8;
  color: #fff;
}
.ja-pill {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  padding: 1px 10px;
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
  white-space: nowrap;
}
.ja-pill-blue {
  background-color: rgba(0, 129, 200, 0.1);
  color: #0081c8;
}
.ja-pill-gray {
  background-color: #f1f5f9;
  color: #475569;
}
.ja-points {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}
.ja-points-top {
  font-size: 13px;
  font-weight: 700;
  color: #0081c8;
}
:deep(.ant-table-thead > tr > th) {
  background-color: #f8fafc;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
}
:deep(.ant-table-tbody > tr:hover > td) {
  background-color: rgba(0, 129, 200, 0.04);
}
</style>
