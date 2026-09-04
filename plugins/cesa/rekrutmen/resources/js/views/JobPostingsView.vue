<template>
  <div class="space-y-6 pb-12">
    <!-- Top Header: Title & Quick Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-xl font-semibold text-zinc-900 tracking-tight">Lowongan Pekerjaan</h1>
          <Badge variant="outline" class="font-mono text-[11px] text-zinc-600 bg-zinc-50 border-zinc-200">
            {{ postings.length }} Posisi
          </Badge>
        </div>
        <p class="text-xs text-zinc-500 mt-1">
          Kelola lowongan aktif, pantau portal karir, dan telusuri kandidat pelamar yang masuk
        </p>
      </div>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          @click="refreshData"
          :disabled="isRefreshing"
          class="text-xs h-8 text-zinc-700 hover:text-zinc-900"
        >
          <RotateCw :class="['w-3.5 h-3.5 mr-1.5', isRefreshing ? 'animate-spin' : '']" />
          <span>Segarkan</span>
        </Button>

        <!-- View Mode Segmented Control -->
        <div class="inline-flex items-center bg-zinc-100 p-0.5 rounded-lg border border-zinc-200">
          <button
            type="button"
            @click="viewMode = 'grid'"
            :class="[
              'px-2.5 py-1 rounded-md text-xs font-medium flex items-center gap-1.5 transition-all cursor-pointer select-none',
              viewMode === 'grid'
                ? 'bg-white text-zinc-900 shadow-2xs font-semibold'
                : 'text-zinc-600 hover:text-zinc-900'
            ]"
          >
            <LayoutGrid class="w-3.5 h-3.5" />
            <span>Grid</span>
          </button>
          <button
            type="button"
            @click="viewMode = 'table'"
            :class="[
              'px-2.5 py-1 rounded-md text-xs font-medium flex items-center gap-1.5 transition-all cursor-pointer select-none',
              viewMode === 'table'
                ? 'bg-white text-zinc-900 shadow-2xs font-semibold'
                : 'text-zinc-600 hover:text-zinc-900'
            ]"
          >
            <ListFilter class="w-3.5 h-3.5" />
            <span>Tabel</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Alert / Toast Banner -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform -translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform -translate-y-2 opacity-0"
    >
      <div
        v-if="toastMessage"
        :class="[
          'p-3 rounded-lg border flex items-center justify-between text-xs font-medium shadow-2xs',
          toastType === 'success'
            ? 'bg-emerald-50/70 border-emerald-200 text-emerald-800'
            : 'bg-rose-50/70 border-rose-200 text-rose-800'
        ]"
      >
        <div class="flex items-center gap-2">
          <CheckCircle2 v-if="toastType === 'success'" class="w-4 h-4 text-emerald-600 shrink-0" />
          <AlertCircle v-else class="w-4 h-4 text-rose-600 shrink-0" />
          <span>{{ toastMessage }}</span>
        </div>
        <button
          @click="toastMessage = null"
          class="text-zinc-400 hover:text-zinc-700 font-bold px-1 rounded hover:bg-zinc-200/50"
        >
          &times;
        </button>
      </div>
    </transition>

    <!-- KPI Summary Metrics (New York Style Clean Cards) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Total Lowongan</span>
          <Briefcase class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-zinc-900">{{ postings.length }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            {{ totalNeededPositions }} total kuota posisi dibutuhkan
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Tayang Aktif</span>
          <CheckCircle2 class="w-4 h-4 text-emerald-600" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-emerald-700">{{ publishedCount }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Tersedia untuk pelamar publik
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Draft Lowongan</span>
          <Clock class="w-4 h-4 text-amber-500" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-amber-700">{{ draftCount }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Belum dipublikasikan ke publik
          </p>
        </CardContent>
      </Card>

      <Card class="hover:border-zinc-300">
        <CardHeader class="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
          <span class="text-xs font-medium text-zinc-500">Total Pelamar</span>
          <Users class="w-4 h-4 text-zinc-400" />
        </CardHeader>
        <CardContent class="p-4 pt-0">
          <div class="text-2xl font-bold tracking-tight text-zinc-900">{{ totalApplicationsCount }}</div>
          <p class="text-[11px] text-zinc-500 mt-0.5">
            Akumulasi lamaran kandidat
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- Filters & Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-3 bg-white rounded-xl border border-zinc-200 shadow-2xs">
      <!-- Left: Status Tabs Filter -->
      <div class="flex items-center gap-1 overflow-x-auto no-scrollbar pb-1 md:pb-0">
        <button
          type="button"
          @click="statusFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer shrink-0 select-none',
            statusFilter === 'all'
              ? 'bg-zinc-900 text-zinc-50 shadow-2xs font-semibold'
              : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'
          ]"
        >
          Semua <span class="ml-1 opacity-70">({{ postings.length }})</span>
        </button>

        <button
          type="button"
          @click="statusFilter = 'published'"
          :class="[
            'px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer shrink-0 select-none',
            statusFilter === 'published'
              ? 'bg-emerald-600 text-white shadow-2xs font-semibold'
              : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'
          ]"
        >
          Tayang Aktif <span class="ml-1 opacity-70">({{ publishedCount }})</span>
        </button>

        <button
          type="button"
          @click="statusFilter = 'draft'"
          :class="[
            'px-3 py-1.5 rounded-md text-xs font-medium transition-all cursor-pointer shrink-0 select-none',
            statusFilter === 'draft'
              ? 'bg-zinc-800 text-zinc-50 shadow-2xs font-semibold'
              : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100'
          ]"
        >
          Draft <span class="ml-1 opacity-70">({{ draftCount }})</span>
        </button>
      </div>

      <!-- Right: Search & Company Filter -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <!-- Company Dropdown Filter -->
        <div class="relative min-w-[190px]">
          <select
            v-model="companyFilter"
            class="w-full h-8 bg-zinc-50 border border-zinc-200 rounded-md pl-3 pr-8 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-950 focus:border-zinc-950 transition-colors appearance-none cursor-pointer"
          >
            <option value="all">Semua Perusahaan</option>
            <option v-for="comp in companies" :key="comp.id" :value="String(comp.id)">
              {{ comp.name }}
            </option>
          </select>
          <ChevronDown class="w-3.5 h-3.5 text-zinc-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>

        <!-- Search Input -->
        <div class="relative w-full sm:w-64">
          <Search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-zinc-400" />
          <Input
            v-model="searchQuery"
            type="text"
            placeholder="Cari posisi atau lokasi..."
            class="pl-8 h-8 text-xs bg-zinc-50 focus:bg-white"
          />
          <button
            v-if="searchQuery"
            type="button"
            @click="searchQuery = ''"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs font-bold"
          >
            &times;
          </button>
        </div>
      </div>
    </div>

    <!-- SKELETON LOADING STATE -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <Card v-for="i in 6" :key="i" class="p-5 space-y-4">
        <div class="flex items-center justify-between">
          <Skeleton class="h-4 w-28" />
          <Skeleton class="h-5 w-16 rounded-full" />
        </div>
        <div class="space-y-2">
          <Skeleton class="h-5 w-4/5" />
          <Skeleton class="h-3 w-1/2" />
        </div>
        <div class="pt-2 flex items-center gap-2">
          <Skeleton class="h-4 w-24" />
          <Skeleton class="h-4 w-20" />
        </div>
        <div class="pt-4 border-t border-zinc-100 flex items-center justify-between">
          <Skeleton class="h-3 w-24" />
          <Skeleton class="h-7 w-24 rounded-md" />
        </div>
      </Card>
    </div>

    <!-- GRID VIEW: Shadcn New York Style Cards -->
    <div
      v-else-if="viewMode === 'grid' && filteredPostings.length"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
    >
      <Card
        v-for="job in filteredPostings"
        :key="job.id"
        class="flex flex-col justify-between hover:border-zinc-300 hover:shadow-sm transition-all duration-150 group"
      >
        <CardHeader class="p-5 pb-3.5 space-y-3">
          <!-- Top Row: Company Badge & Status Badge & Actions -->
          <div class="flex items-start justify-between gap-2">
            <div class="flex flex-col gap-1 min-w-0">
              <span class="text-[11px] font-medium text-zinc-500 truncate block">
                {{ job.company_name || 'PT Complete Selular Group' }}
              </span>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
              <!-- Publish Status Badge -->
              <Badge
                :variant="job.is_published ? 'success' : 'secondary'"
                class="cursor-pointer select-none text-[10px] px-2 py-0.5"
                @click="togglePublish(job)"
                :title="job.is_published ? 'Klik untuk jadikan Draft' : 'Klik untuk Tayangkan'"
              >
                <span :class="['w-1.5 h-1.5 rounded-full mr-0.5', job.is_published ? 'bg-emerald-500' : 'bg-zinc-400']"></span>
                <span>{{ job.is_published ? 'Tayang' : 'Draft' }}</span>
              </Badge>

              <!-- Action Dropdown Menu -->
              <DropdownMenu>
                <DropdownMenuTrigger>
                  <button
                    type="button"
                    class="h-7 w-7 flex items-center justify-center rounded-md text-zinc-400 hover:text-zinc-800 hover:bg-zinc-100 transition-colors cursor-pointer"
                  >
                    <MoreHorizontal class="w-4 h-4" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                  <DropdownMenuItem @click="openEditSheet(job)">
                    <Edit3 class="w-3.5 h-3.5 mr-2 text-zinc-500" />
                    <span>Edit Lowongan</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="togglePublish(job)">
                    <Eye v-if="!job.is_published" class="w-3.5 h-3.5 mr-2 text-emerald-600" />
                    <EyeOff v-else class="w-3.5 h-3.5 mr-2 text-zinc-500" />
                    <span>{{ job.is_published ? 'Tarik ke Draft' : 'Tayangkan Lowongan' }}</span>
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="copyJobLink(job)">
                    <Copy class="w-3.5 h-3.5 mr-2 text-zinc-500" />
                    <span>Salin Info Lowongan</span>
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>

          <!-- Job Title -->
          <div>
            <h2
              @click="openEditSheet(job)"
              class="text-sm font-semibold text-zinc-900 group-hover:text-blue-900 transition-colors cursor-pointer leading-snug tracking-tight"
            >
              {{ job.title }}
            </h2>
            <div class="flex items-center gap-1.5 text-[11px] text-zinc-500 mt-1.5">
              <MapPin class="w-3.5 h-3.5 text-zinc-400 shrink-0" />
              <span class="truncate">{{ job.location || 'Indonesia' }}</span>
            </div>
          </div>

          <!-- Metadata Chips -->
          <div class="flex flex-wrap items-center gap-2 pt-1 text-[11px] text-zinc-600">
            <div class="inline-flex items-center gap-1 bg-zinc-100/70 border border-zinc-200/80 px-2 py-0.5 rounded text-zinc-700">
              <Briefcase class="w-3 h-3 text-zinc-400" />
              <span>{{ job.needed_count || 1 }} Kuota Posisi</span>
            </div>
            <div class="inline-flex items-center gap-1 bg-zinc-100/70 border border-zinc-200/80 px-2 py-0.5 rounded text-zinc-700">
              <Users class="w-3 h-3 text-zinc-400" />
              <span>{{ job.applications_count || 0 }} Pelamar</span>
            </div>
          </div>
        </CardHeader>

        <!-- Card Footer -->
        <CardFooter class="p-5 pt-3 border-t border-zinc-100/80 flex items-center justify-between text-xs text-zinc-500 mt-1">
          <div class="flex items-center gap-1.5 text-[11px] text-zinc-500">
            <Calendar class="w-3.5 h-3.5 text-zinc-400 shrink-0" />
            <span>Tutup: <strong class="text-zinc-700 font-medium">{{ job.closing_date_formatted || '-' }}</strong></span>
          </div>

          <router-link
            :to="{ path: '/admin/job-applications', query: { job_id: job.id } }"
            class="inline-flex"
          >
            <Button variant="outline" size="xs" class="font-medium text-zinc-700 hover:text-zinc-900">
              <span>Pelamar</span>
              <ArrowRight class="w-3 h-3 ml-0.5" />
            </Button>
          </router-link>
        </CardFooter>
      </Card>
    </div>

    <!-- TABLE VIEW: Shadcn Table Component -->
    <div
      v-else-if="viewMode === 'table' && filteredPostings.length"
      class="bg-white rounded-xl border border-zinc-200 shadow-2xs overflow-hidden"
    >
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-[30%]">Posisi Lowongan</TableHead>
            <TableHead class="w-[25%]">Perusahaan & Lokasi</TableHead>
            <TableHead class="w-[12%]">Status</TableHead>
            <TableHead class="w-[13%]">Batas Waktu</TableHead>
            <TableHead class="w-[10%]">Pelamar</TableHead>
            <TableHead class="w-[10%] text-right">Aksi</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="job in filteredPostings" :key="job.id" class="group">
            <TableCell class="py-3.5">
              <div
                @click="openEditSheet(job)"
                class="font-semibold text-zinc-900 hover:text-blue-900 cursor-pointer text-xs transition-colors"
              >
                {{ job.title }}
              </div>
              <div class="text-[11px] text-zinc-400 font-mono mt-0.5">
                #{{ job.id }} &bull; {{ job.needed_count || 1 }} kuota
              </div>
            </TableCell>

            <TableCell class="py-3.5">
              <div class="font-medium text-zinc-800 text-xs truncate max-w-[240px]">
                {{ job.company_name || 'PT Complete Selular Group' }}
              </div>
              <div class="text-[11px] text-zinc-400 flex items-center gap-1 mt-0.5">
                <MapPin class="w-3 h-3 text-zinc-400 shrink-0" />
                <span>{{ job.location || 'Indonesia' }}</span>
              </div>
            </TableCell>

            <TableCell class="py-3.5">
              <Badge
                :variant="job.is_published ? 'success' : 'secondary'"
                class="cursor-pointer select-none text-[10px] px-2 py-0.5"
                @click="togglePublish(job)"
              >
                <span :class="['w-1.5 h-1.5 rounded-full mr-0.5', job.is_published ? 'bg-emerald-500' : 'bg-zinc-400']"></span>
                <span>{{ job.is_published ? 'Tayang' : 'Draft' }}</span>
              </Badge>
            </TableCell>

            <TableCell class="py-3.5 text-zinc-600 text-xs">
              {{ job.closing_date_formatted || '-' }}
            </TableCell>

            <TableCell class="py-3.5 font-semibold text-zinc-900 text-xs">
              {{ job.applications_count || 0 }}
            </TableCell>

            <TableCell class="py-3.5 text-right whitespace-nowrap">
              <div class="inline-flex items-center gap-1.5">
                <router-link :to="{ path: '/admin/job-applications', query: { job_id: job.id } }">
                  <Button variant="ghost" size="xs" class="h-7 text-zinc-600 hover:text-zinc-900">
                    Pelamar
                  </Button>
                </router-link>

                <Button
                  variant="outline"
                  size="xs"
                  @click="openEditSheet(job)"
                  class="h-7 text-zinc-700"
                >
                  Edit
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- EMPTY STATE -->
    <Card v-else class="p-12 text-center border-dashed border-zinc-300">
      <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 mb-3">
        <Briefcase class="h-5 w-5" />
      </div>
      <h3 class="text-sm font-semibold text-zinc-900">Tidak ada lowongan ditemukan</h3>
      <p class="text-xs text-zinc-500 mt-1 max-w-sm mx-auto">
        Tidak ada data lowongan yang sesuai dengan kriteria filter atau pencarian Anda.
      </p>
      <div class="mt-4 flex items-center justify-center gap-2">
        <Button
          variant="outline"
          size="sm"
          @click="resetFilters"
        >
          Reset Filter
        </Button>
      </div>
    </Card>

    <!-- SLIDE-OVER SHEET: Linear/Stripe Style Drawer -->
    <Sheet :open="!!editingJob" @update:open="(val) => { if (!val) editingJob = null; }">
      <SheetContent class="sm:max-w-xl">
        <SheetHeader>
          <div class="flex items-center gap-2">
            <SheetTitle>Edit Lowongan Pekerjaan</SheetTitle>
            <Badge v-if="selectedCompanyName" variant="secondary" class="text-[10px] font-normal truncate max-w-[200px]">
              {{ selectedCompanyName }}
            </Badge>
          </div>
          <SheetDescription>
            Perbarui informasi posisi, kualifikasi, kuota, dan status tayang portal karir.
          </SheetDescription>
        </SheetHeader>

        <!-- Form Body with Comfortable Vertical Scroll -->
        <form @submit.prevent="saveEditJob" class="flex flex-col flex-1 overflow-hidden">
          <div class="p-6 space-y-5 overflow-y-auto flex-1 text-xs text-zinc-900">
            <!-- Section 1: General Info -->
            <div class="space-y-4">
              <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">
                Informasi Utama
              </div>

              <div>
                <label class="block font-medium text-zinc-800 mb-1.5">Judul Posisi <span class="text-red-500">*</span></label>
                <Input
                  v-model="editForm.title"
                  type="text"
                  required
                  placeholder="Contoh: Store Leader, Admin GA, dsb."
                  class="h-9"
                />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                  <label class="block font-medium text-zinc-800 mb-1.5">Perusahaan / PT</label>
                  <div class="relative">
                    <select
                      v-model="editForm.company_id"
                      class="w-full h-9 bg-white border border-zinc-200 rounded-md px-3 text-xs text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-950 focus:border-zinc-950 appearance-none pr-8 cursor-pointer transition-colors"
                    >
                      <option :value="null">-- Gunakan Default (PT Complete Selular Group) --</option>
                      <option v-for="c in companies" :key="c.id" :value="c.id">
                        {{ c.name }}
                      </option>
                    </select>
                    <ChevronDown class="w-3.5 h-3.5 text-zinc-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
                </div>

                <div>
                  <label class="block font-medium text-zinc-800 mb-1.5">Lokasi Kerja</label>
                  <Input
                    v-model="editForm.location"
                    type="text"
                    placeholder="Kota Cirebon, Jakarta, dsb."
                    class="h-9"
                  />
                </div>
              </div>
            </div>

            <!-- Section 2: Quota & Dates -->
            <div class="space-y-4 pt-3 border-t border-zinc-100">
              <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">
                Batas Waktu & Publikasi
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                  <label class="block font-medium text-zinc-800 mb-1.5">Batas Waktu Lamaran</label>
                  <Input
                    v-model="editForm.closing_date"
                    type="date"
                    class="h-9"
                  />
                </div>

                <div>
                  <label class="block font-medium text-zinc-800 mb-1.5">Status Publikasi</label>
                  <div class="flex items-center gap-4 h-9">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs">
                      <input
                        type="radio"
                        name="publication_state"
                        value="active"
                        v-model="editForm.publication_state"
                        class="accent-zinc-900 cursor-pointer"
                      />
                      <span>Tayang Aktif</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs">
                      <input
                        type="radio"
                        name="publication_state"
                        value="draft"
                        v-model="editForm.publication_state"
                        class="accent-zinc-900 cursor-pointer"
                      />
                      <span>Draft</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 3: Job Description & Qualifications -->
            <div class="space-y-4 pt-3 border-t border-zinc-100">
              <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">
                Deskripsi & Persyaratan
              </div>

              <div>
                <label class="block font-medium text-zinc-800 mb-1.5">Deskripsi Tugas & Tanggung Jawab</label>
                <textarea
                  v-model="editForm.description"
                  rows="4"
                  placeholder="• Tugas dan tanggung jawab harian..."
                  class="w-full bg-white border border-zinc-200 rounded-md p-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-950 focus:border-zinc-950 leading-relaxed transition-colors resize-none"
                ></textarea>
              </div>

              <div>
                <label class="block font-medium text-zinc-800 mb-1.5">Kualifikasi & Persyaratan</label>
                <textarea
                  v-model="editForm.requirements"
                  rows="4"
                  placeholder="• Minimal pendidikan...&#10;• Pengalaman kerja...&#10;• Kemampuan teknis..."
                  class="w-full bg-white border border-zinc-200 rounded-md p-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-950 focus:border-zinc-950 leading-relaxed transition-colors resize-none"
                ></textarea>
              </div>
            </div>

            <!-- Section 4: Banner / Poster Thumbnail -->
            <div class="space-y-3 pt-3 border-t border-zinc-100">
              <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">
                Media Banner Lowongan
              </div>

              <div class="border border-zinc-200 rounded-lg p-3 flex items-center justify-between gap-3 bg-zinc-50/60">
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-12 h-12 rounded-md bg-white border border-zinc-200 text-zinc-400 flex items-center justify-center shrink-0 overflow-hidden shadow-2xs">
                    <img
                      v-if="(thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved"
                      :src="thumbnailPreview || editForm.thumbnail_url"
                      alt="Banner Lowongan"
                      class="w-full h-full object-cover"
                      @error="editForm.thumbnail_url = null"
                    />
                    <ImageIcon v-else class="w-5 h-5 text-zinc-400 stroke-[1.5]" />
                  </div>
                  <div class="min-w-0">
                    <span class="text-xs font-medium text-zinc-800 block truncate">
                      {{ (thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved ? (thumbnailFileName || 'Banner Terpasang') : 'Belum ada gambar terpilih' }}
                    </span>
                    <span class="text-[11px] text-zinc-400 block mt-0.5">JPG, PNG, WEBP (Maksimal 5MB)</span>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <label class="cursor-pointer">
                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 transition-colors">
                      {{ (thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved ? 'Ganti' : 'Pilih File' }}
                    </span>
                    <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="handleThumbnailChange" />
                  </label>

                  <Button
                    v-if="(thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved"
                    type="button"
                    variant="ghost"
                    size="xs"
                    @click="removeThumbnail"
                    class="text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                  >
                    Hapus
                  </Button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sheet Action Footer -->
          <SheetFooter>
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="editingJob = null"
            >
              Batal
            </Button>
            <Button
              type="submit"
              size="sm"
              variant="default"
              :disabled="isSubmitting"
              class="bg-zinc-900 hover:bg-zinc-800 text-white min-w-[130px]"
            >
              <RotateCw v-if="isSubmitting" class="w-3.5 h-3.5 mr-1.5 animate-spin" />
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </Button>
          </SheetFooter>
        </form>
      </SheetContent>
    </Sheet>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRekrutmenStore } from '../stores/rekrutmen';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// Shadcn-Vue UI Components
import { Button } from '../components/ui/button';
import { Badge } from '../components/ui/badge';
import { Card, CardHeader, CardContent, CardFooter } from '../components/ui/card';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '../components/ui/table';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator } from '../components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetFooter } from '../components/ui/sheet';
import { Skeleton } from '../components/ui/skeleton';
import { Input } from '../components/ui/input';

