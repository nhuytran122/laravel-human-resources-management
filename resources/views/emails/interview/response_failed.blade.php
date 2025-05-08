@component('mail::message')

Xin chào **{{ $interview->job_application->full_name }}**,

Chúng tôi chân thành cảm ơn bạn đã dành thời gian tham gia buổi phỏng vấn với vị trí **{{ $interview->job_application->job_posting->position->name }}** tại {{ config('app.company') }}.

Sau quá trình cân nhắc kỹ lưỡng, rất tiếc là chúng tôi chưa thể lựa chọn bạn cho vị trí này ở thời điểm hiện tại. Tuy nhiên, chúng tôi đánh giá cao tinh thần cầu tiến và những nỗ lực mà bạn đã thể hiện trong buổi phỏng vấn.

@isset($interview->notes)
> **Ghi chú từ buổi phỏng vấn:**  
> {{ $interview->notes }}
@endisset

Hy vọng chúng tôi sẽ có cơ hội được đồng hành cùng bạn trong những đợt tuyển dụng sắp tới.

Chúc bạn luôn thành công trên hành trình sự nghiệp của mình!

Trân trọng,  
**{{ config('app.company') }}**
@endcomponent
