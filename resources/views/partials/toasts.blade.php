<script>
    @if (session('success'))
        toastr.success(@js(session('success')));
    @endif
    @if (session('error'))
        toastr.error(@js(session('error')));
    @endif
    @if (session('warning'))
        toastr.warning(@js(session('warning')));
    @endif
</script>
