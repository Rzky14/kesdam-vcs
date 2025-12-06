@extends('layouts.app')

@section('title', 'Preferensi Notifikasi')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Preferensi Notifikasi</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('notifications.preferences.update') }}" method="POST">
                        @csrf

                        <!-- Approval Request Notifications -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Notifikasi Persetujuan Dokumen</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="approvalRequestEnabled" 
                                           name="approval_request_enabled" value="1"
                                           @checked($preferences->approval_request_enabled)>
                                    <label class="form-check-label" for="approvalRequestEnabled">
                                        Aktifkan notifikasi dalam aplikasi
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="approvalRequestEmail" 
                                           name="approval_request_email" value="1"
                                           @checked($preferences->approval_request_email)>
                                    <label class="form-check-label" for="approvalRequestEmail">
                                        Aktifkan notifikasi email
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Document Status Change Notifications -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Notifikasi Perubahan Status Dokumen</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="documentStatusEnabled" 
                                           name="document_status_enabled" value="1"
                                           @checked($preferences->document_status_enabled)>
                                    <label class="form-check-label" for="documentStatusEnabled">
                                        Aktifkan notifikasi dalam aplikasi
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="documentStatusEmail" 
                                           name="document_status_email" value="1"
                                           @checked($preferences->document_status_email)>
                                    <label class="form-check-label" for="documentStatusEmail">
                                        Aktifkan notifikasi email
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Reminder Notifications -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Pengingat Jadwal</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="scheduleReminderEnabled" 
                                           name="schedule_reminder_enabled" value="1"
                                           @checked($preferences->schedule_reminder_enabled)>
                                    <label class="form-check-label" for="scheduleReminderEnabled">
                                        Aktifkan pengingat jadwal
                                    </label>
                                </div>

                                <div class="mb-3">
                                    <label for="scheduleReminderTiming" class="form-label">Kapan mengingatkan?</label>
                                    <select class="form-select" id="scheduleReminderTiming" 
                                            name="schedule_reminder_timing">
                                        @foreach(\App\Models\UserNotificationPreference::getTimingOptions() as $value => $label)
                                            <option value="{{ $value }}" 
                                                    @selected($preferences->schedule_reminder_timing === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="scheduleReminderEmail" 
                                           name="schedule_reminder_email" value="1"
                                           @checked($preferences->schedule_reminder_email)>
                                    <label class="form-check-label" for="scheduleReminderEmail">
                                        Aktifkan notifikasi email untuk pengingat
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- System Notifications -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Notifikasi Sistem</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="systemNotificationsEnabled" 
                                           name="system_notifications_enabled" value="1"
                                           @checked($preferences->system_notifications_enabled)>
                                    <label class="form-check-label" for="systemNotificationsEnabled">
                                        Aktifkan notifikasi sistem penting
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Simpan Preferensi
                            </button>
                            <a href="{{ route('notifications.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
