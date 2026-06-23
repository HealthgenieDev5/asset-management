@if (session('success'))
    <script>document.addEventListener('DOMContentLoaded', () => toastr.success(@js(session('success'))));</script>
@endif

@if (session('error'))
    <script>document.addEventListener('DOMContentLoaded', () => toastr.error(@js(session('error'))));</script>
@endif
