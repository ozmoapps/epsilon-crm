@extends('layouts.print')

@section('title', $contract->contract_no)

@section('content')
    {{-- Reuse the same print partial for PDF to ensure identical layout --}}
    @include('contracts.partials._print', ['contract' => $contract, 'companyProfile' => $companyProfile ?? \App\Models\CompanyProfile::current()])
@endsection
