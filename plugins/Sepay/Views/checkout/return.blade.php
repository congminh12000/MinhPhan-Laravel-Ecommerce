@extends('layout.master')

@section('body-class', 'page-account-order-info')
@section('title', $title)

@section('content')
  <div class="container">
    <div class="card mt-5 w-max-1000 mx-auto">
      <div class="card-body">
        <div class="alert alert-{{ $alert }} mb-4">
          <h4 class="mb-2">{{ $title }}</h4>
          <div>{{ $message }}</div>
        </div>

        @if ($order)
          @include('shared.order_info')

          @if ($retry_url && $order->status === 'unpaid')
            <div class="mt-4">
              <a href="{{ $retry_url }}" class="btn btn-primary">{{ __('Sepay::common.retry_payment') }}</a>
            </div>
          @endif
        @else
          <a href="{{ shop_route('home.index') }}" class="btn btn-primary">{{ __('common.return') }}</a>
        @endif
      </div>
    </div>
  </div>
@endsection
