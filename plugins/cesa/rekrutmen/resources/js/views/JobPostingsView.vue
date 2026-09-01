<template>
  <div class="space-y-6">
    <!-- Page Header & Action Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Lowongan Pekerjaan</h1>
        <p class="text-xs text-slate-500 mt-0.5">
          Kelola lowongan aktif, publikasi portal, dan pantau data pelamar yang masuk
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- View Mode Switcher -->
        <div class="inline-flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
          <button
            @click="viewMode = 'grid'"
            :class="[
              'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer',
              viewMode === 'grid' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
            ]"
          >
            <LayoutGrid class="w-3.5 h-3.5" />
            <span>Grid</span>
          </button>
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

    <!-- Filter Tabs & Search Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
      <!-- Status Filter Tabs -->
      <div class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-1 sm:pb-0">
        <button
          @click="statusFilter = 'all'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            statusFilter === 'all'
              ? 'bg-slate-900 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Semua <span class="ml-1 opacity-70">({{ postings.length }})</span>
        </button>

        <button
          @click="statusFilter = 'published'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            statusFilter === 'published'
              ? 'bg-emerald-600 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Tayang Aktif <span class="ml-1 opacity-70">({{ publishedCount }})</span>
        </button>

        <button
          @click="statusFilter = 'draft'"
          :class="[
            'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer shrink-0',
            statusFilter === 'draft'
              ? 'bg-slate-700 text-white'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          Draft <span class="ml-1 opacity-70">({{ draftCount }})</span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full sm:w-80">
        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari judul posisi, lokasi..."
          class="w-full bg-white border border-slate-200 rounded-lg pl-9 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all shadow-2xs"
        />
      </div>
    </div>

    <!-- GRID VIEW (Exact Reference Card Style) -->
    <div v-if="viewMode === 'grid' && filteredPostings.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="job in filteredPostings"
        :key="job.id"
        class="bg-white border-2 border-[#1c5d99]/80 hover:border-[#1c5d99] hover:shadow-md rounded-2xl p-6 flex flex-col justify-between transition-all group"
      >
        <div class="space-y-3.5">
          <!-- Top Row: Company Name & Card Actions -->
          <div class="flex items-start justify-between">
            <p class="text-xs text-slate-500 font-medium leading-none">
              {{ job.company_name || 'PT Complete Selular Group' }}
            </p>
            <div class="flex items-center gap-1.5">
              <button
                @click="togglePublish(job)"
                :class="[
                  'px-2.5 py-0.5 rounded-full text-[10px] font-semibold border transition-colors cursor-pointer',
                  job.is_published
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                    : 'bg-slate-50 text-slate-600 border-slate-200'
                ]"
                :title="job.is_published ? 'Tayang di Portal (Klik untuk ubah)' : 'Draft (Klik untuk tayang)'"
              >
                {{ job.is_published ? 'Tayang' : 'Draft' }}
              </button>
              <button
                @click="openEditModal(job)"
                class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
                title="Edit Lowongan"
              >
                <Edit3 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Job Title & Location -->
          <div>
            <h3
              @click="openEditModal(job)"
              class="text-base font-bold text-slate-900 group-hover:text-blue-700 transition-colors mt-0.5 leading-snug cursor-pointer"
            >
              {{ job.title }}
            </h3>
            <p class="text-xs text-slate-500 mt-1">
              {{ job.location || 'Kota Adm. Cirebon' }}
            </p>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ job.needed_count || 1 }} Posisi &bull; {{ job.applications_count || 0 }} Pelamar
            </p>
          </div>

          <!-- Closing Date -->
          <div class="pt-2 text-xs text-slate-600">
            Penutupan : <span class="text-rose-600 font-bold">{{ job.closing_date_formatted || '31 Agustus 2026' }}</span>
          </div>
        </div>

        <!-- Bottom Date Box -->
        <div class="mt-4 bg-slate-50 border border-slate-100 rounded-xl p-2.5 flex items-center justify-between text-xs text-slate-500">
          <div class="flex items-center gap-2">
            <Calendar class="w-3.5 h-3.5 text-slate-400" />
            <span>Diterbitkan {{ job.created_at || '1 Agustus 2026' }}</span>
          </div>
          <router-link
            :to="{ path: '/admin/job-applications', query: { job_id: job.id } }"
            class="text-blue-600 hover:text-blue-800 font-bold inline-flex items-center gap-1 transition-colors"
          >
            <span>Pelamar</span>
            <ArrowRight class="w-3 h-3" />
          </router-link>
        </div>
      </div>
    </div>

    <!-- TABLE VIEW -->
    <div v-else-if="viewMode === 'table' && filteredPostings.length" class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <th class="py-3 px-4 font-semibold text-[11px]">Posisi Lowongan</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Lokasi</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Status</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Batas Waktu</th>
              <th class="py-3 px-4 font-semibold text-[11px]">Pelamar</th>
              <th class="py-3 px-4 text-right font-semibold text-[11px]">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="job in filteredPostings" :key="job.id" class="hover:bg-slate-50 transition-colors">
              <td class="py-3.5 px-4 align-middle">
                <div class="font-bold text-slate-900 hover:text-blue-600 cursor-pointer text-xs" @click="openEditModal(job)">
                  {{ job.title }}
                </div>
                <div class="text-[11px] text-slate-400 font-mono mt-0.5">#{{ job.id }}</div>
              </td>
              <td class="py-3.5 px-4 align-middle text-slate-700">
                {{ job.location || 'Indonesia' }}
              </td>
              <td class="py-3.5 px-4 align-middle">
                <button
                  @click="togglePublish(job)"
                  :class="[
                    'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border transition-colors cursor-pointer',
                    job.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-600 border-slate-200'
                  ]"
                >
                  <span :class="['w-1.5 h-1.5 rounded-full', job.is_published ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                  <span>{{ job.is_published ? 'Tayang' : 'Draft' }}</span>
                </button>
              </td>
              <td class="py-3.5 px-4 align-middle text-slate-600 text-xs">
                {{ job.closing_date_formatted || '-' }}
              </td>
              <td class="py-3.5 px-4 align-middle font-bold text-slate-900">
                {{ job.applications_count || 0 }} Pelamar
              </td>
              <td class="py-3.5 px-4 align-middle text-right whitespace-nowrap space-x-2">
                <router-link
                  :to="{ path: '/admin/job-applications', query: { job_id: job.id } }"
                  class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-xs font-semibold transition-colors inline-block"
                >
                  Pelamar
                </router-link>
                <button
                  @click="openEditModal(job)"
                  class="px-2.5 py-1 text-blue-600 hover:bg-blue-50 rounded-md text-xs font-semibold transition-colors cursor-pointer"
                >
                  Edit
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white border border-slate-200 rounded-xl p-12 text-center text-xs text-slate-500">
      Tidak ada data lowongan yang sesuai dengan filter atau pencarian Anda.
    </div>

    <!-- Edit Job Modal -->
    <div
      v-if="editingJob"
      class="fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5"
      @click.self="editingJob = null"
    >
      <div class="bg-white rounded-2xl border border-slate-200 w-full max-w-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4.5 border-b border-slate-100 flex items-center justify-between bg-white shrink-0">
          <div>
            <h3 class="text-sm font-bold text-slate-900 leading-tight">Edit Lowongan Pekerjaan</h3>
            <p class="text-xs text-slate-400 mt-0.5 font-normal">Perbarui informasi posisi, kualifikasi, dan detail lainnya.</p>
          </div>
          <button
            @click="editingJob = null"
            class="text-slate-400 hover:text-slate-700 p-1 text-xl font-bold cursor-pointer transition-colors leading-none"
          >
            &times;
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="saveEditJob" class="p-6 space-y-4.5 text-xs overflow-y-auto no-scrollbar flex-1 bg-white">
          <!-- Position Title -->
          <div>
            <label class="block font-bold text-slate-800 mb-1.5">Judul Posisi</label>
            <input
              v-model="editForm.title"
              type="text"
              required
              placeholder="ADMIN GA (GENERAL AFFAIR)"
              class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs font-normal text-slate-900 placeholder-slate-400 focus:outline-none focus:border-slate-400 transition-all"
            />
          </div>

          <!-- Location & Closing Date -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-bold text-slate-800 mb-1.5">Lokasi Kerja / Cabang</label>
              <div class="relative">
                <input
                  v-model="editForm.location"
                  type="text"
                  list="location-options"
                  placeholder="Ocean Space Cirebon"
                  class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 pr-9 transition-all"
                />
                <datalist id="location-options">
                  <option value="Ocean Space Cirebon"></option>
                  <option value="Cirebon"></option>
                  <option value="Jakarta Selatan"></option>
                  <option value="Bandung"></option>
                  <option value="Surabaya"></option>
                  <option value="Head Office"></option>
                  <option value="Remote"></option>
                </datalist>
                <ChevronDown class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              </div>
            </div>
            <div>
              <label class="block font-bold text-slate-800 mb-1.5">Batas Waktu Lamaran</label>
              <input
                v-model="editForm.closing_date"
                type="date"
                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:outline-none focus:border-slate-400 transition-all"
              />
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block font-bold text-slate-800 mb-1.5">Deskripsi Tugas & Tanggung Jawab</label>
            <textarea
              v-model="editForm.description"
              rows="4"
              placeholder="• Seluruh aset perusahaan tercatat.&#10;• Database sesuai kondisi lapangan.&#10;• Tidak ada aset tanpa label.&#10;• Membuat laporan berkala terkait fasilitas & aset."
              class="w-full bg-white border border-slate-200 rounded-lg p-3.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-slate-400 leading-relaxed transition-all resize-none no-scrollbar"
            ></textarea>
          </div>

          <!-- Requirements -->
          <div>
            <label class="block font-bold text-slate-800 mb-1.5">Kualifikasi & Persyaratan</label>
            <textarea
              v-model="editForm.requirements"
              rows="4"
              placeholder="• Laki - laki max 35 tahun&#10;• Memiliki SIM A dan dapat mengendarai mobil&#10;• Bersedia melakukan perjalanan dinas (di luar kota)&#10;• Berpengalaman di posisi admin GA minimal 1 tahun&#10;• Memahami alur administrasi fasilitas & aset perusahaan"
              class="w-full bg-white border border-slate-200 rounded-lg p-3.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-slate-400 leading-relaxed transition-all resize-none no-scrollbar"
            ></textarea>
          </div>

          <!-- Poster / Desain Banner Lowongan -->
          <div>
            <label class="block font-bold text-slate-800 mb-1.5">Desain Banner / Poster Lowongan</label>

            <div class="bg-white border border-slate-200 rounded-lg p-3 flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center shrink-0 overflow-hidden">
                  <img
                    v-if="(thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved"
                    :src="thumbnailPreview || editForm.thumbnail_url"
                    alt="Poster"
                    class="w-full h-full object-cover"
                    @error="editForm.thumbnail_url = null"
                  />
                  <ImageIcon v-else class="w-5 h-5 text-slate-400 stroke-[1.5]" />
                </div>
                <div class="min-w-0">
                  <span class="text-xs font-normal text-slate-800 block truncate">
                    {{ (thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved ? (thumbnailFileName || 'Desain Poster Terpasang') : 'Belum ada file dipilih' }}
                  </span>
                  <span class="text-[11px] text-slate-400 block mt-0.5">JPG, PNG, WEBP. Maks 5MB</span>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <label class="px-3.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 font-normal rounded-lg text-xs cursor-pointer transition-colors border border-slate-200 shadow-2xs">
                  <span>{{ (thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved ? 'Ganti File' : 'Pilih File' }}</span>
                  <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="handleThumbnailChange" />
                </label>
                <button
                  v-if="(thumbnailPreview || editForm.thumbnail_url) && !isThumbnailRemoved"
                  type="button"
                  @click="removeThumbnail"
                  class="px-2.5 py-1.5 text-rose-600 hover:bg-rose-50 text-xs font-normal rounded-lg transition-colors cursor-pointer"
                >
                  Hapus
                </button>
              </div>
            </div>
          </div>

          <!-- Publication Status (Radio Group) -->
          <div>
            <label class="block font-bold text-slate-800 mb-2">Status Publikasi</label>
            <div class="flex items-center gap-6 text-xs text-slate-800">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="radio"
                  name="status_publikasi"
                  value="active"
                  v-model="editForm.publication_state"
                  class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer accent-blue-600"
                />
                <span class="font-medium">Aktif</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="radio"
                  name="status_publikasi"
                  value="inactive"
                  v-model="editForm.publication_state"
                  class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer accent-blue-600"
                />
                <span class="font-medium">Nonaktif</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="radio"
                  name="status_publikasi"
                  value="draft"
                  v-model="editForm.publication_state"
                  class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer accent-blue-600"
                />
                <span class="font-medium">Draft</span>
              </label>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="pt-4 flex items-center justify-end gap-2.5 shrink-0 bg-white">
            <button
              type="button"
              @click="editingJob = null"
              class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-normal rounded-lg border border-slate-200 transition-colors cursor-pointer text-xs"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-5 py-2 bg-[#0c2340] hover:bg-[#07172b] text-white font-normal rounded-lg transition-all cursor-pointer shadow-2xs disabled:opacity-50 text-xs flex items-center gap-1.5"
            >
              <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRekrutmenStore } from '../stores/rekrutmen';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { 
  Search, MapPin, Users, Edit3, ArrowRight,
  LayoutGrid, ListFilter, CheckCircle2, AlertCircle, Calendar,
  Image as ImageIcon, ChevronDown
} from 'lucide-vue-next';

const store = useRekrutmenStore();

const viewMode = ref('grid');
const statusFilter = ref('all');
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
  location: '',
  description: '',
  requirements: '',
  closing_date: '',
  is_published: true,
  thumbnail_url: null,
});

