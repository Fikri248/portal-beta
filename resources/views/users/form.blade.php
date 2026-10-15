@php($isEdit = $user->exists)
@php($isBnnkUser = $isEdit && $user->isBnnk())
@php($emailLocal = \App\Support\BnnEmail::localPart(old('email', $user?->email)))

<form method="POST" action="{{ $formAction }}" class="space-y-6">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <x-card title="Informasi Akun" description="Gunakan identitas yang mudah dikenali oleh pengguna dan administrator.">
        <div class="grid gap-5 md:grid-cols-2">
            <x-form.input name="name" label="Nama" :value="$user?->name" required autocomplete="name" />
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email <span class="text-red-500">*</span></label>
                <div class="flex h-10 overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm transition focus-within:border-slate-500 focus-within:ring-2 focus-within:ring-slate-200">
                    <input id="email" name="email" type="text" value="{{ $emailLocal }}" required inputmode="email" autocomplete="email" class="min-w-0 flex-1 border-0 bg-transparent px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400" placeholder="bnnkota_surabaya">
                    <span class="inline-flex items-center border-l border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-500">@bnn.go.id</span>
                </div>
                <p class="mt-1.5 text-xs text-slate-500">Masukkan bagian sebelum @bnn.go.id.</p>
                <x-form.error for="email" />
            </div>
        </div>
    </x-card>

    <x-card title="Hak Akses" description="Role akun ditentukan oleh alur administrasi. BNNK wajib terhubung dengan satu unit kerja.">
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <p class="mb-1.5 block text-sm font-medium text-slate-700">Role</p>
                <div class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-800">
                    {{ $isEdit ? $user->role->label() : 'BNNK' }}
                </div>
                <p class="mt-1.5 text-xs text-slate-500">{{ $isEdit ? 'Role akun tidak dapat diubah dari halaman ini.' : 'Akun baru dari alur ini selalu dibuat sebagai BNNK.' }}</p>
            </div>

            @if(!$isEdit || $isBnnkUser)
                <div>
                    <x-form.select name="bnnk_id" label="BNNK" :options="$bnnks->pluck('name', 'id')->all()" :value="$user?->bnnk_id" placeholder="Pilih BNNK" required data-bnnk-select="true" />
                    @if(!$isEdit)
                        <button type="button" data-bnnk-create-toggle aria-expanded="false" class="mt-2 text-sm font-semibold text-slate-700 underline decoration-slate-300 underline-offset-4 transition hover:text-slate-900 hover:decoration-slate-500">+ Tambah BNNK Baru</button>
                        <div data-bnnk-create-panel class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div data-bnnk-create data-action="{{ route('bnnks.store') }}" class="space-y-3">
                                <div>
                                    <label for="new-bnnk-name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama Unit / Kota</label>
                                    <input id="new-bnnk-name" type="text" data-bnnk-name maxlength="255" class="block h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200" placeholder="BNNK Kota Sidoarjo">
                                    <p data-bnnk-error class="mt-1.5 hidden text-sm text-red-600"></p>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" data-bnnk-cancel class="inline-flex h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Batal</button>
                                    <button type="button" data-bnnk-submit class="inline-flex h-9 items-center rounded-lg bg-slate-900 px-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">Tambah BNNK</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div>
                    <p class="mb-1.5 block text-sm font-medium text-slate-700">BNNK</p>
                    <div class="flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-500">Tidak terhubung ke BNNK</div>
                </div>
            @endif
        </div>
    </x-card>

    <x-card title="Keamanan" description="Password disimpan menggunakan hashing Laravel dan tidak pernah ditampilkan kembali.">
        <div class="grid gap-5 md:grid-cols-2">
            <x-form.input name="password" label="Password" type="password" :required="!$isEdit" autocomplete="new-password" :help="$isEdit ? 'Kosongkan jika password tidak diubah.' : 'Minimal 8 karakter.'" />
            <x-form.input name="password_confirmation" label="Konfirmasi Password" type="password" :required="!$isEdit" autocomplete="new-password" />
        </div>
    </x-card>

    <x-card title="Status" description="User nonaktif tidak dapat login atau melanjutkan sesi aktif.">
        <div class="max-w-md">
            <x-form.select name="is_active" label="Status Akun" :options="['1' => 'Aktif', '0' => 'Nonaktif']" :value="$isEdit ? (int) $user->is_active : 1" required />
        </div>
    </x-card>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('users.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
        <x-button type="submit">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan User' }}</x-button>
    </div>
</form>