// Icons
import {
  Search,
  MapPin,
  Users,
  Briefcase,
  FileText,
  Edit3,
  ArrowRight,
  LayoutGrid,
  ListFilter,
  CheckCircle2,
  AlertCircle,
  Calendar,
  Clock,
  Image as ImageIcon,
  ChevronDown,
  RotateCw,
  MoreHorizontal,
  Eye,
  EyeOff,
  Copy
} from 'lucide-vue-next';

const store = useRekrutmenStore();

const isLoading = ref(true);
const isRefreshing = ref(false);
const viewMode = ref('grid');
const statusFilter = ref('all');
const companyFilter = ref('all');
const searchQuery = ref('');
const editingJob = ref(null);
const isSubmitting = ref(false);
const toastMessage = ref(null);
const toastType = ref('success');

const thumbnailFile = ref(null);
const thumbnailPreview = ref(null);
const thumbnailFileName = ref('');
const isThumbnailRemoved = ref(false);

const editForm = ref({
  title: '',
  company_id: null,
  location: '',
  description: '',
  requirements: '',
  closing_date: '',
  publication_state: 'active',
  thumbnail_url: null,
});

onMounted(async () => {
  isLoading.value = true;
  try {
    await Promise.all([
      store.fetchPostings('', false),
      store.fetchCompanies(),
    ]);
  } catch (e) {
    console.error('Error fetching job postings data:', e);
  } finally {
    isLoading.value = false;
  }
});

