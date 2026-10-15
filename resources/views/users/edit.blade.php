@extends('layouts.app')

@section('title', 'Edit User — Portal TAT')

@section('content')
<x-page-header title="Edit User" description="Perbarui informasi, unit kerja, password, atau status akun pengguna." />
@include('users.form', ['user' => $user, 'formAction' => route('users.update', $user), 'formMethod' => 'PUT'])
@endsection
