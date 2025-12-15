@extends('frontend.profilepage.viewprofile')
@php
    use Illuminate\Support\Facades\Auth;
    $user = Auth::guard('account')->user();
@endphp
@section('main')
<main class="flex-1 p-8 mt-[50px]">
    <h1 class="text-2xl font-semibold text-gray-800">Thông tin xuất hóa đơn</h1>
    <div class="mt-8 bg-white p-6 rounded-lg shadow-md">
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
            <p class="font-bold">Lưu ý:</p>
            <p>Thông tin dưới đây sẽ được sử dụng mặc định để xuất hóa đơn cho các đơn hàng của bạn. Vui lòng cập nhật chính xác.</p>
        </div>
        
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 font-medium">Tên người mua / Công ty</label>
                    <input type="text" name="name" class="w-full mt-1 px-4 py-2 border rounded-md"
                        value="{{ $user->name }}">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium">Email nhận hóa đơn</label>
                    <input type="email" name="email" class="w-full mt-1 px-4 py-2 border rounded-md"
                        value="{{ $user->email }}">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium">Số điện thoại</label>
                    <input type="text" name="phone" class="w-full mt-1 px-4 py-2 border rounded-md"
                        value="{{ $user->phone }}">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium">Địa chỉ xuất hóa đơn</label>
                    <input type="text" name="address" class="w-full mt-1 px-4 py-2 border rounded-md"
                        value="{{ $user->address }}">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 mr-4">Lưu thông tin</button>
            </div>
        </form>
    </div>
</main>
@endsection
