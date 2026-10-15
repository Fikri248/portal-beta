<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Portal TAT

Aplikasi Register Tim Asesmen Terpadu (TAT) BNNP Jawa Timur — kelola data Register TAT, Draft, Arsip, Laporan, dan Audit Log sesuai kewenangan BNNP / BNNK.

### Akun demo (hasil `php artisan db:seed`)

Semua akun demo memakai password yang sama: `password`.

| Role | Nama | Email | Password |
|------|------|-------|----------|
| BNNP | BNNP Jawa Timur | `bnnp_jatim@bnn.go.id` | `password` |
| BNNK | BNN Kota Malang | `bnnk_kota_malang@bnn.go.id` | `password` |
| BNNK | BNN Kota Kediri | `bnnk_kota_kediri@bnn.go.id` | `password` |
| BNNK | BNN Kota Batu | `bnnk_kota_batu@bnn.go.id` | `password` |
| BNNK | BNN Kota Surabaya | `bnnk_kota_surabaya@bnn.go.id` | `password` |
| BNNK | BNN Kota Mojokerto | `bnnk_kota_mojokerto@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Nganjuk | `bnnk_kab_nganjuk@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Blitar | `bnnk_kab_blitar@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Sidoarjo | `bnnk_kab_sidoarjo@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Tulungagung | `bnnk_kab_tulungagung@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Kediri | `bnnk_kab_kediri@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Malang | `bnnk_kab_malang@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Lumajang | `bnnk_kab_lumajang@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Gresik | `bnnk_kab_gresik@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Trenggalek | `bnnk_kab_trenggalek@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Sumenep | `bnnk_kab_sumenep@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Pasuruan | `bnnk_kab_pasuruan@bnn.go.id` | `password` |
| BNNK | BNN Kabupaten Tuban | `bnnk_kab_tuban@bnn.go.id` | `password` |

> Akun di atas hanya fixture development lokal (lihat `database/seeders/DatabaseSeeder.php`), bukan kredensial production. Ganti password sebelum dipakai di server nyata.

### Cara menjalankan lokal

```text
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

### Cara pull update terbaru dari repo

```text
cd C:\laragon\www\portal-TAT
git pull origin main
composer install
npm install
php artisan migrate
npm run build
```

Kalau ada perubahan lokal yang belum di-commit dan `git pull` ditolak:

```text
git stash push -m "sementara"
git pull origin main
git stash pop
composer install
npm install
php artisan migrate
npm run build
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Runtime Requirements

The project requires **PHP 8.3+ on a 64-bit runtime**.

Required PHP extensions:

`ctype`, `dom`, `fileinfo`, `filter`, `gd`, `iconv`, `libxml`, `mbstring`, `simplexml`, `xml`, `xmlreader`, `xmlwriter`, `zip`, and `zlib`.

The application no longer directly uses `ZipArchive` or `DOMDocument` for spreadsheet handling, but PhpSpreadsheet and Dompdf still require the related PHP extensions.

On Laragon or another local PHP environment, verify the runtime with:

```text
php -m
composer check-platform-reqs
```

## Local Development Seed Workflow

When changing database schema or development seed data:

1. Run migrations.
2. Reseed development data.
3. Verify the Register, Draft, and Arsip counts and ownership.
4. Run the automated tests.

For a destructive local reset, use `php artisan migrate:fresh --seed`. If migration history must be preserved, use `php artisan migrate` followed by `php artisan db:seed`. Do not use `migrate:fresh` for production environments. Seeded accounts and the local `password` value are development fixtures only, not production credentials.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
