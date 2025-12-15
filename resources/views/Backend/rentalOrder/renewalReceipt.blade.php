@extends('Backend.dashboard.layout')

@section('content')

    <x-breadcrumbs breadcrumb="rentalReceipt" />

    <div class="container mx-auto p-4">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Quản lý hóa đơn thuê xe</h2>

        <!-- Nút gia hạn thủ công -->
        <div class="mb-6">
            <a 
                href="{{ route('rental.extend.manual.search') }}" 
                class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md"
            >
                Gia hạn thủ công
            </a>
        </div>

        <!-- Danh sách hóa đơn -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-medium text-gray-700 mb-4">Danh sách hóa đơn</h3>
            <table class="table-auto w-full border-collapse border border-gray-200 text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border border-gray-200 px-4 py-2">Mã hóa đơn</th>
                        <th class="border border-gray-200 px-4 py-2">Tên xe</th>
                        <th class="border border-gray-200 px-4 py-2">Trạng thái</th>
                        <th class="border border-gray-200 px-4 py-2">Ngày bắt đầu</th>
                        <th class="border border-gray-200 px-4 py-2">Ngày kết thúc</th>
                        <th class="border border-gray-200 px-4 py-2">Tổng chi phí</th>
                        <th class="border border-gray-200 px-4 py-2">Yêu cầu gia hạn</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rentalReceipts as $receipt)
                        @php
                            $rowSpan = $receipt->renewals->isNotEmpty() ? $receipt->renewals->count() + 1 : 1;
                        @endphp
                        
                        {{-- Dòng chính hiển thị thông tin hóa đơn --}}
                        <tr class="hover:bg-gray-100 border-b border-gray-200">
                            <td class="border border-gray-200 px-4 py-2" rowspan="{{ $rowSpan }}">{{ $receipt->receipt_id }}</td>
                            <td class="border border-gray-200 px-4 py-2" rowspan="{{ $rowSpan }}">{{ $receipt->rentalCar->carDetails->name ?? 'Không có thông tin' }}</td>
                            <td class="border border-gray-200 px-4 py-2" rowspan="{{ $rowSpan }}">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    @if($receipt->status === 'Active')
                                        bg-green-100 text-green-800
                                    @else
                                        bg-red-100 text-red-800
                                    @endif">
                                    {{ $receipt->status === 'Active' ? 'Khả dụng' : 'Không khả dụng' }}
                                </span>
                            </td>
                            <td class="border border-gray-200 px-4 py-2" rowspan="{{ $rowSpan }}">{{ $receipt->rental_start_date }}</td>
                            <td class="border border-gray-200 px-4 py-2" rowspan="{{ $rowSpan }}">{{ $receipt->rental_end_date }}</td>
                            <td class="border border-gray-200 px-4 py-2" rowspan="{{ $rowSpan }}">{{ number_format($receipt->total_cost, 0, ',', '.') }} VND</td>
                            
                            {{-- Cột Yêu cầu gia hạn (Header/Empty if none) --}}
                            @if($receipt->renewals->isEmpty())
                                <td class="border border-gray-200 px-4 py-2 text-gray-400 italic">Không có yêu cầu</td>
                            @else
                                {{-- Tiêu đề hoặc dòng trống cho dòng đầu tiên --}}
                                <td class="border border-gray-200 px-4 py-2 bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wide">
                                    Chi tiết các yêu cầu bên dưới
                                </td>
                            @endif
                        </tr>

                        {{-- Các dòng hiển thị từng yêu cầu gia hạn --}}
                        @foreach($receipt->renewals as $renewal)
                            <tr class="hover:bg-gray-50">
                                <td class="border border-gray-200 px-4 py-2 flex items-center justify-between">
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-700">Yêu cầu #{{ $renewal->renewal_id }}:</span>
                                        <span class="ml-2 px-2 py-0.5 rounded text-xs
                                            @if($renewal->status === 'Approved') bg-blue-100 text-blue-800
                                            @elseif($renewal->status === 'Pending') bg-yellow-100 text-yellow-800
                                            @elseif($renewal->status === 'Completed') bg-green-100 text-green-800
                                            @elseif($renewal->status === 'Failed' || $renewal->status === 'Rejected') bg-red-100 text-red-800
                                            @endif">
                                            @if($renewal->status === 'Approved')
                                                Đã duyệt
                                            @elseif($renewal->status === 'Pending')
                                                Chờ xử lý
                                            @elseif($renewal->status === 'Completed')
                                                Hoàn thành
                                            @elseif($renewal->status === 'Failed')
                                                Thất bại
                                            @elseif($renewal->status === 'Rejected')
                                                Từ chối
                                            @else
                                                {{ $renewal->status }}
                                            @endif
                                        </span>
                                        <span class="ml-2 text-gray-500 text-xs">
                                            ({{ $receipt->rental_price_per_day > 0 
                                                ? round($renewal->renewal_cost / $receipt->rental_price_per_day) 
                                                : 0 }} ngày)
                                        </span>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        @if($renewal->status === 'Pending')
                                            <a href="{{ route('rental.renewals.show', $renewal->renewal_id) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-xs font-medium border border-blue-600 rounded px-2 py-1">
                                                Xử lý
                                            </a>
                                        @elseif($renewal->status === 'Approved')
                                            <form action="{{ route('rental.renewals.markFailed', $renewal->renewal_id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="text-red-500 hover:text-red-700 text-xs border border-red-500 rounded px-2 py-1"
                                                        onclick="return confirm('Bạn có chắc muốn đánh dấu thất bại không?')">
                                                    Thất bại
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
