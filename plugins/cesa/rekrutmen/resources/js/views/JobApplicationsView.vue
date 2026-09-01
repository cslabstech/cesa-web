<template>
  <div class="space-y-6">
    <!-- Top Header Title & Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">
          {{ activeJobTitle ? `Pelamar: ${activeJobTitle}` : 'Data Pelamar Kerja' }}
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Pantau seluruh data kandidat pelamar, kualifikasi, dan tahapan seleksi
        </p>
      </div>

      <!-- Controls & View Switcher -->
      <div class="flex items-center gap-2.5">
        <!-- Active Filter Indicator -->
        <button
          v-if="activeJobId"
          @click="resetJobFilter"
          class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold border border-blue-200 flex items-center gap-1.5 transition-colors cursor-pointer"
          title="Tampilkan Semua Pelamar"
        >
          <span>Filter: {{ activeJobTitle }}</span>
          <span class="text-blue-500 font-bold">&times;</span>
        </button>

        <!-- Evaluasi Kualifikasi Action Button -->
        <button
          @click="startRescreening"
          :disabled="isScreening"
          class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer disabled:opacity-50"
          title="Jalankan evaluasi kualifikasi otomatis untuk pelamar"
        >
          <RefreshCw class="w-3.5 h-3.5 text-slate-500" :class="{ 'animate-spin': isScreening }" />
          <span>Evaluasi Kualifikasi</span>
        </button>

        <!-- View Switcher (Table / Kanban) -->
        <div class="inline-flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
          <button
            @click="viewMode = 'table'"
            :class="[
              'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
              viewMode === 'table' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <ListFilter class="w-3.5 h-3.5" />
            <span>Tabel</span>
          </button>
          <button
            @click="viewMode = 'kanban'"
            :class="[
              'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
              viewMode === 'kanban' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <Kanban class="w-3.5 h-3.5" />
            <span>Kanban</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Alert / Toast Message -->
    <div
      v-if="toastMessage"
      :class="[
        'p-3 rounded-lg border flex items-center justify-between text-xs font-medium transition-all shadow-2xs',
        toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
      ]"
    >
      <div class="flex items-center gap-2">
        <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 text-emerald-600" />
        <AlertCircle v-else class="w-4 h-4 text-rose-600" />
        <span>{{ toastMessage }}</span>
      </div>
      <button @click="toastMessage = null" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
    </div>

    <!-- Integrated Filter Tabs & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
      <!-- Match Filter Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
        <button
          @click="matchFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'all'
              ? 'bg-slate-900 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Semua Pelamar <span class="ml-1 opacity-70">({{ applications.length }})</span>
        </button>

        <button
          @click="matchFilter = 'recommended'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'recommended'
              ? 'bg-emerald-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Sangat Sesuai <span class="ml-1 opacity-70">({{ recommendedCount }})</span>
        </button>

        <button
          @click="matchFilter = 'considered'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'considered'
              ? 'bg-amber-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Dipertimbangkan <span class="ml-1 opacity-70">({{ consideredCount }})</span>
        </button>

        <button
          @click="matchFilter = 'not_suitable'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            matchFilter === 'not_suitable'
              ? 'bg-slate-700 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Kurang Sesuai <span class="ml-1 opacity-70">({{ notSuitableCount }})</span>
        </button>
      </div>

      <!-- Search Bar -->
      <div class="relative w-full sm:w-80">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nama, email, atau posisi..."
          class="w-full bg-white border border-slate-200 rounded-lg pl-9 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
        />
      </div>
    </div>

    <!-- TABLE VIEW -->
    <div
      v-if="viewMode === 'table'"
      class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr v-if="!selectedAppIds.length" class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <th class="py-3 px-3.5 w-10 text-center">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-3.5 h-3.5"
                  title="Pilih Semua Pelamar"
                />
              </th>
              <th class="py-3 px-4 font-semibold text-[11px]">Kandidat Pelamar</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Posisi Dilamar</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Kualifikasi Match</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Tahapan Seleksi</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Status</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Tanggal Masuk</th>
              <th class="py-3 px-4 text-right font-semibold text-[11px] w-28">Aksi</th>
            </tr>
            <tr v-else class="bg-blue-50/90 text-blue-900 border-b border-blue-200/80">
              <th class="py-2.5 px-3.5 w-10 text-center align-middle">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="rounded border-blue-400 text-blue-600 focus:ring-blue-500 cursor-pointer w-3.5 h-3.5"
                  title="Pilih / Batalkan Semua"
                />
              </th>
              <th colspan="7" class="py-2 px-4 font-medium align-middle">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-xs text-blue-950">{{ selectedAppIds.length }} pelamar dipilih</span>
                    <span class="text-blue-300">&bull;</span>
                    <button
                      type="button"
                      @click="selectedAppIds = []"
                      class="text-xs text-blue-700 hover:text-blue-950 underline cursor-pointer font-medium"
                    >
                      Batalkan pilihan
                    </button>
                  </div>
                  <button
                    type="button"
                    @click="openBulkNotificationModal"
                    class="px-3.5 py-1.5 bg-[#0c2340] hover:bg-[#15325b] text-white font-medium rounded-lg text-xs flex items-center gap-1.5 transition-colors cursor-pointer shadow-xs"
                  >
                    <Send class="w-3.5 h-3.5" />
                    <span>Kirim Notifikasi Massal</span>
                  </button>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="app in filteredApplications"
              :key="app.id"
              :class="['hover:bg-slate-50 transition-colors group', isSelected(app.id) ? 'bg-blue-50/40' : '']"
            >
              <!-- Checkbox -->
              <td class="py-3.5 px-3.5 align-middle text-center" @click.stop>
                <input
                  type="checkbox"
                  :value="app.id"
                  v-model="selectedAppIds"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-3.5 h-3.5"
                />
              </td>

              <!-- Name & Contact -->
              <td class="py-3.5 px-4 align-middle">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                    <User class="w-4 h-4 text-slate-500" />
                  </div>
                  <div>
                    <div class="font-bold text-xs text-slate-900 hover:text-blue-600 transition-colors cursor-pointer" @click="openDetail(app)">
                      {{ app.full_name }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">{{ app.email || '-' }} &bull; {{ app.whatsapp_number || app.phone || '-' }}</div>
                  </div>
                </div>
              </td>

              <!-- Job Title -->
              <td class="py-3.5 px-4 align-middle text-slate-700 font-medium">
                {{ app.job_posting?.title || '-' }}
              </td>

              <!-- Score / Match -->
              <td class="py-3.5 px-4 align-middle">
                <div v-if="app.ai_match_score !== null && app.ai_match_score !== undefined">
                  <button
                    type="button"
                    @click.stop="openAnalysisModal(app)"
                    :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border cursor-pointer hover:shadow-xs transition-all hover:scale-105 active:scale-95', getAiBadgeClasses(app.ai_match_score)]"
                    title="Klik untuk melihat hasil analisis kualifikasi"
                  >
                    {{ app.ai_match_score }}% &bull; {{ formatAiRecommendation(app.ai_recommendation) }}
                  </button>
                </div>
                <div v-else class="text-[11px] text-slate-400 italic">Menunggu evaluasi</div>
              </td>

              <!-- Stage -->
              <td class="py-3.5 px-4 align-middle">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                  {{ app.stage?.name || 'Screening CV' }}
                </span>
              </td>

              <!-- Status -->
              <td class="py-3.5 px-4 align-middle">
                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold border', getStatusBadge(app.status)]">
                  {{ formatStatus(app.status) }}
                </span>
              </td>

              <!-- Date -->
              <td class="py-3.5 px-4 align-middle text-slate-600 whitespace-nowrap text-xs">
                {{ app.created_at }}
              </td>

              <!-- Action -->
              <td class="py-3.5 px-4 align-middle text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click.stop="openSendEmailModal(app)"
                    class="p-1.5 text-slate-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1"
                    title="Kirim Notifikasi (Email / WhatsApp)"
                  >
                    <Send class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="openDetail(app)"
                    class="px-2.5 py-1 text-blue-600 hover:bg-blue-50 rounded-md text-xs font-semibold transition-colors cursor-pointer"
                  >
                    Detail Profil
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!filteredApplications.length">
              <td colspan="8" class="py-12 text-center text-xs text-slate-500">
                Tidak ada data kandidat pelamar yang sesuai kriteria filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- KANBAN BOARD VIEW -->
    <div v-else class="flex gap-4 overflow-x-auto pb-4 custom-scrollbar min-h-[calc(100vh-280px)] items-start select-none">
      <div
        v-for="stage in stages"
        :key="stage.id"
        class="w-80 shrink-0 bg-slate-100/70 border rounded-2xl flex flex-col max-h-[calc(100vh-280px)] transition-all shadow-2xs"
        :class="dragOverStageId === stage.id ? 'border-blue-500 bg-blue-50/50 ring-2 ring-blue-400/30' : 'border-slate-200'"
        @dragover.prevent="handleDragOver(stage.id)"
        @dragleave="handleDragLeave(stage.id)"
        @drop.prevent="handleDrop(stage.id, $event)"
      >
        <!-- Column Header -->
        <div class="p-3.5 border-b border-slate-200/80 bg-white/80 backdrop-blur-xs rounded-t-2xl flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: stage.color || '#64748b' }"></span>
            <span class="text-xs font-bold text-slate-800 tracking-tight">{{ stage.name }}</span>
          </div>
          <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
            {{ getStageApplications(stage.id).length }}
          </span>
        </div>

        <!-- Kanban Cards List -->
        <div class="p-3 space-y-3 overflow-y-auto flex-1 custom-scrollbar">
          <div
            v-for="app in getStageApplications(stage.id)"
            :key="app.id"
            class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs hover:shadow-xs transition-all cursor-grab active:cursor-grabbing hover:border-slate-300 group"
            draggable="true"
            @dragstart="handleDragStart(app, $event)"
            @dragend="handleDragEnd"
            @click="openDetail(app)"
          >
            <!-- Top: Candidate Name & Match Pill -->
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200">
                  <User class="w-3.5 h-3.5 text-slate-500" />
                </div>
                <div class="min-w-0">
                  <h4 class="font-bold text-xs text-slate-900 group-hover:text-blue-600 transition-colors truncate">
                    {{ app.full_name }}
                  </h4>
                  <p class="text-[11px] text-slate-400 truncate">{{ app.email }}</p>
                </div>
              </div>

              <button
                v-if="app.ai_match_score !== null && app.ai_match_score !== undefined"
                type="button"
                @click.stop="openAnalysisModal(app)"
                :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold border shrink-0 hover:shadow-xs transition-transform cursor-pointer', getAiBadgeClasses(app.ai_match_score)]"
                title="Klik untuk melihat hasil analisis kualifikasi"
              >
                {{ app.ai_match_score }}%
              </button>
            </div>

            <!-- Role / Details Subtitle -->
            <div v-if="!activeJobId && app.job_posting?.title" class="text-[11px] font-medium text-slate-600 mt-2.5 pt-2 border-t border-slate-100 line-clamp-1">
              {{ app.job_posting.title }}
            </div>

            <!-- Card Footer -->
            <div class="flex items-center justify-between text-[10px] text-slate-400 mt-2.5 pt-2 border-t border-slate-100">
              <span>{{ app.created_at }}</span>
              <span class="px-1.5 py-0.5 rounded bg-slate-50 text-slate-600 border border-slate-100 font-medium">
                {{ app.source || 'Portal' }}
              </span>
            </div>
          </div>

          <!-- Empty State in Column -->
          <div
            v-if="!getStageApplications(stage.id).length"
            class="py-10 text-center text-xs text-slate-400 border border-dashed border-slate-200/80 rounded-xl flex flex-col items-center justify-center gap-1"
          >
            <span class="text-slate-300 text-sm">&empty;</span>
            <span>Belum ada kandidat</span>
          </div>
        </div>
      </div>
    </div>

    <!-- CANDIDATE ATS DETAIL MODAL -->
    <div
      v-if="selectedApp"
      class="fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 md:p-8"
      @click.self="selectedApp = null"
    >
      <div class="bg-white rounded-xl border border-slate-200 w-full max-w-5xl h-[86vh] max-h-[860px] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Top Header Bar -->
        <div class="px-6 py-3.5 border-b border-slate-200 bg-white flex items-center justify-between shrink-0">
          <div class="flex items-center gap-3 min-w-0">
            <button
              @click="selectedApp = null"
              class="p-1 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors cursor-pointer shrink-0"
              title="Kembali"
            >
              <ArrowLeft class="w-4 h-4" />
            </button>
            <div class="min-w-0">
              <h2 class="text-sm font-bold text-slate-900 leading-tight truncate">
                {{ selectedApp.full_name }}
              </h2>
              <p class="text-[11px] text-slate-400 font-normal mt-0.5">
                ID #{{ selectedApp.id }} &bull; Melamar pada {{ selectedApp.created_at }}
              </p>
            </div>
          </div>

          <!-- Top Right: Stage Selector, Kirim Notifikasi, Buka CV, Close -->
          <div class="flex items-center gap-2.5 shrink-0">
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-medium text-slate-500">Tahap:</span>
              <div class="relative">
                <select
                  :value="selectedApp.current_stage_id || selectedApp.stage?.id || 1"
                  @change="moveCandidateStage(selectedApp, $event.target.value)"
                  class="appearance-none bg-white border border-slate-200 hover:border-slate-300 text-xs font-medium rounded-lg pl-2.5 pr-7 py-1.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer shadow-2xs"
                >
                  <option v-for="stg in stages" :key="stg.id" :value="stg.id">
                    {{ stg.name }}
                  </option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                  <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                </div>
              </div>
            </div>

            <button
              @click="openSendEmailModal(selectedApp)"
              class="px-3 py-1.5 rounded-lg bg-[#0c2340] hover:bg-[#15325b] text-white text-xs font-medium flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer"
            >
              <Send class="w-3.5 h-3.5" />
              <span>Kirim Notifikasi</span>
            </button>

            <a
              v-if="selectedApp.resume_url"
              :href="selectedApp.resume_url"
              target="_blank"
              class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-medium text-slate-700 flex items-center gap-1.5 shadow-2xs transition-colors"
            >
              <span>Buka CV</span>
              <ExternalLink class="w-3.5 h-3.5 text-slate-400" />
            </a>

            <button
              @click="selectedApp = null"
              class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
              title="Tutup"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Main Body: 2 Columns Layout -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden min-h-0">
          
          <!-- LEFT COLUMN: Candidate Data & Evaluation (45% Width) -->
          <div class="w-full lg:w-[45%] p-5 overflow-y-auto custom-scrollbar border-r border-slate-200 bg-white space-y-4">
            
            <!-- 0. Evaluasi Kualifikasi & Kesesuaian -->
            <div class="p-3.5 rounded-xl bg-slate-50/70 border border-slate-200/80 space-y-2.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <CheckCircle2 class="w-4 h-4 text-blue-700" />
                  <h3 class="text-xs font-semibold text-slate-900">Evaluasi Kualifikasi</h3>
                </div>
                <span
                  v-if="selectedApp.ai_match_score !== null && selectedApp.ai_match_score !== undefined"
                  :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold border', getAiBadgeClasses(selectedApp.ai_match_score)]"
                >
                  {{ selectedApp.ai_match_score }}% &bull; {{ formatAiRecommendation(selectedApp.ai_recommendation) }}
                </span>
                <span v-else class="text-[11px] text-slate-400 italic">Belum dievaluasi</span>
              </div>

              <!-- Summary Text with line breaks -->
              <div class="text-xs text-slate-700 leading-relaxed bg-white p-3 rounded-lg border border-slate-200/70 whitespace-pre-line">
                {{ selectedApp.ai_summary || 'Evaluasi kualifikasi membandingkan kriteria posisi dengan berkas CV pelamar.' }}
              </div>

              <!-- Footer with Re-screen button -->
              <div class="flex items-center justify-between pt-0.5 text-[11px] text-slate-400">
                <span v-if="selectedApp.ai_analyzed_at">Diperbarui: {{ selectedApp.ai_analyzed_at }}</span>
                <span v-else></span>
                <div class="flex items-center gap-1.5">
                  <button
                    type="button"
                    @click="rescreenSingleCandidate(selectedApp)"
                    :disabled="isScreening"
                    class="px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 bg-white rounded-md border border-slate-200 flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-50"
                  >
                    <RefreshCw class="w-3 h-3 text-slate-400" :class="{ 'animate-spin': isScreening }" />
                    <span>Evaluasi Ulang</span>
                  </button>
                  <button
                    type="button"
                    @click="openAnalysisModal(selectedApp)"
                    class="px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-50 bg-white rounded-md border border-blue-200 transition-colors cursor-pointer"
                  >
                    Detail Komparasi
                  </button>
                </div>
              </div>
            </div>

            <!-- 1. Data Pribadi -->
            <div>
              <h3 class="text-xs font-semibold text-slate-800 mb-1.5">Data Pribadi</h3>
              <div class="border border-slate-200/80 rounded-lg overflow-hidden">
                <table class="w-full text-xs text-left">
                  <tbody class="divide-y divide-slate-100">
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium w-36 bg-slate-50/50">Nama Lengkap</td>
                      <td class="py-2 px-3 text-slate-900 font-semibold">{{ selectedApp.full_name }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">Email</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.email || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">Jenis Kelamin</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.gender || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">Tanggal Lahir</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.birth_date || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">Status Pernikahan</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.marital_status || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">Sumber Lamaran</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.source || 'Website' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 2. Kontak & Komunikasi -->
            <div>
              <h3 class="text-xs font-semibold text-slate-800 mb-1.5">Kontak &amp; Komunikasi</h3>
              <div class="border border-slate-200/80 rounded-lg overflow-hidden">
                <table class="w-full text-xs text-left">
                  <tbody class="divide-y divide-slate-100">
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium w-36 bg-slate-50/50">No. WhatsApp</td>
                      <td class="py-2 px-3 text-slate-900 font-medium">{{ selectedApp.whatsapp_number || selectedApp.phone || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">No. Telepon</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.active_phone || selectedApp.phone || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 3. Kontak Darurat -->
            <div>
              <h3 class="text-xs font-semibold text-slate-800 mb-1.5">Kontak Darurat</h3>
              <div class="border border-slate-200/80 rounded-lg overflow-hidden">
                <table class="w-full text-xs text-left">
                  <tbody class="divide-y divide-slate-100">
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium w-36 bg-slate-50/50">Nama</td>
                      <td class="py-2 px-3 text-slate-900 font-medium">{{ selectedApp.emergency_contact_name || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">Hubungan</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.emergency_contact_relation || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50">No. Kontak</td>
                      <td class="py-2 px-3 text-slate-800">{{ selectedApp.emergency_contact_phone || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 4. Alamat Pelamar -->
            <div>
              <h3 class="text-xs font-semibold text-slate-800 mb-1.5">Alamat Pelamar</h3>
              <div class="border border-slate-200/80 rounded-lg overflow-hidden">
                <table class="w-full text-xs text-left">
                  <tbody class="divide-y divide-slate-100">
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium w-36 bg-slate-50/50 align-top">Alamat KTP</td>
                      <td class="py-2 px-3 text-slate-800 leading-relaxed">{{ selectedApp.address_ktp || '-' }}</td>
                    </tr>
                    <tr>
                      <td class="py-2 px-3 text-slate-500 font-medium bg-slate-50/50 align-top">Alamat Domisili</td>
                      <td class="py-2 px-3 text-slate-800 leading-relaxed">{{ selectedApp.address_domicile || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>

          <!-- RIGHT COLUMN: CV / Document Viewer (55% Width) -->
          <div class="w-full lg:w-[55%] bg-slate-50/50 p-5 flex flex-col h-full overflow-hidden">
            
            <div class="flex items-center justify-between mb-2.5 shrink-0">
              <h3 class="text-xs font-semibold text-slate-800">Pratinjau Dokumen CV</h3>
              <div class="flex items-center gap-2">
                <!-- Upload / Ganti CV Button -->
                <label
                  class="px-2.5 py-1 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg border border-slate-200 flex items-center gap-1.5 cursor-pointer shadow-2xs transition-colors"
                  title="Upload / Ganti Dokumen CV asli pelamar"
                >
                  <Upload class="w-3.5 h-3.5 text-slate-500" />
                  <span>{{ isUploadingCv ? 'Mengunggah...' : 'Upload CV Asli' }}</span>
                  <input
                    type="file"
                    accept="application/pdf,.pdf,.doc,.docx"
                    @change="handleCvUploadForApplicant"
                    class="hidden"
                    :disabled="isUploadingCv"
                  />
                </label>

                <a
                  v-if="selectedApp.resume_url"
                  :href="selectedApp.resume_url"
                  target="_blank"
                  class="text-xs font-medium text-blue-600 hover:text-blue-800 inline-flex items-center gap-1 ml-1"
                >
                  <span>Unduh Berkas</span>
                  <ExternalLink class="w-3 h-3" />
                </a>
              </div>
            </div>

            <!-- Embedded PDF Document Container -->
            <div class="flex-1 rounded-lg border border-slate-200 bg-white overflow-hidden shadow-2xs flex flex-col relative">
              <iframe
                v-if="selectedApp.resume_url"
                :src="selectedApp.resume_url"
                class="w-full h-full border-0"
              ></iframe>
              <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-slate-50/50">
                <FileText class="w-10 h-10 text-slate-300 mb-2.5" />
                <p class="text-xs font-bold text-slate-700">Belum Ada Dokumen CV Asli</p>
                <p class="text-[11px] text-slate-400 mt-1 max-w-sm">Anda dapat mengunggah file CV asli pelamar (format PDF/DOC) agar muncul di pratinjau ini.</p>
                
                <label
                  class="mt-3.5 px-3.5 py-1.5 bg-[#0c2340] hover:bg-[#15325b] text-white font-medium text-xs rounded-lg shadow-2xs cursor-pointer flex items-center gap-2 transition-colors"
                >
                  <Upload class="w-3.5 h-3.5" />
                  <span>{{ isUploadingCv ? 'Mengunggah...' : 'Upload File CV (PDF)' }}</span>
                  <input
                    type="file"
                    accept="application/pdf,.pdf,.doc,.docx"
                    @change="handleCvUploadForApplicant"
                    class="hidden"
                    :disabled="isUploadingCv"
                  />
                </label>
              </div>
            </div>

          </div>

        </div>
      </div>
    </div>

    <!-- HASIL EVALUASI KUALIFIKASI & PERSYARATAN MODAL -->
    <div
      v-if="analysisModalApp"
      class="fixed inset-0 z-[110] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
      @click.self="analysisModalApp = null"
    >
      <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-100 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4.5 border-b border-slate-200 flex items-center justify-between bg-white shrink-0">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-700 shrink-0">
              <CheckCircle2 class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900">Detail Komparasi Kualifikasi</h3>
              <p class="text-xs text-slate-500 mt-0.5">{{ analysisModalApp.full_name }} &bull; {{ analysisModalApp.job_posting?.title || 'Posisi Lowongan' }}</p>
            </div>
          </div>
          <button
            @click="analysisModalApp = null"
            class="text-slate-400 hover:text-slate-700 rounded-lg p-1 text-xl font-bold cursor-pointer leading-none"
          >
            &times;
          </button>
        </div>

        <!-- Modal Body: Clean Table Style & Analysis Report -->
        <div class="p-6 space-y-4.5 text-xs overflow-y-auto custom-scrollbar flex-1">
          <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
            <table class="w-full text-left">
              <tbody class="divide-y divide-slate-200">
                <tr>
                  <td class="py-2.5 px-4 text-slate-500 font-medium w-40 bg-slate-50/60">Nama Pelamar</td>
                  <td class="py-2.5 px-4 text-slate-900 font-bold uppercase">{{ analysisModalApp.full_name }}</td>
                </tr>
                <tr>
                  <td class="py-2.5 px-4 text-slate-500 font-medium bg-slate-50/60">Posisi yang Dilamar</td>
                  <td class="py-2.5 px-4 text-slate-900 font-semibold">{{ analysisModalApp.job_posting?.title || '-' }}</td>
                </tr>
                <tr>
                  <td class="py-2.5 px-4 text-slate-500 font-medium bg-slate-50/60">Kesesuaian Kualifikasi</td>
                  <td class="py-2.5 px-4">
                    <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border shadow-2xs', getAiBadgeClasses(analysisModalApp.ai_match_score)]">
                      {{ analysisModalApp.ai_match_score }}% Match &bull; {{ formatAiRecommendation(analysisModalApp.ai_recommendation) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Catatan / Rangkuman Evaluasi Komparatif -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <FileText class="w-3.5 h-3.5 text-slate-500" />
                <span>Rangkuman Evaluasi Komparasi CV vs Kualifikasi</span>
              </h4>
              <span class="text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                AI &amp; ATS Screening
              </span>
            </div>
            
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/90 text-slate-700 leading-relaxed text-xs whitespace-pre-line shadow-2xs">
              {{ analysisModalApp.ai_summary || 'Kandidat memiliki kualifikasi yang relevan dengan persyaratan posisi lowongan ini.' }}
            </div>
            
            <div v-if="analysisModalApp.ai_analyzed_at" class="text-[11px] text-slate-400 text-right">
              Terakhir dievaluasi: {{ analysisModalApp.ai_analyzed_at }}
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
          <button
            @click="rescreenSingleCandidate(analysisModalApp)"
            :disabled="isScreening"
            class="px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 rounded-lg border border-slate-300 flex items-center gap-1.5 transition-colors cursor-pointer disabled:opacity-50 shadow-2xs"
          >
            <RefreshCw class="w-3 h-3 text-slate-500" :class="{ 'animate-spin': isScreening }" />
            <span>Evaluasi Ulang CV</span>
          </button>

          <div class="flex items-center gap-2">
            <button
              @click="analysisModalApp = null"
              class="px-3.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer"
            >
              Tutup
            </button>
            <button
              @click="openDetail(analysisModalApp); analysisModalApp = null"
              class="px-4 py-1.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-lg shadow-xs transition-colors cursor-pointer"
            >
              Buka Detail Profil
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- SEND NOTIFICATION MODAL (Email & WhatsApp, Single & Bulk) -->
    <div
      v-if="sendEmailModalApp"
      class="fixed inset-0 z-[120] bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
      @click.self="closeNotificationModal"
    >
      <div class="bg-white rounded-xl border border-slate-200 w-full max-w-2xl shadow-xl overflow-hidden flex flex-col my-6 max-h-[92vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
          <div>
            <h3 class="text-sm font-bold text-slate-900">
              {{ isBulkMode ? 'Kirim Notifikasi Massal' : 'Kirim Undangan / Notifikasi' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
              <template v-if="isBulkMode">
                Target: <strong class="text-slate-800">{{ selectedAppIds.length }} Pelamar</strong> &bull; Pesan disesuaikan per nama pelamar
              </template>
              <template v-else>
                Penerima: <strong class="text-slate-800">{{ sendEmailModalApp.full_name }}</strong>
                <span v-if="sendEmailModalApp.email" class="text-slate-400 ml-1.5">&bull; {{ sendEmailModalApp.email }}</span>
                <span v-if="sendEmailModalApp.whatsapp_number || sendEmailModalApp.phone" class="text-emerald-700 font-medium ml-1.5">&bull; WA: {{ sendEmailModalApp.whatsapp_number || sendEmailModalApp.phone }}</span>
              </template>
            </p>
          </div>
          <button
            type="button"
            @click="closeNotificationModal"
            class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg p-1.5 transition-colors cursor-pointer"
            title="Tutup"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 space-y-4 text-xs overflow-y-auto flex-1 custom-scrollbar">
          <!-- Template Selector: Full Width 4-Column Grid (NO Horizontal Scroll) -->
          <div class="space-y-1.5">
            <div class="grid grid-cols-4 gap-1 p-1 bg-slate-100 rounded-lg w-full">
              <button
                v-for="tpl in [
                  { key: 'psikotes', label: 'Tes Online', icon: FileText },
                  { key: 'interview', label: 'Wawancara', icon: Users },
                  { key: 'offering', label: 'Offering Letter', icon: Mail },
                  { key: 'rejection', label: 'Penolakan', icon: Bell },
                ]"
                :key="tpl.key"
                type="button"
                @click="applyEmailTemplate(tpl.key)"
                :class="[
                  'py-2 px-2 rounded-md text-xs font-medium flex items-center justify-center gap-1.5 transition-all cursor-pointer text-center select-none',
                  activeEmailTemplateKey === tpl.key
                    ? 'bg-white text-slate-900 shadow-2xs font-semibold'
                    : 'text-slate-600 hover:text-slate-900'
                ]"
              >
                <component :is="tpl.icon" class="w-3.5 h-3.5 shrink-0 text-slate-500" />
                <span class="truncate">{{ tpl.label }}</span>
              </button>
            </div>
          </div>

          <!-- Channel Selector: Clean Dedicated Row -->
          <div class="flex items-center justify-between py-2 px-3 bg-slate-50 border border-slate-200/80 rounded-lg">
            <span class="text-xs font-medium text-slate-700">Kanal Pengiriman:</span>
            <div class="flex items-center gap-2">
              <label
                :class="[
                  'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border cursor-pointer transition-all select-none',
                  selectedChannels.includes('email')
                    ? 'bg-white text-blue-700 border-blue-300 shadow-2xs font-semibold'
                    : 'bg-transparent text-slate-400 border-transparent hover:text-slate-600'
                ]"
                title="Kirim via Email"
              >
                <input
                  type="checkbox"
                  value="email"
                  v-model="selectedChannels"
                  class="rounded border-slate-300 text-blue-600 focus:ring-0 w-3.5 h-3.5 cursor-pointer"
                />
                <Mail class="w-3.5 h-3.5 text-blue-600" />
                <span>Email</span>
              </label>

              <label
                :class="[
                  'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-medium border cursor-pointer transition-all select-none',
                  selectedChannels.includes('whatsapp')
                    ? 'bg-white text-emerald-700 border-emerald-300 shadow-2xs font-semibold'
                    : 'bg-transparent text-slate-400 border-transparent hover:text-slate-600'
                ]"
                title="Kirim via WhatsApp"
              >
                <input
                  type="checkbox"
                  value="whatsapp"
                  v-model="selectedChannels"
                  class="rounded border-slate-300 text-emerald-600 focus:ring-0 w-3.5 h-3.5 cursor-pointer"
                />
                <MessageSquare class="w-3.5 h-3.5 text-emerald-600" />
                <span>WhatsApp</span>
              </label>
            </div>
          </div>

          <div v-if="!selectedChannels.length" class="text-rose-600 text-[11px] font-medium">
            Pilih minimal salah satu kanal pengiriman (Email atau WhatsApp).
          </div>

          <!-- Subject Input -->
          <div>
            <label class="block font-medium text-xs text-slate-700 mb-1">Subjek</label>
            <input
              type="text"
              v-model="emailForm.subject"
              class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs font-medium transition-all"
            />
          </div>

          <!-- Body Message Textarea -->
          <div>
            <label class="block font-medium text-xs text-slate-700 mb-1">Isi Pesan</label>
            <textarea
              v-model="emailForm.body_message"
              rows="4"
              class="w-full bg-white border border-slate-200 rounded-lg p-3 text-xs text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 leading-relaxed font-sans shadow-2xs transition-all"
            ></textarea>
          </div>

          <!-- Detail Pelaksanaan (Symmetric 2x2 Grid with matching icons) -->
          <div class="pt-2 border-t border-slate-100 space-y-2.5">
            <div class="text-[11px] font-semibold text-slate-500">Detail Pelaksanaan</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Jadwal / Batas Waktu</label>
                <div class="relative">
                  <Calendar class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                  <input
                    type="text"
                    v-model="emailForm.schedule"
                    placeholder="Contoh: Batas pengerjaan 3 hari kerja"
                    class="w-full bg-white border border-slate-200 rounded-lg pl-8.5 pr-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs transition-all"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Lokasi / Media</label>
                <div class="relative">
                  <MapPin class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                  <input
                    type="text"
                    v-model="emailForm.venue_or_method"
                    placeholder="Contoh: Google Meet / Online Assessment"
                    class="w-full bg-white border border-slate-200 rounded-lg pl-8.5 pr-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs transition-all"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Tautan Akses (Opsional)</label>
                <div class="relative">
                  <Link2 class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                  <input
                    type="url"
                    v-model="emailForm.action_url"
                    placeholder="https://..."
                    class="w-full bg-white border border-slate-200 rounded-lg pl-8.5 pr-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs font-mono text-[11px] transition-all"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                <div class="relative">
                  <FileText class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                  <input
                    type="text"
                    v-model="emailForm.special_note"
                    placeholder="Contoh: Siapkan kartu identitas saat tes"
                    class="w-full bg-white border border-slate-200 rounded-lg pl-8.5 pr-3 py-1.5 text-xs text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs transition-all"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Offering Letter PDF Upload -->
          <div v-if="activeEmailTemplateKey === 'offering'" class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
            <div class="flex items-center justify-between">
              <label class="block font-semibold text-xs text-slate-800 flex items-center gap-1.5">
                <FileText class="w-3.5 h-3.5 text-blue-600" />
                <span>Dokumen Lampiran (PDF)</span>
              </label>
              <span class="text-[10.5px] text-slate-500">Maks. 15MB &bull; Khusus Email</span>
            </div>

            <div v-if="!emailForm.attachment" class="relative border border-dashed border-slate-300 hover:border-slate-400 bg-white rounded-lg p-3 text-center cursor-pointer transition-colors group">
              <input
                type="file"
                accept="application/pdf,.pdf"
                @change="handleAttachmentUpload"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
              />
              <div class="flex items-center justify-center gap-2 text-slate-500 group-hover:text-slate-800">
                <Upload class="w-4 h-4 text-slate-400 group-hover:text-slate-600" />
                <span class="text-xs font-medium">Unggah file PDF Offering Letter</span>
              </div>
            </div>

            <div v-else class="flex items-center justify-between bg-white p-2.5 rounded-lg border border-slate-200 shadow-2xs">
              <div class="flex items-center gap-2 overflow-hidden">
                <div class="w-7 h-7 rounded bg-red-50 text-red-600 flex items-center justify-center font-bold text-[10px] shrink-0">
                  PDF
                </div>
                <div class="truncate">
                  <div class="text-xs font-medium text-slate-800 truncate">{{ emailForm.attachment_name }}</div>
                  <div class="text-[10px] text-slate-400">{{ formatFileSize(emailForm.attachment?.size) }}</div>
                </div>
              </div>
              <button
                type="button"
                @click="removeAttachment"
                class="text-slate-400 hover:text-red-600 p-1 rounded transition-colors cursor-pointer"
                title="Hapus Lampiran"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3.5 border-t border-slate-200/80 bg-slate-50 flex items-center justify-between sticky bottom-0 z-10">
          <button
            type="button"
            @click="closeNotificationModal"
            class="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-200/60 rounded-lg transition-colors cursor-pointer"
          >
            Batal
          </button>

          <button
            type="button"
            @click="executeSendNotification"
            :disabled="isSendingEmail || !selectedChannels.length"
            class="px-5 py-2 bg-[#0c2340] hover:bg-[#15325b] text-white font-medium rounded-lg text-xs transition-colors cursor-pointer shadow-xs disabled:opacity-50 flex items-center gap-2"
          >
            <span v-if="isSendingEmail" class="inline-block w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Send v-else class="w-3.5 h-3.5" />
            <span>
              {{ isSendingEmail ? 'Mengirim...' : (isBulkMode ? `Kirim ke ${selectedAppIds.length} Pelamar` : 'Kirim Notifikasi') }}
            </span>
          </button>
        </div>
      </div>
    </div>


  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRekrutmenStore } from '../stores/rekrutmen';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import axios from 'axios';
import { 
  Search, ListFilter, Kanban, ArrowLeft, ExternalLink,
  CheckCircle2, AlertCircle, Mail, Phone, FileText, RefreshCw, User, Sparkles,
  Link2, Users, Bell, ChevronDown, Upload, MessageSquare, Send, CheckSquare,
  Calendar, MapPin, X
} from 'lucide-vue-next';

const store = useRekrutmenStore();
const route = useRoute();
const router = useRouter();

const logoUrl = '/images/logo.png';
const oceanSpaceLogoUrl = '/images/oceanspace-logo.png';
const viewMode = ref('table');
const matchFilter = ref('all');
const searchQuery = ref('');
const toastMessage = ref(null);
const toastType = ref('success');
const selectedApp = ref(null);
const analysisModalApp = ref(null);
const dragOverStageId = ref(null);
const isScreening = ref(false);

// Bulk selection state
const selectedAppIds = ref([]);
const isBulkMode = ref(false);
const selectedChannels = ref(['email', 'whatsapp']);

const isAllSelected = computed(() => {
  if (!filteredApplications.value.length) return false;
  return filteredApplications.value.every(app => selectedAppIds.value.includes(app.id));
});

const isSelected = (id) => selectedAppIds.value.includes(id);

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedAppIds.value = [];
  } else {
    selectedAppIds.value = filteredApplications.value.map(app => app.id);
  }
};

const defaultStages = [
  { id: 1, name: 'Screening CV', color: '#2563eb' },
  { id: 2, name: 'Interview HR', color: '#d97706' },
  { id: 3, name: 'Psikotes', color: '#7c3aed' },
  { id: 4, name: 'Tes Kompetensi', color: '#4f46e5' },
  { id: 5, name: 'Interview User', color: '#0284c7' },
  { id: 6, name: 'Background Check', color: '#0d9488' },
  { id: 7, name: 'Offering Letter', color: '#ea580c' },
  { id: 8, name: 'Hired', color: '#059669' }
];

onMounted(() => {
  store.fetchApplications('', false).catch(() => {});
});

const applications = computed(() => {
  if (route.query.job_id) {
    return store.applications.filter(a => String(a.job_posting_id) === String(route.query.job_id));
  }
  return store.applications || [];
});

const stages = computed(() => {
  if (store.stages && store.stages.length) return store.stages;
  if (store.configurations?.stages && store.configurations.stages.length) return store.configurations.stages;
  return defaultStages;
});

const activeJobId = computed(() => route.query.job_id || null);
const activeJobTitle = computed(() => {
  if (!activeJobId.value) return null;
  const job = store.postings.find(j => String(j.id) === String(activeJobId.value));
  return job ? job.title : null;
});

const recommendedCount = computed(() => applications.value.filter(a => a.ai_match_score >= 75).length);
const consideredCount = computed(() => applications.value.filter(a => a.ai_match_score >= 50 && a.ai_match_score < 75).length);
const notSuitableCount = computed(() => applications.value.filter(a => a.ai_match_score !== null && a.ai_match_score < 50).length);

const filteredApplications = computed(() => {
  let list = applications.value;

  if (matchFilter.value === 'recommended') {
    list = list.filter(a => a.ai_match_score >= 75);
  } else if (matchFilter.value === 'considered') {
    list = list.filter(a => a.ai_match_score >= 50 && a.ai_match_score < 75);
  } else if (matchFilter.value === 'not_suitable') {
    list = list.filter(a => a.ai_match_score !== null && a.ai_match_score < 50);
  }

  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(a => 
    (a.full_name && a.full_name.toLowerCase().includes(q)) ||
    (a.email && a.email.toLowerCase().includes(q)) ||
    (a.phone && a.phone.includes(q)) ||
    (a.job_posting && a.job_posting.title && a.job_posting.title.toLowerCase().includes(q))
  );
});

const getStageApplications = (stageId) => {
  return filteredApplications.value.filter(a => {
    const currentStage = a.current_stage_id || a.stage?.id || 1;
    return String(currentStage) === String(stageId);
  });
};

const getInitials = (name) => {
  if (!name) return 'PL';
  const parts = name.trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const getAiBadgeClasses = (score) => {
  if (score === null || score === undefined) return 'bg-slate-50 text-slate-500 border-slate-200';
  if (score >= 75) return 'bg-emerald-50 text-emerald-700 border-emerald-300';
  if (score >= 50) return 'bg-amber-50 text-amber-700 border-amber-300';
  return 'bg-rose-50 text-rose-700 border-rose-300';
};

const formatAiRecommendation = (rec) => {
  if (!rec) return 'Direkomendasikan';
  const lower = String(rec).toLowerCase();
  if (lower.includes('rekomend') || (lower.includes('recommend') && !lower.includes('not') && !lower.includes('un'))) return 'Direkomendasikan';
  if (lower.includes('timbang') || lower.includes('consider')) return 'Dipertimbangkan';
  if (lower.includes('kurang') || lower.includes('tidak') || lower.includes('not') || lower.includes('reject')) return 'Kurang Sesuai';
  return rec;
};

const formatStatus = (status) => {
  if (!status) return 'Dalam Proses';
  const s = String(status).toLowerCase().replace(/_/g, ' ');
  if (s.includes('in progress')) return 'Dalam Proses';
  if (s.includes('shortlist')) return 'Shortlisted';
  if (s.includes('reject')) return 'Ditolak';
  if (s.includes('hire')) return 'Diterima';
  return s.charAt(0).toUpperCase() + s.slice(1);
};

const getStatusBadge = (status) => {
  const s = String(status).toLowerCase();
  if (s.includes('hire') || s.includes('shortlist')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if (s.includes('reject')) return 'bg-rose-50 text-rose-700 border-rose-200';
  return 'bg-slate-50 text-slate-700 border-slate-200';
};

const resetJobFilter = () => {
  router.push({ path: '/admin/job-applications' });
};

const isUploadingCv = ref(false);

const openDetail = (app) => {
  selectedApp.value = app;
};

const handleCvUploadForApplicant = async (event) => {
  const file = event.target.files?.[0];
  if (!file || !selectedApp.value) return;

  if (file.size > 20 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'Ukuran File Terlalu Besar',
      text: 'Maksimal ukuran file CV adalah 20MB.',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  const formData = new FormData();
  formData.append('cv', file);

  isUploadingCv.value = true;
  Swal.fire({
    title: 'Mengunggah CV...',
    html: '<div class="text-xs text-slate-500 mt-2">Sedang menyimpan dokumen dan melakukan evaluasi kualifikasi...</div>',
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const res = await axios.post(`/rekrutmen/api/applications/${selectedApp.value.id}/upload-cv`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    isUploadingCv.value = false;
    if (res.data?.success) {
      selectedApp.value.resume_path = res.data.resume_path;
      selectedApp.value.resume_url = res.data.resume_url;
      if (res.data.ai_match_score !== undefined) {
        selectedApp.value.ai_match_score = res.data.ai_match_score;
        selectedApp.value.ai_recommendation = res.data.ai_recommendation;
        selectedApp.value.ai_summary = res.data.ai_summary;
        selectedApp.value.ai_analyzed_at = res.data.ai_analyzed_at;
      }

      const found = store.applications.find(a => a.id === selectedApp.value.id);
      if (found) {
        found.resume_path = res.data.resume_path;
        found.resume_url = res.data.resume_url;
        found.has_resume = true;
        found.ai_match_score = res.data.ai_match_score;
        found.ai_recommendation = res.data.ai_recommendation;
        found.ai_summary = res.data.ai_summary;
      }

      Swal.fire({
        icon: 'success',
        title: 'CV Berhasil Diunggah!',
        text: 'Dokumen CV asli pelamar telah tersimpan dan ditampilkan di pratinjau.',
        timer: 2200,
        showConfirmButton: false,
        iconColor: '#10b981',
        customClass: {
          popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
          title: 'text-sm font-bold text-slate-900',
        }
      });
    }
  } catch (err) {
    isUploadingCv.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Gagal Mengunggah CV',
      text: err.response?.data?.message || 'Terjadi kesalahan saat mengunggah file CV.',
      confirmButtonColor: '#e11d48',
    });
  }
};

const openAnalysisModal = (app) => {
  if (!app) return;
  analysisModalApp.value = app;
};

const startRescreening = async () => {
  isScreening.value = true;
  Swal.fire({
    title: 'Evaluasi Kualifikasi',
    html: '<div class="text-xs text-slate-500 mt-2 leading-relaxed">Sedang menganalisis dan mencocokkan data berkas seluruh pelamar...</div>',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    customClass: {
      popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
      title: 'text-sm font-bold text-slate-900',
    },
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const res = await store.batchAnalyzeWithAi(activeJobId.value);
    isScreening.value = false;
    Swal.fire({
      icon: 'success',
      title: 'Evaluasi Selesai',
      html: `<div class="text-xs text-slate-600 mt-1">${res.message || 'Evaluasi kualifikasi berhasil diperbarui.'}</div>`,
      timer: 2000,
      showConfirmButton: false,
      iconColor: '#10b981',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
      }
    });
  } catch (e) {
    isScreening.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      html: '<div class="text-xs text-slate-600 mt-1">Terjadi kesalahan saat memproses evaluasi kualifikasi.</div>',
      confirmButtonColor: '#739ec5',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
        confirmButton: 'px-4 py-2 rounded-xl text-xs font-bold'
      }
    });
  }
};

const rescreenSingleCandidate = async (app) => {
  if (!app) return;
  isScreening.value = true;
  Swal.fire({
    title: 'Mengevaluasi Pelamar',
    html: `<div class="text-xs text-slate-500 mt-2 leading-relaxed">Menganalisis data kualifikasi <b>${app.full_name}</b>...</div>`,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    customClass: {
      popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
      title: 'text-sm font-bold text-slate-900',
    },
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const res = await store.analyzeCandidateWithAi(app.id);
    isScreening.value = false;
    if (res.application && selectedApp.value && selectedApp.value.id === app.id) {
      selectedApp.value = { ...selectedApp.value, ...res.application };
    }
    Swal.fire({
      icon: 'success',
      title: 'Evaluasi Berhasil',
      html: `<div class="text-xs text-slate-600 mt-1">${res.message || `Evaluasi untuk "${app.full_name}" berhasil diperbarui.`}</div>`,
      timer: 2000,
      showConfirmButton: false,
      iconColor: '#10b981',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
      }
    });
  } catch (e) {
    isScreening.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      html: '<div class="text-xs text-slate-600 mt-1">Gagal mengevaluasi data pelamar.</div>',
      confirmButtonColor: '#739ec5',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
        confirmButton: 'px-4 py-2 rounded-xl text-xs font-bold'
      }
    });
  }
};

const handleDragStart = (app, event) => {
  if (event && event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(app.id));
  }
};

const handleDragEnd = () => {
  dragOverStageId.value = null;
};

const handleDragOver = (stageId) => {
  dragOverStageId.value = stageId;
};

const handleDragLeave = (stageId) => {
  if (dragOverStageId.value === stageId) {
    dragOverStageId.value = null;
  }
};

const handleDrop = async (stageId, event) => {
  dragOverStageId.value = null;
  const appId = event?.dataTransfer?.getData('text/plain');
  if (!appId) return;

  const app = store.applications.find(a => String(a.id) === String(appId));
  const currentStageId = app ? (app.current_stage_id || app.stage?.id || 1) : null;
  if (app && String(currentStageId) !== String(stageId)) {
    await moveCandidateStage(app, stageId);
  }
};

const moveCandidateStage = async (app, stageId) => {
  try {
    const res = await store.moveStage(app.id, stageId);
    if (res && res.success) {
      app.current_stage_id = parseInt(stageId);
      const targetStage = stages.value.find(s => String(s.id) === String(stageId));
      if (targetStage) {
        app.stage = { id: targetStage.id, name: targetStage.name, color: targetStage.color };
      }
      if (selectedApp.value && String(selectedApp.value.id) === String(app.id)) {
        selectedApp.value.current_stage_id = parseInt(stageId);
        if (targetStage) {
          selectedApp.value.stage = { id: targetStage.id, name: targetStage.name, color: targetStage.color };
        }
      }
      toastType.value = 'success';
      toastMessage.value = `Kandidat "${app.full_name}" dipindahkan ke tahap ${targetStage ? targetStage.name : 'baru'}.`;
      setTimeout(() => { toastMessage.value = null; }, 3000);
    }
  } catch (e) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memindahkan tahapan kandidat.';
  }
};

// Send Email Logic
const sendEmailModalApp = ref(null);
const emailTemplatesList = ref({});
const activeEmailTemplateKey = ref('psikotes');
const isSendingEmail = ref(false);

const emailForm = ref({
  subject: '',
  body_message: '',
  badge_text: 'Notifikasi Rekrutmen',
  info_box_title: 'Detail Informasi',
  action_url: '',
  action_label: '',
  schedule: '',
  venue_or_method: '',
  special_note: '',
  attachment: null,
  attachment_name: '',
});

const handleAttachmentUpload = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  if (file.size > 15 * 1024 * 1024) {
    Swal.fire({
      icon: 'warning',
      title: 'Ukuran File Terlalu Besar',
      text: 'Maksimal ukuran file dokumen adalah 15MB.',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  emailForm.value.attachment = file;
  emailForm.value.attachment_name = file.name;
};

const removeAttachment = () => {
  emailForm.value.attachment = null;
  emailForm.value.attachment_name = '';
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const fetchEmailTemplates = async () => {
  try {
    const res = await axios.get('/rekrutmen/api/settings/mail-templates');
    if (res.data?.templates) {
      emailTemplatesList.value = res.data.templates;
    }
  } catch (err) {
    console.error('Failed to fetch email templates', err);
  }
};

const openSendEmailModal = async (app) => {
  if (!app) return;
  isBulkMode.value = false;
  sendEmailModalApp.value = app;
  if (!selectedChannels.value.length) {
    selectedChannels.value = ['email', 'whatsapp'];
  }
  emailForm.value.attachment = null;
  emailForm.value.attachment_name = '';
  
  if (!Object.keys(emailTemplatesList.value).length) {
    await fetchEmailTemplates();
  }
  
  const stgName = (app.stage?.name || '').toLowerCase();
  let defaultKey = 'psikotes';
  if (stgName.includes('interview')) defaultKey = 'interview';
  else if (stgName.includes('offer')) defaultKey = 'offering';
  else if (stgName.includes('reject') || stgName.includes('tolak')) defaultKey = 'rejection';
  
  applyEmailTemplate(defaultKey);
};

const openBulkNotificationModal = async () => {
  if (!selectedAppIds.value.length) return;
  isBulkMode.value = true;
  
  // Use first selected app for template variable preview
  const firstApp = applications.value.find(a => a.id === selectedAppIds.value[0]) || applications.value[0];
  sendEmailModalApp.value = firstApp || { full_name: 'Pelamar Terpilih', email: 'multi@pelamar' };
  
  if (!selectedChannels.value.length) {
    selectedChannels.value = ['email', 'whatsapp'];
  }
  emailForm.value.attachment = null;
  emailForm.value.attachment_name = '';

  if (!Object.keys(emailTemplatesList.value).length) {
    await fetchEmailTemplates();
  }

  applyEmailTemplate('psikotes');
};

const closeNotificationModal = () => {
  sendEmailModalApp.value = null;
  isBulkMode.value = false;
};

const applyEmailTemplate = (key) => {
  activeEmailTemplateKey.value = key;
  const tpl = emailTemplatesList.value[key];
  if (!tpl || !sendEmailModalApp.value) return;

  const app = sendEmailModalApp.value;
  const name = isBulkMode.value ? '{nama_pelamar}' : (app.full_name || 'Pelamar');
  const pos = app.job_posting?.title || 'Posisi Lowongan';
  const comp = 'OCEAN SPACE';
  const loc = app.job_posting?.location || 'Indonesia';

  const replaceTags = (text) => {
    if (!text) return '';
    if (isBulkMode.value) {
      return text
        .replaceAll('{posisi}', pos)
        .replaceAll('{perusahaan}', comp)
        .replaceAll('{lokasi}', loc);
    }
    return text
      .replaceAll('{nama_pelamar}', name)
      .replaceAll('{posisi}', pos)
      .replaceAll('{perusahaan}', comp)
      .replaceAll('{lokasi}', loc);
  };

  emailForm.value.subject = replaceTags(tpl.subject);
  emailForm.value.body_message = replaceTags(tpl.body);
  emailForm.value.badge_text = tpl.badge || 'Notifikasi Rekrutmen';
  emailForm.value.info_box_title = tpl.info_title || 'Detail Informasi';
  emailForm.value.action_label = tpl.action_label || '';
  emailForm.value.special_note = tpl.default_note || '';

  if (key === 'interview') {
    emailForm.value.venue_or_method = 'Online (Google Meet)';
    emailForm.value.schedule = '';
    emailForm.value.action_url = '';
  } else if (key === 'psikotes') {
    emailForm.value.venue_or_method = 'Online Assessment';
    emailForm.value.schedule = 'Batas Pengerjaan: 3 hari kerja';
    emailForm.value.action_url = '';
  } else if (key === 'offering') {
    emailForm.value.venue_or_method = loc;
    emailForm.value.schedule = '';
    emailForm.value.action_url = '';
  } else {
    emailForm.value.action_url = '';
    emailForm.value.schedule = '';
    emailForm.value.venue_or_method = '';
  }
};

const insertTag = (tag) => {
  if (!emailForm.value.body_message) {
    emailForm.value.body_message = tag;
  } else {
    emailForm.value.body_message += ' ' + tag;
  }
};

const executeSendNotification = async () => {
  if (!sendEmailModalApp.value) return;

  if (!selectedChannels.value.length) {
    Swal.fire({
      icon: 'warning',
      title: 'Pilih Kanal Notifikasi',
      text: 'Harap centang minimal salah satu kanal: Email atau WhatsApp.',
      confirmButtonColor: '#0c2340',
    });
    return;
  }

  isSendingEmail.value = true;

  try {
    const formData = new FormData();
    formData.append('subject', emailForm.value.subject || '');
    formData.append('body_message', emailForm.value.body_message || '');
    formData.append('schedule', emailForm.value.schedule || '');
    formData.append('venue_or_method', emailForm.value.venue_or_method || '');
    formData.append('action_url', emailForm.value.action_url || '');
    formData.append('action_label', emailForm.value.action_label || '');
    formData.append('special_note', emailForm.value.special_note || '');
    formData.append('badge_text', emailForm.value.badge_text || '');
    formData.append('info_box_title', emailForm.value.info_box_title || '');

    selectedChannels.value.forEach((ch) => {
      formData.append('channels[]', ch);
    });

    if (emailForm.value.attachment) {
      formData.append('attachment', emailForm.value.attachment);
    }

    if (isBulkMode.value) {
      selectedAppIds.value.forEach((id) => {
        formData.append('application_ids[]', id);
      });

      const res = await axios.post('/rekrutmen/api/applications/bulk-send-notification', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      isSendingEmail.value = false;
      closeNotificationModal();
      const count = selectedAppIds.value.length;
      selectedAppIds.value = [];

      const stats = res.data.stats || {};
      let recapHtml = `
        <div class="text-xs text-left space-y-1.5 mt-2 bg-slate-50 p-3 rounded-lg border border-slate-200">
          <div><strong>Total Sasaran:</strong> ${stats.total || count} pelamar</div>
      `;

      if (selectedChannels.value.includes('email')) {
        recapHtml += `
          <div class="text-blue-700">📧 <strong>Email:</strong> ${stats.email_success || 0} berhasil terkirim (Gagal: ${stats.email_failed || 0}, Email Kosong: ${stats.skipped_no_email || 0})</div>
        `;
      }

      if (selectedChannels.value.includes('whatsapp')) {
        recapHtml += `
          <div class="text-emerald-700">💬 <strong>WhatsApp (WAG Hub):</strong> ${stats.whatsapp_success || 0} berhasil terkirim (Gagal: ${stats.whatsapp_failed || 0})</div>
        `;
      }

      recapHtml += `</div>`;

      Swal.fire({
        icon: 'success',
        title: 'Notifikasi Massal Berhasil Dikirim!',
        html: recapHtml,
        confirmButtonColor: '#0c2340',
        customClass: {
          popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
          title: 'text-sm font-bold text-slate-900',
        }
      });
    } else {
      // Single candidate notification
      const app = sendEmailModalApp.value;

      const res = await axios.post(`/rekrutmen/api/applications/${app.id}/send-notification`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });

      isSendingEmail.value = false;
      closeNotificationModal();

      Swal.fire({
        icon: 'success',
        title: 'Notifikasi Berhasil Terkirim!',
        html: `<div class="text-xs text-slate-600 mt-1">${res.data.message || 'Notifikasi berhasil dikirimkan ke kandidat.'}</div>`,
        timer: 3000,
        showConfirmButton: false,
        iconColor: '#10b981',
        customClass: {
          popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
          title: 'text-sm font-bold text-slate-900',
        }
      });
    }
  } catch (err) {
    isSendingEmail.value = false;
    Swal.fire({
      icon: 'error',
      title: 'Gagal Mengirim Notifikasi',
      text: err.response?.data?.message || 'Terjadi kesalahan saat mengirim notifikasi.',
      confirmButtonColor: '#e11d48',
      customClass: {
        popup: 'rounded-2xl border border-slate-100 shadow-2xl p-6 font-sans',
        title: 'text-sm font-bold text-slate-900',
        confirmButton: 'px-4 py-2 rounded-xl text-xs font-bold'
      }
    });
  }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
