<?php
namespace Database\Factories;
use App\Enums\TatRegisterStatus;
use App\Models\{Bnnk,TatRegister};
use Illuminate\Database\Eloquent\Factories\Factory;
/** @extends Factory<TatRegister> */
class TatRegisterFactory extends Factory { protected $model=TatRegister::class; public function definition(): array { return ['bnnk_id'=>Bnnk::factory(),'status'=>TatRegisterStatus::Draft,'nama'=>fake()->name(),'nik'=>null,'foto_klien'=>null,'keterangan'=>null]; } public function configure(): static { return $this->afterMaking(fn(TatRegister $model) => $model->forceFill(['bnnk_id'=>$model->bnnk_id,'status'=>$model->status])); } public function draft(): static { return $this->state(['status'=>TatRegisterStatus::Draft]); } public function final(): static { return $this->state(['status'=>TatRegisterStatus::Final]); } }