onMounted(() => {
  store.fetchPostings('', false).catch(() => {});
});

const postings = computed(() => store.postings || []);

const publishedCount = computed(() => postings.value.filter(p => p.is_published).length);
const draftCount = computed(() => postings.value.filter(p => !p.is_published).length);

const filteredPostings = computed(() => {
  let list = postings.value;
  
  if (statusFilter.value === 'published') {
    list = list.filter(p => p.is_published);
  } else if (statusFilter.value === 'draft') {
    list = list.filter(p => !p.is_published);
  }

  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(p => 
    (p.title && p.title.toLowerCase().includes(q)) ||
    (p.location && p.location.toLowerCase().includes(q)) ||
    (p.description && p.description.toLowerCase().includes(q)) ||
    (p.requirements && p.requirements.toLowerCase().includes(q))
  );
});

const cleanHtml = (text) => {
  if (!text) return '';
  return text.replace(/<[^>]*>?/gm, '').replace(/&nbsp;/g, ' ').trim();
};

const togglePublish = async (job) => {
  try {
    const res = await store.togglePublishPosting(job.id);
    if (res.success) {
      toastType.value = 'success';
      toastMessage.value = res.message || 'Status lowongan diperbarui.';
      setTimeout(() => { toastMessage.value = null; }, 3000);
    }
  } catch (err) {
    toastType.value = 'error';
    toastMessage.value = 'Gagal memperbarui status publikasi.';
  }
};

