<template>
  <WebLayout title="比賽報名">
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">比賽報名表</h2>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- 賽事橫幅 -->
        <div
          v-if="competition.media.find((m) => m.collection_name == 'competitionBanner')"
          class="mb-6 overflow-hidden rounded-xl shadow-lg"
        >
          <img
            :src="
              competition.media.find((m) => m.collection_name == 'competitionBanner')
                .original_url
            "
            alt="賽事橫幅"
            class="w-full h-48 object-cover"
          />
        </div>

        <!-- 主要內容卡片 -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- 賽事標題和費用 -->
          <div class="p-6 border-b border-gray-200">
            <a-typography-title :level="2" class="text-center text-primary-600 mb-2">
              {{ competition.title_zh }}
            </a-typography-title>

            <div class="flex flex-wrap items-center justify-center gap-4 mb-4">
              <div
                class="flex items-center bg-blue-50 text-blue-700 px-4 py-2 rounded-lg"
              >
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span class="font-semibold">報名費用：</span>
                <span class="ml-1">MOP${{ competition.fee }}</span>
              </div>

              <div
                v-if="competition.start_date"
                class="flex items-center bg-green-50 text-green-700 px-4 py-2 rounded-lg"
              >
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span>比賽日期：{{ formatDate(competition.match_dates) }}</span>
              </div>
            </div>
          </div>

          <!-- 賽事簡介 -->
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
              <svg
                class="w-5 h-5 mr-2 text-primary-500"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                  clip-rule="evenodd"
                />
              </svg>
              賽事簡介
            </h3>
            <div id="pure-html" class="prose max-w-none">
              <div v-html="competition.description" />
            </div>

            <!-- 附件下載 -->
            <div
              v-if="
                competition.media.filter(
                  (m) => m.collection_name == 'competitionAttachment'
                ).length > 0
              "
              class="mt-6 p-4 bg-gray-50 rounded-lg"
            >
              <h4 class="text-md font-semibold text-gray-700 mb-2">相關文件下載</h4>
              <ul class="space-y-2">
                <li
                  v-for="file in competition.media.filter(
                    (m) => m.collection_name == 'competitionAttachment'
                  )"
                  :key="file.id"
                  class="flex items-center"
                >
                  <svg
                    class="w-5 h-5 mr-2 text-gray-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <a
                    :href="file.original_url"
                    target="_blank"
                    download
                    class="text-blue-600 hover:text-blue-800 hover:underline"
                  >
                    {{ file.file_name }}
                  </a>
                </li>
              </ul>
            </div>
          </div>

          <!-- 報名表格 -->
          <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
              <svg
                class="w-5 h-5 mr-2 text-primary-500"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                  clip-rule="evenodd"
                />
              </svg>
              報名資料填寫
            </h3>

            <a-form
              ref="formRef"
              :model="application"
              v-bind="layout"
              name="competition-application"
              :validate-messages="validateMessages"
              layout="vertical"
              @finish="onFinish"
              class="space-y-8"
              :rules="formRules"
            >
              <!-- 基本資料區塊 -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h4 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                  <svg
                    class="w-4 h-4 mr-2 text-gray-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  基本資料
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- 組織/學校 -->
                  <a-form-item
                    v-if="member == null"
                    :label="$t('organization_or_school')"
                    name="organization_id"
                    class="mb-0"
                    :rules="[{ required: true, message: '請選擇所屬組織或學校' }]"
                  >
                    <a-select
                      v-model:value="application.organization_id"
                      :options="organizations"
                      :field-names="{ value: 'id', label: 'title' }"
                      placeholder="請選擇所屬組織或學校"
                      class="w-full"
                      :status="organizationError ? 'error' : undefined"
                      @change="handleOrganizationChange"
                    />
                  </a-form-item>

                  <!-- 中文姓名 -->
                  <a-form-item :label="$t('name_zh')" name="name_zh" class="mb-0">
                    <a-input
                      v-model:value="application.name_zh"
                      placeholder="請輸入中文姓名"
                      @input="handleNameZhInput"
                      allow-clear
                      size="large"
                    />
                  </a-form-item>

                  <!-- 英文姓名 -->
                  <a-form-item
                    :label="$t('name_fn')"
                    name="name_fn"
                    class="mb-0"
                    :rules="[{ required: true, message: '請輸入英文姓名' }]"
                  >
                    <a-input
                      v-model:value="application.name_fn"
                      placeholder="請輸入英文姓名"
                      @input="handleNameFnInput"
                      allow-clear
                      size="large"
                    />
                  </a-form-item>

                  <!-- 身份證號碼 -->
                  <a-form-item
                    :label="$t('id_num')"
                    name="id_num"
                    class="mb-0"
                    :rules="[{ required: true, message: '請輸入身份證號碼' }]"
                  >
                    <a-input
                      v-model:value="application.id_num"
                      placeholder="請輸入身份證號碼"
                      allow-clear
                      size="large"
                    />
                  </a-form-item>

                  <!-- 出生日期 -->
                  <a-form-item
                    :label="$t('dob')"
                    name="dob"
                    class="mb-0"
                    :rules="[{ required: true, message: '請選擇出生日期' }]"
                  >
                    <a-date-picker
                      v-model:value="application.dob"
                      :format="dateFormat"
                      :valueFormat="dateFormat"
                      :disabledDate="disabledDate"
                      @change="onDobChange"
                      placeholder="請選擇出生日期"
                      class="w-full"
                      size="large"
                    />
                  </a-form-item>

                  <a-form-item
                    :label="$t('gender')"
                    name="gender"
                    class="mb-0"
                    :rules="[{ required: true, message: '請選擇性別' }]"
                  >
                    <a-radio-group
                      v-model:value="application.gender"
                      @change="onGenderChange"
                      button-style="solid"
                      class="w-full"
                    >
                      <a-radio-button value="M" class="w-1/2 text-center">{{
                        $t("male")
                      }}</a-radio-button>
                      <a-radio-button value="F" class="w-1/2 text-center">{{
                        $t("female")
                      }}</a-radio-button>
                    </a-radio-group>
                  </a-form-item>

                  <!-- 帶位 -->
                  <a-form-item :label="$t('belt_rank')" name="belt_rank" class="mb-0">
                    <a-select
                      v-model:value="application.belt_rank"
                      :options="belt_ranks"
                      :field-names="{ value: 'rankCode', label: 'name_zh' }"
                      placeholder="請選擇帶別"
                      class="w-full"
                      size="large"
                    />
                  </a-form-item>

                  <!-- 年齡顯示 -->
                  <div v-if="application.age" class="md:col-span-2">
                    <div
                      class="flex flex-wrap items-center gap-4 p-4 bg-blue-50 rounded-lg"
                    >
                      <div class="flex items-center">
                        <span class="font-medium text-gray-700">年齡：</span>
                        <span class="ml-2 text-lg font-bold text-blue-600"
                          >{{ application.age }} 歲</span
                        >
                      </div>
                      <div class="text-gray-600 text-sm">
                        (以比賽開始日期 {{ competitionStartYear }} 年計算)
                      </div>
                      <div
                        v-if="eligibleCategories.length > 0"
                        class="flex items-center text-green-600"
                      >
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path
                            fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        符合 {{ eligibleCategories.length }} 個組別
                      </div>
                      <div
                        v-else-if="application.dob"
                        class="flex items-center text-red-600"
                      >
                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        不符合任何組別的年齡要求
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 聯絡資料區塊 -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h4 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                  <svg
                    class="w-4 h-4 mr-2 text-gray-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"
                    />
                  </svg>
                  聯絡資料
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <a-form-item
                    :label="$t('email')"
                    name="email"
                    class="mb-0"
                    :rules="[
                      { required: true, message: '請輸入電郵地址' },
                      { type: 'email', message: '請輸入有效的電郵地址' },
                    ]"
                  >
                    <a-input
                      v-model:value="application.email"
                      placeholder="請輸入電郵地址"
                      type="email"
                      allow-clear
                      size="large"
                    />
                  </a-form-item>

                  <!-- 電話號碼 -->
                  <a-form-item
                    :label="$t('mobile_number')"
                    name="mobile"
                    class="mb-0"
                    :rules="[
                      { required: true, message: '請輸入電話號碼' },
                      { pattern: /^[0-9+\-\s()]+$/, message: '請輸入有效的電話號碼' },
                    ]"
                  >
                    <a-input
                      v-model:value="application.mobile"
                      placeholder="請輸入電話號碼"
                      allow-clear
                      size="large"
                    />
                  </a-form-item>
                </div>
              </div>

              <!-- 參賽角色區塊 - 只有在填寫了出生日期和性別後才顯示 -->
              <div
                v-if="application.dob && application.gender"
                class="bg-gray-50 p-6 rounded-lg"
              >
                <h4 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                  <svg
                    class="w-4 h-4 mr-2 text-gray-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M6.625 2.655A9 9 0 0119 11a1 1 0 11-2 0 7 7 0 00-9.625-6.492 1 1 0 11-.75-1.853zM4.662 4.959A1 1 0 014.75 6.37 6.97 6.97 0 003 11a1 1 0 11-2 0 8.97 8.97 0 012.25-5.953 1 1 0 011.412-.088z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  參賽角色
                </h4>

                <a-form-item
                  :label="$t('role')"
                  name="role"
                  :rules="[{ required: true, message: '請選擇參賽角色' }]"
                >
                  <a-radio-group
                    v-model:value="application.role"
                    class="w-full"
                    @change="onRoleChange"
                  >
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                      <a-radio
                        v-for="role in competition.roles"
                        :value="role.value"
                        class="role-radio"
                      >
                        <div class="role-card">
                          <div class="role-icon mb-2">
                            <svg
                              class="w-8 h-8 mx-auto"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path
                                v-if="role.value === 'athlete'"
                                d="M10 12a2 2 0 100-4 2 2 0 000 4z"
                              />
                              <path
                                v-if="role.value === 'athlete'"
                                fill-rule="evenodd"
                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                clip-rule="evenodd"
                              />
                              <path
                                v-if="role.value === 'referee'"
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z"
                                clip-rule="evenodd"
                              />
                              <path
                                v-if="role.value === 'staff'"
                                fill-rule="evenodd"
                                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                clip-rule="evenodd"
                              />
                              <path
                                v-if="role.value === 'coach'"
                                fill-rule="evenodd"
                                d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                              />
                            </svg>
                          </div>
                          <div class="text-center font-medium">{{ role.label }}</div>
                        </div>
                      </a-radio>
                    </div>
                  </a-radio-group>
                </a-form-item>

                <!-- 運動員選項 -->
                <template v-if="application.role == 'athlete'">
                  <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <a-form-item
                      :label="$t('category')"
                      name="category"
                      class="mb-0"
                      :rules="[{ required: true, message: '請選擇參賽組別' }]"
                    >
                      <a-alert
                        v-if="eligibleCategories.length === 0 && application.dob"
                        message="年齡不符合要求"
                        :description="`根據您的出生日期和比賽開始時間 ${competitionStartYear} 年，您不符合任何組別的年齡要求。`"
                        type="error"
                        show-icon
                        class="mb-4"
                      />
                      <a-alert
                        v-else-if="eligibleCategories.length > 0"
                        :message="`比賽開始年份: ${competitionStartYear} 年`"
                        :description="`您的年齡將以 ${competitionStartYear} 年計算，請選擇符合的組別`"
                        type="info"
                        show-icon
                        class="mb-4"
                      />

                      <a-radio-group
                        v-model:value="application.category"
                        class="category-radio-group"
                        @change="handleCategoryChange"
                      >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <a-radio
                            v-for="cat in eligibleCategories"
                            :value="cat.code"
                            class="category-radio"
                          >
                            <div
                              class="category-card p-4 border rounded-lg hover:border-blue-300"
                            >
                              <div class="flex items-center justify-between">
                                <div>
                                  <div class="font-semibold text-gray-800">
                                    {{ cat.name }}
                                  </div>
                                  <div class="text-sm text-gray-600 mt-1">
                                    {{ cat.description }}
                                  </div>
                                  <div class="text-xs text-blue-600 mt-2">
                                    年齡: {{ cat.age_range[0] }} -
                                    {{ cat.age_range[1] }}歲
                                  </div>
                                </div>
                                <svg
                                  class="w-6 h-6 text-green-500"
                                  fill="currentColor"
                                  viewBox="0 0 20 20"
                                >
                                  <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                  />
                                </svg>
                              </div>
                            </div>
                          </a-radio>
                        </div>
                      </a-radio-group>
                    </a-form-item>

                    <!-- 公斤級選擇 - 只在選擇了組別和性別後顯示 -->
                    <a-form-item
                      v-if="
                        application.category &&
                        application.gender &&
                        availableWeights.length > 0
                      "
                      :label="$t('weight')"
                      name="weight"
                      class="mt-4 mb-0"
                      :rules="[{ required: true, message: '請選擇體重級別' }]"
                    >
                      <a-radio-group
                        v-model:value="application.weight"
                        class="weight-radio-group"
                      >
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                          <a-radio
                            v-for="weight in availableWeights"
                            :key="weight.code"
                            :value="weight.code"
                            class="weight-radio"
                          >
                            <div
                              class="text-center p-3 border rounded-lg hover:border-blue-300"
                            >
                              <div class="font-medium">{{ weight.name }}</div>
                            </div>
                          </a-radio>
                        </div>
                      </a-radio-group>
                      <div
                        v-if="availableWeights.length === 0"
                        class="text-gray-500 text-sm mt-2"
                      >
                        此組別暫無可選的公斤級
                      </div>
                    </a-form-item>
                  </div>
                </template>

                <!-- 裁判選項 -->
                <template v-if="application.role == 'referee'">
                  <div class="mt-6 p-4 bg-green-50 rounded-lg">
                    <a-form-item
                      :label="$t('referee_options')"
                      name="referee_options"
                      class="mb-0"
                      :rules="[{ required: true, message: '請選擇裁判選項' }]"
                    >
                      <a-radio-group
                        v-model:value="application.referee_options"
                        class="referee-radio-group"
                      >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <a-radio
                            v-for="option in competition.referee_options"
                            :value="option.value"
                            class="option-radio"
                          >
                            <div class="p-4 border rounded-lg hover:border-green-300">
                              <div class="font-medium">{{ option.label }}</div>
                            </div>
                          </a-radio>
                        </div>
                      </a-radio-group>
                    </a-form-item>
                  </div>
                </template>

                <!-- 工作人員選項 -->
                <template v-if="application.role == 'staff'">
                  <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                    <a-form-item
                      :label="$t('staff_options')"
                      name="staff_options"
                      class="mb-0"
                      :rules="[
                        {
                          required: true,
                          message: '請選擇至少一個工作人員選項',
                          validator: staffOptionsValidator,
                        },
                      ]"
                    >
                      <a-checkbox-group
                        v-model:value="application.staff_options"
                        class="staff-checkbox-group"
                      >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                          <a-checkbox
                            v-for="option in competition.staff_options"
                            :value="option.value"
                            class="option-checkbox"
                          >
                            <div class="p-4 border rounded-lg hover:border-yellow-300">
                              <div class="font-medium">{{ option.label }}</div>
                            </div>
                          </a-checkbox>
                        </div>
                      </a-checkbox-group>
                    </a-form-item>
                  </div>
                </template>
              </div>

              <!-- 提示訊息：需要先填寫出生日期和性別 -->
              <div v-else class="bg-yellow-50 p-6 rounded-lg">
                <div class="flex items-center">
                  <svg
                    class="w-5 h-5 mr-3 text-yellow-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  <div>
                    <div class="font-semibold text-yellow-800">
                      請先填寫出生日期和性別
                    </div>
                    <div class="text-yellow-700 text-sm mt-1">
                      為了確定您符合的參賽組別，請先填寫出生日期和性別
                    </div>
                  </div>
                </div>
              </div>

              <!-- 頭像上傳區塊 -->
              <div class="bg-gray-50 p-6 rounded-lg">
                <h4 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                  <svg
                    class="w-4 h-4 mr-2 text-gray-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  頭像照片
                </h4>

                <div class="flex flex-col md:flex-row items-center gap-8">
                  <div class="w-full md:w-1/3">
                    <div class="bg-white p-4 rounded-lg border shadow-sm">
                      <div class="text-center mb-4">
                        <div class="text-sm text-gray-500 mb-2">預覽</div>
                        <div
                          class="aspect-square w-48 mx-auto rounded-lg overflow-hidden border bg-gray-100 flex items-center justify-center"
                        >
                          <div v-if="avatarPreview">
                            <img
                              :src="avatarPreview"
                              alt="頭像預覽"
                              class="w-full h-full object-cover"
                            />
                          </div>
                          <div v-else class="p-8">
                            <svg
                              class="w-24 h-24 text-gray-400"
                              fill="currentColor"
                              viewBox="0 0 20 20"
                            >
                              <path
                                fill-rule="evenodd"
                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                clip-rule="evenodd"
                              />
                            </svg>
                          </div>
                        </div>
                        <div v-if="!avatarPreview" class="mt-2 text-sm text-gray-400">
                          請上傳頭像照片
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="w-full md:w-2/3">
                    <p class="text-gray-600 mb-4">
                      請上傳符合要求的頭像照片，照片將用於參賽證件製作。
                    </p>
                    <ul class="text-sm text-gray-500 mb-6 space-y-1">
                      <li class="flex items-center">
                        <svg
                          class="w-4 h-4 mr-2 text-green-500"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        建議尺寸: 8:8 比例
                      </li>
                      <li class="flex items-center">
                        <svg
                          class="w-4 h-4 mr-2 text-green-500"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        建議背景: 白色或淺色
                      </li>
                      <li class="flex items-center">
                        <svg
                          class="w-4 h-4 mr-2 text-green-500"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        檔案大小: 不超過 5MB
                      </li>
                    </ul>

                    <a-button
                      @click="showCropModal = true"
                      type="primary"
                      size="large"
                      class="flex items-center"
                    >
                      <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          fill-rule="evenodd"
                          d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      {{ $t("upload_profile_image") }}
                    </a-button>
                  </div>
                </div>

                <CropperModal
                  v-if="showCropModal"
                  :minAspectRatioProp="{ width: 8, height: 8 }"
                  :maxAspectRatioProp="{ width: 8, height: 8 }"
                  @croppedImageData="setCroppedImageData"
                  @showModal="showCropModal = false"
                />
              </div>

              <!-- 提交按鈕 -->
              <div class="pt-6 border-t border-gray-200">
                <div class="flex flex-col items-center">
                  <!-- 錯誤提示 -->
                  <div
                    v-if="
                      eligibleCategories.length === 0 && application.role === 'athlete'
                    "
                    class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg w-full max-w-2xl"
                  >
                    <div class="flex items-center">
                      <svg
                        class="w-5 h-5 mr-3 text-red-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      <div>
                        <div class="font-semibold text-red-700">無法提交報名</div>
                        <div class="text-red-600 text-sm mt-1">
                          您的年齡不符合任何運動員組別的要求，請選擇其他角色或確認出生日期。
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- 提交按鈕 -->
                  <a-button
                    type="primary"
                    html-type="submit"
                    :loading="loading"
                    :disabled="
                      eligibleCategories.length === 0 && application.role === 'athlete'
                    "
                    size="large"
                    class="min-w-[200px] h-12 text-lg"
                  >
                    {{ loading ? "提交中..." : $t("submit") }}
                  </a-button>

                  <div class="mt-4 text-sm text-gray-500 text-center">
                    點擊提交表示您已閱讀並同意相關比賽規則
                  </div>
                </div>
              </div>
            </a-form>
          </div>
        </div>
      </div>
    </div>

    <!-- 錯誤模態框 -->
    <a-modal
      v-model:open="modal.isOpen"
      :title="modal.title"
      :cancel-button-props="{ style: { display: 'none' } }"
      :ok-text="$t('understand')"
      @ok="modal.isOpen = false"
    >
      <div class="p-4">
        <div class="flex items-start mb-4">
          <svg
            class="w-6 h-6 text-red-500 mr-3 mt-0.5"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd"
            />
          </svg>
          <div>
            <p class="font-medium text-gray-800">重複報名</p>
            <p class="mt-2 text-gray-600">
              如果您是運動員，在同一比賽中最多可報名 2 個組別。
              或者，您只能選擇一個角色報名。
            </p>
            <p class="mt-2 text-sm text-gray-500">{{ modal.content }}</p>
          </div>
        </div>
      </div>
    </a-modal>
  </WebLayout>
