@extends('frontend.layouts.app')
<link rel="stylesheet" href="{{asset('/assets/css/cart.css')}}"> 
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Accessory Checkout</h1>

    @if($cartItems->isEmpty())
        <p class="text-gray-600">Your cart is empty.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h2 class="text-xl font-bold mb-4">Your Order</h2>
                <table class="table-auto w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="border-b p-4">Product</th>
                            <th class="border-b p-4">Quantity</th>
                            <th class="border-b p-4">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cartItems as $item)
                            <tr>
                                <td class="border-b p-4">
                                    <img src="{{ $item->accessory->image_url }}" alt="{{ $item->accessory->name }}" class="w-16 h-16 object-cover mr-4 inline">
                                    {{ $item->accessory->name }}
                                </td>
                                <td class="border-b p-4">{{ $item->quantity }}</td>
                                <td class="border-b p-4">
                                    {{ number_format($item->accessory->price * $item->quantity, 0, ',', '.') }} VND
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-6">
                    <h3 class="text-xl font-semibold">Total: <span id="total-price">{{ number_format($totalPrice, 0, ',', '.') }} VND</span></h3>
                </div>
            </div>
            <div>
                <h2 class="text-xl font-bold mb-4">Shipping Information</h2>
                <form id="checkout-form" action="{{ route('accessories.checkout.process') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ $user->name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="{{ $user->email }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ $user->accountdetail->phone ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>
                    <div class="mb-4">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>{{ $user->accountdetail->address ?? '' }}</textarea>
                    </div>
                    @foreach($cartItems as $item)
                        <input type="hidden" name="items[]" value="{{ $item->accessory_id }}">
                    @endforeach
                    <input type="hidden" name="total_price" value="{{ $totalPrice }}">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Place Order</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