const refreshData = async () => {
  isRefreshing.value = true;
  try {
    await Promise.all([
      store.fetchPostings('', false),
      store.fetchCompanies(),
    ]);
    toastType.value = 'success';
    toastMessage.value = 'Data lowongan berhasil disegarkan.';
    setTimeout(() => { toastMessage.value = null; }, 2500);
  } catch (e) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memperbarui data lowongan.';
  } finally {
    isRefreshing.value = false;
  }
};

const postings = computed(() => store.postings || []);
const companies = computed(() => store.companies || []);

const selectedCompanyName = computed(() => {
  if (editForm.value.company_id) {
    const found = companies.value.find(c => String(c.id) === String(editForm.value.company_id));
    if (found) return found.name;
  }
  return editingJob.value?.company_name || 'PT Complete Selular Group';
});

const publishedCount = computed(() => postings.value.filter(p => p.is_published).length);
const draftCount = computed(() => postings.value.filter(p => !p.is_published).length);

const totalApplicationsCount = computed(() => {
  return postings.value.reduce((acc, job) => acc + (Number(job.applications_count) || 0), 0);
});

const totalNeededPositions = computed(() => {
  return postings.value.reduce((acc, job) => acc + (Number(job.needed_count) || 1), 0);
});

const filteredPostings = computed(() => {
  let list = postings.value;

  if (statusFilter.value === 'published') {
    list = list.filter(p => p.is_published);
  } else if (statusFilter.value === 'draft') {
    list = list.filter(p => !p.is_published);
  }

  if (companyFilter.value !== 'all') {
    list = list.filter(p => String(p.company_id) === String(companyFilter.value));
  }

  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(p =>
    (p.title && p.title.toLowerCase().includes(q)) ||
    (p.company_name && p.company_name.toLowerCase().includes(q)) ||
    (p.location && p.location.toLowerCase().includes(q)) ||
    (p.description && p.description.toLowerCase().includes(q)) ||
    (p.requirements && p.requirements.toLowerCase().includes(q))
  );
});