</template>

<script>
import WebLayout from "@/Layouts/WebLayout.vue";
import dayjs from "dayjs";
import { message } from "ant-design-vue";
import { Modal } from "ant-design-vue";
import CropperModal from "@/Components/Member/CropperModal.vue";

export default {
  components: {
    WebLayout,
    CropperModal,
  },
  props: ["organizations", "belt_ranks", "member", "competition"],
  data() {
    return {
      loading: false,
      showCropModal: false,
      avatarPreview: null,
      avatarData: null,
      modal: {
        isOpen: false,
        title: "報名失敗",
        content: "錯誤訊息",
      },
      dateFormat: "YYYY-MM-DD",
      application: {
        age: null,
        organization_id: null,
        name_zh: "",
        name_fn: "",
        id_num: "",
        belt_rank: null,
        dob: null,
        gender: null,
        email: "",
        mobile: "",
        role: null,
        category: null,
        weight: null,
        referee_options: null,
        staff_options: [],
        competition_id: null,
        member_id: null,
      },
      eligibleCategories: [],
      availableWeights: [],
      organizationError: false,
      formRules: {
        organization_id: [
          { required: this.member == null, message: "請選擇所屬組織或學校" },
        ],
        name_fn: [{ required: true, message: "請輸入英文姓名" }],
        id_num: [{ required: true, message: "請輸入身份證號碼" }],
        belt_rank: [
          {
            required: () =>
              ["athlete", "referee", "coach"].includes(this.application?.role),
            message: "請選擇帶位",
          },
        ],
        dob: [{ required: true, message: "請選擇出生日期" }],
        gender: [{ required: true, message: "請選擇性別" }],
        email: [
          { required: true, message: "請輸入電郵地址" },
          { type: "email", message: "請輸入有效的電郵地址" },
        ],
        mobile: [
          { required: true, message: "請輸入電話號碼" },
          { pattern: /^[0-9+\-\s()]+$/, message: "請輸入有效的電話號碼" },
        ],
        role: [{ required: true, message: "請選擇參賽角色" }],
        category: [{ required: true, message: "請選擇參賽組別" }],
        weight: [{ required: true, message: "請選擇體重級別" }],
        referee_options: [{ required: true, message: "請選擇裁判選項" }],
        staff_options: [{ required: true, message: "請選擇至少一個工作人員選項" }],
      },
      validateMessages: {
        required: "${label} 是必填項！",
        types: {
          email: "${label} 不是有效的郵箱地址！",
          number: "${label} 不是有效的數字！",
        },
        number: {
          range: "${label} 必須在 ${min} 和 ${max} 之間",
        },
      },
      layout: {
        labelCol: {
          span: 24,
        },
        wrapperCol: {
          span: 24,
        },
      },
    };
  },
  computed: {
    competitionStartYear() {
      if (this.competition.start_date) {
        return dayjs(this.competition.start_date).year();
      }
      return dayjs().year();
    },
  },
  watch: {
    // 監聽 category 變化
    "application.category": function (newCategory, oldCategory) {
      if (newCategory !== oldCategory) {
        this.handleCategoryChange();
      }
    },
    // 監聽 gender 變化
    "application.gender": function (newGender, oldGender) {
      if (newGender !== oldGender && this.application.category) {
        this.updateAvailableWeights();
      }
    },
  },
  mounted() {
    this.application.competition_id = this.competition.id;
    this.competition.roles = JSON.parse(this.competition.roles);
    this.competition.categories_weights = JSON.parse(this.competition.categories_weights);
    if (this.member) {
      this.application.member_id = this.member.id;
      this.application.name_zh = this.member.name_zh;
      this.application.name_fn = this.member.name_fn;
      this.application.gender = this.member.gender;
      this.application.dob = this.member.dob;
      this.application.email = this.member.email;
      this.application.mobile = this.member.mobile;

      // 计算会员年龄
      if (this.member.dob) {
        this.calculateAge(this.member.dob);
      }
    }
  },
  methods: {
    formatDate(dateString) {
      let dates = [];

      try {
        dates = JSON.parse(dateString);
      } catch (error) {
        console.error("解析日期字符串出错:", error);
        return "日期待定";
      }

      if (dates.length === 0) {
        return "日期待定";
      } else if (dates.length === 1) {
        // 只有一天
        return dayjs(dates[0]).format("M月D日");
      } else if (dates.length >= 2) {
        // 两天或更多
        const date1 = dayjs(dates[0]).format("M月D日");
        const date2 = dayjs(dates[1]).format("M月D日");
        return `${date1} 至 ${date2}`;
      }

      return "日期待定";
    },
    handleNameZhInput() {
      this.application.name_zh = this.application.name_zh.toUpperCase();
    },
    handleNameFnInput() {
      this.application.name_fn = this.application.name_fn.toUpperCase();
    },
    setCroppedImageData(data) {
      this.avatarPreview = data.imageUrl;
      this.avatarData = data;
    },
    onDobChange(date) {
      if (date) {
        this.calculateAge(date);
      } else {
        this.application.age = null;
        this.eligibleCategories = [];
      }
      // 重置角色和相關選擇
      this.application.role = null;
      this.application.category = null;
      this.application.weight = null;
      this.availableWeights = [];
    },
    calculateAge(dob) {
      const birthYear = dayjs(dob).year();
      const age = this.competitionStartYear - birthYear;

      this.application.age = age;
      this.updateEligibleCategories(age);
    },
    updateEligibleCategories(age) {
      this.eligibleCategories = this.competition.categories_weights.filter((category) => {
        console.log(category);
        if (category.age_range && category.age_range.length === 2) {
          return age >= category.age_range[0] && age <= category.age_range[1];
        }
        return false;
      });

      // 如果当前选择的category不在符合条件的组别中，清空选择
      if (
        this.application.category &&
        !this.eligibleCategories.some((cat) => cat.code === this.application.category)
      ) {
        this.application.category = null;
        this.application.weight = null;
        this.availableWeights = [];
      }
    },
    onGenderChange(event) {
      // 重置角色和相關選擇
      this.application.role = null;
      this.application.category = null;
      this.application.weight = null;
      this.availableWeights = [];
    },
    onRoleChange() {
      // 重置相關選擇
      this.application.category = null;
      this.application.weight = null;
      this.availableWeights = [];
    },
    handleCategoryChange() {
      // 重置體重選擇
      this.application.weight = null;

      // 更新可用的公斤級選項
      if (this.application.gender && this.application.category) {
        this.updateAvailableWeights();
      }
    },
    updateAvailableWeights() {
      if (!this.application.category || !this.application.gender) {
        this.availableWeights = [];
        return;
      }

      console.log("更新公斤級選項:", this.application.category, this.application.gender);

      const selectedCategory = this.competition.categories_weights.find(
        (cat) => cat.code === this.application.category
      );

      console.log("找到的組別:", selectedCategory);

      if (selectedCategory) {
        if (this.application.gender === "M" && selectedCategory.male) {
          this.availableWeights = selectedCategory.male;
          console.log("男性公斤級:", this.availableWeights);
        } else if (this.application.gender === "F" && selectedCategory.female) {
          this.availableWeights = selectedCategory.female;
          console.log("女性公斤級:", this.availableWeights);
        } else {
          this.availableWeights = [];
          console.log("沒有對應的公斤級");
        }
      } else {
        this.availableWeights = [];
        console.log("未找到組別");
      }
    },
    // 自定義驗證器
    beltRankValidator(rule, value) {
      if (["athlete", "referee", "coach"].includes(this.application.role) && !value) {
        return Promise.reject("請選擇帶位");
      }
      return Promise.resolve();
    },
    staffOptionsValidator(rule, value) {
      if (this.application.role === "staff" && (!value || value.length === 0)) {
        return Promise.reject("請選擇至少一個工作人員選項");
      }
      return Promise.resolve();
    },
    handleOrganizationChange(value) {
      this.organizationError = !value;
    },
    async onFinish(values) {
      try {
        // 表單驗證
        await this.$refs.formRef.validate();

        // 如果是运动员角色，检查年龄是否符合所选组别
        if (this.application.role === "athlete" && this.application.category) {
          const selectedCategory = this.competition.categories_weights.find(
            (cat) => cat.code === this.application.category
          );

          if (selectedCategory && selectedCategory.age_range) {
            const [minAge, maxAge] = selectedCategory.age_range;
            if (this.application.age < minAge || this.application.age > maxAge) {
              message.error({
                content: `您選擇的${selectedCategory.name}組別要求年齡在${minAge}至${maxAge}歲之間，您在比賽開始時間(${this.competitionStartYear}年)時${this.application.age}歲不符合要求。`,
                duration: 5,
              });
              return;
            }
          }
        }

        this.loading = true;

        if (this.avatarData) {
          this.application.avatar = this.avatarData.blob;
        }

        this.$inertia.post(route("competitions.store"), this.application, {
          onSuccess: (page) => {
            this.loading = false;
            message.success({
              content: "報名成功！",
              duration: 3,
            });
          },
          onError: (errors) => {
            this.loading = false;
            console.error("報名錯誤:", errors);

            // 處理重複報名錯誤
            if (errors.message && errors.message.includes("Duplicate")) {
              this.modal = {
                isOpen: true,
                title: "報名失敗",
                content: errors.message,
              };
            } else {
              let errorMessage = "報名失敗，請檢查輸入的資料";
              if (errors.errors) {
                const firstError = Object.values(errors.errors)[0];
                if (firstError && firstError[0]) {
                  errorMessage = firstError[0];
                }
              }
              message.error({
                content: errorMessage,
                duration: 5,
              });
            }
          },
        });
      } catch (error) {
        console.log("表單驗證失敗:", error);
        this.loading = false;
      }
    },
    validateForm() {
      console.log("驗證表單:", this.application);

      // 檢查必填欄位
      const requiredFields = [
        { field: "name_zh", label: "中文姓名" },
        { field: "name_fn", label: "英文姓名" },
        { field: "id_num", label: "身份證號碼" },
        { field: "dob", label: "出生日期" },
        { field: "gender", label: "性別" },
        { field: "email", label: "電郵地址" },
        { field: "mobile", label: "電話號碼" },
        { field: "role", label: "參賽角色" },
      ];

      for (const { field, label } of requiredFields) {
        if (!this.application[field]) {
          message.error(`${label} 是必填項！`);
          return false;
        }
      }

      // 檢查組織/學校（如果不是會員）
      if (this.member == null && !this.application.organization_id) {
        message.error("請選擇所屬組織或學校！");
        return false;
      }

      // 檢查運動員特定欄位
      if (this.application.role === "athlete") {
        if (!this.application.category) {
          message.error("請選擇參賽組別！");
          return false;
        }
        if (!this.application.weight) {
          message.error("請選擇體重級別！");
          return false;
        }
        if (!this.application.belt_rank) {
          message.error("請選擇帶位！");
          return false;
        }
      }

      // 檢查裁判特定欄位
      if (this.application.role === "referee" && !this.application.referee_options) {
        message.error("請選擇裁判選項！");
        return false;
      }

      // 檢查工作人員特定欄位
      if (
        this.application.role === "staff" &&
        (!this.application.staff_options || this.application.staff_options.length === 0)
      ) {
        message.error("請選擇至少一個工作人員選項！");
        return false;
      }

      // 檢查帶位（如果是運動員、裁判或教練）
      if (
        ["athlete", "referee", "coach"].includes(this.application.role) &&
        !this.application.belt_rank
      ) {
        message.error("請選擇帶位！");
        return false;
      }

      // 檢查電郵格式
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(this.application.email)) {
        message.error("請輸入有效的電郵地址！");
        return false;
      }

      return true;
    },
    disabledDate(current) {
      return current && current > dayjs().subtract(3, "year");
    },
  },
};
</script>

