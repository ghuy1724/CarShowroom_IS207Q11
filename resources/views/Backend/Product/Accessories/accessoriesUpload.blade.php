@extends('Backend.dashboard.layout')

@section('content')
<x-breadcrumbs breadcrumb="accessories.create" />

<div class="container">
    <h2>Thêm phụ kiện từ file Excel</h2>

    <!-- Link to Download Excel Template -->
    <div class="mb-3">
        <a href="{{ route('accessories.template') }}" class="btn btn-secondary" download>
            Tải về mẫu Excel
        </a>
    </div>

    <!-- Form to Upload Excel File -->
    <form action="{{ route('accessories.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="file">Chọn file Excel</label>
            <input type="file" class="form-control" id="file" name="file" required>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Tải lên và thêm phụ kiện</button>
    </form>

    <!-- Instructions Section -->
    <div style="background-color: #e3f2fd; border: 1px solid #90caf9; padding: 15px; border-radius: 4px; margin-top: 15px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
		<p style="color: #000000; font-weight: bold; margin-bottom: 10px;">Hướng dẫn:</p>
		<ul style="color: #000000; margin-bottom: 0;">
			<li>1. Tải về file mẫu Excel bằng cách nhấn vào nút <strong>"Tải về file mẫu Excel"</strong>.</li>
			<li>2. Điền thông tin xe vào file Excel theo đúng định dạng.</li>
			<li>3. Chọn file Excel đã hoàn thành và nhấn <strong>"Tải lên và Thêm Xe"</strong>.</li>
		</ul>
	</div>
</div>
@endsection
