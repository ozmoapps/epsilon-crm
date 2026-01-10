@extends('layouts.print')

@section('title', 'Teklif Yazdır - ' . $quote->quote_no)

@section('content')
    @include('quotes.partials._print', ['quote' => $quote, 'companyProfile' => $companyProfile, 'bankAccounts' => $bankAccounts])
@endsection
