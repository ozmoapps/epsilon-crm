@extends('layouts.print')

@section('title', 'Sözleşme Yazdır - ' . $contract->contract_no)

@section('content')
    @include('contracts.partials._print', ['contract' => $contract, 'companyProfile' => $companyProfile ?? null])
@endsection
