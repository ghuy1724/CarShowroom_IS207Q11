@extends('frontend.profilepage.viewprofile')

@section('main')
<div class="bg-gray-100 py-6">
	<div class="container mx-auto bg-white rounded-lg shadow-md p-6">
		<!-- Tab Nội dung Xe Ô Tô -->
		<div id="carsContainer" class="tabcontent" style="display: block;">
			<h1 class="text-2xl mb-6">Xe của tôi</h1>
			@if ($customerCars && $customerCars->isNotEmpty())
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
					@foreach ($customerCars as $payment)
						@if ($payment->order && $payment->order->salesCar && $payment->order->salesCar->carDetails)
							<!-- Card hiển thị thông tin xe -->
							<div class="bg-gray-100 rounded-lg shadow-md p-4">
								<img src="{{ $payment->order->salesCar->carDetails->image_url ?? 'default-image.jpg' }}"
									alt="Car Image" class="w-full h-48 object-cover rounded-t-md">
								<div class="p-4">
									<h2 class="text-lg font-semibold mb-2">{{ $payment->order->salesCar->carDetails->name }}</h2>
									<p><strong>Hãng:</strong> {{ $payment->order->salesCar->carDetails->brand }}</p>
									<p><strong>Model:</strong> {{ $payment->order->salesCar->carDetails->model }}</p>
									<p><strong>Giá:</strong> {{ number_format($payment->order->salesCar->sale_price, 0, ',', '.') }}
										VNĐ
									</p>
									<div class="mt-4">
										<a href="{{route('customer.car.detail', ['id' => $payment->order->order_id])}}"
											class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Xem chi
											tiết</a>
									</div>
								</div>
							</div>
						@endif
					@endforeach
				</div>
			@else
				<p class="text-gray-500">Không tìm thấy xe nào thuộc về bạn.</p>
			@endif
		</div>
	</div>
</div>
@endsection