const resetFilters = () => {
  statusFilter.value = 'all';
  companyFilter.value = 'all';
  searchQuery.value = '';
};

const togglePublish = async (job) => {
  try {
    const res = await store.togglePublishPosting(job.id);
    if (res.success) {
      toastType.value = 'success';
      toastMessage.value = res.message || 'Status publikasi lowongan berhasil diubah.';
      setTimeout(() => { toastMessage.value = null; }, 3000);
    }
  } catch (err) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memperbarui status publikasi.';
  }
};

const copyJobLink = async (job) => {
  const textToCopy = `Lowongan: ${job.title} di ${job.company_name || 'PT Complete Selular Group'} (${job.location || 'Indonesia'}). Batas penutupan: ${job.closing_date_formatted || '-'}`;
  try {
    await navigator.clipboard.writeText(textToCopy);
    toastType.value = 'success';
    toastMessage.value = `Info lowongan "${job.title}" disalin ke clipboard.`;
    setTimeout(() => { toastMessage.value = null; }, 3000);
  } catch (e) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal menyalin link lowongan.';
  }
};

const openEditSheet = (job) => {
  editingJob.value = job;
  thumbnailFile.value = null;
  thumbnailPreview.value = null;
  thumbnailFileName.value = job.thumbnail_path ? job.thumbnail_path.split('/').pop() : '';
  isThumbnailRemoved.value = false;
  editForm.value = {
    title: job.title || '',
    company_id: job.company_id || null,
    location: job.location || '',
    description: job.description || '',
    requirements: job.requirements || '',
    closing_date: job.closing_date ? job.closing_date.split('T')[0] : '',
    publication_state: job.is_published ? 'active' : 'draft',
    thumbnail_url: job.thumbnail_url || null,
  };
};

