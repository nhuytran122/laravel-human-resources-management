@component('mail::message')
# Lời mời nhận việc

Xin chào **{{ $job_offer->job_application->full_name }}**,

Chúng tôi rất vui mừng thông báo rằng bạn đã vượt qua vòng phỏng vấn và được đề xuất cho vị trí **{{ $job_offer->position->name }}**.

### Ngày bắt đầu dự kiến:
**{{ \Carbon\Carbon::parse($job_offer->start_date)->format('d/m/Y') }}**

### Hạn phản hồi:
**{{ \Carbon\Carbon::parse($job_offer->offer_deadline)->format('d/m/Y') }}**

@isset($job_offer->notes)
> Ghi chú: {{ $job_offer->notes }}
@endisset

Trân trọng,  
**{{ config('app.company') }}**
@endcomponent