<style scoped>
#pure-html {
  all: initial;
}

#pure-html * {
  all: revert;
}

/* 角色卡片樣式 */
.role-card {
  @apply w-full p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-all cursor-pointer;
}

.role-card:hover .role-icon {
  @apply text-blue-500;
}

.ant-radio-wrapper-checked .role-card {
  @apply border-blue-500 bg-blue-50;
}

.ant-radio-wrapper-checked .role-icon {
  @apply text-blue-500;
}

/* 組別卡片樣式 */
.category-card {
  @apply transition-all cursor-pointer;
}

.ant-radio-wrapper-checked .category-card {
  @apply border-blue-500 bg-blue-50;
}

/* 體重級別卡片樣式 */
.weight-radio .ant-radio {
  @apply hidden;
}

.weight-radio .ant-radio + * {
  @apply p-0;
}

.ant-radio-wrapper-checked .weight-radio > div {
  @apply border-blue-500 bg-blue-50 text-blue-600;
}

/* 選項卡片樣式 */
.option-radio .ant-radio {
  @apply hidden;
}

.option-radio .ant-radio + * {
  @apply p-0;
}

.ant-radio-wrapper-checked .option-radio > div {
  @apply border-green-500 bg-green-50 text-green-600;
}

.option-checkbox .ant-checkbox {
  @apply hidden;
}

.option-checkbox .ant-checkbox + * {
  @apply p-0;
}

.ant-checkbox-wrapper-checked .option-checkbox > div {
  @apply border-yellow-500 bg-yellow-50 text-yellow-600;
}

/* 表單項目間距 */
:deep(.ant-form-item) {
  margin-bottom: 0;
}

:deep(.ant-form-item-label) {
  padding-bottom: 8px !important;
}

:deep(.ant-form-item-label label) {
  font-weight: 500;
  color: #374151;
}

/* 自定義滾動條 */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
