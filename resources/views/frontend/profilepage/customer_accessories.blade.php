@extends('frontend.profilepage.viewprofile')

@section('main')
<div class="bg-gray-100 py-6">
    <div class="container mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl mb-6">Phụ kiện của tôi</h1>
        @if ($customerAccessories && $customerAccessories->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($customerAccessories as $accessoryData)
                    <div class="bg-gray-100 rounded-lg shadow-md p-4">
                        <img src="{{ $accessoryData->accessory->image_url ?? 'default-image.jpg' }}" alt="Accessory Image"
                            class="w-full h-48 object-cover rounded-t-md">
                        <div class="p-4">
                            <h2 class="text-lg font-semibold mb-2">{{ $accessoryData->accessory->name }}</h2>
                            <p><strong>Price:</strong> {{ number_format($accessoryData->accessory->price, 0, ',', '.') }} VNĐ</p>
                            <p><strong>Quantity:</strong> {{ $accessoryData->quantity }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500">{{ $message ?? 'You have not purchased any accessories yet.' }}</p>
        @endif
    </div>
</div>
@endsection
