@extends('layouts.app')

@section('title', 'Tambah User — Portal TAT')

@section('content')
<x-page-header title="Tambah User" description="Buat akun BNNK baru dan hubungkan dengan unit kerja pengguna." />
@include('users.form', ['user' => $user, 'formAction' => route('users.store'), 'formMethod' => 'POST'])
@endsection
