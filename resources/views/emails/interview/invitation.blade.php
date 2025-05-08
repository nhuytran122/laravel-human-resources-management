@component('mail::message')
# {{ $isUpdate ? 'CẬP NHẬT LỊCH PHỎNG VẤN' : 'THƯ MỜI PHỎNG VẤN' }}

Kính gửi **{{ $interview->job_application->full_name }}**,

@if ($isUpdate)
Chúng tôi xin thông báo rằng **lịch phỏng vấn của bạn đã được cập nhật** với các thông tin mới nhất như sau. Vui lòng kiểm tra kỹ để đảm bảo bạn có mặt đúng thời gian và địa điểm.
@else
Cảm ơn bạn đã quan tâm và ứng tuyển vào vị trí **{{ $interview->job_application->job_posting->position->name }}** tại **{{ config('app.company') }}**.

Chúng tôi xin trân trọng mời bạn tham dự buổi **phỏng vấn** với thông tin chi tiết như sau:
@endif

---

### Thông tin phỏng vấn

- **Thời gian:** {{ \Carbon\Carbon::parse($interview->interview_date)->format('H:i \n\g\à\y d/m/Y') }}
- **Địa điểm:** {{ config('app.company_address') }}
@if (!empty($interview->notes))
- **Ghi chú:** {{ $interview->notes }}
@endif

### Người phỏng vấn

@if ($interview->interviewers && $interview->interviewers->count() > 0)
@foreach ($interview->interviewers as $interviewer)
- {{ $interviewer->full_name }}
@endforeach
@else
*Thông tin đang được cập nhật*
@endif

---

Nếu bạn có bất kỳ thắc mắc hoặc không thể tham gia đúng giờ, vui lòng liên hệ với chúng tôi qua email **hr@company.com** hoặc số điện thoại hỗ trợ.

@if ($isUpdate)
Mong bạn xác nhận và chuẩn bị tốt cho buổi phỏng vấn.
@else
Chúc bạn có một buổi phỏng vấn thành công!
@endif

Trân trọng,  
**{{ config('app.company') }}**

@endcomponent
