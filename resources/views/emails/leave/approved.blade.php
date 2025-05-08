@component('mail::message')
# Xin chào {{ $leaveRequest->send_by->full_name }},

Yêu cầu nghỉ phép của bạn từ **{{ $leaveRequest->start_date }} đến {{ $leaveRequest->end_date }}** đã được duyệt.
**Loại nghỉ phép:** {{ $leaveRequest->leave_type->name }}
**Lý do:** {{ $leaveRequest->reason }}

@component('mail::button', ['url' => 'http://127.0.0.1:8082/api/leave-requests/' . $leaveRequest->id])
Xem chi tiết
@endcomponent

Xin cảm ơn,<br>
{{ config('app.name') }}
@endcomponent
