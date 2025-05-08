@component('mail::message')
# Kính gửi {{ $job_application->full_name ?? 'Ứng viên' }},

Chúng tôi cảm ơn bạn đã dành thời gian quan tâm và ứng tuyển vào vị trí **{{ $job_application->job_posting->position->name ?? 'đã ứng tuyển' }}** tại công ty chúng tôi.

Sau quá trình xem xét kỹ lưỡng, chúng tôi rất tiếc phải thông báo rằng hiện tại hồ sơ của bạn chưa phù hợp với yêu cầu tuyển dụng của vị trí này.

Chúng tôi thực sự trân trọng sự quan tâm của bạn đối với công ty và mong rằng sẽ có cơ hội hợp tác trong tương lai khi có những vị trí phù hợp hơn.

Trân trọng,  
**Phòng Nhân sự**  
{{ config('app.name') }}

@endcomponent
