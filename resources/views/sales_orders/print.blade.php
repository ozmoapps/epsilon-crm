@extends('layouts.print')

@section('title', 'Sipariş Yazdır - #' . $salesOrder->order_no)

@section('content')
    @include('sales_orders.partials._print', ['salesOrder' => $salesOrder, 'companyProfile' => $companyProfile, 'bankAccounts' => $bankAccounts])
@endsection
