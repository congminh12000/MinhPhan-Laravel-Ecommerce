@php
  $sepayService = new \Plugin\Sepay\Services\SepayService($order);
  $formFields = $sepayService->getCheckoutFormFields();
  $actionUrl = $sepayService->getCheckoutActionUrl();
@endphp

<div class="mt-4">
  <div class="alert alert-info mb-4">
    {{ __('Sepay::common.checkout_hint') }}
  </div>

  <div class="card border-0 bg-light">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <h5 class="mb-2">{{ __('Sepay::common.checkout_title') }}</h5>
          <div class="text-muted">{{ __('Sepay::common.checkout_desc') }}</div>
        </div>

        <form id="sepay-payment-form" method="POST" action="{{ $actionUrl }}" class="m-0">
          @foreach ($formFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
          @endforeach

          <button type="submit" class="btn btn-primary btn-lg">
            {{ __('Sepay::common.checkout_button') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
