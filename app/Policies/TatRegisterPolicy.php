<?php
namespace App\Policies;
use App\Enums\UserRole;
use App\Models\{TatRegister, User};
class TatRegisterPolicy { public function viewAny(User $user): bool { return true; } public function view(User $user,TatRegister $record): bool { return $user->role===UserRole::Bnnp || $user->bnnk_id===$record->bnnk_id; } public function create(User $user): bool { return $user->role===UserRole::Bnnp || ($user->role===UserRole::Bnnk && $user->bnnk_id!==null); } public function update(User $user,TatRegister $record): bool { return $this->view($user,$record); } public function delete(User $user,TatRegister $record): bool { return $this->view($user,$record); } public function restore(User $user,TatRegister $record): bool { return $this->view($user,$record); } }