const handleThumbnailChange = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  thumbnailFile.value = file;
  thumbnailFileName.value = file.name;
  isThumbnailRemoved.value = false;
  const reader = new FileReader();
  reader.onload = (event) => {
    thumbnailPreview.value = event.target.result;
  };
  reader.readAsDataURL(file);
};

const removeThumbnail = () => {
  thumbnailFile.value = null;
  thumbnailPreview.value = null;
  thumbnailFileName.value = '';
  isThumbnailRemoved.value = true;
};

const saveEditJob = async () => {
  if (!editingJob.value) return;

  const result = await Swal.fire({
    title: 'Konfirmasi Perubahan',
    text: 'Simpan perubahan data lowongan pekerjaan ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Simpan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#18181b',
    cancelButtonColor: '#71717a',
    reverseButtons: true,
    customClass: {
      popup: 'rounded-xl border border-zinc-200 shadow-lg text-xs',
      title: 'text-sm font-semibold text-zinc-900',
      htmlContainer: 'text-xs text-zinc-500',
      confirmButton: 'rounded-md px-3.5 py-1.5 text-xs font-medium',
      cancelButton: 'rounded-md px-3.5 py-1.5 text-xs font-medium',
    },
  });

  if (!result.isConfirmed) return;

  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append('title', editForm.value.title);
    if (editForm.value.company_id) {
      formData.append('company_id', editForm.value.company_id);
    } else {
      formData.append('company_id', '');
    }
    formData.append('location', editForm.value.location || '');
    formData.append('description', editForm.value.description || '');
    formData.append('requirements', editForm.value.requirements || '');
    formData.append('closing_date', editForm.value.closing_date || '');
    formData.append('is_published', editForm.value.publication_state === 'active' ? '1' : '0');

    if (thumbnailFile.value) {
      formData.append('thumbnail', thumbnailFile.value);
    }
    if (isThumbnailRemoved.value) {
      formData.append('remove_thumbnail', '1');
    }

    const res = await store.updateJobPosting(editingJob.value.id, formData);
    if (res.success) {
      editingJob.value = null;
      toastType.value = 'success';
      toastMessage.value = res.message || 'Lowongan pekerjaan berhasil diperbarui.';
      setTimeout(() => { toastMessage.value = null; }, 3000);
    }
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menyimpan',
      text: err.response?.data?.message || 'Terjadi kesalahan saat menyimpan perubahan lowongan.',
      icon: 'error',
      confirmButtonText: 'Tutup',
      confirmButtonColor: '#e11d48',
      customClass: {
        popup: 'rounded-xl border border-zinc-200 shadow-lg text-xs',
        confirmButton: 'rounded-md px-3.5 py-1.5 text-xs font-medium',
      },
    });
  } finally {
    isSubmitting.value = false;
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
