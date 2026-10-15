<?php

namespace App\Enums;

enum AuditLogAction: string
{
    case AuthLogin = 'auth.login';
    case AuthLogout = 'auth.logout';
    case TatCreatedDraft = 'tat.created_draft';
    case TatCreatedFinal = 'tat.created_final';
    case TatUpdatedDraft = 'tat.updated_draft';
    case TatUpdatedFinal = 'tat.updated_final';
    case TatFinalized = 'tat.finalized';
    case TatArchived = 'tat.archived';
    case TatRestored = 'tat.restored';
    case TatPhotoUploaded = 'tat.photo_uploaded';
    case TatPhotoReplaced = 'tat.photo_replaced';
    case ImportCompleted = 'import.completed';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserActivated = 'user.activated';
    case UserDeactivated = 'user.deactivated';
    case ProfileUpdated = 'profile.updated';
    case PasswordChanged = 'password.changed';
    case ReportExportedExcel = 'report.exported_excel';
    case ReportExportedPdf = 'report.exported_pdf';

    public function label(): string
    {
        return match ($this) {
            self::AuthLogin => 'Login berhasil',
            self::AuthLogout => 'Logout',
            self::TatCreatedDraft => 'Draft TAT dibuat',
            self::TatCreatedFinal => 'Data TAT final dibuat',
            self::TatUpdatedDraft => 'Draft TAT diperbarui',
            self::TatUpdatedFinal => 'Data TAT final diperbarui',
            self::TatFinalized => 'Draft TAT difinalisasi',
            self::TatArchived => 'Data TAT diarsipkan',
            self::TatRestored => 'Data TAT dipulihkan',
            self::TatPhotoUploaded => 'Foto klien diunggah',
            self::TatPhotoReplaced => 'Foto klien diganti',
            self::ImportCompleted => 'Import Register TAT selesai',
            self::UserCreated => 'User dibuat',
            self::UserUpdated => 'User diperbarui',
            self::UserActivated => 'User diaktifkan',
            self::UserDeactivated => 'User dinonaktifkan',
            self::ProfileUpdated => 'Profil diperbarui',
            self::PasswordChanged => 'Password diubah',
            self::ReportExportedExcel => 'Laporan Excel diekspor',
            self::ReportExportedPdf => 'Laporan PDF diekspor',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $action): array => [$action->value => $action->label()])
            ->all();
    }
}