const openEditModal = (job) => {
  editingJob.value = job;
  thumbnailFile.value = null;
  thumbnailPreview.value = null;
  thumbnailFileName.value = job.thumbnail_path ? job.thumbnail_path.split('/').pop() : '';
  isThumbnailRemoved.value = false;
  editForm.value = {
    title: job.title || '',
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
    text: 'Apakah Anda yakin ingin menyimpan perubahan pada lowongan ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Simpan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#0c2340',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    customClass: {
      popup: 'rounded-2xl',
      confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      cancelButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
    }
  });

  if (!result.isConfirmed) return;

  isSubmitting.value = true;
  try {
    const formData = new FormData();
    formData.append('title', editForm.value.title);
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
      Swal.fire({
        title: 'Berhasil Disimpan!',
        text: res.message || 'Data lowongan pekerjaan berhasil diperbarui.',
        icon: 'success',
        confirmButtonText: 'OK',
        confirmButtonColor: '#0c2340',
        timer: 2500,
        customClass: {
          popup: 'rounded-2xl',
          confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
        }
      });
    }
  } catch (err) {
    Swal.fire({
      title: 'Gagal Menyimpan',
      text: err.response?.data?.message || 'Terjadi kesalahan saat menyimpan perubahan lowongan.',
      icon: 'error',
      confirmButtonText: 'Tutup',
      confirmButtonColor: '#e11d48',
      customClass: {
        popup: 'rounded-2xl',
        confirmButton: 'rounded-lg px-4 py-2 text-xs font-semibold',
      }
    });
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar,
textarea::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}
.no-scrollbar,
textarea {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
